<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grado_curso', function (Blueprint $table) {
            $table->foreignId('grado_id')->constrained('grados');
            $table->foreignId('curso_id')->constrained('cursos');
            $table->foreignId('docente_id')->nullable()->constrained('docentes');
            $table->timestamps();

            $table->primary(['grado_id', 'curso_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('grado_curso');
    }
};
