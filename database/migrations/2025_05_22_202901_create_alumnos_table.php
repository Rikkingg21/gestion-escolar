<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['activo', 'de baja'])->default('activo');
            $table->foreignId('grado_id')->constrained('grados');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
