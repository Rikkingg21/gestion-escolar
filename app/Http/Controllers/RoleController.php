<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // Módulos de pensiones solo asignables si la IE es privada y tiene pensiones activas
    const MODULOS_PENSIONES = [24, 25];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('3')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $rolesActivos = Role::where('estado', '1')->get();
        $rolesInactivos = Role::where('estado', '0')->get();

        return view('role.index', compact('rolesActivos', 'rolesInactivos'));
    }

    public function create()
    {
        return view('role.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'required|in:1,0',
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio',
            'nombre.unique' => 'Ya existe un rol con este nombre',
            'estado.required' => 'El estado es obligatorio',
        ]);

        try {
            Role::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado,
            ]);

            return redirect()->route('role.index')
                ->with('success', 'Rol creado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el rol: '.$e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        return view('role.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:roles,nombre,'.$id,
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'required|in:1,0',
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio',
            'nombre.unique' => 'Ya existe un rol con este nombre',
            'estado.required' => 'El estado es obligatorio',
        ]);

        try {
            $role->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado,
            ]);

            return redirect()->route('role.index')
                ->with('success', 'Rol actualizado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el rol: '.$e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        try {
            // Verificar si el rol está siendo usado
            if ($role->users()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados');
            }

            if ($role->modules()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar el rol porque tiene módulos asignados');
            }

            $role->delete();

            return redirect()->route('role.index')
                ->with('success', 'Rol eliminado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar el rol: '.$e->getMessage());
        }
    }

    public function module($id)
    {
        $role = Role::findOrFail($id);

        // Módulos asignados al rol
        $modulesAsignados = $role->modules()
            ->wherePivot('estado', '1')
            ->get();

        // Módulos disponibles (todos los activos menos los ya asignados)
        $pensionesHabilitadas = Colegio::configuracion()->pensionesHabilitadas();

        $modulesDisponibles = Module::where('estado', '1')
            ->whereNotIn('id', $modulesAsignados->pluck('id'))
            ->when(! $pensionesHabilitadas, fn ($query) => $query->whereNotIn('id', self::MODULOS_PENSIONES))
            ->get();

        return view('role.module', compact('role', 'modulesAsignados', 'modulesDisponibles', 'pensionesHabilitadas'));
    }

    public function assignModule(Request $request, $roleId)
    {
        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'estado' => 'required|in:1,0',
        ]);

        try {
            $role = Role::findOrFail($roleId);

            // Los módulos de pensiones solo pueden asignarse si la IE es privada
            // y tiene el módulo de pensiones activado
            if (in_array((int) $request->module_id, self::MODULOS_PENSIONES)
                && ! Colegio::configuracion()->pensionesHabilitadas()) {
                return redirect()->back()
                    ->with('error', 'No puedes asignar módulos de pensiones porque la institución no es privada o el módulo de pensiones está desactivado.');
            }

            // Verificar si ya existe la relación
            $existing = $role->modules()->where('module_id', $request->module_id)->first();

            if ($existing) {
                // Actualizar estado si ya existe
                $role->modules()->updateExistingPivot($request->module_id, [
                    'estado' => $request->estado,
                ]);
            } else {
                // Crear nueva relación
                $role->modules()->attach($request->module_id, [
                    'estado' => $request->estado,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Módulo asignado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al asignar módulo: '.$e->getMessage());
        }
    }

    public function removeModule($roleId, $moduleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->modules()->detach($moduleId);

            return redirect()->back()
                ->with('success', 'Módulo removido exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al remover módulo: '.$e->getMessage());
        }
    }
}
