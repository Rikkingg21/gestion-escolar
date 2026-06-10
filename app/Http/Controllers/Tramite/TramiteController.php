<?php

namespace App\Http\Controllers\Tramite;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Apoderado;
use App\Models\Estudiante;
use App\Models\Tramite\Tramite;
use App\Models\Tramite\Tramitetipo;
use App\Models\Tramite\Tramitepagoregistro;
use App\Models\Tramite\Tramiteregistro;
use App\Models\Tramite\Estadopago;
use App\Models\Tramite\Estadotramite;
use App\Models\Tramite\Pagocomprobante;
use App\Models\Metodopago\Tipopago;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class TramiteController extends Controller
{
    //moduleID 21 = Mis Tramites
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('21')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        $tipoTramitesActivos = Tramitetipo::where('estado', '1')->get();

        $tramites = Tramite::with(['tipoTramite', 'tramiteRegistros.estadoTramite', 'tramitePagoRegistros.estadoPago'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener estudiantes del usuario autenticado
        $estudiantes = collect();
        $parentesco = null; // Variable para almacenar el parentesco
        $user = Auth::user();

        // Verificar si el usuario tiene un apoderado registrado
        $apoderado = Apoderado::where('user_id', $user->id)->first();

        if ($apoderado) {
            // Si es apoderado, obtener sus estudiantes y su parentesco
            $parentesco = $apoderado->parentesco; // ← Aquí está el parentesco
            $estudiantes = Estudiante::with('user', 'grado')
                ->where('apoderado_id', $apoderado->id)
                ->where('estado', '1')
                ->get();
        } else {
            // Si no es apoderado, podría ser el mismo estudiante
            $estudiante = Estudiante::where('user_id', $user->id)->first();
            if ($estudiante) {
                $estudiantes = collect([$estudiante]);
                $parentesco = 'Estudiante mismo'; // Parentesco por defecto
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

        // Obtener el parentesco del usuario si es apoderado
        $apoderado = Apoderado::where('user_id', $user->id)->first();
        $relacion = null;

        if ($apoderado) {
            $relacion = $apoderado->parentesco;
        } else {
            // Si no es apoderado, verificar si es el mismo estudiante
            $estudiante = Estudiante::where('user_id', $user->id)->first();
            if ($estudiante && $estudiante->id == $request->estudiante_id) {
                $relacion = 'Propio estudiante';
            }
        }

        // Generar código único
        $ultimoTramite = Tramite::withTrashed()->orderBy('id', 'desc')->first();
        $numero = $ultimoTramite ? intval(substr($ultimoTramite->codigo_tramite, -4)) + 1 : 1;
        $codigoTramite = 'TRM-' . date('Ymd') . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

        // Crear el trámite
        $tramite = Tramite::create([
            'codigo_tramite' => $codigoTramite,
            'user_id' => $user->id,
            'tipo_tramite_id' => $request->tipo_tramite_id,
            'estudiante_id' => $request->estudiante_id,
            'relacion' => $relacion,
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
            // Si no requiere pago, estado "No requiere pago" (ID 4)
            $estadoPago = Estadopago::find(4); // ID 4 = No requiere pago
            // Si no existe el estado con ID 4, buscarlo por nombre
            if (!$estadoPago) {
                $estadoPago = Estadopago::where('nombre', 'LIKE', '%No requiere pago%')->first();
            }
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
            ->with('success', 'Trámite creado exitosamente. Código: ' . $codigoTramite);
    }
    public function show($id)
    {
        $tramite = Tramite::with([
            'tipoTramite',
            'user',
            'estudiante.user',
            'estudiante.grado',
            'tramiteRegistros' => function($query) {
                $query->with('estadoTramite', 'user')->orderBy('created_at', 'desc');
            },
            'tramitePagoRegistros' => function($query) {
                $query->with('estadoPago', 'user', 'pagoComprobante')->orderBy('fecha_registro', 'desc');
            }
        ])->findOrFail($id);

        // Verificar que el usuario sea el dueño del trámite o tenga permisos de admin
        if ($tramite->user_id != auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para ver este trámite.');
        }

        // Obtener los tipos de pago activos
        $tiposPago = Tipopago::where('estado', '1')->get();

        // Obtener el estado actual
        $estadoActual = $tramite->tramiteRegistros->first();
        $estadoPagoActual = $tramite->tramitePagoRegistros->first();

        return view('tramite.mis-tramites.show', compact('tramite', 'estadoActual', 'estadoPagoActual', 'tiposPago'));
    }
    public function subirComprobante(Request $request, $id)
    {
        $request->validate([
            'numero_operacion' => 'required|string',
            'monto' => 'required|numeric|min:0.01',
            'tipo_pago_id' => 'required|exists:m_tipo_pagos,id',
            'comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observaciones' => 'nullable|string',
        ]);

        $tramite = Tramite::findOrFail($id);

        if ($tramite->user_id != auth()->id()) {
            abort(403);
        }

        if (!$tramite->tipoTramite->requiere_pago) {
            return redirect()->back()->with('error', 'Este trámite no requiere pago.');
        }

        // Guardar archivo en storage/app/private/comprobantes/
        $path = $request->file('comprobante')->store('comprobantes/' . date('Y/m'), 'private');

        $comprobante = Pagocomprobante::create([
            'tramite_id' => $id,
            'user_id' => auth()->id(),
            'metodo_pago_id' => $request->tipo_pago_id,
            'numero_operacion' => $request->numero_operacion,
            'monto' => $request->monto,
            'fecha_pago' => now(),
            'comprobante_path' => $path,
            'observaciones' => $request->observaciones,
        ]);

        // Registrar el cambio de estado (Pendiente de revisión, NO suma al monto pagado)
        $estadoPagoEnRevision = Estadopago::where('nombre', 'LIKE', '%Revisión%')->first();
        if (!$estadoPagoEnRevision) {
            $estadoPagoEnRevision = Estadopago::where('nombre', 'LIKE', '%Pendiente%')->first();
        }

        Tramitepagoregistro::create([
            'tramite_id' => $id,
            'pago_comprobante_id' => $comprobante->id,
            'estado_pago_id' => $estadoPagoEnRevision ? $estadoPagoEnRevision->id : 1,
            'monto' => $request->monto,
            'fecha_registro' => now(),
            'observacion' => 'Usuario subió comprobante de pago - Pendiente de revisión',
            'user_id' => auth()->id(),
        ]);

        // NO actualizar monto_pagado aquí, solo cuando el admin apruebe

        return redirect()->route('mis-tramites.show', $id)
            ->with('success', 'Comprobante subido correctamente. Será revisado por el administrador.');
    }
    public function verComprobante($id)
    {
        $comprobante = Pagocomprobante::with('user', 'tramite')->findOrFail($id);

        // Verificar permisos: solo el dueño del trámite o admin pueden ver
        $user = auth()->user();
        $esAdmin = $user->hasRole('admin');
        $esPropietario = $comprobante->tramite->user_id == $user->id;

        if (!$esAdmin && !$esPropietario) {
            abort(403, 'No tienes permiso para ver este comprobante.');
        }

        // Verificar si el archivo existe
        if (!Storage::disk('private')->exists($comprobante->comprobante_path)) {
            abort(404, 'El archivo no existe.');
        }

        // Obtener el archivo y mostrarlo
        $file = Storage::disk('private')->get($comprobante->comprobante_path);
        $mimeType = Storage::disk('private')->mimeType($comprobante->comprobante_path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }
}
