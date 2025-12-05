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

    return view('user.edit', compact('user', 'roles', 'grados'));
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
        'password' => 'nullable|string|min:8|confirmed',
    ]);

    // Validar que existan roles
    $request->validate([
        'roles' => 'required|array|min:1',
        'roles.*' => 'exists:roles,id',
    ]);

    // Procesar los datos específicos por rol
    $roles = $request->input('roles', []);
    $rolesNombres = Role::whereIn('id', $roles)->pluck('nombre')->map(fn($name) => strtolower($name))->toArray();

    // Validar campos específicos según roles
    foreach ($rolesNombres as $index => $rolNombre) {
        switch ($rolNombre) {
            case 'estudiante':
                $request->validate([
                    'estudiante_grado.' . $index => 'required|exists:grados,id',
                    'estudiante_fecha_nacimiento.' . $index => 'required|date',
                ]);
                break;

            case 'docente':
                $request->validate([
                    'docente_estado.' . $index => 'required|in:0,1',
                ]);
                break;

            case 'apoderado':
                $request->validate([
                    'apoderado_parentesco.' . $index => 'required|string|max:50',
                    'apoderado_estado.' . $index => 'required|in:0,1',
                ]);
                break;

            case 'auxiliar':
                $request->validate([
                    'auxiliar_turno.' . $index => 'required|in:mañana,tarde,noche',
                    'auxiliar_estado.' . $index => 'required|in:0,1',
                    'auxiliar_funciones.' . $index => 'nullable|string',
                ]);
                break;

            case 'director':
                $request->validate([
                    'director_estado.' . $index => 'required|in:0,1',
                ]);
                break;
        }
    }

    // Iniciar transacción para asegurar consistencia
    DB::beginTransaction();

    try {
        // Preparar datos del usuario - NO incluir password si está vacío
        $userUpdateData = [
            'dni' => $userData['dni'],
            'nombre_usuario' => $userData['nombre_usuario'],
            'nombre' => $userData['nombre'],
            'apellido_paterno' => $userData['apellido_paterno'],
            'apellido_materno' => $userData['apellido_materno'],
            'email' => $userData['email'],
            'telefono' => $userData['telefono'],
            'estado' => $userData['estado'],
        ];

        // Solo actualizar password si se proporcionó una nueva
        if ($request->filled('password')) {
            $userUpdateData['password'] = Hash::make($request->password);
        }

        $user->update($userUpdateData);

        // Sincronizar roles (elimina los antiguos y agrega los nuevos)
        $user->roles()->sync($request->roles);

        // Actualizar o crear modelos específicos según roles
        foreach ($rolesNombres as $index => $rolNombre) {
            switch ($rolNombre) {
                case 'estudiante':
                    $estudianteData = [
                        'grado_id' => $request->input("estudiante_grado.$index"),
                        'fecha_nacimiento' => $request->input("estudiante_fecha_nacimiento.$index"),
                        'estado' => '1', // Estado por defecto para estudiante
                    ];

                    if ($user->estudiante) {
                        $user->estudiante->update($estudianteData);
                    } else {
                        Estudiante::create(array_merge($estudianteData, ['user_id' => $user->id]));
                    }
                    break;

                case 'docente':
                    $docenteData = [
                        'estado' => $request->input("docente_estado.$index", '1'),
                    ];

                    if ($user->docente) {
                        $user->docente->update($docenteData);
                    } else {
                        Docente::create(array_merge($docenteData, ['user_id' => $user->id]));
                    }
                    break;

                case 'apoderado':
                    $apoderadoData = [
                        'parentesco' => $request->input("apoderado_parentesco.$index"),
                        'estado' => $request->input("apoderado_estado.$index", '1'),
                    ];

                    if ($user->apoderado) {
                        $user->apoderado->update($apoderadoData);
                    } else {
                        Apoderado::create(array_merge($apoderadoData, ['user_id' => $user->id]));
                    }
                    break;

                case 'auxiliar':
                    $auxiliarData = [
                        'turno' => $request->input("auxiliar_turno.$index"),
                        'funciones' => $request->input("auxiliar_funciones.$index"),
                        'estado' => $request->input("auxiliar_estado.$index", '1'),
                    ];

                    if ($user->auxiliar) {
                        $user->auxiliar->update($auxiliarData);
                    } else {
                        Auxiliar::create(array_merge($auxiliarData, ['user_id' => $user->id]));
                    }
                    break;

                case 'director':
                    $directorData = [
                        'estado' => $request->input("director_estado.$index", '1'),
                    ];

                    if ($user->director) {
                        $user->director->update($directorData);
                    } else {
                        Director::create(array_merge($directorData, ['user_id' => $user->id]));
                    }
                    break;
            }
        }

        // Eliminar modelos específicos para roles que ya no tiene
        $rolesAEliminar = array_diff(['estudiante', 'docente', 'apoderado', 'auxiliar', 'director'], $rolesNombres);

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

        return redirect()->route('user.index')
            ->with('success', 'Usuario actualizado correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();

        // Log del error para debugging
        \Log::error('Error al actualizar usuario: ' . $e->getMessage());
        \Log::error('Trace: ' . $e->getTraceAsString());

        return back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
    }
}
    public function importar()
    {
        return view('user.importar');
    }
    public function validarApoderados(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240' // 10MB máximo
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $registrosValidos = [];
            $errores = [];
            $totalRegistros = 0;

            // Saltar la primera fila (encabezados)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $totalRegistros++;

                // Validar que la fila no esté vacía
                if (empty(array_filter($row))) {
                    continue;
                }

                // Validar campos obligatorios
                if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[5])) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni' => $row[0] ?? 'N/A',
                        'error' => 'Faltan campos obligatorios'
                    ];
                    continue;
                }

                $dni = $row[0];

                // Validar formato de DNI
                if (!preg_match('/^[0-9]{8}$/', $dni)) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni' => $dni,
                        'error' => 'Formato de DNI inválido (debe tener 8 dígitos)'
                    ];
                    continue;
                }

                // Verificar si el usuario ya existe
                if (User::where('dni', $dni)->exists()) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni' => $dni,
                        'error' => 'El DNI ya existe en el sistema'
                    ];
                    continue;
                }

                // Validar parentesco
                $parentescoValido = ['padre', 'madre', 'tutor'];
                $parentesco = strtolower(trim($row[5]));
                if (!in_array($parentesco, $parentescoValido)) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni' => $dni,
                        'error' => 'Parentesco inválido. Debe ser: padre, madre o tutor'
                    ];
                    continue;
                }

                // Registro válido
                $registrosValidos[] = [
                    'fila' => $i + 1,
                    'dni' => $dni,
                    'apellido_paterno' => mb_strtoupper($row[1], 'UTF-8'),
                    'apellido_materno' => mb_strtoupper($row[2], 'UTF-8'),
                    'nombre' => mb_strtoupper($row[3], 'UTF-8'),
                    'telefono' => $row[4] ?? null,
                    'parentesco' => $parentesco,
                    'email' => $dni . '@ietere.com'
                ];
            }

            return response()->json([
                'success' => true,
                'total_registros' => $totalRegistros,
                'registros_validos' => $registrosValidos,
                'total_validos' => count($registrosValidos),
                'errores' => $errores,
                'total_errores' => count($errores),
                'session_key' => 'apoderados_pendientes_' . uniqid()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    // Función original con el nombre que quieres mantener
    public function importarApoderados(Request $request)
    {
        // Aumentar el tiempo de ejecución a 2 minutos
        set_time_limit(120);

        // Decodificar los registros que vienen como JSON string
        $registros = json_decode($request->registros, true);

        // Validar que se pudieron decodificar correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Formato de datos inválido'
            ], 400);
        }

        $validator = Validator::make(['registros' => $registros], [
            'registros' => 'required|array',
            'registros.*.dni' => 'required|string',
            'registros.*.nombre' => 'required|string',
            'registros.*.apellido_paterno' => 'required|string',
            'registros.*.apellido_materno' => 'required|string',
            'registros.*.parentesco' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $exitosos = 0;
        $errores = [];
        $totalRegistros = count($registros);

        foreach ($registros as $index => $registro) {
            try {
                // Verificar nuevamente que no exista (por si acaso)
                if (User::where('dni', $registro['dni'])->exists()) {
                    $errores[] = "DNI {$registro['dni']} ya existe en el sistema";
                    continue;
                }

                // Crear el usuario apoderado
                $user = User::create([
                    'dni' => $registro['dni'],
                    'nombre_usuario' => $registro['dni'],
                    'nombre' => $registro['nombre'],
                    'apellido_paterno' => $registro['apellido_paterno'],
                    'apellido_materno' => $registro['apellido_materno'],
                    'email' => $registro['dni'] . '@ietere.com',
                    'password' => Hash::make($registro['dni']),
                    'telefono' => $registro['telefono'] ?? null,
                    'estado' => '1',
                ]);

                // Asignar rol de apoderado (ID 5)
                $user->roles()->attach(5);

                // Crear registro de apoderado
                Apoderado::create([
                    'user_id' => $user->id,
                    'parentesco' => $registro['parentesco'],
                    'estado' => '1',
                ]);

                $exitosos++;

            } catch (\Exception $e) {
                $errores[] = "DNI {$registro['dni']}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'exitosos' => $exitosos,
            'errores' => $errores
        ]);
    }

    public function validarEstudiantes(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240' // 10MB máximo
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $registrosValidos = [];
            $errores = [];
            $totalRegistros = 0;

            // Saltar la primera fila (encabezados)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $totalRegistros++;

                // Validar que la fila no esté vacía
                if (empty(array_filter($row))) {
                    continue;
                }

                // Validar campos obligatorios
                if (empty($row[0]) || empty($row[1]) || empty($row[3]) || empty($row[5]) || empty($row[6]) || empty($row[7]) || empty($row[8])) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni_estudiante' => $row[0] ?? 'N/A',
                        'error' => 'Faltan campos obligatorios'
                    ];
                    continue;
                }

                $dniEstudiante = $row[0];
                $dniApoderado = $row[5];

                // Validar formato de DNI del estudiante
                if (!preg_match('/^[0-9]{8}$/', $dniEstudiante)) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni_estudiante' => $dniEstudiante,
                        'error' => 'Formato de DNI del estudiante inválido (debe tener 8 dígitos)'
                    ];
                    continue;
                }

                // Validar formato de DNI del apoderado
                if (!preg_match('/^[0-9]{8}$/', $dniApoderado)) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni_estudiante' => $dniEstudiante,
                        'error' => 'Formato de DNI del apoderado inválido (debe tener 8 dígitos)'
                    ];
                    continue;
                }

                // Verificar si el estudiante ya existe
                if (User::where('dni', $dniEstudiante)->exists()) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni_estudiante' => $dniEstudiante,
                        'error' => 'El DNI del estudiante ya existe en el sistema'
                    ];
                    continue;
                }

                // Buscar al apoderado por DNI
                $apoderadoUser = User::where('dni', $dniApoderado)->first();
                if (!$apoderadoUser) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni_estudiante' => $dniEstudiante,
                        'error' => 'No se encontró al apoderado con DNI: ' . $dniApoderado
                    ];
                    continue;
                }

                $apoderado = Apoderado::where('user_id', $apoderadoUser->id)->first();
                if (!$apoderado) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni_estudiante' => $dniEstudiante,
                        'error' => 'El usuario con DNI ' . $dniApoderado . ' no es un apoderado'
                    ];
                    continue;
                }

                // Buscar el grado
                $grado = Grado::where('grado', mb_strtoupper($row[6], 'UTF-8'))
                            ->where('seccion', mb_strtoupper($row[7], 'UTF-8'))
                            ->where('nivel', mb_strtoupper($row[8], 'UTF-8'))
                            ->first();

                if (!$grado) {
                    $errores[] = [
                        'fila' => $i + 1,
                        'dni_estudiante' => $dniEstudiante,
                        'error' => 'No se encontró el grado: ' . mb_strtoupper($row[6], 'UTF-8') .
                                ' - sección: ' . mb_strtoupper($row[7], 'UTF-8') .
                                ' - nivel: ' . mb_strtoupper($row[8], 'UTF-8')
                    ];
                    continue;
                }

                // Validar fecha de nacimiento
                $fechaNacimiento = null;
                if (!empty($row[4])) {
                    try {
                        $fechaNacimiento = \Carbon\Carbon::createFromFormat('d/m/Y', $row[4])->format('Y-m-d');
                    } catch (\Exception $e) {
                        try {
                            $fechaNacimiento = \Carbon\Carbon::parse($row[4])->format('Y-m-d');
                        } catch (\Exception $e) {
                            $errores[] = [
                                'fila' => $i + 1,
                                'dni_estudiante' => $dniEstudiante,
                                'error' => 'Formato de fecha de nacimiento inválido: ' . $row[4]
                            ];
                            continue;
                        }
                    }
                }

                // Registro válido
                $registrosValidos[] = [
                    'fila' => $i + 1,
                    'dni_estudiante' => $dniEstudiante,
                    'apellido_paterno' => mb_strtoupper($row[1], 'UTF-8'),
                    'apellido_materno' => !empty($row[2]) ? mb_strtoupper($row[2], 'UTF-8') : null,
                    'nombre' => mb_strtoupper($row[3], 'UTF-8'),
                    'fecha_nacimiento' => $fechaNacimiento,
                    'dni_apoderado' => $dniApoderado,
                    'grado' => mb_strtoupper($row[6], 'UTF-8'),
                    'seccion' => mb_strtoupper($row[7], 'UTF-8'),
                    'nivel' => mb_strtoupper($row[8], 'UTF-8'),
                    'grado_id' => $grado->id,
                    'apoderado_id' => $apoderado->id,
                    'apoderado_nombre' => $apoderadoUser->nombre . ' ' . $apoderadoUser->apellido_paterno,
                    'email' => $dniEstudiante . '@ietere.com'
                ];
            }

            return response()->json([
                'success' => true,
                'total_registros' => $totalRegistros,
                'registros_validos' => $registrosValidos,
                'total_validos' => count($registrosValidos),
                'errores' => $errores,
                'total_errores' => count($errores),
                'session_key' => 'estudiantes_pendientes_' . uniqid()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
    public function importarEstudiantes(Request $request)
    {
        // Aumentar el tiempo de ejecución a 2 minutos
        set_time_limit(120);

        // Decodificar los registros que vienen como JSON string
        $registros = json_decode($request->registros, true);

        // Validar que se pudieron decodificar correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Formato de datos inválido'
            ], 400);
        }

        $validator = Validator::make(['registros' => $registros], [
            'registros' => 'required|array',
            'registros.*.dni_estudiante' => 'required|string',
            'registros.*.nombre' => 'required|string',
            'registros.*.apellido_paterno' => 'required|string',
            'registros.*.grado_id' => 'required|integer',
            'registros.*.apoderado_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $exitosos = 0;
        $errores = [];
        $totalRegistros = count($registros);

        foreach ($registros as $index => $registro) {
            try {
                // Verificar nuevamente que no exista (por si acaso)
                if (User::where('dni', $registro['dni_estudiante'])->exists()) {
                    $errores[] = "DNI {$registro['dni_estudiante']} ya existe en el sistema";
                    continue;
                }

                // Crear el usuario estudiante
                $user = User::create([
                    'dni' => $registro['dni_estudiante'],
                    'nombre_usuario' => $registro['dni_estudiante'],
                    'nombre' => $registro['nombre'],
                    'apellido_paterno' => $registro['apellido_paterno'],
                    'apellido_materno' => $registro['apellido_materno'] ?? null,
                    'email' => $registro['dni_estudiante'] . '@ietere.com',
                    'password' => Hash::make($registro['dni_estudiante']),
                    'estado' => '1',
                ]);

                // Asignar rol de estudiante (ID 6)
                $user->roles()->attach(6);

                // Crear registro de estudiante
                Estudiante::create([
                    'user_id' => $user->id,
                    'grado_id' => $registro['grado_id'],
                    'apoderado_id' => $registro['apoderado_id'],
                    'fecha_nacimiento' => $registro['fecha_nacimiento'] ?? null,
                    'parentesco' => 'Hijo(a)',
                    'estado' => '1',
                ]);

                $exitosos++;

            } catch (\Exception $e) {
                $errores[] = "DNI {$registro['dni_estudiante']}: " . $e->getMessage();

                // Eliminar usuario si se creó pero falló algo después
                if (isset($user)) {
                    $user->delete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'exitosos' => $exitosos,
            'errores' => $errores
        ]);
    }
}
