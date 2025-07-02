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

        $usuarios = collect(); // Inicializamos una colección vacía

        // Ahora usa $user para la lógica de roles para obtener los usuarios PERMITIDOS
        if ($user->hasRole('admin')) {
            // Un administrador puede ver todos los usuarios (activos, lectores, inactivos)
            $usuarios = User::with('roles')->get();
        } elseif ($user->hasRole('director')) {
            // Un director ve todos los usuarios excepto administradores, independientemente de su estado
            $usuarios = User::whereDoesntHave('roles', function ($q) {
                    $q->where('nombre', 'admin');
                })
                ->with('roles')->get();
        } elseif ($user->hasRole('docente') || $user->hasRole('auxiliar') || $user->hasRole('estudiante') || $user->hasRole('apoderado')) {
            // Docentes, auxiliares, estudiantes, apoderados solo se ven a sí mismos.
            // La visibilidad de su propio estado (activo/inactivo/lector) se manejará en la vista.
            $usuarios = collect([$user->load('roles')]);
            // Para apoderado, si necesitas agregar estudiantes relacionados, la lógica iría aquí.
            // Ejemplo: $usuarios = $usuarios->merge($user->relatedStudents()->with('roles')->get());
        } else {
            // Para cualquier otro rol, solo se ve a sí mismo
            $usuarios = collect([$user->load('roles')]);
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
