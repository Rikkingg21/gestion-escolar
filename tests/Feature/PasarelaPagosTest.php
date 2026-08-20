<?php

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Services\Pagos\CulqiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class PasarelaPagosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->crearEstructuraMinima();

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
        $rolAdmin = Role::create(['nombre' => 'admin', 'estado' => '1']);
        $admin->roles()->attach($rolAdmin->id);
        $rolAdmin->modules()->attach([3, 6], ['estado' => '1']);

        session(['current_role' => 'admin']);
        $this->actingAs($admin);

        Colegio::configuracion();
    }

    public function test_guardar_pasarela_almacena_keys_y_modo_prueba(): void
    {
        $this->put(route('colegioconfig.update', 1), [
            'nombre' => 'Colegio Test',
            'direccion' => 'Av. Test 123',
            'ruc' => '20123456789',
            'usa_pasarela_pagos' => '1',
            'culqi_modo_prueba' => '1',
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('colegio_config', [
            'id' => 1,
            'usa_pasarela_pagos' => true,
            'culqi_modo_prueba' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);
    }

    public function test_secret_key_enmascarada_no_sobrescribe_la_guardada(): void
    {
        $colegio = Colegio::configuracion();
        $colegio->update([
            'usa_pasarela_pagos' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_real',
        ]);

        $this->put(route('colegioconfig.update', 1), [
            'nombre' => 'Colegio Test',
            'direccion' => 'Av. Test 123',
            'ruc' => '20123456789',
            'usa_pasarela_pagos' => '1',
            'culqi_secret_key' => '********',
        ]);

        $this->assertDatabaseHas('colegio_config', [
            'id' => 1,
            'culqi_secret_key' => 'sk_test_real',
        ]);
    }

    public function test_desactivar_pasarela_limpia_las_keys(): void
    {
        $colegio = Colegio::configuracion();
        $colegio->update([
            'usa_pasarela_pagos' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);

        $this->put(route('colegioconfig.update', 1), [
            'nombre' => 'Colegio Test',
            'direccion' => 'Av. Test 123',
            'ruc' => '20123456789',
        ]);

        $this->assertDatabaseHas('colegio_config', [
            'id' => 1,
            'usa_pasarela_pagos' => false,
            'culqi_public_key' => null,
            'culqi_secret_key' => null,
        ]);
    }

    public function test_pasarela_habilitada_solo_con_keys_y_secretos(): void
    {
        $colegio = Colegio::configuracion();
        $colegio->update([
            'usa_pasarela_pagos' => true,
            'culqi_modo_prueba' => true,
        ]);

        $this->assertFalse($colegio->pasarelaHabilitada());

        $colegio->update([
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);

        $this->assertTrue($colegio->pasarelaHabilitada());
        $this->assertTrue($colegio->culqiEnModoPrueba());
    }

    public function test_crear_cargo_envia_monto_en_centimos(): void
    {
        Http::fake([
            'https://api.culqi.com/v2/charges' => Http::response([
                'id' => 'chr_test_123',
                'amount' => '12345',
                'currency_code' => 'PEN',
                'outcome' => ['type' => 'authorized'],
            ], 201),
        ]);

        $colegio = Colegio::configuracion();
        $colegio->update([
            'usa_pasarela_pagos' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);

        $servicio = new CulqiService($colegio);
        $respuesta = $servicio->crearCargo(12345, 'tok_test_1', 'cliente@test.com', 'Constancia de matrícula');

        $this->assertSame('chr_test_123', $respuesta['id']);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->url() === 'https://api.culqi.com/v2/charges'
                && $request->hasHeader('Authorization', 'Bearer sk_test_xyz789')
                && $body['amount'] === '12345'
                && $body['currency_code'] === 'PEN'
                && $body['source_id'] === 'tok_test_1'
                && $body['email'] === 'cliente@test.com';
        });
    }

    public function test_crear_cargo_rechaza_montos_menores_a_un_sol(): void
    {
        $colegio = Colegio::configuracion();
        $colegio->update([
            'usa_pasarela_pagos' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        (new CulqiService($colegio))->crearCargo(99, 'tok_test_1', 'cliente@test.com', 'Test');
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
            $table->boolean('usa_pasarela_pagos')->default(false);
            $table->boolean('culqi_modo_prueba')->default(true);
            $table->string('culqi_public_key')->nullable();
            $table->string('culqi_secret_key')->nullable();
            $table->timestamps();
        });
    }
}
