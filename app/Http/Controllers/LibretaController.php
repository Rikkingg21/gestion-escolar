<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Asistencia\Asistencia;
use App\Models\Grado;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use App\Models\Colegio;
use App\Models\Conductanota;
use App\Models\Periodo;
use App\Models\Matricula;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class LibretaController extends Controller
{
    //moduleID 15 = Libreta
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('15')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }

    public function index(Request $request, $anio, $bimestre)
    {
        $user = auth()->user();

        // Obtener el estudiante con el usuario relacionado
        $estudiante = Estudiante::with(['user'])
            ->where('user_id', $user->id)
            ->first();

        if (!$estudiante) {
            abort(404, 'Estudiante no encontrado.');
        }

        // Validar que el bimestre sea válido (1-4 o anual)
        if (!in_array($bimestre, ['anual', '1', '2', '3', '4'])) {
            abort(404, 'Bimestre no válido.');
        }

        // Buscar el periodo por año (anio)
        $periodoActual = Periodo::where('anio', $anio)->first();

        if (!$periodoActual) {
            // Si no encuentra el periodo, buscar el periodo activo más reciente
            $periodoActual = Periodo::where('estado', '1')
                ->orderBy('anio', 'desc')
                ->first();

            if ($periodoActual) {
                // Redirigir al año correcto
                return redirect()->route('libreta.index', [
                    'anio' => $periodoActual->anio,
                    'bimestre' => $bimestre
                ]);
            }
        }

        // Obtener la matrícula del estudiante para este periodo
        $matriculaActual = null;
        if ($periodoActual) {
            $matriculaActual = Matricula::with(['grado', 'periodo'])
                ->where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoActual->id)
                ->first();
        }

        // Obtener todos los periodos donde el estudiante tiene matrículas
        $periodos = Periodo::whereIn('id', function($query) use ($estudiante) {
                $query->select('periodo_id')
                    ->from('matriculas')
                    ->where('estudiante_id', $estudiante->id);
            })
            ->orderBy('anio', 'desc')
            ->get();

        // Obtener datos de la IE (Colegio)
        $colegio = Colegio::configuracion();

        // Variables para datos
        $materiasConJerarquia = collect();
        $notasConducta = collect();
        $asistencias = collect();
        $resumenAsistencias = [
            'total' => 0,
            'tipos' => []
        ];

        if ($matriculaActual && $periodoActual) {
            // ==============================================
            // 1. OBTENER NOTAS DE MATERIAS
            // ==============================================

            // Nota importante: Las notas están relacionadas con criterios, y los criterios tienen el campo 'anio'
            // Debemos filtrar las notas a través de sus criterios por el año del periodo

            $notasEstudiante = Nota::with(['criterio.materiaCompetencia', 'criterio.materia'])
                ->where('estudiante_id', $estudiante->id)
                ->where('publico', '!=', '0')
                // Filtrar notas que pertenecen a criterios del año actual
                ->whereHas('criterio', function($query) use ($periodoActual) {
                    $query->where('anio', $periodoActual->anio);
                });

            // Filtrar por bimestre si no es "anual"
            if ($bimestre !== 'anual') {
                $notasEstudiante->where('bimestre', $bimestre);
            }

            $notasEstudiante = $notasEstudiante->get();

            // Agrupar notas por materia
            $notasPorMateria = [];
            foreach ($notasEstudiante as $nota) {
                if ($nota->criterio && $nota->criterio->materia) {
                    $materiaId = $nota->criterio->materia_id;
                    $competenciaId = $nota->criterio->materia_competencia_id;

                    if (!isset($notasPorMateria[$materiaId])) {
                        $notasPorMateria[$materiaId] = [
                            'materia_id' => $materiaId,
                            'materia_nombre' => $nota->criterio->materia->nombre ?? 'Sin nombre',
                            'competencias' => []
                        ];
                    }

                    if (!isset($notasPorMateria[$materiaId]['competencias'][$competenciaId])) {
                        $notasPorMateria[$materiaId]['competencias'][$competenciaId] = [
                            'competencia_id' => $competenciaId,
                            'competencia_nombre' => $nota->criterio->materiaCompetencia->nombre ?? 'Sin competencia',
                            'criterios' => []
                        ];
                    }

                    $notasPorMateria[$materiaId]['competencias'][$competenciaId]['criterios'][] = [
                        'criterio_id' => $nota->criterio->id,
                        'criterio_nombre' => $nota->criterio->nombre,
                        'nota' => [
                            'id' => $nota->id,
                            'valor' => $nota->nota,
                            'publico' => $nota->publico,
                            'bimestre' => $nota->bimestre,
                        ]
                    ];
                }
            }

            // Convertir a la estructura final
            foreach ($notasPorMateria as $materiaData) {
                $competencias = collect($materiaData['competencias'])
                    ->map(function($competencia) {
                        $competencia['criterios'] = collect($competencia['criterios']);
                        return $competencia;
                    })
                    ->values();

                $materiasConJerarquia->push([
                    'materia_id' => $materiaData['materia_id'],
                    'materia_nombre' => $materiaData['materia_nombre'],
                    'competencias' => $competencias
                ]);
            }
            // ==============================================
            // 2. OBTENER NOTAS DE CONDUCTA (ACTUALIZADO)
            // ==============================================

            // Ahora podemos filtrar por periodo_id ya que el modelo tiene esta relación
            $notasConductaQuery = Conductanota::with(['conducta', 'periodo'])
                ->where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoActual->id) // ¡FILTRO POR PERIODO!
                ->where('publico', '!=', '0');

            // Filtrar por bimestre si no es "anual"
            if ($bimestre !== 'anual') {
                $notasConductaQuery->where('bimestre', $bimestre);
            }

            $notasConducta = $notasConductaQuery->get()
                ->map(function($notaConducta) {
                    return [
                        'id' => $notaConducta->id,
                        'conducta_id' => $notaConducta->conducta_id,
                        'conducta_nombre' => $notaConducta->conducta->nombre ?? 'Sin nombre',
                        'valor' => $notaConducta->nota,
                        'bimestre' => $notaConducta->bimestre,
                        'publico' => $notaConducta->publico,
                        'periodo_anio' => $notaConducta->periodo->anio ?? null,
                    ];
                });


            // ==============================================
            // 3. OBTENER ASISTENCIAS
            // ==============================================

            // Para asistencias, también necesitamos filtrar por año
            // Si la tabla asistencias no tiene campo 'anio', podemos filtrar por fechas dentro del año

            $asistenciasQuery = Asistencia::with(['tipoasistencia', 'grado'])
                ->where('estudiante_id', $estudiante->id)
                ->where('grado_id', $matriculaActual->grado_id);

            // Si las asistencias tienen fecha, podemos filtrar por año escolar
            // Esto asume que el año escolar va desde enero a diciembre del año indicado
            $asistenciasQuery->whereYear('fecha', $periodoActual->anio);

            // Filtrar por bimestre si no es "anual"
            if ($bimestre !== 'anual') {
                $asistenciasQuery->where('bimestre', $bimestre);
            }

            $asistencias = $asistenciasQuery->orderBy('fecha', 'desc')->get();

            // Calcular resumen de asistencias
            if ($asistencias->count() > 0) {
                $resumenAsistencias['total'] = $asistencias->count();

                // Agrupar por tipo de asistencia
                $asistenciasPorTipo = $asistencias->groupBy('tipo_asistencia_id');

                foreach ($asistenciasPorTipo as $tipoId => $asistenciasTipo) {
                    $tipo = $asistenciasTipo->first()->tipoasistencia;
                    $resumenAsistencias['tipos'][] = [
                        'tipo_id' => $tipoId,
                        'tipo_nombre' => $tipo->nombre ?? 'Sin tipo',
                        'cantidad' => $asistenciasTipo->count(),
                        'porcentaje' => round(($asistenciasTipo->count() / $asistencias->count()) * 100, 1)
                    ];
                }
            }
        }

        return view('libreta.index', [
            'estudiante' => $estudiante,
            'matricula_actual' => $matriculaActual,
            'periodo_actual' => $periodoActual,
            'periodos' => $periodos,
            'colegio' => $colegio,
            'materias_con_jerarquia' => $materiasConJerarquia,
            'notas_conducta' => $notasConducta,
            'asistencias' => $asistencias,
            'resumen_asistencias' => $resumenAsistencias,
            'anio_param' => $anio,
            'bimestre_param' => $bimestre,
        ]);
    }
}
