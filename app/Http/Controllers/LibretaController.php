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
use App\Models\Conductaperiodobimestrenota;
use App\Models\Periodobimestre;
use App\Models\Conducta;
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
    public function index($anio, $sigla = null)
    {
        $estudiante = $this->getEstudiante();
        if (!$estudiante) abort(404, 'Estudiante no encontrado.');

        $periodos = $this->getPeriodosEstudiante($estudiante);
        if ($periodos->isEmpty()) abort(404, 'No se encontraron periodos para este estudiante.');

        $periodoActual = $this->getPeriodoActual($anio, $periodos);
        if (!$periodoActual) {
            return redirect()->route('libreta.index', [
                'anio' => $periodos->first()['anio'],
                'sigla' => $sigla ?? 'anual'
            ]);
        }

        $matriculaActual = $this->getMatriculaActual($estudiante, $periodoActual);
        $bimestres = $this->getBimestresDisponibles($periodoActual);
        $sigla = $this->validarSigla($sigla, $bimestres);
        $colegio = Colegio::configuracion();
        $esAnual = ($sigla === 'anual');

        // Notas de materias
        $notasMaterias = $this->getNotasMaterias($estudiante->id, $periodoActual, $sigla);
        $materiasAgrupadas = $this->agruparNotasPorMateria($notasMaterias, $esAnual);
        $materiasConPromedios = $this->calcularPromedios($materiasAgrupadas, $esAnual);
        $promedioTransversales = $this->calcularPromedioTransversales($materiasConPromedios);

        // OBTENER Y PROCESAR CONDUCTAS (SIMPLIFICADO)
        $todasLasConductas = $this->obtenerTodasLasConductas($estudiante->id, $periodoActual, $sigla, $matriculaActual->grado_id ?? null);

        $datosVista = $this->prepararDatosVista([
            'estudiante' => $estudiante,
            'periodos' => $periodos,
            'periodoActual' => $periodoActual,
            'matriculaActual' => $matriculaActual,
            'bimestres' => $bimestres,
            'sigla' => $sigla,
            'colegio' => $colegio,
            'anio' => $anio,
        ]);

        return view('libreta.index', array_merge($datosVista, [
            'materias' => $materiasConPromedios,
            'todas_las_conductas' => $todasLasConductas,
            'promedioTransversales' => $promedioTransversales,
        ]));
    }
    private function redondearNota($nota)
    {
        $decimal = $nota - floor($nota);
        if ($decimal >= 0.5) {
            return ceil($nota);
        } else {
            return floor($nota);
        }
    }
    private function obtenerTodasLasConductas($estudianteId, $periodoActual, $sigla, $gradoId)
    {
        // Obtener todos los cursos del grado del estudiante (materias)
        $cursos = Cursogradosecnivanio::with('materia')
            ->where('periodo_id', $periodoActual->id)
            ->where('grado_id', $gradoId)
            ->get();

        // Obtener los bimestres del periodo
        $bimestres = Periodobimestre::where('periodo_id', $periodoActual->id)
            ->where('tipo_bimestre', 'A')
            ->orderBy('bimestre')
            ->get();

        // Obtener el bimestre seleccionado (si no es anual)
        $periodoBimestreSeleccionado = null;
        if ($sigla !== 'anual') {
            $periodoBimestreSeleccionado = $bimestres->firstWhere('sigla', $sigla);
        }

        // OBTENER LAS CONDUCTAS REALMENTE RELACIONADAS CON EL PERIODO
        // A través de Conductaperiodobimestre (relación entre periodobimestre y conducta)
        // IMPORTANTE: Solo considerar conductaperiodobimestres que NO estén eliminados (deleted_at IS NULL)
        $conductasQuery = Conducta::whereHas('periodosBimestres', function($query) use ($periodoActual, $periodoBimestreSeleccionado, $sigla) {
            $query->where('periodo_id', $periodoActual->id)
                ->whereNull('conducta_periodo_bimestres.deleted_at'); // Excluir eliminados

            // Si es modo bimestre, filtrar por el bimestre específico
            if ($sigla !== 'anual' && $periodoBimestreSeleccionado) {
                $query->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
            }
            // Si es anual, traer todas las conductas de todos los bimestres del periodo
        });

        $todasLasConductasDB = $conductasQuery->distinct()->get();
        $totalConductas = $todasLasConductasDB->count();

        // Si no hay conductas asignadas al periodo, retornar array vacío
        if ($totalConductas == 0) {
            return [];
        }

        // Obtener las notas existentes
        // IMPORTANTE: Solo considerar notas que pertenezcan a conductaperiodobimestres NO eliminados
        $query = Conductaperiodobimestrenota::with([
                'conductaPeriodoBimestre' => function($query) {
                    $query->whereNull('deleted_at'); // Solo relaciones no eliminadas
                },
                'conductaPeriodoBimestre.conducta',
                'periodoBimestre',
                'curso_grado_sec_niv_anio.materia'
            ])
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoActual->id)
            ->where('publico', '!=', '0')
            ->whereHas('conductaPeriodoBimestre', function($query) {
                $query->whereNull('deleted_at'); // Solo notas con conducta_periodo_bimestre no eliminado
            });

        // Si es modo bimestre, filtrar por el bimestre seleccionado
        if ($sigla !== 'anual' && $periodoBimestreSeleccionado) {
            $query->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
        }

        $notasExistentes = $query->get();

        // Si no hay notas existentes y es modo bimestre, retornar array con guiones
        if ($notasExistentes->isEmpty() && $sigla !== 'anual') {
            $resultado = [];
            foreach ($todasLasConductasDB as $conducta) {
                $resultado[] = [
                    'nombre' => $conducta->nombre,
                    'nota' => '-',
                    'nota_original' => '-',
                    'estado' => "0/{$cursos->count()} materias - Sin notas registradas",
                    'tiene_tooltip' => true,
                    'es_guion' => true
                ];
            }
            return $resultado;
        }

        // Crear un array de notas existentes para fácil acceso
        $notasMap = [];
        foreach ($notasExistentes as $nota) {
            // Verificar que la relación conductaPeriodoBimestre existe y no está eliminada
            if (!$nota->conductaPeriodoBimestre || $nota->conductaPeriodoBimestre->trashed()) {
                continue;
            }

            $conductaId = $nota->conductaPeriodoBimestre->conducta_id ?? null;
            $cursoId = $nota->curso_grado_sec_niv_anio_id;
            $bimestreId = $nota->periodo_bimestre_id;

            if ($conductaId && $cursoId && $bimestreId) {
                $key = $conductaId . '|' . $cursoId . '|' . $bimestreId;
                $notasMap[$key] = $nota->nota;
            }
        }

        // Si es modo anual
        if ($sigla === 'anual') {
            $totalBimestres = $bimestres->count();

            // Verificar si hay alguna nota en todo el año
            $hayNotas = false;
            foreach ($notasMap as $nota) {
                if ($nota) {
                    $hayNotas = true;
                    break;
                }
            }

            if (!$hayNotas) {
                $resultado = [];
                foreach ($todasLasConductasDB as $conducta) {
                    $resultado[] = [
                        'nombre' => $conducta->nombre,
                        'nota' => '-',
                        'nota_original' => '-',
                        'estado' => "0/{$totalBimestres} Bimestres - Sin notas registradas",
                        'tiene_tooltip' => true,
                        'es_guion' => true
                    ];
                }
                return $resultado;
            }

            $todasLasConductas = [];

            foreach ($todasLasConductasDB as $conducta) {
                $promediosPorBimestre = [];
                $bimestresCompletos = [];
                $bimestresIncompletos = [];

                foreach ($bimestres as $bimestre) {
                    $notasBimestre = [];
                    $notasEncontradasEnBimestre = 0;

                    foreach ($cursos as $curso) {
                        $key = $conducta->id . '|' . $curso->id . '|' . $bimestre->id;

                        if (isset($notasMap[$key])) {
                            $notasBimestre[] = $notasMap[$key];
                            $notasEncontradasEnBimestre++;
                        } else {
                            $notasBimestre[] = 1; // Nota faltante = 1
                        }
                    }

                    $promedio = round(array_sum($notasBimestre) / count($notasBimestre), 1);
                    $promediosPorBimestre[$bimestre->bimestre] = $promedio;

                    if ($notasEncontradasEnBimestre == $cursos->count()) {
                        $bimestresCompletos[] = $bimestre->bimestre;
                    } else if ($notasEncontradasEnBimestre > 0) {
                        $bimestresIncompletos[] = $bimestre->bimestre;
                    }
                }

                // Determinar mensaje de estado para tooltip
                $estadoMensaje = '';
                $tieneTooltip = false;

                if (count($bimestresCompletos) == $totalBimestres) {
                    $estadoMensaje = "{$totalBimestres}/{$totalBimestres} Bimestres completos";
                    $tieneTooltip = false;
                } else if (count($bimestresCompletos) > 0 || count($bimestresIncompletos) > 0) {
                    $bimestresConNotas = array_merge($bimestresCompletos, $bimestresIncompletos);
                    sort($bimestresConNotas);
                    $bimestresTexto = implode(', ', array_map(function($b) {
                        $siglas = ['I', 'II', 'III', 'IV'];
                        return $siglas[$b-1] . ' Bim';
                    }, $bimestresConNotas));
                    $estadoMensaje = count($bimestresCompletos) . "/{$totalBimestres} Bim - {$bimestresTexto}" . (count($bimestresIncompletos) > 0 ? " con notas faltantes" : "");
                    $tieneTooltip = true;
                } else {
                    $estadoMensaje = "0/{$totalBimestres} Bimestres - Sin notas registradas";
                    $tieneTooltip = true;
                }

                // Mostrar promedios (sin decimales)
                if (count($promediosPorBimestre) == $totalBimestres) {
                    $promedioAnual = round(array_sum($promediosPorBimestre) / $totalBimestres, 1);
                    $promedioAnualRedondeado = $this->redondearNota($promedioAnual);
                    $todasLasConductas[] = [
                        'nombre' => $conducta->nombre,
                        'nota' => $promedioAnualRedondeado,
                        'nota_original' => $promedioAnualRedondeado,
                        'estado' => $estadoMensaje,
                        'tiene_tooltip' => $tieneTooltip,
                        'es_guion' => false
                    ];
                } else {
                    foreach ($promediosPorBimestre as $bimestreNum => $promedio) {
                        $bimestreSigla = $this->getSiglaByBimestre($bimestreNum);
                        $promedioRedondeado = $this->redondearNota($promedio);
                        $todasLasConductas[] = [
                            'nombre' => $conducta->nombre . ' (' . $bimestreSigla . ')',
                            'nota' => $promedioRedondeado,
                            'nota_original' => $promedioRedondeado,
                            'estado' => $estadoMensaje,
                            'tiene_tooltip' => $tieneTooltip,
                            'es_guion' => false
                        ];
                    }
                }
            }

            return $todasLasConductas;
        }
        else {
            // MODO BIMESTRE - Calcular completitud por MATERIA
            $bimestreId = $periodoBimestreSeleccionado ? $periodoBimestreSeleccionado->id : null;

            // Contar materias completas (las que tienen todas las conductas registradas)
            $materiasCompletas = 0;
            $materiasIncompletas = 0;

            foreach ($cursos as $curso) {
                $conductasCompletas = 0;
                foreach ($todasLasConductasDB as $conducta) {
                    $key = $conducta->id . '|' . $curso->id . '|' . $bimestreId;
                    if (isset($notasMap[$key])) {
                        $conductasCompletas++;
                    }
                }
                if ($conductasCompletas == $totalConductas) {
                    $materiasCompletas++;
                } else if ($conductasCompletas > 0) {
                    $materiasIncompletas++;
                }
            }

            $totalMaterias = $cursos->count();
            $estadoGeneral = "{$materiasCompletas}/{$totalMaterias} materias completas";
            if ($materiasIncompletas > 0) {
                $estadoGeneral .= " - {$materiasIncompletas} con notas faltantes";
            }

            $resultado = [];

            foreach ($todasLasConductasDB as $conducta) {
                $notasConducta = [];
                $materiasConNota = 0;

                foreach ($cursos as $curso) {
                    $key = $conducta->id . '|' . $curso->id . '|' . $bimestreId;

                    if (isset($notasMap[$key])) {
                        $notasConducta[] = $notasMap[$key];
                        $materiasConNota++;
                    } else {
                        $notasConducta[] = 1; // Nota faltante = 1
                    }
                }

                $promedio = round(array_sum($notasConducta) / count($notasConducta), 1);
                $promedioRedondeado = $this->redondearNota($promedio);

                $resultado[] = [
                    'nombre' => $conducta->nombre,
                    'nota' => $promedioRedondeado,
                    'nota_original' => $promedioRedondeado,
                    'estado' => $estadoGeneral,
                    'tiene_tooltip' => true,
                    'es_guion' => false
                ];
            }

            return $resultado;
        }
    }
    private function getEstudiante()
    {
        return Estudiante::with(['user'])
            ->where('user_id', auth()->user()->id)
            ->first();
    }

    private function getPeriodosEstudiante($estudiante)
    {
        return Periodo::whereIn('id', function($query) use ($estudiante) {
                $query->select('periodo_id')
                    ->from('matriculas')
                    ->where('estudiante_id', $estudiante->id);
            })
            ->orderBy('anio', 'desc')
            ->get()
            ->map(function($periodo) {
                return [
                    'id' => $periodo->id,
                    'anio' => $periodo->anio,
                    'nombre' => $periodo->nombre,
                    'estado' => $periodo->estado,
                    'descripcion' => $periodo->descripcion,
                ];
            });
    }

    private function getPeriodoActual($anio, $periodos)
    {
        $periodo = Periodo::where('anio', $anio)->first();
        if (!$periodo || !collect($periodos)->contains('id', $periodo->id)) {
            return null;
        }
        return $periodo;
    }

    private function getMatriculaActual($estudiante, $periodoActual)
    {
        return Matricula::with(['grado', 'periodo'])
            ->where('estudiante_id', $estudiante->id)
            ->where('periodo_id', $periodoActual->id)
            ->first();
    }

    private function getBimestresDisponibles($periodoActual)
    {
        $bimestres = Periodobimestre::where('periodo_id', $periodoActual->id)
            ->where('tipo_bimestre', 'A')
            ->orderBy('bimestre')
            ->get()
            ->map(function($bimestre) {
                return [
                    'sigla' => $bimestre->sigla,
                    'bimestre' => $bimestre->bimestre,
                    'nombre' => $bimestre->sigla . ' - Bimestre ' . $bimestre->bimestre,
                    'fecha_inicio' => $bimestre->fecha_inicio,
                    'fecha_fin' => $bimestre->fecha_fin,
                ];
            });

        return collect([
            [
                'sigla' => 'anual',
                'bimestre' => null,
                'nombre' => 'Promedio Anual',
                'fecha_inicio' => null,
                'fecha_fin' => null,
            ]
        ])->concat($bimestres);
    }

    private function validarSigla($sigla, $bimestres)
    {
        $siglasValidas = $bimestres->pluck('sigla')->toArray();
        if (!$sigla || !in_array($sigla, $siglasValidas)) {
            return 'anual';
        }
        return $sigla;
    }

    private function prepararDatosVista($params)
    {
        $bimestreSeleccionado = $params['bimestres']->firstWhere('sigla', $params['sigla']);

        return [
            'estudiante' => $params['estudiante'],
            'matricula_actual' => $params['matriculaActual'],
            'periodo_actual' => [
                'id' => $params['periodoActual']->id,
                'anio' => $params['periodoActual']->anio,
                'nombre' => $params['periodoActual']->nombre,
                'descripcion' => $params['periodoActual']->descripcion,
            ],
            'periodos' => $params['periodos'],
            'bimestres_disponibles' => $params['bimestres'],
            'bimestre_seleccionado' => $bimestreSeleccionado,
            'bimestre_nombre' => $bimestreSeleccionado['nombre'] ?? 'Promedio Anual',
            'colegio' => $params['colegio'],
            'anio_param' => $params['anio'],
            'sigla_param' => $params['sigla'],
        ];
    }

    private function getNotasMaterias($estudianteId, $periodoActual, $sigla)
    {
        $query = Nota::with(['criterio.materiaCompetencia', 'criterio.materia'])
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoActual->id)
            ->where('publico', '!=', '0');

        if ($sigla !== 'anual') {
            $periodoBimestre = Periodobimestre::where('periodo_id', $periodoActual->id)
                ->where('sigla', $sigla)
                ->where('tipo_bimestre', 'A')
                ->first();

            if ($periodoBimestre) {
                $query->where('periodo_bimestre_id', $periodoBimestre->id);
            } else {
                return collect();
            }
        }

        return $query->get();
    }

    private function agruparNotasPorMateria($notas, $esAnual = false)
    {
        $materias = [];

        foreach ($notas as $nota) {
            $criterio = $nota->criterio;
            if (!$criterio) continue;

            $materiaId = $criterio->materia_id;
            $materiaNombre = $criterio->materia->nombre ?? 'Sin materia';
            $competenciaId = $criterio->materia_competencia_id;
            $competenciaNombre = $criterio->materiaCompetencia->nombre ?? 'Sin competencia';
            $esTransversal = str_contains(strtoupper($competenciaNombre), 'TRANSVERSAL');

            if (!isset($materias[$materiaId])) {
                $materias[$materiaId] = [
                    'id' => $materiaId,
                    'nombre' => $materiaNombre,
                    'competencias' => [],
                    'competencias_transversales' => [],
                    'es_transversal' => false
                ];
            }

            $targetArray = $esTransversal ? 'competencias_transversales' : 'competencias';

            if (!isset($materias[$materiaId][$targetArray][$competenciaId])) {
                $materias[$materiaId][$targetArray][$competenciaId] = [
                    'id' => $competenciaId,
                    'nombre' => $competenciaNombre,
                    'criterios' => [],
                    'es_transversal' => $esTransversal
                ];
            }

            $materias[$materiaId][$targetArray][$competenciaId]['criterios'][] = [
                'id' => $criterio->id,
                'nombre' => $criterio->nombre,
                'nota' => $nota->nota,
                'publico' => $nota->publico
            ];
        }

        foreach ($materias as &$materia) {
            $materia['competencias'] = array_values($materia['competencias']);
            $materia['competencias_transversales'] = array_values($materia['competencias_transversales']);
            foreach ($materia['competencias'] as &$competencia) {
                $competencia['criterios'] = array_values($competencia['criterios']);
            }
            foreach ($materia['competencias_transversales'] as &$competencia) {
                $competencia['criterios'] = array_values($competencia['criterios']);
            }
        }

        return array_values($materias);
    }

    private function calcularPromedios($materias, $esAnual = false)
    {
        foreach ($materias as &$materia) {
            $sumaCompetencias = 0;
            $totalCompetencias = 0;

            foreach ($materia['competencias'] as &$competencia) {
                if ($esAnual) {
                    $sumaCriterios = 0;
                    $totalCriterios = 0;
                    foreach ($competencia['criterios'] as $criterio) {
                        if ($criterio['nota']) {
                            $sumaCriterios += $criterio['nota'];
                            $totalCriterios++;
                        }
                    }
                    $competencia['promedio'] = $totalCriterios > 0 ? round($sumaCriterios / $totalCriterios, 1) : null;
                } else {
                    $notasValidas = array_filter(array_column($competencia['criterios'], 'nota'));
                    $competencia['promedio'] = !empty($notasValidas) ? round(array_sum($notasValidas) / count($notasValidas), 1) : null;
                }

                if ($competencia['promedio']) {
                    $sumaCompetencias += $competencia['promedio'];
                    $totalCompetencias++;
                }
            }

            $materia['promedio'] = $totalCompetencias > 0 ? round($sumaCompetencias / $totalCompetencias, 1) : null;

            foreach ($materia['competencias_transversales'] as &$competencia) {
                $sumaCriterios = 0;
                $totalCriterios = 0;
                foreach ($competencia['criterios'] as $criterio) {
                    if ($criterio['nota']) {
                        $sumaCriterios += $criterio['nota'];
                        $totalCriterios++;
                    }
                }
                $competencia['promedio'] = $totalCriterios > 0 ? round($sumaCriterios / $totalCriterios, 1) : null;
            }
        }

        return $materias;
    }

    private function calcularPromedioTransversales($materias)
    {
        $sumaTransversales = 0;
        $totalTransversales = 0;

        foreach ($materias as $materia) {
            foreach ($materia['competencias_transversales'] as $competencia) {
                if ($competencia['promedio']) {
                    $sumaTransversales += $competencia['promedio'];
                    $totalTransversales++;
                }
            }
        }

        return $totalTransversales > 0 ? round($sumaTransversales / $totalTransversales, 1) : null;
    }
}
