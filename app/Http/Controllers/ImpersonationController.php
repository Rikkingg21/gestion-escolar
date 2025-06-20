<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $users = User::query();

        // Filtros según el rol del usuario actual
        if ($currentUser->hasRole('admin')) {
            // Admin puede ver todos excepto otros admins
            $users = $users->whereDoesntHave('roles', function($q) {
                $q->where('nombre', 'admin');
            });
        } elseif ($currentUser->hasRole('director')) {
            // Director puede ver docentes, auxiliares, apoderados y estudiantes
            $users = $users->whereHas('roles', function($q) {
                $q->whereIn('nombre', ['docente', 'auxiliar', 'apoderado', 'estudiante']);
            });
        } elseif ($currentUser->hasRole('docente')) {
            // Docente puede ver sus estudiantes
            $users = $users->whereHas('roles', function($q) {
                $q->where('nombre', 'estudiante');
            });
            // Aquí deberías añadir lógica para filtrar solo sus estudiantes
        } elseif ($currentUser->hasRole('apoderado')) {
            // Apoderado puede ver sus estudiantes vinculados
            $students = $currentUser->apoderado->estudiantes;
            $users = $users->whereIn('id', $students->pluck('user_id'));
        } else {
            abort(403);
        }

        return view('auth.impersonate', [
            'users' => $users->with('roles')->get()
        ]);
    }
}
