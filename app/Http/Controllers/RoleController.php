<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\Module;


class RoleController extends Controller
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

            // Para role controller, el módulo base es 'role'
            $module = \App\Models\Module::where('ruta_base', 'role')->first();
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
            'estado' => 'required|in:1,0'
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio',
            'nombre.unique' => 'Ya existe un rol con este nombre',
            'estado.required' => 'El estado es obligatorio'
        ]);

        try {
            Role::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado
            ]);

            return redirect()->route('role.index')
                ->with('success', 'Rol creado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el rol: ' . $e->getMessage())
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
            'nombre' => 'required|string|max:100|unique:roles,nombre,' . $id,
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'required|in:1,0'
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio',
            'nombre.unique' => 'Ya existe un rol con este nombre',
            'estado.required' => 'El estado es obligatorio'
        ]);

        try {
            $role->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado
            ]);

            return redirect()->route('role.index')
                ->with('success', 'Rol actualizado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el rol: ' . $e->getMessage())
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
                ->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
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
        $modulesDisponibles = Module::where('estado', '1')
            ->whereNotIn('id', $modulesAsignados->pluck('id'))
            ->get();

        return view('role.module', compact('role', 'modulesAsignados', 'modulesDisponibles'));
    }
    public function assignModule(Request $request, $roleId)
    {
        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'estado' => 'required|in:1,0'
        ]);

        try {
            $role = Role::findOrFail($roleId);

            // Verificar si ya existe la relación
            $existing = $role->modules()->where('module_id', $request->module_id)->first();

            if ($existing) {
                // Actualizar estado si ya existe
                $role->modules()->updateExistingPivot($request->module_id, [
                    'estado' => $request->estado
                ]);
            } else {
                // Crear nueva relación
                $role->modules()->attach($request->module_id, [
                    'estado' => $request->estado
                ]);
            }

            return redirect()->back()
                ->with('success', 'Módulo asignado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al asignar módulo: ' . $e->getMessage());
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
                ->with('error', 'Error al remover módulo: ' . $e->getMessage());
        }
    }
    public function selectRole()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $roles = Role::all();
        } elseif ($user->isDirector()) {
            $roles = Role::where('nombre', '!=', 'admin')->get();
        } else {
            return redirect()->route('home');
        }

        return view('select-role', compact('roles'));
    }

    public function switchRole(Request $request)
    {
        $request->validate(['role_id' => 'required|exists:roles,id']);

        $role = Role::find($request->role_id);
        $request->session()->put('current_role', $role->nombre);
        $request->session()->put('current_role_id', $role->id);

        return redirect()->route('home');
    }
}
