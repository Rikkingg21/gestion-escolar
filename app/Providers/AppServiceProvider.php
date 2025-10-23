<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Models\Colegio;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use App\Services\ModuleService;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Gate::policy(User::class, UserPolicy::class);

        // Directiva Blade para verificar acceso a módulos
        Blade::if('canAccessModule', function ($moduleName) {
            $user = auth()->user();
            if (!$user) return false;

            return Gate::allows('access-module', $moduleName);
        });

        // Directiva Blade para roles (dinámica desde DB)
        Blade::if('hasrole', function ($roles) {
            $currentRole = session('current_role');
            if (!$currentRole) return false;

            $rolesArray = is_array($roles) ? $roles : explode(',', $roles);
            return in_array($currentRole, $rolesArray);
        });

        // Compartir solo módulos a los que el usuario tiene acceso
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $modules = collect();

            if ($user) {
                $modules = ModuleService::getUserModules($user);
            }

            $view->with('sidebarModules', $modules);
        });

        // Compartir configuración del colegio
        View::composer('layouts.app', function ($view) {
            $view->with('colegio', Colegio::configuracion());
        });

    }
}
