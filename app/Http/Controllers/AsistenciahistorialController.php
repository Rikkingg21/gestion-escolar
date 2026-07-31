<?php

namespace App\Http\Controllers;

use App\Models\Asistencia\Asistencia;
use App\Models\Asistencia\Tipoasistencia;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Periodobimestre;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AsistenciahistorialController extends Controller
{
    // moduleID 16 = Mis Asistencias
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('16')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function calendarioAsistencia(Request $request, $periodo_id = null, $periodobimestre_sigla = null)
    {
        // Obtener el ID del usuario de la sesión sub (si existe) o el actual
        $userId = session('sub_session_user_id') ?? auth()->id();

        // Buscar el usuario y su estudiante
        $user = User::find($userId);
        $estudiante = $user->estudiante;

        // Obtener períodos en los que el estudiante tiene asistencias registradas
        $periodosConAsistencias = $estudiante->asistencias()
            ->select('periodo_id')
            ->with('periodo')
            ->distinct()
            ->get()
            ->pluck('periodo')
            ->filter()
            ->values();

        // Si no se proporciona período, usar el período más reciente con asistencias o null
        if (! $periodo_id && $periodosConAsistencias->isNotEmpty()) {
            $periodo_id = $periodosConAsistencias->first()->id;
        }

        // Obtener la matrícula del estudiante en el período seleccionado
        $matricula = null;
        $gradoActual = null;

        if ($periodo_id) {
            $matricula = Matricula::where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodo_id)
                ->first();

            if ($matricula && $matricula->grado) {
                $gradoActual = $matricula->grado;
            }
        }

        // Obtener período seleccionado
        $periodoSeleccionado = $periodo_id ? Periodo::find($periodo_id) : null;

        // Obtener el bimestre por su sigla
        $periodobimestreSeleccionado = null;
        $periodobimestre_id = null;

        if ($periodo_id && $periodobimestre_sigla && $periodobimestre_sigla !== 'anual') {
            $periodobimestreSeleccionado = Periodobimestre::where('periodo_id', $periodo_id)
                ->where('sigla', $periodobimestre_sigla)
                ->first();

            if ($periodobimestreSeleccionado) {
                $periodobimestre_id = $periodobimestreSeleccionado->id;
            }
        }

        // Construir consulta base
        $query = $estudiante->asistencias()
            ->with(['tipoasistencia', 'grado', 'periodobimestre', 'periodo']);

        // Filtrar por período si está seleccionado
        if ($periodo_id) {
            $query->where('periodo_id', $periodo_id);
        }

        // Aplicar filtro por bimestre (si no es anual)
        if ($periodobimestre_id) {
            $query->where('periodobimestre_id', $periodobimestre_id);
        }

        // Obtener asistencias para el calendario
        $asistenciasParaEventos = $query->orderBy('fecha', 'desc')->get();
        $eventosCalendario = [];

        // Obtener todos los tipos de asistencia para el resumen
        $tiposAsistencia = Tipoasistencia::all();

        // Inicializar estadísticas con todos los tipos
        $estadisticas = [];
        foreach ($tiposAsistencia as $tipo) {
            $estadisticas[$tipo->id] = [
                'nombre' => $tipo->nombre,
                'color' => $tipo->color_hex ?? '#6c757d',
                'count' => 0,
            ];
        }
        $estadisticas['total'] = 0;

        foreach ($asistenciasParaEventos as $asistencia) {
            $fechaCarbon = Carbon::parse($asistencia->fecha);
            $color = $asistencia->tipoasistencia->color_hex ?? '#6c757d';

            // Obtener nombre del bimestre
            $bimestreNombre = $asistencia->periodobimestre ? $asistencia->periodobimestre->sigla : 'Anual';

            $eventosCalendario[] = [
                'title' => $asistencia->tipoasistencia->nombre ?? 'Sin tipo',
                'start' => $fechaCarbon->toDateString(),
                'color' => $color,
                'extendedProps' => [
                    'tipo' => $asistencia->tipoasistencia->nombre ?? '',
                    'hora' => $asistencia->hora ? date('h:i A', strtotime($asistencia->hora)) : '',
                    'grado' => $asistencia->grado ? $asistencia->grado->grado.'° '.$asistencia->grado->seccion : 'N/A',
                    'bimestre' => $bimestreNombre,
                    'descripcion' => $asistencia->descripcion ?? 'Sin descripción',
                    'periodo' => $asistencia->periodo ? $asistencia->periodo->nombre : 'N/A',
                ],
            ];

            // Actualizar estadísticas
            $tipoId = $asistencia->tipo_asistencia_id;
            if (isset($estadisticas[$tipoId])) {
                $estadisticas[$tipoId]['count']++;
            }
            $estadisticas['total']++;
        }

        // Obtener bimestres disponibles para el período seleccionado (con sigla)
        $bimestresDisponibles = collect();
        if ($periodo_id) {
            $bimestresDisponibles = Periodobimestre::where('periodo_id', $periodo_id)
                ->where('tipo_bimestre', 'A')
                ->orderBy('bimestre', 'asc')
                ->get(['id', 'bimestre', 'sigla', 'fecha_inicio', 'fecha_fin']);
        }

        // PRECARGAR TODOS LOS BIMESTRES POR PERÍODO
        $todosLosBimestres = [];
        foreach ($periodosConAsistencias as $periodo) {
            $todosLosBimestres[$periodo->id] = Periodobimestre::where('periodo_id', $periodo->id)
                ->where('tipo_bimestre', 'A')
                ->orderBy('bimestre', 'asc')
                ->get(['id', 'bimestre', 'sigla', 'fecha_inicio', 'fecha_fin']);
        }

        return view('asistencia.calendario', compact(
            'estudiante',
            'gradoActual',
            'eventosCalendario',
            'estadisticas',
            'tiposAsistencia',
            'periodosConAsistencias',
            'bimestresDisponibles',
            'periodo_id',
            'periodobimestre_sigla',
            'periodoSeleccionado',
            'todosLosBimestres'
        ));
    }
}
