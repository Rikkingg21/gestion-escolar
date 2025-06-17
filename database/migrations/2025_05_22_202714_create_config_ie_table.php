<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
     public function up()
    {
        Schema::create('config_ie', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('ruc', 20)->unique();
            $table->text('direccion');
            $table->string('logo', 255)->nullable();
            $table->foreignId('director_actual')->nullable()->constrained('directores');
            $table->string('telefono', 10)->nullable();
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('config_ie')->insert([
            'nombre' => 'Institución Educativa Ejemplo',
            'ruc' => '12345678901',
            'direccion' => 'Av. Ejemplo 123 - Lima',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    public function down(): void
    {
        Schema::dropIfExists('config_ie');
    }
};
