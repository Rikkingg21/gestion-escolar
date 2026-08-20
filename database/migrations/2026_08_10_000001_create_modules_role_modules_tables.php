<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('icono')->nullable();
                $table->string('ruta_base')->nullable();
                $table->string('estado')->default('1');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('role_modules')) {
            Schema::create('role_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->string('estado')->default('1');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_modules');
        Schema::dropIfExists('modules');
    }
};
