<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ColegioController extends Controller
{
    // moduleID 6 = colegio
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('6')) {
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
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'ruc' => 'nullable|string|size:11',
            'director_actual' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:5000',
            'es_privado' => 'nullable|boolean',
            'pensiones_activo' => 'nullable|boolean',
            'usa_pasarela_pagos' => 'nullable|boolean',
            'culqi_modo_prueba' => 'nullable|boolean',
            'culqi_public_key' => 'nullable|string|max:255',
            'culqi_secret_key' => 'nullable|string|max:255',
        ]);

        $colegio = Colegio::configuracion();

        // Manejo del logo
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $nombreArchivo = 'logo-actual.'.$extension;

            // Ruta destino: public/storage/logo/
            $directorio = public_path('storage/logo');

            // Crear directorio si no existe
            if (! is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Eliminar logo anterior si existe
            $archivos = glob($directorio.'/logo-actual.*');
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    unlink($archivo);
                }
            }

            // Mover nuevo logo
            $file->move($directorio, $nombreArchivo);

            // Guardar ruta en BD
            $colegio->logo_path = 'logo/'.$nombreArchivo;
        }

        // Eliminar logo si se marca la casilla
        if ($request->has('eliminar_logo') && $colegio->logo_path) {
            $rutaArchivo = public_path($colegio->logo_path);
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
            $colegio->logo_path = null;
        }

        // Actualizar otros campos
        // Las llaves de la pasarela se gestionan abajo para no pisarlas con placeholders
        unset($validated['culqi_public_key'], $validated['culqi_secret_key']);
        $colegio->fill($validated);
        $colegio->es_privado = $request->boolean('es_privado');
        $colegio->pensiones_activo = $request->boolean('pensiones_activo');

        // Pasarela de pagos: solo se guardan las keys si la pasarela está activa.
        // Si un campo de key llega vacío, se conserva la key existente.
        $colegio->usa_pasarela_pagos = $request->boolean('usa_pasarela_pagos');
        $colegio->culqi_modo_prueba = $request->boolean('culqi_modo_prueba');

        if ($colegio->usa_pasarela_pagos) {
            if ($request->filled('culqi_public_key')) {
                $colegio->culqi_public_key = trim($request->culqi_public_key);
            }

            if ($request->filled('culqi_secret_key') && $request->culqi_secret_key !== '********') {
                $colegio->culqi_secret_key = trim($request->culqi_secret_key);
            }
        } else {
            // Al desactivar la pasarela se limpian las keys para no dejarlas huérfanas
            $colegio->culqi_public_key = null;
            $colegio->culqi_secret_key = null;
        }

        // Si las pensiones no están habilitadas, se quitan los módulos de pensiones
        // de todos los roles para evitar asignaciones huérfanas.
        if (! $colegio->pensionesHabilitadas()) {
            DB::table('role_modules')
                ->whereIn('module_id', [24, 25])
                ->delete();
        }

        $colegio->save();

        return redirect()->route('colegioconfig.edit', $colegio)
            ->with('success', 'Configuración del colegio actualizada correctamente');
    }
}
