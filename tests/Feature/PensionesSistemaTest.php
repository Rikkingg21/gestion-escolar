<?php

namespace Tests\Feature;

use App\Models\Apoderado;
use App\Models\Colegio;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Metodopago\Tipopago;
use App\Models\Module;
use App\Models\Pension\Pension;
use App\Models\Pension\PensionConfig;
use App\Models\Pension\PensionConfigCuota;
use App\Models\Pension\PensionPago;
use App\Models\Pension\PensionPagoRegistro;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\Tramite\Estadopago;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class PensionesSistemaTest extends TestCase
{
    private User $admin;

    private User $apoderado;

    private Estudiante $estudiante1;

    private Estudiante $estudiante2;

    private Grado $grado;

    private Periodo $periodo;

    private Tipopago $efectivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearEstructuraMinima();
        $this->crearEscuela();
        $this->crearPersonas();
        $this->crearRolesYModulos();
        $this->crearTiposPago();
    }

    public function test_crear_configuracion_genera_cuotas_para_matriculas_existentes(): void
    {
        $this->crearMatricula($this->estudiante1);

        session(['current_role' => 'admin']);
        $this->actingAs($this->admin);

        $this->post(route('pensiones-admin.configuracion.store'), [
            'periodo_id' => $this->periodo->id,
            'grado_id' => [$this->grado->id],
            'cuotas' => [
                ['concepto' => 'Pensión marzo', 'mes' => 3, 'fecha_vencimiento' => '2026-03-05', 'monto' => 350.00],
                ['concepto' => 'Pensión abril', 'mes' => 4, 'fecha_vencimiento' => '2026-04-05', 'monto' => 350.00],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pension_configs', [
            'periodo_id' => $this->periodo->id,
            'grado_id' => $this->grado->id,
        ]);

        $this->assertSame(2, Pension::count());
        $this->assertSame(35000, Pension::first()->monto);
        $this->assertSame('pendiente', Pension::first()->estado);
        $this->assertSame(2026, PensionConfigCuota::first()->anio);
    }

    public function test_crear_configuracion_multigrado_genera_una_config_por_grado(): void
    {
        $grado2 = Grado::create([
            'grado' => '2',
            'seccion' => 'A',
            'nivel' => 'PRIMARIA',
            'estado' => '1',
        ]);

        session(['current_role' => 'admin']);
        $this->actingAs($this->admin);

        $this->post(route('pensiones-admin.configuracion.store'), [
            'periodo_id' => $this->periodo->id,
            'grado_id' => [$this->grado->id, $grado2->id],
            'cuotas' => [
                ['concepto' => 'Pensión marzo', 'mes' => 3, 'fecha_vencimiento' => '2026-03-05', 'monto' => 350.00],
                ['concepto' => 'Pensión abril', 'mes' => 4, 'fecha_vencimiento' => '2026-04-05', 'monto' => 350.00],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, PensionConfig::count());
        $this->assertSame(4, PensionConfigCuota::count());

        foreach (PensionConfig::all() as $config) {
            $this->assertSame($this->periodo->id, $config->periodo_id);
            $this->assertSame(2, $config->cuotas->count());
            $this->assertSame(2026, $config->cuotas->first()->anio);
        }
    }

    public function test_fecha_vencimiento_fuera_del_anio_del_periodo_es_rechazada(): void
    {
        session(['current_role' => 'admin']);
        $this->actingAs($this->admin);

        $this->post(route('pensiones-admin.configuracion.store'), [
            'periodo_id' => $this->periodo->id,
            'grado_id' => [$this->grado->id],
            'cuotas' => [
                ['concepto' => 'Pensión marzo', 'mes' => 3, 'fecha_vencimiento' => '2027-03-05', 'monto' => 350.00],
            ],
        ])->assertSessionHasErrors('cuotas.0.fecha_vencimiento');

        $this->assertSame(0, PensionConfig::count());
    }

    public function test_apoderado_paga_con_tarjeta_cuando_pasarela_habilitada(): void
    {
        $colegio = Colegio::configuracion();
        $colegio->update([
            'es_privado' => true,
            'pensiones_activo' => true,
            'usa_pasarela_pagos' => true,
            'culqi_modo_prueba' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);

        Http::fake([
            'https://api.culqi.com/v2/charges' => Http::response([
                'id' => 'chr_test_123',
                'outcome' => ['type' => 'venta_exitosa', 'code' => 'AUT0000'],
                'amount' => 35000,
            ], 200),
        ]);

        $this->crearConfig();
        $matricula = $this->crearMatricula($this->estudiante1);
        $pension = Pension::where('matricula_id', $matricula->id)->first();

        session(['current_role' => 'apoderado']);
        $this->actingAs($this->apoderado);

        $this->post(route('pensiones.tarjeta', $pension->id), ['token' => 'tok_test_1'])
            ->assertRedirect(route('pensiones.show', $pension->id))
            ->assertSessionHas('success');

        $pension->refresh();
        $this->assertSame(Pension::ESTADO_PAGADO, $pension->estado);
        $this->assertSame($pension->monto, $pension->monto_pagado);

        $pago = PensionPago::where('pension_id', $pension->id)->first();
        $this->assertNotNull($pago);
        $this->assertSame('chr_test_123', $pago->numero_operacion);
        $this->assertSame('Tarjeta (Culqi)', $pago->metodoPago->nombre);

        $registro = PensionPagoRegistro::where('pension_id', $pension->id)->first();
        $this->assertNotNull($registro);
        $this->assertStringContainsString('Aprobado', $registro->estadoPago->nombre);
    }

    public function test_pago_con_tarjeta_rechazado_no_marca_cuota_pagada(): void
    {
        $colegio = Colegio::configuracion();
        $colegio->update([
            'es_privado' => true,
            'pensiones_activo' => true,
            'usa_pasarela_pagos' => true,
            'culqi_modo_prueba' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);

        Http::fake([
            'https://api.culqi.com/v2/charges' => Http::response([
                'id' => 'chr_rejected_1',
                'outcome' => ['code' => 'card_declined'],
            ], 200),
        ]);

        $this->crearConfig();
        $matricula = $this->crearMatricula($this->estudiante1);
        $pension = Pension::where('matricula_id', $matricula->id)->first();

        session(['current_role' => 'apoderado']);
        $this->actingAs($this->apoderado);

        $this->post(route('pensiones.tarjeta', $pension->id), ['token' => 'tok_test_1'])
            ->assertRedirect(route('pensiones.show', $pension->id))
            ->assertSessionHas('error');

        $pension->refresh();
        $this->assertSame(Pension::ESTADO_PENDIENTE, $pension->estado);
        $this->assertSame(0, $pension->monto_pagado);
    }

    public function test_pago_con_tarjeta_sin_pasarela_habilitada_no_procesa(): void
    {
        $this->crearConfig();
        $matricula = $this->crearMatricula($this->estudiante1);
        $pension = Pension::where('matricula_id', $matricula->id)->first();

        session(['current_role' => 'apoderado']);
        $this->actingAs($this->apoderado);

        $this->post(route('pensiones.tarjeta', $pension->id), ['token' => 'tok_test_1'])
            ->assertSessionHas('error');

        $this->assertSame(0, PensionPago::count());
    }

    public function test_apoderado_no_paga_con_tarjeta_pension_de_estudiante_ajeno(): void
    {
        $colegio = Colegio::configuracion();
        $colegio->update([
            'es_privado' => true,
            'pensiones_activo' => true,
            'usa_pasarela_pagos' => true,
            'culqi_modo_prueba' => true,
            'culqi_public_key' => 'pk_test_abc123',
            'culqi_secret_key' => 'sk_test_xyz789',
        ]);

        $this->crearConfig();
        $this->crearMatricula($this->estudiante1);
        $matriculaAjeno = $this->crearMatricula($this->estudiante2);
        $pensionAjeno = Pension::where('matricula_id', $matriculaAjeno->id)->first();

        session(['current_role' => 'apoderado']);
        $this->actingAs($this->apoderado);

        $this->post(route('pensiones.tarjeta', $pensionAjeno->id), ['token' => 'tok_test_1'])
            ->assertForbidden();

        $this->assertSame(0, PensionPago::count());
    }

    public function test_apoderado_solo_ve_periodos_donde_esta_matriculado_su_hijo(): void
    {
        $periodo2 = Periodo::create([
            'nombre' => 'Periodo 2027',
            'estado' => '1',
            'anio' => 2027,
            'tipo_periodo' => 'año escolar',
        ]);

        // El hijo solo está matriculado en el periodo 2026
        $this->crearMatricula($this->estudiante1);

        session(['current_role' => 'apoderado']);
        $this->actingAs($this->apoderado);

        $response = $this->get(route('pensiones.index'));
        $response->assertOk();
        $response->assertViewHas('periodos', function ($periodos) use ($periodo2) {
            return $periodos->pluck('id')->doesntContain($periodo2->id)
                && $periodos->pluck('id')->contains($this->periodo->id);
        });
    }

    public function test_matricula_nueva_genera_cuotas_automaticamente(): void
    {
        $this->crearConfig();

        $matricula = $this->crearMatricula($this->estudiante1);

        $this->assertSame(2, Pension::where('matricula_id', $matricula->id)->count());
    }

    public function test_estado_efectivo_atrasado_pendiente_y_pagado(): void
    {
        $matricula = $this->crearMatricula($this->estudiante1);

        $atrasada = Pension::create([
            'matricula_id' => $matricula->id,
            'concepto' => 'Pensión marzo',
            'fecha_vencimiento' => now()->subDays(5)->toDateString(),
            'monto' => 35000,
            'monto_pagado' => 0,
            'estado' => Pension::ESTADO_PENDIENTE,
        ]);

        $pendiente = Pension::create([
            'matricula_id' => $matricula->id,
            'concepto' => 'Pensión abril',
            'fecha_vencimiento' => now()->addDays(5)->toDateString(),
            'monto' => 35000,
            'monto_pagado' => 0,
            'estado' => Pension::ESTADO_PENDIENTE,
        ]);

        $pagada = Pension::create([
            'matricula_id' => $matricula->id,
            'concepto' => 'Pensión mayo',
            'fecha_vencimiento' => now()->subDays(2)->toDateString(),
            'monto' => 35000,
            'monto_pagado' => 35000,
            'estado' => Pension::ESTADO_PAGADO,
        ]);

        $this->assertSame('atrasado', $atrasada->estado_efectivo);
        $this->assertTrue($atrasada->atrasada);
        $this->assertSame('pendiente', $pendiente->estado_efectivo);
        $this->assertFalse($pendiente->atrasada);
        $this->assertSame('pagado', $pagada->estado_efectivo);

        $this->assertSame(1, Pension::atrasadas()->count());
        $this->assertSame(1, Pension::pagadas()->count());
    }

    public function test_admin_registra_pago_y_marca_cuota_pagada(): void
    {
        $this->crearConfig();
        $matricula = $this->crearMatricula($this->estudiante1);
        $pension = Pension::where('matricula_id', $matricula->id)->first();

        session(['current_role' => 'admin']);
        $this->actingAs($this->admin);

        $this->post(route('pensiones-admin.registrar-pago.store'), [
            'periodo_id' => $this->periodo->id,
            'estudiante_id' => $this->estudiante1->id,
            'pension_id' => $pension->id,
            'metodo_pago_id' => $this->efectivo->id,
            'observaciones' => 'Pago recibido en caja',
        ])->assertSessionHasNoErrors();

        $pension->refresh();

        $this->assertSame(Pension::ESTADO_PAGADO, $pension->estado);
        $this->assertSame($pension->monto, $pension->monto_pagado);

        $pago = PensionPago::where('pension_id', $pension->id)->first();
        $this->assertNotNull($pago);
        $this->assertStringStartsWith('EFECTIVO_', $pago->numero_operacion);
        $this->assertNull($pago->comprobante_path);
        $this->assertSame($pago->metodo_pago_id, $this->efectivo->id);

        $registro = PensionPagoRegistro::where('pension_id', $pension->id)->first();
        $this->assertNotNull($registro);
        $this->assertStringContainsString('Aprobado', $registro->estadoPago->nombre);
    }

    public function test_apoderado_subir_comprobante_queda_en_revision_y_admin_aprueba(): void
    {
        $this->crearConfig();
        $matricula = $this->crearMatricula($this->estudiante1);
        $pension = Pension::where('matricula_id', $matricula->id)->first();

        session(['current_role' => 'apoderado']);
        $this->actingAs($this->apoderado);

        $this->post(route('pensiones.comprobante', $pension->id), [
            'tipo_pago_id' => $this->efectivo->id,
            'observaciones' => 'Pago en efectivo',
        ])->assertSessionHasNoErrors();

        $pension->refresh();
        $this->assertSame(Pension::ESTADO_PENDIENTE, $pension->estado);

        $registro = PensionPagoRegistro::where('pension_id', $pension->id)->first();
        $this->assertNotNull($registro);
        $this->assertStringContainsString('revisión', $registro->estadoPago->nombre);

        // El admin aprueba
        $aprobado = Estadopago::where('nombre', 'LIKE', '%Aprobado%')->first();

        session(['current_role' => 'admin']);
        $this->actingAs($this->admin);

        $this->post(route('pensiones-admin.update-estado-pago', $pension->id), [
            'pago_registro_id' => $registro->id,
            'estado_pago_id' => $aprobado->id,
        ])->assertSessionHasNoErrors();

        $pension->refresh();
        $this->assertSame(Pension::ESTADO_PAGADO, $pension->estado);
        $this->assertSame($pension->monto, $pension->monto_pagado);
    }

    public function test_apoderado_no_accede_a_pension_de_estudiante_ajeno(): void
    {
        $this->crearConfig();

        $matriculaAjeno = $this->crearMatricula($this->estudiante2);
        $pensionAjeno = Pension::where('matricula_id', $matriculaAjeno->id)->first();

        session(['current_role' => 'apoderado']);
        $this->actingAs($this->apoderado);

        $this->get(route('pensiones.show', $pensionAjeno->id))->assertForbidden();

        // El index solo muestra las cuotas de sus hijos
        $response = $this->get(route('pensiones.index'));
        $response->assertOk();
        $response->assertViewHas('pensiones', function ($pensiones) use ($pensionAjeno) {
            return ! $pensiones->pluck('id')->contains($pensionAjeno->id);
        });
    }

    public function test_admin_no_puede_registrar_pago_de_estudiante_no_matriculado(): void
    {
        $this->crearConfig();
        $this->crearMatricula($this->estudiante1);

        $config = PensionConfig::first();
        $configCuota = $config->cuotas->first();

        session(['current_role' => 'admin']);
        $this->actingAs($this->admin);

        // Estudiante 2 no tiene matrícula: se crea una pensión "fantasma" para validar el rechazo
        $pensionFantasma = Pension::create([
            'matricula_id' => $this->crearMatricula($this->estudiante1)->id,
            'config_cuota_id' => $configCuota->id,
            'concepto' => 'Pensión fantasma',
            'fecha_vencimiento' => '2026-03-05',
            'monto' => 35000,
            'monto_pagado' => 0,
            'estado' => Pension::ESTADO_PENDIENTE,
        ]);

        $this->post(route('pensiones-admin.registrar-pago.store'), [
            'periodo_id' => $this->periodo->id,
            'estudiante_id' => $this->estudiante2->id,
            'pension_id' => $pensionFantasma->id,
            'metodo_pago_id' => $this->efectivo->id,
        ])->assertSessionHas('error');

        $pensionFantasma->refresh();
        $this->assertSame(Pension::ESTADO_PENDIENTE, $pensionFantasma->estado);
        $this->assertSame(0, PensionPagoRegistro::count());
    }

    private function crearConfig(): void
    {
        $config = PensionConfig::create([
            'periodo_id' => $this->periodo->id,
            'grado_id' => $this->grado->id,
            'estado' => '1',
        ]);

        PensionConfigCuota::create([
            'pension_config_id' => $config->id,
            'concepto' => 'Pensión marzo',
            'mes' => 3,
            'anio' => 2026,
            'fecha_vencimiento' => '2026-03-05',
            'monto' => 35000,
        ]);

        PensionConfigCuota::create([
            'pension_config_id' => $config->id,
            'concepto' => 'Pensión abril',
            'mes' => 4,
            'anio' => 2026,
            'fecha_vencimiento' => '2026-04-05',
            'monto' => 35000,
        ]);
    }

    private function crearMatricula(Estudiante $estudiante): Matricula
    {
        return Matricula::create([
            'estudiante_id' => $estudiante->id,
            'periodo_id' => $this->periodo->id,
            'grado_id' => $this->grado->id,
            'estado' => '1',
        ]);
    }

    private function crearTiposPago(): void
    {
        $this->efectivo = Tipopago::create([
            'nombre' => 'Efectivo',
            'categoria' => 'efectivo',
            'requiere_verificacion' => false,
            'estado' => '1',
            'es_efectivo' => '1',
        ]);

        Tipopago::create([
            'nombre' => 'Banco Test',
            'categoria' => 'transferencia',
            'entidad_financiera' => 'Banco de Prueba',
            'numero_cuenta' => '1234567890',
            'requiere_verificacion' => true,
            'estado' => '1',
            'es_efectivo' => '0',
        ]);
    }

    private function crearRolesYModulos(): void
    {
        Module::insert([
            ['id' => 24, 'nombre' => 'Pensiones Admin', 'icono' => 'bi-cash-coin', 'ruta_base' => 'pensiones-admin', 'estado' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 25, 'nombre' => 'Pensiones', 'icono' => 'bi-cash-stack', 'ruta_base' => 'pensiones', 'estado' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $rolAdmin = Role::create(['nombre' => 'admin', 'estado' => '1']);
        $rolApoderado = Role::create(['nombre' => 'apoderado', 'estado' => '1']);

        $rolAdmin->modules()->attach(24, ['estado' => '1']);
        $rolApoderado->modules()->attach(25, ['estado' => '1']);

        $this->admin->roles()->attach($rolAdmin->id);
        $this->apoderado->roles()->attach($rolApoderado->id);
    }

    private function crearPersonas(): void
    {
        $this->admin = User::create([
            'dni' => '11111111',
            'nombre' => 'Admin',
            'apellido_paterno' => 'Principal',
            'email' => 'admin@pensiones.test',
            'password' => bcrypt('password'),
            'estado' => '1',
        ]);

        $apoderadoUser = User::create([
            'dni' => '22222222',
            'nombre' => 'Apoderado',
            'apellido_paterno' => 'Padre',
            'apellido_materno' => 'Familia',
            'email' => 'apoderado@pensiones.test',
            'password' => bcrypt('password'),
            'estado' => '1',
        ]);

        $this->apoderado = $apoderadoUser;

        $estudianteUser1 = User::create([
            'dni' => '33333333',
            'nombre' => 'Hijo',
            'apellido_paterno' => 'Estudiante',
            'apellido_materno' => 'Uno',
            'email' => 'estudiante1@pensiones.test',
            'password' => bcrypt('password'),
            'estado' => '1',
        ]);

        $estudianteUser2 = User::create([
            'dni' => '44444444',
            'nombre' => 'Hijo',
            'apellido_paterno' => 'Estudiante',
            'apellido_materno' => 'Dos',
            'email' => 'estudiante2@pensiones.test',
            'password' => bcrypt('password'),
            'estado' => '1',
        ]);

        $apoderadoRecord = Apoderado::create([
            'user_id' => $apoderadoUser->id,
            'parentesco' => 'padre',
            'estado' => '1',
        ]);

        $this->estudiante1 = Estudiante::create([
            'user_id' => $estudianteUser1->id,
            'grado_id' => $this->grado->id,
            'apoderado_id' => $apoderadoRecord->id,
            'estado' => '1',
        ]);

        // Estudiante 2 sin apoderado (ajeno al apoderado del test)
        $this->estudiante2 = Estudiante::create([
            'user_id' => $estudianteUser2->id,
            'grado_id' => $this->grado->id,
            'apoderado_id' => null,
            'estado' => '1',
        ]);
    }

    private function crearEscuela(): void
    {
        $this->grado = Grado::create([
            'grado' => '1',
            'seccion' => 'A',
            'nivel' => 'PRIMARIA',
            'estado' => '1',
        ]);

        $this->periodo = Periodo::create([
            'nombre' => 'Periodo 2026',
            'estado' => '1',
            'anio' => 2026,
            'tipo_periodo' => 'año escolar',
        ]);
    }

    private function crearEstructuraMinima(): void
    {
        Schema::dropIfExists('pension_pago_registros');
        Schema::dropIfExists('pension_pagos');
        Schema::dropIfExists('pensiones');
        Schema::dropIfExists('pension_config_cuotas');
        Schema::dropIfExists('pension_configs');
        Schema::dropIfExists('m_tipo_pagos');
        Schema::dropIfExists('estado_pagos');
        Schema::dropIfExists('matriculas');
        Schema::dropIfExists('estudiantes');
        Schema::dropIfExists('apoderados');
        Schema::dropIfExists('periodos');
        Schema::dropIfExists('grados');
        Schema::dropIfExists('colegio_config');
        Schema::dropIfExists('role_modules');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

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

        Schema::create('grados', function ($table) {
            $table->id();
            $table->string('grado', 10);
            $table->string('seccion', 10);
            $table->string('nivel', 30);
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('periodos', function ($table) {
            $table->id();
            $table->integer('director_id')->nullable();
            $table->string('nombre', 50);
            $table->string('estado')->default('1');
            $table->year('anio');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('tipo_periodo')->default('año escolar');
            $table->string('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('apoderados', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('parentesco')->nullable();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('estudiantes', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('apoderado_id')->nullable()->constrained('apoderados')->nullOnDelete();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('matriculas', function ($table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('estado_pagos', function ($table) {
            $table->id();
            $table->string('nombre');
            $table->string('color')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('m_tipo_pagos', function ($table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria')->nullable();
            $table->string('entidad_financiera')->nullable();
            $table->string('numero_cuenta')->nullable();
            $table->string('cci')->nullable();
            $table->string('titular_cuenta')->nullable();
            $table->string('numero_celular')->nullable();
            $table->boolean('requiere_verificacion')->default(false);
            $table->string('color_hex')->nullable();
            $table->string('estado')->default('1');
            $table->string('es_efectivo')->default('0');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pension_configs', function ($table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();
            $table->string('estado')->default('1');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['periodo_id', 'grado_id']);
        });

        Schema::create('pension_config_cuotas', function ($table) {
            $table->id();
            $table->foreignId('pension_config_id')->constrained('pension_configs')->cascadeOnDelete();
            $table->string('concepto');
            $table->unsignedTinyInteger('mes')->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->date('fecha_vencimiento');
            $table->bigInteger('monto')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pensiones', function ($table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('config_cuota_id')->nullable()->constrained('pension_config_cuotas')->nullOnDelete();
            $table->string('concepto');
            $table->unsignedTinyInteger('mes')->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->date('fecha_vencimiento');
            $table->bigInteger('monto')->default(0);
            $table->bigInteger('monto_pagado')->default(0);
            $table->string('estado')->default('pendiente');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['matricula_id', 'estado']);
            $table->index(['fecha_vencimiento', 'estado']);
        });

        Schema::create('pension_pagos', function ($table) {
            $table->id();
            $table->foreignId('pension_id')->constrained('pensiones')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('metodo_pago_id')->nullable()->constrained('m_tipo_pagos')->nullOnDelete();
            $table->string('numero_operacion')->nullable();
            $table->bigInteger('monto')->default(0);
            $table->dateTime('fecha_pago')->nullable();
            $table->string('comprobante_path')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pension_pago_registros', function ($table) {
            $table->id();
            $table->foreignId('pension_id')->constrained('pensiones')->cascadeOnDelete();
            $table->foreignId('pago_id')->nullable()->constrained('pension_pagos')->nullOnDelete();
            $table->foreignId('estado_pago_id')->nullable()->constrained('estado_pagos')->nullOnDelete();
            $table->bigInteger('monto')->default(0);
            $table->dateTime('fecha_registro')->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['pension_id', 'estado_pago_id']);
        });

        $estados = [
            ['nombre' => 'Pendiente', 'color' => '#f59e0b', 'orden' => 1],
            ['nombre' => 'En revisión', 'color' => '#8b5cf6', 'orden' => 2],
            ['nombre' => 'Aprobado', 'color' => '#10b981', 'orden' => 3],
            ['nombre' => 'Rechazado', 'color' => '#ef4444', 'orden' => 4],
            ['nombre' => 'No requiere pago', 'color' => '#6b7280', 'orden' => 5],
        ];

        foreach ($estados as $estado) {
            Estadopago::create($estado);
        }
    }
}
