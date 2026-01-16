<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Periodo;
use App\Models\Matricula;
use App\Models\Grado;
use App\Models\Estudiante;

class MatriculaController extends Controller
{
    //moduleID  = Matricula
    public function __construct()
    {

    }
    public function index($nombre)
    {
        // Obtener el período por nombre
        $periodo = Periodo::where('nombre', $nombre)->first();

        if (!$periodo) {
            return redirect()->route('matricula.index')->with('error', 'Período no encontrado');
        }

        // Obtener todos los grados activos
        $grados = Grado::where('estado', '1')
            ->withCount(['matriculas' => function($query) use ($periodo) {
                $query->where('periodo_id', $periodo->id);
            }])
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->get();

        // Separar grados con y sin matrículas
        $gradosConMatriculas = $grados->where('matriculas_count', '>', 0);
        $gradosSinMatriculas = $grados->where('matriculas_count', 0);

        // Verificar si hay matrículas para este período
        $hayMatriculas = $gradosConMatriculas->count() > 0;

        // Obtener todos los períodos para el select
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
