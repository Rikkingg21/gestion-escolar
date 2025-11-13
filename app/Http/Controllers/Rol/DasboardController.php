<?php

namespace App\Http\Controllers\Rol;

use App\Http\Controllers\Controller;
use App\Models\Apoderado;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Auxiliar;
use App\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class DasboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->admin();
        } elseif ($user->hasRole('director')) {
            return $this->director();
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

    protected function director()
    {
        if (!Auth::user()->hasRole('director')) {
            abort(403, 'Acceso denegado');
        }

        $usuarios = User::with('roles')->get();
        $anio = date('Y');

        // Obtener solo los grados activos (estado = 1)
        $grados = \App\Models\Grado::where('estado', 1)->get();

        $progreso = [];
        $labelsBimestres = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4'];

        foreach ($grados as $grado) {
            $progresoGrado = [];

            for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                // Obtener notas oficiales o extra oficiales (estados 2 y 3) para este grado y bimestre
                $notas = \App\Models\Nota::where('bimestre', $bimestre)
                    ->whereIn('publico', ['2', '3']) // Solo notas oficiales o extra oficiales
                    ->whereHas('estudiante', function($q) use ($grado) {
                        $q->where('grado_id', $grado->id)
                        ->where('estado', '1'); // Solo estudiantes activos
                    })
                    ->whereHas('criterio', function($q) use ($anio) {
                        $q->where('anio', $anio); // Solo criterios del año actual
                    })
                    ->pluck('nota');

                // Calcular promedio solo si hay notas
                $promedio = $notas->count() > 0 ? round($notas->avg(), 2) : null;
                $progresoGrado[] = $promedio;
            }

            $progreso[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'promedios' => $progresoGrado,
            ];
        }

        // Estadísticas adicionales para el dashboard
        $estadisticas = $this->obtenerEstadisticasDirector($anio);

        return view('rol.director.dashboard', compact(
            'usuarios',
            'progreso',
            'labelsBimestres',
            'estadisticas'
        ));
    }

    /**
     * Obtener estadísticas adicionales para el dashboard del director
     */
    private function obtenerEstadisticasDirector($anio)
    {
        // Total de estudiantes activos
        $totalEstudiantes = \App\Models\Estudiante::where('estado', '1')->count();

        // Total de docentes activos
        $totalDocentes = \App\Models\Docente::whereHas('user', function($q) {
            $q->where('estado', '1');
        })->count();

        // Cursos activos este año
        $totalCursos = \App\Models\Maya\Cursogradosecnivanio::where('anio', $anio)->count();

        // Porcentaje de notas publicadas por bimestre
        $notasPorBimestre = [];
        for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
            $totalNotasBimestre = \App\Models\Nota::where('bimestre', $bimestre)
                ->whereHas('criterio', function($q) use ($anio) {
                    $q->where('anio', $anio);
                })
                ->count();

            $notasPublicadasBimestre = \App\Models\Nota::where('bimestre', $bimestre)
                ->whereIn('publico', ['2', '3']) // Oficiales o extra oficiales
                ->whereHas('criterio', function($q) use ($anio) {
                    $q->where('anio', $anio);
                })
                ->count();

            $porcentaje = $totalNotasBimestre > 0
                ? round(($notasPublicadasBimestre / $totalNotasBimestre) * 100, 1)
                : 0;

            $notasPorBimestre[$bimestre] = [
                'total' => $totalNotasBimestre,
                'publicadas' => $notasPublicadasBimestre,
                'porcentaje' => $porcentaje
            ];
        }

        return [
            'totalEstudiantes' => $totalEstudiantes,
            'totalDocentes' => $totalDocentes,
            'totalCursos' => $totalCursos,
            'notasPorBimestre' => $notasPorBimestre,
            'anio' => $anio
        ];
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
        $cursos = \App\Models\Maya\Cursogradosecnivanio::with([
            'grado',
            'materia'
        ])->where('docente_designado_id', $docente->id)
        ->where('anio', $anio)
        ->get();

        // Si no hay cursos, retornar vista con mensaje
        if ($cursos->isEmpty()) {
            $usuarios = User::with('roles')->get();
            return view('rol.nuevorol.dashboard', compact('usuarios'));
        }

        // Agrupar cursos por grado
        $cursosPorGrado = $cursos->groupBy('grado_id');

        $datosGraficos = [];
        $estadisticasDocente = [];

        foreach ($cursosPorGrado as $gradoId => $cursosDelGrado) {
            $grado = $cursosDelGrado->first()->grado;

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
                        ->whereHas('criterio', function($query) use ($cursosDelGrado) {
                            $query->whereIn('materia_id', $cursosDelGrado->pluck('materia_id'));
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
            $estadisticasGrado = $this->calcularEstadisticasGrado($estudiantes, $cursosDelGrado);

            $datosGraficos[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'estudiantes' => $graficosEstudiantes,
                'cursos' => $cursosDelGrado->pluck('materia.nombre')->toArray(),
                'totalEstudiantes' => count($graficosEstudiantes)
            ];

            $estadisticasDocente[$grado->getNombreCompletoAttribute()] = $estadisticasGrado;
        }

        // Estadísticas generales del docente
        $estadisticasGenerales = $this->calcularEstadisticasGenerales($cursos, $anio);

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
