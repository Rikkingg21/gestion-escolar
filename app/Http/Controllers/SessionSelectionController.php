<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SessionSelectionController extends Controller
{
    public function showSessionSelection()
    {
        // Usa el usuario principal guardado en la sesión
        $user = session('sessionmain');

        // Si no hay usuario principal, redirige al login
        if (!$user) {
            return redirect()->route('login')->withErrors('Sesión principal no encontrada.');
        }

        // Ahora usa $user para la lógica de roles
        if ($user->hasRole('admin')) {
            $usuarios = User::whereIn('estado', [1, 2])->with('roles')->get();
         } elseif ($user->hasRole('director')) {
            $usuarios = User::where('estado', "1")
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('nombre', 'admin');
                })
                ->with('roles')->get();
        } elseif ($user->hasRole('docente') || $user->hasRole('auxiliar') || $user->hasRole('estudiante')) {
            $usuarios = User::where('id', $user->id)->with('roles')->get();
        } elseif ($user->hasRole('apoderado')) {
            // Aquí tu lógica para apoderado
            $usuarios = collect([$user]);
            // Puedes agregar estudiantes relacionados si lo necesitas
        } else {
            $usuarios = collect([$user]);
        }

        return view('auth.select-session', compact('usuarios'));
    }

    public function selectSessionUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string'
        ]);

        $user = User::findOrFail($request->user_id);

        Auth::login($user);

        // Guarda el rol seleccionado en la sesión
        session(['current_role' => $request->role]);

        // Redirige a la vista principal con la sub-sesión activa a admin.dashboard si el rol es admin, rol director a director.dashboard, etc.
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('director')) {
            return redirect()->route('director.dashboard');
        } elseif ($user->hasRole('docente')) {
            return redirect()->route('docente.dashboard');
        } elseif ($user->hasRole('auxiliar')) {
            return redirect()->route('auxiliar.dashboard');
        } elseif ($user->hasRole('estudiante')) {
            return redirect()->route('estudiante.dashboard');
        } elseif ($user->hasRole('apoderado')) {
            return redirect()->route('apoderado.dashboard');
        }
    }
}
