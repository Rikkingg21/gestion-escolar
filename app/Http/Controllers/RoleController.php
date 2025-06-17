<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function select()
    {
        $user = Auth::user();
        return view('auth.role-select', compact('user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required|exists:roles,nombre'
        ]);

        $role = $request->role;

        // Verificar que el usuario tenga este rol
        if (!Auth::user()->roles()->where('nombre', $role)->exists()) {
            abort(403, 'No tienes acceso a este rol');
        }

        return redirect()->route('dashboard', ['role' => $role]);
    }
}
