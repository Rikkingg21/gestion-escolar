<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Periodo;
use App\Models\Matricula;
use App\Models\Grado;
use App\Models\Estudiante;

class MatriculaController extends Controller
{
    //moduleID 17 = Matricula
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('17')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
public function index($nombre)
{
    // Buscar el período por nombre
    $periodo = Periodo::where('nombre', $nombre)->first();

    if (!$periodo) {
        // Intentar obtener el período del año actual
        $anioActual = date('Y');
        $periodoActual = Periodo::where('anio', $anioActual)
            ->where('estado', '1')
            ->first();

        if ($periodoActual) {
            return redirect()->route('matricula.index', ['nombre' => $periodoActual->nombre])
                ->with('info', 'Período "' . $nombre . '" no encontrado. Mostrando el período del año actual.');
        }

        // Si no hay período del año actual, obtener el último período con estado '1'
        $ultimoPeriodoActivo = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimoPeriodoActivo) {
            return redirect()->route('matricula.index', ['nombre' => $ultimoPeriodoActivo->nombre])
                ->with('info', 'Período "' . $nombre . '" no encontrado. Mostrando el último período activo disponible.');
        }

        // Si no hay períodos activos, obtener cualquier período
        $cualquierPeriodo = Periodo::orderBy('anio', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($cualquierPeriodo) {
            return redirect()->route('matricula.index', ['nombre' => $cualquierPeriodo->nombre])
                ->with('info', 'Período "' . $nombre . '" no encontrado. Mostrando el último período disponible.');
        }

        // Si no hay períodos, mostrar error
        return redirect()->back()->with('error', 'No hay períodos disponibles');
    }

    // Resto de tu código existente...
    $gradosConMatriculas = Grado::whereHas('matriculas', function($query) use ($periodo) {
            $query->where('periodo_id', $periodo->id);
        })
        ->withCount(['matriculas' => function($query) use ($periodo) {
            $query->where('periodo_id', $periodo->id);
        }])
        ->orderBy('nivel')
        ->orderBy('grado')
        ->orderBy('seccion')
        ->get();

    $gradosSinMatriculas = Grado::where('estado', '1')
        ->whereDoesntHave('matriculas', function($query) use ($periodo) {
            $query->where('periodo_id', $periodo->id);
        })
        ->orderBy('nivel')
        ->orderBy('grado')
        ->orderBy('seccion')
        ->get();

    $hayMatriculas = $gradosConMatriculas->count() > 0;
    $nombresPeriodos = Periodo::pluck('nombre', 'id');

    return view('matricula.index', compact(
        'periodo',
        'gradosConMatriculas',
        'gradosSinMatriculas',
        'nombresPeriodos',
        'nombre',
        'hayMatriculas'
    ));
}
    public function store(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'periodo_id' => 'required|exists:periodos,id',
            'grado_id' => 'required|exists:grados,id',
        ]);

        // Verificar si ya existe la matrícula
        $existe = Matricula::where('estudiante_id', $request->estudiante_id)
            ->where('periodo_id', $request->periodo_id)
            ->where('grado_id', $request->grado_id)
            ->exists();

        if ($existe) {
            return back()->with('error', 'El estudiante ya está matriculado en este período y grado.');
        }

        $matricula = Matricula::create($request->all());

        // Obtener datos para redireccionar
        $periodo = Periodo::find($request->periodo_id);
        $grado = Grado::find($request->grado_id);

        return redirect()->route('matricula.grado', [
            'nombre' => $periodo->nombre,
            'grado_id' => $grado->id
        ])->with('success', 'Estudiante matriculado exitosamente.');
    }
    // En MatriculaController.php
    public function matricularMasivamente(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'grado_id' => 'required|exists:grados,id',
            'estudiante_ids' => 'required|array',
            'estudiante_ids.*' => 'exists:estudiantes,id',
        ]);

        $periodo = Periodo::find($request->periodo_id);
        $grado = Grado::find($request->grado_id);

        $estudiantesMatriculados = [];
        $estudiantesNoMatriculados = [];

        foreach ($request->estudiante_ids as $estudiante_id) {
            // Verificar si ya existe matrícula
            $existe = Matricula::where('estudiante_id', $estudiante_id)
                ->where('periodo_id', $request->periodo_id)
                ->where('grado_id', $request->grado_id)
                ->exists();

            if (!$existe) {
                // Crear matrícula
                $matricula = Matricula::create([
                    'estudiante_id' => $estudiante_id,
                    'periodo_id' => $request->periodo_id,
                    'grado_id' => $request->grado_id,
                    'estado' => '1', // Estado activo
                ]);

                $estudiantesMatriculados[] = $estudiante_id;
            } else {
                $estudiantesNoMatriculados[] = $estudiante_id;
            }
        }

        // Preparar mensaje de respuesta
        $mensaje = "Matrícula masiva completada. ";
        $mensaje .= count($estudiantesMatriculados) . " estudiante(s) matriculado(s) exitosamente. ";

        if (count($estudiantesNoMatriculados) > 0) {
            $mensaje .= count($estudiantesNoMatriculados) . " estudiante(s) ya estaban matriculado(s).";
        }

        return back()->with('success', $mensaje);
    }
    public function grado($nombre, $grado_id)
    {
        // Obtener el período por nombre
        $periodo = Periodo::where('nombre', $nombre)->first();

        if (!$periodo) {
            return redirect()->route('matricula.index')->with('error', 'Período no encontrado');
        }

        // Obtener el grado
        $grado = Grado::findOrFail($grado_id);

        // Obtener TODAS las matrículas para el grado y período especificados
        $matriculas = Matricula::where('periodo_id', $periodo->id)
            ->where('grado_id', $grado_id)
            ->with(['estudiante.user'])
            ->get();

        // Obtener IDs de estudiantes que YA tienen matrícula (activa o retirada) en este período
        $estudiantesMatriculadosIds = $matriculas->pluck('estudiante_id')->toArray();

        // Obtener estudiantes activos del grado que NO están matriculados en este período
        $estudiantesNoMatriculados = Estudiante::where('grado_id', $grado_id)
            ->where('estado', '1') // Solo estudiantes activos
            ->whereNotIn('id', $estudiantesMatriculadosIds)
            ->with(['user'])
            ->get();

        return view('matricula.grado', compact(
            'matriculas',
            'periodo',
            'grado',
            'nombre',
            'estudiantesNoMatriculados'
        ));
    }
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:0,1',
        ]);

        $matricula = Matricula::findOrFail($id);

        $matricula->estado = $request->estado;
        $matricula->save();

        $mensaje = $request->estado == '1'
            ? 'Estudiante reactivado exitosamente.'
            : 'Estudiante retirado exitosamente.';

        return back()->with('success', $mensaje);
    }
}
