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
            $periodoActual = Periodo::where('estado', '1')
                ->orderBy('anio', 'desc')
                ->first();

            if ($periodoActual) {
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

        // Variables principales para la vista
        $datosVista = [
            'materias_regulares' => [],
            'competencias_transversales' => [],
            'criterios_transversales' => [],
            'promedios_por_criterio' => [],
            'promedio_general_transversales' => 0,
            'notas_conducta' => collect(),
            'asistencias' => collect(),
            'resumen_asistencias' => ['total' => 0, 'tipos' => []],
            'numero_criterio_global' => 0,
            'numero_competencia_global' => 0,
            'total_materias_regulares' => 0,
            'promedio_general_materias' => 0,
            'materias_procesadas' => [] // Para facilitar el render en la vista
        ];

        if ($matriculaActual && $periodoActual) {
            // 1. OBTENER Y PROCESAR NOTAS DE MATERIAS
            $datosMaterias = $this->procesarNotasMaterias($estudiante->id, $periodoActual, $bimestre);
            $datosVista = array_merge($datosVista, $datosMaterias);

            // 2. OBTENER NOTAS DE CONDUCTA
            $datosVista['notas_conducta'] = $this->obtenerNotasConducta($estudiante->id, $periodoActual, $bimestre);

            // 3. OBTENER ASISTENCIAS
            $datosAsistencias = $this->obtenerAsistencias($estudiante->id, $matriculaActual->grado_id, $periodoActual, $bimestre);
            $datosVista['asistencias'] = $datosAsistencias['asistencias'];
            $datosVista['resumen_asistencias'] = $datosAsistencias['resumen'];

            // 4. CALCULAR ESTADÍSTICAS GLOBALES
            $datosVista['numero_criterio_global'] = $this->calcularTotalCriterios($datosVista['materias_regulares']);
            $datosVista['numero_competencia_global'] = $this->calcularTotalCompetencias($datosVista['materias_regulares']);
            $datosVista['total_materias_regulares'] = count($datosVista['materias_regulares']);
            $datosVista['promedio_general_materias'] = $this->calcularPromedioGeneralMaterias($datosVista['materias_regulares']);

            // 5. PREPARAR DATOS PARA LA VISTA (rowspan y promedios por materia)
            $datosVista['materias_procesadas'] = $this->prepararMateriasParaVista($datosVista['materias_regulares']);
        }

        return view('libreta.index', array_merge([
            'estudiante' => $estudiante,
            'matricula_actual' => $matriculaActual,
            'periodo_actual' => $periodoActual,
            'periodos' => $periodos,
            'colegio' => $colegio,
            'anio_param' => $anio,
            'bimestre_param' => $bimestre,
        ], $datosVista));
    }

    /**
     * Procesar todas las notas de materias
     */
    private function procesarNotasMaterias($estudianteId, $periodoActual, $bimestre)
    {
        $notasEstudiante = Nota::with(['criterio.materiaCompetencia', 'criterio.materia'])
            ->where('estudiante_id', $estudianteId)
            ->where('publico', '!=', '0')
            ->whereHas('criterio', function($query) use ($periodoActual) {
                $query->where('anio', $periodoActual->anio);
            });

        if ($bimestre !== 'anual') {
            $notasEstudiante->where('bimestre', $bimestre);
        }

        $notasEstudiante = $notasEstudiante->get();

        // Agrupar notas por materia
        $notasPorMateria = $this->agruparNotasPorMateria($notasEstudiante);

        // Separar materias regulares y transversales
        $resultados = $this->separarMateriasRegularesTransversales($notasPorMateria);

        // Procesar competencias transversales
        $datosTransversales = $this->procesarCompetenciasTransversales($resultados['competencias_transversales']);

        return array_merge($resultados, $datosTransversales);
    }

    //Agrupar notas por materia, competencia y criterio
    private function agruparNotasPorMateria($notasEstudiante)
    {
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

        return $notasPorMateria;
    }

    //Separar materias regulares de competencias transversales
    private function separarMateriasRegularesTransversales($notasPorMateria)
    {
        $materiasRegulares = [];
        $competenciasTransversales = [];

        foreach ($notasPorMateria as $materiaData) {
            $competencias = collect($materiaData['competencias'])
                ->map(function($competencia) {
                    $competencia['criterios'] = collect($competencia['criterios']);
                    return $competencia;
                })
                ->values();

            // Separar competencias
            $transversales = [];
            $regulares = [];

            foreach ($competencias as $competencia) {
                $competenciaNombre = strtoupper(trim($competencia['competencia_nombre']));
                if ($competenciaNombre == 'COMPETENCIAS TRANSVERSALES' ||
                    str_contains($competenciaNombre, 'TRANSVERSAL')) {
                    $transversales[] = $competencia;
                } else {
                    $regulares[] = $competencia;
                }
            }

            if (count($regulares) > 0) {
                $materiasRegulares[] = [
                    'materia_nombre' => $materiaData['materia_nombre'],
                    'competencias' => $regulares,
                    'competencias_original' => $competencias
                ];
            }

            if (count($transversales) > 0) {
                foreach ($transversales as $transversal) {
                    $competenciasTransversales[] = [
                        'materia_nombre' => $materiaData['materia_nombre'],
                        'competencia' => $transversal
                    ];
                }
            }
        }

        return [
            'materias_regulares' => $materiasRegulares,
            'competencias_transversales' => $competenciasTransversales
        ];
    }

    //Procesar competencias transversales
    private function procesarCompetenciasTransversales($competenciasTransversales)
    {
        $criteriosTransversales = [];
        $promediosPorCriterio = [];
        $promedioGeneralTransversales = 0;
        $sumaPromediosTransversales = 0;
        $totalCompetenciasTransversales = 0;

        // Agrupar criterios por nombre
        foreach ($competenciasTransversales as $transversal) {
            foreach ($transversal['competencia']['criterios'] as $criterio) {
                $criterioNombre = $criterio['criterio_nombre'];

                if (!isset($criteriosTransversales[$criterioNombre])) {
                    $criteriosTransversales[$criterioNombre] = [
                        'notas' => [],
                        'bimestres' => []
                    ];
                }

                if ($criterio['nota']) {
                    $criteriosTransversales[$criterioNombre]['notas'][] = $criterio['nota']['valor'];
                    if ($criterio['nota']['bimestre']) {
                        $criteriosTransversales[$criterioNombre]['bimestres'][] = $criterio['nota']['bimestre'];
                    }
                }
            }
        }

        // Calcular promedios por criterio
        foreach ($criteriosTransversales as $criterioNombre => $data) {
            $totalNotas = count($data['notas']);
            $promediosPorCriterio[$criterioNombre] = $totalNotas > 0 ?
                array_sum($data['notas']) / $totalNotas : 0;
        }

        // Calcular promedio general de transversales
        foreach ($competenciasTransversales as $transversal) {
            $promedioTransversal = 0;
            $criteriosConNota = 0;

            foreach ($transversal['competencia']['criterios'] as $criterio) {
                if ($criterio['nota']) {
                    $promedioTransversal += $criterio['nota']['valor'];
                    $criteriosConNota++;
                }
            }

            if ($criteriosConNota > 0) {
                $promedioTransversalCalculado = $promedioTransversal / $criteriosConNota;
                $sumaPromediosTransversales += $promedioTransversalCalculado;
                $totalCompetenciasTransversales++;
            }
        }

        $promedioGeneralTransversales = $totalCompetenciasTransversales > 0 ?
            $sumaPromediosTransversales / $totalCompetenciasTransversales : 0;

        return [
            'criterios_transversales' => $criteriosTransversales,
            'promedios_por_criterio' => $promediosPorCriterio,
            'promedio_general_transversales' => $promedioGeneralTransversales,
            'total_competencias_transversales' => $totalCompetenciasTransversales,
        ];
    }

    //Obtener notas de conducta
    private function obtenerNotasConducta($estudianteId, $periodoActual, $bimestre)
    {
        $notasConductaQuery = Conductanota::with(['conducta', 'periodo'])
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoActual->id)
            ->where('publico', '!=', '0');

        if ($bimestre !== 'anual') {
            $notasConductaQuery->where('bimestre', $bimestre);
        }

        return $notasConductaQuery->get()
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
    }

    //Obtener asistencias
    private function obtenerAsistencias($estudianteId, $gradoId, $periodoActual, $bimestre)
    {
        $asistenciasQuery = Asistencia::with(['tipoasistencia', 'grado'])
            ->where('estudiante_id', $estudianteId)
            ->where('grado_id', $gradoId)
            ->whereYear('fecha', $periodoActual->anio);

        if ($bimestre !== 'anual') {
            $asistenciasQuery->where('bimestre', $bimestre);
        }

        $asistencias = $asistenciasQuery->orderBy('fecha', 'desc')->get();

        $resumenAsistencias = [
            'total' => 0,
            'tipos' => []
        ];

        if ($asistencias->count() > 0) {
            $resumenAsistencias['total'] = $asistencias->count();

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

        return [
            'asistencias' => $asistencias,
            'resumen' => $resumenAsistencias
        ];
    }

    //Calcular total de criterios
    private function calcularTotalCriterios($materiasRegulares)
    {
        $totalCriterios = 0;

        foreach ($materiasRegulares as $materia) {
            foreach ($materia['competencias'] as $competencia) {
                $criteriosCount = $competencia['criterios']->count();
                $totalCriterios += ($criteriosCount > 0 ? $criteriosCount : 1);
            }
        }

        return $totalCriterios;
    }

    //Calcular total de competencias
    private function calcularTotalCompetencias($materiasRegulares)
    {
        $totalCompetencias = 0;

        foreach ($materiasRegulares as $materia) {
            $totalCompetencias += count($materia['competencias']);
        }

        return $totalCompetencias;
    }

    // Calcular promedio general de materias
    private function calcularPromedioGeneralMaterias($materiasRegulares)
    {
        $sumaPromedios = 0;
        $materiasConPromedio = 0;

        foreach ($materiasRegulares as $materia) {
            $promedioMateria = 0;
            $totalCompetencias = 0;

            foreach ($materia['competencias'] as $competencia) {
                $promedioCompetencia = 0;
                $criteriosConNota = 0;

                foreach ($competencia['criterios'] as $criterio) {
                    if ($criterio['nota']) {
                        $promedioCompetencia += $criterio['nota']['valor'];
                        $criteriosConNota++;
                    }
                }

                if ($criteriosConNota > 0) {
                    $promedioCompetenciaCalculado = $promedioCompetencia / $criteriosConNota;
                    $promedioMateria += $promedioCompetenciaCalculado;
                    $totalCompetencias++;
                }
            }

            if ($totalCompetencias > 0) {
                $promedioMateriaCalculado = $promedioMateria / $totalCompetencias;
                $sumaPromedios += $promedioMateriaCalculado;
                $materiasConPromedio++;
            }
        }

        return $materiasConPromedio > 0 ? $sumaPromedios / $materiasConPromedio : 0;
    }

    //Preparar datos de materias para la vista (rowspan y promedios)
    private function prepararMateriasParaVista($materiasRegulares)
    {
        $materiasProcesadas = [];

        foreach ($materiasRegulares as $materia) {
            $rowspanMateria = 0;
            $promedioMateria = 0;
            $totalCompetencias = 0;
            $competenciasProcesadas = [];

            foreach ($materia['competencias'] as $competencia) {
                $criteriosCount = $competencia['criterios']->count();
                $rowspanMateria += ($criteriosCount > 0 ? $criteriosCount + 1 : 2);

                // Calcular promedio de competencia
                $promedioCompetencia = 0;
                $criteriosConNota = 0;
                $ultimoCriterio = null;

                foreach ($competencia['criterios'] as $criterio) {
                    $ultimoCriterio = $criterio;
                    if ($criterio['nota']) {
                        $promedioCompetencia += $criterio['nota']['valor'];
                        $criteriosConNota++;
                    }
                }

                $promedioCompetenciaCalculado = $criteriosConNota > 0 ?
                    $promedioCompetencia / $criteriosConNota : 0;

                if ($criteriosConNota > 0) {
                    $promedioMateria += $promedioCompetenciaCalculado;
                    $totalCompetencias++;
                }

                $competenciasProcesadas[] = [
                    'nombre' => $competencia['competencia_nombre'],
                    'criterios' => $competencia['criterios'],
                    'criterios_count' => $criteriosCount,
                    'promedio' => $promedioCompetenciaCalculado,
                    'ultimo_criterio' => $ultimoCriterio
                ];
            }

            $promedioMateriaCalculado = $totalCompetencias > 0 ?
                $promedioMateria / $totalCompetencias : 0;

            $materiasProcesadas[] = [
                'nombre' => $materia['materia_nombre'],
                'competencias' => $competenciasProcesadas,
                'rowspan' => $rowspanMateria,
                'promedio' => $promedioMateriaCalculado,
                'total_competencias' => $totalCompetencias
            ];
        }

        return $materiasProcesadas;
    }
    public function pdf(Request $request, $anio, $bimestre)
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

        // Obtener el tipo de PDF (cuantitativo o cualitativo)
        $tipoPdf = $request->input('tipo_pdf', 'cuantitativo');
        if (!in_array($tipoPdf, ['cuantitativo', 'cualitativo'])) {
            $tipoPdf = 'cuantitativo';
        }

        // Buscar el periodo por año (anio)
        $periodoActual = Periodo::where('anio', $anio)->first();

        if (!$periodoActual) {
            $periodoActual = Periodo::where('estado', '1')
                ->orderBy('anio', 'desc')
                ->first();

            if ($periodoActual) {
                return redirect()->route('libreta.pdf', [
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

        // Obtener datos de la IE (Colegio)
        $colegio = Colegio::configuracion();

        // Variables principales para la vista
        $datosVista = [
            'materias_regulares' => [],
            'competencias_transversales' => [],
            'criterios_transversales' => [],
            'promedios_por_criterio' => [],
            'promedio_general_transversales' => 0,
            'notas_conducta' => collect(),
            'asistencias' => collect(),
            'resumen_asistencias' => ['total' => 0, 'tipos' => []],
            'numero_criterio_global' => 0,
            'numero_competencia_global' => 0,
            'total_materias_regulares' => 0,
            'promedio_general_materias' => 0,
            'materias_procesadas' => []
        ];

        if ($matriculaActual && $periodoActual) {
            // 1. OBTENER Y PROCESAR NOTAS DE MATERIAS
            $datosMaterias = $this->procesarNotasMaterias($estudiante->id, $periodoActual, $bimestre);
            $datosVista = array_merge($datosVista, $datosMaterias);

            // 2. OBTENER NOTAS DE CONDUCTA
            $datosVista['notas_conducta'] = $this->obtenerNotasConducta($estudiante->id, $periodoActual, $bimestre);

            // 3. OBTENER ASISTENCIAS
            $datosAsistencias = $this->obtenerAsistencias($estudiante->id, $matriculaActual->grado_id, $periodoActual, $bimestre);
            $datosVista['asistencias'] = $datosAsistencias['asistencias'];
            $datosVista['resumen_asistencias'] = $datosAsistencias['resumen'];

            // 4. CALCULAR ESTADÍSTICAS GLOBALES
            $datosVista['numero_criterio_global'] = $this->calcularTotalCriterios($datosVista['materias_regulares']);
            $datosVista['numero_competencia_global'] = $this->calcularTotalCompetencias($datosVista['materias_regulares']);
            $datosVista['total_materias_regulares'] = count($datosVista['materias_regulares']);
            $datosVista['promedio_general_materias'] = $this->calcularPromedioGeneralMaterias($datosVista['materias_regulares']);

            // 5. PREPARAR DATOS PARA LA VISTA (rowspan y promedios por materia)
            $datosVista['materias_procesadas'] = $this->prepararMateriasParaVista($datosVista['materias_regulares']);
        }

        // Preparar datos para el PDF
        $pdfData = array_merge([
            'estudiante' => $estudiante,
            'matricula_actual' => $matriculaActual,
            'periodo_actual' => $periodoActual,
            'colegio' => $colegio,
            'anio_param' => $anio,
            'bimestre_param' => $bimestre,
            'tipo_pdf' => $tipoPdf,
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'user' => $user,
            'modo_pdf' => true,
        ], $datosVista);

        // Aplicar conversión cualitativa si es necesario
        if ($tipoPdf === 'cualitativo') {
            $pdfData = $this->convertirDatosACualitativo($pdfData);
        }

        // Configurar opciones del PDF
        $options = [
            'defaultFont' => 'Arial',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'dpi' => 150,
        ];

        // Generar nombre del archivo
        $nombreArchivo = 'libreta_' .
                        $estudiante->user->dni . '_' .
                        $anio . '_' .
                        ($bimestre == 'anual' ? 'anual' : 'bim' . $bimestre) . '_' .
                        $tipoPdf . '_' .
                        date('YmdHis') . '.pdf';

        // Generar PDF
        $pdf = Pdf::loadView('libreta.pdf', $pdfData)
            ->setOptions($options)
            ->setPaper('A4', 'portrait');

        // Forzar descarga del PDF
        return $pdf->download($nombreArchivo);
    }

    /**
     * Convertir datos numéricos a cualitativos
     */
    private function convertirDatosACualitativo($datos)
    {
        // Función para convertir número a letra CON REDONDEO
        $convertirNota = function($nota) {
            if (is_numeric($nota)) {
                $nota = floatval($nota);
                // Redondear al entero más cercano
                $notaRedondeada = round($nota);

                // Ajustar para que esté en rango 1-4
                if ($notaRedondeada > 4) $notaRedondeada = 4;
                if ($notaRedondeada < 1) $notaRedondeada = 1;

                // Convertir a letra
                switch ($notaRedondeada) {
                    case 4: return 'AD';
                    case 3: return 'A';
                    case 2: return 'B';
                    case 1: return 'C';
                    default: return 'C';
                }
            }
            return $nota;
        };

        // 1. CONVERTIR CALIFICACIONES DE CRITERIOS EN MATERIAS REGULARES
        if (isset($datos['materias_procesadas']) && is_array($datos['materias_procesadas'])) {
            foreach ($datos['materias_procesadas'] as &$materia) {
                // Convertir promedio de la materia (ya redondeado)
                if (isset($materia['promedio']) && is_numeric($materia['promedio'])) {
                    $materia['promedio'] = $convertirNota($materia['promedio']);
                }

                // Procesar competencias
                if (isset($materia['competencias']) && is_array($materia['competencias'])) {
                    foreach ($materia['competencias'] as &$competencia) {
                        // Convertir promedio de competencia
                        if (isset($competencia['promedio']) && is_numeric($competencia['promedio'])) {
                            $competencia['promedio'] = $convertirNota($competencia['promedio']);
                        }

                        // Convertir notas de criterios
                        if (isset($competencia['criterios']) && is_array($competencia['criterios'])) {
                            foreach ($competencia['criterios'] as &$criterio) {
                                // Asegurarnos de convertir el valor de la nota
                                if (isset($criterio['nota']['valor']) && is_numeric($criterio['nota']['valor'])) {
                                    $criterio['nota']['valor'] = $convertirNota($criterio['nota']['valor']);
                                }
                            }
                        }
                    }
                }
            }
        }

        // 2. CONVERTIR COMPETENCIAS TRANSVERSALES
        if (isset($datos['competencias_transversales']) && is_array($datos['competencias_transversales'])) {
            foreach ($datos['competencias_transversales'] as &$transversal) {
                if (isset($transversal['competencia']['criterios']) && is_array($transversal['competencia']['criterios'])) {
                    foreach ($transversal['competencia']['criterios'] as &$criterio) {
                        if (isset($criterio['nota']['valor']) && is_numeric($criterio['nota']['valor'])) {
                            $criterio['nota']['valor'] = $convertirNota($criterio['nota']['valor']);
                        }
                    }
                }
            }
        }

        // 3. CONVERTIR PROMEDIOS POR CRITERIO EN TRANSVERSALES
        if (isset($datos['promedios_por_criterio']) && is_array($datos['promedios_por_criterio'])) {
            foreach ($datos['promedios_por_criterio'] as &$promedio) {
                if (is_numeric($promedio)) {
                    $promedio = $convertirNota($promedio);
                }
            }
        }

        // 4. CONVERTIR PROMEDIO GENERAL DE TRANSVERSALES
        if (isset($datos['promedio_general_transversales']) && is_numeric($datos['promedio_general_transversales'])) {
            $datos['promedio_general_transversales'] = $convertirNota($datos['promedio_general_transversales']);
        }

        // 5. CONVERTIR PROMEDIO GENERAL DE MATERIAS
        if (isset($datos['promedio_general_materias']) && is_numeric($datos['promedio_general_materias'])) {
            $datos['promedio_general_materias'] = $convertirNota($datos['promedio_general_materias']);
        }

        // 6. CONVERTIR NOTAS DE CONDUCTA
        if (isset($datos['notas_conducta']) && $datos['notas_conducta']->count() > 0) {
            $datos['notas_conducta'] = $datos['notas_conducta']->map(function($notaConducta) use ($convertirNota) {
                if (isset($notaConducta['valor']) && is_numeric($notaConducta['valor'])) {
                    $notaConducta['valor'] = $convertirNota($notaConducta['valor']);
                }
                return $notaConducta;
            });
        }

        // 7. CONVERTIR MATERIAS REGULARES (datos originales no procesados)
        if (isset($datos['materias_regulares']) && is_array($datos['materias_regulares'])) {
            foreach ($datos['materias_regulares'] as &$materia) {
                if (isset($materia['competencias']) && is_array($materia['competencias'])) {
                    foreach ($materia['competencias'] as &$competencia) {
                        // Convertir promedio de competencia
                        if (isset($competencia['promedio']) && is_numeric($competencia['promedio'])) {
                            $competencia['promedio'] = $convertirNota($competencia['promedio']);
                        }

                        // Convertir criterios
                        if (isset($competencia['criterios']) && is_array($competencia['criterios'])) {
                            foreach ($competencia['criterios'] as &$criterio) {
                                if (isset($criterio['nota']['valor']) && is_numeric($criterio['nota']['valor'])) {
                                    $criterio['nota']['valor'] = $convertirNota($criterio['nota']['valor']);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $datos;
    }
}
