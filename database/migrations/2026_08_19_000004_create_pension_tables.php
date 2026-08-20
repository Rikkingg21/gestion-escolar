<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pension_configs')) {
            Schema::create('pension_configs', function (Blueprint $table) {
                $table->id();
                $table->integer('periodo_id');
                $table->integer('grado_id');
                $table->string('estado')->default('1');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('periodo_id')->references('id')->on('periodos')->cascadeOnDelete();
                $table->foreign('grado_id')->references('id')->on('grados')->cascadeOnDelete();
                $table->unique(['periodo_id', 'grado_id']);
            });
        }

        if (! Schema::hasTable('pension_config_cuotas')) {
            Schema::create('pension_config_cuotas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pension_config_id')->constrained('pension_configs')->cascadeOnDelete();
                $table->string('concepto');
                $table->unsignedTinyInteger('mes')->nullable();
                $table->unsignedSmallInteger('anio')->nullable();
                $table->date('fecha_vencimiento');
                $table->bigInteger('monto')->default(0)->comment('Monto en céntimos');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pensiones')) {
            Schema::create('pensiones', function (Blueprint $table) {
                $table->id();
                $table->integer('matricula_id');
                $table->foreignId('config_cuota_id')->nullable()->constrained('pension_config_cuotas')->nullOnDelete();
                $table->string('concepto');
                $table->unsignedTinyInteger('mes')->nullable();
                $table->unsignedSmallInteger('anio')->nullable();
                $table->date('fecha_vencimiento');
                $table->bigInteger('monto')->default(0)->comment('Monto en céntimos');
                $table->bigInteger('monto_pagado')->default(0)->comment('Monto pagado en céntimos');
                $table->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('matricula_id')->references('id')->on('matriculas')->cascadeOnDelete();
                $table->index(['matricula_id', 'estado']);
                $table->index(['fecha_vencimiento', 'estado']);
            });
        }

        if (! Schema::hasTable('pension_pagos')) {
            Schema::create('pension_pagos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pension_id')->constrained('pensiones')->cascadeOnDelete();
                $table->integer('user_id')->nullable();
                $table->integer('metodo_pago_id')->nullable();
                $table->string('numero_operacion')->nullable();
                $table->bigInteger('monto')->default(0)->comment('Monto en céntimos');
                $table->dateTime('fecha_pago')->nullable();
                $table->string('comprobante_path')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('metodo_pago_id')->references('id')->on('m_tipo_pagos')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('pension_pago_registros')) {
            Schema::create('pension_pago_registros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pension_id')->constrained('pensiones')->cascadeOnDelete();
                $table->foreignId('pago_id')->nullable()->constrained('pension_pagos')->nullOnDelete();
                $table->foreignId('estado_pago_id')->nullable()->constrained('estado_pagos')->nullOnDelete();
                $table->bigInteger('monto')->default(0)->comment('Monto en céntimos');
                $table->dateTime('fecha_registro')->nullable();
                $table->text('observacion')->nullable();
                $table->integer('user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['pension_id', 'estado_pago_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pension_pago_registros');
        Schema::dropIfExists('pension_pagos');
        Schema::dropIfExists('pensiones');
        Schema::dropIfExists('pension_config_cuotas');
        Schema::dropIfExists('pension_configs');
    }
};
