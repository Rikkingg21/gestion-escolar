<?php

namespace App\Providers;

use App\Models\Colegio;
use App\Models\Matricula;
use App\Models\User;
use App\Observers\MatriculaObserver;
use App\Policies\UserPolicy;
use App\Services\ModuleService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Matricula::observe(MatriculaObserver::class);

        Gate::policy(User::class, UserPolicy::class);

        // Directiva Blade para verificar acceso a módulos
        Blade::if('canAccessModule', function ($moduleId) {
            $user = auth()->user();
            if (! $user) {
                return false;
            }

            return $user->canAccessModule($moduleId);
        });

        // Directiva Blade para roles (dinámica desde DB)
        Blade::if('hasrole', function ($roles) {
            $currentRole = session('current_role');
            if (! $currentRole) {
                return false;
            }

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

        // Compartir la sesión principal resuelta (sessionmain puede guardar un ID o un modelo)
        View::composer('*', function ($view) {
            $sessionMain = session('sessionmain');
            $sessionMainUser = null;

            if ($sessionMain instanceof User) {
                $sessionMainUser = $sessionMain;
            } elseif ($sessionMain) {
                $sessionMainUser = User::find($sessionMain);
            }

            $view->with('sessionMainUser', $sessionMainUser);
        });

        // Compartir configuración del colegio
        View::composer('layouts.app', function ($view) {
            $view->with('colegio', Colegio::configuracion());
        });

    }
}
