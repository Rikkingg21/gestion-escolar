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

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,director'])->except(['show']);
    }

    public function index(Request $request)
    {
        $currentRole = session('current_role');
        $perPage = 10;
        $activeTab = $request->query('tab', 'all');
        $search = $request->query('search');

        // Base query para todos los usuarios
        $baseQuery = User::with('roles')
            ->when($search, function($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido_paterno', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        // Aplicar restricciones según el rol actual
        if ($currentRole === 'director') {
            $baseQuery->whereDoesntHave('roles', function($q) {
                $q->where('nombre', 'admin');
            });
        }

        // Obtener datos para cada tab
        $data = [
            'users' => $baseQuery->paginate($perPage, ['*'], 'all_page'),
            'directores' => $this->getUsersByRole($baseQuery, 'director', $perPage, 'directores_page'),
            'docentes' => $this->getUsersByRole($baseQuery, 'docente', $perPage, 'docentes_page'),
            'auxiliares' => $this->getUsersByRole($baseQuery, 'auxiliar', $perPage, 'auxiliares_page'),
            'estudiantes' => $this->getUsersByRole($baseQuery, 'estudiante', $perPage, 'estudiantes_page'),
            'apoderados' => $this->getUsersByRole($baseQuery, 'apoderado', $perPage, 'apoderados_page'),
            'currentTab' => $activeTab,
            'search' => $search
        ];

        return view('users.index', $data);
    }

    protected function getUsersByRole($query, $roleName, $perPage, $pageName)
    {
        return $query->clone()
            ->whereHas('roles', function($q) use ($roleName) {
                $q->where('nombre', $roleName);
            })
            ->paginate($perPage, ['*'], $pageName);
    }

    public function create()
    {
        {
            $currentRole = session('current_role');

            // Obtener roles según el usuario actual
            $query = Role::query();

            if ($currentRole === 'director') {
                $query->where('nombre', '!=', 'admin');
            }
            // Si es admin, muestra todos los roles

            $allRoles = $query->get();

            return view('users.create', compact('allRoles'));
        }
    }

    public function store(Request $request)
    {
        try {
            $currentRole = session('current_role');

            $validated = $request->validate([
                'dni' => 'required|string|max:20|unique:users',
                'nombre_usuario' => 'required|string|max:50|unique:users',
                'nombre' => 'required|string|max:100',
                'apellido_paterno' => 'required|string|max:100',
                'apellido_materno' => 'nullable|string|max:100',
                'email' => 'required|email|max:100|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,id',
                // Campos adicionales para estudiantes/apoderados
                'grado_id' => 'nullable|required_if:roles,estudiante|exists:grados,id',
                'fecha_nacimiento' => 'nullable|date',
                'apoderado_id' => 'nullable|exists:apoderados,id'
            ], [
                'roles.required' => 'Debe seleccionar al menos un rol',
                'grado_id.required_if' => 'El grado es requerido para estudiantes'
            ]);

            // Verificar roles permitidos (tu lógica actual)
            if ($currentRole === 'director') {
                $forbiddenRoles = Role::where('nombre', 'admin')->pluck('id')->toArray();
                if (array_intersect($request->roles, $forbiddenRoles)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'No tiene permisos para asignar roles de administrador');
                }
            }

            // Crear usuario
            $user = User::create([
                'dni' => $validated['dni'],
                'nombre_usuario' => $validated['nombre_usuario'],
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
                'email' => $validated['email'] ?? null,
                'password' => bcrypt($validated['password']),
                'estado' => 'activo'
            ]);

            // Asignar roles
            $user->roles()->sync($request->roles);

            // Crear registros relacionados según el rol
            $roles = Role::whereIn('id', $request->roles)->pluck('nombre')->toArray();

            if (in_array('docente', $roles)) {
                Docente::create([
                    'user_id' => $user->id,
                    'grado_id' => $validated['grado_id'] ?? null,
                ]);
            }

            if (in_array('estudiante', $roles)) {
                Estudiante::create([
                    'user_id' => $user->id,
                    'grado_id' => $validated['grado_id'] ?? null,
                    'apoderado_id' => $validated['apoderado_id'] ?? null,
                    'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null
                ]);
            }

            if (in_array('apoderado', $roles)) {
                Apoderado::create([
                    'user_id' => $user->id,
                    'parentesco' => $validated['parentesco'] ?? null,
                    'telefono1' => $validated['telefono1'] ?? null,
                    'telefono2' => $validated['telefono2'] ?? null
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'Usuario creado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(User $user)
    {
        $currentRole = session('current_role');

        // Verificar si el usuario actual puede editar este usuario
        if ($currentRole === 'director' && $user->hasRole('admin')) {
            abort(403, 'No puedes editar usuarios administradores');
        }

        // Obtener roles disponibles según el usuario actual
        $query = Role::query();
        if ($currentRole === 'director') {
            $query->where('nombre', '!=', 'admin');
        }

        return view('users.edit', [
            'user' => $user,
            'availableRoles' => $query->get()
        ]);
    }

    public function update(Request $request, User $user)
    {
        try {
            $currentRole = session('current_role');

            // Validación básica
            $validated = $request->validate([
                'dni' => 'required|string|max:20|unique:users,dni,'.$user->id,
                'nombre_usuario' => 'required|string|max:50|unique:users,nombre_usuario,'.$user->id,
                'nombre' => 'required|string|max:100',
                'apellido_paterno' => 'required|string|max:100',
                'apellido_materno' => 'nullable|string|max:100',
                'email' => 'required|email|max:100|unique:users,email,'.$user->id,
                'estado' => 'required|in:activo,inactivo',
                'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,id'
            ]);

            // Verificar permisos para editar este usuario
            if ($currentRole === 'director') {
                if ($user->hasRole('admin')) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'No puedes editar usuarios administradores');
                }

                // Verificar que no intente asignar roles de admin
                $forbiddenRoles = Role::where('nombre', 'admin')->pluck('id')->toArray();
                if (array_intersect($request->roles, $forbiddenRoles)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'No tienes permisos para asignar roles de administrador');
                }
            }

            // Actualizar datos básicos
            $user->update([
                'dni' => $validated['dni'],
                'nombre_usuario' => $validated['nombre_usuario'],
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
                'email' => $validated['email'],
                'estado' => $validated['estado'],
            ]);

            // Actualizar contraseña si se proporcionó
            if (!empty($validated['password'])) {
                $user->update(['password' => bcrypt($validated['password'])]);
            }

            // Sincronizar roles
            $user->roles()->sync($request->roles);

            return redirect()->route('users.index')
                ->with('success', 'Usuario actualizado exitosamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }
    public function destroy(User $user)
    {
        try {
            $currentRole = session('current_role');

            // Verificar permisos
            if ($currentRole === 'director' && $user->hasRole('admin')) {
                return redirect()->back()
                    ->with('error', 'No puedes eliminar usuarios administradores');
            }

            // No permitir auto-eliminación
            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'No puedes eliminar tu propio usuario');
            }

            // Eliminar usuario
            $user->roles()->detach(); // Eliminar relaciones primero
            $user->delete();

            return redirect()->route('users.index')
                ->with('success', 'Usuario eliminado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }
    public function changeStatus(User $user)
    {
        $user->update([
            'estado' => $user->estado == 'activo' ? 'inactivo' : 'activo'
        ]);

        return back()->with('success', 'Estado actualizado');
    }
}
