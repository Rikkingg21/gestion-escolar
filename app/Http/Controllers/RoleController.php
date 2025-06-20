<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;


class RoleController extends Controller
{
    public function selectRole()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $roles = Role::all();
        } elseif ($user->isDirector()) {
            $roles = Role::where('nombre', '!=', 'admin')->get();
        } else {
            return redirect()->route('home');
        }

        return view('select-role', compact('roles'));
    }

    public function switchRole(Request $request)
    {
        $request->validate(['role_id' => 'required|exists:roles,id']);

        $role = Role::find($request->role_id);
        $request->session()->put('current_role', $role->nombre);
        $request->session()->put('current_role_id', $role->id);

        return redirect()->route('home');
    }
}
