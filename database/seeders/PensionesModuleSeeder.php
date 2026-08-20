<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PensionesModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        DB::table('modules')->insertOrIgnore([
            [
                'id' => 24,
                'nombre' => 'Pensiones Admin',
                'icono' => 'bi-cash-coin',
                'ruta_base' => 'pensiones-admin',
                'estado' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 25,
                'nombre' => 'Pensiones',
                'icono' => 'bi-cash-stack',
                'ruta_base' => 'pensiones',
                'estado' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
