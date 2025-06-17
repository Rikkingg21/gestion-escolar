<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            ['nombre' => 'admin'],
            ['nombre' => 'director'],
            ['nombre' => 'auxiliar'],
            ['nombre' => 'docente'],
            ['nombre' => 'alumno'],
            ['nombre' => 'apoderado']
        ]);
    }
}
