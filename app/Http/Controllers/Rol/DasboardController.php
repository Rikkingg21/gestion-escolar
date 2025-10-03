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
        // Verifica si el usuario autenticado tiene el rol de admin
        if (!Auth::user()->hasRole('docente')) {
            abort(403, 'Acceso denegado');
        }
        // Obtiene todos los usuarios con sus roles
        $usuarios = User::with('roles')->get();

        return view('rol.docente.dashboard', compact('usuarios'));
    }
    public function auxiliar()
    {
        // Verifica si el usuario autenticado tiene el rol de admin
        if (!Auth::user()->hasRole('auxiliar')) {
            abort(403, 'Acceso denegado');
        }
        // Obtiene todos los usuarios con sus roles
        $usuarios = User::with('roles')->get();

        return view('rol.auxiliar.dashboard', compact('usuarios'));

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
