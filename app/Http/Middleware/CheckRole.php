<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!in_array(session('current_role'), $roles)) {
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        return $next($request);
    }
}
