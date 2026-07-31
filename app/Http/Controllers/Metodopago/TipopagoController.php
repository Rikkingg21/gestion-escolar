<?php

namespace App\Http\Controllers\Metodopago;

use App\Http\Controllers\Controller;
use App\Models\Metodopago\Tipopago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipopagoController extends Controller
{
    // moduleID 20 = Tipos de Pago
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('20')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        $categoria = $request->get('categoria');
        $estado = $request->get('estado');

        $query = Tipopago::query();

        // Filtros
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('entidad_financiera', 'LIKE', "%{$search}%")
                    ->orWhere('titular_cuenta', 'LIKE', "%{$search}%");
            });
        }

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        if ($estado !== null && $estado !== '') {
            $query->where('estado', $estado);
        }

        $tiposPago = $query->orderBy('nombre', 'asc')->paginate(15);

        // Obtener categorías únicas para el filtro
        $categorias = Tipopago::select('categoria')->distinct()->pluck('categoria');

        return view('metodopago.index', compact('tiposPago', 'categorias'));
    }

    public function create()
    {
        return view('metodopago.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50|unique:m_tipo_pagos,nombre',
            'categoria' => 'required|string|max:30',
            'entidad_financiera' => 'nullable|string|max:50',
            'numero_cuenta' => 'nullable|string|max:30',
            'cci' => 'nullable|string|max:20|unique:m_tipo_pagos,cci',
            'titular_cuenta' => 'nullable|string|max:100',
            'numero_celular' => 'nullable|string|max:15',
            'requiere_verificacion' => 'nullable|boolean',
            'color_hex' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'estado' => 'required|in:1,0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $tipopago = Tipopago::create([
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'entidad_financiera' => $request->entidad_financiera,
            'numero_cuenta' => $request->numero_cuenta,
            'cci' => $request->cci,
            'titular_cuenta' => $request->titular_cuenta,
            'numero_celular' => $request->numero_celular,
            'requiere_verificacion' => $request->requiere_verificacion ?? false,
            'color_hex' => $request->color_hex ?? '#FFFFFF',
            'estado' => $request->estado,
        ]);

        return redirect()->route('metodopago.index')
            ->with('success', 'Método de pago creado exitosamente.');
    }

    public function show(string $id)
    {
        $tipopago = Tipopago::findOrFail($id);

        return view('metodopago.show', compact('tipopago'));
    }

    public function edit(string $id)
    {
        $tipopago = Tipopago::findOrFail($id);

        return view('metodopago.edit', compact('tipopago'));
    }

    public function update(Request $request, string $id)
    {
        $tipopago = Tipopago::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50|unique:m_tipo_pagos,nombre,'.$id,
            'categoria' => 'required|string|max:30',
            'entidad_financiera' => 'nullable|string|max:50',
            'numero_cuenta' => 'nullable|string|max:30',
            'cci' => 'nullable|string|max:20|unique:m_tipo_pagos,cci,'.$id,
            'titular_cuenta' => 'nullable|string|max:100',
            'numero_celular' => 'nullable|string|max:15',
            'requiere_verificacion' => 'nullable|boolean',
            'color_hex' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'estado' => 'required|in:1,0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $tipopago->update([
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'entidad_financiera' => $request->entidad_financiera,
            'numero_cuenta' => $request->numero_cuenta,
            'cci' => $request->cci,
            'titular_cuenta' => $request->titular_cuenta,
            'numero_celular' => $request->numero_celular,
            'requiere_verificacion' => $request->requiere_verificacion ?? false,
            'color_hex' => $request->color_hex ?? '#FFFFFF',
            'estado' => $request->estado,
        ]);

        return redirect()->route('metodopago.index')
            ->with('success', 'Método de pago actualizado exitosamente.');
    }

    public function destroy(string $id)
    {
        $tipopago = Tipopago::findOrFail($id);
        $tipopago->delete();

        return redirect()->route('metodopago.index')
            ->with('success', 'Método de pago eliminado exitosamente.');
    }

    public function changeStatus(string $id)
    {
        $tipopago = Tipopago::findOrFail($id);
        $tipopago->estado = $tipopago->estado == '1' ? '0' : '1';
        $tipopago->save();

        $message = $tipopago->estado == '1'
            ? 'Método de pago activado exitosamente.'
            : 'Método de pago desactivado exitosamente.';

        return redirect()->route('metodopago.index')
            ->with('success', $message);
    }
}
