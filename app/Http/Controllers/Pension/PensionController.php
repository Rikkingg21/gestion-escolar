<?php

namespace App\Http\Controllers\Pension;

use App\Http\Controllers\Controller;
use App\Models\Apoderado;
use App\Models\Colegio;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Metodopago\Tipopago;
use App\Models\Pension\Pension;
use App\Models\Pension\PensionPago;
use App\Models\Pension\PensionPagoRegistro;
use App\Models\Periodo;
use App\Models\Tramite\Estadopago;
use App\Services\Pagos\CulqiService;
use App\Services\Pension\PensionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PensionController extends Controller
{
    public function __construct(private PensionService $pensionService)
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('25')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $estudiantes = $this->estudiantesDelUsuario();

        $periodos = Periodo::where('estado', '1')
            ->whereIn('id', Matricula::whereIn('estudiante_id', $estudiantes->pluck('id'))
                ->where('estado', '1')
                ->distinct()
                ->pluck('periodo_id'))
            ->orderBy('anio', 'desc')
            ->get();

        $periodoId = $request->filled('periodo_id') && $periodos->pluck('id')->contains($request->periodo_id)
            ? $request->periodo_id
            : $periodos->first()?->id;

        $pensiones = collect();

        foreach ($estudiantes as $estudiante) {
            $matricula = Matricula::where('estudiante_id', $estudiante->id)
                ->when($periodoId, fn ($q) => $q->where('periodo_id', $periodoId))
                ->where('estado', '1')
                ->first();

            if (! $matricula) {
                continue;
            }

            $query = Pension::with(['matricula.grado', 'matricula.periodo'])
                ->where('matricula_id', $matricula->id);

            if ($request->estado === 'atrasado') {
                $query->atrasadas();
            } elseif ($request->estado === 'pendiente') {
                $query->pendientes();
            } elseif ($request->estado === 'pagado') {
                $query->pagadas();
            }

            $pensiones = $pensiones->merge($query->orderBy('fecha_vencimiento')->get());
        }

        $pensiones = $pensiones->sortBy('fecha_vencimiento');

        return view('pensiones.apoderado.index', compact('pensiones', 'estudiantes', 'periodos', 'periodoId'));
    }

    public function show($id)
    {
        $pension = Pension::with([
            'matricula.estudiante.user',
            'matricula.grado',
            'matricula.periodo',
            'pagoRegistros' => fn ($q) => $q->with(['estadoPago', 'user', 'pago.metodoPago'])->orderBy('fecha_registro', 'desc'),
        ])->findOrFail($id);

        $estudiantes = $this->estudiantesDelUsuario();

        if (! auth()->user()->hasRole('admin') && ! $estudiantes->contains('id', $pension->matricula->estudiante_id)) {
            abort(403, 'No tienes permiso para ver esta pensión.');
        }

        $tiposPago = Tipopago::where('estado', '1')->get();
        $estadoActual = $pension->pagoRegistros->first();

        $colegio = Colegio::configuracion();
        $pasarelaHabilitada = $colegio->pasarelaHabilitada();
        $culqiPublicKey = $colegio->culqi_public_key;

        return view('pensiones.apoderado.show', compact('pension', 'tiposPago', 'estadoActual', 'pasarelaHabilitada', 'culqiPublicKey'));
    }

    public function subirComprobante(Request $request, $id)
    {
        $pension = Pension::with('matricula')->findOrFail($id);

        if (! auth()->user()->hasRole('admin')) {
            $estudiantes = $this->estudiantesDelUsuario();

            if (! $estudiantes->contains('id', $pension->matricula->estudiante_id)) {
                abort(403, 'No tienes permiso para pagar esta pensión.');
            }
        }

        if ($pension->esPagada()) {
            return redirect()->back()->with('error', 'Esta cuota ya está pagada.');
        }

        $request->validate([
            'tipo_pago_id' => 'required|exists:m_tipo_pagos,id',
            'numero_operacion' => 'nullable|string|max:50',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observaciones' => 'nullable|string',
        ]);

        $tipoPago = Tipopago::where('id', $request->tipo_pago_id)->where('estado', '1')->first();

        if (! $tipoPago) {
            return redirect()->back()->with('error', 'El método de pago no está activo.');
        }

        if ($tipoPago->es_efectivo == '1') {
            $numeroOperacion = 'EFECTIVO_'.date('YmdHis');
            $comprobantePath = null;
        } else {
            $request->validate([
                'numero_operacion' => 'required|string|max:50',
                'comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            $numeroOperacion = $request->numero_operacion;
            $comprobantePath = $request->file('comprobante')->store('comprobantes/'.date('Y/m'), 'private');
        }

        DB::transaction(function () use ($pension, $tipoPago, $numeroOperacion, $comprobantePath, $request) {
            $pago = PensionPago::create([
                'pension_id' => $pension->id,
                'user_id' => auth()->id(),
                'metodo_pago_id' => $tipoPago->id,
                'numero_operacion' => $numeroOperacion,
                'monto' => $pension->monto,
                'fecha_pago' => now(),
                'comprobante_path' => $comprobantePath,
                'observaciones' => $request->observaciones,
            ]);

            $estadoRevision = Estadopago::where('nombre', 'LIKE', '%Revisión%')->first()
                ?? Estadopago::where('nombre', 'LIKE', '%Pendiente%')->first();

            PensionPagoRegistro::create([
                'pension_id' => $pension->id,
                'pago_id' => $pago->id,
                'estado_pago_id' => $estadoRevision->id ?? 2,
                'monto' => $pension->monto,
                'fecha_registro' => now(),
                'observacion' => $tipoPago->es_efectivo == '1'
                    ? 'Usuario registró pago en efectivo - Pendiente de verificación'
                    : 'Usuario subió comprobante de pago - Pendiente de revisión',
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('pensiones.show', $pension->id)
            ->with('success', $tipoPago->es_efectivo == '1'
                ? 'Pago en efectivo registrado. Será verificado por el administrador.'
                : 'Comprobante subido correctamente. Será revisado por el administrador.');
    }

    public function pagarConTarjeta(Request $request, $id)
    {
        $pension = Pension::with('matricula')->findOrFail($id);

        if (! auth()->user()->hasRole('admin')) {
            $estudiantes = $this->estudiantesDelUsuario();

            if (! $estudiantes->contains('id', $pension->matricula->estudiante_id)) {
                abort(403, 'No tienes permiso para pagar esta pensión.');
            }
        }

        if ($pension->esPagada()) {
            return redirect()->back()->with('error', 'Esta cuota ya está pagada.');
        }

        $request->validate([
            'token' => 'required|string',
        ]);

        $colegio = Colegio::configuracion();

        if (! $colegio->pasarelaHabilitada()) {
            return redirect()->back()->with('error', 'El pago con tarjeta no está habilitado para este colegio.');
        }

        $email = auth()->user()->email ?: $colegio->email;

        try {
            $cargo = (new CulqiService($colegio))->crearCargo(
                $pension->monto,
                $request->token,
                $email,
                'Pensión: '.$pension->concepto.' - '.$pension->matricula->estudiante->user->nombre_completo
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'No se pudo procesar el pago con tarjeta. Verifique sus datos e intente nuevamente.');
        }

        $code = $cargo['outcome']['code'] ?? '';
        $tipo = $cargo['outcome']['type'] ?? '';
        $aprobado = in_array($code, ['approved', 'AUT0000'], true) || $tipo === 'venta_exitosa';
        $estado = $aprobado
            ? Estadopago::where('nombre', 'LIKE', '%Aprobado%')->first()
            : Estadopago::where('nombre', 'LIKE', '%Rechazado%')->first();

        $tipoPago = $this->tipoPagoTarjeta();

        DB::transaction(function () use ($pension, $tipoPago, $estado, $aprobado, $cargo) {
            $pago = PensionPago::create([
                'pension_id' => $pension->id,
                'user_id' => auth()->id(),
                'metodo_pago_id' => $tipoPago->id,
                'numero_operacion' => $cargo['id'] ?? ('CULQI_'.date('YmdHis')),
                'monto' => $pension->monto,
                'fecha_pago' => now(),
                'comprobante_path' => null,
                'observaciones' => $aprobado
                    ? 'Pago con tarjeta aprobado (Culqi)'
                    : 'Intento de pago con tarjeta rechazado',
            ]);

            PensionPagoRegistro::create([
                'pension_id' => $pension->id,
                'pago_id' => $pago->id,
                'estado_pago_id' => $estado->id ?? 3,
                'monto' => $pension->monto,
                'fecha_registro' => now(),
                'observacion' => $aprobado
                    ? 'Pago con tarjeta aprobado (Culqi)'
                    : 'Pago rechazado por el emisor de la tarjeta',
                'user_id' => auth()->id(),
            ]);

            $this->pensionService->sincronizarEstadoPension($pension);
        });

        if ($aprobado) {
            return redirect()->route('pensiones.show', $pension->id)
                ->with('success', 'Pago con tarjeta aprobado correctamente.');
        }

        return redirect()->route('pensiones.show', $pension->id)
            ->with('error', ($cargo['outcome']['user_message'] ?? null)
                ?: 'El pago fue rechazado por el emisor de la tarjeta. Verifique e intente nuevamente.');
    }

    private function tipoPagoTarjeta(): Tipopago
    {
        return Tipopago::firstOrCreate(
            ['categoria' => 'tarjeta', 'nombre' => 'Tarjeta (Culqi)'],
            [
                'es_efectivo' => '0',
                'requiere_verificacion' => false,
                'estado' => '1',
            ]
        );
    }

    public function verComprobante($id)
    {
        $pago = PensionPago::with('pension.matricula')->findOrFail($id);

        if (! auth()->user()->hasRole('admin')) {
            $estudiantes = $this->estudiantesDelUsuario();

            if (! $estudiantes->contains('id', $pago->pension->matricula->estudiante_id)) {
                abort(403, 'No tienes permiso para ver este comprobante.');
            }
        }

        if (! $pago->comprobante_path) {
            abort(404, 'Este pago no tiene archivo adjunto (pago en efectivo).');
        }

        if (! Storage::disk('private')->exists($pago->comprobante_path)) {
            abort(404, 'El archivo no existe.');
        }

        $file = Storage::disk('private')->get($pago->comprobante_path);
        $mimeType = Storage::disk('private')->mimeType($pago->comprobante_path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    private function estudiantesDelUsuario()
    {
        $user = auth()->user();
        $apoderado = Apoderado::where('user_id', $user->id)->first();

        if ($apoderado) {
            return Estudiante::with('user', 'grado')
                ->where('apoderado_id', $apoderado->id)
                ->where('estado', '1')
                ->get();
        }

        $estudiante = Estudiante::where('user_id', $user->id)->first();

        return $estudiante ? collect([$estudiante]) : collect();
    }
}
