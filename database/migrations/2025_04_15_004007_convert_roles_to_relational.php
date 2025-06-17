<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            // Crear roles iniciales
        $roles = ['admin', 'director', 'docente', 'auxiliar', 'apoderado', 'estudiante'];

        foreach ($roles as $role) {
            DB::table('roles')->insert(['nombre' => $role]);
        }

        // Migrar roles existentes
        $users = DB::table('users')->whereNotNull('role')->get();

        foreach ($users as $user) {
            $roleId = DB::table('roles')
                ->where('nombre', $user->role)
                ->value('id');

            if ($roleId) {
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleId
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relational', function (Blueprint $table) {
            //
        });
    }
};
