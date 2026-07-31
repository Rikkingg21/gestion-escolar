<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigIeSeeder extends Seeder
{
    public function run()
    {
        DB::table('config_ie')->insert([
            'nombre' => 'Institución Educativa Ejemplo',
            'ruc' => '12345678901',
            'direccion' => 'Av. Ejemplo 123 - Lima',
        ]);
    }
}
