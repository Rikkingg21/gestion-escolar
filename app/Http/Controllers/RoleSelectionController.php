<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSelectionController extends Controller
{
    public function showRoleSelection()
    {
        return view('auth.select-role');
    }
    public function selectRole(Request $request)
    {
        $request->validate([
            'selected_role' => 'required|exists:roles,id'
        ]);

        $selectedRole = Auth::user()->roles()
            ->where('roles.id', $request->selected_role)
            ->firstOrFail();

        // Guardar el rol seleccionado en la sesión
        session(['current_role' => $selectedRole->nombre]);
        session(['current_role_id' => $selectedRole->id]);

        return $this->redirectToRole($selectedRole->nombre);
    }
    protected function redirectToRole($role)
    {
        return redirect()->route('dashboard.index');
    }
}
