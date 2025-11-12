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

        // Obtener bimestres del año actual
        $bimestres = \App\Models\Maya\Bimestre::whereHas('cursoGradoSecNivAnio', function($q) use ($anio) {
            $q->where('anio', $anio);
        })->get();

        $datosAsistencias = [];

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

            foreach ($estudiantes as $estudiante) {
                // Obtener todas las asistencias del estudiante
                $totalAsistencias = $estudiante->asistencias->count();

                $porcentajesPorTipo = [];

                foreach ($tiposAsistencia as $tipo) {
                    $countTipo = $estudiante->asistencias->where('tipo_asistencia_id', $tipo->id)->count();
                    $porcentaje = $totalAsistencias > 0 ? round(($countTipo / $totalAsistencias) * 100, 2) : 0;

                    $porcentajesPorTipo[$tipo->nombre] = $porcentaje;
                }

                $datosEstudiantes[] = [
                    'nombre_completo' => $estudiante->user->apellido_paterno . ' ' .
                                    $estudiante->user->apellido_materno . ' ' .
                                    $estudiante->user->name,
                    'total_asistencias' => $totalAsistencias,
                    'porcentajes_tipo' => $porcentajesPorTipo
                ];
            }

            $datosAsistencias[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'estudiantes' => $datosEstudiantes,
                'tipos_asistencia' => $tiposAsistencia->pluck('nombre')->toArray()
            ];
        }

        return view('rol.auxiliar.dashboard', compact('usuarios', 'datosAsistencias', 'tiposAsistencia'));
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
            // Obtener todas las notas del estudiante
            $notas = \App\Models\Nota::with([
                'bimestre.cursoGradoSecNivAnio.materia',
                'criterio.materiaCompetencia'
            ])
            ->whereHas('bimestre.cursoGradoSecNivAnio', function($query) use ($anio) {
                $query->where('anio', $anio);
            })
            ->where('estudiante_id', $estudiante->id)
            ->get();

            // Organizar datos por cursos y bimestres para este estudiante
            $datosCursos = [];
            $cursosConNotas = [];

            // Identificar todos los cursos que tienen notas
            foreach ($notas as $nota) {
                $curso = $nota->bimestre->cursoGradoSecNivAnio;
                $materiaNombre = $curso->materia->nombre ?? 'Sin nombre';
                $cursoId = $curso->id;

                if (!in_array($cursoId, $cursosConNotas)) {
                    $cursosConNotas[] = $cursoId;
                    $datosCursos[$cursoId] = [
                        'curso_id' => $cursoId,
                        'materia' => $materiaNombre,
                        'bimestres' => [1 => null, 2 => null, 3 => null, 4 => null]
                    ];
                }
            }

            // Calcular promedios por curso y bimestre
            foreach ($notas as $nota) {
                $curso = $nota->bimestre->cursoGradoSecNivAnio;
                $cursoId = $curso->id;
                $bimestreNumero = (int)$nota->bimestre->nombre;

                if ($bimestreNumero >= 1 && $bimestreNumero <= 4) {
                    if (!isset($datosCursos[$cursoId]['notas_bimestre'][$bimestreNumero])) {
                        $datosCursos[$cursoId]['notas_bimestre'][$bimestreNumero] = [];
                    }
                    $datosCursos[$cursoId]['notas_bimestre'][$bimestreNumero][] = $nota->nota;
                }
            }

            // Calcular promedios finales por bimestre
            $progresoFinal = [];
            foreach ($datosCursos as $cursoId => $cursoData) {
                $promediosBimestres = [];

                for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                    if (isset($cursoData['notas_bimestre'][$bimestre]) &&
                        count($cursoData['notas_bimestre'][$bimestre]) > 0) {

                        $notasBimestre = $cursoData['notas_bimestre'][$bimestre];
                        $promedio = round(array_sum($notasBimestre) / count($notasBimestre), 2);
                        $promediosBimestres[] = $promedio;
                    } else {
                        $promediosBimestres[] = null;
                    }
                }

                $progresoFinal[] = [
                    'curso' => $cursoData['materia'],
                    'promedios' => $promediosBimestres
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

        // Obtener todas las notas del estudiante con relaciones CORREGIDAS
        $notas = \App\Models\Nota::with([
            'bimestre.cursoGradoSecNivAnio.materia',
            'criterio.materiaCompetencia' // Cambiado de 'competencia' a 'materiaCompetencia'
        ])
        ->whereHas('bimestre.cursoGradoSecNivAnio', function($query) use ($anio) {
            $query->where('anio', $anio);
        })
        ->where('estudiante_id', $estudiante->id)
        ->get();

        // Organizar datos por cursos y bimestres
        $datosCursos = [];
        $cursosConNotas = [];

        // Primero, identificar todos los cursos que tienen notas
        foreach ($notas as $nota) {
            $curso = $nota->bimestre->cursoGradoSecNivAnio;
            $materiaNombre = $curso->materia->nombre ?? 'Sin nombre';
            $cursoId = $curso->id;

            if (!in_array($cursoId, $cursosConNotas)) {
                $cursosConNotas[] = $cursoId;
                $datosCursos[$cursoId] = [
                    'curso_id' => $cursoId,
                    'materia' => $materiaNombre,
                    'bimestres' => [
                        1 => null, // Bimestre 1
                        2 => null, // Bimestre 2
                        3 => null, // Bimestre 3
                        4 => null  // Bimestre 4
                    ]
                ];
            }
        }

        // Calcular promedios por curso y bimestre
        foreach ($notas as $nota) {
            $curso = $nota->bimestre->cursoGradoSecNivAnio;
            $cursoId = $curso->id;
            $bimestreNumero = (int)$nota->bimestre->nombre;

            // Solo procesar bimestres del 1 al 4
            if ($bimestreNumero >= 1 && $bimestreNumero <= 4) {
                // Inicializar array para este bimestre si no existe
                if (!isset($datosCursos[$cursoId]['notas_bimestre'][$bimestreNumero])) {
                    $datosCursos[$cursoId]['notas_bimestre'][$bimestreNumero] = [];
                }

                // Agregar nota al bimestre correspondiente
                $datosCursos[$cursoId]['notas_bimestre'][$bimestreNumero][] = $nota->nota;
            }
        }

        // Calcular promedios finales por bimestre
        $progresoFinal = [];
        foreach ($datosCursos as $cursoId => $cursoData) {
            $promediosBimestres = [];

            for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                if (isset($cursoData['notas_bimestre'][$bimestre]) &&
                    count($cursoData['notas_bimestre'][$bimestre]) > 0) {

                    $notasBimestre = $cursoData['notas_bimestre'][$bimestre];
                    $promedio = round(array_sum($notasBimestre) / count($notasBimestre), 2);
                    $promediosBimestres[] = $promedio;
                } else {
                    $promediosBimestres[] = null;
                }
            }

            $progresoFinal[] = [
                'curso' => $cursoData['materia'],
                'promedios' => $promediosBimestres
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
