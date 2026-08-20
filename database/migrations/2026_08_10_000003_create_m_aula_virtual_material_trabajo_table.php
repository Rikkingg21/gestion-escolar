<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_aula_virtual_material_trabajo')) {
            Schema::create('m_aula_virtual_material_trabajo', function (Blueprint $table) {
                $table->id();
                $table->integer('docente_id')->unique();
                $table->text('enlace_google_drive')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_aula_virtual_material_trabajo');
    }
};
