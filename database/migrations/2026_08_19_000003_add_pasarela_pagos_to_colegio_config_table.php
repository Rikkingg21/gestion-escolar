<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('colegio_config')) {
            Schema::create('colegio_config', function (Blueprint $table) {
                $table->id();
                $table->string('nombre')->nullable();
                $table->text('direccion')->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->string('ruc', 11)->nullable();
                $table->string('director_actual')->nullable();
                $table->string('logo_path')->nullable();
                $table->boolean('es_privado')->default(false);
                $table->boolean('pensiones_activo')->default(false);
                $table->boolean('usa_pasarela_pagos')->default(false);
                $table->boolean('culqi_modo_prueba')->default(true);
                $table->string('culqi_public_key')->nullable();
                $table->string('culqi_secret_key')->nullable();
                $table->timestamps();
            });
        } else {
            $columnas = [
                'usa_pasarela_pagos' => ['boolean', false],
                'culqi_modo_prueba' => ['boolean', true],
                'culqi_public_key' => ['string', null],
                'culqi_secret_key' => ['string', null],
            ];

            foreach ($columnas as $columna => [$tipo, $default]) {
                if (Schema::hasColumn('colegio_config', $columna)) {
                    continue;
                }

                Schema::table('colegio_config', function (Blueprint $table) use ($columna, $tipo, $default) {
                    if ($tipo === 'boolean') {
                        $table->boolean($columna)->default($default);
                    } else {
                        $table->string($columna)->nullable();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('colegio_config')) {
            return;
        }

        Schema::table('colegio_config', function (Blueprint $table) {
            $table->dropColumn([
                'usa_pasarela_pagos',
                'culqi_modo_prueba',
                'culqi_public_key',
                'culqi_secret_key',
            ]);
        });
    }
};
