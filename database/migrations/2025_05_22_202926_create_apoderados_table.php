<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apoderados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['activo', 'de baja'])->default('activo');
            $table->foreignId('id_alumno')->constrained('alumnos');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('apoderados');
    }
};
