<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maya_curso_grado_sec_niv_anios', function (Blueprint $table) {
            $table->id();
            $table->string('docente_designado_id')->nullable(false);
            $table->string('grado_id')->nullable();
            $table->string('materia_id')->nullable();
            $table->string('anio')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maya_curso_grado_sec_niv_anios');
    }
};
