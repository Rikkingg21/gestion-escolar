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
            return $module;
        });
    }

    public static function getUserModules($user)
    {
        if (!$user) {
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
            return $module;
        });
    }

        public static function hasAccessToCurrentModule()
    {
        $currentRoute = request()->route()->getName();

        // Buscar módulo por ruta base
        $module = Module::where('ruta_base', $currentRoute)
                       ->orWhere('ruta_base', 'like', "%{$currentRoute}%")
                       ->where('estado', '1')
                       ->first();

        if (!$module) {
            return false;
        }

        return self::hasAccessToModule($module->nombre);
    }

    public static function hasAccessToModule($moduleIdentifier)
    {
        $currentRole = Session::get('current_role');

        if (!$currentRole) {
            return false;
        }

        $role = Role::where('nombre', $currentRole)
                   ->where('estado', '1')
                   ->first();

        if (!$role) {
            return false;
        }

        // Buscar por nombre O ruta_base
        return $role->modules()
            ->where(function ($query) use ($moduleIdentifier) {
                $query->where('modules.nombre', $moduleIdentifier)
                      ->orWhere('modules.ruta_base', $moduleIdentifier);
            })
            ->where('modules.estado', '1')
            ->where('role_modules.estado', '1')
            ->exists();
    }
    public static function getFilteredModules($excludeNames = [])
    {
        $modules = self::getActiveModules();

        return $modules->filter(function ($module) use ($excludeNames) {
            return !in_array($module->nombre, $excludeNames);
        });
    }
}
