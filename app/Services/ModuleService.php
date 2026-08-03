<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ModuleService
{
    public static function getActiveModules()
    {
        if (! Auth::check()) {
            return collect();
        }

        $currentRole = Session::get('current_role');

        if (! $currentRole) {
            return collect();
        }

        // Buscar el rol por nombre
        $role = Role::where('nombre', $currentRole)->first();

        if (! $role) {
            return collect();
        }

        // Obtener módulos activos asignados al rol
        $modules = $role->modules()
            ->wherePivot('estado', '1')
            ->where('modules.estado', '1')
            ->orderBy('modules.nombre')
            ->get();

        // Enriquecer los módulos con información adicional
        return $modules->map(function ($module) {
            $module->custom_route = ModuleRouteService::getModuleRoute($module);
            $module->custom_icon = ModuleRouteService::getModuleIcon($module);

            return $module;
        });
    }

    public static function getUserModules($user)
    {
        return self::getActiveModules();
    }
}
