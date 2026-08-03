<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_tramite_pago_registros')) {
            Schema::create('m_tramite_pago_registros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tramite_id')->constrained('m_tramite_tramites')->cascadeOnDelete();
                $table->foreignId('pago_comprobante_id')->nullable()->constrained('m_tramite_pago_comprobantes')->nullOnDelete();
                $table->foreignId('estado_pago_id')->constrained('estado_pagos')->cascadeOnDelete();
                $table->decimal('monto', 10, 2)->default(0);
                $table->dateTime('fecha_registro')->nullable();
                $table->text('observacion')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_tramite_pago_registros');
    }
};
