<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SessionSelectionController extends Controller
{
    public function showSessionSelection()
    {
    $user = auth()->user();

    // Si es admin, ve todas las sesiones
    if ($user->hasRole('admin')) {
        $usuarios = User::where('estado', "activo")->with('roles')->get();
    }
    // Si es director, ve todas menos los admin
    elseif ($user->hasRole('director')) {
        $usuarios = User::where('estado', "activo")
            ->whereDoesntHave('roles', function($q) {
                $q->where('nombre', 'admin');
            })
            ->with('roles')->get();
    }
    // Si es docente, auxiliar o estudiante, solo ve su sesión
    elseif ($user->hasRole('docente') || $user->hasRole('auxiliar') || $user->hasRole('estudiante')) {
        $usuarios = User::where('id', $user->id)->with('roles')->get();
    }
    // Si es apoderado, ve su sesión y la de sus estudiantes asignados
    elseif ($user->hasRole('apoderado')) {
        // Suponiendo que tienes una relación 'estudiantes' en el modelo User para apoderado
        $estudiantes = $user->apoderado && $user->apoderado->estudiantes
            ? $user->apoderado->estudiantes->pluck('user_id')->toArray()
            : [];
        $usuarios = User::whereIn('id', array_merge([$user->id], $estudiantes))->with('roles')->get();
    }
    // Por defecto, solo su sesión
    else {
        $usuarios = User::where('id', $user->id)->with('roles')->get();
    }

    return view('auth.select-session', compact('usuarios'));
    }
    /*
    verificar rol
    Si el usuario tiene rol
    */

    public function selectSessionUser(Request $request)
    {

    }

    protected function redirectToRole($role)
    {
        switch ($role) {
            case 'admin':
            case 'director':
                return redirect()->route('admin.dashboard');
            case 'docente':
                return redirect()->route('docente.dashboard');
            case 'auxiliar':
                return redirect()->route('auxiliar.dashboard');
            case 'apoderado':
                return redirect()->route('apoderado.dashboard');
            case 'estudiante':
                return redirect()->route('estudiante.dashboard');
            default:
                return redirect('/home');
        }
    }
}
