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
    // Verificar que el usuario tenga rol de docente
    if (!Auth::user()->hasRole('docente')) {
        abort(403, 'Acceso denegado');
    }

    // Obtener el docente autenticado usando la relación del User
    $docente = Auth::user()->docente;

    if (!$docente) {
        abort(404, 'Perfil de docente no encontrado');
    }

    // Obtener periodos con estado '1' (activos)
    $periodos = Periodo::where('estado', 1)->orderBy('anio', 'desc')->get();

    // Obtener periodo seleccionado (si viene por request o tomar el primero)
    $periodoId = $request->input('periodo_id');
    $periodoSeleccionado = $periodoId
        ? Periodo::find($periodoId)
        : $periodos->first();

    // Variables para notas y estadísticas
    $asignaciones = collect();
    $grados = collect();
    $estudiantesPorGrado = collect();
    $estadisticasNotas = collect();
    $estadisticasConducta = collect();
    $datosGraficos = [];
    $datosGraficosConducta = []; // Inicializar como array vacío
    $progresoEstudiantes = collect();
    $progresoConducta = collect(); // Inicializar como colección vacía

    if ($periodoSeleccionado) {
        // Obtener asignaciones del docente en el periodo seleccionado
        $asignaciones = Cursogradosecnivanio::where('docente_designado_id', $docente->id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->with(['grado', 'materia', 'periodo'])
            ->get();

        // Obtener grados únicos de las asignaciones
        $grados = $asignaciones->pluck('grado')->unique('id');

        // Para cada grado, obtener estudiantes matriculados en el periodo
        foreach ($grados as $grado) {
            // Obtener estudiantes a través de matrículas
            $estudiantes = \App\Models\Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodoSeleccionado) {
                $query->where('grado_id', $grado->id)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->where('estado', 1);
            })
            ->with(['user', 'matriculas' => function ($query) use ($grado, $periodoSeleccionado) {
                $query->where('grado_id', $grado->id)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->where('estado', 1);
            }])
            ->get();

            $estudiantesPorGrado->put($grado->id, $estudiantes);
        }

        // Inicializar colecciones
        $progresoEstudiantes = collect();
        $progresoConducta = collect();

        // Obtener progreso individual de estudiantes por materia (notas académicas)
        foreach ($asignaciones as $asignacion) {
            $progresoEstudiantes->put($asignacion->id, $this->obtenerProgresoEstudiantes(
                $asignacion,
                $periodoSeleccionado->id,
                false // false para notas académicas
            ));
        }

        // Obtener progreso de conducta por materia
        foreach ($asignaciones as $asignacion) {
            $progresoConducta->put($asignacion->id, $this->obtenerProgresoConducta(
                $asignacion,
                $periodoSeleccionado->id,
                true // true para conducta
            ));
        }

        // Preparar datos para gráficos de notas académicas
        $datosGraficos = $this->prepararDatosGraficosEstudiantes($progresoEstudiantes);

        // Preparar datos para gráficos de conducta
        $datosGraficosConducta = $this->prepararDatosGraficosConducta($progresoConducta);
    }

    return view('rol.docente.dashboard', compact(
        'docente',
        'periodos',
        'periodoSeleccionado',
        'asignaciones',
        'grados',
        'estudiantesPorGrado',
        'estadisticasNotas',
        'estadisticasConducta',
        'datosGraficos',
        'datosGraficosConducta',
        'progresoEstudiantes',
        'progresoConducta'
    ));
}

private function obtenerProgresoEstudiantes($asignacion, $periodoId, $esConducta = false)
{
    // Obtener estudiantes del grado
    $estudiantes = \App\Models\Estudiante::whereHas('matriculas', function ($query) use ($asignacion, $periodoId) {
        $query->where('grado_id', $asignacion->grado_id)
            ->where('periodo_id', $periodoId)
            ->where('estado', 1);
    })
    ->with(['user'])
    ->get();

    $progreso = [];

    foreach ($estudiantes as $estudiante) {
        // Obtener notas académicas del estudiante para esta materia
        $notasEstudiante = Nota::whereHas('criterio', function ($query) use ($asignacion) {
            $query->where('materia_id', $asignacion->materia_id)
                ->where('grado_id', $asignacion->grado_id)
                ->whereDoesntHave('materiaCompetencia', function ($q) {
                    $q->where('nombre', 'LIKE', '%TRANSVERSAL%'); // Excluir competencias transversales
                });
        })
        ->where('estudiante_id', $estudiante->id)
        ->where('periodo_id', $periodoId)
        ->selectRaw('bimestre, AVG(nota) as promedio')
        ->groupBy('bimestre')
        ->orderBy('bimestre')
        ->get();

        if ($notasEstudiante->isNotEmpty()) {
            // Preparar datos para todos los bimestres (1-4)
            $datosBimestres = [];
            $totalNotas = 0;
            $sumaNotas = 0;

            for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                $notaBimestre = $notasEstudiante->firstWhere('bimestre', $bimestre);
                $promedioBimestre = $notaBimestre ? round($notaBimestre->promedio, 2) : null;
                $datosBimestres[$bimestre] = $promedioBimestre;

                if ($promedioBimestre !== null) {
                    $totalNotas++;
                    $sumaNotas += $promedioBimestre;
                }
            }

            $progreso[$estudiante->id] = [
                'estudiante' => $estudiante->user->nombre . ' ' . $estudiante->user->apellido_paterno,
                'dni' => $estudiante->user->dni ?? '',
                'datos' => $datosBimestres,
                'datos_completos' => $notasEstudiante,
                'promedio_general' => $totalNotas > 0 ? round($sumaNotas / $totalNotas, 2) : null,
                'total_bimestres_con_datos' => $totalNotas,
                'estudiante_id' => $estudiante->id
            ];
        }
    }

    return [
        'materia' => $asignacion->materia->nombre,
        'grado' => $asignacion->grado->nombreCompleto,
        'grado_id' => $asignacion->grado_id,
        'materia_id' => $asignacion->materia_id,
        'progreso' => $progreso
    ];
}
private function obtenerProgresoConducta($asignacion, $periodoId)
{
    // Obtener estudiantes del grado
    $estudiantes = \App\Models\Estudiante::whereHas('matriculas', function ($query) use ($asignacion, $periodoId) {
        $query->where('grado_id', $asignacion->grado_id)
            ->where('periodo_id', $periodoId)
            ->where('estado', 1);
    })
    ->with(['user'])
    ->get();

    $progreso = [];

    foreach ($estudiantes as $estudiante) {
        // Obtener notas de conducta del estudiante para esta materia
        $conductaEstudiante = Conductanota::where('estudiante_id', $estudiante->id)
            ->where('periodo_id', $periodoId)
            ->where('curso_grado_sec_niv_anio_id', $asignacion->id)
            ->selectRaw('bimestre, AVG(nota) as promedio')
            ->groupBy('bimestre')
            ->orderBy('bimestre')
            ->get();

        if ($conductaEstudiante->isNotEmpty()) {
            // Preparar datos para todos los bimestres (1-4)
            $datosBimestres = [];
            $totalConductas = 0;
            $sumaConductas = 0;

            for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                $conductaBimestre = $conductaEstudiante->firstWhere('bimestre', $bimestre);
                $promedioBimestre = $conductaBimestre ? round($conductaBimestre->promedio, 2) : null;
                $datosBimestres[$bimestre] = $promedioBimestre;

                if ($promedioBimestre !== null) {
                    $totalConductas++;
                    $sumaConductas += $promedioBimestre;
                }
            }

            $progreso[$estudiante->id] = [
                'estudiante' => $estudiante->user->nombre . ' ' . $estudiante->user->apellido_paterno,
                'dni' => $estudiante->user->dni ?? '',
                'datos' => $datosBimestres,
                'datos_completos' => $conductaEstudiante,
                'promedio_general' => $totalConductas > 0 ? round($sumaConductas / $totalConductas, 2) : null,
                'total_bimestres_con_datos' => $totalConductas,
                'estudiante_id' => $estudiante->id
            ];
        }
    }

    return [
        'materia' => $asignacion->materia->nombre,
        'grado' => $asignacion->grado->nombreCompleto,
        'grado_id' => $asignacion->grado_id,
        'materia_id' => $asignacion->materia_id,
        'es_conducta' => true,
        'progreso' => $progreso
    ];
}

private function prepararDatosGraficosEstudiantes($progresoEstudiantes)
{
    $datos = [];

    foreach ($progresoEstudiantes as $asignacionId => $datosAsignacion) {
        if (!empty($datosAsignacion['progreso'])) {
            $labels = ['Bim. 1', 'Bim. 2', 'Bim. 3', 'Bim. 4'];
            $datasets = [];

            // Colores para diferentes estudiantes
            $colores = [
                'rgb(54, 162, 235)',    // Azul
                'rgb(255, 99, 132)',    // Rojo
                'rgb(75, 192, 192)',    // Verde azulado
                'rgb(255, 159, 64)',    // Naranja
                'rgb(153, 102, 255)',   // Morado
                'rgb(255, 205, 86)',    // Amarillo
                'rgb(201, 203, 207)',   // Gris
                'rgb(50, 168, 82)',     // Verde
                'rgb(220, 57, 18)',     // Rojo oscuro
                'rgb(255, 153, 0)',     // Naranja oscuro
            ];

            $colorIndex = 0;

            foreach ($datosAsignacion['progreso'] as $estudianteId => $estudianteData) {
                // Solo incluir estudiantes con datos suficientes
                $datosBimestres = array_values($estudianteData['datos']);
                $datosValidos = array_filter($datosBimestres, function($valor) {
                    return $valor !== null;
                });

                if (count($datosValidos) >= 2) {
                    $datasets[] = [
                        'label' => $estudianteData['estudiante'],
                        'data' => $datosBimestres,
                        'borderColor' => $colores[$colorIndex % count($colores)],
                        'backgroundColor' => $colores[$colorIndex % count($colores)] . '20',
                        'tension' => 0.3,
                        'fill' => false,
                        'pointRadius' => 6,
                        'pointHoverRadius' => 8,
                        'estudiante_id' => $estudianteId,
                        'dni' => $estudianteData['dni']
                    ];

                    $colorIndex++;

                    if ($colorIndex >= 10) {
                        break;
                    }
                }
            }

            if (!empty($datasets)) {
                $datos['estudiantes_lineas'][$asignacionId] = [
                    'labels' => $labels,
                    'datasets' => $datasets,
                    'materia' => $datosAsignacion['materia'],
                    'grado' => $datosAsignacion['grado']
                ];
            }
        }
    }

    return $datos;
}

private function prepararDatosGraficosConducta($progresoConducta)
{
    $datos = [];

    foreach ($progresoConducta as $asignacionId => $datosAsignacion) {
        if (!empty($datosAsignacion['progreso'])) {
            $labels = ['Bim. 1', 'Bim. 2', 'Bim. 3', 'Bim. 4'];
            $datasets = [];

            // Colores diferentes para conducta (tonos verdes/morados)
            $colores = [
                'rgb(76, 175, 80)',     // Verde
                'rgb(139, 195, 74)',    // Verde claro
                'rgb(205, 220, 57)',    // Lima
                'rgb(156, 39, 176)',    // Morado
                'rgb(103, 58, 183)',    // Morado oscuro
                'rgb(63, 81, 181)',     // Azul índigo
                'rgb(33, 150, 243)',    // Azul claro
                'rgb(0, 150, 136)',     // Verde azulado
                'rgb(121, 85, 72)',     // Marrón
                'rgb(96, 125, 139)',    // Azul grisáceo
            ];

            $colorIndex = 0;

            foreach ($datosAsignacion['progreso'] as $estudianteId => $estudianteData) {
                // Solo incluir estudiantes con datos suficientes
                $datosBimestres = array_values($estudianteData['datos']);
                $datosValidos = array_filter($datosBimestres, function($valor) {
                    return $valor !== null;
                });

                if (count($datosValidos) >= 2) {
                    $datasets[] = [
                        'label' => $estudianteData['estudiante'],
                        'data' => $datosBimestres,
                        'borderColor' => $colores[$colorIndex % count($colores)],
                        'backgroundColor' => $colores[$colorIndex % count($colores)] . '20',
                        'tension' => 0.3,
                        'fill' => false,
                        'pointRadius' => 6,
                        'pointHoverRadius' => 8,
                        'estudiante_id' => $estudianteId,
                        'dni' => $estudianteData['dni']
                    ];

                    $colorIndex++;

                    if ($colorIndex >= 10) {
                        break;
                    }
                }
            }

            if (!empty($datasets)) {
                $datos['conducta_lineas'][$asignacionId] = [
                    'labels' => $labels,
                    'datasets' => $datasets,
                    'materia' => $datosAsignacion['materia'],
                    'grado' => $datosAsignacion['grado'],
                    'es_conducta' => true
                ];
            }
        }
    }

    return $datos;
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

    // Obtener clase de color Bootstrap para tipo de asistencia
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
    protected function apoderado(Request $request)
    {
        if (!Auth::user()->hasRole('apoderado')) {
            abort(403, 'Acceso denegado');
        }

        // Obtener el apoderado autenticado
        $apoderado = Apoderado::where('user_id', Auth::id())->first();

        // Obtener todos los estudiantes del apoderado
        $estudiantes = Estudiante::with(['user'])
            ->where('apoderado_id', $apoderado->id)
            //->where('estado', 1)
            ->get();

        if ($estudiantes->isEmpty()) {
            return view('rol.apoderado.dashboard')->with('info', 'No tiene estudiantes asignados.');
        }

        $estudianteIds = $estudiantes->pluck('id')->toArray();

        // Obtener parámetros de filtro
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

        $bimestreFiltro = $request->input('bimestre');
        $usuarios = User::with('roles')->get();

        if (!$apoderado) {
            abort(403, 'No se encontró el perfil de apoderado');
        }

        // Obtener todos los estudiantes del apoderado
        $estudiantes = Estudiante::with(['user', 'grado'])
            ->where('apoderado_id', $apoderado->id)
            ->where('estado', 1)
            ->get();

        if ($estudiantes->isEmpty()) {
            return view('rol.apoderado.dashboard', compact(
                'periodos',
                'periodoSeleccionado',
                'usuarios'
            ))->with('info', 'No tiene estudiantes asignados.');
        }

        $datosEstudiantes = [];

        foreach ($estudiantes as $estudiante) {
            // Obtener la matrícula del estudiante en el período seleccionado
            $matricula = Matricula::where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('estado', 1)
                ->first();

            // Si no está matriculado en este período, mostrar mensaje
            if (!$matricula) {
                $datosEstudiantes[] = [
                    'estudiante_id' => $estudiante->id,
                    'nombre_completo' => $estudiante->user->apellido_paterno . ' ' .
                                    $estudiante->user->apellido_materno . ' ' .
                                    $estudiante->user->nombre,
                    'grado' => $estudiante->grado->getNombreCompletoAttribute() ?? 'Sin grado asignado',
                    'progreso_cursos' => [],
                    'progreso_conducta' => [],
                    'total_cursos' => 0,
                    'total_conducta' => 0,
                    'mensaje' => 'El estudiante no está matriculado en el período seleccionado.'
                ];
                continue;
            }

            // Obtener las asignaciones de cursos para el grado del estudiante en el período
            $asignaciones = \App\Models\Maya\Cursogradosecnivanio::where('grado_id', $estudiante->grado_id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->with(['materia', 'grado'])
                ->get();

            // =================== NOTAS ACADÉMICAS ===================
            $progresoCursos = [];
            $progresoConducta = [];

            foreach ($asignaciones as $asignacion) {
                // Obtener notas académicas para esta asignación
                $notasAcademicas = Nota::whereHas('criterio', function ($query) use ($asignacion) {
                    $query->where('materia_id', $asignacion->materia_id)
                        ->where('grado_id', $asignacion->grado_id)
                        ->whereDoesntHave('materiaCompetencia', function ($q) {
                            $q->where('nombre', 'LIKE', '%TRANSVERSAL%');
                        });
                })
                ->where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->when($bimestreFiltro && $bimestreFiltro !== 'anual', function ($query) use ($bimestreFiltro) {
                    return $query->where('bimestre', $bimestreFiltro);
                })
                ->selectRaw('bimestre, AVG(nota) as promedio')
                ->groupBy('bimestre')
                ->orderBy('bimestre')
                ->get();

                if ($notasAcademicas->isNotEmpty()) {
                    $promediosBimestres = [];
                    for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                        $notaBimestre = $notasAcademicas->firstWhere('bimestre', $bimestre);
                        $promediosBimestres[$bimestre] = $notaBimestre ? round($notaBimestre->promedio, 2) : null;
                    }

                    // Calcular promedio general del curso
                    $notasValidas = array_filter($promediosBimestres, function($n) { return $n !== null; });
                    $promedioGeneral = count($notasValidas) > 0 ?
                        round(array_sum($notasValidas) / count($notasValidas), 2) : null;

                    $progresoCursos[] = [
                        'curso' => $asignacion->materia->nombre ?? 'Sin nombre',
                        'promedios' => $promediosBimestres,
                        'promedio_general' => $promedioGeneral,
                        'estado' => $promedioGeneral !== null ?
                            ($promedioGeneral >= 2.5 ? 'Aprobado' : 'Reprobado') : 'Sin datos',
                        'asignacion_id' => $asignacion->id
                    ];
                }

                // =================== NOTAS DE CONDUCTA ===================
                // Obtener notas de conducta para esta asignación
                $notasConducta = Conductanota::where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodoSeleccionado->id)
                    ->where('curso_grado_sec_niv_anio_id', $asignacion->id)
                    ->when($bimestreFiltro && $bimestreFiltro !== 'anual', function ($query) use ($bimestreFiltro) {
                        return $query->where('bimestre', $bimestreFiltro);
                    })
                    ->selectRaw('bimestre, AVG(nota) as promedio')
                    ->groupBy('bimestre')
                    ->orderBy('bimestre')
                    ->get();

                if ($notasConducta->isNotEmpty()) {
                    $conductaBimestres = [];
                    for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                        $conductaBimestre = $notasConducta->firstWhere('bimestre', $bimestre);
                        $conductaBimestres[$bimestre] = $conductaBimestre ? round($conductaBimestre->promedio, 2) : null;
                    }

                    // Calcular promedio general de conducta
                    $conductasValidas = array_filter($conductaBimestres, function($c) { return $c !== null; });
                    $promedioConductaGeneral = count($conductasValidas) > 0 ?
                        round(array_sum($conductasValidas) / count($conductasValidas), 2) : null;

                    $progresoConducta[] = [
                        'curso' => $asignacion->materia->nombre ?? 'Sin nombre',
                        'promedios' => $conductaBimestres,
                        'promedio_general' => $promedioConductaGeneral,
                        'estado' => $promedioConductaGeneral !== null ?
                            ($promedioConductaGeneral >= 2.5 ? 'Adecuado' : 'Inadecuado') : 'Sin datos',
                        'asignacion_id' => $asignacion->id
                    ];
                }
            }

            // Información del estudiante
            $datosEstudiantes[] = [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => $estudiante->user->apellido_paterno . ' ' .
                                $estudiante->user->apellido_materno . ' ' .
                                $estudiante->user->nombre,
                'grado' => $estudiante->grado->getNombreCompletoAttribute() ?? 'Sin grado asignado',
                'grado_id' => $estudiante->grado_id,
                'progreso_cursos' => $progresoCursos,
                'progreso_conducta' => $progresoConducta,
                'total_cursos' => count($progresoCursos),
                'total_conducta' => count($progresoConducta),
                'mensaje' => count($progresoCursos) == 0 && count($progresoConducta) == 0 ?
                    'No hay notas registradas para este período' : null
            ];
        }

        // Información del apoderado
        $infoApoderado = [
            'nombre_completo' => $apoderado->user->apellido_paterno . ' ' .
                            $apoderado->user->apellido_materno . ' ' .
                            $apoderado->user->nombre,
            'parentesco' => $apoderado->parentesco,
            'total_estudiantes' => count($estudiantes)
        ];

        $labelsBimestres = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4'];

        return view('rol.apoderado.dashboard', compact(
            'periodos',
            'periodoSeleccionado',
            'usuarios',
            'datosEstudiantes',
            'labelsBimestres',
            'infoApoderado',
            'bimestreFiltro'
        ));
    }

    protected function estudiante(Request $request)
    {
        if (!Auth::user()->hasRole('estudiante')) {
            abort(403, 'Acceso denegado');
        }

        // Obtener el estudiante autenticado
        $estudiante = \App\Models\Estudiante::where('user_id', Auth::id())->first();

        if (!$estudiante) {
            abort(403, 'No se encontró el perfil de estudiante');
        }

        // Obtener períodos donde el estudiante tiene matrículas
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

        // Obtener período seleccionado
        $periodoId = $request->input('periodo_id');
        $periodoSeleccionado = $periodoId
            ? Periodo::find($periodoId)
            : $periodos->first();

        if (!$periodoSeleccionado) {
            return back()->with('error', 'No hay períodos disponibles.');
        }

        $bimestreFiltro = $request->input('bimestre');
        $usuarios = User::with('roles')->get();

        // Obtener la matrícula del estudiante en el período seleccionado
        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->where('estado', 1)
            ->first();

        // Si no está matriculado en este período, mostrar mensaje
        if (!$matricula) {
            return view('rol.estudiante.dashboard', compact(
                'periodos',
                'periodoSeleccionado',
                'usuarios'
            ))->with('error', 'No estás matriculado en el período seleccionado.');
        }

        // Obtener las asignaciones de cursos para el grado de la matrícula en el período
        // IMPORTANTE: Usar el grado_id de la matrícula, no del estudiante
        $asignaciones = \App\Models\Maya\Cursogradosecnivanio::where('grado_id', $matricula->grado_id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->with(['materia', 'grado'])
            ->get();

        // =================== NOTAS ACADÉMICAS ===================
        $progresoCursos = [];
        $progresoConducta = [];

        foreach ($asignaciones as $asignacion) {
            // Obtener notas académicas para esta asignación
            $notasAcademicas = Nota::whereHas('criterio', function ($query) use ($asignacion) {
                $query->where('materia_id', $asignacion->materia_id)
                    ->where('grado_id', $asignacion->grado_id)
                    ->whereDoesntHave('materiaCompetencia', function ($q) {
                        $q->where('nombre', 'LIKE', '%TRANSVERSAL%');
                    });
            })
            ->where('estudiante_id', $estudiante->id)
            ->where('periodo_id', $periodoSeleccionado->id)
            ->when($bimestreFiltro && $bimestreFiltro !== 'anual', function ($query) use ($bimestreFiltro) {
                return $query->where('bimestre', $bimestreFiltro);
            })
            ->selectRaw('bimestre, AVG(nota) as promedio')
            ->groupBy('bimestre')
            ->orderBy('bimestre')
            ->get();

            if ($notasAcademicas->isNotEmpty()) {
                $promediosBimestres = [];
                for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                    $notaBimestre = $notasAcademicas->firstWhere('bimestre', $bimestre);
                    $promediosBimestres[$bimestre] = $notaBimestre ? round($notaBimestre->promedio, 2) : null;
                }

                // Calcular promedio general del curso
                $notasValidas = array_filter($promediosBimestres, function($n) { return $n !== null; });
                $promedioGeneral = count($notasValidas) > 0 ?
                    round(array_sum($notasValidas) / count($notasValidas), 2) : null;

                $progresoCursos[] = [
                    'curso' => $asignacion->materia->nombre ?? 'Sin nombre',
                    'promedios' => $promediosBimestres,
                    'promedio_general' => $promedioGeneral,
                    'estado' => $promedioGeneral !== null ?
                        ($promedioGeneral >= 2.5 ? 'Aprobado' : 'Reprobado') : 'Sin datos',
                    'asignacion_id' => $asignacion->id
                ];
            }

            // =================== NOTAS DE CONDUCTA ===================
            // Obtener notas de conducta para esta asignación
            $notasConducta = Conductanota::where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoSeleccionado->id)
                ->where('curso_grado_sec_niv_anio_id', $asignacion->id)
                ->when($bimestreFiltro && $bimestreFiltro !== 'anual', function ($query) use ($bimestreFiltro) {
                    return $query->where('bimestre', $bimestreFiltro);
                })
                ->selectRaw('bimestre, AVG(nota) as promedio')
                ->groupBy('bimestre')
                ->orderBy('bimestre')
                ->get();

            if ($notasConducta->isNotEmpty()) {
                $conductaBimestres = [];
                for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                    $conductaBimestre = $notasConducta->firstWhere('bimestre', $bimestre);
                    $conductaBimestres[$bimestre] = $conductaBimestre ? round($conductaBimestre->promedio, 2) : null;
                }

                // Calcular promedio general de conducta
                $conductasValidas = array_filter($conductaBimestres, function($c) { return $c !== null; });
                $promedioConductaGeneral = count($conductasValidas) > 0 ?
                    round(array_sum($conductasValidas) / count($conductasValidas), 2) : null;

                $progresoConducta[] = [
                    'curso' => $asignacion->materia->nombre ?? 'Sin nombre',
                    'promedios' => $conductaBimestres,
                    'promedio_general' => $promedioConductaGeneral,
                    'estado' => $promedioConductaGeneral !== null ?
                        ($promedioConductaGeneral >= 2.5 ? 'Adecuado' : 'Inadecuado') : 'Sin datos',
                    'asignacion_id' => $asignacion->id
                ];
            }
        }

        // Información del estudiante
        // IMPORTANTE: Obtener el grado desde la matrícula, no del estudiante
        $grado = $matricula->grado;
        $infoEstudiante = [
            'estudiante_id' => $estudiante->id,
            'nombre_completo' => $estudiante->user->apellido_paterno . ' ' .
                            $estudiante->user->apellido_materno . ' ' .
                            $estudiante->user->nombre,
            'grado' => $grado ? $grado->getNombreCompletoAttribute() : 'Sin grado asignado',
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
