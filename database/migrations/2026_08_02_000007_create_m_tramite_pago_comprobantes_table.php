<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_tramite_pago_comprobantes')) {
            Schema::create('m_tramite_pago_comprobantes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tramite_id')->constrained('m_tramite_tramites')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('metodo_pago_id')->nullable()->constrained('m_tipo_pagos')->nullOnDelete();
                $table->string('numero_operacion')->nullable();
                $table->decimal('monto', 10, 2)->default(0);
                $table->dateTime('fecha_pago')->nullable();
                $table->string('comprobante_path')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_tramite_pago_comprobantes');
    }
};
