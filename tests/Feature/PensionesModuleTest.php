<?php

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class PensionesModuleTest extends TestCase
{
    private Role $rol;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearEstructuraMinima();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\PensionesModuleSeeder::class);

        // Módulos base que usan los middlewares (roles=3, colegio=6)
        Module::insert([
            ['id' => 3, 'nombre' => 'Roles', 'ruta_base' => 'role', 'estado' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'nombre' => 'Colegio', 'ruta_base' => 'colegioconfig', 'estado' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $admin = User::create([
            'dni' => '12345678',
            'nombre' => 'Admin',
            'apellido_paterno' => 'Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => '1',
        ]);
        $rolAdmin = Role::where('nombre', 'admin')->firstOrFail();
        $admin->roles()->attach($rolAdmin->id);
        $rolAdmin->modules()->attach([3, 6], ['estado' => '1']);

        $this->rol = Role::where('nombre', 'director')->firstOrFail();

        session(['current_role' => 'admin']);
        $this->actingAs($admin);

        $this->configurarColegio(false, false);
    }

    public function test_pensiones_modules_no_disponibles_cuando_colegio_es_publico(): void
    {
        $response = $this->get(route('role.module', $this->rol->id));

        $response->assertOk();
        $response->assertViewHas('modulesDisponibles', function ($modules) {
            return $modules->pluck('id')->intersect([24, 25])->isEmpty();
        });

        $this->post(route('role.assign-module', $this->rol->id), [
            'module_id' => 24,
            'estado' => '1',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('role_modules', [
            'role_id' => $this->rol->id,
            'module_id' => 24,
        ]);
    }

    public function test_pensiones_modules_disponibles_cuando_colegio_es_privado(): void
    {
        $this->configurarColegio(true, true);

        $response = $this->get(route('role.module', $this->rol->id));

        $response->assertOk();
        $response->assertViewHas('modulesDisponibles', function ($modules) {
            return $modules->pluck('id')->intersect([24, 25])->count() === 2;
        });

        $this->post(route('role.assign-module', $this->rol->id), [
            'module_id' => 25,
            'estado' => '1',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('role_modules', [
            'role_id' => $this->rol->id,
            'module_id' => 25,
        ]);
    }

    public function test_guardar_colegio_como_publico_elimina_asignaciones_pensiones(): void
    {
        $this->configurarColegio(true, true);

        $this->post(route('role.assign-module', $this->rol->id), [
            'module_id' => 24,
            'estado' => '1',
        ]);
        $this->assertDatabaseHas('role_modules', [
            'role_id' => $this->rol->id,
            'module_id' => 24,
        ]);

        $this->put(route('colegioconfig.update', 1), [
            'nombre' => 'Colegio Test',
            'direccion' => 'Av. Test 123',
            'ruc' => '20123456789',
        ]);

        $this->assertDatabaseMissing('role_modules', [
            'role_id' => $this->rol->id,
            'module_id' => 24,
        ]);
    }

    private function configurarColegio(bool $esPrivado, bool $pensiones): void
    {
        $colegio = Colegio::configuracion();
        $colegio->es_privado = $esPrivado;
        $colegio->pensiones_activo = $pensiones;
        $colegio->save();
    }

    private function crearEstructuraMinima(): void
    {
        Schema::dropIfExists('role_modules');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('colegio_config');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('dni', 8)->unique();
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('foto_path')->nullable();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('descripcion')->nullable();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('modules', function ($table) {
            $table->id();
            $table->string('nombre');
            $table->string('icono')->nullable();
            $table->string('ruta_base')->nullable();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('role_modules', function ($table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('colegio_config', function ($table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->text('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('ruc', 11)->nullable();
            $table->string('director_actual')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('es_privado')->default(false);
            $table->boolean('pensiones_activo')->default(false);
            $table->timestamps();
        });
    }
}
