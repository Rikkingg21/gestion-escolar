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
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('colegio_config', 'es_privado')) {
                Schema::table('colegio_config', function (Blueprint $table) {
                    $table->boolean('es_privado')->default(false)->after('logo_path');
                });
            }

            if (! Schema::hasColumn('colegio_config', 'pensiones_activo')) {
                Schema::table('colegio_config', function (Blueprint $table) {
                    $table->boolean('pensiones_activo')->default(false)->after('es_privado');
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
            $table->dropColumn(['es_privado', 'pensiones_activo']);
        });
    }
};
