<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Colegio;

class AdminController extends Controller
{
    public function dashboard()
    {
        $currentRole = session('current_role');
        $user = auth()->user();

        $colegio = Colegio::configuracion();

        $counts = [
            'students' => User::whereHas('roles', fn($q) => $q->where('nombre', 'estudiante'))->count(),
            'teachers' => User::whereHas('roles', fn($q) => $q->where('nombre', 'docente'))->count(),
            'admins' => User::whereHas('roles', fn($q) => $q->where('nombre', 'admin'))->count(),
        ];

        // Lógica para obtener usuarios según rol
        $query = User::query()->with('roles');

        if ($currentRole === 'admin') {
            // Admin ve todos los usuarios
            $latestUsers = $query->latest()->take(5)->get();
        } elseif ($currentRole === 'director') {
            // Director ve todos excepto admins
            $latestUsers = $query->whereDoesntHave('roles', fn($q) => $q->where('nombre', 'admin'))
                              ->latest()
                              ->take(5)
                              ->get();
        } else {
            // Otros roles solo ven su propio usuario
            $latestUsers = $query->where('id', $user->id)->get();
        }

        $counts['latestUsers'] = $latestUsers;

        return view('admin.dashboard', compact('counts', 'colegio'));
    }
}
