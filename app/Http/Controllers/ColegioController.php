<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;


class ColegioController extends Controller
{
    //moduleID 6 = colegio
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('6')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }

    public function edit(Colegio $colegio)
    {

        $colegio = Colegio::configuracion();
        return view('rol.admin.colegioconfig.edit', compact('colegio'));
    }

    public function update(Request $request, Colegio $colegio)
    {

        //dd($request->all());
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'ruc' => 'nullable|string|size:11',
            'director_actual' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:5000'
        ]);

        $colegio = Colegio::configuracion();

        // Manejo del logo
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $rutaImagen = $file->store('logo', ['disk' => 'public']);

            // Solo eliminar si ya existe un logo
            if ($colegio->logo_path && Storage::disk('public')->exists($colegio->logo_path)) {
                Storage::disk('public')->delete($colegio->logo_path);
            }

            $colegio->logo_path = $rutaImagen;
        }

        // Actualizar datos
        $colegio->update($validated);

        return redirect()->route('colegioconfig.edit')
            ->with('success', 'Configuración del colegio actualizada correctamente');
    }
}
