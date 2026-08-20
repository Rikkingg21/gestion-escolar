<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AulaVirtualRoutesTest extends TestCase
{
    public function test_aula_virtual_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('aula-virtual-docente.index'));
        $this->assertTrue(Route::has('aula-virtual-docente.create'));
        $this->assertTrue(Route::has('aula-virtual-docente.store'));
        $this->assertTrue(Route::has('aula-virtual-docente.edit'));
        $this->assertTrue(Route::has('aula-virtual-docente.update'));
        $this->assertTrue(Route::has('aula-virtual-docente.destroy'));
        $this->assertTrue(Route::has('aula-virtual-docente.material'));
        $this->assertTrue(Route::has('aula-virtual-estudiante.index'));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('aula-virtual-docente.index'))->assertRedirect(route('index'));
        $this->get(route('aula-virtual-estudiante.index'))->assertRedirect(route('index'));
    }

    public function test_aula_virtual_module_seeder_is_safe_without_modules_table(): void
    {
        if (Schema::hasTable('modules')) {
            $this->markTestSkipped('La tabla modules ya existe en el entorno.');
        }

        $this->seed(\Database\Seeders\AulaVirtualModuleSeeder::class);

        $this->assertFalse(Schema::hasTable('modules'));
    }
}
