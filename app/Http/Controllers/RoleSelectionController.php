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
        switch ($role) {
            case 'admin':
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
}
