<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos generales
        $permissions = [
            // Configuración IE
            'editar-institucion',

            // Usuarios
            'crear-usuarios',
            'editar-usuarios',
            'eliminar-usuarios',
            'ver-usuarios',

            // Grados
            'gestionar-grados',

            // Cursos
            'gestionar-cursos',

            // Notas
            'registrar-notas',
            'ver-notas',
            'ver-notas-hijos'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Asignar permisos a roles
        $admin = Role::findByName('admin');
        $admin->givePermissionTo(Permission::all());

        $director = Role::findByName('director');
        $director->givePermissionTo([
            'editar-institucion',
            'crear-usuarios',
            'editar-usuarios',
            'ver-usuarios',
            'gestionar-grados',
            'gestionar-cursos',
            'ver-notas'
        ]);

        $docente = Role::findByName('docente');
        $docente->givePermissionTo([
            'registrar-notas',
            'ver-notas'
        ]);

        $alumno = Role::findByName('alumno');
        $alumno->givePermissionTo([
            'ver-notas'
        ]);

        $apoderado = Role::findByName('apoderado');
        $apoderado->givePermissionTo([
            'ver-notas-hijos'
        ]);
    }
}
