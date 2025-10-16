<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ModuleService
{
    public static function getActiveModules()
    {
        if (!Auth::check()) {
            return collect();
        }

        $currentRole = Session::get('current_role');

        if (!$currentRole) {
            return collect();
        }

        // Buscar el rol por nombre
        $role = Role::where('nombre', $currentRole)->first();

        if (!$role) {
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
            $module->has_special_route = ModuleRouteService::hasSpecialRoute($module->nombre);
            return $module;
        });
    }

    public static function hasAccessToModule($moduleRoute)
    {
        $modules = self::getActiveModules();

        return $modules->contains(function ($module) use ($moduleRoute) {
            return $module->ruta_base === $moduleRoute ||
                   str_starts_with($moduleRoute, $module->ruta_base);
        });
    }

    /**
     * Obtiene módulos excluyendo algunos por nombre
     */
    public static function getFilteredModules($excludeNames = [])
    {
        $modules = self::getActiveModules();

        return $modules->filter(function ($module) use ($excludeNames) {
            return !in_array($module->nombre, $excludeNames);
        });
    }
}
