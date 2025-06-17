<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->foreignId('rol_id')->constrained('roles');
            $table->enum('estado', ['activo', 'de baja', 'inactivo'])->default('activo');
            $table->string('a_paterno', 50);
            $table->string('a_materno', 50);
            $table->string('nombres', 100);
            $table->string('DNI', 9)->unique();
            $table->date('f_nacimiento')->nullable();
            $table->string('correo', 100)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Insertar usuario administrador inicial
        DB::table('users')->insert([
            'username' => 'admin',
            'password' => bcrypt('Admin123'),
            'rol_id' => 1,
            'estado' => 'activo',
            'a_paterno' => 'Admin',
            'a_materno' => 'Sistema',
            'nombres' => 'Usuario',
            'DNI' => '00000000',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
