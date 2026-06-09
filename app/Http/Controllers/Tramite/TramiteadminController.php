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
        // Estadísticas
        $totalTramites = Tramite::count();
        $totalPendientes = Tramite::whereHas('tramiteRegistros', function($q) {
            $q->whereHas('estadoTramite', function($qr) {
                $qr->where('nombre', 'LIKE', '%Pendiente%');
            });
        })->count();

        $totalEnProceso = Tramite::whereHas('tramiteRegistros', function($q) {
            $q->whereHas('estadoTramite', function($qr) {
                $qr->where('nombre', 'LIKE', '%Proceso%');
            });
        })->count();

        $totalCompletados = Tramite::whereHas('tramiteRegistros', function($q) {
            $q->whereHas('estadoTramite', function($qr) {
                $qr->where('nombre', 'LIKE', '%Completado%')
                   ->orWhere('nombre', 'LIKE', '%Finalizado%')
                   ->orWhere('nombre', 'LIKE', '%Resuelto%');
            });
        })->count();

        $totalPagosPendientes = Tramite::whereHas('tramitePagoRegistros', function($q) {
            $q->whereHas('estadoPago', function($qr) {
                $qr->where('nombre', 'LIKE', '%Pendiente%');
            });
        })->count();

        $totalPagosAprobados = Tramite::whereHas('tramitePagoRegistros', function($q) {
            $q->whereHas('estadoPago', function($qr) {
                $qr->where('nombre', 'LIKE', '%Aprobado%');
            });
        })->count();

        $totalPagosRechazados = Tramite::whereHas('tramitePagoRegistros', function($q) {
            $q->whereHas('estadoPago', function($qr) {
                $qr->where('nombre', 'LIKE', '%Rechazado%');
            });
        })->count();

        // Obtener trámites con filtros
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

        // Filtros
        if ($request->filled('estado_tramite')) {
            $query->whereHas('tramiteRegistros', function($q) use ($request) {
                $q->where('estado_tramite_id', $request->estado_tramite);
            });
        }

        if ($request->filled('estado_pago')) {
            $query->whereHas('tramitePagoRegistros', function($q) use ($request) {
                $q->where('estado_pago_id', $request->estado_pago);
            });
        }

        if ($request->filled('tipo_tramite')) {
            $query->where('tipo_tramite_id', $request->tipo_tramite);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('codigo_tramite', 'LIKE', "%{$buscar}%")
                  ->orWhereHas('user', function($qr) use ($buscar) {
                      $qr->where('dni', 'LIKE', "%{$buscar}%")
                         ->orWhere('nombre', 'LIKE', "%{$buscar}%")
                         ->orWhere('apellido_paterno', 'LIKE', "%{$buscar}%");
                  })
                  ->orWhereHas('estudiante.user', function($qr) use ($buscar) {
                      $qr->where('dni', 'LIKE', "%{$buscar}%")
                         ->orWhere('nombre', 'LIKE', "%{$buscar}%");
                  });
            });
        }

        $tramites = $query->orderBy('created_at', 'desc')->paginate(15);

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
            'totalPagosRechazados'
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

        // Obtener el último registro de pago
        $ultimoRegistroPago = $tramite->tramitePagoRegistros()->latest('fecha_registro')->first();

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

        // Si el pago es aprobado, actualizar monto pagado en el trámite
        $estadoPago = Estadopago::find($request->estado_pago_id);
        if (strtolower($estadoPago->nombre) == 'aprobado') {
            $montoAAprobar = $ultimoRegistroPago?->monto ?? 0;
            $tramite->increment('monto_pagado', $montoAAprobar);
        }

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
