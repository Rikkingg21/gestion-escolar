<?php

namespace App\Http\Controllers\Tramite;

use App\Http\Controllers\Controller;
use App\Models\Tramite\Tramitetipo;
use App\Models\Tramite\EstadoTramite;
use App\Models\Tramite\EstadoPago;
use App\Models\Tramite\Tramite;
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
        $totalTipos = Tramitetipo::count();
        $totalTramites = Tramite::count();
        $totalEstadosTramite = EstadoTramite::count();
        $totalEstadosPago = EstadoPago::count();

        // Obtener trámites con filtros
        $query = Tramite::with(['user', 'tipoTramite', 'estadoTramite', 'estadoPago']);

        if ($request->estado_tramite_id) {
            $query->where('estado_tramite_id', $request->estado_tramite_id);
        }

        if ($request->estado_pago_id) {
            $query->where('estado_pago_id', $request->estado_pago_id);
        }

        $tramites = $query->orderBy('created_at', 'desc')->paginate(15);

        $estadosTramite = EstadoTramite::all();
        $estadosPago = EstadoPago::all();

        return view('tramite.admin.index', compact(
            'totalTipos',
            'totalTramites',
            'totalEstadosTramite',
            'totalEstadosPago',
            'tramites',
            'estadosTramite',
            'estadosPago'
        ));
    }

    public function show($id)
    {
        $tramite = Tramite::with(['user', 'tipoTramite', 'estadoTramite', 'estadoPago'])->findOrFail($id);
        $estadosTramite = EstadoTramite::all();
        $estadosPago = EstadoPago::all();

        return view('tramite.admin.show', compact('tramite', 'estadosTramite', 'estadosPago'));
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
