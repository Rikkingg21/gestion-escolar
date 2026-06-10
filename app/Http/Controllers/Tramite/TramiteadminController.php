<?php

namespace App\Http\Controllers\Tramite;

use App\Http\Controllers\Controller;
use App\Models\Tramite\Tramite;
use App\Models\Tramite\Tramitetipo;
use App\Models\Tramite\Tramitepagoregistro;
use App\Models\Tramite\Tramiteregistro;
use App\Models\Tramite\Estadopago;
use App\Models\Tramite\Estadotramite;
use App\Models\Tramite\Pagocomprobante;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class TramiteadminController extends Controller
{
    //moduleID 16 = Trámites - admin
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('19')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        // Estadísticas de TRÁMITES - obtener el último estado
        $ultimosEstados = Tramiteregistro::select('tramite_id', 'estado_tramite_id')
            ->whereIn('id', function($query) {
                $query->selectRaw('MAX(id)')
                    ->from('m_tramite_registros')
                    ->groupBy('tramite_id');
            })->get();

        $totalTramites = Tramite::count();
        $totalPendientes = 0;
        $totalEnProceso = 0;
        $totalCompletados = 0;

        foreach ($ultimosEstados as $ultimoEstado) {
            $estado = Estadotramite::find($ultimoEstado->estado_tramite_id);
            $nombre = strtolower($estado->nombre ?? '');

            if (str_contains($nombre, 'pendiente')) {
                $totalPendientes++;
            } elseif (str_contains($nombre, 'proceso') || str_contains($nombre, 'atender')) {
                $totalEnProceso++;
            } elseif (str_contains($nombre, 'completado') || str_contains($nombre, 'finalizado') || str_contains($nombre, 'resuelto')) {
                $totalCompletados++;
            }
        }

        // Estadísticas de PAGOS - obtener el último estado de pago
        $ultimosPagos = Tramitepagoregistro::select('tramite_id', 'estado_pago_id')
            ->whereIn('id', function($query) {
                $query->selectRaw('MAX(id)')
                    ->from('m_tramite_pago_registros')
                    ->groupBy('tramite_id');
            })->get();

        $totalPagosPendientes = 0;
        $totalPagosAprobados = 0;
        $totalPagosRechazados = 0;

        foreach ($ultimosPagos as $ultimoPago) {
            $estado = Estadopago::find($ultimoPago->estado_pago_id);
            $nombre = strtolower($estado->nombre ?? '');

            if (str_contains($nombre, 'pendiente') || str_contains($nombre, 'revisión')) {
                $totalPagosPendientes++;
            } elseif (str_contains($nombre, 'aprobado')) {
                $totalPagosAprobados++;
            } elseif (str_contains($nombre, 'rechazado')) {
                $totalPagosRechazados++;
            }
        }

        // Obtener trámites con filtros para la tabla
        $query = Tramite::with([
            'user',
            'tipoTramite',
            'estudiante.user',
            'tramiteRegistros' => function($q) {
                $q->with('estadoTramite')->latest();
            },
            'tramitePagoRegistros' => function($q) {
                $q->with('estadoPago')->latest('fecha_registro');
            }
        ]);

        // Filtro por búsqueda
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('codigo_tramite', 'LIKE', "%{$buscar}%")
                    ->orWhereHas('user', function($qr) use ($buscar) {
                        $qr->where('dni', 'LIKE', "%{$buscar}%")
                            ->orWhere('nombre', 'LIKE', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'LIKE', "%{$buscar}%")
                            ->orWhere('apellido_materno', 'LIKE', "%{$buscar}%");
                    })
                    ->orWhereHas('estudiante.user', function($qr) use ($buscar) {
                        $qr->where('dni', 'LIKE', "%{$buscar}%")
                            ->orWhere('nombre', 'LIKE', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'LIKE', "%{$buscar}%")
                            ->orWhere('apellido_materno', 'LIKE', "%{$buscar}%");
                    });
            });
        }

        // Filtro por tipo de trámite
        if ($request->filled('tipo_tramite')) {
            $query->where('tipo_tramite_id', $request->tipo_tramite);
        }

        // Filtro por estado de trámite
        if ($request->filled('estado_tramite')) {
            $query->whereHas('tramiteRegistros', function($q) use ($request) {
                $q->where('estado_tramite_id', $request->estado_tramite)
                    ->whereIn('id', function($sub) {
                        $sub->selectRaw('MAX(id)')
                            ->from('m_tramite_registros')
                            ->groupBy('tramite_id');
                    });
            });
        }

        // Filtro por estado de pago
        if ($request->filled('estado_pago')) {
            $query->whereHas('tramitePagoRegistros', function($q) use ($request) {
                $q->where('estado_pago_id', $request->estado_pago)
                    ->whereIn('id', function($sub) {
                        $sub->selectRaw('MAX(id)')
                            ->from('m_tramite_pago_registros')
                            ->groupBy('tramite_id');
                    });
            });
        }

        // Filtros por año y mes
        if ($request->filled('anio')) {
            $anio = $request->anio;
            if ($request->fecha_tipo == 'resolucion') {
                $query->whereYear('fecha_resolucion', $anio);
            } else {
                $query->whereYear('fecha_solicitud', $anio);
            }
        }

        if ($request->filled('mes')) {
            $mes = $request->mes;
            if ($request->fecha_tipo == 'resolucion') {
                $query->whereMonth('fecha_resolucion', $mes);
            } else {
                $query->whereMonth('fecha_solicitud', $mes);
            }
        }

        // Filtros por rango de fechas
        if ($request->filled('fecha_desde')) {
            $fechaDesde = $request->fecha_desde;
            if ($request->fecha_tipo == 'resolucion') {
                $query->whereDate('fecha_resolucion', '>=', $fechaDesde);
            } else {
                $query->whereDate('fecha_solicitud', '>=', $fechaDesde);
            }
        }

        if ($request->filled('fecha_hasta')) {
            $fechaHasta = $request->fecha_hasta;
            if ($request->fecha_tipo == 'resolucion') {
                $query->whereDate('fecha_resolucion', '<=', $fechaHasta);
            } else {
                $query->whereDate('fecha_solicitud', '<=', $fechaHasta);
            }
        }

        // Obtener años disponibles para filtros (basados en fecha_solicitud)
        $aniosDisponibles = Tramite::selectRaw('YEAR(fecha_solicitud) as anio')
            ->whereNotNull('fecha_solicitud')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();

        // También obtener años de fecha_resolucion (para completar)
        $aniosResolucion = Tramite::selectRaw('YEAR(fecha_resolucion) as anio')
            ->whereNotNull('fecha_resolucion')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();

        // Combinar y obtener años únicos
        $aniosDisponibles = array_unique(array_merge($aniosDisponibles, $aniosResolucion));
        sort($aniosDisponibles); // Ordenar ascendente

        // Obtener todos los trámites (sin paginación para DataTables)
        $tramites = $query->orderBy('created_at', 'desc')->get();

        // Datos para los selects de filtros
        $tiposTramite = Tramitetipo::where('estado', '1')->get();
        $estadosTramite = Estadotramite::all();
        $estadosPago = Estadopago::all();

        return view('tramite.admin.index', compact(
            'tramites',
            'tiposTramite',
            'estadosTramite',
            'estadosPago',
            'totalTramites',
            'totalPendientes',
            'totalEnProceso',
            'totalCompletados',
            'totalPagosPendientes',
            'totalPagosAprobados',
            'totalPagosRechazados',
            'aniosDisponibles'
        ));
    }
    public function show($id)
    {
        $tramite = Tramite::with([
            'user',
            'tipoTramite',
            'estudiante.user',
            'estudiante.grado',
            'tramiteRegistros' => function($query) {
                $query->with('estadoTramite', 'user')->orderBy('created_at', 'desc');
            },
            'tramitePagoRegistros' => function($query) {
                $query->with('estadoPago', 'user', 'pagoComprobante')->orderBy('fecha_registro', 'desc');
            }
        ])->findOrFail($id);

        $estadosTramite = Estadotramite::all();
        $estadosPago = Estadopago::all();

        // Agregar esta variable
        $requierePago = $tramite->tipoTramite->requiere_pago ?? false;

        return view('tramite.admin.show', compact('tramite', 'estadosTramite', 'estadosPago', 'requierePago'));
    }
    public function updateEstadoTramite(Request $request, $id)
    {
        $request->validate([
            'estado_tramite_id' => 'required|exists:estado_tramites,id',
            'observacion' => 'nullable|string',
        ]);

        $tramite = Tramite::findOrFail($id);

        // Crear registro de cambio de estado
        Tramiteregistro::create([
            'tramite_id' => $id,
            'estado_tramite_id' => $request->estado_tramite_id,
            'observacion' => $request->observacion ?: 'Cambio de estado realizado por administrador',
            'user_id' => auth()->id(),
        ]);

        // Si el estado es "Completado" o "Resuelto", actualizar fecha de resolución
        $estado = Estadotramite::find($request->estado_tramite_id);
        if (in_array(strtolower($estado->nombre), ['completado', 'resuelto', 'finalizado'])) {
            $tramite->update(['fecha_resolucion' => now()]);
        }

        return redirect()->back()->with('success', 'Estado del trámite actualizado correctamente.');
    }
    public function updateEstadoPago(Request $request, $id)
    {
        $request->validate([
            'estado_pago_id' => 'required|exists:estado_pagos,id',
            'observacion' => 'nullable|string',
        ]);

        $tramite = Tramite::findOrFail($id);

        // Validar si el trámite requiere pago
        if (!$tramite->tipoTramite->requiere_pago) {
            return redirect()->back()->with('error', 'Este trámite no requiere gestión de pagos.');
        }

        // Obtener el último registro de pago
        $ultimoRegistroPago = $tramite->tramitePagoRegistros()->latest('fecha_registro')->first();

        $estadoPago = Estadopago::find($request->estado_pago_id);

        // Si el nuevo estado es APROBADO y el estado actual NO era aprobado, sumar el monto
        if (strtolower($estadoPago->nombre) == 'aprobado') {
            // Verificar si el estado actual no era aprobado para no duplicar
            $estadoActual = $ultimoRegistroPago?->estadoPago;
            if (!$estadoActual || strtolower($estadoActual->nombre) != 'aprobado') {
                $montoAAprobar = $ultimoRegistroPago?->monto ?? 0;

                // En lugar de incrementar, recalcular desde cero
                $totalAprobado = $tramite->tramitePagoRegistros()
                    ->whereHas('estadoPago', function($q) {
                        $q->where('nombre', 'LIKE', '%Aprobado%');
                    })
                    ->sum('monto');

                // Sumar el nuevo monto aprobado
                $tramite->update(['monto_pagado' => $totalAprobado + $montoAAprobar]);
            }
        }

        // Si el nuevo estado es RECHAZADO o PENDIENTE, recalcular todo
        if (strtolower($estadoPago->nombre) != 'aprobado') {
            $totalAprobado = $tramite->tramitePagoRegistros()
                ->whereHas('estadoPago', function($q) {
                    $q->where('nombre', 'LIKE', '%Aprobado%');
                })
                ->sum('monto');
            $tramite->update(['monto_pagado' => $totalAprobado]);
        }

        // Crear registro de cambio de estado de pago
        Tramitepagoregistro::create([
            'tramite_id' => $id,
            'pago_comprobante_id' => $ultimoRegistroPago?->pago_comprobante_id,
            'estado_pago_id' => $request->estado_pago_id,
            'monto' => $ultimoRegistroPago?->monto ?? 0,
            'fecha_registro' => now(),
            'observacion' => $request->observacion ?: 'Cambio de estado de pago realizado por administrador',
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Estado del pago actualizado correctamente.');
    }
    public function verComprobante($id)
    {
        $comprobante = Pagocomprobante::with('user', 'tramite')->findOrFail($id);

        // Verificar permisos: solo admin puede ver
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para ver este comprobante.');
        }

        if (!Storage::disk('private')->exists($comprobante->comprobante_path)) {
            abort(404, 'El archivo no existe.');
        }

        $file = Storage::disk('private')->get($comprobante->comprobante_path);
        $mimeType = Storage::disk('private')->mimeType($comprobante->comprobante_path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    public function tipoTramiteIndex()
    {
        $tipos = Tramitetipo::orderBy('created_at', 'desc')->paginate(10);
        return view('tramite.admin.tipo-tramite.index', compact('tipos'));
    }

    public function tipoTramiteStore(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'codigo' => 'nullable|string|max:30|unique:m_tramite_tipo_tramites,codigo',
            'descripcion' => 'nullable|string',
            'costo' => 'nullable|numeric|min:0',
            'requiere_pago' => 'boolean',
            'requiere_documentos' => 'boolean',
            'tiempo_estimado_dias' => 'nullable|integer|min:1',
            'estado' => 'required|in:1,0',
        ]);

        Tramitetipo::create($request->all());

        return redirect()->route('tramiteadmin.tipos-tramite.index')
            ->with('success', 'Tipo de trámite creado correctamente.');
    }

    public function tipoTramiteUpdate($id, Request $request)
    {
        $tipo = Tramitetipo::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:150',
            'codigo' => 'nullable|string|max:30|unique:m_tramite_tipo_tramites,codigo,' . $id,
            'descripcion' => 'nullable|string',
            'costo' => 'nullable|numeric|min:0',
            'requiere_pago' => 'boolean',
            'requiere_documentos' => 'boolean',
            'tiempo_estimado_dias' => 'nullable|integer|min:1',
            'estado' => 'required|in:1,0',
        ]);

        $tipo->update($request->all());

        return redirect()->route('tramiteadmin.tipos-tramite.index')
            ->with('success', 'Tipo de trámite actualizado correctamente.');
    }

    public function tipoTramiteDestroy($id)
    {
        $tipo = Tramitetipo::findOrFail($id);
        $tipo->delete();

        return redirect()->route('tramiteadmin.tipos-tramite.index')
            ->with('success', 'Tipo de trámite eliminado correctamente.');
    }
}
