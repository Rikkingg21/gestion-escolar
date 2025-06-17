<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSelectedRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if ($this->shouldExemptRoute($request)) {
            return $next($request);
        }

        if (!$this->hasValidRole($roles)) {
            return $this->denyAccess($request);
        }

        return $next($request);
    }

    protected function shouldExemptRoute(Request $request): bool
    {
        return $request->routeIs(['role.*', 'logout', 'home']);
    }

    protected function hasValidRole(array $requiredRoles): bool
    {
        $currentRole = session('current_role');
        return $currentRole && (empty($requiredRoles) || in_array($currentRole, $requiredRoles));
    }

    protected function denyAccess(Request $request)
    {
        return $request->expectsJson()
            ? abort(403)
            : redirect()->route('role.selection');
    }
}
