<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('roles')->where('nombre', 'alumno')->update(['nombre' => 'estudiante']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('nombre', 'estudiante')->update(['nombre' => 'alumno']);
    }
};
