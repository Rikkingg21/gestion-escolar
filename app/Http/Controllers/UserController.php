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
use Illuminate\Support\Facades\Log;

class UserController extends Controller
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
    public function ajaxUserActivo(Request $request)
    {
        return $this->getUsersByStatus($request, 1);
    }
    public function ajaxUserLector(Request $request)
    {
        return $this->getUsersByStatus($request, 2);
    }
    public function ajaxUserInactivo(Request $request)
    {
        return $this->getUsersByStatus($request, 0);
    }
    private function getUsersByStatus(Request $request, $status)
    {
        try {
        $users = User::with('roles')
            ->where('estado', $status)
            ->get()
            ->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre.' '.$user->apellido_paterno.' '.$user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'estado' => $user->estado == 1 ? 'Activo' : ($user->estado == 2 ? 'Lector' : 'Inactivo'),
                    'acciones' => view('user.partials.actions', compact('user'))->render()
                ];
            });

        return response()->json([
            'data' => $users,
            'draw' => $request->input('draw', 1),
            'recordsTotal' => User::where('estado', $status)->count(),
            'recordsFiltered' => $users->count()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
    }


    public function ajaxDirector(Request $request)
    {
        try {
            $estado = $request->input('estado', 'activos');

            $query = User::with('roles');

            if ($estado === 'activos') {
                $query->where('estado', 1);
            } else {
                $query->where('estado', 0);
            }

            $users = $query->get()->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre.' '.$user->apellido_paterno.' '.$user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'estado' => $user->estado ? 'Activo' : 'Inactivo',
                    'acciones' => view('director.partials.actions', compact('user'))->render()
                ];
            });

            return response()->json([
                'data' => $users, // DataTables espera los datos en una propiedad 'data'
                'draw' => $request->input('draw', 1), // Necesario para serverSide
                'recordsTotal' => User::count(),
                'recordsFiltered' => $users->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        return view('user.index');
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
