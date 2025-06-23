<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ColegioController extends Controller
{

    public function edit(Colegio $colegio)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso denegado');
        }
        $colegio = Colegio::configuracion();
        return view('rol.admin.colegioconfig.edit', compact('colegio'));
    }

    public function update(Request $request, Colegio $colegio)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso denegado');
        }
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'ruc' => 'nullable|string|size:11',
            'director_actual' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $colegio = Colegio::configuracion();

        // Manejo del logo
        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($colegio->logo_path) {
                Storage::delete(str_replace('storage/', 'public/', $colegio->logo_path));
            }

            $path = $request->file('logo')->store('public/logos');
            $validated['logo_path'] = str_replace('public/', 'storage/', $path);
        }

        // Actualizar datos
        $colegio->update($validated);

        return redirect()->route('colegioconfig.edit')
            ->with('success', 'Configuración del colegio actualizada correctamente');
    }
}
