<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
        {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
                $table->enum('estado', ['activo', 'de baja'])->default('activo');
                $table->timestamps();
            });

            // Insertar admin inicial
            DB::table('admins')->insert([
                'id_user' => 1,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
