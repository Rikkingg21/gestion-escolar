<?php

namespace App\Http\Controllers\Pension;

use App\Http\Controllers\Controller;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Metodopago\Tipopago;
use App\Models\Pension\Pension;
use App\Models\Pension\PensionConfig;
use App\Models\Pension\PensionConfigCuota;
use App\Models\Pension\PensionPago;
use App\Models\Pension\PensionPagoRegistro;
use App\Models\Periodo;
use App\Models\Tramite\Estadopago;
use App\Services\Pension\PensionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PensionadminController extends Controller
{
    public function __construct(private PensionService $pensionService)
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('24')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $periodos = Periodo::orderBy('anio', 'desc')->get();
        $periodoSeleccionado = $request->filled('periodo_id')
            ? Periodo::find($request->periodo_id)
            : Periodo::where('estado', '1')->orderBy('anio', 'desc')->first();

        $configs = PensionConfig::with(['periodo', 'grado', 'cuotas'])
            ->when($periodoSeleccionado, fn ($q) => $q->where('periodo_id', $periodoSeleccionado->id))
            ->orderBy('created_at', 'desc')
            ->get();

        $queryPensiones = Pension::query();
        if ($periodoSeleccionado) {
            $queryPensiones->whereHas('matricula', fn ($q) => $q->where('periodo_id', $periodoSeleccionado->id));
        }

        $totalCuotas = (clone $queryPensiones)->count();
        $totalPagadas = (clone $queryPensiones)->pagadas()->count();
        $totalPendientes = (clone $queryPensiones)->pendientes()->count();
        $totalAtrasadas = (clone $queryPensiones)->atrasadas()->count();
        $recaudado = (clone $queryPensiones)->pagadas()->sum('monto_pagado');
        $porCobrar = (clone $queryPensiones)->pendientes()->sum(DB::raw('monto - monto_pagado'));

        $pagosEnRevision = $this->registrosEnRevision()->count();

        return view('pensiones.admin.index', compact(
            'periodos',
            'periodoSeleccionado',
            'configs',
            'totalCuotas',
            'totalPagadas',
            'totalPendientes',
            'totalAtrasadas',
            'recaudado',
            'porCobrar',
            'pagosEnRevision'
        ));
    }

    public function configuracionCreate(Request $request)
    {
        $periodos = Periodo::orderBy('anio', 'desc')->get();
        $grados = Grado::where('estado', '1')->orderBy('nivel')->orderBy('grado')->orderBy('seccion')->get();

        $periodoSeleccionado = $request->filled('periodo_id')
            ? Periodo::find($request->periodo_id)
            : null;

        return view('pensiones.admin.configuracion-form', compact('periodos', 'grados', 'periodoSeleccionado'));
    }

    public function configuracionStore(Request $request)
    {
        $request->validate($this->reglasConfiguracion($request, true));

        $periodo = Periodo::findOrFail($request->periodo_id);

        $creadas = 0;
        $omitidas = 0;

        DB::transaction(function () use ($request, $periodo, &$creadas, &$omitidas) {
            foreach ($request->grado_id as $gradoId) {
                $existe = PensionConfig::where('periodo_id', $periodo->id)
                    ->where('grado_id', $gradoId)
                    ->exists();

                if ($existe) {
                    $omitidas++;

                    continue;
                }

                $config = PensionConfig::create([
                    'periodo_id' => $periodo->id,
                    'grado_id' => $gradoId,
                    'estado' => '1',
                ]);

                $this->crearCuotasConfig($config, $request->cuotas);

                $creadas += $this->pensionService->generarCuotasParaConfig($config);
            }
        });

        $mensaje = "Configuración creada. Se generaron {$creadas} cuota(s) para las matrículas existentes.";
        if ($omitidas > 0) {
            $mensaje .= " Se omitieron {$omitidas} grado(s) que ya tenían configuración para el periodo.";
        }

        return redirect()->route('pensiones-admin.index', ['periodo_id' => $periodo->id])
            ->with('success', $mensaje);
    }

    public function configuracionEdit($id)
    {
        $config = PensionConfig::with('cuotas')->findOrFail($id);
        $periodos = Periodo::orderBy('anio', 'desc')->get();
        $grados = Grado::where('estado', '1')->orderBy('nivel')->orderBy('grado')->orderBy('seccion')->get();

        return view('pensiones.admin.configuracion-form', compact('config', 'periodos', 'grados'));
    }

    public function configuracionUpdate($id, Request $request)
    {
        $config = PensionConfig::findOrFail($id);

        $request->validate($this->reglasConfiguracion($request));

        DB::transaction(function () use ($config, $request) {
            // Borrar las cuotas generadas que aún no fueron pagadas/anuladas
            $cuotasViejas = $config->cuotas()->pluck('id');

            Pension::whereIn('config_cuota_id', $cuotasViejas)
                ->where('estado', Pension::ESTADO_PENDIENTE)
                ->delete();

            PensionConfigCuota::whereIn('id', $cuotasViejas)->forceDelete();

            $config->update([
                'periodo_id' => $request->periodo_id,
                'grado_id' => $request->grado_id,
                'estado' => '1',
            ]);

            $this->crearCuotasConfig($config, $request->cuotas);
        });

        $creadas = $this->pensionService->generarCuotasParaConfig($config);

        return redirect()->route('pensiones-admin.index')
            ->with('success', "Configuración actualizada. Se generaron {$creadas} cuota(s) faltante(s).");
    }

    public function configuracionDestroy($id)
    {
        $config = PensionConfig::findOrFail($id);

        DB::transaction(function () use ($config) {
            $cuotasViejas = $config->cuotas()->pluck('id');

            Pension::whereIn('config_cuota_id', $cuotasViejas)
                ->where('estado', Pension::ESTADO_PENDIENTE)
                ->delete();

            PensionConfigCuota::whereIn('id', $cuotasViejas)->forceDelete();

            $config->delete();
        });

        return redirect()->route('pensiones-admin.index')
            ->with('success', 'Configuración de pensiones eliminada.');
    }

    public function cuotas(Request $request)
    {
        $periodos = Periodo::orderBy('anio', 'desc')->get();
        $grados = Grado::where('estado', '1')->orderBy('nivel')->orderBy('grado')->orderBy('seccion')->get();

        $periodoId = $request->filled('periodo_id')
            ? $request->periodo_id
            : $periodos->first()?->id;

        $query = Pension::with([
            'matricula.estudiante.user',
            'matricula.grado',
            'matricula.periodo',
            'pagoRegistros.estadoPago',
        ])
            ->when($periodoId, fn ($q) => $q->whereHas('matricula', fn ($m) => $m->where('periodo_id', $periodoId)))
            ->when($request->filled('grado_id'), fn ($q) => $q->whereHas('matricula', fn ($m) => $m->where('grado_id', $request->grado_id)))
            ->when($request->filled('mes'), fn ($q) => $q->where('mes', $request->mes))
            ->when($request->filled('estado'), function ($q) use ($request) {
                if ($request->estado === 'atrasado') {
                    $q->atrasadas();
                } elseif ($request->estado === 'pendiente') {
                    $q->pendientes();
                } elseif ($request->estado === 'pagado') {
                    $q->pagadas();
                } elseif ($request->estado === 'anulado') {
                    $q->anuladas();
                }
            })
            ->when($request->filled('buscar'), fn ($q) => $q->whereHas('matricula.estudiante.user', function ($u) use ($request) {
                $buscar = $request->buscar;
                $u->where('dni', 'LIKE', "%{$buscar}%")
                    ->orWhere('nombre', 'LIKE', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'LIKE', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'LIKE', "%{$buscar}%");
            }));

        $pensiones = $query->orderBy('fecha_vencimiento')->paginate(20)->withQueryString();

        return view('pensiones.admin.cuotas', compact('pensiones', 'periodos', 'grados', 'periodoId'));
    }

    public function showPension($id)
    {
        $pension = Pension::with([
            'matricula.estudiante.user',
            'matricula.grado',
            'matricula.periodo',
            'pagoRegistros' => fn ($q) => $q->with(['estadoPago', 'user', 'pago.metodoPago'])->orderBy('fecha_registro', 'desc'),
        ])->findOrFail($id);

        $estadosPago = Estadopago::all();

        return view('pensiones.admin.show', compact('pension', 'estadosPago'));
    }

    public function registrarPago()
    {
        $periodos = Periodo::orderBy('anio', 'desc')->get();
        $tiposPago = Tipopago::where('estado', '1')->get();

        return view('pensiones.admin.registrar-pago', compact('periodos', 'tiposPago'));
    }

    public function buscarEstudiantes(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'q' => 'nullable|string|max:100',
        ]);

        $periodoId = $request->periodo_id;
        $q = trim($request->q ?? '');

        $query = Matricula::where('periodo_id', $periodoId)
            ->where('estado', '1')
            ->with(['estudiante.user', 'estudiante.apoderado.user', 'grado']);

        if ($q !== '') {
            $query->where(function ($query) use ($q) {
                $query->whereHas('estudiante.user', function ($u) use ($q) {
                    $u->where('dni', 'LIKE', "%{$q}%")
                        ->orWhere('nombre', 'LIKE', "%{$q}%")
                        ->orWhere('apellido_paterno', 'LIKE', "%{$q}%")
                        ->orWhere('apellido_materno', 'LIKE', "%{$q}%");
                });

                $query->orWhereHas('estudiante.apoderado.user', function ($u) use ($q) {
                    $u->where('dni', 'LIKE', "%{$q}%")
                        ->orWhere('nombre', 'LIKE', "%{$q}%")
                        ->orWhere('apellido_paterno', 'LIKE', "%{$q}%")
                        ->orWhere('apellido_materno', 'LIKE', "%{$q}%");
                });
            });
        }

        $matriculas = $query->limit(15)->get();

        $resultados = $matriculas->map(function ($matricula) {
            $estudiante = $matricula->estudiante;
            $user = $estudiante->user;
            $apoderadoUser = $estudiante->apoderado?->user;

            return [
                'estudiante_id' => $estudiante->id,
                'nombre_completo' => trim(($user->nombre ?? '').' '.($user->apellido_paterno ?? '').' '.($user->apellido_materno ?? '')),
                'dni' => $user->dni ?? 'N/A',
                'grado_nombre' => $matricula->grado?->nombre_completo ?? 'N/A',
                'apoderado_nombre' => $apoderadoUser
                    ? trim(($apoderadoUser->nombre ?? '').' '.($apoderadoUser->apellido_paterno ?? '').' '.($apoderadoUser->apellido_materno ?? ''))
                    : 'Sin apoderado',
            ];
        });

        return response()->json(['success' => true, 'data' => $resultados]);
    }

    public function cuotasEstudiante(Request $request, $estudianteId)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
        ]);

        $matricula = Matricula::where('estudiante_id', $estudianteId)
            ->where('periodo_id', $request->periodo_id)
            ->where('estado', '1')
            ->first();

        if (! $matricula) {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante no está matriculado en el periodo seleccionado.',
            ], 404);
        }

        $pensiones = Pension::where('matricula_id', $matricula->id)
            ->pendientesOAtrasadas()
            ->orderBy('fecha_vencimiento')
            ->get();

        $data = $pensiones->map(fn ($p) => [
            'id' => $p->id,
            'concepto' => $p->concepto,
            'fecha_vencimiento' => $p->fecha_vencimiento_formateada,
            'monto' => number_format($p->monto / 100, 2, '.', ''),
            'monto_formateado' => $p->monto_formateado,
            'estado_efectivo' => $p->estado_efectivo,
            'estado_label' => $p->estado_efectivo_label,
            'estado_color' => $p->estado_efectivo_color,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function registrarPagoStore(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'estudiante_id' => 'required|exists:estudiantes,id',
            'pension_id' => 'required|exists:pensiones,id',
            'metodo_pago_id' => 'required|exists:m_tipo_pagos,id',
            'numero_operacion' => 'nullable|string|max:50',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observaciones' => 'nullable|string|max:255',
        ]);

        $matricula = Matricula::where('estudiante_id', $request->estudiante_id)
            ->where('periodo_id', $request->periodo_id)
            ->where('estado', '1')
            ->first();

        if (! $matricula) {
            return back()->with('error', 'El estudiante no está matriculado en el periodo seleccionado.');
        }

        $pension = Pension::findOrFail($request->pension_id);

        if ($pension->matricula_id !== $matricula->id) {
            return back()->with('error', 'La cuota seleccionada no pertenece al estudiante.');
        }

        if ($pension->esPagada()) {
            return back()->with('error', 'Esta cuota ya está pagada.');
        }

        $metodoPago = Tipopago::where('id', $request->metodo_pago_id)->where('estado', '1')->first();

        if (! $metodoPago) {
            return back()->with('error', 'El método de pago no está activo.');
        }

        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $comprobantePath = $request->file('comprobante')->store('comprobantes/'.date('Y/m'), 'private');
        }

        $numeroOperacion = $request->numero_operacion;
        if (blank($numeroOperacion) && $metodoPago->es_efectivo == '1') {
            $numeroOperacion = 'EFECTIVO_'.date('YmdHis');
        }

        DB::transaction(function () use ($pension, $metodoPago, $comprobantePath, $numeroOperacion, $request) {
            $pago = PensionPago::create([
                'pension_id' => $pension->id,
                'user_id' => auth()->id(),
                'metodo_pago_id' => $metodoPago->id,
                'numero_operacion' => $numeroOperacion,
                'monto' => $pension->monto,
                'fecha_pago' => now(),
                'comprobante_path' => $comprobantePath,
                'observaciones' => $request->observaciones,
            ]);

            $estadoAprobado = Estadopago::where('nombre', 'LIKE', '%Aprobado%')->first();

            PensionPagoRegistro::create([
                'pension_id' => $pension->id,
                'pago_id' => $pago->id,
                'estado_pago_id' => $estadoAprobado->id ?? 3,
                'monto' => $pension->monto,
                'fecha_registro' => now(),
                'observacion' => 'Pago registrado por administración y aprobado',
                'user_id' => auth()->id(),
            ]);

            $this->pensionService->sincronizarEstadoPension($pension);
        });

        return redirect()->route('pensiones-admin.pensiones.show', $pension->id)
            ->with('success', "Pago registrado y aprobado para la cuota: {$pension->concepto}");
    }

    public function pagosPendientes()
    {
        $registros = $this->registrosEnRevision()
            ->with([
                'pension.matricula.estudiante.user',
                'pension.matricula.grado',
                'pago.metodoPago',
                'user',
                'estadoPago',
            ])
            ->orderBy('fecha_registro', 'desc')
            ->get();

        $estadosPago = Estadopago::all();

        return view('pensiones.admin.pagos-pendientes', compact('registros', 'estadosPago'));
    }

    public function updateEstadoPago($id, Request $request)
    {
        $request->validate([
            'pago_registro_id' => 'required|exists:pension_pago_registros,id',
            'estado_pago_id' => 'required|exists:estado_pagos,id',
            'observacion' => 'nullable|string',
        ]);

        $pension = Pension::findOrFail($id);
        $registro = PensionPagoRegistro::findOrFail($request->pago_registro_id);

        if ($registro->pension_id !== $pension->id) {
            return back()->with('error', 'El registro de pago no pertenece a esta cuota.');
        }

        if ($registro->estado_pago_id == $request->estado_pago_id) {
            return back()->with('warning', 'El estado del pago ya es el mismo.');
        }

        PensionPagoRegistro::create([
            'pension_id' => $pension->id,
            'pago_id' => $registro->pago_id,
            'estado_pago_id' => $request->estado_pago_id,
            'monto' => $registro->monto,
            'fecha_registro' => now(),
            'observacion' => $request->observacion ?: 'Cambio de estado de pago realizado por administrador',
            'user_id' => auth()->id(),
        ]);

        $this->pensionService->sincronizarEstadoPension($pension);

        return back()->with('success', 'Estado del pago actualizado correctamente.');
    }

    public function verComprobante($id)
    {
        $pago = PensionPago::findOrFail($id);

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

    private function registrosEnRevision()
    {
        $ids = PensionPagoRegistro::selectRaw('MAX(id) as id')
            ->whereNotNull('pago_id')
            ->groupBy('pension_id')
            ->pluck('id');

        return PensionPagoRegistro::whereIn('id', $ids)
            ->whereHas('estadoPago', function ($q) {
                $q->where('nombre', 'LIKE', '%Revisión%')
                    ->orWhere('nombre', 'LIKE', '%Pendiente%');
            });
    }

    private function reglasConfiguracion(Request $request, bool $multigrado = false): array
    {
        $periodo = Periodo::find($request->periodo_id);
        $anio = $periodo?->anio ?? now()->year;

        $gradoRule = $multigrado
            ? 'required|array|min:1'
            : 'required|exists:grados,id';

        return [
            'periodo_id' => 'required|exists:periodos,id',
            'grado_id' => $gradoRule,
            'grado_id.*' => 'exists:grados,id',
            'cuotas' => 'required|array|min:1',
            'cuotas.*.concepto' => 'required|string|max:150',
            'cuotas.*.mes' => 'nullable|integer|between:1,12',
            'cuotas.*.fecha_vencimiento' => "required|date|after_or_equal:{$anio}-01-01|before_or_equal:{$anio}-12-31",
            'cuotas.*.monto' => 'required|numeric|min:0.01',
        ];
    }

    private function crearCuotasConfig(PensionConfig $config, array $cuotas): void
    {
        $anio = $config->periodo->anio;

        foreach ($cuotas as $cuota) {
            PensionConfigCuota::create([
                'pension_config_id' => $config->id,
                'concepto' => $cuota['concepto'],
                'mes' => $cuota['mes'] ?? null,
                'anio' => $anio,
                'fecha_vencimiento' => $cuota['fecha_vencimiento'],
                'monto' => (int) round((float) $cuota['monto'] * 100),
            ]);
        }
    }
}
