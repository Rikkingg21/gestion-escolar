<?php

namespace App\Http\Controllers\Tramite;

use App\Http\Controllers\Controller;
use App\Models\Apoderado;
use App\Models\Estudiante;
use App\Models\Metodopago\Tipopago;
use App\Models\Tramite\Estadopago;
use App\Models\Tramite\Estadotramite;
use App\Models\Tramite\Pagocomprobante;
use App\Models\Tramite\Tramite;
use App\Models\Tramite\Tramitepagoregistro;
use App\Models\Tramite\Tramiteregistro;
use App\Models\Tramite\Tramitetipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TramiteController extends Controller
{
    // moduleID 21 = Mis Tramites
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('21')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $tipoTramitesActivos = Tramitetipo::where('estado', '1')->get();

        $query = Tramite::with([
            'tipoTramite',
            'tramiteRegistros' => fn ($q) => $q->with('estadoTramite')->latest(),
            'tramitePagoRegistros' => fn ($q) => $q->with('estadoPago')->latest('fecha_registro'),
        ])
            ->where('user_id', auth()->id());

        // Aplicar filtro de fechas
        if ($request->filled('fecha_inicio')) {
            $fechaInicio = $request->fecha_inicio;
            $query->whereDate('fecha_solicitud', '>=', $fechaInicio);
        } else {
            // Por defecto: desde el 1 de enero del año actual
            $query->whereDate('fecha_solicitud', '>=', now()->startOfYear());
        }

        if ($request->filled('fecha_fin')) {
            $fechaFin = $request->fecha_fin;
            $query->whereDate('fecha_solicitud', '<=', $fechaFin);
        } else {
            // Por defecto: hasta hoy
            $query->whereDate('fecha_solicitud', '<=', now());
        }

        // Aplicar filtro por tipo de trámite
        if ($request->filled('tipo_tramite_id')) {
            $query->where('tipo_tramite_id', $request->tipo_tramite_id);
        }

        $tramites = $query->orderBy('created_at', 'desc')->get();

        // Obtener estudiantes del usuario autenticado
        $estudiantes = collect();
        $parentesco = null;
        $user = Auth::user();

        $apoderado = Apoderado::where('user_id', $user->id)->first();

        if ($apoderado) {
            $parentesco = $apoderado->parentesco;
            $estudiantes = Estudiante::with('user', 'grado')
                ->where('apoderado_id', $apoderado->id)
                ->where('estado', '1')
                ->get();
        } else {
            $estudiante = Estudiante::where('user_id', $user->id)->first();
            if ($estudiante) {
                $estudiantes = collect([$estudiante]);
                $parentesco = 'Estudiante mismo';
            }
        }

        return view('tramite.mis-tramites.index', compact('tipoTramitesActivos', 'tramites', 'estudiantes', 'parentesco'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_tramite_id' => 'required|exists:m_tramite_tipo_tramites,id',
            'estudiante_id' => 'required|exists:estudiantes,id',
            'observaciones' => 'nullable|string',
        ]);

        $tipoTramite = Tramitetipo::findOrFail($request->tipo_tramite_id);
        $user = Auth::user();

        // Validar pertenencia del estudiante
        $estudianteValido = false;

        if ($user->hasRole('admin') || $user->hasRole('director')) {
            $estudianteValido = true;
        } elseif ($user->hasRole('apoderado')) {
            $apoderado = Apoderado::where('user_id', $user->id)->first();
            $estudianteValido = $apoderado && Estudiante::where('id', $request->estudiante_id)
                ->where('apoderado_id', $apoderado->id)
                ->exists();
        } else {
            $estudiante = Estudiante::where('user_id', $user->id)->first();
            $estudianteValido = $estudiante && $estudiante->id == $request->estudiante_id;
        }

        if (! $estudianteValido) {
            return redirect()->back()
                ->with('error', 'No tienes permiso para crear un trámite para este estudiante.')
                ->withInput();
        }

        // Generar código único
        $ultimoTramite = Tramite::withTrashed()->orderBy('id', 'desc')->first();
        $numero = $ultimoTramite ? intval(substr($ultimoTramite->codigo_tramite, -4)) + 1 : 1;
        $codigoTramite = 'TRM-'.date('Ymd').'-'.str_pad($numero, 4, '0', STR_PAD_LEFT);

        // Crear el trámite
        $tramite = Tramite::create([
            'codigo_tramite' => $codigoTramite,
            'user_id' => $user->id,
            'tipo_tramite_id' => $request->tipo_tramite_id,
            'estudiante_id' => $request->estudiante_id,
            'monto_pagado' => 0,
            'fecha_solicitud' => now(),
            'observaciones' => $request->observaciones,
        ]);

        // Crear registro inicial del trámite (estado: Pendiente)
        $estadoPendiente = Estadotramite::where('nombre', 'LIKE', '%Pendiente%')->first();
        Tramiteregistro::create([
            'tramite_id' => $tramite->id,
            'estado_tramite_id' => $estadoPendiente ? $estadoPendiente->id : 1,
            'observacion' => 'Trámite creado correctamente',
            'user_id' => $user->id,
        ]);

        // Registrar estado de pago según corresponda
        if ($tipoTramite->requiere_pago) {
            // Si requiere pago, estado "Pendiente"
            $estadoPago = Estadopago::where('nombre', 'LIKE', '%Pendiente%')->first();
            $observacionPago = 'Registro de pago inicial - Pendiente';
        } else {
            // Si no requiere pago, estado "No requiere pago"
            $estadoPago = Estadopago::where('nombre', 'LIKE', '%No requiere pago%')->first();
            $observacionPago = 'Este trámite no requiere pago';
        }

        // Solo crear registro de pago si existe el estado
        if ($estadoPago) {
            Tramitepagoregistro::create([
                'tramite_id' => $tramite->id,
                'estado_pago_id' => $estadoPago->id,
                'monto' => $tipoTramite->requiere_pago ? $tipoTramite->costo : 0,
                'fecha_registro' => now(),
                'observacion' => $observacionPago,
                'user_id' => $user->id,
            ]);
        }

        return redirect()->route('mis-tramites.index')
            ->with('success', 'Trámite creado exitosamente. Código: '.$codigoTramite);
    }

    public function show($id)
    {
        $tramite = Tramite::with([
            'tipoTramite',
            'user',
            'estudiante.user',
            'estudiante.grado',
            'tramiteRegistros' => function ($query) {
                $query->with('estadoTramite', 'user')->orderBy('created_at', 'desc');
            },
            'tramitePagoRegistros' => function ($query) {
                $query->with(['estadoPago', 'user', 'pagoComprobante'])->orderBy('fecha_registro', 'desc');
            },
        ])->findOrFail($id);

        // Verificar que el usuario sea el dueño del trámite o tenga permisos de admin
        if ($tramite->user_id != auth()->id() && ! auth()->user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para ver este trámite.');
        }

        // Obtener los tipos de pago activos
        $tiposPago = Tipopago::where('estado', '1')->get();

        // Obtener el estado actual
        $estadoActual = $tramite->tramiteRegistros->first();
        $estadoPagoActual = $tramite->tramitePagoRegistros->first();

        // Calcular costo total del trámite
        $costoTotal = $tramite->tipoTramite->requiere_pago ? ($tramite->tipoTramite->costo ?? 0) : 0;

        // Calcular monto pagado total usando el accessor del modelo
        $montoPagado = $tramite->tipoTramite->requiere_pago ? ($tramite->monto_pagado_total ?? 0) : 0;

        // Calcular saldo pendiente
        $saldoPendiente = $costoTotal - $montoPagado;

        // Determinar si el trámite requiere pago
        $requierePago = $tramite->tipoTramite->requiere_pago;

        // Determinar si el botón de subir comprobante debe mostrarse
        $mostrarBotonSubirComprobante = $requierePago && $saldoPendiente > 0;

        // Preparar datos de pagos enriquecidos para la vista
        $pagosEnriquecidos = $tramite->tramitePagoRegistros->map(function ($registroPago) {
            $metodoPago = null;
            $esEfectivo = false;

            if ($registroPago->pagoComprobante) {
                $metodoPago = Tipopago::find($registroPago->pagoComprobante->metodo_pago_id);
                $esEfectivo = $metodoPago && $metodoPago->es_efectivo == '1';
            }

            return [
                'registro' => $registroPago,
                'metodo_pago' => $metodoPago,
                'es_efectivo' => $esEfectivo,
                'monto_formateado' => 'S/ '.number_format($registroPago->monto, 2),
                'fecha_formateada' => $registroPago->fecha_registro
                    ? \Carbon\Carbon::parse($registroPago->fecha_registro)->format('d/m/Y H:i:s')
                    : $registroPago->created_at->format('d/m/Y H:i:s'),
            ];
        });

        // Preparar datos del solicitante para la vista
        $solicitante = [
            'nombre_completo' => ($tramite->user->nombre ?? 'N/A').' '.($tramite->user->apellido_paterno ?? ''),
            'dni' => $tramite->user->dni ?? 'N/A',
            'email' => $tramite->user->email ?? 'N/A',
            'telefono' => $tramite->user->telefono ?? 'N/A',
        ];

        // Preparar datos del estudiante para la vista
        $estudianteData = [
            'nombre_completo' => ($tramite->estudiante->user->nombre ?? 'N/A').' '.($tramite->estudiante->user->apellido_paterno ?? ''),
            'dni' => $tramite->estudiante->user->dni ?? 'N/A',
        ];

        // Fechas formateadas
        $fechaSolicitud = $tramite->fecha_solicitud
            ? \Carbon\Carbon::parse($tramite->fecha_solicitud)->format('d/m/Y')
            : 'N/A';

        $fechaResolucion = $tramite->fecha_resolucion
            ? \Carbon\Carbon::parse($tramite->fecha_resolucion)->format('d/m/Y')
            : 'Pendiente';

        // Tipo de trámite
        $tipoTramiteNombre = $tramite->tipoTramite->nombre ?? 'Sin nombre';

        // Observación general
        $observacionGeneral = $tramite->observaciones ?? 'Sin observaciones registradas';

        // Preparar datos para el historial de trámites enriquecidos
        $historialTramitesEnriquecidos = $tramite->tramiteRegistros->map(function ($registro) {
            return [
                'registro' => $registro,
                'fecha_formateada' => $registro->created_at->format('d/m/Y H:i'),
                'color_estado' => $registro->estadoTramite->color ?? '#6c757d',
                'nombre_estado' => $registro->estadoTramite->nombre ?? 'N/A',
                'nombre_usuario' => $registro->user->nombre ?? 'Sistema',
            ];
        });

        // Totales para el footer
        $totalRegistrosTramite = $tramite->tramiteRegistros->count();
        $totalRegistrosPago = $tramite->tramitePagoRegistros->count();

        return view('tramite.mis-tramites.show', compact(
            'tramite',
            'estadoActual',
            'estadoPagoActual',
            'tiposPago',
            // Nuevas variables
            'costoTotal',
            'montoPagado',
            'saldoPendiente',
            'requierePago',
            'mostrarBotonSubirComprobante',
            'pagosEnriquecidos',
            'solicitante',
            'estudianteData',
            'fechaSolicitud',
            'fechaResolucion',
            'tipoTramiteNombre',
            'observacionGeneral',
            'historialTramitesEnriquecidos',
            'totalRegistrosTramite',
            'totalRegistrosPago'
        ));
    }

    public function subirComprobante(Request $request, $id)
    {
        $tramite = Tramite::findOrFail($id);

        if ($tramite->user_id != auth()->id()) {
            abort(403);
        }

        if (! $tramite->tipoTramite->requiere_pago) {
            return redirect()->back()->with('error', 'Este trámite no requiere pago.');
        }

        // No permitir pagos en trámites finalizados o rechazados
        $ultimoRegistro = $tramite->tramiteRegistros()->latest()->first();
        if ($ultimoRegistro && $ultimoRegistro->estadoTramite) {
            $nombreEstado = strtolower($ultimoRegistro->estadoTramite->nombre);
            if (in_array($nombreEstado, ['completado', 'resuelto', 'finalizado', 'rechazado'])) {
                return redirect()->back()->with('error', 'No se puede subir comprobantes a un trámite '.$ultimoRegistro->estadoTramite->nombre.'.');
            }
        }

        // Calcular saldo pendiente
        $costoTotal = $tramite->tipoTramite->costo ?? 0;
        $montoPagado = $tramite->monto_pagado_total;
        $saldoPendiente = $costoTotal - $montoPagado;

        // Obtener el tipo de pago seleccionado
        $tipoPago = Tipopago::findOrFail($request->tipo_pago_id);

        // Validar según el tipo de pago
        if ($tipoPago->es_efectivo == '1') {
            // Pago en efectivo - no requiere comprobante ni número de operación
            $request->validate([
                'monto' => 'required|numeric|min:0.01',
                'observaciones' => 'nullable|string',
            ]);

            $numeroOperacion = 'EFECTIVO_'.date('YmdHis');
            $comprobantePath = null;

        } else {
            // Pago con transferencia/depósito - requiere comprobante
            $request->validate([
                'numero_operacion' => 'required|string',
                'monto' => 'required|numeric|min:0.01',
                'comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'observaciones' => 'nullable|string',
            ]);

            $numeroOperacion = $request->numero_operacion;
            $comprobantePath = $request->file('comprobante')->store('comprobantes/'.date('Y/m'), 'private');
        }

        // Validar que el monto no supere el saldo pendiente
        if ((float) $request->monto > $saldoPendiente) {
            return redirect()->back()
                ->with('error', 'El monto no puede superar el saldo pendiente (S/ '.number_format($saldoPendiente, 2).').')
                ->withInput();
        }

        // Crear el comprobante (para efectivo se guarda sin archivo)
        $comprobante = Pagocomprobante::create([
            'tramite_id' => $id,
            'user_id' => auth()->id(),
            'metodo_pago_id' => $request->tipo_pago_id,
            'numero_operacion' => $numeroOperacion,
            'monto' => $request->monto,
            'fecha_pago' => now(),
            'comprobante_path' => $comprobantePath,
            'observaciones' => $request->observaciones,
        ]);

        // Registrar el cambio de estado
        $estadoPagoEnRevision = Estadopago::where('nombre', 'LIKE', '%Revisión%')->first();
        if (! $estadoPagoEnRevision) {
            $estadoPagoEnRevision = Estadopago::where('nombre', 'LIKE', '%Pendiente%')->first();
        }

        $observacionPago = $tipoPago->es_efectivo == '1'
            ? 'Usuario registró pago en efectivo - Pendiente de verificación'
            : 'Usuario subió comprobante de pago - Pendiente de revisión';

        Tramitepagoregistro::create([
            'tramite_id' => $id,
            'pago_comprobante_id' => $comprobante->id,
            'estado_pago_id' => $estadoPagoEnRevision ? $estadoPagoEnRevision->id : 1,
            'monto' => $request->monto,
            'fecha_registro' => now(),
            'observacion' => $observacionPago,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('mis-tramites.show', $id)
            ->with('success', $tipoPago->es_efectivo == '1'
                ? 'Pago en efectivo registrado. Será verificado por el administrador.'
                : 'Comprobante subido correctamente. Será revisado por el administrador.');
    }

    public function verComprobante($id)
    {
        $comprobante = Pagocomprobante::with('user', 'tramite')->findOrFail($id);

        // Verificar permisos: solo el dueño del trámite o admin pueden ver
        $user = auth()->user();
        $esAdmin = $user->hasRole('admin');
        $esPropietario = $comprobante->tramite->user_id == $user->id;

        if (! $esAdmin && ! $esPropietario) {
            abort(403, 'No tienes permiso para ver este comprobante.');
        }

        // Verificar si el archivo existe
        if (! Storage::disk('private')->exists($comprobante->comprobante_path)) {
            abort(404, 'El archivo no existe.');
        }

        // Obtener el archivo y mostrarlo
        $file = Storage::disk('private')->get($comprobante->comprobante_path);
        $mimeType = Storage::disk('private')->mimeType($comprobante->comprobante_path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }
}
