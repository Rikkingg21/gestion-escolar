<?php

namespace App\Http\Controllers\Rol;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class DasboardController extends Controller
{
    public function admin()
    {
        // Verifica si el usuario autenticado tiene el rol de admin
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso denegado');
        }

        // Obtiene todos los usuarios con sus roles
        $usuarios = User::with('roles')->get();

        return view('rol.admin.dashboard', compact('usuarios'));
    }
    public function director()
    {
        // Verifica si el usuario autenticado tiene el rol de director
        if (!Auth::user()->hasRole('director')) {
            abort(403, 'Acceso denegado');
        }

        // Obtiene todos los usuarios con sus roles
        $usuarios = User::with('roles')->get();

        return view('rol.director.dashboard', compact('usuarios'));
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
