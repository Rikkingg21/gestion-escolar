<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AulaVirtualModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        DB::table('modules')->insertOrIgnore([
            [
                'id' => 22,
                'nombre' => 'Aula Virtual Docente',
                'icono' => 'bi-camera-video',
                'ruta_base' => 'aula-virtual-docente',
                'estado' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 23,
                'nombre' => 'Aula Virtual Estudiante',
                'icono' => 'bi-play-btn',
                'ruta_base' => 'aula-virtual-estudiante',
                'estado' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        if (! Schema::hasTable('role_modules') || ! Schema::hasTable('roles')) {
            return;
        }

        $roles = DB::table('roles')->get(['id', 'nombre'])->keyBy('nombre')->map->id;

        $asignaciones = [
            'admin' => [22, 23],
            'director' => [22],
            'docente' => [22],
            'estudiante' => [23],
        ];

        foreach ($asignaciones as $nombreRol => $moduleIds) {
            $roleId = $roles->get($nombreRol);

            if (! $roleId) {
                continue;
            }

            foreach ($moduleIds as $moduleId) {
                DB::table('role_modules')->insertOrIgnore([
                    'role_id' => $roleId,
                    'module_id' => $moduleId,
                    'estado' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
