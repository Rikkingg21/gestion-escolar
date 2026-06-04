<?php

namespace App\Http\Controllers\Tramite;

use App\Http\Controllers\Controller;
use App\Models\Tramite\Tramite;
use App\Models\Tramite\Tramitetipo;
use Illuminate\Http\Request;

class TramiteController extends Controller
{
    public function index()
    {
        $tramites = Tramite::with(['tipoTramite', 'estadoTramite', 'estadoPago'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('tramite.mis-tramites.index', compact('tramites'));
    }
    public function create()
    {
        $tipos = Tramitetipo::where('estado', '1')->get();
        return view('tramite.mis-tramites.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_tramite_id' => 'required|exists:m_tramite_tipo_tramites,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'relacion' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
        ]);

        // Generar código único
        $anio = date('Y');
        $ultimo = Tramite::whereYear('created_at', $anio)->count();
        $codigo = 'TRAM-' . $anio . '-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);

        $tramite = Tramite::create([
            'codigo_tramite' => $codigo,
            'user_id' => auth()->id(),
            'tipo_tramite_id' => $request->tipo_tramite_id,
            'estudiante_id' => $request->estudiante_id,
            'relacion' => $request->relacion,
            'estado_tramite_id' => 1, // Pendiente
            'estado_pago_id' => 1, // Pendiente
            'monto_pagado' => 0,
            'fecha_solicitud' => date('Y-m-d'),
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('tramite.index')
            ->with('success', 'Trámite solicitado correctamente. Código: ' . $codigo);
    }

    public function show($id)
    {
        $tramite = Tramite::with(['tipoTramite', 'estadoTramite', 'estadoPago', 'user', 'estudiante'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return view('tramite.mis-tramites.show', compact('tramite'));
    }

    public function seguimiento($id)
    {
        $tramite = Tramite::with(['tipoTramite', 'estadoTramite', 'estadoPago'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return view('tramite.mis-tramites.seguimiento', compact('tramite'));
    }

    public function cancelar($id)
    {
        $tramite = Tramite::where('user_id', auth()->id())
            ->whereIn('estado_tramite_id', [1]) // Solo pendientes
            ->findOrFail($id);

        $tramite->estado_tramite_id = 6; // Cancelado
        $tramite->save();

        return redirect()->route('tramite.index')
            ->with('success', 'Trámite cancelado correctamente.');
    }
}
