<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        return view ('nota.index');
    }
}
