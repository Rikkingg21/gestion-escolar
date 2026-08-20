<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_aula_virtual_sesiones')) {
            Schema::create('m_aula_virtual_sesiones', function (Blueprint $table) {
                $table->id();
                $table->integer('curso_grado_sec_niv_anio_id')->index();
                $table->integer('docente_id')->nullable()->index();
                $table->string('titulo')->nullable();
                $table->string('plataforma')->nullable();
                $table->text('enlace');
                $table->date('fecha');
                $table->time('hora');
                $table->text('motivo');
                $table->text('observaciones')->nullable();
                $table->string('estado')->default('1');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_aula_virtual_sesiones');
    }
};
