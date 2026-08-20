<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLAS = [
        'm_tramite_tipo_tramites' => 'costo',
        'm_tramite_tramites' => 'monto_pagado',
        'm_tramite_pago_comprobantes' => 'monto',
        'm_tramite_pago_registros' => 'monto',
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla => $columna) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            // Solo convierte si aún es decimal (BD creada manualmente). En instalaciones
            // frescas la migración original ya crea la columna como bigint.
            if (Schema::getColumnType($tabla, $columna) !== 'decimal') {
                continue;
            }

            // Backfill: soles -> céntimos
            DB::table($tabla)->update([$columna => DB::raw("ROUND({$columna} * 100)")]);

            Schema::table($tabla, function (Blueprint $table) use ($columna) {
                $table->bigInteger($columna)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla => $columna) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            if (Schema::getColumnType($tabla, $columna) === 'bigint') {
                DB::table($tabla)->update([$columna => DB::raw("ROUND({$columna} / 100)")]);

                Schema::table($tabla, function (Blueprint $table) use ($columna) {
                    $table->decimal($columna, 10, 2)->default(0)->change();
                });
            }
        }
    }
};
