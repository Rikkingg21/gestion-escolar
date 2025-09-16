<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Apoderado;
use App\Models\Estudiante;
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
        } elseif ($user->hasRole('apoderado')) {
            // Apoderado: obtener todos los estudiantes asociados a este apoderado
            $apoderado = Apoderado::where('user_id', $user->id)->first();

            if ($apoderado) {
                // Obtener todos los estudiantes asociados a este apoderado
                $estudiantes = Estudiante::where('apoderado_id', $apoderado->id)
                    ->with('user.roles')
                    ->get();

                // Extraer los usuarios de los estudiantes
                $usuarios = $estudiantes->map(function ($estudiante) {
                    return $estudiante->user;
                });

                // También agregar al propio apoderado a la lista
                $usuarios->prepend($user->load('roles'));
            } else {
                // Si no se encuentra el apoderado, solo mostrar el usuario actual
                $usuarios = collect([$user->load('roles')]);
            }
        } elseif ($user->hasRole('docente') || $user->hasRole('auxiliar') || $user->hasRole('estudiante')) {
            // Docentes, auxiliares, estudiantes solo se ven a sí mismos
            $usuarios = collect([$user->load('roles')]);
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
