<?php

namespace App\Http\Controllers;

use App\Models\Reporte\Reporte;
use App\Models\Reporte\Estadoreporte;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\User;
use App\Models\Apoderado;
use App\Models\Materia;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente') && !$user->hasRole('auxiliar') && !$user->hasRole('apoderado')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        $user = auth()->user();
        $year = now()->year;

        if ($user->hasRole('admin') || $user->hasRole('director')) {
            // Todos los reportes del año actual
            $reportes = Reporte::with(['creador', 'destinatario.apoderado', 'materia', 'estadoreporte'])
                ->whereYear('fecha', $year)
                ->get();
        } elseif ($user->hasRole('docente') || $user->hasRole('auxiliar')) {
            // Solo los reportes creados por el usuario en el año actual
            $reportes = Reporte::with(['creador', 'destinatario.apoderado', 'materia', 'estadoreporte'])
                ->where('creador_id', $user->id)
                ->whereYear('fecha', $year)
                ->get();
        } elseif ($user->hasRole('apoderado')) {
            // Solo los reportes donde el usuario es destinatario en el año actual
            $reportes = Reporte::with(['creador', 'destinatario.apoderado', 'materia', 'estadoreporte'])
                ->where('destinatario_id', $user->id)
                ->whereYear('fecha', $year)
                ->get();
        } else {
            $reportes = collect(); // vacío
        }

        return view('reporte.index', compact('reportes'));
    }
    public function show($id)
    {
        $reporte = Reporte::with(['creador', 'destinatario.apoderado', 'materia', 'estadoreporte'])
            ->findOrFail($id);

        $user = auth()->user();

        // Verificar permisos
        if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente') && !$user->hasRole('auxiliar') && !$user->hasRole('apoderado')) {
            abort(403, 'Acceso no autorizado.');
        }

        // Si el usuario es el destinatario y el estado es 1 o 2, actualizar a 3 (Visto)
        $estadoActual = $reporte->estadoreporte->estado ?? 1;
        if (
            $user->id == $reporte->destinatario_id &&
            in_array($estadoActual, [1, 2])
        ) {
            $reporte->estadoreporte->estado = 3;
            $reporte->estadoreporte->save();
            $estadoActual = 3; // Actualiza la variable para la vista
        }

        return view('reporte.show', compact('reporte'));
    }

    public function create()
    {
        $user = auth()->user();

        // Solo permitir acceso a ciertos roles
        if (
            !$user->hasRole('admin') &&
            !$user->hasRole('director') &&
            !$user->hasRole('docente') &&
            !$user->hasRole('auxiliar')
        ) {
            abort(403, 'No tienes permiso para crear reportes.');
        }

        // ...existing code...
        if ($user->hasRole('docente')) {
            $cursos = Cursogradosecnivanio::with('materia')
                ->where('docente_designado_id', $user->docente->id)
                ->get();
            $materias = $cursos->pluck('materia')->unique('id');
        } else {
            $materias = Materia::all();
        }

        $apoderados = Apoderado::with('user')->get();
        return view('reporte.create', compact('materias', 'apoderados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'nullable|exists:materias,id',
            'destinatario_id' => 'required|exists:users,id',
            'asunto' => 'required|string',
            'fecha' => 'required|date',
            'hora' => 'required',
        ]);
        $reporte = Reporte::create([
            'creador_id' => Auth::id(),
            'destinatario_id' => $request->destinatario_id,
            'materia_id' => $request->materia_id,
            'asunto' => $request->asunto,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
        ]);
        Estadoreporte::create([
            'reporte_id' => $reporte->id,
            'estado' => 1, // Creado
        ]);
        return redirect()->route('reporte.index')->with('success', 'Reporte creado correctamente.');
    }
    public function update(Request $request, $id)
    {
        $reporte = Reporte::with('estadoreporte')->findOrFail($id);
        $user = auth()->user();

        // Validar permisos
        $puedeActualizar = (
            $user->hasRole('admin') ||
            $user->hasRole('director') ||
            $user->id == $reporte->creador_id ||
            $user->id == $reporte->destinatario_id
        );

        if (!$puedeActualizar) {
            abort(403, 'No tienes permiso para actualizar este reporte.');
        }

        $nuevoEstado = $request->input('estado');

        // Solo permitir cambios válidos
        if ($user->id == $reporte->creador_id || $user->hasRole('admin') || $user->hasRole('director')) {
            // El creador/admin/director puede cambiar de 1 a 2 (Enviar)
            if (($reporte->estadoreporte->estado ?? 1) == 1 && $nuevoEstado == 2) {
                $reporte->estadoreporte->estado = 2;
                $reporte->estadoreporte->save();
                return redirect()->route('reporte.show', $reporte->id)->with('success', 'Reporte enviado.');
            }
        }

        if ($user->id == $reporte->destinatario_id) {
            // El destinatario puede confirmar recepción (de 1, 2 o 3 a 4)
            if (in_array($reporte->estadoreporte->estado ?? 1, [1,2,3]) && $nuevoEstado == 4) {
                $reporte->estadoreporte->estado = 4;
                $reporte->estadoreporte->save();
                return redirect()->route('reporte.show', $reporte->id)->with('success', 'Recepción confirmada.');
            }
        }
        return redirect()->route('reporte.show', $reporte->id)->with('error', 'No se pudo actualizar el estado.');
    }
    public function destroy($id)
    {
        $reporte = Reporte::findOrFail($id);
        $user = auth()->user();

        // Solo admin, director o creador pueden eliminar
        if (
            !$user->hasRole('admin') &&
            !$user->hasRole('director') &&
            $user->id != $reporte->creador_id
        ) {
            abort(403, 'No tienes permiso para eliminar este reporte.');
        }

        // Elimina el estado primero si existe
        if ($reporte->estadoreporte) {
            $reporte->estadoreporte->delete();
        }
        $reporte->delete();

        return redirect()->route('reporte.index')->with('success', 'Reporte eliminado correctamente.');
    }
}
