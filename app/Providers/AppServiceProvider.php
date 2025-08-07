<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Models\Colegio;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot()
    {
        // Registrar políticas de acceso
        Gate::policy(User::class, UserPolicy::class);

        // Directiva Blade personalizada para roles
        Blade::if('hasrole', function ($roles) {
            $currentRole = session('current_role');
            if (!$currentRole) return false;

            $rolesArray = is_array($roles) ? $roles : explode(',', $roles);
            return in_array($currentRole, $rolesArray);
        });

        // Compartir datos del colegio con el layout app.blade.php
        View::composer('layouts.app', function ($view) {
            $view->with('colegio', Colegio::configuracion());
        });
    }
}
