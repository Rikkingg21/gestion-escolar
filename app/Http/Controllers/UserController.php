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
use App\Models\Userrole;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;



class UserController extends Controller
{
    //moduleID 7 = User
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('7')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function ajaxUserActivo(Request $request)
    {
        $query = User::activos()
            ->with([
                'roles',
                'estudiante.grado', // Solo estudiantes tienen grado
                'docente' // Docentes sin relación con grado
            ])
            ->select('id', 'dni', 'nombre_usuario', 'nombre', 'apellido_paterno', 'apellido_materno', 'estado');

        // Aplicar filtros
        $query = $this->aplicarFiltros($query, $request);

        $users = $query->get()
            ->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'nombre_usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre . ' ' . $user->apellido_paterno . ' ' . $user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'grado' => $this->getGradoUsuario($user),
                    'estado' => $this->getEstadoTexto($user->estado),
                    'acciones' => $this->getActionButtons($user)
                ];
            });

        return response()->json(['data' => $users]);
    }

    public function ajaxUserLector(Request $request)
    {
        $query = User::lectores()
            ->with([
                'roles',
                'estudiante.grado',
                'docente'
            ])
            ->select('id', 'dni', 'nombre_usuario', 'nombre', 'apellido_paterno', 'apellido_materno', 'estado');

        // Aplicar filtros
        $query = $this->aplicarFiltros($query, $request);

        $users = $query->get()
            ->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'nombre_usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre . ' ' . $user->apellido_paterno . ' ' . $user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'grado' => $this->getGradoUsuario($user),
                    'estado' => $this->getEstadoTexto($user->estado),
                    'acciones' => $this->getActionButtons($user)
                ];
            });

        return response()->json(['data' => $users]);
    }

    public function ajaxUserInactivo(Request $request)
    {
        $query = User::inactivos()
            ->with([
                'roles',
                'estudiante.grado',
                'docente'
            ])
            ->select('id', 'dni', 'nombre_usuario', 'nombre', 'apellido_paterno', 'apellido_materno', 'estado');

        // Aplicar filtros
        $query = $this->aplicarFiltros($query, $request);

        $users = $query->get()
            ->map(function($user) {
                return [
                    'dni' => $user->dni,
                    'nombre_usuario' => $user->nombre_usuario,
                    'nombre_completo' => $user->nombre . ' ' . $user->apellido_paterno . ' ' . $user->apellido_materno,
                    'roles' => $user->roles->pluck('nombre')->implode(', '),
                    'grado' => $this->getGradoUsuario($user),
                    'estado' => $this->getEstadoTexto($user->estado),
                    'acciones' => $this->getActionButtons($user)
                ];
            });

        return response()->json(['data' => $users]);
    }

    /**
     * Método para aplicar filtros comunes
     */
    private function aplicarFiltros($query, Request $request)
    {
        // Filtro por rol
        if ($request->has('rol') && !empty($request->rol)) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('nombre', $request->rol);
            });
        }

        // Filtro por grado (SOLO para estudiantes)
        if ($request->has('grado') && !empty($request->grado)) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('grado_id', $request->grado);
            });
        }

        return $query;
    }

    /**
     * Obtener el grado del usuario formateado (SOLO estudiantes)
     */
    private function getGradoUsuario($user)
    {
        // Verificar si es estudiante y tiene grado
        if ($user->estudiante && $user->estudiante->grado) {
            $grado = $user->estudiante->grado;
            return "{$grado->grado}° '{$grado->seccion}' - {$grado->nivel}";
        }

        // Para otros roles, mostrar el rol principal
        $roles = $user->roles->pluck('nombre')->implode(', ');

        // Si es docente
        if ($user->docente) {
            return 'Docente';
        }

        // Para otros roles
        if (!empty($roles)) {
            return $roles;
        }

        return 'Sin información';
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
        $roles = Role::where('estado', '1')->get(['id', 'nombre']);
        $grados = Grado::where('estado', '1')->get(['id', 'grado', 'seccion', 'nivel']);

        return view('user.index', compact('roles', 'grados'));
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
        // Mensajes personalizados en español
        $messages = [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'Este DNI ya está registrado en el sistema.',
            'dni.max' => 'El DNI debe tener máximo 8 dígitos.',
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.unique' => 'Este nombre de usuario ya está en uso.',
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'roles.required' => 'Debe seleccionar al menos un rol.',
            'roles.array' => 'Los roles deben ser un arreglo.',
            'roles.*.exists' => 'Uno de los roles seleccionados no es válido.',
            'telefono.max' => 'El teléfono debe tener máximo 9 dígitos.',
        ];

        // Validación básica del usuario
        $request->validate([
            'dni' => 'required|unique:users,dni|max:8',
            'nombre_usuario' => 'required|unique:users,nombre_usuario',
            'nombre' => 'required',
            'apellido_paterno' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'telefono' => 'nullable|max:9',
        ], $messages);

        // Crear el usuario
        $user = User::create([
            'dni' => $request->dni,
            'nombre_usuario' => $request->nombre_usuario,
            'nombre' => mb_strtoupper($request->nombre, 'UTF-8'),
            'apellido_paterno' => mb_strtoupper($request->apellido_paterno, 'UTF-8'),
            'apellido_materno' => $request->apellido_materno ? mb_strtoupper($request->apellido_materno, 'UTF-8') : null,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'foto_path' => null,
            'estado' => '1',
            'telefono' => $request->telefono ?? null,
        ]);

        // Asignar todos los roles al usuario (sin duplicados)
        $rolesUnicos = array_unique($request->roles);
        $user->roles()->attach($rolesUnicos);

        // Crear los registros específicos según los roles seleccionados
        $this->crearRegistrosPorRoles($user, $request, $rolesUnicos);

        return redirect()->route('user.index')->with('success', 'Usuario creado exitosamente.');
    }

    private function crearRegistrosPorRoles(User $user, Request $request, array $roles)
    {
        $messages = [
            'grado_id.required' => 'El grado es obligatorio para estudiantes.',
            'grado_id.exists' => 'El grado seleccionado no es válido.',
            'fecha_nacimiento.date' => 'Ingrese una fecha de nacimiento válida.',
            'apoderado_id.required_if' => 'Debe seleccionar un apoderado.',
            'apoderado_id.exists' => 'El apoderado seleccionado no es válido.',
            'parentesco.required' => 'El parentesco es obligatorio.',
            'parentesco.in' => 'Seleccione un parentesco válido.',
            'especialidad.max' => 'La especialidad no debe exceder 100 caracteres.',
            'materia_id.exists' => 'La materia seleccionada no es válida.',
            'turno.in' => 'Seleccione un turno válido.',
            'funciones.max' => 'Las funciones no deben exceder 50 caracteres.',
        ];

        // Verificar y crear registro para cada rol
        foreach ($roles as $rolId) {
            switch ($rolId) {
                case 6: // Estudiante
                    // Verificar si ya existe un registro de estudiante
                    if (!Estudiante::where('user_id', $user->id)->exists()) {
                        $request->validate([
                            'grado_id' => 'required|exists:grados,id',
                            'fecha_nacimiento' => 'nullable|date',
                            'apoderado_id' => 'required_if:sin_apoderado,false|nullable|exists:apoderados,id',
                            'parentesco' => 'required_if:sin_apoderado,false|nullable|in:padre,madre,tutor,otro',
                        ], $messages);

                        Estudiante::create([
                            'user_id' => $user->id,
                            'grado_id' => $request->grado_id,
                            'apoderado_id' => $request->sin_apoderado ? null : $request->apoderado_id,
                            'fecha_nacimiento' => $request->fecha_nacimiento ?? null,
                            'parentesco' => $request->sin_apoderado ? null : $request->parentesco,
                            'estado' => '1',
                        ]);
                    }
                    break;

                case 3: // Docente
                    if (!Docente::where('user_id', $user->id)->exists()) {
                        $request->validate([
                            'especialidad' => 'nullable|string|max:100',
                            'materia_id' => 'nullable|exists:materias,id',
                        ], $messages);

                        Docente::create([
                            'user_id' => $user->id,
                            'especialidad' => $request->especialidad ?? null,
                            'materia_id' => $request->materia_id ?? null,
                            'estado' => '1',
                        ]);
                    }
                    break;

                case 5: // Apoderado
                    if (!Apoderado::where('user_id', $user->id)->exists()) {
                        $request->validate([
                            'parentesco' => 'required|in:padre,madre,tutor,otro',
                        ], $messages);

                        Apoderado::create([
                            'user_id' => $user->id,
                            'parentesco' => $request->parentesco,
                            'estado' => '1',
                        ]);
                    }
                    break;

                case 4: // Auxiliar
                    if (!Auxiliar::where('user_id', $user->id)->exists()) {
                        $request->validate([
                            'turno' => 'nullable|in:mañana,tarde,completo',
                            'funciones' => 'nullable|string|max:50',
                        ], $messages);

                        Auxiliar::create([
                            'user_id' => $user->id,
                            'turno' => $request->turno ?? null,
                            'funciones' => $request->funciones ?? null,
                            'estado' => '1',
                        ]);
                    }
                    break;

                case 2: // Director
                    if (!Director::where('user_id', $user->id)->exists()) {
                        Director::create([
                            'user_id' => $user->id,
                            'estado' => 1,
                        ]);
                    }
                    break;

                case 1: // Admin
                    // No necesita registro específico
                    break;
            }
        }
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $grados = Grado::where('estado', 1)->get();

        // Cargar todas las relaciones del usuario
        $user->load([
            'roles',
            'estudiante.grado',
            'estudiante.apoderado.user',
            'docente',
            'apoderado',
            'auxiliar',
            'director'
        ]);

        // Obtener roles actuales para mostrar en el formulario
        $userRoles = $user->roles->pluck('id')->toArray();

        // Definir roles protegidos (IDs que NO se pueden eliminar)
        $rolesProtegidos = [1, 2, 3, 4, 5, 6]; // Ajusta según tus necesidades

        return view('user.edit', compact('user', 'roles', 'grados', 'userRoles', 'rolesProtegidos'));
    }
    public function update(Request $request, User $user)
    {
        // Validar datos básicos del usuario
        $userData = $request->validate([
            'dni' => 'required|string|max:8|unique:users,dni,' . $user->id,
            'nombre_usuario' => 'required|string|max:50|unique:users,nombre_usuario,' . $user->id,
            'nombre' => 'required|string|max:50',
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:9',
            'estado' => 'required|in:0,1,2',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
        }

        // Validar roles existentes
        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ]);

        // Validar nuevos roles si existen
        if ($request->has('nuevos_roles')) {
            $request->validate([
                'nuevos_roles' => 'array',
                'nuevos_roles.*' => 'exists:roles,id',
            ]);
        }

        // Combinar todos los roles
        $todosLosRoles = array_merge($request->roles, $request->nuevos_roles ?? []);

        // Obtener nombres de todos los roles
        $todosLosRolesNombres = Role::whereIn('id', $todosLosRoles)
            ->pluck('nombre')
            ->map(fn($name) => strtolower($name))
            ->toArray();

        // Validar campos específicos para roles EXISTENTES
        $rolesExistentesNombres = Role::whereIn('id', $request->roles)
            ->pluck('nombre')
            ->map(fn($name) => strtolower($name))
            ->toArray();

        foreach ($rolesExistentesNombres as $rolNombre) {
            switch ($rolNombre) {
                case 'estudiante':
                    $request->validate([
                        'estudiante_grado' => 'required|exists:grados,id',
                        'estudiante_apoderado' => 'nullable|exists:apoderados,id',
                        'estudiante_fecha_nacimiento' => 'required|date',
                        'estudiante_estado' => 'required|in:0,1',
                    ]);
                    break;
                case 'docente':
                    $request->validate([
                        'docente_estado' => 'required|in:0,1',
                    ]);
                    break;
                case 'apoderado':
                    $request->validate([
                        'apoderado_parentesco' => 'required|string|max:50',
                        'apoderado_estado' => 'required|in:0,1',
                    ]);
                    break;
                case 'auxiliar':
                    $request->validate([
                        'auxiliar_turno' => 'required|in:mañana,tarde,noche',
                        'auxiliar_estado' => 'required|in:0,1',
                        'auxiliar_funciones' => 'nullable|string',
                    ]);
                    break;
                case 'director':
                    $request->validate([
                        'director_estado' => 'required|in:0,1',
                    ]);
                    break;
            }
        }

        // Validar campos específicos para roles NUEVOS
        if ($request->has('nuevos_roles')) {
            $nuevosRolesNombres = Role::whereIn('id', $request->nuevos_roles)
                ->pluck('nombre')
                ->map(fn($name) => strtolower($name))
                ->toArray();

            foreach ($nuevosRolesNombres as $index => $rolNombre) {
                switch ($rolNombre) {
                    case 'estudiante':
                        $request->validate([
                            'nuevo_estudiante_grado.' . $index => 'required|exists:grados,id',
                            'nuevo_estudiante_apoderado.' . $index => 'nullable|exists:apoderados,id',
                            'nuevo_estudiante_fecha_nacimiento.' . $index => 'required|date',
                            'nuevo_estudiante_estado.' . $index => 'required|in:0,1',
                        ]);
                        break;
                    case 'docente':
                        $request->validate([
                            'nuevo_docente_estado.' . $index => 'required|in:0,1',
                        ]);
                        break;
                    case 'apoderado':
                        $request->validate([
                            'nuevo_apoderado_parentesco.' . $index => 'required|string|max:50',
                            'nuevo_apoderado_estado.' . $index => 'required|in:0,1',
                        ]);
                        break;
                    case 'auxiliar':
                        $request->validate([
                            'nuevo_auxiliar_turno.' . $index => 'required|in:mañana,tarde,noche',
                            'nuevo_auxiliar_estado.' . $index => 'required|in:0,1',
                            'nuevo_auxiliar_funciones.' . $index => 'nullable|string',
                        ]);
                        break;
                    case 'director':
                        $request->validate([
                            'nuevo_director_estado.' . $index => 'required|in:0,1',
                        ]);
                        break;
                }
            }
        }

        DB::beginTransaction();

        try {
            // Actualizar datos básicos del usuario
            $updateData = [
                'dni' => $request->dni,
                'nombre_usuario' => $request->nombre_usuario,
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'estado' => $request->estado,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Sincronizar TODOS los roles (existentes + nuevos)
            $user->roles()->sync($todosLosRoles);

            // Procesar roles EXISTENTES (ACTUALIZAR)
            foreach ($rolesExistentesNombres as $rolNombre) {
                switch ($rolNombre) {
                    case 'estudiante':
                        $estudianteData = [
                            'grado_id' => $request->estudiante_grado,
                            'apoderado_id' => $request->estudiante_apoderado,
                            'fecha_nacimiento' => $request->estudiante_fecha_nacimiento,
                            'estado' => $request->estudiante_estado,
                        ];

                        if ($user->estudiante) {
                            $user->estudiante->update($estudianteData);
                        } else {
                            // Esto podría pasar si un rol fue removido y luego se agrega de nuevo
                            Estudiante::create(array_merge($estudianteData, ['user_id' => $user->id]));
                        }
                        break;

                    case 'docente':
                        $docenteData = [
                            'estado' => $request->docente_estado,
                        ];

                        if ($user->docente) {
                            $user->docente->update($docenteData);
                        } else {
                            Docente::create(array_merge($docenteData, ['user_id' => $user->id]));
                        }
                        break;

                    case 'apoderado':
                        $apoderadoData = [
                            'parentesco' => $request->apoderado_parentesco,
                            'estado' => $request->apoderado_estado,
                        ];

                        if ($user->apoderado) {
                            $user->apoderado->update($apoderadoData);
                        } else {
                            Apoderado::create(array_merge($apoderadoData, ['user_id' => $user->id]));
                        }
                        break;

                    case 'auxiliar':
                        $auxiliarData = [
                            'turno' => $request->auxiliar_turno,
                            'funciones' => $request->auxiliar_funciones,
                            'estado' => $request->auxiliar_estado,
                        ];

                        if ($user->auxiliar) {
                            $user->auxiliar->update($auxiliarData);
                        } else {
                            Auxiliar::create(array_merge($auxiliarData, ['user_id' => $user->id]));
                        }
                        break;

                    case 'director':
                        $directorData = [
                            'estado' => $request->director_estado,
                        ];

                        if ($user->director) {
                            $user->director->update($directorData);
                        } else {
                            Director::create(array_merge($directorData, ['user_id' => $user->id]));
                        }
                        break;
                }
            }

            // Procesar roles NUEVOS (CREAR)
            if ($request->has('nuevos_roles')) {
                foreach ($nuevosRolesNombres as $index => $rolNombre) {
                    switch ($rolNombre) {
                        case 'estudiante':
                            $estudianteData = [
                                'grado_id' => $request->input("nuevo_estudiante_grado.{$index}"),
                                'apoderado_id' => $request->input("nuevo_estudiante_apoderado.{$index}"),
                                'fecha_nacimiento' => $request->input("nuevo_estudiante_fecha_nacimiento.{$index}"),
                                'estado' => $request->input("nuevo_estudiante_estado.{$index}", '1'),
                                'user_id' => $user->id,
                            ];

                            Estudiante::create($estudianteData);
                            break;

                        case 'docente':
                            $docenteData = [
                                'estado' => $request->input("nuevo_docente_estado.{$index}", '1'),
                                'user_id' => $user->id,
                            ];

                            Docente::create($docenteData);
                            break;

                        case 'apoderado':
                            $apoderadoData = [
                                'parentesco' => $request->input("nuevo_apoderado_parentesco.{$index}"),
                                'estado' => $request->input("nuevo_apoderado_estado.{$index}", '1'),
                                'user_id' => $user->id,
                            ];

                            Apoderado::create($apoderadoData);
                            break;

                        case 'auxiliar':
                            $auxiliarData = [
                                'turno' => $request->input("nuevo_auxiliar_turno.{$index}"),
                                'funciones' => $request->input("nuevo_auxiliar_funciones.{$index}"),
                                'estado' => $request->input("nuevo_auxiliar_estado.{$index}", '1'),
                                'user_id' => $user->id,
                            ];

                            Auxiliar::create($auxiliarData);
                            break;

                        case 'director':
                            $directorData = [
                                'estado' => $request->input("nuevo_director_estado.{$index}", '1'),
                                'user_id' => $user->id,
                            ];

                            Director::create($directorData);
                            break;
                    }
                }
            }

            // Eliminar modelos para roles que ya no tiene
            $rolesAEliminar = array_diff(['estudiante', 'docente', 'apoderado', 'auxiliar', 'director'], $todosLosRolesNombres);

            foreach ($rolesAEliminar as $rolAEliminar) {
                switch ($rolAEliminar) {
                    case 'estudiante':
                        $user->estudiante?->delete();
                        break;
                    case 'docente':
                        $user->docente?->delete();
                        break;
                    case 'apoderado':
                        $user->apoderado?->delete();
                        break;
                    case 'auxiliar':
                        $user->auxiliar?->delete();
                        break;
                    case 'director':
                        $user->director?->delete();
                        break;
                }
            }

            DB::commit();

            return redirect()->route('user.edit', $user->id)
                ->with('success', 'Usuario actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el usuario.')
                        ->withInput();
        }
    }
    public function removeRelacionRolNoProtegidos(Request $request, User $user)
    {
        $roleId = $request->role_id;
        $rolesProtegidos = [1, 2, 3, 4, 5, 6];

        if (in_array($roleId, $rolesProtegidos)) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar un rol protegido.'
            ], 403);
        }

        $deleted = Userrole::where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->delete();

        return response()->json([
            'success' => (bool) $deleted,
            'message' => $deleted
                ? 'Rol desvinculado correctamente.'
                : 'Relación no encontrada.'
        ], $deleted ? 200 : 404);
    }
    public function importar()
    {
        return view('user.importar');
    }
    public function importarApoderados(Request $request)
    {
        // Paso 1: Validar si es confirmación o cancelación
        if ($request->has('accion')) {
            $accion = $request->input('accion');

            if ($accion === 'cancelar') {
                // Limpiar sesión y cancelar
                session()->forget('import_apoderados_data');

                return redirect()->route('user.importar')
                    ->with('info', 'Importación de apoderados cancelada.');
            }

            if ($accion === 'procesar') {
                return $this->procesarImportacionApoderados($request);
            }
        }

        // Paso 1: Validación inicial del archivo
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB máximo
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $totalRegistros = 0;
            $errores = [];
            $registrosValidos = [];

            // Validar cada fila (saltando encabezados)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $numeroFila = $i + 1;

                // Verificar si la fila está vacía
                if (empty(array_filter($row, function($value) {
                    return $value !== null && $value !== '';
                }))) {
                    continue;
                }

                $totalRegistros++;

                try {
                    // Validar campos obligatorios
                    if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[5])) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni' => $row[0] ?? 'N/A',
                            'error' => 'Faltan campos obligatorios'
                        ];
                        continue;
                    }

                    $dni = trim($row[0]);

                    // Validar formato de DNI
                    if (!preg_match('/^[0-9]{8}$/', $dni)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni' => $dni,
                            'error' => 'Formato de DNI inválido (debe tener 8 dígitos)'
                        ];
                        continue;
                    }

                    // Verificar si el usuario ya existe
                    if (User::where('dni', $dni)->exists()) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni' => $dni,
                            'error' => 'El DNI ya existe en el sistema'
                        ];
                        continue;
                    }

                    // Validar parentesco
                    $parentescosValidos = ['padre', 'madre', 'tutor'];
                    $parentesco = strtolower(trim($row[5]));
                    if (!in_array($parentesco, $parentescosValidos)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni' => $dni,
                            'error' => 'Parentesco inválido. Debe ser: ' . implode(', ', $parentescosValidos)
                        ];
                        continue;
                    }

                    // Validar teléfono si está presente
                    $telefono = isset($row[4]) ? trim($row[4]) : null;
                    if ($telefono && !preg_match('/^[0-9]{7,15}$/', $telefono)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni' => $dni,
                            'error' => 'Formato de teléfono inválido'
                        ];
                        continue;
                    }

                    // Verificar duplicados dentro del archivo
                    $claveRegistro = $dni;
                    $duplicadoEnArchivo = collect($registrosValidos)->contains(function($registro) use ($dni) {
                        return $registro['dni'] === $dni;
                    });

                    if ($duplicadoEnArchivo) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni' => $dni,
                            'error' => 'DNI duplicado en el archivo'
                        ];
                        continue;
                    }

                    // Agregar a registros válidos
                    $registrosValidos[] = [
                        'fila' => $numeroFila,
                        'datos' => [
                            'dni' => $dni,
                            'apellido_paterno' => mb_strtoupper(trim($row[1]), 'UTF-8'),
                            'apellido_materno' => mb_strtoupper(trim($row[2]), 'UTF-8'),
                            'nombre' => mb_strtoupper(trim($row[3]), 'UTF-8'),
                            'telefono' => $telefono,
                            'parentesco' => $parentesco,
                            'email' => $dni . '@ietere.com',
                            'password' => $dni, // Se hasheará al crear
                            'nombre_usuario' => $dni
                        ]
                    ];

                } catch (\Exception $e) {
                    $errores[] = [
                        'fila' => $numeroFila,
                        'dni' => $row[0] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Guardar datos en sesión para el próximo paso
            session()->put('import_apoderados_data', [
                'registros_validos' => $registrosValidos,
                'total_registros' => $totalRegistros,
                'errores' => $errores,
                'archivo_nombre' => $request->file('file')->getClientOriginalName()
            ]);

            // Devolver a la vista con datos de validación
            return redirect()->route('user.importar')
                ->with('validacion_apoderados', true)
                ->with('total_registros_apoderados', $totalRegistros)
                ->with('registros_validos_apoderados', count($registrosValidos))
                ->with('errores_validacion_apoderados', $errores)
                ->with('datos_validos_apoderados', $registrosValidos)
                ->with('tipo_importacion', 'apoderados');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Método privado para procesar la importación de apoderados
    private function procesarImportacionApoderados(Request $request)
    {
        // Aumentar el tiempo de ejecución para procesamiento masivo
        set_time_limit(120);

        try {
            $importData = session()->get('import_apoderados_data');

            if (!$importData) {
                return redirect()->route('user.importar')
                    ->with('error', 'No hay datos de importación para procesar. Por favor, valide el archivo nuevamente.');
            }

            $registrosValidos = $importData['registros_validos'];
            $exitosos = 0;
            $erroresProceso = [];

            // Procesar cada registro válido
            foreach ($registrosValidos as $registro) {
                try {
                    DB::beginTransaction();

                    // Verificar nuevamente que no exista (concurrente)
                    if (User::where('dni', $registro['datos']['dni'])->exists()) {
                        $erroresProceso[] = [
                            'fila' => $registro['fila'],
                            'dni' => $registro['datos']['dni'],
                            'error' => 'El DNI ya existe en el sistema'
                        ];
                        DB::rollBack();
                        continue;
                    }

                    // Crear el usuario
                    $user = User::create([
                        'dni' => $registro['datos']['dni'],
                        'nombre_usuario' => $registro['datos']['nombre_usuario'],
                        'nombre' => $registro['datos']['nombre'],
                        'apellido_paterno' => $registro['datos']['apellido_paterno'],
                        'apellido_materno' => $registro['datos']['apellido_materno'],
                        'email' => $registro['datos']['email'],
                        'password' => Hash::make($registro['datos']['password']),
                        'telefono' => $registro['datos']['telefono'],
                        'estado' => '1',
                        'email_verified_at' => now(),
                    ]);

                    // Asignar rol de apoderado (ID 5)
                    $user->roles()->attach(5);

                    // Crear registro de apoderado
                    Apoderado::create([
                        'user_id' => $user->id,
                        'parentesco' => $registro['datos']['parentesco'],
                        'estado' => '1',
                    ]);

                    DB::commit();
                    $exitosos++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $erroresProceso[] = [
                        'fila' => $registro['fila'],
                        'dni' => $registro['datos']['dni'],
                        'error' => 'Error al crear usuario: ' . $e->getMessage()
                    ];
                }
            }

            // Limpiar sesión
            session()->forget('import_apoderados_data');

            // Preparar mensaje final
            $mensaje = "Importación completada: $exitosos apoderados importados exitosamente.";
            $tipoMensaje = 'success';

            if (count($erroresProceso) > 0) {
                $mensaje .= " Se produjeron " . count($erroresProceso) . " errores durante el procesamiento.";
                $tipoMensaje = 'warning';

                // Guardar errores de proceso en sesión
                session()->flash('errores_proceso_apoderados', $erroresProceso);
            }

            return redirect()->route('user.importar')
                ->with($tipoMensaje, $mensaje)
                ->with('exitosos_apoderados', $exitosos);

        } catch (\Exception $e) {
            return redirect()->route('user.importar')
                ->with('error', 'Error durante el procesamiento: ' . $e->getMessage());
        }
    }

    public function importarEstudiantes(Request $request)
    {
        // Paso 1: Validar si es confirmación o cancelación
        if ($request->has('accion')) {
            $accion = $request->input('accion');

            if ($accion === 'cancelar') {
                // Limpiar sesión y cancelar
                session()->forget('import_estudiantes_data');

                return redirect()->route('user.importar')
                    ->with('info', 'Importación de estudiantes cancelada.');
            }

            if ($accion === 'procesar') {
                return $this->procesarImportacionEstudiantes($request);
            }
        }

        // Paso 1: Validación inicial del archivo
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB máximo
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $totalRegistros = 0;
            $errores = [];
            $registrosValidos = [];

            // Validar cada fila (saltando encabezados)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $numeroFila = $i + 1;

                // Verificar si la fila está vacía
                if (empty(array_filter($row, function($value) {
                    return $value !== null && $value !== '';
                }))) {
                    continue;
                }

                $totalRegistros++;

                try {
                    // Validar campos obligatorios
                    if (empty($row[0]) || empty($row[1]) || empty($row[3]) || empty($row[5]) || empty($row[6]) || empty($row[7]) || empty($row[8])) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $row[0] ?? 'N/A',
                            'error' => 'Faltan campos obligatorios'
                        ];
                        continue;
                    }

                    $dniEstudiante = trim($row[0]);
                    $dniApoderado = trim($row[5]);

                    // Validar formato de DNI del estudiante
                    if (!preg_match('/^[0-9]{8}$/', $dniEstudiante)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'Formato de DNI del estudiante inválido (debe tener 8 dígitos)'
                        ];
                        continue;
                    }

                    // Validar formato de DNI del apoderado
                    if (!preg_match('/^[0-9]{8}$/', $dniApoderado)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'Formato de DNI del apoderado inválido (debe tener 8 dígitos)'
                        ];
                        continue;
                    }

                    // Verificar si el estudiante ya existe
                    if (User::where('dni', $dniEstudiante)->exists()) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'El DNI del estudiante ya existe en el sistema'
                        ];
                        continue;
                    }

                    // Buscar al apoderado por DNI
                    $apoderadoUser = User::where('dni', $dniApoderado)->first();
                    if (!$apoderadoUser) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'No se encontró al apoderado con DNI: ' . $dniApoderado
                        ];
                        continue;
                    }

                    // Verificar que el usuario sea apoderado
                    $apoderado = Apoderado::where('user_id', $apoderadoUser->id)->first();
                    if (!$apoderado) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'El usuario con DNI ' . $dniApoderado . ' no es un apoderado'
                        ];
                        continue;
                    }

                    // Validar grado, sección y nivel
                    $gradoNumero = trim($row[6]);
                    $seccion = mb_strtoupper(trim($row[7]), 'UTF-8');
                    $nivel = mb_strtoupper(trim($row[8]), 'UTF-8');

                    // Validar formato del grado
                    if (!is_numeric($gradoNumero)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'El grado debe ser un número'
                        ];
                        continue;
                    }

                    // Validar nivel
                    $nivelesValidos = ['PRIMARIA', 'SECUNDARIA'];
                    if (!in_array($nivel, $nivelesValidos)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'Nivel inválido. Debe ser: ' . implode(' o ', $nivelesValidos)
                        ];
                        continue;
                    }

                    // Buscar el grado
                    $grado = Grado::where('grado', $gradoNumero)
                        ->where('seccion', $seccion)
                        ->where('nivel', $nivel)
                        ->where('estado', '1')
                        ->first();

                    if (!$grado) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'No se encontró el grado: ' . $gradoNumero .
                                    '° - sección: ' . $seccion .
                                    ' - nivel: ' . $nivel . ' (o no está activo)'
                        ];
                        continue;
                    }

                    // Validar fecha de nacimiento
                    $fechaNacimiento = null;
                    if (!empty($row[4])) {
                        $fechaStr = trim($row[4]);

                        // Primero, verificar si es un valor numérico de Excel (serial date)
                        if (is_numeric($fechaStr)) {
                            try {
                                // Convertir serial date de Excel a fecha PHP
                                $excelDate = (int)$fechaStr;
                                if ($excelDate > 60) {
                                    // Excel para Windows usa 1900 como base, pero tiene un error: cree que 1900 fue bisiesto
                                    $excelDate -= 1;
                                }
                                $fechaNacimiento = Carbon::create(1900, 1, 1)->addDays($excelDate - 1);
                            } catch (\Exception $e) {
                                $errores[] = [
                                    'fila' => $numeroFila,
                                    'dni_estudiante' => $dniEstudiante,
                                    'error' => 'Fecha de nacimiento (serial Excel) inválida: ' . $fechaStr
                                ];
                                continue;
                            }
                        } else {
                            try {
                                // Si es string, intentar con múltiples formatos
                                $fechaParseada = null;

                                // Intentar con formato dd/mm/yyyy
                                if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}$/', $fechaStr)) {
                                    // Reemplazar cualquier separador por /
                                    $fechaNormalizada = str_replace(['-', '\\'], '/', $fechaStr);

                                    // Separar partes
                                    $partes = explode('/', $fechaNormalizada);
                                    if (count($partes) === 3) {
                                        $dia = intval($partes[0]);
                                        $mes = intval($partes[1]);
                                        $anio = intval($partes[2]);

                                        // Verificar qué formato tiene (dd/mm/yyyy o mm/dd/yyyy)
                                        if ($mes > 12 && $dia <= 12) {
                                            // Probablemente es mm/dd/yyyy pero mes > 12, así que intercambiar
                                            $temp = $mes;
                                            $mes = $dia;
                                            $dia = $temp;
                                        }

                                        // Crear fecha
                                        $fechaParseada = Carbon::create($anio, $mes, $dia);
                                    }
                                }

                                // Si no se pudo parsear con formato específico, intentar con Carbon
                                if (!$fechaParseada) {
                                    $fechaParseada = Carbon::parse($fechaStr);
                                }

                                $fechaNacimiento = $fechaParseada;

                                // Validar que la fecha sea válida (no en el futuro)
                                if ($fechaNacimiento->isFuture()) {
                                    throw new \Exception('La fecha de nacimiento no puede ser futura');
                                }

                                // Validar edad mínima (3 años) y máxima (20 años)
                                $edad = $fechaNacimiento->age;
                                if ($edad < 3 || $edad > 20) {
                                    throw new \Exception('Edad fuera del rango permitido (3-20 años)');
                                }

                                $fechaNacimiento = $fechaNacimiento->format('Y-m-d');

                            } catch (\Exception $e) {
                                $errores[] = [
                                    'fila' => $numeroFila,
                                    'dni_estudiante' => $dniEstudiante,
                                    'error' => 'Fecha de nacimiento inválida: "' . $fechaStr . '" - ' . $e->getMessage()
                                ];
                                continue;
                            }
                        }
                    }

                    // Verificar duplicados dentro del archivo
                    $claveRegistro = $dniEstudiante;
                    $duplicadoEnArchivo = collect($registrosValidos)->contains(function($registro) use ($dniEstudiante) {
                        return $registro['dni_estudiante'] === $dniEstudiante;
                    });

                    if ($duplicadoEnArchivo) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'dni_estudiante' => $dniEstudiante,
                            'error' => 'DNI de estudiante duplicado en el archivo'
                        ];
                        continue;
                    }

                    // Agregar a registros válidos
                    $registrosValidos[] = [
                        'fila' => $numeroFila,
                        'datos' => [
                            'dni_estudiante' => $dniEstudiante,
                            'apellido_paterno' => mb_strtoupper(trim($row[1]), 'UTF-8'),
                            'apellido_materno' => !empty($row[2]) ? mb_strtoupper(trim($row[2]), 'UTF-8') : null,
                            'nombre' => mb_strtoupper(trim($row[3]), 'UTF-8'),
                            'fecha_nacimiento' => $fechaNacimiento,
                            'dni_apoderado' => $dniApoderado,
                            'grado' => $gradoNumero,
                            'seccion' => $seccion,
                            'nivel' => $nivel,
                            'grado_id' => $grado->id,
                            'grado_nombre' => $grado->nombreCompleto ?? $gradoNumero . '° ' . $seccion . ' - ' . $nivel,
                            'apoderado_id' => $apoderado->id,
                            'apoderado_nombre' => $apoderadoUser->nombreCompleto ?? ($apoderadoUser->nombre . ' ' . $apoderadoUser->apellido_paterno),
                            'email' => $dniEstudiante . '@ietere.com',
                            'password' => $dniEstudiante, // Se hasheará al crear
                            'nombre_usuario' => $dniEstudiante
                        ]
                    ];

                } catch (\Exception $e) {
                    $errores[] = [
                        'fila' => $numeroFila,
                        'dni_estudiante' => $row[0] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Guardar datos en sesión para el próximo paso
            session()->put('import_estudiantes_data', [
                'registros_validos' => $registrosValidos,
                'total_registros' => $totalRegistros,
                'errores' => $errores,
                'archivo_nombre' => $request->file('file')->getClientOriginalName()
            ]);

            // Devolver a la vista con datos de validación
            return redirect()->route('user.importar')
                ->with('validacion_estudiantes', true)
                ->with('total_registros_estudiantes', $totalRegistros)
                ->with('registros_validos_estudiantes', count($registrosValidos))
                ->with('errores_validacion_estudiantes', $errores)
                ->with('datos_validos_estudiantes', $registrosValidos)
                ->with('tipo_importacion', 'estudiantes');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Método privado para procesar la importación de estudiantes
    private function procesarImportacionEstudiantes(Request $request)
    {
        // Aumentar el tiempo de ejecución para procesamiento masivo
        set_time_limit(120);

        try {
            $importData = session()->get('import_estudiantes_data');

            if (!$importData) {
                return redirect()->route('user.importar')
                    ->with('error', 'No hay datos de importación para procesar. Por favor, valide el archivo nuevamente.');
            }

            $registrosValidos = $importData['registros_validos'];
            $exitosos = 0;
            $erroresProceso = [];

            // Procesar cada registro válido
            foreach ($registrosValidos as $registro) {
                try {
                    DB::beginTransaction();

                    // Verificar nuevamente que no exista (concurrente)
                    if (User::where('dni', $registro['datos']['dni_estudiante'])->exists()) {
                        $erroresProceso[] = [
                            'fila' => $registro['fila'],
                            'dni_estudiante' => $registro['datos']['dni_estudiante'],
                            'error' => 'El DNI del estudiante ya existe en el sistema'
                        ];
                        DB::rollBack();
                        continue;
                    }

                    // Verificar que el apoderado aún existe
                    $apoderado = Apoderado::find($registro['datos']['apoderado_id']);
                    if (!$apoderado) {
                        $erroresProceso[] = [
                            'fila' => $registro['fila'],
                            'dni_estudiante' => $registro['datos']['dni_estudiante'],
                            'error' => 'El apoderado ya no existe en el sistema'
                        ];
                        DB::rollBack();
                        continue;
                    }

                    // Verificar que el grado aún existe
                    $grado = Grado::find($registro['datos']['grado_id']);
                    if (!$grado || $grado->estado !== '1') {
                        $erroresProceso[] = [
                            'fila' => $registro['fila'],
                            'dni_estudiante' => $registro['datos']['dni_estudiante'],
                            'error' => 'El grado ya no existe o no está activo'
                        ];
                        DB::rollBack();
                        continue;
                    }

                    // Crear el usuario estudiante
                    $user = User::create([
                        'dni' => $registro['datos']['dni_estudiante'],
                        'nombre_usuario' => $registro['datos']['nombre_usuario'],
                        'nombre' => $registro['datos']['nombre'],
                        'apellido_paterno' => $registro['datos']['apellido_paterno'],
                        'apellido_materno' => $registro['datos']['apellido_materno'],
                        'email' => $registro['datos']['email'],
                        'password' => Hash::make($registro['datos']['password']),
                        'estado' => '1',
                        'email_verified_at' => now(),
                    ]);

                    // Asignar rol de estudiante (ID 6)
                    $user->roles()->attach(6);

                    // Crear registro de estudiante
                    Estudiante::create([
                        'user_id' => $user->id,
                        'grado_id' => $registro['datos']['grado_id'],
                        'apoderado_id' => $registro['datos']['apoderado_id'],
                        'fecha_nacimiento' => $registro['datos']['fecha_nacimiento'],
                        'parentesco' => 'Hijo(a)',
                        'estado' => '1',
                    ]);

                    DB::commit();
                    $exitosos++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $erroresProceso[] = [
                        'fila' => $registro['fila'],
                        'dni_estudiante' => $registro['datos']['dni_estudiante'],
                        'error' => 'Error al crear estudiante: ' . $e->getMessage()
                    ];
                }
            }

            // Limpiar sesión
            session()->forget('import_estudiantes_data');

            // Preparar mensaje final
            $mensaje = "Importación completada: $exitosos estudiantes importados exitosamente.";
            $tipoMensaje = 'success';

            if (count($erroresProceso) > 0) {
                $mensaje .= " Se produjeron " . count($erroresProceso) . " errores durante el procesamiento.";
                $tipoMensaje = 'warning';

                // Guardar errores de proceso en sesión
                session()->flash('errores_proceso_estudiantes', $erroresProceso);
            }

            return redirect()->route('user.importar')
                ->with($tipoMensaje, $mensaje)
                ->with('exitosos_estudiantes', $exitosos);

        } catch (\Exception $e) {
            return redirect()->route('user.importar')
                ->with('error', 'Error durante el procesamiento: ' . $e->getMessage());
        }
    }
}
