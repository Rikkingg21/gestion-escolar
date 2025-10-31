<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\Estudiante;
use App\Models\Apoderado;
use App\Models\Docente;
use App\Models\Auxiliar;
use App\Models\Director;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;


class ModuleController extends Controller
{
    //moduleID 1 = Modulos
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('1')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }


    public function index()
    {
        $modulesActivos = Module::where('estado', '1')->get();
        $modulesInactivos = Module::where('estado', '0')->get();

        return view('module.index', compact('modulesActivos', 'modulesInactivos'));
    }

    public function create()
    {
        return view('module.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:modules,nombre',
            'icono' => 'required|string|max:50',
            'ruta_base' => 'required|string|max:100',
            'estado' => 'required|in:1,0'
        ], [
            'nombre.required' => 'El nombre del módulo es obligatorio',
            'nombre.unique' => 'Ya existe un módulo con este nombre',
            'icono.required' => 'El icono es obligatorio',
            'ruta_base.required' => 'La ruta base es obligatoria',
            'estado.required' => 'El estado es obligatorio'
        ]);

        try {
            Module::create([
                'nombre' => $request->nombre,
                'icono' => $request->icono,
                'ruta_base' => $request->ruta_base,
                'estado' => $request->estado
            ]);

            return redirect()->route('module.index')
                ->with('success', 'Módulo creado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el módulo: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function edit($id)
    {
        $module = Module::findOrFail($id);
        return view('module.edit', compact('module'));
    }
    public function update(Request $request, $id)
    {
        $module = Module::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:modules,nombre,' . $id,
            'icono' => 'required|string|max:50',
            'ruta_base' => 'required|string|max:100',
            'estado' => 'required|in:1,0'
        ], [
            'nombre.required' => 'El nombre del módulo es obligatorio',
            'nombre.unique' => 'Ya existe un módulo con este nombre',
            'icono.required' => 'El icono es obligatorio',
            'ruta_base.required' => 'La ruta base es obligatoria',
            'estado.required' => 'El estado es obligatorio'
        ]);

        try {
            $module->update([
                'nombre' => $request->nombre,
                'icono' => $request->icono,
                'ruta_base' => $request->ruta_base,
                'estado' => $request->estado
            ]);

            return redirect()->route('module.index')
                ->with('success', 'Módulo actualizado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el módulo: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function destroy($id)
    {
        $module = Module::findOrFail($id);

        try {
            if ($module->roles()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar el módulo porque está asignado a roles');
            }

            $module->delete();

            return redirect()->route('module.index')
                ->with('success', 'Módulo eliminado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar el módulo: ' . $e->getMessage());
        }
    }
}
