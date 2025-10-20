<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class ModuleRouteService
{
    /**
     * Obtiene la ruta específica para un módulo según reglas especiales
     */
    public static function getModuleRoute($module)
    {
        $currentRole = Session::get('current_role');

        // Reglas especiales por nombre de módulo
        switch ($module->nombre) {


            case 'Libreta':
                return route('libreta.index', [
                    'anio' => date('Y'),
                    'bimestre' => self::getCurrentBimestre()
                ]);

            default:
                // Para módulos normales, usar la ruta_base
                return url($module->ruta_base);
        }
    }

    /**
     * Obtiene el bimestre actual basado en la fecha
     */
    private static function getCurrentBimestre()
    {
        $month = date('n');

        if ($month >= 1 && $month <= 3) return 1;
        if ($month >= 4 && $month <= 6) return 2;
        if ($month >= 7 && $month <= 9) return 3;
        return 4; // Octubre - Diciembre
    }



    /**
     * Obtiene el icono personalizado si existe
     */
    public static function getModuleIcon($module)
    {
        $iconMap = [
            'Dashboard' => 'bi-speedometer2',
            'Libreta' => 'bi-journal-text',
        ];

        return $iconMap[$module->nombre] ?? $module->icono;
    }
}
