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
        // 1. Obtener estudiante autenticado
        $estudiante = $this->getEstudiante();
        if (!$estudiante) {
            abort(404, 'Estudiante no encontrado.');
        }

        // 2. Obtener periodos del estudiante
        $periodos = $this->getPeriodosEstudiante($estudiante);
        if ($periodos->isEmpty()) {
            abort(404, 'No se encontraron periodos para este estudiante.');
        }

        // 3. Validar y obtener periodo actual
        $periodoActual = $this->getPeriodoActual($anio, $periodos);
        if (!$periodoActual) {
            $primerPeriodo = $periodos->first();
            return redirect()->route('libreta.index', [
                'anio' => $primerPeriodo['anio'],
                'sigla' => $sigla ?? 'anual'
            ]);
        }

        // 4. Obtener matrícula actual
        $matriculaActual = $this->getMatriculaActual($estudiante, $periodoActual);

        // 5. Obtener bimestres disponibles (incluyendo opción anual)
        $bimestres = $this->getBimestresDisponibles($periodoActual);

        // 6. Validar y obtener sigla seleccionada
        $sigla = $this->validarSigla($sigla, $bimestres);

        // 7. Obtener datos del colegio
        $colegio = Colegio::configuracion();

        // 8. OBTENER NOTAS DE MATERIAS
        $esAnual = ($sigla === 'anual');
        $notasMaterias = $this->getNotasMaterias($estudiante->id, $periodoActual, $sigla);
        $materiasAgrupadas = $this->agruparNotasPorMateria($notasMaterias, $esAnual);
        $materiasConPromedios = $this->calcularPromedios($materiasAgrupadas, $esAnual);
        $promedioTransversales = $this->calcularPromedioTransversales($materiasConPromedios);

        // 9. OBTENER NOTAS DE CONDUCTA
        $notasConducta = $this->getNotasConducta($estudiante->id, $periodoActual, $sigla, $matriculaActual->grado_id ?? null);
        $conductasPorMateria = $this->agruparNotasConductaPorMateria($notasConducta);

        // 10. Preparar datos para la vista
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

        // 11. Retornar la vista
        return view('libreta.index', array_merge($datosVista, [
            'materias' => $materiasConPromedios,
            'conductas_por_materia' => $conductasPorMateria,
            'promedioTransversales' => $promedioTransversales,
        ]));
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

        // Agregar opción anual al inicio
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
            // Datos del estudiante
            'estudiante' => $params['estudiante'],

            // Datos de matrícula
            'matricula_actual' => $params['matriculaActual'],

            // Datos del periodo actual
            'periodo_actual' => [
                'id' => $params['periodoActual']->id,
                'anio' => $params['periodoActual']->anio,
                'nombre' => $params['periodoActual']->nombre,
                'descripcion' => $params['periodoActual']->descripcion,
            ],

            // Lista de periodos para el selector
            'periodos' => $params['periodos'],

            // Lista de bimestres para el selector
            'bimestres_disponibles' => $params['bimestres'],

            // Bimestre seleccionado
            'bimestre_seleccionado' => $bimestreSeleccionado,
            'bimestre_nombre' => $bimestreSeleccionado['nombre'] ?? 'Promedio Anual',

            // Datos del colegio
            'colegio' => $params['colegio'],

            // Parámetros de URL
            'anio_param' => $params['anio'],
            'sigla_param' => $params['sigla'],
        ];
    }
    private function getNotasMaterias($estudianteId, $periodoActual, $sigla)
    {
        $query = Nota::with(['criterio.materiaCompetencia', 'criterio.materia'])
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoActual->id)
            ->where('publico', '!=', '0'); // Solo notas publicadas

        // Si no es anual, filtrar por periodo_bimestre_id
        if ($sigla !== 'anual') {
            // Buscar el periodo_bimestre_id por sigla
            $periodoBimestre = Periodobimestre::where('periodo_id', $periodoActual->id)
                ->where('sigla', $sigla)
                ->where('tipo_bimestre', 'A')
                ->first();

            if ($periodoBimestre) {
                $query->where('periodo_bimestre_id', $periodoBimestre->id);
            } else {
                // No se encontró el bimestre, retornar colección vacía
                return collect();
            }
        }

        $notas = $query->get();

        return $notas;
    }
    private function getNotasConducta($estudianteId, $periodoActual, $bimestreNumero, $gradoId)
    {
        // Obtener los cursos del estudiante
        $cursos = Cursogradosecnivanio::where('periodo_id', $periodoActual->id)
            ->where('grado_id', $gradoId)
            ->pluck('id');

        if ($cursos->isEmpty()) {
            return collect();
        }

        $query = Conductaperiodobimestrenota::with([
                'conductaPeriodoBimestre.conducta',
                'curso_grado_sec_niv_anio.materia'
            ])
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoActual->id)
            ->whereIn('curso_grado_sec_niv_anio_id', $cursos)
            ->where('publico', '!=', '0');

        // Filtrar por bimestre si no es anual
        if ($bimestreNumero) {
            $periodoBimestre = Periodobimestre::where('periodo_id', $periodoActual->id)
                ->where('bimestre', $bimestreNumero)
                ->where('tipo_bimestre', 'A')
                ->first();

            if ($periodoBimestre) {
                $query->where('periodo_bimestre_id', $periodoBimestre->id);
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

            // Detectar si es transversal
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

        // Convertir arrays asociativos a indexados
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
    private function agruparNotasConductaPorMateria($notasConducta)
    {
        $conductasPorMateria = [];

        foreach ($notasConducta as $nota) {
            $curso = $nota->curso_grado_sec_niv_anio;
            if (!$curso) continue;

            $materiaNombre = $curso->materia->nombre ?? 'Sin materia';
            $conducta = $nota->conductaPeriodoBimestre->conducta ?? null;

            if (!$conducta) continue;

            if (!isset($conductasPorMateria[$materiaNombre])) {
                $conductasPorMateria[$materiaNombre] = [];
            }

            $conductasPorMateria[$materiaNombre][] = [
                'conducta_id' => $conducta->id,
                'conducta_nombre' => $conducta->nombre,
                'nota' => $nota->nota,
                'publico' => $nota->publico
            ];
        }

        return $conductasPorMateria;
    }
    private function calcularPromedios($materias, $esAnual = false)
    {
        foreach ($materias as &$materia) {
            $sumaCompetencias = 0;
            $totalCompetencias = 0;

            // Procesar competencias regulares
            foreach ($materia['competencias'] as &$competencia) {
                if ($esAnual) {
                    // En modo anual, calcular promedio de TODOS los criterios de la competencia
                    $sumaCriterios = 0;
                    $totalCriterios = 0;
                    foreach ($competencia['criterios'] as $criterio) {
                        if ($criterio['nota']) {
                            $sumaCriterios += $criterio['nota'];
                            $totalCriterios++;
                        }
                    }
                    $competencia['promedio'] = $totalCriterios > 0
                        ? round($sumaCriterios / $totalCriterios, 1)
                        : null;
                } else {
                    // En modo bimestre, tomar la nota directa del criterio
                    $notasValidas = array_filter(array_column($competencia['criterios'], 'nota'));
                    $competencia['promedio'] = !empty($notasValidas)
                        ? round(array_sum($notasValidas) / count($notasValidas), 1)
                        : null;
                }

                if ($competencia['promedio']) {
                    $sumaCompetencias += $competencia['promedio'];
                    $totalCompetencias++;
                }
            }

            $materia['promedio'] = $totalCompetencias > 0
                ? round($sumaCompetencias / $totalCompetencias, 1)
                : null;

            // Procesar competencias transversales (siempre se muestran aparte)
            foreach ($materia['competencias_transversales'] as &$competencia) {
                $sumaCriterios = 0;
                $totalCriterios = 0;
                foreach ($competencia['criterios'] as $criterio) {
                    if ($criterio['nota']) {
                        $sumaCriterios += $criterio['nota'];
                        $totalCriterios++;
                    }
                }
                $competencia['promedio'] = $totalCriterios > 0
                    ? round($sumaCriterios / $totalCriterios, 1)
                    : null;
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

        return $totalTransversales > 0
            ? round($sumaTransversales / $totalTransversales, 1)
            : null;
    }
}
