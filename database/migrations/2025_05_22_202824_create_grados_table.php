<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->enum('nivel', ['primaria', 'secundaria']);
            $table->string('grado', 1);
            $table->string('seccion', 1);
            $table->year('periodo');
            $table->timestamps();

            $table->unique(['nivel', 'grado', 'seccion', 'periodo']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('grados');
    }
};
