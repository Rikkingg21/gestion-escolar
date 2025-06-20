<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // Definir gates para roles
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // Definir gates para cada rol
        $roles = ['admin', 'director', 'docente', 'auxiliar', 'apoderado', 'estudiante'];
        foreach ($roles as $role) {
            Gate::define($role, function ($user) use ($role) {
                return $user->hasRole($role);
            });
        }
    }
}
