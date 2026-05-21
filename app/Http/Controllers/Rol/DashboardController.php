<?php

namespace App\Http\Controllers\Rol;

use App\Http\Controllers\Controller;
use App\Models\Apoderado;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Materia\Materiacriterio;
use App\Models\Materia\Materiacompetencia;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Periodo;
use App\Models\Periodobimestre;
use App\Models\Conductanota;
use App\Models\Conducta;
use App\Models\Conductaperiodobimestrenota;
use App\Models\Auxiliar;
use App\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class DashboardController extends Controller
{
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
        if (!Auth::user()->hasRole('admin')) {
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
        if (!Auth::user()->hasRole('director')) {
            abort(403, 'Acceso denegado');
        }
        $user = Auth::user();

        // Obtener periodo seleccionado o el activo por defecto
        $periodoSeleccionado = null;

        if ($request->has('periodo_id')) {
            $periodoSeleccionado = Periodo::find($request->periodo_id);
        }

        if (!$periodoSeleccionado) {
            $periodoSeleccionado = Periodo::where('estado', '1')->first();
        }

        // Obtener todos los periodos para el selector
        $periodos = Periodo::orderBy('anio', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Si no hay periodo seleccionado, crear objeto vacío
        if (!$periodoSeleccionado && $periodos->isNotEmpty()) {
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
            'excelentes' => 0,
            'buenos' => 0,
            'regulares' => 0,
            'bajos' => 0,
            'total_materias' => 0,
        ];

        // Si hay periodo seleccionado, cargar datos
        if ($periodoSeleccionado) {
            // Obtener grados con estudiantes matriculados en el periodo
            $grados = Grado::where('estado', '1')
                ->with(['matriculas' => function($query) use ($periodoSeleccionado) {
                    $query->where('periodo_id', $periodoSeleccionado->id)
                        ->where('estado', '1')
                        ->with(['estudiante']);
                }])
                ->get();

            // Obtener IDs de estudiantes por grado
            $estudiantesPorGrado = [];
            foreach ($grados as $grado) {
                $estudiantesPorGrado[$grado->id] = $grado->matriculas->pluck('estudiante_id')->toArray();
            }

            // Obtener todos los IDs de estudiantes
            $todosEstudianteIds = collect($estudiantesPorGrado)->flatten()->unique()->toArray();

            // Obtener promedios si hay estudiantes
            $promediosNotas = [];
            $promediosConducta = [];

            if (!empty($todosEstudianteIds)) {
                // Obtener IDs de competencias transversales para excluir
                $competenciasTransversalesIds = Materiacompetencia::where('nombre', 'like', '%TRANSVERSAL%')
                    ->orWhere('nombre', 'like', '%TRANSVERSALES%')
                    ->orWhere('descripcion', 'like', '%TRANSVERSAL%')
                    ->orWhere('descripcion', 'like', '%TRANSVERSALES%')
                    ->pluck('id')
                    ->toArray();

                // Obtener IDs de criterios que pertenecen a competencias transversales
                $criteriosTransversalesIds = Materiacriterio::whereIn('materia_competencia_id', $competenciasTransversalesIds)
                    ->pluck('id')
                    ->toArray();

                // Obtener promedios de notas académicas EXCLUYENDO competencias transversales
                $notasPromedio = Nota::selectRaw('estudiante_id, AVG(nota) as promedio')
                    ->whereIn('estudiante_id', $todosEstudianteIds)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->whereNotIn('materia_criterio_id', $criteriosTransversalesIds)
                    ->where(function($query) {
                        $query->where('publico', '1')
                            ->orWhere('publico', '2')
                            ->orWhere('publico', '3');
                    })
                    ->groupBy('estudiante_id')
                    ->get()
                    ->keyBy('estudiante_id');

                // Obtener todos los cursos_grados (materias por grado) del periodo
                $cursosIds = Cursogradosecnivanio::where('periodo_id', $periodoSeleccionado->id)
                    ->pluck('id')
                    ->toArray();

                // Obtener promedios de notas de conducta para los cursos del periodo
                $conductaPromedio = Conductanota::selectRaw('estudiante_id, AVG(nota) as promedio')
                    ->whereIn('estudiante_id', $todosEstudianteIds)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->whereIn('curso_grado_sec_niv_anio_id', $cursosIds)
                    ->where(function($query) {
                        $query->where('publico', '1')
                            ->orWhere('publico', '2')
                            ->orWhere('publico', '3');
                    })
                    ->groupBy('estudiante_id')
                    ->get()
                    ->keyBy('estudiante_id');

                // Asignar promedios - NOTA: No convertir si ya están en escala 1-4
                foreach ($notasPromedio as $estudianteId => $nota) {
                    // Si las notas ya están en escala 1-4, usar directamente
                    // Si están en 0-100, usar: $this->convertirEscalaNota($nota->promedio, 0, 100, 1, 4)
                    $promediosNotas[$estudianteId] = round($nota->promedio, 2);
                }

                foreach ($conductaPromedio as $estudianteId => $nota) {
                    // Si las notas de conducta ya están en escala 1-4, usar directamente
                    // Si están en 0-100, usar: $this->convertirEscalaNota($nota->promedio, 0, 100, 1, 4)
                    $promediosConducta[$estudianteId] = round($nota->promedio, 2);
                }
            }

            // Obtener información de materias por grado para el periodo
            $materiasPorGrado = Cursogradosecnivanio::where('periodo_id', $periodoSeleccionado->id)
                ->with(['materia', 'docente'])
                ->get()
                ->groupBy('grado_id');

            // Calcular promedios por grado
            foreach ($grados as $grado) {
                $estudianteIds = $estudiantesPorGrado[$grado->id] ?? [];
                $grado->estudiantes_matriculados = count($estudianteIds);

                // Agregar información de materias del grado en este periodo
                $materiasGrado = $materiasPorGrado[$grado->id] ?? collect();
                $grado->total_materias = $materiasGrado->count();
                $grado->materias_lista = $materiasGrado->pluck('materia.nombre')->unique()->values();
                $grado->docentes_lista = $materiasGrado->pluck('docente.nombre_completo')->filter()->unique()->values();

                if (!empty($estudianteIds)) {
                    $sumaNotas = 0;
                    $sumaConducta = 0;
                    $contador = 0;

                    foreach ($estudianteIds as $estudianteId) {
                        if (isset($promediosNotas[$estudianteId])) {
                            $sumaNotas += $promediosNotas[$estudianteId];
                        }
                        if (isset($promediosConducta[$estudianteId])) {
                            $sumaConducta += $promediosConducta[$estudianteId];
                        }
                        $contador++;
                    }

                    $grado->promedio_notas = $contador > 0 ? round($sumaNotas / $contador, 2) : 0;
                    $grado->promedio_conducta = $contador > 0 ? round($sumaConducta / $contador, 2) : 0;
                    $grado->promedio_general = $contador > 0
                        ? round(($grado->promedio_notas + $grado->promedio_conducta) / 2, 2)
                        : 0;
                } else {
                    $grado->promedio_notas = 0;
                    $grado->promedio_conducta = 0;
                    $grado->promedio_general = 0;
                }

                // Determinar categoría del grado
                if ($grado->promedio_general >= 3.5) {
                    $grado->categoria = 'excelente';
                    $grado->color_categoria = 'success';
                    $grado->icono_categoria = 'trophy';
                } elseif ($grado->promedio_general >= 2.5) {
                    $grado->categoria = 'bueno';
                    $grado->color_categoria = 'primary';
                    $grado->icono_categoria = 'medal';
                } elseif ($grado->promedio_general >= 2.0) {
                    $grado->categoria = 'regular';
                    $grado->color_categoria = 'warning';
                    $grado->icono_categoria = 'certificate';
                } else {
                    $grado->categoria = 'bajo';
                    $grado->color_categoria = 'danger';
                    $grado->icono_categoria = 'exclamation-triangle';
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
            ];

            // Agregar estadísticas adicionales
            $estadisticas['excelentes'] = $grados->filter(function($grado) {
                return $grado->promedio_general >= 3.5;
            })->count();

            $estadisticas['buenos'] = $grados->filter(function($grado) {
                return $grado->promedio_general >= 2.5 && $grado->promedio_general < 3.5;
            })->count();

            $estadisticas['regulares'] = $grados->filter(function($grado) {
                return $grado->promedio_general >= 2.0 && $grado->promedio_general < 2.5;
            })->count();

            $estadisticas['bajos'] = $grados->filter(function($grado) {
                return $grado->promedio_general < 2.0;
            })->count();

            // Estadísticas por nivel
            $estadisticas['por_nivel'] = $grados->groupBy('nivel')->map(function($gradosNivel) {
                return [
                    'total' => $gradosNivel->count(),
                    'estudiantes' => $gradosNivel->sum('estudiantes_matriculados'),
                    'materias' => $gradosNivel->sum('total_materias'),
                    'promedio' => round($gradosNivel->avg('promedio_general'), 2),
                    'excelentes' => $gradosNivel->filter(fn($g) => $g->promedio_general >= 3.5)->count(),
                    'buenos' => $gradosNivel->filter(fn($g) => $g->promedio_general >= 2.5 && $g->promedio_general < 3.5)->count(),
                    'regulares' => $gradosNivel->filter(fn($g) => $g->promedio_general >= 2.0 && $g->promedio_general < 2.5)->count(),
                    'bajos' => $gradosNivel->filter(fn($g) => $g->promedio_general < 2.0)->count(),
                ];
            });

            // Ordenar grados por promedio general descendente
            $grados = $grados->sortByDesc('promedio_general')->values();
        }

        return view('rol.director.dashboard', [
            'periodoSeleccionado' => $periodoSeleccionado,
            'periodos' => $periodos,
            'grados' => $grados,
            'estadisticas' => $estadisticas,
            'user' => $user
        ]);
    }

    protected function docente(Request $request)
    {
        if (!Auth::user()->hasRole('docente')) {
            abort(403, 'Acceso denegado');
        }

        $docente = Auth::user()->docente;

        if (!$docente) {
            abort(404, 'Perfil de docente no encontrado');
        }

        // Obtener solo los periodos donde el docente tiene asignaciones
        $periodos = Periodo::whereHas('cursosGradoSecNivAnio', function($query) use ($docente) {
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
        $estudiantes = Estudiante::whereHas('matriculas', function($query) use ($grado, $periodo) {
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
            $criteriosPorBimestre[$bim->sigla] = Materiacriterio::whereHas('materiaCompetencia', function($q) use ($materia) {
                $q->where('materia_id', $materia->id)
                    ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%');
            })->where('grado_id', $grado->id)
            ->where('periodo_bimestre_id', $bim->id)
            ->count();
        }

        // Obtener conductas activas
        $conductas = Conducta::whereHas('periodosBimestres', function($q) use ($periodo) {
            $q->where('periodo_id', $periodo->id)
                ->whereNull('conducta_periodo_bimestres.deleted_at');
        })->distinct()->get();

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
                'min' => null,
                'max' => null,
            ];
        }

        foreach ($estudiantes as $index => $estudiante) {
            // Calcular notas por bimestre
            $notasPorBimestre = [];
            $criteriosRegistrados = [];
            $bimestresConNotas = 0;
            $sumaNotasEstudiante = 0;

            foreach ($bimestres as $bim) {
                $criteriosIds = Materiacriterio::whereHas('materiaCompetencia', function($q) use ($materia) {
                    $q->where('materia_id', $materia->id)
                        ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%');
                })->where('grado_id', $grado->id)
                ->where('periodo_bimestre_id', $bim->id)
                ->pluck('id')
                ->toArray();

                $notas = Nota::whereIn('materia_criterio_id', $criteriosIds)
                    ->where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('publico', '!=', '0')
                    ->get();

                $criteriosRegistrados[$bim->sigla] = $notas->count();
                $totalPosibles = count($criteriosIds);

                $estadisticasNotas[$bim->sigla]['total_notas_registradas'] += $notas->count();
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

            // Calcular conducta por bimestre
            $conductaPorBimestre = [];
            $bimestresConConducta = 0;
            $sumaConductaEstudiante = 0;

            foreach ($bimestres as $bim) {
                $periodoBimestre = $bim;

                $notasConducta = Conductaperiodobimestrenota::where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('periodo_bimestre_id', $periodoBimestre->id)
                    ->where('publico', '!=', '0')
                    ->whereHas('curso_grado_sec_niv_anio', function($q) use ($asignacion) {
                        $q->where('id', $asignacion->id);
                    })
                    ->get();

                $conductaRegistradas = $notasConducta->count();
                $totalPosiblesConducta = $conductas->count();

                $estadisticasConducta[$bim->sigla]['total_conductas_registradas'] += $conductaRegistradas;
                $estadisticasConducta[$bim->sigla]['total_conductas_posibles'] += $totalPosiblesConducta;

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

            // Determinar estado del estudiante
            $estadoTexto = 'Sin datos';
            $estadoClase = 'danger';
            if ($promedioNotas !== null && $promedioConducta !== null) {
                if ($promedioNotas > 2 && $promedioConducta > 2) {
                    $estadoTexto = 'Completo';
                    $estadoClase = 'success';
                } else {
                    $estadoTexto = 'Parcial';
                    $estadoClase = 'warning';
                }
            } else if ($promedioNotas !== null || $promedioConducta !== null) {
                $estadoTexto = 'Parcial';
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
                'estado_texto' => $estadoTexto,
                'estado_clase' => $estadoClase,
                'tiene_notas' => $promedioNotas !== null,
                'tiene_conducta' => $promedioConducta !== null,
            ];
        }

        // Calcular promedios generales por bimestre
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
            'grado_nombre' => $grado->grado . '° ' . $grado->seccion,
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
                'con_datos' => count(array_filter($estadisticasNotas, fn($s) => ($s['total_estudiantes_con_notas'] ?? 0) > 0)),
            ],
            'resumen_conducta' => [
                'con_datos' => count(array_filter($estadisticasConducta, fn($s) => ($s['total_estudiantes_con_conducta'] ?? 0) > 0)),
            ],
            'total_criterios' => array_sum($criteriosPorBimestre),
        ];
    }
    // Función auxiliar para generar colores por bimestre (se mantiene igual)
    protected function getColorForBimestre($bimestre)
    {
        $colores = [
            1 => '#FF6384', // Bimestre 1 - Rojo
            2 => '#36A2EB', // Bimestre 2 - Azul
            3 => '#FFCE56', // Bimestre 3 - Amarillo
            4 => '#4BC0C0', // Bimestre 4 - Verde
        ];

        return $colores[$bimestre] ?? '#999999';
    }
    protected function getColorForEstudiante($index)
    {
        $colores = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
            '#9966FF', '#FF9F40', '#8AC926', '#1982C4',
            '#6A4C93', '#F15BB5', '#00BBF9', '#FB5607',
            '#8338EC', '#3A86FF', '#FF006E', '#FB5607',
            '#FFBE0B', '#3A86FF', '#8338EC', '#FF006E'
        ];

        return $colores[$index % count($colores)] ?? '#999999';
    }
    protected function auxiliar(Request $request)
    {
        if (!Auth::user()->hasRole('auxiliar')) {
            abort(403, 'Acceso denegado');
        }

        // Obtener parámetros de filtro
        $periodos = Periodo::where('estado', 1)->orderBy('anio', 'desc')->get();
        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        if (!$periodoSeleccionado) {
            return back()->with('error', 'No hay períodos activos disponibles.');
        }

        $bimestreFiltro = $request->input('bimestre');
        $mesFiltro = $request->input('mes');

        $usuarios = User::with('roles')->get();
        $anio = date('Y');

        // Obtener grados con estudiantes matriculados en el periodo seleccionado
        $grados = Grado::whereHas('matriculas', function ($query) use ($periodoSeleccionado) {
            $query->where('periodo_id', $periodoSeleccionado->id);
        })
        ->withCount(['matriculas' => function ($query) use ($periodoSeleccionado) {
            $query->where('periodo_id', $periodoSeleccionado->id);
        }])
        ->orderBy('grado')
        ->orderBy('seccion')
        ->get();

        $tiposAsistencia = \App\Models\Asistencia\Tipoasistencia::all();

        $datosAsistencias = [];
        $estadisticasGenerales = [
            'totalEstudiantes' => 0,
            'totalAsistencias' => 0,
            'porcentajeAsistencia' => 0,
            'filtros_aplicados' => $this->getTextoFiltros($bimestreFiltro, $mesFiltro)
        ];

        foreach ($grados as $grado) {
            $estudianteIds = Matricula::where('periodo_id', $periodoSeleccionado->id)
                ->where('grado_id', $grado->id)
                ->pluck('estudiante_id')
                ->toArray();

            if (empty($estudianteIds)) {
                continue;
            }

            // Obtener estudiantes con asistencias filtradas
            $estudiantes = Estudiante::with([
                'user',
                'asistencias' => function($query) use ($periodoSeleccionado, $bimestreFiltro, $mesFiltro) {
                    $query->where('periodo_id', $periodoSeleccionado->id)
                        ->with('tipoasistencia');

                    // Aplicar filtro de bimestre
                    if ($bimestreFiltro && $bimestreFiltro !== 'anual') {
                        $query->where('bimestre', $bimestreFiltro);
                    }

                    // Aplicar filtro de mes
                    if ($mesFiltro && is_numeric($mesFiltro)) {
                        $query->whereMonth('fecha', $mesFiltro);
                    }
                }
            ])
            ->whereIn('id', $estudianteIds)
            ->where('estado', 1)
            ->get()
            ->sortBy(function($estudiante) {
                return $estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno;
            });

            $datosEstudiantes = [];
            $estadisticasGrado = [
                'totalEstudiantes' => $estudiantes->count(),
                'totalAsistencias' => 0,
                'porcentajesTipo' => [],
                'filtros_aplicados' => $this->getTextoFiltros($bimestreFiltro, $mesFiltro)
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
                    'nombre_completo' => $estudiante->user->apellido_paterno . ' ' .
                                    $estudiante->user->apellido_materno . ', ' .
                                    $estudiante->user->nombre,
                    'total_asistencias' => $totalAsistencias,
                    'porcentajes_tipo' => $porcentajesPorTipo,
                    'conteo_tipos' => $conteoTipos,
                    'estudiante_id' => $estudiante->id
                ];
            }

            // Calcular porcentajes generales del grado
            foreach ($tiposAsistencia as $tipo) {
                $totalTipo = 0;

                foreach ($datosEstudiantes as $estudianteData) {
                    $totalTipo += $estudianteData['conteo_tipos'][$tipo->nombre] ?? 0;
                }

                $porcentajeGrado = $estadisticasGrado['totalAsistencias'] > 0
                    ? round(($totalTipo / $estadisticasGrado['totalAsistencias']) * 100, 2)
                    : 0;
                $estadisticasGrado['porcentajesTipo'][$tipo->nombre] = $porcentajeGrado;
            }

            $datosAsistencias[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'estudiantes' => $datosEstudiantes,
                'estadisticas' => $estadisticasGrado,
                'tipos_asistencia' => $tiposAsistencia->pluck('nombre')->toArray(),
                'grado_id' => $grado->id
            ];

            $estadisticasGenerales['totalEstudiantes'] += $estadisticasGrado['totalEstudiantes'];
            $estadisticasGenerales['totalAsistencias'] += $estadisticasGrado['totalAsistencias'];
        }

        // Calcular porcentaje general de asistencia
        if ($estadisticasGenerales['totalEstudiantes'] > 0 && $estadisticasGenerales['totalAsistencias'] > 0) {
            $totalPuntualidad = 0;
            foreach ($datosAsistencias as $gradoData) {
                if (isset($gradoData['estadisticas']['porcentajesTipo']['PUNTUALIDAD'])) {
                    $totalPuntualidad += $gradoData['estadisticas']['porcentajesTipo']['PUNTUALIDAD'];
                }
            }
            $estadisticasGenerales['porcentajeAsistencia'] = count($datosAsistencias) > 0
                ? round($totalPuntualidad / count($datosAsistencias), 2)
                : 0;
        }

        $coloresTipos = [
            'PUNTUALIDAD' => ['hex' => '#28a745', 'class' => 'success'],
            'FALTA' => ['hex' => '#dc3545', 'class' => 'danger'],
            'FALTA JUSTIFICADA' => ['hex' => '#fd7e14', 'class' => 'warning'],
            'TARDANZA' => ['hex' => '#ffc107', 'class' => 'info'],
            'TARDANZA JUSTIFICADA' => ['hex' => '#17a2b8', 'class' => 'primary'],
        ];

        return view('rol.auxiliar.dashboard', compact(
            'periodos',
            'periodoSeleccionado',
            'usuarios',
            'datosAsistencias',
            'tiposAsistencia',
            'estadisticasGenerales',
            'coloresTipos',
            'bimestreFiltro',
            'mesFiltro'
        ));
    }

    //Obtener texto descriptivo de los filtros aplicados(Auxiliar)
    private function getTextoFiltros($bimestreFiltro, $mesFiltro)
    {
        $texto = '';
        $filtros = [];

        if ($bimestreFiltro && $bimestreFiltro !== 'anual') {
            $filtros[] = "{$bimestreFiltro}° Bimestre";
        } else {
            $filtros[] = "Anual";
        }

        if ($mesFiltro && is_numeric($mesFiltro)) {
            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            if (isset($meses[$mesFiltro])) {
                $filtros[] = "Mes: " . $meses[$mesFiltro];
            }
        }

        if (!empty($filtros)) {
            $texto = implode(' | ', $filtros);
        }

        return $texto;
    }
    protected function apoderado(Request $request)
    {
        if (!Auth::user()->hasRole('apoderado')) {
            abort(403, 'Acceso denegado');
        }

        $apoderado = Apoderado::where('user_id', Auth::id())->first();

        if (!$apoderado) {
            abort(403, 'No se encontró el perfil de apoderado');
        }

        $estudiantes = Estudiante::with(['user', 'grado'])
            ->where('apoderado_id', $apoderado->id)
            ->where('estado', 1)
            ->get();

        if ($estudiantes->isEmpty()) {
            return view('rol.apoderado.dashboard')->with('info', 'No tiene estudiantes asignados.');
        }

        $estudianteIds = $estudiantes->pluck('id')->toArray();

        $periodos = Periodo::whereHas('matriculas', function($query) use ($estudianteIds) {
                $query->whereIn('estudiante_id', $estudianteIds)
                    ->where('estado', 1);
            })
            ->where('estado', 1)
            ->orderBy('anio', 'desc')
            ->get();

        if ($periodos->isEmpty()) {
            return view('rol.apoderado.dashboard')->with('error', 'No hay períodos con matrículas para sus estudiantes.');
        }

        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        if (!$periodoSeleccionado) {
            return back()->with('error', 'No hay períodos activos disponibles.');
        }

        $bimestreFiltro = $request->input('bimestre', 'anual');
        $usuarios = User::with('roles')->get();

        // Obtener bimestres regulares del periodo
        $bimestresRegulares = Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
            ->where('tipo_bimestre', 'A')
            ->orderBy('bimestre')
            ->get();

        // Obtener periodo_bimestre seleccionado si no es anual
        $periodoBimestreSeleccionado = null;
        if ($bimestreFiltro !== 'anual') {
            $periodoBimestreSeleccionado = $bimestresRegulares->firstWhere('sigla', $bimestreFiltro);
        }

        $datosEstudiantes = [];

        foreach ($estudiantes as $estudiante) {
            $matricula = Matricula::where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('estado', 1)
                ->first();

            if (!$matricula) {
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
                    'mensaje' => 'El estudiante no está matriculado en el período seleccionado.'
                ];
                continue;
            }

            $gradoMatricula = $matricula->grado;

            $cursos = Cursogradosecnivanio::where('grado_id', $matricula->grado_id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->with(['materia', 'grado'])
                ->get();

            // =================== NOTAS ACADÉMICAS ===================
            $progresoCursos = [];

            foreach ($cursos as $curso) {
                $competencias = Materiacompetencia::where('materia_id', $curso->materia_id)
                    ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%')
                    ->get();

                if ($competencias->isEmpty()) {
                    continue;
                }

                $competenciasData = [];
                $sumaNotasCurso = 0;
                $totalNotasCurso = 0;

                foreach ($competencias as $competencia) {
                    $criteriosQuery = Materiacriterio::where('materia_competencia_id', $competencia->id)
                        ->where('grado_id', $matricula->grado_id);

                    if ($bimestreFiltro !== 'anual' && $periodoBimestreSeleccionado) {
                        $criteriosQuery->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
                    }

                    $criterios = $criteriosQuery->get();

                    if ($criterios->isEmpty()) {
                        continue;
                    }

                    $sumaNotas = 0;
                    $totalNotas = 0;

                    foreach ($criterios as $criterio) {
                        $nota = Nota::where('materia_criterio_id', $criterio->id)
                            ->where('estudiante_id', $estudiante->id)
                            ->where('periodo_id', $periodoSeleccionado->id)
                            ->where('publico', '!=', '0')
                            ->first();

                        $notaValor = $nota ? $nota->nota : null;

                        if ($notaValor !== null) {
                            $sumaNotas += $notaValor;
                            $totalNotas++;
                            $sumaNotasCurso += $notaValor;
                            $totalNotasCurso++;
                        }
                    }

                    $promedioCompetencia = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 1) : null;

                    $competenciasData[] = [
                        'nombre' => $competencia->nombre,
                        'promedio' => $promedioCompetencia
                    ];
                }

                if (empty($competenciasData)) {
                    continue;
                }

                $promedioGeneralCurso = $totalNotasCurso > 0 ? round($sumaNotasCurso / $totalNotasCurso, 1) : null;

                // Obtener promedios por bimestre para el gráfico (solo en modo anual)
                $promediosPorBimestre = [];
                if ($bimestreFiltro === 'anual') {
                    foreach ($bimestresRegulares as $bim) {
                        $criteriosIds = Materiacriterio::whereHas('materiaCompetencia', function($q) use ($curso) {
                                $q->where('materia_id', $curso->materia_id)
                                    ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%');
                            })
                            ->where('grado_id', $matricula->grado_id)
                            ->where('periodo_bimestre_id', $bim->id)
                            ->pluck('id')
                            ->toArray();

                        if (!empty($criteriosIds)) {
                            $notasBimestre = Nota::whereIn('materia_criterio_id', $criteriosIds)
                                ->where('estudiante_id', $estudiante->id)
                                ->where('periodo_id', $periodoSeleccionado->id)
                                ->where('publico', '!=', '0')
                                ->get();

                            $sumaBimestre = $notasBimestre->sum('nota');
                            $totalBimestre = $notasBimestre->count();
                            $promediosPorBimestre[$bim->sigla] = $totalBimestre > 0 ? round($sumaBimestre / $totalBimestre, 1) : null;
                        } else {
                            $promediosPorBimestre[$bim->sigla] = null;
                        }
                    }
                } else {
                    // Modo bimestral: solo mostrar el bimestre seleccionado
                    foreach ($bimestresRegulares as $bim) {
                        $promediosPorBimestre[$bim->sigla] = ($bim->sigla == $bimestreFiltro) ? $promedioGeneralCurso : null;
                    }
                }

                $progresoCursos[] = [
                    'curso' => $curso->materia->nombre ?? 'Sin nombre',
                    'competencias' => $competenciasData,
                    'promedios' => $promediosPorBimestre,
                    'promedio_general' => $promedioGeneralCurso,
                    'estado' => $promedioGeneralCurso !== null
                        ? ($promedioGeneralCurso > 2 ? 'Aprobado' : 'Reprobado')
                        : 'Sin datos'
                ];
            }

            // =================== NOTAS DE CONDUCTA ===================
            $progresoConducta = [];

            $conductasDB = Conducta::whereHas('periodosBimestres', function($query) use ($periodoSeleccionado) {
                $query->where('periodo_id', $periodoSeleccionado->id)
                    ->whereNull('conducta_periodo_bimestres.deleted_at');
            })->distinct()->get();

            if ($conductasDB->isNotEmpty() && $bimestresRegulares->isNotEmpty()) {
                $periodoBimestreConducta = ($bimestreFiltro !== 'anual')
                    ? $bimestresRegulares->firstWhere('sigla', $bimestreFiltro)
                    : null;

                $queryConducta = Conductaperiodobimestrenota::with([
                        'conductaPeriodoBimestre.conducta',
                        'periodoBimestre',
                        'curso_grado_sec_niv_anio.materia'
                    ])
                    ->where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->where('publico', '!=', '0')
                    ->whereHas('conductaPeriodoBimestre', function($q) {
                        $q->whereNull('deleted_at');
                    });

                if ($bimestreFiltro !== 'anual' && $periodoBimestreConducta) {
                    $queryConducta->where('periodo_bimestre_id', $periodoBimestreConducta->id);
                }

                $notasConducta = $queryConducta->get();

                if ($notasConducta->isNotEmpty()) {
                    $notasMap = [];
                    foreach ($notasConducta as $nota) {
                        if (!$nota->conductaPeriodoBimestre || $nota->conductaPeriodoBimestre->trashed()) {
                            continue;
                        }
                        $key = $nota->conductaPeriodoBimestre->conducta_id . '|' . $nota->curso_grado_sec_niv_anio_id;
                        $notasMap[$key] = $nota->nota;
                    }

                    foreach ($conductasDB as $conducta) {
                        $sumaNotas = 0;
                        $totalNotas = 0;
                        $promediosPorBimestre = [];

                        if ($bimestreFiltro === 'anual') {
                            foreach ($bimestresRegulares as $bim) {
                                $notasBimestre = [];
                                foreach ($cursos as $curso) {
                                    $key = $conducta->id . '|' . $curso->id;
                                    if (isset($notasMap[$key])) {
                                        $notasBimestre[] = $notasMap[$key];
                                    }
                                }
                                $promedioBimestre = !empty($notasBimestre)
                                    ? round(array_sum($notasBimestre) / count($notasBimestre), 1)
                                    : null;

                                $promediosPorBimestre[$bim->sigla] = $promedioBimestre;

                                if ($promedioBimestre !== null) {
                                    $sumaNotas += $promedioBimestre;
                                    $totalNotas++;
                                }
                            }
                        } else {
                            // Modo bimestral específico
                            foreach ($cursos as $curso) {
                                $key = $conducta->id . '|' . $curso->id;
                                $notaValor = $notasMap[$key] ?? null;
                                if ($notaValor !== null) {
                                    $sumaNotas += $notaValor;
                                    $totalNotas++;
                                }
                            }

                            $promedioBimestre = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 1) : null;
                            foreach ($bimestresRegulares as $bim) {
                                $promediosPorBimestre[$bim->sigla] = ($bim->sigla == $bimestreFiltro) ? $promedioBimestre : null;
                            }
                        }

                        $promedioGeneral = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 1) : null;

                        $progresoConducta[] = [
                            'nombre' => $conducta->nombre,
                            'promedios' => $promediosPorBimestre,
                            'promedio_general' => $promedioGeneral,
                            'estado' => $promedioGeneral !== null
                                ? ($promedioGeneral > 2 ? 'Adecuado' : 'Inadecuado')
                                : 'Sin datos'
                        ];
                    }
                }
            }

            $gradoNombre = $gradoMatricula ? $gradoMatricula->grado . '° ' . $gradoMatricula->seccion : 'Sin grado';

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
                'mensaje' => count($progresoCursos) == 0 && count($progresoConducta) == 0
                    ? 'No hay notas registradas para este período'
                    : null
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
            'total_estudiantes' => count($estudiantes)
        ];

        return view('rol.apoderado.dashboard', compact(
            'periodos',
            'periodoSeleccionado',
            'usuarios',
            'datosEstudiantes',
            'infoApoderado',
            'bimestreFiltro',
            'bimestresRegulares'
        ));
    }
    protected function estudiante(Request $request)
    {
        if (!Auth::user()->hasRole('estudiante')) {
            abort(403, 'Acceso denegado');
        }

        $estudiante = \App\Models\Estudiante::where('user_id', Auth::id())->first();

        if (!$estudiante) {
            abort(403, 'No se encontró el perfil de estudiante');
        }

        $estudianteId = $estudiante->id;

        $periodos = Periodo::whereHas('matriculas', function($query) use ($estudianteId) {
                $query->where('estudiante_id', $estudianteId)
                    ->where('estado', 1);
            })
            ->where('estado', 1)
            ->orderBy('anio', 'desc')
            ->get();

        if ($periodos->isEmpty()) {
            return view('rol.estudiante.dashboard')->with('error', 'No hay períodos con matrículas.');
        }

        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        if (!$periodoSeleccionado) {
            return back()->with('error', 'No hay períodos disponibles.');
        }

        $bimestreFiltro = $request->input('bimestre', 'anual');
        $usuarios = User::with('roles')->get();

        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->where('estado', 1)
            ->first();

        if (!$matricula) {
            return view('rol.estudiante.dashboard', compact(
                'periodos',
                'periodoSeleccionado',
                'usuarios'
            ))->with('error', 'No estás matriculado en el período seleccionado.');
        }

        // Obtener periodo_bimestre seleccionado si no es anual
        $periodoBimestreSeleccionado = null;
        if ($bimestreFiltro !== 'anual') {
            $periodoBimestreSeleccionado = Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
                ->where('sigla', $bimestreFiltro)
                ->where('tipo_bimestre', 'A')
                ->first();
        }

        // Obtener cursos del grado (materias)
        $cursos = Cursogradosecnivanio::where('grado_id', $matricula->grado_id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->with(['materia', 'grado'])
            ->get();

        $progresoCursos = [];

        foreach ($cursos as $curso) {
            // Obtener competencias de la materia (excluyendo transversales)
            $competencias = Materiacompetencia::where('materia_id', $curso->materia_id)
                ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%')
                ->get();

            if ($competencias->isEmpty()) {
                continue;
            }

            // Para cada competencia, obtener sus criterios y notas
            $competenciasData = [];

            foreach ($competencias as $competencia) {
                $criteriosQuery = Materiacriterio::where('materia_competencia_id', $competencia->id)
                    ->where('grado_id', $matricula->grado_id);

                // Filtrar por periodo_bimestre si no es anual
                if ($bimestreFiltro !== 'anual' && $periodoBimestreSeleccionado) {
                    $criteriosQuery->where('periodo_bimestre_id', $periodoBimestreSeleccionado->id);
                }

                $criterios = $criteriosQuery->get();

                if ($criterios->isEmpty()) {
                    continue;
                }

                // Obtener notas de los criterios
                $notasCriterios = [];
                $sumaNotas = 0;
                $totalNotas = 0;

                foreach ($criterios as $criterio) {
                    $nota = Nota::where('materia_criterio_id', $criterio->id)
                        ->where('estudiante_id', $estudiante->id)
                        ->where('periodo_id', $periodoSeleccionado->id)
                        ->where('publico', '!=', '0')
                        ->first();

                    $notaValor = $nota ? $nota->nota : null;
                    $notasCriterios[] = [
                        'materia_criterio_id' => $criterio->id,
                        'criterio_nombre' => $criterio->nombre,
                        'nota' => $notaValor
                    ];

                    if ($notaValor !== null) {
                        $sumaNotas += $notaValor;
                        $totalNotas++;
                    }
                }

                $promedioCompetencia = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 1) : null;

                $competenciasData[] = [
                    'competencia_id' => $competencia->id,
                    'competencia_nombre' => $competencia->nombre,
                    'criterios' => $notasCriterios,
                    'promedio' => $promedioCompetencia
                ];
            }

            if (empty($competenciasData)) {
                continue;
            }

            // Calcular promedio general del curso (promedio de competencias)
            $promediosCompetencias = array_filter(array_column($competenciasData, 'promedio'));
            $promedioGeneralCurso = !empty($promediosCompetencias)
                ? round(array_sum($promediosCompetencias) / count($promediosCompetencias), 1)
                : null;

            // Para el gráfico, necesitamos los promedios por bimestre
            // Obtener notas agrupadas por periodo_bimestre
            $promediosPorBimestre = [];

            if ($bimestreFiltro === 'anual') {
                // Obtener todos los bimestres del periodo
                $bimestres = Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
                    ->where('tipo_bimestre', 'A')
                    ->orderBy('bimestre')
                    ->get();

                foreach ($bimestres as $bim) {
                    // Obtener todas las notas de este curso para este bimestre
                    $criteriosIds = Materiacriterio::whereHas('materiaCompetencia', function($q) use ($curso) {
                            $q->where('materia_id', $curso->materia_id)
                                ->whereNot('nombre', 'LIKE', '%TRANSVERSAL%');
                        })
                        ->where('grado_id', $matricula->grado_id)
                        ->where('periodo_bimestre_id', $bim->id)
                        ->pluck('id')
                        ->toArray();

                    if (!empty($criteriosIds)) {
                        $notasBimestre = Nota::whereIn('materia_criterio_id', $criteriosIds)
                            ->where('estudiante_id', $estudiante->id)
                            ->where('periodo_id', $periodoSeleccionado->id)
                            ->where('publico', '!=', '0')
                            ->get();

                        $sumaBimestre = $notasBimestre->sum('nota');
                        $totalBimestre = $notasBimestre->count();
                        $promediosPorBimestre[$bim->bimestre] = $totalBimestre > 0
                            ? round($sumaBimestre / $totalBimestre, 1)
                            : null;
                    } else {
                        $promediosPorBimestre[$bim->bimestre] = null;
                    }
                }
            } else {
                // Modo bimestral: solo un bimestre
                $promediosPorBimestre = [
                    1 => $bimestreFiltro == 'B1' ? $promedioGeneralCurso : null,
                    2 => $bimestreFiltro == 'B2' ? $promedioGeneralCurso : null,
                    3 => $bimestreFiltro == 'B3' ? $promedioGeneralCurso : null,
                    4 => $bimestreFiltro == 'B4' ? $promedioGeneralCurso : null,
                ];
            }

            $progresoCursos[] = [
                'curso' => $curso->materia->nombre ?? 'Sin nombre',
                'competencias' => $competenciasData,
                'promedios' => $promediosPorBimestre,
                'promedio_general' => $promedioGeneralCurso,
                'estado' => $promedioGeneralCurso !== null
                    ? ($promedioGeneralCurso > 2 ? 'Aprobado' : 'Reprobado')
                    : 'Sin datos'
            ];
        }

        // Obtener conductas (igual que en libreta)
        $progresoConducta = [];

        $conductasDB = Conducta::whereHas('periodosBimestres', function($query) use ($periodoSeleccionado) {
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
                    'curso_grado_sec_niv_anio.materia'
                ])
                ->where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('publico', '!=', '0')
                ->whereHas('conductaPeriodoBimestre', function($q) {
                    $q->whereNull('deleted_at');
                });

            if ($bimestreFiltro !== 'anual' && $periodoBimestreConducta) {
                $queryConducta->where('periodo_bimestre_id', $periodoBimestreConducta->id);
            }

            $notasConducta = $queryConducta->get();

            if ($notasConducta->isNotEmpty()) {
                $notasMap = [];
                foreach ($notasConducta as $nota) {
                    if (!$nota->conductaPeriodoBimestre || $nota->conductaPeriodoBimestre->trashed()) {
                        continue;
                    }
                    $key = $nota->conductaPeriodoBimestre->conducta_id . '|' . $nota->curso_grado_sec_niv_anio_id;
                    $notasMap[$key] = $nota->nota;
                }

                foreach ($conductasDB as $conducta) {
                    $notasConductaCurso = [];
                    $sumaNotas = 0;
                    $totalNotas = 0;

                    foreach ($cursos as $curso) {
                        $key = $conducta->id . '|' . $curso->id;
                        $notaValor = $notasMap[$key] ?? null;
                        $notasConductaCurso[] = [
                            'curso' => $curso->materia->nombre ?? 'Sin nombre',
                            'nota' => $notaValor
                        ];
                        if ($notaValor !== null) {
                            $sumaNotas += $notaValor;
                            $totalNotas++;
                        }
                    }

                    $promedioGeneral = $totalNotas > 0 ? round($sumaNotas / $totalNotas, 1) : null;

                    $progresoConducta[] = [
                        'nombre' => $conducta->nombre,
                        'cursos' => $notasConductaCurso,
                        'promedio_general' => $promedioGeneral,
                        'estado' => $promedioGeneral !== null
                            ? ($promedioGeneral > 2 ? 'Adecuado' : 'Inadecuado')
                            : 'Sin datos'
                    ];
                }
            }
        }

        $grado = $matricula->grado;
        $infoEstudiante = [
            'estudiante_id' => $estudiante->id,
            'nombre_completo' => trim(sprintf(
                '%s %s, %s',
                $estudiante->user->apellido_paterno ?? '',
                $estudiante->user->apellido_materno ?? '',
                $estudiante->user->nombre ?? ''
            )),
            'grado' => $grado ? $grado->grado . '° ' . $grado->seccion . ' - ' . $grado->nivel : 'Sin grado',
            'grado_id' => $matricula->grado_id,
            'progreso_cursos' => $progresoCursos,
            'progreso_conducta' => $progresoConducta,
            'total_cursos' => count($progresoCursos),
            'total_conducta' => count($progresoConducta),
            'mensaje' => count($progresoCursos) == 0 && count($progresoConducta) == 0 ?
                'No hay notas registradas para este período' : null
        ];

        $labelsBimestres = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4'];

        return view('rol.estudiante.dashboard', compact(
            'periodos',
            'periodoSeleccionado',
            'usuarios',
            'infoEstudiante',
            'labelsBimestres',
            'bimestreFiltro'
        ));
    }
    protected function NuevoRol()
    {
        $usuarios = User::with('roles')->get();

        return view('rol.nuevorol.dashboard', compact('usuarios'));
    }
}
