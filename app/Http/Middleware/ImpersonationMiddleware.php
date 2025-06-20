<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
