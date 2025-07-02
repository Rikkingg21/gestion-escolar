<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }
    //verificar si hay alguna sessión activa en el dispositivo, si hay que regrese al ultimo enlace que estaba

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nombre_usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            // Regenerar la sesión y guardar el usuario en 'sessionmain'
            $request->session()->regenerate();
            $request->session()->put('sessionmain', Auth::user());

            return redirect()->route('session.selection');
        }

        return back()->withErrors([
            'nombre_usuario' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
    public function logout_sub(Request $request)
    {
        // Solo elimina la sesión secundaria
        $request->session()->forget('sub_session');

        return redirect()->route('session.selection');
    }
}
