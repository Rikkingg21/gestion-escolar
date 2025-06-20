<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'dni' => 'required|string',
            'password' => 'required|string',
        ]);

        // Buscar usuario por DNI
        $user = User::where('dni', $credentials['dni'])->first();

        if (!$user) {
            return back()->withErrors(['dni' => 'Credenciales incorrectas']);
        }

        // Verificar contraseña
        if (password_verify($credentials['password'], $user->password)) {
            Auth::login($user);

            // Si es admin, redirigir a selector de roles
            if ($user->hasRole('admin')) {
                return redirect()->route('select-role');
            }

            // Redirigir según el primer rol
            return $this->redirectToRole($user);
        }

        return back()->withErrors(['dni' => 'Credenciales incorrectas']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function showRoleSelector()
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403);
        }

        return view('auth.role-selector');
    }

    public function switchRole(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $role = $request->input('role');

        // Validar que el rol existe
        $validRoles = ['admin', 'director', 'docente', 'auxiliar', 'apoderado', 'estudiante'];
        if (!in_array($role, $validRoles)) {
            return back()->withErrors(['role' => 'Rol inválido']);
        }

        // Redirigir según el rol seleccionado
        return redirect()->route("$role.dashboard");
    }

    protected function redirectToRole($user)
    {
        $roles = $user->roles->pluck('nombre')->toArray();

        if (in_array('admin', $roles)) {
            return redirect()->route('select-role');
        } elseif (in_array('director', $roles)) {
            return redirect()->route('director.dashboard');
        } elseif (in_array('docente', $roles)) {
            return redirect()->route('docente.dashboard');
        } elseif (in_array('auxiliar', $roles)) {
            return redirect()->route('auxiliar.dashboard');
        } elseif (in_array('apoderado', $roles)) {
            return redirect()->route('apoderado.dashboard');
        } elseif (in_array('estudiante', $roles)) {
            return redirect()->route('estudiante.dashboard');
        }

        return redirect('/');
    }

    public function impersonate(Request $request, User $user)
    {
        // Verificar permisos
        if (!auth()->user()->canImpersonate()) {
            abort(403);
        }

        // Registrar la suplantación
        Impersonation::create([
            'impersonator_id' => auth()->id(),
            'impersonated_id' => $user->id,
            'started_at' => now()
        ]);

        // Iniciar sesión como el otro usuario
        Auth::login($user);

        return $this->redirectToRole($user);
    }

    public function stopImpersonating()
    {
        if (!session()->has('impersonated_by')) {
            abort(403);
        }

        // Registrar fin de suplantación
        $impersonation = Impersonation::find(session('impersonation_id'));
        $impersonation->update(['ended_at' => now()]);

        // Volver al usuario original
        Auth::login(User::find(session('impersonated_by')));

        session()->forget(['impersonated_by', 'impersonation_id']);

        return redirect()->route('dashboard');
    }
}
