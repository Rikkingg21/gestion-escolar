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

        // Año actual
        $anio = date('Y');

        // Obtener todos los grados activos
        $grados = \App\Models\Grado::all();

        // Obtener todos los bimestres del año actual
        $bimestres = \App\Models\Maya\Bimestre::whereHas('cursoGradoSecNivAnio', function($q) use ($anio) {
            $q->where('anio', $anio);
        })->get();

        // Preparar datos: promedio de notas por grado y bimestre
        $progreso = [];
        foreach ($grados as $grado) {
            $progresoGrado = [];
            foreach ($bimestres as $bimestre) {
                // Notas de estudiantes de este grado en este bimestre
                $notas = \App\Models\Nota::whereHas('estudiante', function($q) use ($grado) {
                    $q->where('grado_id', $grado->id);
                })->where('bimestre_id', $bimestre->id)->pluck('nota');

                $promedio = $notas->count() ? round($notas->avg(), 2) : null;
                $progresoGrado[] = $promedio;
            }
            $progreso[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'promedios' => $progresoGrado,
            ];
        }

        // Nombres de los bimestres
        $labelsBimestres = collect([1, 2, 3, 4]);

        $progreso = [];
        foreach ($grados as $grado) {
            $progresoGrado = [];
            foreach ($labelsBimestres as $numBimestre) {
                // Busca el bimestre correspondiente
                $bimestre = $bimestres->first(function($b) use ($numBimestre) {
                    return (int)$b->nombre === $numBimestre;
                });

                if ($bimestre) {
                    $notas = \App\Models\Nota::whereHas('estudiante', function($q) use ($grado) {
                        $q->where('grado_id', $grado->id);
                    })->where('bimestre_id', $bimestre->id)->pluck('nota');

                    $promedio = $notas->count() ? round($notas->avg(), 2) : null;
                } else {
                    $promedio = null;
                }
                $progresoGrado[] = $promedio;
            }
            $progreso[] = [
                'grado' => $grado->getNombreCompletoAttribute(),
                'promedios' => $progresoGrado,
            ];
        }

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
