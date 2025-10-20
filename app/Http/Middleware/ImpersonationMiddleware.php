<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ModuleService;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class ImpersonationMiddleware
{
    public function handle($request, Closure $next)
    {
        if (auth()->check() && session()->has('impersonated_by')) {
            // El usuario actual está siendo suplantado
            view()->share('isImpersonating', true);
            view()->share('impersonator', User::find(session('impersonated_by')));
        } else {
            view()->share('isImpersonating', false);
        }

        return $next($request);
    }
}
/*
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener la ruta actual
        $currentRoute = $request->route()->getName() ?? $request->path();

        // Verificar si el usuario tiene acceso al módulo
        if (!ModuleService::hasAccessToModule($currentRoute)) {
            abort(403, 'No tienes permisos para acceder a este módulo');
        }

        return $next($request);
    }
}
    */
