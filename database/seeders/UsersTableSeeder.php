<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'username' => 'admin',
            'password' => Hash::make('Admin123'),
            'rol_id' => 1,
            'estado' => 'activo',
            'a_paterno' => 'Admin',
            'a_materno' => 'Sistema',
            'nombres' => 'Usuario',
            'DNI' => '00000000'
        ]);
    }
}
