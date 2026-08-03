<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Colegio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login', [
            'colegio' => Colegio::configuracion(),
        ]);
    }
    // verificar si hay alguna sessión activa en el dispositivo, si hay que regrese al ultimo enlace que estaba

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nombre_usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            // Regenerar la sesión y guardar el id del usuario principal
            $request->session()->regenerate();
            $request->session()->put('sessionmain', Auth::id());

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
        // Volver a la sesión principal y limpiar la sub-sesión
        $sessionMain = session('sessionmain');
        $mainUser = $sessionMain instanceof User ? $sessionMain : ($sessionMain ? User::find($sessionMain) : null);

        if ($mainUser) {
            Auth::login($mainUser);
        }

        $request->session()->forget(['sub_session_user_id', 'current_role']);

        return redirect()->route('session.selection');
    }
}
