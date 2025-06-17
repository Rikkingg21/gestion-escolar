<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->char('grado', 2); // Ej: '1', '2', '3A'
            $table->char('seccion', 2); // Ej: 'A', 'B'
            $table->string('nivel', 50); // 'Primaria', 'Secundaria'
            $table->timestamps(); // created_at y updated_at

            // Para evitar duplicados
            $table->unique(['grado', 'seccion', 'nivel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grados');
    }
};
