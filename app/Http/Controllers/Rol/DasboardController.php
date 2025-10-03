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
    public function admin()
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

    public function director()
    {
        if (!Auth::user()->hasRole('director')) {
            abort(403, 'Acceso denegado');
        }

        $usuarios = User::with('roles')->get();
        $anio = date('Y');

        // Obtener solo los grados activos (estado = 1)
        $grados = \App\Models\Grado::where('estado', 1)->get();

        // Obtener todos los cursos del año actual con sus bimestres
        $cursos = \App\Models\Maya\Cursogradosecnivanio::with(['bimestres', 'grado'])
            ->where('anio', $anio)
            ->get();

        $progreso = [];

        foreach ($grados as $grado) {
            $progresoGrado = [];

            // Buscar los cursos de este grado
            $cursosDelGrado = $cursos->where('grado_id', $grado->id);

            for ($numBimestre = 1; $numBimestre <= 4; $numBimestre++) {
                $promediosBimestre = [];

                foreach ($cursosDelGrado as $curso) {
                    // Buscar el bimestre específico
                    $bimestre = $curso->bimestres->where('nombre', $numBimestre)->first();

                    if ($bimestre) {
                        // Obtener notas para este bimestre
                        $notas = \App\Models\Nota::where('bimestre_id', $bimestre->id)
                            ->whereHas('estudiante', function($q) use ($grado) {
                                $q->where('grado_id', $grado->id);
                            })
                            ->pluck('nota');

                        if ($notas->count() > 0) {
                            $promediosBimestre[] = $notas->avg();
                        }
                    }
                }

                // Calcular el promedio general del bimestre para el grado
                $promedio = count($promediosBimestre) > 0 ? round(array_sum($promediosBimestre) / count($promediosBimestre), 2) : null;
                $progresoGrado[] = $promedio;
            }

            $progreso[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'promedios' => $progresoGrado,
            ];
        }

        $labelsBimestres = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4'];

        return view('rol.director.dashboard', compact('usuarios', 'progreso', 'labelsBimestres'));
    }

    public function docente()
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
            'bimestres',
            'materia'
        ])->where('docente_designado_id', $docente->id)
        ->where('anio', $anio)
        ->get();

        // Si no hay cursos, retornar vista con mensaje
        if ($cursos->isEmpty()) {
            return view('rol.docente.dashboard', compact('usuarios', 'datosGraficos'))->with('info', 'No tiene cursos asignados para el año actual.');
        }

        // Agrupar cursos por grado
        $cursosPorGrado = $cursos->groupBy('grado_id');

        $datosGraficos = [];

        foreach ($cursosPorGrado as $gradoId => $cursosDelGrado) {
            $grado = $cursosDelGrado->first()->grado;

            // Obtener estudiantes de este grado, ordenados alfabéticamente por apellidos
            $estudiantes = \App\Models\Estudiante::with(['user', 'notas' => function($query) use ($cursosDelGrado) {
                // Obtener los bimestres de los cursos del docente
                $bimestreIds = $cursosDelGrado->flatMap(function($curso) {
                    return $curso->bimestres->pluck('id');
                })->unique()->toArray();

                $query->whereIn('bimestre_id', $bimestreIds);
            }])
            ->where('grado_id', $gradoId)
            ->where('estado', 1) // Solo estudiantes activos
            ->get()
            ->sortBy(function($estudiante) {
                return $estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno;
            });

            // CORRECCIÓN: Cambiar $cursosDelGgado por $cursosDelGrado
            // Obtener bimestres del año actual para estos cursos
            $bimestres = $cursosDelGrado->flatMap(function($curso) {
                return $curso->bimestres;
            })->unique('id')->sortBy('nombre');

            // Si no hay estudiantes, continuar con el siguiente grado
            if ($estudiantes->isEmpty()) {
                continue;
            }

            // Preparar datos para el gráfico
            $labelsEstudiantes = [];
            $datosBimestres = [];

            // Inicializar datos por bimestre
            foreach ($bimestres as $bimestre) {
                $datosBimestres[$bimestre->nombre] = [
                    'label' => 'Bimestre ' . $bimestre->nombre,
                    'data' => [],
                    'backgroundColor' => $this->getColorForBimestre($bimestre->nombre)
                ];
            }

            // Para cada estudiante, obtener sus promedios por bimestre
            foreach ($estudiantes as $estudiante) {
                $nombreCompleto = $estudiante->user->apellido_paterno . ' ' .
                                $estudiante->user->apellido_materno . ' ' .
                                $estudiante->user->name;
                $labelsEstudiantes[] = $nombreCompleto;

                // Calcular promedio por bimestre para este estudiante
                foreach ($bimestres as $bimestre) {
                    $notasBimestre = $estudiante->notas->where('bimestre_id', $bimestre->id);

                    if ($notasBimestre->count() > 0) {
                        $promedio = $notasBimestre->avg('nota');
                        $datosBimestres[$bimestre->nombre]['data'][] = round($promedio, 2);
                    } else {
                        $datosBimestres[$bimestre->nombre]['data'][] = null;
                    }
                }
            }

            $datosGraficos[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'labelsEstudiantes' => $labelsEstudiantes,
                'datasets' => array_values($datosBimestres),
                'bimestres' => $bimestres->pluck('nombre')->toArray()
            ];
        }

        return view('rol.docente.dashboard', compact('usuarios', 'datosGraficos'));
    }

    // Función auxiliar para generar colores por bimestre
    private function getColorForBimestre($bimestre)
    {
        $colores = [
            1 => '#FF6384', // Bimestre 1 - Rojo
            2 => '#36A2EB', // Bimestre 2 - Azul
            3 => '#FFCE56', // Bimestre 3 - Amarillo
            4 => '#4BC0C0', // Bimestre 4 - Verde
        ];

        return $colores[$bimestre] ?? '#999999';
    }
public function auxiliar()
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
    public function apoderado()
    {
        // Verifica si el usuario autenticado tiene el rol de admin
        if (!Auth::user()->hasRole('apoderado')) {
            abort(403, 'Acceso denegado');
        }
        // Obtiene todos los usuarios con sus roles
        $usuarios = User::with('roles')->get();

        return view('rol.apoderado.dashboard', compact('usuarios'));
    }

    public function estudiante()
    {
        // Verifica si el usuario autenticado tiene el rol de admin
        if (!Auth::user()->hasRole('estudiante')) {
            abort(403, 'Acceso denegado');
        }
        // Obtiene todos los usuarios con sus roles
        $usuarios = User::with('roles')->get();

        return view('rol.estudiante.dashboard', compact('usuarios'));
    }
}
