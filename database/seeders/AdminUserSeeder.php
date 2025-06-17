<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'dni' => '12345678',
            'nombre' => 'Admin',
            'apellido_paterno' => 'Sistema',
            'apellido_materno' => 'Principal',
            'email' => 'admin@colegio.edu.pe',
            'password' => Hash::make('1234'),
            'role' => 'admin',
            'estado' => 'activo'
        ]);

        $this->command->info('Usuario admin creado exitosamente!');
    }
}
