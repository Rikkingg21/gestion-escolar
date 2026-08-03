<?php

namespace App\Http\Controllers\Tramite;

use App\Http\Controllers\Controller;
use App\Models\Tramite\Estadopago;
use App\Models\Tramite\Estadotramite;
use App\Models\Tramite\Pagocomprobante;
use App\Models\Tramite\Tramite;
use App\Models\Tramite\Tramitepagoregistro;
use App\Models\Tramite\Tramiteregistro;
use App\Models\Tramite\Tramitetipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TramiteadminController extends Controller
{
    // moduleID 19 = Trámites - admin
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('19')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Estadísticas de TRÁMITES - obtener el último estado
        $ultimosEstados = Tramiteregistro::select('tramite_id', 'estado_tramite_id')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('m_tramite_registros')
                    ->groupBy('tramite_id');
            })->get();

        $estadosNombres = Estadotramite::whereIn('id', $ultimosEstados->pluck('estado_tramite_id')->unique())
            ->pluck('nombre', 'id');

        $totalTramites = Tramite::count();
        $totalPendientes = 0;
        $totalEnProceso = 0;
        $totalCompletados = 0;

        foreach ($ultimosEstados as $ultimoEstado) {
            $nombre = strtolower($estadosNombres[$ultimoEstado->estado_tramite_id] ?? '');

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
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('m_tramite_pago_registros')
                    ->groupBy('tramite_id');
            })->get();

        $estadosPagoNombres = Estadopago::whereIn('id', $ultimosPagos->pluck('estado_pago_id')->unique())
            ->pluck('nombre', 'id');

        $totalPagosPendientes = 0;
        $totalPagosAprobados = 0;
        $totalPagosRechazados = 0;

        foreach ($ultimosPagos as $ultimoPago) {
            $nombre = strtolower($estadosPagoNombres[$ultimoPago->estado_pago_id] ?? '');

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
            'tramiteRegistros' => function ($q) {
                $q->with('estadoTramite')->latest();
            },
            'tramitePagoRegistros' => function ($q) {
                $q->with('estadoPago')->latest('fecha_registro');
            },
        ]);

        // Filtro por búsqueda
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_tramite', 'LIKE', "%{$buscar}%")
                    ->orWhereHas('user', function ($qr) use ($buscar) {
                        $qr->where('dni', 'LIKE', "%{$buscar}%")
                            ->orWhere('nombre', 'LIKE', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'LIKE', "%{$buscar}%")
                            ->orWhere('apellido_materno', 'LIKE', "%{$buscar}%");
                    })
                    ->orWhereHas('estudiante.user', function ($qr) use ($buscar) {
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
            $query->whereHas('tramiteRegistros', function ($q) use ($request) {
                $q->where('estado_tramite_id', $request->estado_tramite)
                    ->whereIn('id', function ($sub) {
                        $sub->selectRaw('MAX(id)')
                            ->from('m_tramite_registros')
                            ->groupBy('tramite_id');
                    });
            });
        }

        // Filtro por estado de pago
        if ($request->filled('estado_pago')) {
            $query->whereHas('tramitePagoRegistros', function ($q) use ($request) {
                $q->where('estado_pago_id', $request->estado_pago)
                    ->whereIn('id', function ($sub) {
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
            'tramiteRegistros' => function ($query) {
                $query->with('estadoTramite', 'user')->orderBy('created_at', 'desc');
            },
            'tramitePagoRegistros' => function ($query) {
                $query->with(['estadoPago', 'user', 'pagoComprobante'])->orderBy('fecha_registro', 'desc');
            },
        ])->findOrFail($id);

        $estadosTramite = Estadotramite::all();
        $estadosPago = Estadopago::all();

        // Verificar si requiere pago
        $requierePago = $tramite->tipoTramite->requiere_pago ?? false;

        // Calcular costo total
        $costoTotal = $requierePago ? ($tramite->tipoTramite->costo ?? 0) : 0;

        // Calcular monto pagado (usando el campo directo del modelo o el accessor)
        $montoPagado = $tramite->monto_pagado ?? 0;

        // Calcular saldo pendiente
        $saldoPendiente = $costoTotal - $montoPagado;

        // Fechas formateadas
        $fechaSolicitud = $tramite->fecha_solicitud
            ? \Carbon\Carbon::parse($tramite->fecha_solicitud)->format('d/m/Y')
            : 'N/A';

        $fechaResolucion = $tramite->fecha_resolucion
            ? \Carbon\Carbon::parse($tramite->fecha_resolucion)->format('d/m/Y')
            : 'Pendiente';

        // Datos del solicitante
        $solicitante = [
            'nombre_completo' => trim(($tramite->user->nombre ?? 'N/A').' '.($tramite->user->apellido_paterno ?? '').' '.($tramite->user->apellido_materno ?? '')),
            'dni' => $tramite->user->dni ?? 'N/A',
            'email' => $tramite->user->email ?? 'N/A',
            'telefono' => $tramite->user->telefono ?? 'N/A',
        ];

        // Datos del estudiante
        $estudianteData = [
            'nombre_completo' => ($tramite->estudiante->user->nombre ?? 'N/A').' '.($tramite->estudiante->user->apellido_paterno ?? ''),
            'dni' => $tramite->estudiante->user->dni ?? 'N/A',
        ];

        // Observación general
        $observacionGeneral = $tramite->observaciones ?? 'Sin observaciones registradas';

        // Estado actual del trámite
        $estadoActualTramite = $tramite->tramiteRegistros->first();

        // Historial de trámites enriquecido
        $historialTramites = $tramite->tramiteRegistros->map(function ($registro) {
            return [
                'registro' => $registro,
                'fecha_formateada' => $registro->created_at->format('d/m/Y H:i'),
                'color_estado' => $registro->estadoTramite->color ?? '#6c757d',
                'nombre_estado' => $registro->estadoTramite->nombre ?? 'N/A',
                'nombre_usuario' => $registro->user->nombre ?? 'Sistema',
                'observacion' => $registro->observacion,
            ];
        });

        $totalRegistrosTramite = $tramite->tramiteRegistros->count();

        // Datos de pagos enriquecidos (incluyendo observaciones del comprobante)
        $pagosEnriquecidos = $tramite->tramitePagoRegistros->map(function ($registroPago) {
            $estadoNombre = strtolower($registroPago->estadoPago->nombre ?? '');
            $tieneComprobante = ! is_null($registroPago->pago_comprobante_id);

            $iconoClase = '';
            if (str_contains($estadoNombre, 'aprobado')) {
                $iconoClase = 'bi-check-circle-fill text-success';
            } elseif (str_contains($estadoNombre, 'rechazado')) {
                $iconoClase = 'bi-x-circle-fill text-danger';
            } elseif (str_contains($estadoNombre, 'revisión')) {
                $iconoClase = 'bi-arrow-repeat text-warning';
            } elseif (str_contains($estadoNombre, 'pendiente')) {
                $iconoClase = 'bi-hourglass-split text-secondary';
            } else {
                $iconoClase = 'bi-question-circle-fill text-muted';
            }

            // Determinar si el botón de acciones debe mostrarse (pendiente o en revisión y tiene comprobante)
            $mostrarBotonesAccion = $tieneComprobante &&
                ! str_contains($estadoNombre, 'aprobado') &&
                ! str_contains($estadoNombre, 'rechazado');

            return [
                'registro' => $registroPago,
                'id' => $registroPago->id,
                'fecha_formateada' => $registroPago->fecha_registro
                    ? \Carbon\Carbon::parse($registroPago->fecha_registro)->format('d/m/Y H:i')
                    : $registroPago->created_at->format('d/m/Y H:i'),
                'color_estado' => $registroPago->estadoPago->color ?? '#6c757d',
                'nombre_estado' => $registroPago->estadoPago->nombre ?? 'N/A',
                'nombre_usuario' => $registroPago->user->nombre ?? 'Sistema',
                'monto_formateado' => 'S/ '.number_format($registroPago->monto, 2),
                'observacion' => $registroPago->observacion,
                'icono_clase' => $iconoClase,
                'tiene_comprobante' => $tieneComprobante,
                'tiene_archivo' => $tieneComprobante && ! is_null($registroPago->pagoComprobante?->comprobante_path),
                'mostrar_botones_accion' => $mostrarBotonesAccion,
                'comprobante' => $tieneComprobante ? [
                    'id' => $registroPago->pagoComprobante->id,
                    'numero_operacion' => $registroPago->pagoComprobante->numero_operacion ?? 'N/A',
                    'observaciones' => $registroPago->pagoComprobante->observaciones ?? 'Sin observaciones',
                    'metodo_pago_nombre' => $registroPago->pagoComprobante->metodoPago->nombre ?? 'N/A',
                    'metodo_pago_entidad' => $registroPago->pagoComprobante->metodoPago->entidad_financiera ?? null,
                    'metodo_pago_cuenta' => $registroPago->pagoComprobante->metodoPago->numero_cuenta ?? null,
                ] : null,
            ];
        });

        $totalRegistrosPago = $tramite->tramitePagoRegistros->count();

        // Preparar opciones para el select de comprobantes en el modal
        $opcionesComprobantes = $tramite->tramitePagoRegistros
            ->filter(function ($pagoRegistro) {
                return ! is_null($pagoRegistro->pago_comprobante_id);
            })
            ->map(function ($pagoRegistro) {
                $estadoNombre = strtolower($pagoRegistro->estadoPago->nombre ?? '');
                $icono = '';
                if (str_contains($estadoNombre, 'aprobado')) {
                    $icono = '✅';
                } elseif (str_contains($estadoNombre, 'rechazado')) {
                    $icono = '❌';
                } elseif (str_contains($estadoNombre, 'revisión')) {
                    $icono = '🔄';
                } elseif (str_contains($estadoNombre, 'pendiente')) {
                    $icono = '⏳';
                }

                return [
                    'id' => $pagoRegistro->id,
                    'fecha' => $pagoRegistro->fecha_registro
                        ? \Carbon\Carbon::parse($pagoRegistro->fecha_registro)->format('d/m/Y H:i')
                        : $pagoRegistro->created_at->format('d/m/Y H:i'),
                    'monto' => $pagoRegistro->monto,
                    'monto_formateado' => 'S/ '.number_format($pagoRegistro->monto, 2),
                    'estado_nombre' => $pagoRegistro->estadoPago->nombre ?? 'N/A',
                    'icono' => $icono,
                    'numero_operacion' => $pagoRegistro->pagoComprobante->numero_operacion ?? 'N/A',
                ];
            });

        return view('tramite.admin.show', compact(
            'tramite',
            'estadosTramite',
            'estadosPago',
            'requierePago',
            'costoTotal',
            'montoPagado',
            'saldoPendiente',
            'fechaSolicitud',
            'fechaResolucion',
            'solicitante',
            'estudianteData',
            'observacionGeneral',
            'estadoActualTramite',
            'historialTramites',
            'totalRegistrosTramite',
            'pagosEnriquecidos',
            'totalRegistrosPago',
            'opcionesComprobantes'
        ));
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

        // Si el estado es "Completado" o "Resuelto", actualizar fecha de resolución;
        // si ya no es un estado final, limpiarla
        $estado = Estadotramite::find($request->estado_tramite_id);
        $esEstadoFinal = in_array(strtolower($estado->nombre ?? ''), ['completado', 'resuelto', 'finalizado']);

        if ($esEstadoFinal) {
            $tramite->update(['fecha_resolucion' => now()]);
        } elseif ($tramite->fecha_resolucion) {
            $tramite->update(['fecha_resolucion' => null]);
        }

        return redirect()->back()->with('success', 'Estado del trámite actualizado correctamente.');
    }

    public function updateEstadoPago(Request $request, $id)
    {
        $request->validate([
            'pago_registro_id' => 'required|exists:m_tramite_pago_registros,id',
            'estado_pago_id' => 'required|exists:estado_pagos,id',
            'observacion' => 'nullable|string',
        ]);

        $tramite = Tramite::findOrFail($id);

        // Validar si el trámite requiere pago
        if (! $tramite->tipoTramite->requiere_pago) {
            return redirect()->back()->with('error', 'Este trámite no requiere gestión de pagos.');
        }

        // Obtener el registro de pago específico
        $registroPago = Tramitepagoregistro::findOrFail($request->pago_registro_id);

        // Validar que el registro de pago pertenezca a este trámite
        if ($registroPago->tramite_id != $id) {
            return redirect()->back()->with('error', 'El registro de pago no pertenece a este trámite.');
        }

        // Validar que el registro tenga comprobante (no sea el registro inicial)
        if (is_null($registroPago->pago_comprobante_id)) {
            return redirect()->back()->with('error', 'No se puede cambiar el estado del registro inicial de pago.');
        }

        // Obtener el estado actual de ese registro
        $estadoActual = Estadopago::find($registroPago->estado_pago_id);
        $nuevoEstado = Estadopago::find($request->estado_pago_id);

        // Verificar que no se esté cambiando al mismo estado
        if ($estadoActual->id == $nuevoEstado->id) {
            return redirect()->back()->with('warning', 'El estado ya es el mismo.');
        }

        $estadoActualNombre = strtolower($estadoActual->nombre ?? '');
        $nuevoEstadoNombre = strtolower($nuevoEstado->nombre ?? '');

        // Si el nuevo estado es APROBADO y el actual no lo era, sumar el monto
        if ($nuevoEstadoNombre == 'aprobado' && $estadoActualNombre != 'aprobado') {
            $tramite->increment('monto_pagado', $registroPago->monto);
        }

        // Si el estado actual era APROBADO y el nuevo ya no lo es, restar el monto
        if ($estadoActualNombre == 'aprobado' && $nuevoEstadoNombre != 'aprobado') {
            $nuevoTotal = max(0, ($tramite->monto_pagado ?? 0) - $registroPago->monto);
            $tramite->update(['monto_pagado' => $nuevoTotal]);
        }

        // Crear un NUEVO registro de cambio de estado para ese comprobante específico
        Tramitepagoregistro::create([
            'tramite_id' => $id,
            'pago_comprobante_id' => $registroPago->pago_comprobante_id,
            'estado_pago_id' => $request->estado_pago_id,
            'monto' => $registroPago->monto,
            'fecha_registro' => now(),
            'observacion' => $request->observacion ?: 'Cambio de estado de pago realizado por administrador',
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Estado del pago actualizado correctamente.');
    }

    public function verComprobante($id)
    {
        $comprobante = Pagocomprobante::with('user', 'tramite')->findOrFail($id);

        // El acceso ya está restringido al módulo 19 (tramite-admin) en el constructor,
        // así que cualquier usuario que gestione pagos puede ver el comprobante.

        if (! $comprobante->comprobante_path) {
            abort(404, 'Este comprobante no tiene archivo adjunto (pago en efectivo).');
        }

        if (! Storage::disk('private')->exists($comprobante->comprobante_path)) {
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
            'codigo' => 'nullable|string|max:30|unique:m_tramite_tipo_tramites,codigo,'.$id,
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
