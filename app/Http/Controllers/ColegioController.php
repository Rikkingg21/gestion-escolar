<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;


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
            $extension = $file->getClientOriginalExtension();
            $nombreArchivo = 'logo-actual.' . $extension;

            // Ruta destino: public/storage/logo/
            $directorio = public_path('storage/logo');

            // Crear directorio si no existe
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Eliminar logo anterior si existe
            $archivos = glob($directorio . '/logo-actual.*');
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    unlink($archivo);
                }
            }

            // Mover nuevo logo
            $file->move($directorio, $nombreArchivo);

            // Guardar ruta en BD
            $colegio->logo_path = 'logo/' . $nombreArchivo;
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
        $colegio->fill($validated);
        $colegio->save();

        return redirect()->route('colegioconfig.edit', $colegio)
            ->with('success', 'Configuración del colegio actualizada correctamente');
    }
}
