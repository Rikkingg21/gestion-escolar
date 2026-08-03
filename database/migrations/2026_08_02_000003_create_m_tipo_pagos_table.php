<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_tipo_pagos')) {
            Schema::create('m_tipo_pagos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('categoria')->nullable();
                $table->string('entidad_financiera')->nullable();
                $table->string('numero_cuenta')->nullable();
                $table->string('cci')->nullable();
                $table->string('titular_cuenta')->nullable();
                $table->string('numero_celular')->nullable();
                $table->boolean('requiere_verificacion')->default(false);
                $table->string('color_hex')->nullable();
                $table->string('estado')->default('1');
                $table->string('es_efectivo')->default('0');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_tipo_pagos');
    }
};
