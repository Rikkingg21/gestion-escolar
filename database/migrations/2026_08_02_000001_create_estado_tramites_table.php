<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('estado_tramites')) {
            Schema::create('estado_tramites', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('color')->nullable();
                $table->integer('orden')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('estado_tramites');
    }
};
