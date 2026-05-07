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

private function obtenerTodasLasConductas($estudianteId, $periodoActual, $sigla, $gradoId)
{
    // Obtener todos los cursos del grado del estudiante (materias)
    $cursos = Cursogradosecnivanio::with('materia')
        ->where('periodo_id', $periodoActual->id)
        ->where('grado_id', $gradoId)
        ->get();

    // Obtener todas las conductas disponibles para este periodo
    $todasLasConductasDB = Conducta::where('estado', '1')->get();

    // Obtener los bimestres del periodo
    $bimestres = Periodobimestre::where('periodo_id', $periodoActual->id)
        ->where('tipo_bimestre', 'A')
        ->orderBy('bimestre')
        ->get();

    // Obtener las notas existentes
    $query = Conductaperiodobimestrenota::with([
            'conductaPeriodoBimestre.conducta',
            'periodoBimestre',
            'curso_grado_sec_niv_anio.materia'
        ])
        ->where('estudiante_id', $estudianteId)
        ->where('periodo_id', $periodoActual->id)
        ->where('publico', '!=', '0');

    // Si es modo bimestre, filtrar por el bimestre seleccionado
    if ($sigla !== 'anual') {
        $periodoBimestre = $bimestres->firstWhere('sigla', $sigla);
        if ($periodoBimestre) {
            $query->where('periodo_bimestre_id', $periodoBimestre->id);
        }
    }

    $notasExistentes = $query->get();

    // Crear un array de notas existentes para fácil acceso
    $notasMap = [];
    foreach ($notasExistentes as $nota) {
        $conductaId = $nota->conductaPeriodoBimestre->conducta_id ?? null;
        $cursoId = $nota->curso_grado_sec_niv_anio_id;
        $bimestreId = $nota->periodo_bimestre_id;

        if ($conductaId && $cursoId && $bimestreId) {
            $key = $conductaId . '|' . $cursoId . '|' . $bimestreId;
            $notasMap[$key] = $nota->nota;
        }
    }

    // Si es modo anual, necesitamos generar todas las combinaciones
    if ($sigla === 'anual') {
        $todasLasConductas = [];

        foreach ($todasLasConductasDB as $conducta) {
            $notasPorBimestre = [];
            $bimestresConNota = 0;

            foreach ($cursos as $curso) {
                foreach ($bimestres as $bimestre) {
                    $key = $conducta->id . '|' . $curso->id . '|' . $bimestre->id;

                    if (isset($notasMap[$key])) {
                        // Nota existente
                        $nota = $notasMap[$key];
                        $notasPorBimestre[$bimestre->bimestre] = $nota;
                        $bimestresConNota++;
                    } else {
                        // Nota faltante, asignar 1
                        if (!isset($notasPorBimestre[$bimestre->bimestre])) {
                            $notasPorBimestre[$bimestre->bimestre] = 1;
                        }
                    }
                }
            }

            // Calcular promedio si tiene notas en los 4 bimestres
            if (count($notasPorBimestre) == 4) {
                $promedio = round(array_sum($notasPorBimestre) / 4, 1);
                $todasLasConductas[] = [
                    'nombre' => $conducta->nombre,
                    'nota' => $promedio
                ];
            } else {
                // Mostrar cada bimestre individualmente
                foreach ($notasPorBimestre as $bimestreNum => $nota) {
                    $bimestreSigla = $this->getSiglaByBimestre($bimestreNum);
                    $todasLasConductas[] = [
                        'nombre' => $conducta->nombre . ' (' . $bimestreSigla . ')',
                        'nota' => $nota
                    ];
                }
            }
        }

        return $todasLasConductas;
    }
    else {
        // Modo bimestre: mostrar solo las notas del bimestre seleccionado
        $periodoBimestre = $bimestres->firstWhere('sigla', $sigla);
        $bimestreNumero = $periodoBimestre ? $periodoBimestre->bimestre : 1;

        $resultado = [];

        foreach ($todasLasConductasDB as $conducta) {
            $notasConducta = [];

            foreach ($cursos as $curso) {
                $key = $conducta->id . '|' . $curso->id . '|' . ($periodoBimestre ? $periodoBimestre->id : null);

                if (isset($notasMap[$key])) {
                    $notasConducta[] = $notasMap[$key];
                } else {
                    $notasConducta[] = 1; // Nota faltante = 1
                }
            }

            // Promediar las notas de todas las materias para esta conducta en este bimestre
            if (!empty($notasConducta)) {
                $promedio = round(array_sum($notasConducta) / count($notasConducta), 1);
                $resultado[] = [
                    'nombre' => $conducta->nombre,
                    'nota' => $promedio
                ];
            }
        }

        // Eliminar duplicados
        $resultadoUnico = [];
        foreach ($resultado as $item) {
            $key = $item['nombre'];
            if (!isset($resultadoUnico[$key])) {
                $resultadoUnico[$key] = $item;
            }
        }

        return array_values($resultadoUnico);
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
