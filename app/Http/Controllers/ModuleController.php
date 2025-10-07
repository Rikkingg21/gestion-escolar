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
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $modulosActivos = Module::activos()->get();
        $modulosInactivos = Module::inactivos()->get();

        return view('modules.index', compact('modulosActivos', 'modulosInactivos'));
    }

    public function create()
    {
        return view('modules.create');
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
            DB::beginTransaction();

            Module::create([
                'nombre' => $request->nombre,
                'icono' => $request->icono,
                'ruta_base' => $request->ruta_base,
                'estado' => $request->estado
            ]);

            DB::commit();

            return redirect()->route('modules.index')
                ->with('success', 'Módulo creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al crear el módulo: ' . $e->getMessage())
                ->withInput();
        }
    }
}
