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
    public function ajaxUserActivo()
    {
        $users = User::activos()
            ->with('roles')
            ->select('id', 'dni', 'nombre_usuario', 'nombre', 'apellido_paterno', 'apellido_materno', 'estado')
            ->get()
            ->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'nombre_usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre . ' ' . $user->apellido_paterno . ' ' . $user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'estado' => $this->getEstadoTexto($user->estado), // Cambio aquí
                    'acciones' => $this->getActionButtons($user)
                ];
            });

        return response()->json(['data' => $users]);
    }
    public function ajaxUserLector()
    {
        $users = User::lectores()
            ->with('roles')
            ->select('id', 'dni', 'nombre_usuario', 'nombre', 'apellido_paterno', 'apellido_materno', 'estado')
            ->get()
            ->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'nombre_usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre . ' ' . $user->apellido_paterno . ' ' . $user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'estado' => $this->getEstadoTexto($user->estado), // Cambio aquí
                    'acciones' => $this->getActionButtons($user)
                ];
            });

        return response()->json(['data' => $users]);
    }
    public function ajaxUserInactivo()
    {
        $users = User::inactivos()
            ->with('roles')
            ->select('id', 'dni', 'nombre_usuario', 'nombre', 'apellido_paterno', 'apellido_materno', 'estado')
            ->get()
            ->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'nombre_usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre . ' ' . $user->apellido_paterno . ' ' . $user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'estado' => $this->getEstadoTexto($user->estado), // Cambio aquí
                    'acciones' => $this->getActionButtons($user)
                ];
            });

        return response()->json(['data' => $users]);
    }
    private function getActionButtons($user)
    {
        $buttons = '';
        if (auth()->user()->can('update', $user)) {
            $buttons .= '<a href="'.route('user.edit', $user->id).'" class="btn btn-sm btn-warning">Editar</a> ';
        }
        return $buttons;
    }
    private function getEstadoTexto($estado)
    {
        switch ((int)$estado) {
            case 0: return 'Inactivo';
            case 1: return 'Activo';
            case 2: return 'Lector';
            default: return 'Desconocido';
        }
    }

    public function index(Request $request)
    {
        return view('user.index');
    }

    public function create()
    {
        $roles = Role::all();
        $grados = Grado::where('estado', 1)->get();
        $materias = Materia::all();

        return view('user.create', compact('roles', 'grados', 'materias'));
    }

    public function store(Request $request)
    {
    $request->validate([
        'dni' => 'required|unique:users,dni|max:8',
        'nombre_usuario' => 'required|unique:users,nombre_usuario',
        'nombre' => 'required',
        'apellido_paterno' => 'required',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|confirmed|min:8',
        'rol' => 'required|exists:roles,id',
        'foto_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        //'telefono' => 'required|unique:users,telefono|max:9',
    ]);

    // Procesar la imagen si se subió
    $fotoPath = null;
    if ($request->hasFile('foto_path')) {
        $fotoPath = $request->file('foto_path')->store('profile-photos', 'public');
    }

    // Crear el usuario con los nombres en mayúsculas
    $user = User::create([
        'dni' => $request->dni,
        'nombre_usuario' => $request->nombre_usuario,
        'nombre' => mb_strtoupper($request->nombre, 'UTF-8'),
        'apellido_paterno' => mb_strtoupper($request->apellido_paterno, 'UTF-8'),
        'apellido_materno' => $request->apellido_materno ? mb_strtoupper($request->apellido_materno, 'UTF-8') : null,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'foto_path' => $fotoPath,
        'estado' => '1', // Activo por defecto
        'telefono' => $request->telefono,
    ]);

        // Asignar el rol al usuario
        $user->roles()->attach($request->rol);

        // Crear el registro específico según el rol
        switch ($request->rol) {
            case 6: // Estudiante (ID 6)
                $request->validate([
                    //'fecha_nacimiento' => 'required|date',
                    'grado_id' => 'required|exists:grados,id',
                    'parentesco' => 'required_if:sin_apoderado,false',
                    'apoderado_id' => 'required_if:sin_apoderado,false|exists:apoderados,id'
                ]);

                Estudiante::create([
                    'user_id' => $user->id,
                    'grado_id' => $request->grado_id,
                    'apoderado_id' => $request->sin_apoderado ? null : $request->apoderado_id,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'parentesco' => $request->parentesco,
                    'estado' => '1',
                ]);
                break;

            case 3: // Docente (ID 3)
                $request->validate([
                    //'especialidad' => 'required',
                    //'materia_id' => 'required|exists:materias,id',
                ]);

                Docente::create([
                    'user_id' => $user->id,
                    'especialidad' => $request->especialidad,
                    'materia_id' => $request->materia_id,
                    'estado' => '1', // Activo por defecto
                ]);
                break;

            case 5: // Apoderado (ID 5)
                $request->validate([
                    'parentesco' => 'required',
                ]);

                Apoderado::create([
                    'user_id' => $user->id,
                    'parentesco' => $request->parentesco,
                    'estado' => '1',
                ]);
                break;

            case 4: // Auxiliar (ID 4)
                $request->validate([
                    'turno' => 'nullable|string|max:50', // Hacer opcional pero con validación si existe
                    'funciones' => 'nullable|string'     // Hacer opcional pero con validación si existe
                ]);

                Auxiliar::create([
                    'user_id' => $user->id,
                    'turno' => $request->turno ?? null,  // Usar null si no se proporciona
                    'funciones' => $request->funciones ?? null, // Usar null si no se proporciona
                    'estado' => '1',
                ]);
                break;

            case 2: // Director (ID 2)
                // Si tienes un modelo Director, puedes agregarlo aquí
                // Director::create(['user_id' => $user->id, ...]);
                break;

            case 1: // Admin (ID 1)
                // No necesita campos adicionales
                break;
        }

        return redirect()->route('user.index')->with('success', 'Usuario creado exitosamente.');
    }


    public function edit(User $user)
    {
        $roles = Role::all();
        $grados = Grado::where('estado', 1)->get();
        $materias = Materia::all();

        // Cargar relaciones según el rol del usuario
        if($user->hasRole('estudiante')) {
            $user->load('estudiante.grado', 'estudiante.apoderado.user');
        } elseif($user->hasRole('docente')) {
            $user->load('docente.materia', 'docente.grado');
        } elseif($user->hasRole('apoderado')) {
            $user->load('apoderado');
        } elseif($user->hasRole('auxiliar')) {
            $user->load('auxiliar');
        }

        return view('user.edit', compact('user', 'roles', 'grados', 'materias'));
    }

    public function update(Request $request, User $user)
    {
        // Validación básica del usuario
        $request->validate([
            'dni' => 'required|max:8|unique:users,dni,'.$user->id,
            'nombre_usuario' => 'required|unique:users,nombre_usuario,'.$user->id,
            'nombre' => 'required',
            'apellido_paterno' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|confirmed|min:8', // Hacer la contraseña opcional
            'rol' => 'required|exists:roles,id',
            'foto_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'estado' => 'required|in:0,1,2',
        ]);

        // Procesar la imagen si se subió
        $fotoPath = $user->foto_path;
        if ($request->hasFile('foto_path')) {
            // Eliminar la foto anterior si existe
            if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                Storage::disk('public')->delete($fotoPath);
            }
            $fotoPath = $request->file('foto_path')->store('profile-photos', 'public');
        }

        // Actualizar el usuario
        $userData = [
            'dni' => $request->dni,
            'nombre_usuario' => $request->nombre_usuario,
            'nombre' => mb_strtoupper($request->nombre, 'UTF-8'),
            'apellido_paterno' => mb_strtoupper($request->apellido_paterno, 'UTF-8'),
            'apellido_materno' => $request->apellido_materno ? mb_strtoupper($request->apellido_materno, 'UTF-8') : null,
            'email' => $request->email,
            'estado' => $request->estado,
            'telefono' => $request->telefono,
            'foto_path' => $fotoPath,
        ];

        // Solo actualizar la contraseña si se proporcionó
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Actualizar el rol del usuario (sincronizar para evitar duplicados)
        $user->roles()->sync([$request->rol]);

        // Actualizar el registro específico según el rol
        switch ($request->rol) {
            case 6: // Estudiante (ID 6)
                $request->validate([
                    'grado_id' => 'required|exists:grados,id',
                    'parentesco' => 'required_if:sin_apoderado,false',
                    'apoderado_id' => 'required_if:sin_apoderado,false|exists:apoderados,id',
                    'fecha_nacimiento' => 'nullable|date',
                ]);

                $estudianteData = [
                    'grado_id' => $request->grado_id,
                    'apoderado_id' => $request->sin_apoderado ? null : $request->apoderado_id,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'parentesco' => $request->parentesco,
                    'estado' => $request->estado_estudiante ?? 1,
                ];

                if ($user->estudiante) {
                    $user->estudiante->update($estudianteData);
                } else {
                    $estudianteData['user_id'] = $user->id;
                    Estudiante::create($estudianteData);
                }
                break;

            case 3: // Docente (ID 3)
                $docenteData = [
                    'especialidad' => $request->especialidad,
                    'materia_id' => $request->materia_id,
                    'estado' => $request->estado_docente ?? 1,
                ];

                if ($user->docente) {
                    $user->docente->update($docenteData);
                } else {
                    $docenteData['user_id'] = $user->id;
                    Docente::create($docenteData);
                }
                break;

            case 5: // Apoderado (ID 5)
                $request->validate([
                    'parentesco' => 'required',
                ]);

                $apoderadoData = [
                    'parentesco' => $request->parentesco,
                    'estado' => $request->estado_apoderado ?? 1,
                ];

                if ($user->apoderado) {
                    $user->apoderado->update($apoderadoData);
                } else {
                    $apoderadoData['user_id'] = $user->id;
                    Apoderado::create($apoderadoData);
                }
                break;

            case 4: // Auxiliar (ID 4)
                $auxiliarData = [
                    'turno' => $request->turno,
                    'funciones' => $request->funciones,
                    'estado' => $request->estado_auxiliar ?? 1,
                ];

                if ($user->auxiliar) {
                    $user->auxiliar->update($auxiliarData);
                } else {
                    $auxiliarData['user_id'] = $user->id;
                    Auxiliar::create($auxiliarData);
                }
                break;
        }

        return redirect()->route('user.index')->with('success', 'Usuario actualizado exitosamente.');
    }

}
