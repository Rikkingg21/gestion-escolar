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
use App\Models\Asistencia\Tipoasistencia;
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

        $notasMaterias = $this->getNotasMaterias($estudiante->id, $periodoActual, $sigla);
        $materiasAgrupadas = $this->agruparNotasPorMateria($notasMaterias, $esAnual, $matriculaActual->grado_id ?? null);
        $materiasConPromedios = $this->calcularPromedios($materiasAgrupadas, $esAnual);
        $materiasConRowspan = $this->calcularRowspanMaterias($materiasConPromedios);

        $competenciasTransversalesAgrupadas = $this->agruparCompetenciasTransversales($materiasConPromedios, $esAnual);
        $competenciasTransversalesItems = $this->agruparCompetenciasTransversales($materiasConPromedios);

        $todasLasConductas = $this->obtenerTodasLasConductas($estudiante->id, $periodoActual, $sigla, $matriculaActual->grado_id ?? null);
        $conductasEnriquecidas = $this->enriquecerConductas($todasLasConductas);

        $asistencias = $this->obtenerAsistencias($estudiante->id, $periodoActual, $sigla, $matriculaActual->grado_id ?? null);

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

        // Agregar los datos adicionales que necesita la vista
        $datosVista['materias'] = $materiasConRowspan;
        $datosVista['todas_las_conductas'] = $conductasEnriquecidas;
        $datosVista['competencias_transversales_items'] = $competenciasTransversalesItems;
        $datosVista['competencias_transversales_agrupadas'] = $competenciasTransversalesAgrupadas;
        $datosVista['asistencias'] = $asistencias;
        $datosVista['titulo_periodo'] = $esAnual ? 'EVALUACIÓN ANUAL' : strtoupper($sigla);
        $datosVista['titulo_conducta'] = $esAnual ? 'PROMEDIO ANUAL' : "CALIFICACIÓN " . strtoupper($sigla);
        $datosVista['datos_estudiante'] = $this->getDatosEstudiante($estudiante, $matriculaActual, $colegio);
        $datosVista['promedio_general_bimestre'] = $this->calcularPromedioGeneralBimestre($materiasConRowspan);
        $datosVista['sin_criterios'] = $this->calcularSinCriterios($materiasConRowspan);

        return view('libreta.index', $datosVista);
    }
    private function calcularSinCriterios($materias)
    {
        $totalCriterios = 0;
        foreach ($materias as $materia) {
            foreach ($materia['competencias'] as $competencia) {
                $totalCriterios += count($competencia['criterios']);
            }
        }
        return $totalCriterios == 0;
    }
    private function redondearNota($nota)
    {
        return ($nota - floor($nota) >= 0.5) ? ceil($nota) : floor($nota);
    }
    private function obtenerTodasLasConductas($estudianteId, $periodoActual, $sigla, $gradoId)
    {
        $cursos = Cursogradosecnivanio::with('materia')
            ->where('periodo_id', $periodoActual->id)
            ->where('grado_id', $gradoId)
            ->get();

        $bimestres = Periodobimestre::where('periodo_id', $periodoActual->id)
            ->where('tipo_bimestre', 'A')
            ->orderBy('bimestre')
            ->get();

        $periodoBimestreSeleccionado = ($sigla !== 'anual')
            ? $bimestres->firstWhere('sigla', $sigla)
            : null;

        $conductasQuery = Conducta::whereHas('periodosBimestres', function($query) use ($periodoActual, $periodoBimestreSeleccionado, $sigla) {
            $query->where('periodo_id', $periodoActual->id)
                ->whereNull('conducta_periodo_bimestres.deleted_at');

            if ($sigla !== 'anual' && $periodoBimestreSeleccionado) {
                $query->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
            }
        });

        $todasLasConductasDB = $conductasQuery->distinct()->get();

        if ($todasLasConductasDB->isEmpty()) {
            return [];
        }

        $query = Conductaperiodobimestrenota::with([
                'conductaPeriodoBimestre' => function($query) {
                    $query->whereNull('deleted_at');
                },
                'conductaPeriodoBimestre.conducta',
                'periodoBimestre',
                'curso_grado_sec_niv_anio.materia'
            ])
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoActual->id)
            ->where('publico', '!=', '0')
            ->whereHas('conductaPeriodoBimestre', function($query) {
                $query->whereNull('deleted_at');
            });

        if ($sigla !== 'anual' && $periodoBimestreSeleccionado) {
            $query->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
        }

        $notasExistentes = $query->get();

        if ($notasExistentes->isEmpty() && $sigla !== 'anual') {
            return $this->formatoConductasSinNotas($todasLasConductasDB, $cursos);
        }

        $notasMap = [];
        foreach ($notasExistentes as $nota) {
            if (!$nota->conductaPeriodoBimestre || $nota->conductaPeriodoBimestre->trashed()) {
                continue;
            }

            $conductaId = $nota->conductaPeriodoBimestre->conducta_id ?? null;
            $cursoId = $nota->curso_grado_sec_niv_anio_id;
            $bimestreId = $nota->periodo_bimestre_id;

            if ($conductaId && $cursoId && $bimestreId) {
                $key = "{$conductaId}|{$cursoId}|{$bimestreId}";
                $notasMap[$key] = $nota->nota;
            }
        }

        if ($sigla === 'anual') {
            return $this->procesarConductasAnual($todasLasConductasDB, $cursos, $bimestres, $notasMap);
        }

        return $this->procesarConductasBimestral($todasLasConductasDB, $cursos, $periodoBimestreSeleccionado, $notasMap);
    }
    private function formatoConductasSinNotas($conductas, $cursos)
    {
        $resultado = [];
        foreach ($conductas as $conducta) {
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
    private function procesarConductasAnual($conductas, $cursos, $bimestres, $notasMap)
    {
        $totalBimestres = $bimestres->count();
        $hayNotas = !empty($notasMap);

        if (!$hayNotas) {
            return $this->formatoConductasAnualSinNotas($conductas, $totalBimestres);
        }

        $resultado = [];
        foreach ($conductas as $conducta) {
            $promediosPorBimestre = [];
            $bimestresCompletos = [];
            $bimestresIncompletos = [];

            foreach ($bimestres as $bimestre) {
                $notasBimestre = [];
                $notasEncontradas = 0;

                foreach ($cursos as $curso) {
                    $key = $conducta->id . '|' . $curso->id . '|' . $bimestre->id;

                    if (isset($notasMap[$key])) {
                        $notasBimestre[] = $notasMap[$key];
                        $notasEncontradas++;
                    } else {
                        $notasBimestre[] = 1;
                    }
                }

                $promedio = round(array_sum($notasBimestre) / count($notasBimestre), 1);
                $promediosPorBimestre[$bimestre->bimestre] = $promedio;

                if ($notasEncontradas == $cursos->count()) {
                    $bimestresCompletos[] = $bimestre->bimestre;
                } elseif ($notasEncontradas > 0) {
                    $bimestresIncompletos[] = $bimestre->bimestre;
                }
            }

            $estadoMensaje = $this->generarEstadoMensaje($bimestresCompletos, $bimestresIncompletos, $totalBimestres);
            $tieneTooltip = $estadoMensaje !== "{$totalBimestres}/{$totalBimestres} Bimestres completos";

            if (count($promediosPorBimestre) == $totalBimestres) {
                $promedioAnual = round(array_sum($promediosPorBimestre) / $totalBimestres, 1);
                $resultado[] = [
                    'nombre' => $conducta->nombre,
                    'nota' => $this->redondearNota($promedioAnual),
                    'nota_original' => $this->redondearNota($promedioAnual),
                    'estado' => $estadoMensaje,
                    'tiene_tooltip' => $tieneTooltip,
                    'es_guion' => false
                ];
            } else {
                foreach ($promediosPorBimestre as $bimestreNum => $promedio) {
                    $bimestreSigla = $this->getSiglaByBimestre($bimestreNum);
                    $resultado[] = [
                        'nombre' => $conducta->nombre . ' (' . $bimestreSigla . ')',
                        'nota' => $this->redondearNota($promedio),
                        'nota_original' => $this->redondearNota($promedio),
                        'estado' => $estadoMensaje,
                        'tiene_tooltip' => $tieneTooltip,
                        'es_guion' => false
                    ];
                }
            }
        }

        return $resultado;
    }
    private function formatoConductasAnualSinNotas($conductas, $totalBimestres)
    {
        $resultado = [];
        foreach ($conductas as $conducta) {
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
    private function procesarConductasBimestral($conductas, $cursos, $periodoBimestre, $notasMap)
    {
        $bimestreId = $periodoBimestre ? $periodoBimestre->id : null;
        $totalConductas = $conductas->count();
        $totalMaterias = $cursos->count();

        $materiasCompletas = 0;
        $materiasIncompletas = 0;

        foreach ($cursos as $curso) {
            $conductasCompletas = 0;
            foreach ($conductas as $conducta) {
                $key = $conducta->id . '|' . $curso->id . '|' . $bimestreId;
                if (isset($notasMap[$key])) {
                    $conductasCompletas++;
                }
            }
            if ($conductasCompletas == $totalConductas) {
                $materiasCompletas++;
            } elseif ($conductasCompletas > 0) {
                $materiasIncompletas++;
            }
        }

        $estadoGeneral = "{$materiasCompletas}/{$totalMaterias} materias completas";
        if ($materiasIncompletas > 0) {
            $estadoGeneral .= " - {$materiasIncompletas} con notas faltantes";
        }

        $resultado = [];
        foreach ($conductas as $conducta) {
            $notasConducta = [];
            foreach ($cursos as $curso) {
                $key = $conducta->id . '|' . $curso->id . '|' . $bimestreId;
                $notasConducta[] = isset($notasMap[$key]) ? $notasMap[$key] : 1;
            }

            $promedio = round(array_sum($notasConducta) / count($notasConducta), 1);
            $resultado[] = [
                'nombre' => $conducta->nombre,
                'nota' => $this->redondearNota($promedio),
                'nota_original' => $this->redondearNota($promedio),
                'estado' => $estadoGeneral,
                'tiene_tooltip' => true,
                'es_guion' => false
            ];
        }

        return $resultado;
    }
    private function generarEstadoMensaje($bimestresCompletos, $bimestresIncompletos, $totalBimestres)
    {
        if (count($bimestresCompletos) == $totalBimestres) {
            return "{$totalBimestres}/{$totalBimestres} Bimestres completos";
        }

        if (count($bimestresCompletos) > 0 || count($bimestresIncompletos) > 0) {
            $bimestresConNotas = array_merge($bimestresCompletos, $bimestresIncompletos);
            sort($bimestresConNotas);
            $siglas = ['I', 'II', 'III', 'IV'];
            $bimestresTexto = implode(', ', array_map(function($b) use ($siglas) {
                return $siglas[$b-1] . ' Bim';
            }, $bimestresConNotas));

            return count($bimestresCompletos) . "/{$totalBimestres} Bim - {$bimestresTexto}" .
                   (count($bimestresIncompletos) > 0 ? " con notas faltantes" : "");
        }

        return "0/{$totalBimestres} Bimestres - Sin notas registradas";
    }
    private function getSiglaByBimestre($bimestreNum)
    {
        $siglas = ['I', 'II', 'III', 'IV'];
        return $siglas[$bimestreNum - 1] ?? '';
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
            ->map(fn($periodo) => $periodo->only(['id', 'anio', 'nombre', 'estado', 'descripcion']));
    }
    private function getPeriodoActual($anio, $periodos)
    {
        $periodo = Periodo::where('anio', $anio)->first();
        return ($periodo && collect($periodos)->contains('id', $periodo->id)) ? $periodo : null;
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
            ->map(fn($bimestre) => [
                'sigla' => $bimestre->sigla,
                'bimestre' => $bimestre->bimestre,
                'nombre' => $bimestre->sigla . ' - Bimestre ' . $bimestre->bimestre,
                'fecha_inicio' => $bimestre->fecha_inicio,
                'fecha_fin' => $bimestre->fecha_fin,
            ]);

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
        return ($sigla && in_array($sigla, $siglasValidas)) ? $sigla : 'anual';
    }
    private function prepararDatosVista($params)
    {
        $bimestreSeleccionado = $params['bimestres']->firstWhere('sigla', $params['sigla']);

        return [
            'estudiante' => $params['estudiante'],
            'matricula_actual' => $params['matriculaActual'],
            'periodo_actual' => $params['periodoActual']->only(['id', 'anio', 'nombre', 'descripcion']),
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
    private function agruparNotasPorMateria($notas, $esAnual = false, $gradoId = null)
    {
        $periodoActual = Periodo::where('anio', request()->segment(2))->first();
        if (!$periodoActual) {
            return [];
        }

        $siglaParam = request()->segment(3) ?? 'anual';
        $periodoBimestreSeleccionado = null;

        if ($siglaParam !== 'anual') {
            $periodoBimestreSeleccionado = Periodobimestre::where('periodo_id', $periodoActual->id)
                ->where('sigla', $siglaParam)
                ->where('tipo_bimestre', 'A')
                ->first();
        }

        if (!$gradoId) {
            $estudiante = $this->getEstudiante();
            if ($estudiante && $periodoActual) {
                $matricula = Matricula::where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodoActual->id)
                    ->first();
                $gradoId = $matricula->grado_id ?? null;
            }
        }

        if (!$gradoId) {
            return [];
        }

        $cursos = Cursogradosecnivanio::with(['materia', 'grado'])
            ->where('periodo_id', $periodoActual->id)
            ->where('grado_id', $gradoId)
            ->get()
            ->unique('materia_id');

        if ($cursos->isEmpty()) {
            return [];
        }

        $materias = [];

        foreach ($cursos as $curso) {
            $materiaId = $curso->materia_id;
            $materiaNombre = $curso->materia->nombre ?? 'Sin materia';

            $competencias = Materiacompetencia::where('materia_id', $materiaId)->get();

            if (!isset($materias[$materiaId])) {
                $materias[$materiaId] = [
                    'id' => $materiaId,
                    'nombre' => $materiaNombre,
                    'competencias' => [],
                    'competencias_transversales' => [],
                ];
            }

            foreach ($competencias as $competencia) {
                $competenciaNombre = $competencia->nombre;
                $esTransversal = str_contains(strtoupper($competenciaNombre), 'TRANSVERSAL');
                $targetArray = $esTransversal ? 'competencias_transversales' : 'competencias';

                $criteriosQuery = Materiacriterio::where('materia_competencia_id', $competencia->id)
                    ->where('grado_id', $gradoId);

                if (!$esAnual && $periodoBimestreSeleccionado) {
                    $criteriosQuery->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
                }

                // Para modo anual, NO filtramos por bimestre, pero guardamos la información del bimestre
                $criterios = $criteriosQuery->get();

                if (!isset($materias[$materiaId][$targetArray][$competencia->id])) {
                    $materias[$materiaId][$targetArray][$competencia->id] = [
                        'id' => $competencia->id,
                        'nombre' => $competenciaNombre,
                        'criterios' => [],
                        'es_transversal' => $esTransversal
                    ];

                    foreach ($criterios as $criterio) {
                        // Obtener la sigla del bimestre
                        $siglaBimestre = null;
                        $bimestreNum = null;
                        if ($criterio->periodo_bimestre_id) {
                            $periodoBimestre = Periodobimestre::find($criterio->periodo_bimestre_id);
                            if ($periodoBimestre) {
                                $siglaBimestre = $periodoBimestre->sigla;
                                $bimestreNum = $periodoBimestre->bimestre;
                            }
                        }

                        $materias[$materiaId][$targetArray][$competencia->id]['criterios'][$criterio->id] = [
                            'id' => $criterio->id,
                            'nombre' => $criterio->nombre,
                            'nota' => null,
                            'nota_original' => null,
                            'publico' => '0',
                            'tiene_nota' => false,
                            'periodo_bimestre_id' => $criterio->periodo_bimestre_id,
                            'sigla_bimestre' => $siglaBimestre,
                            'bimestre_num' => $bimestreNum
                        ];
                    }
                }
            }
        }

        $notasMap = [];
        foreach ($notas as $nota) {
            $criterio = $nota->criterio;
            if ($criterio) {
                $key = $criterio->materia_id . '|' . $criterio->materia_competencia_id . '|' . $criterio->id;
                $notasMap[$key] = $nota;
            }
        }

        foreach ($notas as $nota) {
            $criterio = $nota->criterio;
            if (!$criterio) continue;

            $materiaId = $criterio->materia_id;
            $competenciaId = $criterio->materia_competencia_id;
            $criterioId = $criterio->id;

            if (isset($materias[$materiaId])) {
                foreach (['competencias', 'competencias_transversales'] as $tipo) {
                    if (isset($materias[$materiaId][$tipo][$competenciaId])) {
                        foreach ($materias[$materiaId][$tipo][$competenciaId]['criterios'] as &$criterioItem) {
                            if ($criterioItem['id'] == $criterioId) {
                                $criterioItem['nota'] = $nota->nota;
                                $criterioItem['nota_original'] = $nota->nota;
                                $criterioItem['publico'] = $nota->publico;
                                $criterioItem['tiene_nota'] = true;
                                break;
                            }
                        }
                        break;
                    }
                }
            }
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
    private function calcularRowspanMaterias($materias)
    {
        foreach ($materias as &$materia) {
            $rowspan = 0;
            foreach ($materia['competencias'] as $competencia) {
                $rowspan += count($competencia['criterios']) + 1;
            }
            $materia['rowspan'] = $rowspan;
        }

        return $materias;
    }
    private function getDatosEstudiante($estudiante, $matricula, $colegio)
    {
        $nombreCompleto = trim(sprintf(
            '%s %s, %s',
            $estudiante->user->apellido_paterno ?? '',
            $estudiante->user->apellido_materno ?? '',
            $estudiante->user->nombre ?? ''
        ));

        return [
            'UGEL' => $colegio->ugel ?? 'Tacna',
            'II.EE' => $colegio->nombre ?? 'NO REGISTRADO',
            'NIVEL' => $matricula->grado->nivel ?? 'No disponible',
            'GRADO' => ($matricula->grado->grado ?? 'No disponible') . '°',
            'SECCIÓN' => '"' . ($matricula->grado->seccion ?? 'No disponible') . '"',
            'ESTUDIANTE' => $nombreCompleto,
            'DNI' => $estudiante->user->dni ?? 'No disponible',
        ];
    }
    private function calcularPromedioGeneralBimestre($materias)
    {
        $suma = 0;
        $total = 0;

        foreach ($materias as $materia) {
            if (isset($materia['promedio']) && $materia['promedio'] !== null) {
                $suma += $materia['promedio'];
                $total++;
            }
        }

        return $total > 0 ? round($suma / $total, 1) : 0;
    }
    private function enriquecerConductas($conductas)
    {
        if (empty($conductas)) {
            return [];
        }

        foreach ($conductas as &$conducta) {
            $notaRaw = $conducta['nota'] ?? '-';
            $esBaja = false;

            if ($notaRaw !== '-') {
                $notaNumerica = is_numeric($notaRaw) ? (float)$notaRaw : (float)$notaRaw;
                if ($notaNumerica <= 2 || $notaRaw === 'C' || $notaRaw === 'B') {
                    $esBaja = true;
                }
            }

            $conducta['clase_color'] = $esBaja ? 'text-danger fw-bold' : '';
            $conducta['es_guion'] = $notaRaw === '-';
        }

        return $conductas;
    }
    private function agruparCompetenciasTransversales($materias, $esAnual = false)
    {
        $criteriosMap = [];

        foreach ($materias as $materia) {
            foreach ($materia['competencias_transversales'] as $competencia) {
                foreach ($competencia['criterios'] as $criterio) {
                    $criterioNombre = $criterio['nombre'];
                    $nota = $criterio['nota'];
                    $siglaBimestre = $criterio['sigla_bimestre'] ?? null;
                    $bimestreNum = $criterio['bimestre_num'] ?? null;
                    $materiaNombre = $materia['nombre'];

                    // Clave principal: solo el nombre del criterio (para el promedio general)
                    if (!isset($criteriosMap[$criterioNombre])) {
                        $criteriosMap[$criterioNombre] = [
                            'nombre' => $criterioNombre,
                            'sumaNotas' => 0,
                            'totalNotas' => 0,
                            'totalMaterias' => 0,
                            'detalle' => []  // Aquí guardamos el detalle por materia y bimestre
                        ];
                    }

                    // Contar total de materias (para el badge)
                    $criteriosMap[$criterioNombre]['totalMaterias']++;

                    // Sumar para el promedio general
                    if ($nota !== null) {
                        $criteriosMap[$criterioNombre]['sumaNotas'] += $nota;
                        $criteriosMap[$criterioNombre]['totalNotas']++;
                    }

                    // Guardar detalle (incluye bimestre para modo anual)
                    $criteriosMap[$criterioNombre]['detalle'][] = [
                        'materia' => $materiaNombre,
                        'nota' => $nota,
                        'sigla_bimestre' => $siglaBimestre,
                        'bimestre_num' => $bimestreNum
                    ];
                }
            }
        }

        $resultado = [];
        foreach ($criteriosMap as $criterio) {
            $promedioReal = $criterio['totalNotas'] > 0
                ? $criterio['sumaNotas'] / $criterio['totalNotas']
                : null;

            $promedioRedondeado = $promedioReal !== null
                ? $this->redondearNota($promedioReal)
                : null;

            $materiasCalificadas = $criterio['totalNotas'];
            $totalMaterias = $criterio['totalMaterias'];
            $faltantes = $totalMaterias - $materiasCalificadas;

            $resultado[] = [
                'criterio' => $criterio['nombre'],
                'promedio' => $promedioRedondeado,
                'promedio_real' => $promedioReal,
                'total_materias' => $totalMaterias,
                'materias_calificadas' => $materiasCalificadas,
                'faltantes' => $faltantes,
                'detalle' => $criterio['detalle']
            ];
        }

        return $resultado;
    }
    private function obtenerAsistencias($estudianteId, $periodoActual, $sigla, $gradoId)
    {
        // Obtener los bimestres del periodo
        $bimestres = Periodobimestre::where('periodo_id', $periodoActual->id)
            ->where('tipo_bimestre', 'A')
            ->orderBy('bimestre')
            ->get();

        // Determinar el bimestre seleccionado
        $periodoBimestreSeleccionado = ($sigla !== 'anual')
            ? $bimestres->firstWhere('sigla', $sigla)
            : null;

        // Construir la consulta de asistencias
        $query = Asistencia::with(['tipoasistencia'])
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoActual->id)
            ->where('grado_id', $gradoId);

        // Filtrar por bimestre si no es anual
        if ($sigla !== 'anual' && $periodoBimestreSeleccionado) {
            $query->where('periodobimestre_id', $periodoBimestreSeleccionado->id);
        }

        $asistencias = $query->get();

        // Agrupar por tipo de asistencia
        $tiposAsistencia = Tipoasistencia::all();
        $resultado = [];

        foreach ($tiposAsistencia as $tipo) {
            $count = $asistencias->where('tipo_asistencia_id', $tipo->id)->count();

            $resultado[] = [
                'tipo' => $tipo->nombre,
                'color' => $tipo->color_hex,
                'total' => $count
            ];
        }

        // Filtrar tipos que tienen al menos un registro
        $resultado = array_filter($resultado, function($item) {
            return $item['total'] > 0;
        });

        return array_values($resultado);
    }
}
