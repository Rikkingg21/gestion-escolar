<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;


class ColegioController extends Controller
{
public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $currentRole = session('current_role');

            // Validar que el rol existe
            $role = \App\Models\Role::where('nombre', $currentRole)->first();
            if (!$role || $role->estado != '1') {
                abort(403, 'Rol no válido o inactivo');
            }

            // Buscar el módulo actual
            $module = \App\Models\Module::where('ruta_base', 'colegioconfig/edit')->first();
            if (!$module || $module->estado != '1') {
                abort(403, 'Módulo no encontrado o inactivo');
            }

            // Verificar si el rol tiene acceso al módulo
            $hasAccess = \App\Models\Rolemodule::where('role_id', $role->id)
                ->where('module_id', $module->id)
                ->where('estado', '1')
                ->exists();

            if (!$hasAccess) {
                abort(403, 'No tienes permisos para acceder a este módulo');
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
