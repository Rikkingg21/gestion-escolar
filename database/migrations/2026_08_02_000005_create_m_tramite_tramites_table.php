<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_tramite_tramites')) {
            Schema::create('m_tramite_tramites', function (Blueprint $table) {
                $table->id();
                $table->string('codigo_tramite')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tipo_tramite_id')->constrained('m_tramite_tipo_tramites')->cascadeOnDelete();
                $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
                $table->decimal('monto_pagado', 10, 2)->default(0);
                $table->date('fecha_solicitud')->nullable();
                $table->date('fecha_resolucion')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_tramite_tramites');
    }
};
