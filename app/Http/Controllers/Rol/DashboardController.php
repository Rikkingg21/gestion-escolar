<?php

namespace App\Http\Controllers\Rol;

use App\Http\Controllers\Controller;
use App\Models\Apoderado;
use App\Models\Asistencia\Asistencia;
use App\Models\Asistencia\Tipoasistencia;
use App\Models\Auxiliar;
use App\Models\Conducta;
use App\Models\Conductaperiodobimestrenota;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use App\Models\Materia\Recuperacioncompetencia;
use App\Models\Matricula;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\Periodobimestre;
use App\Models\User;
use App\Services\EvaluacionEstudianteService;
use App\Services\ProcesarnotasCompetenciaService;
use App\Services\ProcesarnotasCriterioService;
use App\Services\ProcesarnotasMateriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $criterioService;

    protected $competenciaService;

    protected $materiaService;

    protected $evaluacionService;

    public function __construct(
        ProcesarnotasCriterioService $criterioService,
        ProcesarnotasCompetenciaService $competenciaService,
        ProcesarnotasMateriaService $materiaService,
        EvaluacionEstudianteService $evaluacionService
    ) {
        $this->criterioService = $criterioService;
        $this->competenciaService = $competenciaService;
        $this->materiaService = $materiaService;
        $this->evaluacionService = $evaluacionService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->admin();
        } elseif ($user->hasRole('director')) {
            return $this->director($request);
        } elseif ($user->hasRole('docente')) {
            return $this->docente($request);
        } elseif ($user->hasRole('auxiliar')) {
            return $this->auxiliar($request);
        } elseif ($user->hasRole('apoderado')) {
            return $this->apoderado($request);
        } elseif ($user->hasRole('estudiante')) {
            return $this->estudiante($request);
        } else {
            return $this->NuevoRol();
        }
    }

    protected function admin()
    {
        if (! Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso denegado');
        }

        $usuarios = User::with('roles')->get();
        $rolesCount = User::with('roles')->get()->flatMap->roles->groupBy('name')->map->count();

        $docentes = Docente::all();
        $docentesCount = $docentes->count();

        $estudiantes = Estudiante::all();
        $estudiantesCount = $estudiantes->count();

        $apoderados = Apoderado::all();
        $apoderadosCount = $apoderados->count();

        $auxiliares = Auxiliar::all();
        $auxiliaresCount = $auxiliares->count();

        return view('rol.admin.dashboard', compact('usuarios', 'rolesCount', 'docentesCount', 'estudiantesCount', 'apoderadosCount', 'auxiliaresCount'));
    }

    protected function director(Request $request)
    {
        if (! Auth::user()->hasRole('director')) {
            abort(403, 'Acceso denegado');
        }
        $user = Auth::user();

        // Obtener periodo seleccionado o el activo por defecto
        $periodoSeleccionado = null;
        $bimestreSeleccionado = null;

        if ($request->has('periodo_id')) {
            $periodoSeleccionado = Periodo::find($request->periodo_id);
        }

        if (! $periodoSeleccionado) {
            $periodoSeleccionado = Periodo::where('estado', '1')->first();
        }

        // Obtener bimestre seleccionado
        if ($request->has('periodobimestre_id') && $request->periodobimestre_id) {
            $bimestreSeleccionado = Periodobimestre::find($request->periodobimestre_id);
        }

        // Obtener todos los periodos para el selector
        $periodos = Periodo::orderBy('anio', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener bimestres del periodo seleccionado para el filtro
        $bimestresDisponibles = collect();
        if ($periodoSeleccionado) {
            $bimestresDisponibles = Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
                ->where('tipo_bimestre', 'A')
                ->orderBy('bimestre')
                ->get();
        }

        if (! $periodoSeleccionado && $periodos->isNotEmpty()) {
            $periodoSeleccionado = $periodos->first();
        }

        // Inicializar variables
        $grados = collect();
        $estadisticas = [
            'total_grados' => 0,
            'total_estudiantes' => 0,
            'promedio_general' => 0,
            'promedio_academico' => 0,
            'promedio_conducta' => 0,
            'promedio_asistencia' => 0,
            'excelentes' => 0,
            'buenos' => 0,
            'regulares' => 0,
            'bajos' => 0,
            'total_materias' => 0,
            'total_asistencias' => 0,
            'porcentaje_puntualidad' => 0,
            'porcentaje_falta' => 0,
            'porcentaje_tardanza' => 0,
        ];

        if ($periodoSeleccionado) {
            // Obtener SOLO los grados que tienen estudiantes matriculados en el periodo
            $grados = Grado::where('estado', '1')
                ->whereHas('matriculas', function ($query) use ($periodoSeleccionado) {
                    $query->where('periodo_id', $periodoSeleccionado->id)
                        ->where('estado', '1');
                })
                ->with(['matriculas' => function ($query) use ($periodoSeleccionado) {
                    $query->where('periodo_id', $periodoSeleccionado->id)
                        ->where('estado', '1')
                        ->with(['estudiante.user']);
                }])
                ->orderBy('nivel')
                ->orderBy('grado')
                ->orderBy('seccion')
                ->get();

            // Obtener IDs de estudiantes por grado
            $estudiantesPorGrado = [];
            $todosEstudianteIds = [];

            foreach ($grados as $grado) {
                $estudianteIds = $grado->matriculas->pluck('estudiante_id')->toArray();
                $estudiantesPorGrado[$grado->id] = $estudianteIds;
                $todosEstudianteIds = array_merge($todosEstudianteIds, $estudianteIds);
            }

            $todosEstudianteIds = array_unique($todosEstudianteIds);

            $promediosNotas = [];
            $promediosConducta = [];
            $promediosAsistencia = [];

            if (! empty($todosEstudianteIds)) {
                // Obtener IDs de competencias transversales para excluir
                $competenciasTransversalesIds = Materiacompetencia::where('nombre', 'LIKE', '%TRANSVERSAL%')
                    ->pluck('id')
                    ->toArray();

                // Obtener IDs de criterios que pertenecen a competencias transversales
                $criteriosTransversalesIds = Materiacriterio::whereIn('materia_competencia_id', $competenciasTransversalesIds)
                    ->pluck('id')
                    ->toArray();

                // Obtener promedios de notas académicas EXCLUYENDO competencias transversales
                $notasQuery = Nota::selectRaw('estudiante_id, AVG(nota) as promedio')
                    ->whereIn('estudiante_id', $todosEstudianteIds)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->whereNotIn('materia_criterio_id', $criteriosTransversalesIds)
                    ->where('publico', '!=', '0');

                if ($bimestreSeleccionado) {
                    $notasQuery->where('periodo_bimestre_id', $bimestreSeleccionado->id);
                }

                $notasPromedio = $notasQuery->groupBy('estudiante_id')
                    ->get()
                    ->keyBy('estudiante_id');

                // Obtener todos los cursos_grados (materias por grado) del periodo
                $cursosIds = Cursogradosecnivanio::where('periodo_id', $periodoSeleccionado->id)
                    ->pluck('id')
                    ->toArray();

                // Obtener promedios de notas de conducta usando Conductaperiodobimestrenota
                if (! empty($cursosIds)) {
                    $conductaQuery = Conductaperiodobimestrenota::selectRaw('estudiante_id, AVG(nota) as promedio')
                        ->whereIn('estudiante_id', $todosEstudianteIds)
                        ->where('periodo_id', $periodoSeleccionado->id)
                        ->whereIn('curso_grado_sec_niv_anio_id', $cursosIds)
                        ->where('publico', '!=', '0')
                        ->whereHas('conductaPeriodoBimestre', function ($q) {
                            $q->whereNull('deleted_at');
                        });

                    if ($bimestreSeleccionado) {
                        $conductaQuery->where('periodo_bimestre_id', $bimestreSeleccionado->id);
                    }

                    $conductaPromedio = $conductaQuery->groupBy('estudiante_id')
                        ->get()
                        ->keyBy('estudiante_id');
                }

                // Obtener estadísticas de asistencia
                $asistenciaQuery = Asistencia::whereIn('estudiante_id', $todosEstudianteIds)
                    ->where('periodo_id', $periodoSeleccionado->id);

                if ($bimestreSeleccionado) {
                    $asistenciaQuery->where('periodobimestre_id', $bimestreSeleccionado->id);
                }

                $asistencias = $asistenciaQuery->get()->groupBy('estudiante_id');

                // Obtener los IDs de los tipos de asistencia dinámicamente
                $tiposAsistencia = Tipoasistencia::all();
                $tipoPuntualidadId = $tiposAsistencia->where('nombre', 'PUNTUALIDAD')->first()?->id ?? 0;
                $tipoFaltaId = $tiposAsistencia->where('nombre', 'FALTA')->first()?->id ?? 0;
                $tipoTardanzaId = $tiposAsistencia->where('nombre', 'TARDANZA')->first()?->id ?? 0;

                foreach ($todosEstudianteIds as $estudianteId) {
                    $asistenciasEstudiante = $asistencias->get($estudianteId, collect());
                    $totalAsistencias = $asistenciasEstudiante->count();

                    if ($totalAsistencias > 0) {
                        $puntualidad = $asistenciasEstudiante->where('tipo_asistencia_id', $tipoPuntualidadId)->count();
                        $falta = $asistenciasEstudiante->where('tipo_asistencia_id', $tipoFaltaId)->count();
                        $tardanza = $asistenciasEstudiante->where('tipo_asistencia_id', $tipoTardanzaId)->count();

                        $promediosAsistencia[$estudianteId] = [
                            'total' => $totalAsistencias,
                            'puntualidad' => round(($puntualidad / $totalAsistencias) * 100, 2),
                            'falta' => round(($falta / $totalAsistencias) * 100, 2),
                            'tardanza' => round(($tardanza / $totalAsistencias) * 100, 2),
                            'puntualidad_raw' => $puntualidad,
                            'falta_raw' => $falta,
                            'tardanza_raw' => $tardanza,
                        ];
                    } else {
                        $promediosAsistencia[$estudianteId] = [
                            'total' => 0,
                            'puntualidad' => 0,
                            'falta' => 0,
                            'tardanza' => 0,
                            'puntualidad_raw' => 0,
                            'falta_raw' => 0,
                            'tardanza_raw' => 0,
                        ];
                    }
                }

                foreach ($notasPromedio as $estudianteId => $nota) {
                    $promediosNotas[$estudianteId] = round($nota->promedio, 2);
                }

                if (isset($conductaPromedio)) {
                    foreach ($conductaPromedio as $estudianteId => $nota) {
                        $promediosConducta[$estudianteId] = round($nota->promedio, 2);
                    }
                }
            }

            // Obtener información de materias por grado para el periodo
            $materiasPorGrado = Cursogradosecnivanio::where('periodo_id', $periodoSeleccionado->id)
                ->with(['materia', 'docente.user'])
                ->get()
                ->groupBy('grado_id');

            // Calcular promedios por grado
            $totalPuntualidadGlobal = 0;
            $totalFaltaGlobal = 0;
            $totalTardanzaGlobal = 0;
            $totalRegistrosGlobal = 0;

            foreach ($grados as $grado) {
                $estudianteIds = $estudiantesPorGrado[$grado->id] ?? [];
                $grado->estudiantes_matriculados = count($estudianteIds);

                $materiasGrado = $materiasPorGrado[$grado->id] ?? collect();
                $grado->total_materias = $materiasGrado->count();
                $grado->materias_lista = $materiasGrado->pluck('materia.nombre')->unique()->values();
                $grado->docentes_lista = $materiasGrado->pluck('docente.user.nombre_completo')->filter()->unique()->values();

                if (! empty($estudianteIds)) {
                    $sumaNotas = 0;
                    $sumaConducta = 0;
                    $contador = 0;
                    $sumaAsistenciaPuntualidad = 0;
                    $sumaAsistenciaFalta = 0;
                    $sumaAsistenciaTardanza = 0;
                    $contadorAsistencia = 0;

                    // Totales raw para este grado
                    $totalPuntualidadRaw = 0;
                    $totalFaltaRaw = 0;
                    $totalTardanzaRaw = 0;

                    foreach ($estudianteIds as $estudianteId) {
                        if (isset($promediosNotas[$estudianteId])) {
                            $sumaNotas += $promediosNotas[$estudianteId];
                            $contador++;
                        }
                        if (isset($promediosConducta[$estudianteId])) {
                            $sumaConducta += $promediosConducta[$estudianteId];
                        }
                        if (isset($promediosAsistencia[$estudianteId])) {
                            $sumaAsistenciaPuntualidad += $promediosAsistencia[$estudianteId]['puntualidad'];
                            $sumaAsistenciaFalta += $promediosAsistencia[$estudianteId]['falta'];
                            $sumaAsistenciaTardanza += $promediosAsistencia[$estudianteId]['tardanza'];
                            $contadorAsistencia++;

                            // Sumar valores raw
                            $totalPuntualidadRaw += $promediosAsistencia[$estudianteId]['puntualidad_raw'];
                            $totalFaltaRaw += $promediosAsistencia[$estudianteId]['falta_raw'];
                            $totalTardanzaRaw += $promediosAsistencia[$estudianteId]['tardanza_raw'];
                        }
                    }

                    $grado->promedio_notas = $contador > 0 ? round($sumaNotas / $contador, 2) : 0;
                    $grado->promedio_conducta = $contador > 0 ? round($sumaConducta / $contador, 2) : 0;
                    $grado->promedio_general = $contador > 0
                        ? round(($grado->promedio_notas + $grado->promedio_conducta) / 2, 2)
                        : 0;

                    // Guardar valores raw para cálculos posteriores
                    $grado->total_puntualidad_raw = $totalPuntualidadRaw;
                    $grado->total_falta_raw = $totalFaltaRaw;
                    $grado->total_tardanza_raw = $totalTardanzaRaw;
                    $grado->total_asistencias_raw = $totalPuntualidadRaw + $totalFaltaRaw + $totalTardanzaRaw;

                    $grado->promedio_asistencia_puntualidad = $contadorAsistencia > 0 ? round($sumaAsistenciaPuntualidad / $contadorAsistencia, 2) : 0;
                    $grado->promedio_asistencia_falta = $contadorAsistencia > 0 ? round($sumaAsistenciaFalta / $contadorAsistencia, 2) : 0;
                    $grado->promedio_asistencia_tardanza = $contadorAsistencia > 0 ? round($sumaAsistenciaTardanza / $contadorAsistencia, 2) : 0;

                    // Acumular para estadísticas globales
                    $totalPuntualidadGlobal += $totalPuntualidadRaw;
                    $totalFaltaGlobal += $totalFaltaRaw;
                    $totalTardanzaGlobal += $totalTardanzaRaw;
                    $totalRegistrosGlobal += $grado->total_asistencias_raw;
                } else {
                    $grado->promedio_notas = 0;
                    $grado->promedio_conducta = 0;
                    $grado->promedio_general = 0;
                    $grado->promedio_asistencia_puntualidad = 0;
                    $grado->promedio_asistencia_falta = 0;
                    $grado->promedio_asistencia_tardanza = 0;
                    $grado->total_asistencias_raw = 0;
                }
            }

            // Calcular estadísticas generales
            $estadisticas = [
                'total_grados' => $grados->count(),
                'total_estudiantes' => $grados->sum('estudiantes_matriculados'),
                'total_materias' => $grados->sum('total_materias'),
                'promedio_academico' => $grados->avg('promedio_notas') ? round($grados->avg('promedio_notas'), 2) : 0,
                'promedio_conducta' => $grados->avg('promedio_conducta') ? round($grados->avg('promedio_conducta'), 2) : 0,
                'promedio_general' => $grados->avg('promedio_general') ? round($grados->avg('promedio_general'), 2) : 0,

                // Estadísticas de asistencia
                'total_registros_asistencia' => $totalRegistrosGlobal,
                'total_puntualidad' => $totalPuntualidadGlobal,
                'total_falta' => $totalFaltaGlobal,
                'total_tardanza' => $totalTardanzaGlobal,
                'porcentaje_puntualidad' => $totalRegistrosGlobal > 0 ? round(($totalPuntualidadGlobal / $totalRegistrosGlobal) * 100, 2) : 0,
                'porcentaje_falta' => $totalRegistrosGlobal > 0 ? round(($totalFaltaGlobal / $totalRegistrosGlobal) * 100, 2) : 0,
                'porcentaje_tardanza' => $totalRegistrosGlobal > 0 ? round(($totalTardanzaGlobal / $totalRegistrosGlobal) * 100, 2) : 0,
                'promedio_asistencia' => $totalRegistrosGlobal > 0 ? round(($totalPuntualidadGlobal / $totalRegistrosGlobal) * 100, 2) : 0,
            ];

            $estadisticas['excelentes'] = $grados->filter(fn ($g) => $g->promedio_general >= 3.5)->count();
            $estadisticas['buenos'] = $grados->filter(fn ($g) => $g->promedio_general >= 2.5 && $g->promedio_general < 3.5)->count();
            $estadisticas['regulares'] = $grados->filter(fn ($g) => $g->promedio_general >= 2.0 && $g->promedio_general < 2.5)->count();
            $estadisticas['bajos'] = $grados->filter(fn ($g) => $g->promedio_general < 2.0)->count();

            $estadisticas['por_nivel'] = $grados->groupBy('nivel')->map(function ($gradosNivel) {
                return [
                    'total' => $gradosNivel->count(),
                    'estudiantes' => $gradosNivel->sum('estudiantes_matriculados'),
                    'materias' => $gradosNivel->sum('total_materias'),
                    'promedio' => round($gradosNivel->avg('promedio_general'), 2),
                    'excelentes' => $gradosNivel->filter(fn ($g) => $g->promedio_general >= 3.5)->count(),
                    'buenos' => $gradosNivel->filter(fn ($g) => $g->promedio_general >= 2.5 && $g->promedio_general < 3.5)->count(),
                    'regulares' => $gradosNivel->filter(fn ($g) => $g->promedio_general >= 2.0 && $g->promedio_general < 2.5)->count(),
                    'bajos' => $gradosNivel->filter(fn ($g) => $g->promedio_general < 2.0)->count(),
                ];
            });

            $grados = $grados->sortByDesc('promedio_general')->values();
        }

        return view('rol.director.dashboard', [
            'periodoSeleccionado' => $periodoSeleccionado,
            'periodos' => $periodos,
            'bimestresDisponibles' => $bimestresDisponibles,
            'bimestreSeleccionado' => $bimestreSeleccionado,
            'grados' => $grados,
            'estadisticas' => $estadisticas,
            'user' => $user,
        ]);
    }

    protected function docente(Request $request)
    {
        if (! Auth::user()->hasRole('docente')) {
            abort(403, 'Acceso denegado');
        }

        $docente = Auth::user()->docente;

        if (! $docente) {
            abort(404, 'Perfil de docente no encontrado');
        }

        // Obtener solo los periodos donde el docente tiene asignaciones
        $periodos = Periodo::whereHas('cursosGradoSecNivAnio', function ($query) use ($docente) {
            $query->where('docente_designado_id', $docente->id);
        })
            ->where('estado', 1)
            ->orderBy('anio', 'desc')
            ->get();

        if ($periodos->isEmpty()) {
            return view('rol.docente.dashboard', compact('docente', 'periodos'))
                ->with('error', 'No tiene asignaciones en ningún período activo.');
        }

        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        $asignacionesData = [];

        if ($periodoSeleccionado) {
            $asignaciones = Cursogradosecnivanio::where('docente_designado_id', $docente->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->with(['grado', 'materia'])
                ->get();

            foreach ($asignaciones as $asignacion) {
                $asignacionesData[$asignacion->id] = $this->procesarAsignacionDocente(
                    $asignacion,
                    $periodoSeleccionado
                );
            }
        }

        return view('rol.docente.dashboard', compact(
            'docente',
            'periodos',
            'periodoSeleccionado',
            'asignacionesData'
        ));
    }

    private function procesarAsignacionDocente($asignacion, $periodo)
    {
        $grado = $asignacion->grado;
        $materia = $asignacion->materia;

        // Obtener estudiantes matriculados
        $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodo) {
            $query->where('grado_id', $grado->id)
                ->where('periodo_id', $periodo->id)
                ->where('estado', 1);
        })->with('user')->get();

        // Obtener bimestres regulares
        $bimestres = Periodobimestre::where('periodo_id', $periodo->id)
            ->where('tipo_bimestre', 'A')
            ->orderBy('bimestre')
            ->get();

        // Obtener competencias de la materia (excluyendo transversales)
        $competencias = Materiacompetencia::where('materia_id', $materia->id)
            ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%')
            ->get();

        // Obtener criterios por bimestre
        $criteriosPorBimestre = [];
        foreach ($bimestres as $bim) {
            $criteriosPorBimestre[$bim->sigla] = Materiacriterio::whereHas('materiaCompetencia', function ($q) use ($materia) {
                $q->where('materia_id', $materia->id)
                    ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%');
            })->where('grado_id', $grado->id)
                ->where('periodo_bimestre_id', $bim->id)
                ->count();
        }

        // Obtener conductas activas
        $conductas = Conducta::whereHas('periodosBimestres', function ($q) use ($periodo) {
            $q->where('periodo_id', $periodo->id)
                ->whereNull('conducta_periodo_bimestres.deleted_at');
        })->distinct()->get();

        $totalConductas = $conductas->count();

        // Procesar datos de cada estudiante
        $estudiantesData = [];
        $sumaNotasGeneral = 0;
        $totalNotasGeneral = 0;
        $sumaConductaGeneral = 0;
        $totalConductaGeneral = 0;
        $estudiantesConNotas = 0;
        $estudiantesConConducta = 0;

        // Estadísticas por bimestre
        $estadisticasNotas = [];
        $estadisticasConducta = [];

        foreach ($bimestres as $bim) {
            $estadisticasNotas[$bim->sigla] = [
                'total_estudiantes_con_notas' => 0,
                'suma_notas' => 0,
                'total_notas' => 0,
                'criterios_en_bimestre' => $criteriosPorBimestre[$bim->sigla] ?? 0,
                'total_notas_registradas' => 0,
                'total_notas_posibles' => 0,
            ];
            $estadisticasConducta[$bim->sigla] = [
                'total_estudiantes_con_conducta' => 0,
                'suma_conducta' => 0,
                'total_conducta' => 0,
                'total_conductas_registradas' => 0,
                'total_conductas_posibles' => 0,
                'total_conductas_en_bimestre' => $totalConductas,
                'min' => null,
                'max' => null,
            ];
        }

        foreach ($estudiantes as $index => $estudiante) {
            // Calcular notas por bimestre
            $notasPorBimestre = [];
            $criteriosRegistrados = [];
            $criteriosPosibles = [];
            $bimestresConNotas = 0;
            $sumaNotasEstudiante = 0;
            $totalCriteriosRegistrados = 0;
            $totalCriteriosPosibles = 0;

            foreach ($bimestres as $bim) {
                $criteriosIds = Materiacriterio::whereHas('materiaCompetencia', function ($q) use ($materia) {
                    $q->where('materia_id', $materia->id)
                        ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%');
                })->where('grado_id', $grado->id)
                    ->where('periodo_bimestre_id', $bim->id)
                    ->pluck('id')
                    ->toArray();

                $totalPosibles = count($criteriosIds);
                $criteriosPosibles[$bim->sigla] = $totalPosibles;
                $totalCriteriosPosibles += $totalPosibles;

                $notas = Nota::whereIn('materia_criterio_id', $criteriosIds)
                    ->where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('publico', '!=', '0')
                    ->get();

                $registrados = $notas->count();
                $criteriosRegistrados[$bim->sigla] = $registrados;
                $totalCriteriosRegistrados += $registrados;

                $estadisticasNotas[$bim->sigla]['total_notas_registradas'] += $registrados;
                $estadisticasNotas[$bim->sigla]['total_notas_posibles'] += $totalPosibles;

                if ($notas->isNotEmpty()) {
                    $promedio = round($notas->avg('nota'), 1);
                    $notasPorBimestre[$bim->sigla] = $promedio;
                    $bimestresConNotas++;
                    $sumaNotasEstudiante += $promedio;

                    $estadisticasNotas[$bim->sigla]['total_estudiantes_con_notas']++;
                    $estadisticasNotas[$bim->sigla]['suma_notas'] += $promedio;
                    $estadisticasNotas[$bim->sigla]['total_notas']++;
                } else {
                    $notasPorBimestre[$bim->sigla] = null;
                }
            }

            $promedioNotas = $bimestresConNotas > 0 ? round($sumaNotasEstudiante / $bimestresConNotas, 1) : null;
            if ($promedioNotas !== null) {
                $sumaNotasGeneral += $promedioNotas;
                $totalNotasGeneral++;
                $estudiantesConNotas++;
            }

            // Calcular porcentaje de completitud de notas
            $porcentajeNotasCompletas = $totalCriteriosPosibles > 0
                ? round(($totalCriteriosRegistrados / $totalCriteriosPosibles) * 100, 1)
                : 0;
            $notasCompletasTexto = "{$totalCriteriosRegistrados}/{$totalCriteriosPosibles}";

            // Calcular conducta por bimestre
            $conductaPorBimestre = [];
            $conductasRegistradas = [];
            $bimestresConConducta = 0;
            $sumaConductaEstudiante = 0;
            $totalConductasRegistradas = 0;
            $totalConductasPosibles = $totalConductas * $bimestres->count();

            foreach ($bimestres as $bim) {
                $periodoBimestre = $bim;

                $notasConducta = Conductaperiodobimestrenota::where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('periodo_bimestre_id', $periodoBimestre->id)
                    ->where('publico', '!=', '0')
                    ->whereHas('curso_grado_sec_niv_anio', function ($q) use ($asignacion) {
                        $q->where('id', $asignacion->id);
                    })
                    ->get();

                $registradas = $notasConducta->count();
                $conductasRegistradas[$bim->sigla] = $registradas;
                $totalConductasRegistradas += $registradas;
                $posibles = $totalConductas;

                $estadisticasConducta[$bim->sigla]['total_conductas_registradas'] += $registradas;
                $estadisticasConducta[$bim->sigla]['total_conductas_posibles'] += $posibles;

                if ($notasConducta->isNotEmpty()) {
                    $promedio = round($notasConducta->avg('nota'), 1);
                    $conductaPorBimestre[$bim->sigla] = $promedio;
                    $bimestresConConducta++;
                    $sumaConductaEstudiante += $promedio;

                    $estadisticasConducta[$bim->sigla]['total_estudiantes_con_conducta']++;
                    $estadisticasConducta[$bim->sigla]['suma_conducta'] += $promedio;
                    $estadisticasConducta[$bim->sigla]['total_conducta']++;

                    if ($estadisticasConducta[$bim->sigla]['min'] === null || $promedio < $estadisticasConducta[$bim->sigla]['min']) {
                        $estadisticasConducta[$bim->sigla]['min'] = $promedio;
                    }
                    if ($estadisticasConducta[$bim->sigla]['max'] === null || $promedio > $estadisticasConducta[$bim->sigla]['max']) {
                        $estadisticasConducta[$bim->sigla]['max'] = $promedio;
                    }
                } else {
                    $conductaPorBimestre[$bim->sigla] = null;
                }
            }

            $promedioConducta = $bimestresConConducta > 0 ? round($sumaConductaEstudiante / $bimestresConConducta, 1) : null;
            if ($promedioConducta !== null) {
                $sumaConductaGeneral += $promedioConducta;
                $totalConductaGeneral++;
                $estudiantesConConducta++;
            }

            // Calcular porcentaje de completitud de conducta
            $porcentajeConductaCompleta = $totalConductasPosibles > 0
                ? round(($totalConductasRegistradas / $totalConductasPosibles) * 100, 1)
                : 0;
            $conductaCompletaTexto = "{$totalConductasRegistradas}/{$totalConductasPosibles}";

            // Determinar estado del estudiante
            $estadoTexto = 'Sin datos';
            $estadoClase = 'danger';
            if ($promedioNotas !== null && $promedioConducta !== null) {
                if ($promedioNotas > 2 && $promedioConducta > 2) {
                    $estadoTexto = 'Aprobado';
                    $estadoClase = 'success';
                } else {
                    $estadoTexto = 'Desaprobado';
                    $estadoClase = 'danger';
                }
            } elseif ($promedioNotas !== null || $promedioConducta !== null) {
                $estadoTexto = 'S/N';
                $estadoClase = 'warning';
            }

            $estudiantesData[] = [
                'id' => $estudiante->id,
                'dni' => $estudiante->user->dni,
                'nombre_completo' => trim(sprintf(
                    '%s %s, %s',
                    $estudiante->user->apellido_paterno ?? '',
                    $estudiante->user->apellido_materno ?? '',
                    $estudiante->user->nombre ?? ''
                )),
                'notas' => $notasPorBimestre,
                'promedio_notas' => $promedioNotas,
                'conducta' => $conductaPorBimestre,
                'promedio_conducta' => $promedioConducta,
                'bimestres_notas' => $bimestresConNotas,
                'bimestres_conducta' => $bimestresConConducta,
                'criterios_registrados' => $criteriosRegistrados,
                'criterios_posibles' => $criteriosPosibles,
                'total_criterios_registrados' => $totalCriteriosRegistrados,
                'total_criterios_posibles' => $totalCriteriosPosibles,
                'porcentaje_notas_completas' => $porcentajeNotasCompletas,
                'notas_completas_texto' => $notasCompletasTexto,
                'conductas_registradas' => $conductasRegistradas,
                'total_conductas_registradas' => $totalConductasRegistradas,
                'total_conductas_posibles' => $totalConductasPosibles,
                'porcentaje_conducta_completa' => $porcentajeConductaCompleta,
                'conducta_completa_texto' => $conductaCompletaTexto,
                'estado_texto' => $estadoTexto,
                'estado_clase' => $estadoClase,
                'tiene_notas' => $promedioNotas !== null,
                'tiene_conducta' => $promedioConducta !== null,
            ];
        }

        // Resto del código igual...
        foreach ($bimestres as $bim) {
            $sigla = $bim->sigla;

            if ($estadisticasNotas[$sigla]['total_notas'] > 0) {
                $estadisticasNotas[$sigla]['promedio'] = round(
                    $estadisticasNotas[$sigla]['suma_notas'] / $estadisticasNotas[$sigla]['total_notas'],
                    1
                );
            } else {
                $estadisticasNotas[$sigla]['promedio'] = null;
            }

            $totalPosibles = $estadisticasNotas[$sigla]['total_notas_posibles'];
            $totalRegistradas = $estadisticasNotas[$sigla]['total_notas_registradas'];
            $estadisticasNotas[$sigla]['porcentaje_avance'] = $totalPosibles > 0
                ? round(($totalRegistradas / $totalPosibles) * 100, 1)
                : 0;

            if ($estadisticasConducta[$sigla]['total_conducta'] > 0) {
                $estadisticasConducta[$sigla]['promedio'] = round(
                    $estadisticasConducta[$sigla]['suma_conducta'] / $estadisticasConducta[$sigla]['total_conducta'],
                    1
                );
            } else {
                $estadisticasConducta[$sigla]['promedio'] = null;
            }

            $totalPosiblesCond = $estadisticasConducta[$sigla]['total_conductas_posibles'];
            $totalRegistradasCond = $estadisticasConducta[$sigla]['total_conductas_registradas'];
            $estadisticasConducta[$sigla]['porcentaje_avance'] = $totalPosiblesCond > 0
                ? round(($totalRegistradasCond / $totalPosiblesCond) * 100, 1)
                : 0;

            $estadisticasConducta[$sigla]['porcentaje_estudiantes'] = $estudiantes->count() > 0
                ? round(($estadisticasConducta[$sigla]['total_estudiantes_con_conducta'] / $estudiantes->count()) * 100, 1)
                : 0;
        }

        // Preparar datos para gráficos
        $datosGraficoNotas = [];
        $datosGraficoConducta = [];

        foreach ($estudiantesData as $estudiante) {
            $datosGraficoNotas[] = [
                'label' => $estudiante['nombre_completo'],
                'data' => array_values($estudiante['notas']),
                'borderColor' => '',
                'backgroundColor' => '',
            ];
            $datosGraficoConducta[] = [
                'label' => $estudiante['nombre_completo'],
                'data' => array_values($estudiante['conducta']),
                'borderColor' => '',
                'backgroundColor' => '',
            ];
        }

        return [
            'asignacion_id' => $asignacion->id,
            'materia_nombre' => $materia->nombre,
            'grado_nombre' => $grado->grado.'° '.$grado->seccion,
            'periodo_anio' => $periodo->anio,
            'total_estudiantes' => $estudiantes->count(),
            'estudiantes' => $estudiantesData,
            'estudiantes_con_notas' => $estudiantesConNotas,
            'estudiantes_con_conducta' => $estudiantesConConducta,
            'promedio_general_notas' => $totalNotasGeneral > 0 ? round($sumaNotasGeneral / $totalNotasGeneral, 1) : null,
            'promedio_general_conducta' => $totalConductaGeneral > 0 ? round($sumaConductaGeneral / $totalConductaGeneral, 1) : null,
            'criterios_por_bimestre' => $criteriosPorBimestre,
            'estadisticas_bimestres' => [
                'notas' => $estadisticasNotas,
                'conducta' => $estadisticasConducta,
            ],
            'datos_grafico_notas' => [
                'labels' => $bimestres->pluck('sigla')->toArray(),
                'datasets' => $datosGraficoNotas,
            ],
            'datos_grafico_conducta' => [
                'labels' => $bimestres->pluck('sigla')->toArray(),
                'datasets' => $datosGraficoConducta,
            ],
            'resumen_notas' => [
                'con_datos' => count(array_filter($estadisticasNotas, fn ($s) => ($s['total_estudiantes_con_notas'] ?? 0) > 0)),
            ],
            'resumen_conducta' => [
                'con_datos' => count(array_filter($estadisticasConducta, fn ($s) => ($s['total_estudiantes_con_conducta'] ?? 0) > 0)),
            ],
            'total_criterios' => array_sum($criteriosPorBimestre),
        ];
    }

    protected function auxiliar(Request $request)
    {
        if (! Auth::user()->hasRole('auxiliar')) {
            abort(403, 'Acceso denegado');
        }

        $periodos = Periodo::where('estado', 1)->orderBy('anio', 'desc')->get();

        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        if (! $periodoSeleccionado) {
            return back()->with('error', 'No hay períodos activos disponibles.');
        }

        $periodobimestreId = $request->input('periodobimestre_id');
        $mesFiltro = $request->input('mes');

        $grados = Grado::whereHas('matriculas', function ($query) use ($periodoSeleccionado) {
            $query->where('periodo_id', $periodoSeleccionado->id);
        })
            ->withCount(['matriculas' => function ($query) use ($periodoSeleccionado) {
                $query->where('periodo_id', $periodoSeleccionado->id);
            }])
            ->orderBy('grado')
            ->orderBy('seccion')
            ->get();

        $tiposAsistencia = Tipoasistencia::all();

        $datosAsistencias = [];
        $estadisticasGenerales = [
            'totalEstudiantes' => 0,
            'totalAsistencias' => 0,
            'porcentajeAsistencia' => 0,
        ];

        foreach ($grados as $grado) {
            $estudianteIds = Matricula::where('periodo_id', $periodoSeleccionado->id)
                ->where('grado_id', $grado->id)
                ->pluck('estudiante_id')
                ->toArray();

            if (empty($estudianteIds)) {
                continue;
            }

            $estudiantes = Estudiante::with([
                'user',
                'asistencias' => function ($query) use ($periodoSeleccionado, $periodobimestreId, $mesFiltro) {
                    $query->where('periodo_id', $periodoSeleccionado->id)
                        ->with('tipoasistencia');

                    if ($periodobimestreId) {
                        $query->where('periodobimestre_id', $periodobimestreId);
                    }

                    if ($mesFiltro && is_numeric($mesFiltro)) {
                        $query->whereMonth('fecha', $mesFiltro);
                    }
                },
            ])
                ->whereIn('id', $estudianteIds)
                ->where('estado', 1)
                ->get()
                ->sortBy(function ($estudiante) {
                    return $estudiante->user->apellido_paterno.' '.$estudiante->user->apellido_materno;
                });

            if ($estudiantes->isEmpty()) {
                continue;
            }

            $datosEstudiantes = [];
            $estadisticasGrado = [
                'totalEstudiantes' => $estudiantes->count(),
                'totalAsistencias' => 0,
                'porcentajesTipo' => [],
            ];

            foreach ($tiposAsistencia as $tipo) {
                $estadisticasGrado['porcentajesTipo'][$tipo->nombre] = 0;
            }

            foreach ($estudiantes as $estudiante) {
                $asistenciasPeriodo = $estudiante->asistencias;
                $totalAsistencias = $asistenciasPeriodo->count();
                $estadisticasGrado['totalAsistencias'] += $totalAsistencias;

                $porcentajesPorTipo = [];
                $conteoTipos = [];

                foreach ($tiposAsistencia as $tipo) {
                    $countTipo = $asistenciasPeriodo->where('tipo_asistencia_id', $tipo->id)->count();
                    $porcentaje = $totalAsistencias > 0 ? round(($countTipo / $totalAsistencias) * 100, 2) : 0;

                    $porcentajesPorTipo[$tipo->nombre] = $porcentaje;
                    $conteoTipos[$tipo->nombre] = $countTipo;
                }

                $datosEstudiantes[] = [
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $estudiante->user->apellido_paterno ?? '',
                        $estudiante->user->apellido_materno ?? '',
                        $estudiante->user->nombre ?? ''
                    )),
                    'total_asistencias' => $totalAsistencias,
                    'porcentajes_tipo' => $porcentajesPorTipo,
                    'conteo_tipos' => $conteoTipos,
                    'estudiante_id' => $estudiante->id,
                ];
            }

            foreach ($tiposAsistencia as $tipo) {
                $totalTipo = 0;
                foreach ($datosEstudiantes as $estudianteData) {
                    $totalTipo += $estudianteData['conteo_tipos'][$tipo->nombre] ?? 0;
                }
                $estadisticasGrado['porcentajesTipo'][$tipo->nombre] = $estadisticasGrado['totalAsistencias'] > 0
                    ? round(($totalTipo / $estadisticasGrado['totalAsistencias']) * 100, 2)
                    : 0;
            }

            $datosAsistencias[] = [
                'grado' => $grado->grado.'° '.$grado->seccion,
                'estudiantes' => $datosEstudiantes,
                'estadisticas' => $estadisticasGrado,
                'tipos_asistencia' => $tiposAsistencia->pluck('nombre')->toArray(),
                'grado_id' => $grado->id,
            ];

            $estadisticasGenerales['totalEstudiantes'] += $estadisticasGrado['totalEstudiantes'];
            $estadisticasGenerales['totalAsistencias'] += $estadisticasGrado['totalAsistencias'];
        }

        return view('rol.auxiliar.dashboard', compact(
            'periodos',
            'periodoSeleccionado',
            'datosAsistencias',
            'tiposAsistencia',
            'estadisticasGenerales'
        ));
    }

    protected function apoderado(Request $request)
    {
        if (! Auth::user()->hasRole('apoderado')) {
            abort(403, 'Acceso denegado');
        }

        $apoderado = Apoderado::where('user_id', Auth::id())->first();

        if (! $apoderado) {
            abort(403, 'No se encontró el perfil de apoderado');
        }

        $estudiantes = Estudiante::with(['user', 'grado'])
            ->where('apoderado_id', $apoderado->id)
            ->where('estado', 1)
            ->get();

        if ($estudiantes->isEmpty()) {
            return view('rol.apoderado.dashboard', [
                'periodos' => collect(),
                'periodoSeleccionado' => null,
                'bimestresDisponibles' => collect(),
                'bimestresRegulares' => collect(),
                'datosEstudiantes' => [],
                'infoApoderado' => [
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $apoderado->user->apellido_paterno ?? '',
                        $apoderado->user->apellido_materno ?? '',
                        $apoderado->user->nombre ?? ''
                    )),
                    'parentesco' => $apoderado->parentesco,
                    'total_estudiantes' => 0,
                ],
                'bimestreFiltro' => 'anual',
                'esPeriodoRecuperacion' => false,
                'mensajeRecuperacion' => null,
                'info' => 'No tiene estudiantes asignados.',
            ]);
        }

        $estudianteIds = $estudiantes->pluck('id')->toArray();

        $periodos = Periodo::whereHas('matriculas', function ($query) use ($estudianteIds) {
            $query->whereIn('estudiante_id', $estudianteIds)
                ->where('estado', 1);
        })
            ->where('estado', 1)
            ->orderBy('anio', 'desc')
            ->get();

        if ($periodos->isEmpty()) {
            return view('rol.apoderado.dashboard', [
                'periodos' => collect(),
                'periodoSeleccionado' => null,
                'bimestresDisponibles' => collect(),
                'bimestresRegulares' => collect(),
                'datosEstudiantes' => [],
                'infoApoderado' => [
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $apoderado->user->apellido_paterno ?? '',
                        $apoderado->user->apellido_materno ?? '',
                        $apoderado->user->nombre ?? ''
                    )),
                    'parentesco' => $apoderado->parentesco,
                    'total_estudiantes' => $estudiantes->count(),
                ],
                'bimestreFiltro' => 'anual',
                'esPeriodoRecuperacion' => false,
                'mensajeRecuperacion' => null,
                'error' => 'No hay períodos con matrículas para sus estudiantes.',
            ]);
        }

        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        if (! $periodoSeleccionado) {
            return back()->with('error', 'No hay períodos activos disponibles.');
        }

        $bimestreFiltro = $request->input('bimestre', 'anual');

        // Obtener TODOS los bimestres del período
        $bimestresDisponibles = Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
            ->orderBy('bimestre')
            ->get();

        // Verificar si el período actual es de recuperación
        $esPeriodoRecuperacion = in_array($periodoSeleccionado->tipo_periodo, ['recuperacion', 'recuperación']);

        // Obtener bimestres regulares (tipo A) para los filtros
        $bimestresRegulares = $bimestresDisponibles->filter(function ($bim) {
            return $bim->tipo_bimestre === 'A';
        })->values();

        // Mensaje contextual para recuperación
        $mensajeRecuperacion = null;
        if ($esPeriodoRecuperacion) {
            $mensajeRecuperacion = '📌 Estás visualizando el período de RECUPERACIÓN. Las notas mostradas son las notas finales después de la recuperación.';
        }

        $datosEstudiantes = [];

        foreach ($estudiantes as $estudiante) {
            $matricula = Matricula::where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('estado', 1)
                ->first();

            if (! $matricula) {
                $datosEstudiantes[] = [
                    'estudiante_id' => $estudiante->id,
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $estudiante->user->apellido_paterno ?? '',
                        $estudiante->user->apellido_materno ?? '',
                        $estudiante->user->nombre ?? ''
                    )),
                    'grado' => 'No matriculado',
                    'progreso_cursos' => [],
                    'total_cursos' => 0,
                    'progreso_conducta' => [],
                    'total_conducta' => 0,
                    'promedio_general' => null,
                    'cursos_aprobados' => 0,
                    'cursos_desaprobados' => 0,
                    'chartData' => [],
                    'mensaje' => 'El estudiante no está matriculado en el período seleccionado.',
                ];

                continue;
            }

            $gradoMatricula = $matricula->grado;
            $gradoNombre = $gradoMatricula ? $gradoMatricula->grado.'° '.$gradoMatricula->seccion.' - '.$gradoMatricula->nivel : 'Sin grado';

            // Obtener materias del grado
            $materiasAsignadas = Cursogradosecnivanio::where('grado_id', $matricula->grado_id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->with(['materia', 'grado'])
                ->get();

            if ($materiasAsignadas->isEmpty()) {
                $datosEstudiantes[] = [
                    'estudiante_id' => $estudiante->id,
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $estudiante->user->apellido_paterno ?? '',
                        $estudiante->user->apellido_materno ?? '',
                        $estudiante->user->nombre ?? ''
                    )),
                    'grado' => $gradoNombre,
                    'progreso_cursos' => [],
                    'total_cursos' => 0,
                    'progreso_conducta' => [],
                    'total_conducta' => 0,
                    'promedio_general' => null,
                    'cursos_aprobados' => 0,
                    'cursos_desaprobados' => 0,
                    'chartData' => [],
                    'mensaje' => 'No hay materias asignadas para este grado.',
                ];

                continue;
            }

            // Array de materias [materia_id => nombre]
            $materiasArray = [];
            foreach ($materiasAsignadas as $asignacion) {
                $materiasArray[$asignacion->materia_id] = $asignacion->materia->nombre;
            }
            $materiaIds = array_keys($materiasArray);

            if ($esPeriodoRecuperacion) {
                $chartData = [];

                $recuperaciones = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->where('estado', '!=', '0')
                    ->with(['materiaCompetencia', 'materia'])
                    ->get();

                if ($recuperaciones->isEmpty()) {
                    $datosEstudiantes[] = [
                        'estudiante_id' => $estudiante->id,
                        'nombre_completo' => trim(sprintf(
                            '%s %s, %s',
                            $estudiante->user->apellido_paterno ?? '',
                            $estudiante->user->apellido_materno ?? '',
                            $estudiante->user->nombre ?? ''
                        )),
                        'grado' => $gradoNombre,
                        'progreso_cursos' => [],
                        'total_cursos' => 0,
                        'progreso_conducta' => [],
                        'total_conducta' => 0,
                        'promedio_general' => null,
                        'cursos_aprobados' => 0,
                        'cursos_desaprobados' => 0,
                        'chartData' => [],
                        'mensaje' => 'No hay registros de recuperación para este período.',
                    ];

                    continue;
                }

                // Agrupar recuperaciones por materia
                $materiasRecuperacion = [];
                foreach ($recuperaciones as $rec) {
                    $materiaId = $rec->materia_id;
                    if (! isset($materiasRecuperacion[$materiaId])) {
                        $materiasRecuperacion[$materiaId] = [
                            'materia_nombre' => $materiasArray[$materiaId] ?? $rec->materia->nombre ?? 'Materia',
                            'competencias' => [],
                        ];
                    }

                    $notaFinal = $this->competenciaService->convertirEnumANota($rec->nivel_logro_final);
                    $notaInicial = $this->competenciaService->convertirEnumANota($rec->nivel_logro_inicial);

                    $materiasRecuperacion[$materiaId]['competencias'][] = [
                        'id' => $rec->materia_competencia_id,
                        'nombre' => $rec->materiaCompetencia->nombre ?? 'Competencia',
                        'promedio_original' => $notaInicial,
                        'promedio_original_cualitativo' => $rec->nivel_logro_inicial ?? 'C',
                        'nota_recuperacion' => $notaFinal,
                        'promedio_final' => $notaFinal ?? $notaInicial,
                        'promedio_final_cualitativo' => $rec->nivel_logro_final ?? $rec->nivel_logro_inicial ?? 'C',
                        'tiene_recuperacion' => $notaFinal !== null,
                        'esta_aprobada' => ($notaFinal ?? $notaInicial) >= 1.5,
                        'requiere_recuperacion' => false,
                        'tiene_registro_recuperacion' => false,
                        'promedios_bimestres' => [],
                    ];
                }

                // Construir progreso de cursos para recuperación
                $progresoCursos = [];
                $todasNotas = [];
                $cursosAprobados = 0;
                $cursosDesaprobados = 0;

                foreach ($materiasRecuperacion as $materiaId => $materia) {
                    $promedioMateria = collect($materia['competencias'])->avg('promedio_final');
                    $estado = $promedioMateria >= 1.5 ? 'aprobado' : 'desaprobado';

                    if ($estado === 'aprobado') {
                        $cursosAprobados++;
                    } else {
                        $cursosDesaprobados++;
                    }

                    if ($promedioMateria !== null) {
                        $todasNotas[] = $promedioMateria;
                    }

                    $progresoCursos[] = [
                        'curso' => $materia['materia_nombre'],
                        'competencias' => $materia['competencias'],
                        'promedio_general' => $promedioMateria,
                        'promedio_cualitativo' => $this->competenciaService->convertirNotaAEnum($promedioMateria),
                        'estado' => $estado,
                        'total_competencias' => count($materia['competencias']),
                        'competencias_aprobadas' => collect($materia['competencias'])->where('esta_aprobada', true)->count(),
                        'competencias_desaprobadas' => collect($materia['competencias'])->where('esta_aprobada', false)->count(),
                        'competencias_recuperacion' => 0,
                    ];
                }

                $promedioGeneralTodosCursos = ! empty($todasNotas) ? round(array_sum($todasNotas) / count($todasNotas), 2) : null;

                $datosEstudiantes[] = [
                    'estudiante_id' => $estudiante->id,
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $estudiante->user->apellido_paterno ?? '',
                        $estudiante->user->apellido_materno ?? '',
                        $estudiante->user->nombre ?? ''
                    )),
                    'grado' => $gradoNombre,
                    'progreso_cursos' => $progresoCursos,
                    'total_cursos' => count($progresoCursos),
                    'progreso_conducta' => [],
                    'total_conducta' => 0,
                    'promedio_general' => $promedioGeneralTodosCursos,
                    'cursos_aprobados' => $cursosAprobados,
                    'cursos_desaprobados' => count($progresoCursos) - $cursosAprobados,
                    'chartData' => [],
                    'mensaje' => null,
                ];

                continue;
            }

            $periodoBimestreSeleccionado = null;
            if ($bimestreFiltro !== 'anual') {
                $periodoBimestreSeleccionado = $bimestresRegulares->firstWhere('sigla', $bimestreFiltro);
            }

            // Obtener competencias (excluyendo transversales)
            $competenciasQuery = Materiacompetencia::whereIn('materia_id', $materiaIds)
                ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%transversal%'])
                ->whereHas('materiaCriterio', function ($query) use ($matricula, $periodoBimestreSeleccionado, $bimestreFiltro) {
                    $query->where('grado_id', $matricula->grado_id);
                    if ($bimestreFiltro !== 'anual' && $periodoBimestreSeleccionado) {
                        $query->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
                    }
                })
                ->get();

            $competenciasNombres = [];
            $competenciasMateria = [];
            foreach ($competenciasQuery as $competencia) {
                $competenciasNombres[$competencia->id] = $competencia->nombre;
                $competenciasMateria[$competencia->id] = $competencia->materia_id;
            }

            $competenciaIds = array_keys($competenciasNombres);

            if (empty($competenciaIds)) {
                $datosEstudiantes[] = [
                    'estudiante_id' => $estudiante->id,
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $estudiante->user->apellido_paterno ?? '',
                        $estudiante->user->apellido_materno ?? '',
                        $estudiante->user->nombre ?? ''
                    )),
                    'grado' => $gradoNombre,
                    'progreso_cursos' => [],
                    'total_cursos' => 0,
                    'progreso_conducta' => [],
                    'total_conducta' => 0,
                    'promedio_general' => null,
                    'cursos_aprobados' => 0,
                    'cursos_desaprobados' => 0,
                    'chartData' => [],
                    'mensaje' => 'No hay competencias registradas para este período.',
                ];

                continue;
            }

            // Obtener criterios
            $criterios = Materiacriterio::whereIn('materia_competencia_id', $competenciaIds)
                ->where('grado_id', $matricula->grado_id)
                ->when($bimestreFiltro !== 'anual' && $periodoBimestreSeleccionado, function ($q) use ($periodoBimestreSeleccionado) {
                    $q->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
                })
                ->get();

            $criteriosArray = [];
            foreach ($criterios as $criterio) {
                $criteriosArray[$criterio->id] = [
                    'competencia_id' => $criterio->materia_competencia_id,
                    'materia_id' => $competenciasMateria[$criterio->materia_competencia_id] ?? null,
                ];
            }

            $criterioIds = array_keys($criteriosArray);

            // Obtener notas del estudiante
            $notasQuery = Nota::where('estudiante_id', $estudiante->id)
                ->whereIn('materia_criterio_id', $criterioIds)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('publico', '!=', '0')
                ->select('estudiante_id', 'materia_criterio_id', 'nota')
                ->get();

            // Construir array para el servicio de criterios
            $notasArray = [];
            foreach ($notasQuery as $nota) {
                if (isset($criteriosArray[$nota->materia_criterio_id])) {
                    $criterioInfo = $criteriosArray[$nota->materia_criterio_id];
                    $notasArray[] = [
                        'estudiante_id' => $nota->estudiante_id,
                        'materia_criterio_id' => $nota->materia_criterio_id,
                        'materia_competencia_id' => $criterioInfo['competencia_id'],
                        'materia_id' => $criterioInfo['materia_id'],
                        'nota' => $nota->nota,
                    ];
                }
            }

            // Obtener recuperaciones del período de recuperación asociado
            $periodoRecuperacion = Periodo::where('anio', $periodoSeleccionado->anio)
                ->whereIn('tipo_periodo', ['recuperacion', 'recuperación'])
                ->where('estado', '1')
                ->first();

            $recuperacionesPorEstudiante = [];
            if ($periodoRecuperacion) {
                $recuperaciones = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
                    ->whereIn('materia_competencia_id', $competenciaIds)
                    ->where('periodo_id', $periodoRecuperacion->id)
                    ->where('estado', '!=', '0')
                    ->get();

                foreach ($recuperaciones as $rec) {
                    $compId = $rec->materia_competencia_id;
                    $notaRecuperacion = $this->competenciaService->convertirEnumANota($rec->nivel_logro_final);
                    if ($notaRecuperacion !== null) {
                        $recuperacionesPorEstudiante[$compId] = [
                            'nota' => $notaRecuperacion,
                            'tiene_registro' => true,
                            'recuperacion_id' => $rec->id,
                            'estado' => $rec->estado,
                        ];
                    }
                }
            }

            // FLUJO: Procesar datos usando servicios
            $criteriosProcesados = $this->criterioService->procesar($notasArray);
            $competenciasProcesadas = $this->competenciaService->procesar($criteriosProcesados, $recuperacionesPorEstudiante);
            $materiasProcesadas = $this->materiaService->procesar($competenciasProcesadas, $materiasArray, $competenciasNombres);
            $materiasEnriquecidas = $this->evaluacionService->enriquecerMaterias($materiasProcesadas, $recuperacionesPorEstudiante);

            // Obtener promedios por bimestre para cada competencia (solo en modo anual)
            $chartData = [];
            if ($bimestreFiltro === 'anual') {
                $bimestres = $bimestresRegulares;
                foreach ($materiasEnriquecidas as &$materia) {
                    foreach ($materia['competencias'] as &$competencia) {
                        $competenciaId = $competencia['id'];
                        $criteriosCompetencia = Materiacriterio::where('materia_competencia_id', $competenciaId)
                            ->where('grado_id', $matricula->grado_id)
                            ->get();
                        $promediosPorBimestre = [];
                        foreach ($bimestres as $bim) {
                            $criteriosBimestre = $criteriosCompetencia->filter(function ($criterio) use ($bim) {
                                return $criterio->periodo_bimestre_id == $bim->id;
                            });
                            if ($criteriosBimestre->isEmpty()) {
                                $promediosPorBimestre[$bim->bimestre] = null;

                                continue;
                            }
                            $criterioIdsBimestre = $criteriosBimestre->pluck('id')->toArray();
                            $notasBimestre = Nota::where('estudiante_id', $estudiante->id)
                                ->whereIn('materia_criterio_id', $criterioIdsBimestre)
                                ->where('periodo_id', $periodoSeleccionado->id)
                                ->where('publico', '!=', '0')
                                ->get();
                            if ($notasBimestre->isEmpty()) {
                                $promediosPorBimestre[$bim->bimestre] = null;
                            } else {
                                $promedio = round($notasBimestre->avg('nota'), 2);
                                $promediosPorBimestre[$bim->bimestre] = $promedio;
                            }
                        }
                        $competencia['promedios_bimestres'] = $promediosPorBimestre;
                    }
                }

                // Preparar datos para el gráfico
                foreach ($materiasEnriquecidas as $materia) {
                    $materiaNombre = $materia['materia_nombre'];
                    foreach ($materia['competencias'] as $competencia) {
                        $tieneDatos = false;
                        foreach ($bimestres as $bim) {
                            if (isset($competencia['promedios_bimestres'][$bim->bimestre]) &&
                                $competencia['promedios_bimestres'][$bim->bimestre] !== null) {
                                $tieneDatos = true;
                                break;
                            }
                        }
                        if ($tieneDatos) {
                            $chartData[] = [
                                'nombre' => $competencia['nombre'],
                                'materia' => $materiaNombre,
                                'promedios' => $competencia['promedios_bimestres'],
                            ];
                        }
                    }
                }
            }

            // Construir progreso de cursos
            $progresoCursos = [];
            foreach ($materiasEnriquecidas as $materia) {
                $promedioGeneral = $materia['promedio'];
                $estado = $promedioGeneral !== null
                    ? ($this->evaluacionService->competenciaEstaAprobada($promedioGeneral) ? 'aprobado' : 'desaprobado')
                    : 'sin_datos';

                $progresoCursos[] = [
                    'curso' => $materia['materia_nombre'],
                    'competencias' => $materia['competencias'],
                    'promedio_general' => $promedioGeneral,
                    'promedio_cualitativo' => $materia['promedio_cualitativo'],
                    'estado' => $estado,
                    'total_competencias' => $materia['total_competencias'],
                    'competencias_aprobadas' => $materia['competencias_aprobadas_count'],
                    'competencias_desaprobadas' => $materia['competencias_desaprobadas_count'],
                    'competencias_recuperacion' => $materia['competencias_requieren_recuperacion_count'],
                ];
            }

            $progresoConducta = [];

            $conductasDB = Conducta::whereHas('periodosBimestres', function ($query) use ($periodoSeleccionado) {
                $query->where('periodo_id', $periodoSeleccionado->id)
                    ->whereNull('conducta_periodo_bimestres.deleted_at');
            })->distinct()->get();

            if ($conductasDB->isNotEmpty()) {
                $queryConducta = Conductaperiodobimestrenota::with([
                    'conductaPeriodoBimestre.conducta',
                    'periodoBimestre',
                    'curso_grado_sec_niv_anio.materia',
                ])
                    ->where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->where('publico', '!=', '0')
                    ->whereHas('conductaPeriodoBimestre', function ($q) {
                        $q->whereNull('deleted_at');
                    });

                if ($bimestreFiltro !== 'anual' && $periodoBimestreSeleccionado) {
                    $queryConducta->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
                }

                $notasConducta = $queryConducta->get();

                if ($notasConducta->isNotEmpty()) {
                    $notasMap = [];
                    foreach ($notasConducta as $nota) {
                        if (! $nota->conductaPeriodoBimestre || $nota->conductaPeriodoBimestre->trashed()) {
                            continue;
                        }
                        $key = $nota->conductaPeriodoBimestre->conducta_id.'|'.$nota->curso_grado_sec_niv_anio_id;
                        $notasMap[$key] = $nota->nota;
                    }

                    foreach ($conductasDB as $conducta) {
                        $notasConductaCurso = [];
                        $sumaNotas = 0;
                        $totalNotas = 0;

                        foreach ($materiasAsignadas as $curso) {
                            $key = $conducta->id.'|'.$curso->id;
                            $notaValor = $notasMap[$key] ?? null;
                            $notasConductaCurso[] = [
                                'curso' => $curso->materia->nombre ?? 'Sin nombre',
                                'nota' => $notaValor,
                            ];
                            if ($notaValor !== null) {
                                $sumaNotas += $notaValor;
                                $totalNotas++;
                            }
                        }

                        $promedioGeneral = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 2) : null;
                        $estado = $promedioGeneral !== null
                            ? ($promedioGeneral >= 1.5 ? 'adecuado' : 'inadecuado')
                            : 'sin_datos';

                        $progresoConducta[] = [
                            'nombre' => $conducta->nombre,
                            'cursos' => $notasConductaCurso,
                            'promedio_general' => $promedioGeneral,
                            'estado' => $estado,
                        ];
                    }
                }
            }

            // Estadísticas generales
            $totalCompetencias = 0;
            $competenciasAprobadas = 0;
            $promedioGeneralSuma = 0;
            $cursosAprobados = 0;

            foreach ($progresoCursos as $curso) {
                $cursosAprobados += ($curso['estado'] === 'aprobado') ? 1 : 0;
                foreach ($curso['competencias'] as $comp) {
                    $totalCompetencias++;
                    if ($comp['esta_aprobada'] ?? false) {
                        $competenciasAprobadas++;
                    }
                    $promedioGeneralSuma += $comp['promedio_final'] ?? 0;
                }
            }

            $promedioGeneralTodosCursos = $totalCompetencias > 0 ? round($promedioGeneralSuma / $totalCompetencias, 2) : null;

            $datosEstudiantes[] = [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => trim(sprintf(
                    '%s %s, %s',
                    $estudiante->user->apellido_paterno ?? '',
                    $estudiante->user->apellido_materno ?? '',
                    $estudiante->user->nombre ?? ''
                )),
                'grado' => $gradoNombre,
                'progreso_cursos' => $progresoCursos,
                'total_cursos' => count($progresoCursos),
                'progreso_conducta' => $progresoConducta,
                'total_conducta' => count($progresoConducta),
                'promedio_general' => $promedioGeneralTodosCursos,
                'cursos_aprobados' => $cursosAprobados,
                'cursos_desaprobados' => count($progresoCursos) - $cursosAprobados,
                'competencias_aprobadas' => $competenciasAprobadas,
                'total_competencias' => $totalCompetencias,
                'chartData' => $chartData,
                'mensaje' => count($progresoCursos) == 0 && count($progresoConducta) == 0
                    ? 'No hay notas registradas para este período'
                    : null,
            ];
        }

        $infoApoderado = [
            'nombre_completo' => trim(sprintf(
                '%s %s, %s',
                $apoderado->user->apellido_paterno ?? '',
                $apoderado->user->apellido_materno ?? '',
                $apoderado->user->nombre ?? ''
            )),
            'parentesco' => $apoderado->parentesco,
            'total_estudiantes' => count($estudiantes),
        ];

        return view('rol.apoderado.dashboard', compact(
            'periodos',
            'periodoSeleccionado',
            'bimestresDisponibles',
            'bimestresRegulares',
            'datosEstudiantes',
            'infoApoderado',
            'bimestreFiltro',
            'esPeriodoRecuperacion',
            'mensajeRecuperacion'
        ));
    }

    protected function estudiante(Request $request)
    {
        if (! Auth::user()->hasRole('estudiante')) {
            abort(403, 'Acceso denegado');
        }

        $estudiante = Estudiante::where('user_id', Auth::id())->first();

        if (! $estudiante) {
            abort(403, 'No se encontró el perfil de estudiante');
        }

        $estudianteId = $estudiante->id;
        $usuarios = User::with('roles')->get();

        // Obtener periodos disponibles (incluyendo recuperación)
        $periodos = Periodo::whereHas('matriculas', function ($query) use ($estudianteId) {
            $query->where('estudiante_id', $estudianteId)
                ->where('estado', 1);
        })
            ->where('estado', 1)
            ->orderBy('anio', 'desc')
            ->get();

        if ($periodos->isEmpty()) {
            return view('rol.estudiante.dashboard', [
                'periodos' => collect(),
                'periodoSeleccionado' => null,
                'usuarios' => $usuarios,
                'infoEstudiante' => null,
                'bimestreFiltro' => 'anual',
                'bimestresDisponibles' => collect(),
                'chartData' => [],
                'mensajeRecuperacion' => null,
                'esPeriodoRecuperacion' => false,
                'error' => 'No hay períodos con matrículas.',
            ]);
        }

        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        if (! $periodoSeleccionado) {
            return back()->with('error', 'No hay períodos disponibles.');
        }

        $bimestreFiltro = $request->input('bimestre', 'anual');

        // Obtener TODOS los bimestres del período
        $bimestresDisponibles = Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
            ->orderBy('bimestre')
            ->get();

        // Verificar si el período actual es de recuperación
        $esPeriodoRecuperacion = in_array($periodoSeleccionado->tipo_periodo, ['recuperacion', 'recuperación']);

        // Mensaje contextual
        $mensajeRecuperacion = null;
        if ($esPeriodoRecuperacion) {
            $mensajeRecuperacion = '📌 Estás visualizando el período de RECUPERACIÓN. Las notas mostradas son las notas finales después de la recuperación.';
        }

        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->where('estado', 1)
            ->first();

        if (! $matricula) {
            $infoEstudiante = [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => trim(sprintf(
                    '%s %s, %s',
                    $estudiante->user->apellido_paterno ?? '',
                    $estudiante->user->apellido_materno ?? '',
                    $estudiante->user->nombre ?? ''
                )),
                'grado' => 'No matriculado',
                'grado_id' => null,
                'progreso_cursos' => [],
                'progreso_conducta' => [],
                'total_cursos' => 0,
                'total_conducta' => 0,
                'cursos_aprobados' => 0,
                'cursos_desaprobados' => 0,
                'cursos_sin_datos' => 0,
                'promedio_general' => null,
                'mensaje' => 'No estás matriculado en el período seleccionado.',
            ];

            return view('rol.estudiante.dashboard', [
                'periodos' => $periodos,
                'periodoSeleccionado' => $periodoSeleccionado,
                'usuarios' => $usuarios,
                'infoEstudiante' => $infoEstudiante,
                'bimestreFiltro' => $bimestreFiltro,
                'bimestresDisponibles' => $bimestresDisponibles,
                'chartData' => [],
                'mensajeRecuperacion' => $mensajeRecuperacion,
                'esPeriodoRecuperacion' => $esPeriodoRecuperacion,
            ]);
        }

        // Obtener materias del grado
        $materiasAsignadas = Cursogradosecnivanio::where('grado_id', $matricula->grado_id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->with(['materia', 'grado'])
            ->get();

        if ($materiasAsignadas->isEmpty()) {
            $infoEstudiante = [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => trim(sprintf(
                    '%s %s, %s',
                    $estudiante->user->apellido_paterno ?? '',
                    $estudiante->user->apellido_materno ?? '',
                    $estudiante->user->nombre ?? ''
                )),
                'grado' => $matricula->grado ? $matricula->grado->grado.'° '.$matricula->grado->seccion.' - '.$matricula->grado->nivel : 'Sin grado',
                'grado_id' => $matricula->grado_id,
                'progreso_cursos' => [],
                'progreso_conducta' => [],
                'total_cursos' => 0,
                'total_conducta' => 0,
                'cursos_aprobados' => 0,
                'cursos_desaprobados' => 0,
                'cursos_sin_datos' => 0,
                'promedio_general' => null,
                'mensaje' => 'No hay materias asignadas para este grado.',
            ];

            return view('rol.estudiante.dashboard', [
                'periodos' => $periodos,
                'periodoSeleccionado' => $periodoSeleccionado,
                'usuarios' => $usuarios,
                'infoEstudiante' => $infoEstudiante,
                'bimestreFiltro' => $bimestreFiltro,
                'bimestresDisponibles' => $bimestresDisponibles,
                'chartData' => [],
                'mensajeRecuperacion' => $mensajeRecuperacion,
                'esPeriodoRecuperacion' => $esPeriodoRecuperacion,
            ]);
        }

        // Array de materias [materia_id => nombre]
        $materiasArray = [];
        foreach ($materiasAsignadas as $asignacion) {
            $materiasArray[$asignacion->materia_id] = $asignacion->materia->nombre;
        }
        $materiaIds = array_keys($materiasArray);

        // Inicializar chartData
        $chartData = [];

        if ($esPeriodoRecuperacion) {
            // En período de recuperación, obtenemos los datos de Recuperacioncompetencia
            $recuperaciones = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('estado', '!=', '0')
                ->with(['materiaCompetencia', 'materia'])
                ->get();

            if ($recuperaciones->isEmpty()) {
                $infoEstudiante = [
                    'estudiante_id' => $estudiante->id,
                    'nombre_completo' => trim(sprintf(
                        '%s %s, %s',
                        $estudiante->user->apellido_paterno ?? '',
                        $estudiante->user->apellido_materno ?? '',
                        $estudiante->user->nombre ?? ''
                    )),
                    'grado' => $matricula->grado ? $matricula->grado->grado.'° '.$matricula->grado->seccion.' - '.$matricula->grado->nivel : 'Sin grado',
                    'grado_id' => $matricula->grado_id,
                    'progreso_cursos' => [],
                    'progreso_conducta' => [],
                    'total_cursos' => 0,
                    'total_conducta' => 0,
                    'cursos_aprobados' => 0,
                    'cursos_desaprobados' => 0,
                    'cursos_sin_datos' => 0,
                    'promedio_general' => null,
                    'mensaje' => 'No hay registros de recuperación para este período.',
                ];

                return view('rol.estudiante.dashboard', [
                    'periodos' => $periodos,
                    'periodoSeleccionado' => $periodoSeleccionado,
                    'usuarios' => $usuarios,
                    'infoEstudiante' => $infoEstudiante,
                    'bimestreFiltro' => $bimestreFiltro,
                    'bimestresDisponibles' => $bimestresDisponibles,
                    'chartData' => $chartData,
                    'mensajeRecuperacion' => $mensajeRecuperacion,
                    'esPeriodoRecuperacion' => $esPeriodoRecuperacion,
                ]);
            }

            // Agrupar recuperaciones por materia
            $materiasRecuperacion = [];
            foreach ($recuperaciones as $rec) {
                $materiaId = $rec->materia_id;
                if (! isset($materiasRecuperacion[$materiaId])) {
                    $materiasRecuperacion[$materiaId] = [
                        'materia_nombre' => $materiasArray[$materiaId] ?? $rec->materia->nombre ?? 'Materia',
                        'competencias' => [],
                    ];
                }

                $notaFinal = $this->competenciaService->convertirEnumANota($rec->nivel_logro_final);
                $notaInicial = $this->competenciaService->convertirEnumANota($rec->nivel_logro_inicial);

                $materiasRecuperacion[$materiaId]['competencias'][] = [
                    'id' => $rec->materia_competencia_id,
                    'nombre' => $rec->materiaCompetencia->nombre ?? 'Competencia',
                    'promedio_original' => $notaInicial,
                    'promedio_original_cualitativo' => $rec->nivel_logro_inicial ?? 'C',
                    'nota_recuperacion' => $notaFinal,
                    'promedio_final' => $notaFinal ?? $notaInicial,
                    'promedio_final_cualitativo' => $rec->nivel_logro_final ?? $rec->nivel_logro_inicial ?? 'C',
                    'tiene_recuperacion' => $notaFinal !== null,
                    'esta_aprobada' => ($notaFinal ?? $notaInicial) >= 1.5,
                    'requiere_recuperacion' => false,
                    'tiene_registro_recuperacion' => false,
                    'promedios_bimestres' => [],
                ];
            }

            // Construir progreso de cursos para recuperación
            $progresoCursos = [];
            $todasNotas = [];
            $cursosAprobados = 0;
            $cursosDesaprobados = 0;

            foreach ($materiasRecuperacion as $materiaId => $materia) {
                $promedioMateria = collect($materia['competencias'])->avg('promedio_final');
                $estado = $promedioMateria >= 1.5 ? 'aprobado' : 'desaprobado';

                if ($estado === 'aprobado') {
                    $cursosAprobados++;
                } else {
                    $cursosDesaprobados++;
                }

                if ($promedioMateria !== null) {
                    $todasNotas[] = $promedioMateria;
                }

                $progresoCursos[] = [
                    'curso' => $materia['materia_nombre'],
                    'competencias' => $materia['competencias'],
                    'promedio_general' => $promedioMateria,
                    'promedio_cualitativo' => $this->competenciaService->convertirNotaAEnum($promedioMateria),
                    'estado' => $estado,
                    'total_competencias' => count($materia['competencias']),
                    'competencias_aprobadas' => collect($materia['competencias'])->where('esta_aprobada', true)->count(),
                    'competencias_desaprobadas' => collect($materia['competencias'])->where('esta_aprobada', false)->count(),
                    'competencias_recuperacion' => 0,
                ];
            }

            $promedioGeneralTodosCursos = ! empty($todasNotas) ? round(array_sum($todasNotas) / count($todasNotas), 2) : null;

            $infoEstudiante = [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => trim(sprintf(
                    '%s %s, %s',
                    $estudiante->user->apellido_paterno ?? '',
                    $estudiante->user->apellido_materno ?? '',
                    $estudiante->user->nombre ?? ''
                )),
                'grado' => $matricula->grado ? $matricula->grado->grado.'° '.$matricula->grado->seccion.' - '.$matricula->grado->nivel : 'Sin grado',
                'grado_id' => $matricula->grado_id,
                'progreso_cursos' => $progresoCursos,
                'progreso_conducta' => [],
                'total_cursos' => count($progresoCursos),
                'total_conducta' => 0,
                'cursos_aprobados' => $cursosAprobados,
                'cursos_desaprobados' => $cursosDesaprobados,
                'cursos_sin_datos' => 0,
                'promedio_general' => $promedioGeneralTodosCursos,
                'mensaje' => null,
            ];

            return view('rol.estudiante.dashboard', [
                'periodos' => $periodos,
                'periodoSeleccionado' => $periodoSeleccionado,
                'usuarios' => $usuarios,
                'infoEstudiante' => $infoEstudiante,
                'bimestreFiltro' => $bimestreFiltro,
                'bimestresDisponibles' => $bimestresDisponibles,
                'chartData' => $chartData,
                'mensajeRecuperacion' => $mensajeRecuperacion,
                'esPeriodoRecuperacion' => $esPeriodoRecuperacion,
            ]);
        }

        // Obtener periodo_bimestre seleccionado si no es anual
        $periodoBimestreSeleccionado = null;
        if ($bimestreFiltro !== 'anual') {
            $periodoBimestreSeleccionado = $bimestresDisponibles->firstWhere('sigla', $bimestreFiltro);
        }

        // Obtener competencias (excluyendo transversales)
        $competenciasQuery = Materiacompetencia::whereIn('materia_id', $materiaIds)
            ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%transversal%'])
            ->whereHas('materiaCriterio', function ($query) use ($matricula, $periodoBimestreSeleccionado, $bimestreFiltro) {
                $query->where('grado_id', $matricula->grado_id);

                if ($bimestreFiltro !== 'anual' && $periodoBimestreSeleccionado) {
                    $query->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
                }
            })
            ->get();

        $competenciasNombres = [];
        $competenciasMateria = [];
        foreach ($competenciasQuery as $competencia) {
            $competenciasNombres[$competencia->id] = $competencia->nombre;
            $competenciasMateria[$competencia->id] = $competencia->materia_id;
        }

        $competenciaIds = array_keys($competenciasNombres);

        if (empty($competenciaIds)) {
            $infoEstudiante = [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => trim(sprintf(
                    '%s %s, %s',
                    $estudiante->user->apellido_paterno ?? '',
                    $estudiante->user->apellido_materno ?? '',
                    $estudiante->user->nombre ?? ''
                )),
                'grado' => $matricula->grado ? $matricula->grado->grado.'° '.$matricula->grado->seccion.' - '.$matricula->grado->nivel : 'Sin grado',
                'grado_id' => $matricula->grado_id,
                'progreso_cursos' => [],
                'progreso_conducta' => [],
                'total_cursos' => 0,
                'total_conducta' => 0,
                'cursos_aprobados' => 0,
                'cursos_desaprobados' => 0,
                'cursos_sin_datos' => 0,
                'promedio_general' => null,
                'mensaje' => 'No hay competencias registradas para este período.',
            ];

            return view('rol.estudiante.dashboard', [
                'periodos' => $periodos,
                'periodoSeleccionado' => $periodoSeleccionado,
                'usuarios' => $usuarios,
                'infoEstudiante' => $infoEstudiante,
                'bimestreFiltro' => $bimestreFiltro,
                'bimestresDisponibles' => $bimestresDisponibles,
                'chartData' => $chartData,
                'mensajeRecuperacion' => $mensajeRecuperacion,
                'esPeriodoRecuperacion' => $esPeriodoRecuperacion,
            ]);
        }

        // Obtener criterios
        $criterios = Materiacriterio::whereIn('materia_competencia_id', $competenciaIds)
            ->where('grado_id', $matricula->grado_id)
            ->when($bimestreFiltro !== 'anual' && $periodoBimestreSeleccionado, function ($q) use ($periodoBimestreSeleccionado) {
                $q->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
            })
            ->get();

        $criteriosArray = [];
        foreach ($criterios as $criterio) {
            $criteriosArray[$criterio->id] = [
                'competencia_id' => $criterio->materia_competencia_id,
                'materia_id' => $competenciasMateria[$criterio->materia_competencia_id] ?? null,
            ];
        }

        $criterioIds = array_keys($criteriosArray);

        // Obtener notas del estudiante
        $notasQuery = Nota::where('estudiante_id', $estudiante->id)
            ->whereIn('materia_criterio_id', $criterioIds)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->where('publico', '!=', '0')
            ->select('estudiante_id', 'materia_criterio_id', 'nota')
            ->get();

        // Construir array para el servicio de criterios
        $notasArray = [];
        foreach ($notasQuery as $nota) {
            if (isset($criteriosArray[$nota->materia_criterio_id])) {
                $criterioInfo = $criteriosArray[$nota->materia_criterio_id];
                $notasArray[] = [
                    'estudiante_id' => $nota->estudiante_id,
                    'materia_criterio_id' => $nota->materia_criterio_id,
                    'materia_competencia_id' => $criterioInfo['competencia_id'],
                    'materia_id' => $criterioInfo['materia_id'],
                    'nota' => $nota->nota,
                ];
            }
        }

        // Obtener recuperaciones del período de recuperación asociado
        $periodoRecuperacion = Periodo::where('anio', $periodoSeleccionado->anio)
            ->whereIn('tipo_periodo', ['recuperacion', 'recuperación'])
            ->where('estado', '1')
            ->first();

        $recuperacionesPorEstudiante = [];
        if ($periodoRecuperacion) {
            $recuperaciones = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
                ->whereIn('materia_competencia_id', $competenciaIds)
                ->where('periodo_id', $periodoRecuperacion->id)
                ->where('estado', '!=', '0')
                ->get();

            foreach ($recuperaciones as $rec) {
                $compId = $rec->materia_competencia_id;
                $notaRecuperacion = $this->competenciaService->convertirEnumANota($rec->nivel_logro_final);

                if ($notaRecuperacion !== null) {
                    $recuperacionesPorEstudiante[$compId] = [
                        'nota' => $notaRecuperacion,
                        'tiene_registro' => true,
                        'recuperacion_id' => $rec->id,
                        'estado' => $rec->estado,
                    ];
                }
            }
        }

        // FLUJO: Procesar datos usando servicios
        $criteriosProcesados = $this->criterioService->procesar($notasArray);
        $competenciasProcesadas = $this->competenciaService->procesar($criteriosProcesados, $recuperacionesPorEstudiante);
        $materiasProcesadas = $this->materiaService->procesar($competenciasProcesadas, $materiasArray, $competenciasNombres);

        // Aplicar evaluación
        $materiasEnriquecidas = $this->evaluacionService->enriquecerMaterias($materiasProcesadas, $recuperacionesPorEstudiante);

        // Obtener promedios por bimestre para cada competencia (solo en modo anual)
        if ($bimestreFiltro === 'anual') {
            $bimestres = $bimestresDisponibles->filter(function ($bim) {
                return $bim->tipo_bimestre === 'A';
            });

            foreach ($materiasEnriquecidas as &$materia) {
                foreach ($materia['competencias'] as &$competencia) {
                    $competenciaId = $competencia['id'];

                    $criteriosCompetencia = Materiacriterio::where('materia_competencia_id', $competenciaId)
                        ->where('grado_id', $matricula->grado_id)
                        ->get();

                    $promediosPorBimestre = [];

                    foreach ($bimestres as $bim) {
                        $criteriosBimestre = $criteriosCompetencia->filter(function ($criterio) use ($bim) {
                            return $criterio->periodo_bimestre_id == $bim->id;
                        });

                        if ($criteriosBimestre->isEmpty()) {
                            $promediosPorBimestre[$bim->bimestre] = null;

                            continue;
                        }

                        $criterioIdsBimestre = $criteriosBimestre->pluck('id')->toArray();
                        $notasBimestre = Nota::where('estudiante_id', $estudiante->id)
                            ->whereIn('materia_criterio_id', $criterioIdsBimestre)
                            ->where('periodo_id', $periodoSeleccionado->id)
                            ->where('publico', '!=', '0')
                            ->get();

                        if ($notasBimestre->isEmpty()) {
                            $promediosPorBimestre[$bim->bimestre] = null;
                        } else {
                            $promedio = round($notasBimestre->avg('nota'), 2);
                            $promediosPorBimestre[$bim->bimestre] = $promedio;
                        }
                    }

                    $competencia['promedios_bimestres'] = $promediosPorBimestre;
                }
            }

            // Preparar datos para el gráfico
            foreach ($materiasEnriquecidas as $materia) {
                $materiaNombre = $materia['materia_nombre'];
                foreach ($materia['competencias'] as $competencia) {
                    $tieneDatos = false;
                    foreach ($bimestres as $bim) {
                        if (isset($competencia['promedios_bimestres'][$bim->bimestre]) &&
                            $competencia['promedios_bimestres'][$bim->bimestre] !== null) {
                            $tieneDatos = true;
                            break;
                        }
                    }
                    if ($tieneDatos) {
                        $chartData[] = [
                            'nombre' => $competencia['nombre'],
                            'materia' => $materiaNombre,
                            'promedios' => $competencia['promedios_bimestres'],
                        ];
                    }
                }
            }
        }

        // Construir resultado final
        $progresoCursos = [];
        foreach ($materiasEnriquecidas as $materia) {
            $promedioGeneral = $materia['promedio'];
            $estado = $promedioGeneral !== null
                ? ($this->evaluacionService->competenciaEstaAprobada($promedioGeneral) ? 'aprobado' : 'desaprobado')
                : 'sin_datos';

            $progresoCursos[] = [
                'curso' => $materia['materia_nombre'],
                'competencias' => $materia['competencias'],
                'promedio_general' => $promedioGeneral,
                'promedio_cualitativo' => $materia['promedio_cualitativo'],
                'estado' => $estado,
                'total_competencias' => $materia['total_competencias'],
                'competencias_aprobadas' => $materia['competencias_aprobadas_count'],
                'competencias_desaprobadas' => $materia['competencias_desaprobadas_count'],
                'competencias_recuperacion' => $materia['competencias_requieren_recuperacion_count'],
            ];
        }

        $progresoConducta = [];

        $conductasDB = Conducta::whereHas('periodosBimestres', function ($query) use ($periodoSeleccionado) {
            $query->where('periodo_id', $periodoSeleccionado->id)
                ->whereNull('conducta_periodo_bimestres.deleted_at');
        })->distinct()->get();

        if ($conductasDB->isNotEmpty()) {
            $bimestresList = Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
                ->where('tipo_bimestre', 'A')
                ->orderBy('bimestre')
                ->get();

            $periodoBimestreConducta = ($bimestreFiltro !== 'anual')
                ? $bimestresList->firstWhere('sigla', $bimestreFiltro)
                : null;

            $queryConducta = Conductaperiodobimestrenota::with([
                'conductaPeriodoBimestre.conducta',
                'periodoBimestre',
                'curso_grado_sec_niv_anio.materia',
            ])
                ->where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('publico', '!=', '0')
                ->whereHas('conductaPeriodoBimestre', function ($q) {
                    $q->whereNull('deleted_at');
                });

            if ($bimestreFiltro !== 'anual' && $periodoBimestreConducta) {
                $queryConducta->where('periodo_bimestre_id', $periodoBimestreConducta->id);
            }

            $notasConducta = $queryConducta->get();

            if ($notasConducta->isNotEmpty()) {
                $notasMap = [];
                foreach ($notasConducta as $nota) {
                    if (! $nota->conductaPeriodoBimestre || $nota->conductaPeriodoBimestre->trashed()) {
                        continue;
                    }
                    $key = $nota->conductaPeriodoBimestre->conducta_id.'|'.$nota->curso_grado_sec_niv_anio_id;
                    $notasMap[$key] = $nota->nota;
                }

                foreach ($conductasDB as $conducta) {
                    $notasConductaCurso = [];
                    $sumaNotas = 0;
                    $totalNotas = 0;

                    foreach ($materiasAsignadas as $curso) {
                        $key = $conducta->id.'|'.$curso->id;
                        $notaValor = $notasMap[$key] ?? null;
                        $notasConductaCurso[] = [
                            'curso' => $curso->materia->nombre ?? 'Sin nombre',
                            'nota' => $notaValor,
                        ];
                        if ($notaValor !== null) {
                            $sumaNotas += $notaValor;
                            $totalNotas++;
                        }
                    }

                    $promedioGeneral = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 2) : null;
                    $estado = $promedioGeneral !== null
                        ? ($promedioGeneral >= 1.5 ? 'adecuado' : 'inadecuado')
                        : 'sin_datos';

                    $progresoConducta[] = [
                        'nombre' => $conducta->nombre,
                        'cursos' => $notasConductaCurso,
                        'promedio_general' => $promedioGeneral,
                        'estado' => $estado,
                    ];
                }
            }
        }

        // Estadísticas generales
        $cursosAprobados = 0;
        $cursosDesaprobados = 0;
        $cursosSinDatos = 0;
        $todasNotas = [];

        foreach ($progresoCursos as $curso) {
            if ($curso['promedio_general'] !== null) {
                $todasNotas[] = $curso['promedio_general'];
                if ($curso['estado'] === 'aprobado') {
                    $cursosAprobados++;
                } elseif ($curso['estado'] === 'desaprobado') {
                    $cursosDesaprobados++;
                }
            } else {
                $cursosSinDatos++;
            }
        }

        $promedioGeneralTodosCursos = ! empty($todasNotas) ? round(array_sum($todasNotas) / count($todasNotas), 2) : null;

        $infoEstudiante = [
            'estudiante_id' => $estudiante->id,
            'nombre_completo' => trim(sprintf(
                '%s %s, %s',
                $estudiante->user->apellido_paterno ?? '',
                $estudiante->user->apellido_materno ?? '',
                $estudiante->user->nombre ?? ''
            )),
            'grado' => $matricula->grado ? $matricula->grado->grado.'° '.$matricula->grado->seccion.' - '.$matricula->grado->nivel : 'Sin grado',
            'grado_id' => $matricula->grado_id,
            'progreso_cursos' => $progresoCursos,
            'progreso_conducta' => $progresoConducta,
            'total_cursos' => count($progresoCursos),
            'total_conducta' => count($progresoConducta),
            'cursos_aprobados' => $cursosAprobados,
            'cursos_desaprobados' => $cursosDesaprobados,
            'cursos_sin_datos' => $cursosSinDatos,
            'promedio_general' => $promedioGeneralTodosCursos,
            'mensaje' => count($progresoCursos) == 0 ? 'No hay notas registradas para este período' : null,
        ];

        return view('rol.estudiante.dashboard', [
            'periodos' => $periodos,
            'periodoSeleccionado' => $periodoSeleccionado,
            'usuarios' => $usuarios,
            'infoEstudiante' => $infoEstudiante,
            'bimestreFiltro' => $bimestreFiltro,
            'bimestresDisponibles' => $bimestresDisponibles,
            'chartData' => $chartData,
            'mensajeRecuperacion' => $mensajeRecuperacion,
            'esPeriodoRecuperacion' => $esPeriodoRecuperacion,
        ]);
    }

    protected function NuevoRol()
    {
        $usuarios = User::with('roles')->get();

        return view('rol.nuevorol.dashboard', compact('usuarios'));
    }
}
