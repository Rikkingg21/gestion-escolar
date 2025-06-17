<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nombre_usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();

            return redirect()->route('role.selection');
        }

        return back()->withErrors([
            'nombre_usuario' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
    }
    protected function redirectToRole($role)
    {
        switch ($role) {
            case 'admin':
                return redirect()->route('colegioconfig.edit');
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
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
