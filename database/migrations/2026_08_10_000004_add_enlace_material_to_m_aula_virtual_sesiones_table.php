<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('m_aula_virtual_sesiones') && ! Schema::hasColumn('m_aula_virtual_sesiones', 'enlace_material')) {
            Schema::table('m_aula_virtual_sesiones', function (Blueprint $table) {
                $table->text('enlace_material')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('m_aula_virtual_sesiones') && Schema::hasColumn('m_aula_virtual_sesiones', 'enlace_material')) {
            Schema::table('m_aula_virtual_sesiones', function (Blueprint $table) {
                $table->dropColumn('enlace_material');
            });
        }
    }
};
