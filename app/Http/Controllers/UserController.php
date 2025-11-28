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
