<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ColegioController extends Controller
{

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Colegio $colegio)
    {
        //
    }

    public function edit(Colegio $colegio)
    {
        $colegio = Colegio::configuracion();
        return view('colegioconfig.edit', compact('colegio'));
    }

    public function update(Request $request, Colegio $colegio)
    {
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

    public function destroy(Colegio $colegio)
    {
        //
    }
}
