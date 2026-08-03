<?php

namespace App\Http\Controllers;

use App\Models\Apoderado;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionSelectionController extends Controller
{
    public function showSessionSelection()
    {
        // Usa el usuario principal guardado en la sesión
        $sessionMain = session('sessionmain');

        // Si no hay usuario principal, redirige al login
        if (! $sessionMain) {
            return redirect()->route('login')->withErrors('Sesión principal no encontrada.');
        }

        $user = $sessionMain instanceof User ? $sessionMain : User::find($sessionMain);

        if (! $user) {
            return redirect()->route('login')->withErrors('Sesión principal no encontrada.');
        }

        $usuarios = $this->usuariosPermitidos($user);

        return view('auth.select-session', compact('usuarios'));
    }

    public function selectSessionUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        $sessionMain = session('sessionmain');

        if (! $sessionMain) {
            return redirect()->route('login')->withErrors('Sesión principal no encontrada.');
        }

        $mainUser = $sessionMain instanceof User ? $sessionMain : User::find($sessionMain);

        if (! $mainUser) {
            return redirect()->route('login')->withErrors('Sesión principal no encontrada.');
        }

        // Validar que el usuario principal tenga permiso de seleccionar al usuario objetivo
        $usuariosPermitidosIds = $this->usuariosPermitidos($mainUser)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! in_array((int) $request->user_id, $usuariosPermitidosIds)) {
            abort(403, 'No tienes permiso para seleccionar este usuario.');
        }

        $user = User::with('roles')->findOrFail($request->user_id);

        // El rol seleccionado debe ser un rol real del usuario objetivo
        $rolesUsuario = $user->roles->pluck('nombre')->all();

        if (! in_array($request->role, $rolesUsuario)) {
            return back()->withErrors(['role' => 'El rol seleccionado no corresponde a este usuario.']);
        }

        Auth::login($user);

        // Guarda el rol seleccionado en la sesión
        session(['current_role' => $request->role]);
        session(['sub_session_user_id' => $user->id]);

        // Redirige a la vista principal con la sub-sesión activa a admin.dashboard si el rol es admin, rol director a director.dashboard, etc.
        return redirect()->route('dashboard.index');
    }

    private function usuariosPermitidos(User $user)
    {
        if ($user->hasRole('admin')) {
            // Un administrador puede ver todos los usuarios (activos, lectores, inactivos)
            return User::with('roles')->get();
        }

        if ($user->hasRole('director')) {
            // Un director ve todos los usuarios excepto administradores, independientemente de su estado
            return User::whereDoesntHave('roles', function ($q) {
                $q->where('nombre', 'admin');
            })
                ->with('roles')->get();
        }

        if ($user->hasRole('apoderado')) {
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

                return $usuarios;
            }

            // Si no se encuentra el apoderado, solo mostrar el usuario actual
            return collect([$user->load('roles')]);
        }

        // Docentes, auxiliares, estudiantes y cualquier otro rol solo se ven a sí mismos
        return collect([$user->load('roles')]);
    }
}
