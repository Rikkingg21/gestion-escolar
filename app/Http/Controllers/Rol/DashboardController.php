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
use App\Models\Conductanota;
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
            return $this->docente();
        } elseif ($user->hasRole('auxiliar')) {
            return $this->auxiliar();
        } elseif ($user->hasRole('apoderado')) {
            return $this->apoderado();
        } elseif ($user->hasRole('estudiante')) {
            return $this->estudiante();
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

    protected function docente()
    {
        if (!Auth::user()->hasRole('docente')) {
            abort(403, 'Acceso denegado');
        }

        $usuarios = User::with('roles')->get();
        $anio = date('Y');

        // Obtener el docente autenticado
        $docente = \App\Models\Docente::where('user_id', Auth::id())->first();

        if (!$docente) {
            abort(403, 'No se encontró el perfil de docente');
        }

        // Obtener los cursos asignados a este docente para el año actual
        $materias = \App\Models\Maya\Cursogradosecnivanio::with([
            'grado',
            'materia'
        ])->where('docente_designado_id', $docente->id)
        ->where('anio', $anio)
        ->get();

        // Si no hay materias, retornar vista con mensaje
        if ($materias->isEmpty()) {
            $usuarios = User::with('roles')->get();
            return view('rol.nuevorol.dashboard', compact('usuarios'));
        }

        // Agrupar materias por grado
        $materiaPorGrado = $materias->groupBy('grado_id');

        $datosGraficos = [];
        $estadisticasDocente = [];

        foreach ($materiaPorGrado as $gradoId => $materiasDelGrado) {
            $grado = $materiasDelGrado->first()->grado;

            // Obtener estudiantes de este grado, ordenados alfabéticamente por apellidos
            $estudiantes = \App\Models\Estudiante::with(['user'])
                ->where('grado_id', $gradoId)
                ->where('estado', 1) // Solo estudiantes activos
                ->get()
                ->sortBy(function($estudiante) {
                    return $estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno;
                });

            // Si no hay estudiantes, continuar con el siguiente grado
            if ($estudiantes->isEmpty()) {
                continue;
            }

            // Preparar datos para el gráfico - Un gráfico por estudiante
            $graficosEstudiantes = [];

            // Usar un índice manual para los colores
            $colorIndex = 0;

            foreach ($estudiantes as $estudiante) {
                $nombreCompleto = $estudiante->user->apellido_paterno . ' ' .
                                $estudiante->user->apellido_materno . ', ' .
                                $estudiante->user->nombre;

                // Datos para los 4 bimestres
                $datosBimestres = [];
                $labelsBimestres = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4'];

                for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                    // Obtener promedio del estudiante para este bimestre
                    $notasBimestre = \App\Models\Nota::where('estudiante_id', $estudiante->id)
                        ->where('bimestre', $bimestre)
                        ->whereIn('publico', ['0', '1', '2', '3'])
                        ->whereHas('criterio', function($query) use ($materiasDelGrado) {
                            $query->whereIn('materia_id', $materiasDelGrado->pluck('materia_id'));
                        })
                        ->pluck('nota');

                    $promedio = $notasBimestre->count() > 0 ? round($notasBimestre->avg(), 2) : null;
                    $datosBimestres[] = $promedio;
                }

                // Solo incluir estudiantes que tengan al menos un dato
                if (!empty(array_filter($datosBimestres))) {
                    $graficosEstudiantes[] = [
                        'estudiante' => $nombreCompleto,
                        'labels' => $labelsBimestres,
                        'datos' => $datosBimestres,
                        'color' => $this->getColorForEstudiante($colorIndex)
                    ];
                }

                $colorIndex++;
            }

            // Estadísticas para este grado
            $estadisticasGrado = $this->calcularEstadisticasGrado($estudiantes, $materiasDelGrado);

            $datosGraficos[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'estudiantes' => $graficosEstudiantes,
                'materias' => $materiasDelGrado->pluck('materia.nombre')->toArray(),
                'totalEstudiantes' => count($graficosEstudiantes)
            ];

            $estadisticasDocente[$grado->getNombreCompletoAttribute()] = $estadisticasGrado;
        }

        // Estadísticas generales del docente
        $estadisticasGenerales = $this->calcularEstadisticasGenerales($materias, $anio);

        return view('rol.docente.dashboard', compact(
            'usuarios',
            'datosGraficos',
            'estadisticasDocente',
            'estadisticasGenerales',
            'docente'
        ));
    }

    /**
     * Calcular estadísticas por grado
     */
    private function calcularEstadisticasGrado($estudiantes, $cursosDelGrado)
    {
        $totalEstudiantes = $estudiantes->count();
        $estadisticasBimestres = [];

        for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
            $totalNotas = 0;
            $notasRegistradas = 0;

            foreach ($estudiantes as $estudiante) {
                $notasCount = \App\Models\Nota::where('estudiante_id', $estudiante->id)
                    ->where('bimestre', $bimestre)
                    ->whereIn('publico', ['0', '1', '2', '3'])
                    ->whereHas('criterio', function($query) use ($cursosDelGrado) {
                        $query->whereIn('materia_id', $cursosDelGrado->pluck('materia_id'));
                    })
                    ->count();

                $totalNotas += $cursosDelGrado->count(); // Total esperado de notas
                $notasRegistradas += $notasCount;
            }

            $porcentajeCompletado = $totalNotas > 0 ? round(($notasRegistradas / $totalNotas) * 100, 1) : 0;

            $estadisticasBimestres[$bimestre] = [
                'completado' => $porcentajeCompletado,
                'notasRegistradas' => $notasRegistradas,
                'totalEsperado' => $totalNotas
            ];
        }

        return [
            'totalEstudiantes' => $totalEstudiantes,
            'totalCursos' => $cursosDelGrado->count(),
            'bimestres' => $estadisticasBimestres
        ];
    }

    /**
     * Calcular estadísticas generales del docente
     */
    private function calcularEstadisticasGenerales($cursos, $anio)
    {
        $totalCursos = $cursos->count();
        $totalGrados = $cursos->groupBy('grado_id')->count();

        // Calcular progreso general de notas
        $notasTotales = 0;
        $notasRegistradas = 0;

        foreach ($cursos as $curso) {
            for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                $estudiantesCount = \App\Models\Estudiante::where('grado_id', $curso->grado_id)
                    ->where('estado', 1)
                    ->count();

                $criteriosCount = \App\Models\Materia\Materiacriterio::where('materia_id', $curso->materia_id)
                    ->where('grado_id', $curso->grado_id)
                    ->where('anio', $anio)
                    ->where('bimestre', $bimestre)
                    ->count();

                $totalEsperado = $estudiantesCount * $criteriosCount;
                $notasTotales += $totalEsperado;

                $notasActuales = \App\Models\Nota::where('bimestre', $bimestre)
                    ->whereIn('publico', ['0', '1', '2', '3'])
                    ->whereHas('criterio', function($query) use ($curso, $bimestre, $anio) {
                        $query->where('materia_id', $curso->materia_id)
                            ->where('grado_id', $curso->grado_id)
                            ->where('anio', $anio)
                            ->where('bimestre', $bimestre);
                    })
                    ->whereHas('estudiante', function($query) use ($curso) {
                        $query->where('grado_id', $curso->grado_id)
                            ->where('estado', 1);
                    })
                    ->count();

                $notasRegistradas += $notasActuales;
            }
        }

        $progresoGeneral = $notasTotales > 0 ? round(($notasRegistradas / $notasTotales) * 100, 1) : 0;

        return [
            'totalCursos' => $totalCursos,
            'totalGrados' => $totalGrados,
            'progresoGeneral' => $progresoGeneral,
            'notasRegistradas' => $notasRegistradas,
            'notasTotales' => $notasTotales
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
    protected function auxiliar()
    {
        if (!Auth::user()->hasRole('auxiliar')) {
            abort(403, 'Acceso denegado');
        }

        $usuarios = User::with('roles')->get();
        $anio = date('Y');

        // Obtener todos los grados activos
        $grados = \App\Models\Grado::where('estado', 1)->get();

        // Obtener todos los tipos de asistencia
        $tiposAsistencia = \App\Models\Asistencia\Tipoasistencia::all();

        $datosAsistencias = [];
        $estadisticasGenerales = [
            'totalEstudiantes' => 0,
            'totalAsistencias' => 0,
            'porcentajeAsistencia' => 0
        ];

        foreach ($grados as $grado) {
            // Obtener estudiantes activos del grado, ordenados por apellidos
            $estudiantes = \App\Models\Estudiante::with(['user', 'asistencias' => function($query) {
                $query->with('tipoasistencia');
            }])
            ->where('grado_id', $grado->id)
            ->where('estado', 1)
            ->get()
            ->sortBy(function($estudiante) {
                return $estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno;
            });

            if ($estudiantes->isEmpty()) {
                continue;
            }

            $datosEstudiantes = [];
            $estadisticasGrado = [
                'totalEstudiantes' => $estudiantes->count(),
                'totalAsistencias' => 0,
                'porcentajesTipo' => []
            ];

            foreach ($tiposAsistencia as $tipo) {
                $estadisticasGrado['porcentajesTipo'][$tipo->nombre] = 0;
            }

            foreach ($estudiantes as $estudiante) {
                // Obtener todas las asistencias del estudiante
                $totalAsistencias = $estudiante->asistencias->count();
                $estadisticasGrado['totalAsistencias'] += $totalAsistencias;

                $porcentajesPorTipo = [];
                $conteoTipos = [];

                foreach ($tiposAsistencia as $tipo) {
                    $countTipo = $estudiante->asistencias->where('tipo_asistencia_id', $tipo->id)->count();
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
                    'conteo_tipos' => $conteoTipos
                ];
            }

            // Calcular porcentajes generales del grado
            foreach ($tiposAsistencia as $tipo) {
                $totalTipo = array_sum(array_column($datosEstudiantes, 'conteo_tipos.' . $tipo->nombre));
                $porcentajeGrado = $estadisticasGrado['totalAsistencias'] > 0
                    ? round(($totalTipo / $estadisticasGrado['totalAsistencias']) * 100, 2)
                    : 0;
                $estadisticasGrado['porcentajesTipo'][$tipo->nombre] = $porcentajeGrado;
            }

            $datosAsistencias[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'estudiantes' => $datosEstudiantes,
                'estadisticas' => $estadisticasGrado,
                'tipos_asistencia' => $tiposAsistencia->pluck('nombre')->toArray()
            ];

            // Actualizar estadísticas generales
            $estadisticasGenerales['totalEstudiantes'] += $estadisticasGrado['totalEstudiantes'];
            $estadisticasGenerales['totalAsistencias'] += $estadisticasGrado['totalAsistencias'];
        }


        $coloresTipos = [
            'PUNTUALIDAD' => ['hex' => '#28a745', 'class' => 'success'],
            'FALTA' => ['hex' => '#dc3545', 'class' => 'danger'],
            'FALTA JUSTIFICADA' => ['hex' => '#fd7e14', 'class' => 'warning'],
            'TARDANZA' => ['hex' => '#ffc107', 'class' => 'info'],
            'TARDANZA JUSTIFICADA' => ['hex' => '#17a2b8', 'class' => 'primary'],
        ];

        return view('rol.auxiliar.dashboard', compact(
            'usuarios',
            'datosAsistencias',
            'tiposAsistencia',
            'estadisticasGenerales',
            'coloresTipos'
        ));

    }
    protected function getColorHexForTipo($tipoNombre)
    {
        $colores = [
            'PUNTUALIDAD' => '#28a745',
            'FALTA' => '#dc3545',
            'FALTA JUSTIFICADA' => '#fd7e14',
            'TARDANZA' => '#ffc107',
            'TARDANZA JUSTIFICADA' => '#17a2b8',
        ];

        return $colores[$tipoNombre] ?? '#6c757d';
    }


    /**
     * Obtener clase de color Bootstrap para tipo de asistencia
     */
    protected function getColorClassForTipo($tipoNombre)
    {
        $clases = [
            'PUNTUALIDAD' => 'success',
            'FALTA' => 'danger',
            'FALTA JUSTIFICADA' => 'warning',
            'TARDANZA' => 'info',
            'TARDANZA JUSTIFICADA' => 'primary',
        ];

        return $clases[$tipoNombre] ?? 'secondary';
    }
    protected function apoderado()
    {
        if (!Auth::user()->hasRole('apoderado')) {
            abort(403, 'Acceso denegado');
        }

        $usuarios = User::with('roles')->get();

        // Obtener el apoderado autenticado
        $apoderado = \App\Models\Apoderado::where('user_id', Auth::id())->first();

        if (!$apoderado) {
            abort(403, 'No se encontró el perfil de apoderado');
        }

        $anio = date('Y');

        // Obtener todos los estudiantes del apoderado
        $estudiantes = \App\Models\Estudiante::with(['user', 'grado'])
            ->where('apoderado_id', $apoderado->id)
            ->where('estado', 1)
            ->get();

        if ($estudiantes->isEmpty()) {
            return view('rol.apoderado.dashboard', compact('usuarios'))->with('info', 'No tiene estudiantes asignados.');
        }

        $datosEstudiantes = [];

        foreach ($estudiantes as $estudiante) {
            // Obtener todas las notas del estudiante organizadas por materia y bimestre
            $notas = \App\Models\Nota::with([
                'criterio.materia',
                'criterio.grado',
                'criterio.materiaCompetencia'
            ])
            ->where('estudiante_id', $estudiante->id)
            ->whereHas('criterio', function($query) use ($anio) {
                $query->where('anio', $anio);
            })
            ->get();

            // Organizar datos por materias y bimestres
            $materiasData = [];

            foreach ($notas as $nota) {
                $criterio = $nota->criterio;
                $materiaId = $criterio->materia_id;
                $materiaNombre = $criterio->materia->nombre ?? 'Sin nombre';
                $bimestre = $criterio->bimestre;

                // Validar que el bimestre esté entre 1 y 4
                if ($bimestre < 1 || $bimestre > 4) {
                    continue;
                }

                if (!isset($materiasData[$materiaId])) {
                    $materiasData[$materiaId] = [
                        'materia_id' => $materiaId,
                        'materia_nombre' => $materiaNombre,
                        'bimestres' => [
                            1 => ['notas' => [], 'promedio' => null],
                            2 => ['notas' => [], 'promedio' => null],
                            3 => ['notas' => [], 'promedio' => null],
                            4 => ['notas' => [], 'promedio' => null]
                        ]
                    ];
                }

                // Agregar nota al bimestre correspondiente
                $materiasData[$materiaId]['bimestres'][$bimestre]['notas'][] = $nota->nota;
            }

            // Calcular promedios por bimestre para cada materia
            $progresoFinal = [];
            foreach ($materiasData as $materiaId => $materiaData) {
                $promediosBimestres = [];

                for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                    $notasBimestre = $materiaData['bimestres'][$bimestre]['notas'];

                    if (count($notasBimestre) > 0) {
                        $promedio = round(array_sum($notasBimestre) / count($notasBimestre), 2);
                        $promediosBimestres[] = $promedio;

                        // Guardar el promedio en la estructura original también
                        $materiasData[$materiaId]['bimestres'][$bimestre]['promedio'] = $promedio;
                    } else {
                        $promediosBimestres[] = null;
                    }
                }

                $progresoFinal[] = [
                    'curso' => $materiaData['materia_nombre'],
                    'promedios' => $promediosBimestres,
                    'materia_data' => $materiasData[$materiaId] // Para información adicional si se necesita
                ];
            }

            // Información del estudiante
            $datosEstudiantes[] = [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => $estudiante->user->apellido_paterno . ' ' .
                                $estudiante->user->apellido_materno . ' ' .
                                $estudiante->user->name,
                'grado' => $estudiante->grado->getNombreCompletoAttribute() ?? 'Sin grado asignado',
                'progreso_cursos' => $progresoFinal,
                'total_cursos' => count($progresoFinal)
            ];
        }

        // Información del apoderado
        $infoApoderado = [
            'nombre_completo' => $apoderado->user->apellido_paterno . ' ' .
                            $apoderado->user->apellido_materno . ' ' .
                            $apoderado->user->name,
            'parentesco' => $apoderado->parentesco,
            'total_estudiantes' => count($estudiantes)
        ];

        $labelsBimestres = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4'];

        return view('rol.apoderado.dashboard', compact(
            'usuarios',
            'datosEstudiantes',
            'labelsBimestres',
            'infoApoderado'
        ));
    }

    protected function estudiante()
    {
        if (!Auth::user()->hasRole('estudiante')) {
            abort(403, 'Acceso denegado');
        }

        $usuarios = User::with('roles')->get();

        // Obtener el estudiante autenticado
        $estudiante = \App\Models\Estudiante::where('user_id', Auth::id())->first();

        if (!$estudiante) {
            abort(403, 'No se encontró el perfil de estudiante');
        }

        $anio = date('Y');

        // Obtener todas las notas del estudiante usando Materiacriterio
        $notas = \App\Models\Nota::with([
            'criterio.materia',
            'criterio.grado',
            'criterio.materiaCompetencia'
        ])
        ->where('estudiante_id', $estudiante->id)
        ->whereHas('criterio', function($query) use ($anio) {
            $query->where('anio', $anio);
        })
        ->get();

        // Organizar datos por materias y bimestres
        $materiasData = [];

        foreach ($notas as $nota) {
            $criterio = $nota->criterio;
            $materiaId = $criterio->materia_id;
            $materiaNombre = $criterio->materia->nombre ?? 'Sin nombre';
            $bimestre = $criterio->bimestre;

            // Validar que el bimestre esté entre 1 y 4
            if ($bimestre < 1 || $bimestre > 4) {
                continue;
            }

            if (!isset($materiasData[$materiaId])) {
                $materiasData[$materiaId] = [
                    'materia_id' => $materiaId,
                    'materia_nombre' => $materiaNombre,
                    'bimestres' => [
                        1 => ['notas' => [], 'promedio' => null],
                        2 => ['notas' => [], 'promedio' => null],
                        3 => ['notas' => [], 'promedio' => null],
                        4 => ['notas' => [], 'promedio' => null]
                    ]
                ];
            }

            // Agregar nota al bimestre correspondiente
            $materiasData[$materiaId]['bimestres'][$bimestre]['notas'][] = $nota->nota;
        }

        // Calcular promedios por bimestre para cada materia
        $progresoFinal = [];
        foreach ($materiasData as $materiaId => $materiaData) {
            $promediosBimestres = [];

            for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                $notasBimestre = $materiaData['bimestres'][$bimestre]['notas'];

                if (count($notasBimestre) > 0) {
                    $promedio = round(array_sum($notasBimestre) / count($notasBimestre), 2);
                    $promediosBimestres[] = $promedio;

                    // Guardar el promedio en la estructura original también
                    $materiasData[$materiaId]['bimestres'][$bimestre]['promedio'] = $promedio;
                } else {
                    $promediosBimestres[] = null;
                }
            }

            $progresoFinal[] = [
                'curso' => $materiaData['materia_nombre'],
                'promedios' => $promediosBimestres,
                'materia_data' => $materiasData[$materiaId] // Para información adicional si se necesita
            ];
        }

        // Obtener información del estudiante para la vista
        $infoEstudiante = [
            'nombre_completo' => $estudiante->user->apellido_paterno . ' ' .
                            $estudiante->user->apellido_materno . ' ' .
                            $estudiante->user->name,
            'grado' => $estudiante->grado->getNombreCompletoAttribute() ?? 'Sin grado asignado',
            'total_cursos' => count($progresoFinal)
        ];

        $labelsBimestres = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4'];

        return view('rol.estudiante.dashboard', compact(
            'usuarios',
            'progresoFinal',
            'labelsBimestres',
            'infoEstudiante'
        ));
    }
    protected function NuevoRol()
    {
        $usuarios = User::with('roles')->get();

        return view('rol.nuevorol.dashboard', compact('usuarios'));
    }
}
