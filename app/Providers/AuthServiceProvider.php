<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Role;
use App\Models\Module;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // Gate para verificar acceso a módulos específicos
        Gate::define('access-module', function ($user, $moduleName) {
            $currentRole = session('current_role');

            if (!$currentRole) {
                return false;
            }

            // Buscar el rol en la base de datos
            $role = Role::where('nombre', $currentRole)
                        ->where('estado', '1') // Rol activo
                        ->first();

            if (!$role) {
                return false;
            }

            // Verificar si el rol tiene acceso al módulo
            return $role->modules()
                ->where('modules.nombre', $moduleName)
                ->where('modules.estado', '1') // Módulo activo
                ->where('role_modules.estado', '1') // Asignación activa
                ->exists();
        });

        // Gates dinámicos para cada rol en la base de datos
        $roles = Role::where('estado', '1')->get();

        foreach ($roles as $role) {
            Gate::define($role->nombre, function ($user) use ($role) {
                return $user->hasRole($role->nombre);
            });
        }
        /*
        // Definir gates para cada rol
        $roles = ['admin', 'director', 'docente', 'auxiliar', 'apoderado', 'estudiante'];
        foreach ($roles as $role) {
            Gate::define($role, function ($user) use ($role) {
                return $user->hasRole($role);
            });
        }
            */
    }
}
