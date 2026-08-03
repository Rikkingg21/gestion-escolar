<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_tramite_tipo_tramites')) {
            Schema::create('m_tramite_tipo_tramites', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('codigo')->nullable()->unique();
                $table->text('descripcion')->nullable();
                $table->decimal('costo', 10, 2)->default(0);
                $table->boolean('requiere_pago')->default(false);
                $table->boolean('requiere_documentos')->default(false);
                $table->integer('tiempo_estimado_dias')->nullable();
                $table->string('estado')->default('1');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_tramite_tipo_tramites');
    }
};
