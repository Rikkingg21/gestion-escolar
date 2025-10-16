<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;

class ModuleService
{
    public static function getActiveModules()
    {
        if (!Auth::check()) {
            return collect();
        }

        $currentRole = session('current_role');

        if (!$currentRole) {
            return collect();
        }

        // Buscar el rol por nombre
        $role = Role::where('nombre', $currentRole)->first();

        if (!$role) {
            return collect();
        }

        // Obtener módulos activos asignados al rol
        return $role->modules()
            ->wherePivot('estado', '1')
            ->where('modules.estado', '1')
            ->orderBy('modules.nombre')
            ->get();
    }

    public static function hasAccessToModule($moduleRoute)
    {
        $modules = self::getActiveModules();

        return $modules->contains(function ($module) use ($moduleRoute) {
            return $module->ruta_base === $moduleRoute ||
                   str_starts_with($moduleRoute, $module->ruta_base);
        });
    }
}
