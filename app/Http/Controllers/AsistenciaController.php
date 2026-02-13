<?php
namespace App\Http\Controllers;

use App\Models\Asistencia\Asistencia;
use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\User;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use App\Models\Periodo;
use App\Models\Matricula;
use Carbon\Carbon;
use App\Models\Asistencia\Tipoasistencia;
use App\Models\Grado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AsistenciaController extends Controller
{
    //moduleID 14 = Asistencia
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('14')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Obtener el año seleccionado (por defecto año actual)
        $currentYear = $request->get('year', now()->year);
        $hoy = now()->format('Y-m-d');

        // Obtener el período activo para el año seleccionado
        $periodoActual = Periodo::where('anio', $currentYear)
            ->where('estado', '1')
            ->first();

        if (!$periodoActual) {
            $periodoActual = Periodo::where('estado', '1')
                ->orderBy('anio', 'desc')
                ->first();
            // Si encontramos un período, actualizamos el currentYear
            if ($periodoActual) {
                $currentYear = $periodoActual->anio;
            }
        }

        // Obtener grados con múltiples conteos
        $grados = Grado::withCount([
            // Total de asistencias en el período
            'asistencias as total_asistencias' => function($query) use ($periodoActual) {
                $query->where('periodo_id', $periodoActual->id);
            },
            // Asistencias de hoy en el período
            'asistencias as asistencias_hoy' => function($query) use ($periodoActual, $hoy) {
                $query->where('periodo_id', $periodoActual->id)
                    ->whereDate('fecha', $hoy);
            },
            // Estudiantes matriculados activos en este período
            'matriculas as estudiantes_matriculados' => function($query) use ($periodoActual) {
                $query->where('periodo_id', $periodoActual->id)
                    ->where('estado', '1');
            }
        ])
        ->orderBy('nivel')
        ->orderBy('grado')
        ->orderBy('seccion')
        ->get();

        // Para cada grado, verificar si tiene registros bloqueados (estado '1' o '2')
        foreach ($grados as $grado) {
            $grado->tiene_registros_bloqueados = Asistencia::where('periodo_id', $periodoActual->id)
                ->where('grado_id', $grado->id)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            // Verificar específicamente para la fecha de hoy
            $grado->tiene_registros_bloqueados_hoy = Asistencia::where('periodo_id', $periodoActual->id)
                ->where('grado_id', $grado->id)
                ->whereDate('fecha', $hoy)
                ->whereIn('estado', ['1', '2'])
                ->exists();
        }

        $availableYears = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->unique()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        // Determinar la fecha por defecto para el calendario
        $fechaPorDefecto = $hoy; // Por defecto hoy

        // Si el año del período es diferente al año actual, usar el primer día del año del período
        if ($periodoActual && $periodoActual->anio != now()->year) {
            $fechaPorDefecto = $periodoActual->anio . '-01-01'; // Primer día del año del período
        }

        return view('asistencia.index', [
            'grados' => $grados,
            'currentYear' => $currentYear,
            'availableYears' => $availableYears,
            'periodoActual' => $periodoActual,
            'fechaHoy' => $hoy,
            'fechaPorDefecto' => $fechaPorDefecto, // Nueva variable
        ]);
    }
    public function obtenerBimestreYEstadoPorFecha(Request $request)
    {
        try {
            $fecha = Carbon::parse($request->fecha)->format('Y-m-d');
            $anioFecha = Carbon::parse($request->fecha)->year;

            $periodo = Periodo::where('anio', $anioFecha)
                ->where('estado', '1')
                ->first();

            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay período activo para este año'
                ]);
            }

            // Buscar si existe algún registro de asistencia para esta fecha
            $asistencia = Asistencia::where('periodo_id', $periodo->id)
                ->whereDate('fecha', $fecha)
                ->first();

            // Verificar si existe algún registro con estado '0' para esta fecha
            $existeRegistroPendiente = Asistencia::where('periodo_id', $periodo->id)
                ->whereDate('fecha', $fecha)
                ->where('estado', '0')
                ->exists();

            // Verificar si TODOS los estudiantes tienen asistencia (cualquier estado)
            $totalEstudiantesActivos = Estudiante::whereHas('matriculas', function($query) use ($periodo) {
                $query->where('periodo_id', $periodo->id)
                    ->where('estado', '1');
            })->count();

            $totalAsistenciasFecha = Asistencia::where('periodo_id', $periodo->id)
                ->whereDate('fecha', $fecha)
                ->count();

            $todosTienenAsistencia = ($totalEstudiantesActivos > 0 &&
                                    $totalAsistenciasFecha >= $totalEstudiantesActivos);

            $respuesta = [
                'success' => true,
                'bimestre' => $asistencia ? $asistencia->bimestre : null,
                'existe_registro_pendiente' => $existeRegistroPendiente,
                'todos_tienen_asistencia' => $todosTienenAsistencia,
                'total_estudiantes' => $totalEstudiantesActivos,
                'total_asistencias' => $totalAsistenciasFecha,
                'message' => $asistencia ? 'Bimestre encontrado' : 'No hay registros para esta fecha'
            ];

            // Si existen registros pendientes, agregar información adicional
            if ($existeRegistroPendiente) {
                $totalPendientes = Asistencia::where('periodo_id', $periodo->id)
                    ->whereDate('fecha', $fecha)
                    ->where('estado', '0')
                    ->count();

                $respuesta['total_pendientes'] = $totalPendientes;
                $respuesta['message'] = "Existen {$totalPendientes} registro(s) pendiente(s) para esta fecha";
            }

            // Mensaje si todos tienen asistencia
            if ($todosTienenAsistencia && !$existeRegistroPendiente) {
                $respuesta['message'] = "Todos los estudiantes ya tienen asistencia registrada para esta fecha";
            }

            return response()->json($respuesta);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }
    public function marcarRestoDeEstudiantesConPuntualidad(Request $request)
    {
        try {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'fecha' => 'required|date',
                'bimestre' => 'required|integer|between:1,4'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();
            $grado = Grado::findOrFail($request->grado_id);
            $userId = Auth::id();

            // Obtener el período basado en la fecha
            $periodo = Periodo::where('anio', $fechaCarbon->year)
                ->where('estado', '1')
                ->first();

            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => "No hay un período activo para el año {$fechaCarbon->year}"
                ], 400);
            }

            // Verificar si hay registros bloqueados en este grado y fecha
            $tieneBloqueo = Asistencia::where('periodo_id', $periodo->id)
                ->where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            if ($tieneBloqueo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede realizar esta operación porque existen registros confirmados o validados'
                ], 403);
            }

            DB::beginTransaction();
            try {
                // Obtener todos los estudiantes matriculados activos en este grado
                $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodo) {
                    $query->where('grado_id', $grado->id)
                        ->where('periodo_id', $periodo->id)
                        ->where('estado', '1');
                })->get();

                // Obtener estudiantes que YA TIENEN asistencia registrada para esta fecha
                $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodo->id)
                    ->where('grado_id', $grado->id)
                    ->whereDate('fecha', $fechaCarbon)
                    ->pluck('estudiante_id')
                    ->toArray();

                // Filtrar solo los estudiantes que NO tienen asistencia
                $estudiantesSinAsistencia = $estudiantes->filter(function($estudiante) use ($estudiantesConAsistencia) {
                    return !in_array($estudiante->id, $estudiantesConAsistencia);
                });

                if ($estudiantesSinAsistencia->isEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos los estudiantes ya tienen asistencia registrada para esta fecha'
                    ], 400);
                }

                // Obtener el tipo de asistencia "Puntual"
                $tipoPuntual = Tipoasistencia::where('nombre', 'like', '%puntual%')
                    ->orWhere('nombre', 'like', '%presente%')
                    ->first();

                if (!$tipoPuntual) {
                    $tipoPuntual = Tipoasistencia::first();
                }

                $contador = 0;
                foreach ($estudiantesSinAsistencia as $estudiante) {
                    Asistencia::create([
                        'estudiante_id' => $estudiante->id,
                        'grado_id' => $grado->id,
                        'periodo_id' => $periodo->id,
                        'tipo_asistencia_id' => $tipoPuntual->id,
                        'fecha' => $fechaCarbon,
                        'hora' => now()->format('H:i'),
                        'bimestre' => $request->bimestre,
                        'registrador_id' => $userId,
                        'descripcion' => 'Marcado masivo - resto de estudiantes',
                        'estado' => '0'
                    ]);
                    $contador++;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Se marcaron {$contador} estudiantes como puntuales",
                    'contador' => $contador,
                    'total_sin_asistencia' => $estudiantesSinAsistencia->count()
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la solicitud: ' . $e->getMessage()
            ], 400);
        }
    }

    public function marcarRestoDeEstudiantesConTardanza(Request $request)
    {
        try {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'fecha' => 'required|date',
                'bimestre' => 'required|integer|between:1,4'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();
            $grado = Grado::findOrFail($request->grado_id);
            $userId = Auth::id();

            // Obtener el período basado en la fecha
            $periodo = Periodo::where('anio', $fechaCarbon->year)
                ->where('estado', '1')
                ->first();

            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => "No hay un período activo para el año {$fechaCarbon->year}"
                ], 400);
            }

            // Verificar si hay registros bloqueados en este grado y fecha
            $tieneBloqueo = Asistencia::where('periodo_id', $periodo->id)
                ->where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            if ($tieneBloqueo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede realizar esta operación porque existen registros confirmados o validados'
                ], 403);
            }

            DB::beginTransaction();
            try {
                // Obtener todos los estudiantes matriculados activos en este grado
                $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodo) {
                    $query->where('grado_id', $grado->id)
                        ->where('periodo_id', $periodo->id)
                        ->where('estado', '1');
                })->get();

                // Obtener estudiantes que YA TIENEN asistencia registrada para esta fecha
                $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodo->id)
                    ->where('grado_id', $grado->id)
                    ->whereDate('fecha', $fechaCarbon)
                    ->pluck('estudiante_id')
                    ->toArray();

                // Filtrar solo los estudiantes que NO tienen asistencia
                $estudiantesSinAsistencia = $estudiantes->filter(function($estudiante) use ($estudiantesConAsistencia) {
                    return !in_array($estudiante->id, $estudiantesConAsistencia);
                });

                if ($estudiantesSinAsistencia->isEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos los estudiantes ya tienen asistencia registrada para esta fecha'
                    ], 400);
                }

                // Obtener el tipo de asistencia "Tardanza"
                $tipoTardanza = Tipoasistencia::where('nombre', 'like', '%tardanza%')
                    ->orWhere('nombre', 'like', '%tarde%')
                    ->first();

                if (!$tipoTardanza) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontró un tipo de asistencia para tardanza'
                    ], 400);
                }

                $contador = 0;
                foreach ($estudiantesSinAsistencia as $estudiante) {
                    Asistencia::create([
                        'estudiante_id' => $estudiante->id,
                        'grado_id' => $grado->id,
                        'periodo_id' => $periodo->id,
                        'tipo_asistencia_id' => $tipoTardanza->id,
                        'fecha' => $fechaCarbon,
                        'hora' => now()->format('H:i'),
                        'bimestre' => $request->bimestre,
                        'registrador_id' => $userId,
                        'descripcion' => 'Marcado masivo - resto de estudiantes con tardanza',
                        'estado' => '0'
                    ]);
                    $contador++;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Se marcaron {$contador} estudiantes con tardanza",
                    'contador' => $contador,
                    'total_sin_asistencia' => $estudiantesSinAsistencia->count()
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la solicitud: ' . $e->getMessage()
            ], 400);
        }
    }

    public function showDate($grado_grado_seccion, $grado_nivel, $date)
    {
        try {
            $fechaFormateada = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
            $anioFecha = Carbon::createFromFormat('d-m-Y', $date)->year;
            $mesFecha = Carbon::createFromFormat('d-m-Y', $date)->format('m');
        } catch (\Exception $e) {
            abort(400, 'Formato de fecha inválido. Use dd-mm-yyyy');
        }

        // Parsear grado y sección
        if (!preg_match('/^(\d+)([a-zA-Z]+)$/', $grado_grado_seccion, $matches)) {
            abort(400, 'Formato de grado/sección inválido. Ejemplo: 1a, 2b');
        }

        $gradoNumero = $matches[1];
        $gradoSeccion = $matches[2];

        $grado = Grado::where('grado', $gradoNumero)
            ->where('seccion', $gradoSeccion)
            ->where('nivel', $grado_nivel)
            ->firstOrFail();

        // Obtener el período basado en el AÑO de la fecha seleccionada
        $periodoFecha = Periodo::where('anio', $anioFecha)
            ->where('estado', '1')
            ->first();

        if (!$periodoFecha) {
            abort(400, "No hay un período activo configurado para el año {$anioFecha}");
        }

        // ========== VALIDACIÓN DE MES BLOQUEADO ==========
        // Si existe AL MENOS UNA asistencia con estado '1' o '2' en este período, mes y grado
        // entonces TODO el mes está BLOQUEADO COMPLETAMENTE
        $mesBloqueado = Asistencia::where('periodo_id', $periodoFecha->id)
            ->where('grado_id', $grado->id)
            ->whereMonth('fecha', $mesFecha)
            ->whereIn('estado', ['1', '2'])
            ->exists();

        // Contar cuántos registros bloqueados hay (solo para informar)
        $cantidadBloqueados = 0;
        if ($mesBloqueado) {
            $cantidadBloqueados = Asistencia::where('periodo_id', $periodoFecha->id)
                ->where('grado_id', $grado->id)
                ->whereMonth('fecha', $mesFecha)
                ->whereIn('estado', ['1', '2'])
                ->count();
        }
        // =================================================

        // Obtener estudiantes usando whereHas para mejor eficiencia
        $estudiantesMatriculadosActivos = Estudiante::with(['user',
            'asistencias' => function ($query) use ($fechaFormateada, $grado) {
                $query->whereDate('fecha', $fechaFormateada)
                    ->where('grado_id', $grado->id);
            }
        ])
        ->whereHas('matriculas', function ($query) use ($grado, $periodoFecha) {
            $query->where('grado_id', $grado->id)
                ->where('periodo_id', $periodoFecha->id)
                ->where('estado', '1');
        })
        ->get()
        ->sortBy(function ($estudiante) {
            return optional($estudiante->user)->apellido_paterno .
                optional($estudiante->user)->apellido_materno .
                optional($estudiante->user)->nombre;
        });

        $estudiantesMatriculadosRetirados = Estudiante::with(['user',
            'asistencias' => function ($query) use ($fechaFormateada, $grado) {
                $query->whereDate('fecha', $fechaFormateada)
                    ->where('grado_id', $grado->id);
            }
        ])
        ->whereHas('matriculas', function ($query) use ($grado, $periodoFecha) {
            $query->where('grado_id', $grado->id)
                ->where('periodo_id', $periodoFecha->id)
                ->where('estado', '0');
        })
        ->get()
        ->sortBy(function ($estudiante) {
            return optional($estudiante->user)->apellido_paterno .
                optional($estudiante->user)->apellido_materno .
                optional($estudiante->user)->nombre;
        });

        $todosEstudiantes = $estudiantesMatriculadosActivos->merge($estudiantesMatriculadosRetirados)
            ->sortBy(function ($estudiante) {
                return optional($estudiante->user)->apellido_paterno .
                    optional($estudiante->user)->apellido_materno .
                    optional($estudiante->user)->nombre;
            });

        $tiposAsistencia = Tipoasistencia::all();

        $existenRegistros = Asistencia::where('grado_id', $grado->id)
            ->whereDate('fecha', $fechaFormateada)
            ->exists();

        $bimestreActual = null;
        if ($existenRegistros) {
            $registroEjemplo = Asistencia::where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaFormateada)
                ->first();
            $bimestreActual = $registroEjemplo?->bimestre;
        }

        return view('asistencia.grado', [
            'grado'                            => $grado,
            'estudiantesMatriculadosActivos'   => $estudiantesMatriculadosActivos,
            'estudiantesMatriculadosRetirados' => $estudiantesMatriculadosRetirados,
            'estudiantes'                      => $todosEstudiantes,
            'fechaSeleccionada'                => $date,
            'fechaFormateada'                  => $fechaFormateada,
            'tiposAsistencia'                  => $tiposAsistencia,
            'existenRegistros'                => $existenRegistros,
            'bimestreActual'                  => $bimestreActual,
            'periodoActual'                   => $periodoFecha,
            'anioFecha'                       => $anioFecha,
            // Variables simplificadas para bloqueo
            'mesBloqueado'                    => $mesBloqueado,
            'cantidadBloqueados'             => $cantidadBloqueados,
        ]);
    }
    public function guardarMultiple(Request $request, Grado $grado, string $fecha)
    {
        $request->validate([
            'bimestre'                  => 'required|integer|between:1,4',
            'periodo_id'                => 'required|exists:periodos,id',
            'asistencias'               => 'required|array',
            'asistencias.*'             => 'nullable|exists:tipo_asistencias,id',
            'horas'                     => 'required|array',
            'horas.*'                   => 'nullable|date_format:H:i',
        ]);

        $fechaCarbon = Carbon::parse($fecha)->startOfDay();
        $bimestre    = $request->bimestre;
        $periodoId   = $request->periodo_id;
        $userId      = Auth::id();

        $periodo = Periodo::find($periodoId);
        if (!$periodo) {
            return back()->with('error', 'El período especificado no existe.');
        }

        $procesadas  = 0;
        $creadas     = 0;
        $actualizadas = 0;
        $omitidas    = 0;
        $noMatriculados = 0;
        $retirados   = 0;

        // Usar transacción para asegurar integridad de datos
        DB::beginTransaction();

        try {
            foreach ($request->asistencias as $estudianteId => $tipoAsistenciaId) {
                // Si no se seleccionó nada → omitir (pero si ya existe registro, podría eliminarse)
                if (empty($tipoAsistenciaId)) {
                    // Opcional: eliminar registro existente si se deja en blanco
                    $asistenciaExistente = Asistencia::where([
                        'estudiante_id' => $estudianteId,
                        'grado_id'      => $grado->id,
                        'periodo_id'    => $periodoId,
                        'fecha'         => $fechaCarbon,
                    ])->first();

                    if ($asistenciaExistente) {
                        $asistenciaExistente->delete();
                        $actualizadas++; // Contar como actualización (eliminación)
                    }

                    $omitidas++;
                    continue;
                }

                $hora = $request->horas[$estudianteId] ?? now()->format('H:i');

                // Verificar matrícula
                $matricula = Matricula::where('estudiante_id', $estudianteId)
                    ->where('grado_id', $grado->id)
                    ->where('periodo_id', $periodoId)
                    ->first();

                if (!$matricula) {
                    $noMatriculados++;
                    continue;
                }

                if ($matricula->estado == '0') {
                    $retirados++;
                    continue;
                }

                // Usar updateOrCreate para simplificar
                $asistencia = Asistencia::updateOrCreate(
                    [
                        'estudiante_id' => $estudianteId,
                        'grado_id'      => $grado->id,
                        'periodo_id'    => $periodoId,
                        'fecha'         => $fechaCarbon,
                    ],
                    [
                        'tipo_asistencia_id' => $tipoAsistenciaId,
                        'hora'               => $hora,
                        'bimestre'           => $bimestre,
                        'registrador_id'     => $userId,
                        'estado'             => '0',
                    ]
                );

                // Manejar descripción
                if ($asistencia->wasRecentlyCreated) {
                    $asistencia->update(['descripcion' => 'Registro múltiple']);
                    $creadas++;
                } else {
                    // Solo actualizar descripción si no es registro manual
                    if (strpos($asistencia->descripcion, 'Registro Manual') === false) {
                        $asistencia->update(['descripcion' => 'Actualizado múltiple']);
                    }
                    $actualizadas++;
                }

                $procesadas++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al guardar las asistencias: ' . $e->getMessage());
        }

        // Construir mensaje
        $mensaje = "Procesadas $procesadas asistencias: $creadas nuevas, $actualizadas actualizadas.";

        if ($omitidas > 0) {
            $mensaje .= " $omitidas omitidas.";
        }

        if ($noMatriculados > 0) {
            $mensaje .= " $noMatriculados no matriculados.";
        }

        if ($retirados > 0) {
            $mensaje .= " $retirados retirados.";
        }

        $fechaDMY = $fechaCarbon->format('d-m-Y');
        $gradoSeccion = $grado->grado . $grado->seccion;
        $nivel = strtolower($grado->nivel);

        return redirect()->route('asistencia.grado', [
            'grado_grado_seccion' => $gradoSeccion,
            'grado_nivel'         => $nivel,
            'date'                => $fechaDMY,
        ])->with('success', $mensaje);
    }
    public function marcarIndividual(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'tipo_asistencia_id' => 'required|exists:tipo_asistencias,id',
            'fecha'              => 'required|date_format:d-m-Y',
            'hora'               => 'nullable|date_format:H:i',
            'grado_id'           => 'required|exists:grados,id',
            'periodo_id'         => 'required|exists:periodos,id',
            'bimestre'           => 'required|integer|between:1,4',
        ]);

        $fecha = Carbon::createFromFormat('d-m-Y', $request->fecha)->startOfDay();
        $hora  = $request->hora ?? now()->format('H:i');

        // Verificar que existe período
        $periodo = Periodo::find($request->periodo_id);
        if (!$periodo) {
            return response()->json([
                'success' => false,
                'message' => 'El período especificado no existe.'
            ], 422);
        }

        // Verificar que el estudiante está matriculado en ese grado y período
        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->where('grado_id', $request->grado_id)
            ->where('periodo_id', $request->periodo_id)
            ->first();

        if (!$matricula) {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante no está matriculado en este grado para el período seleccionado.'
            ], 422);
        }

        // Verificar que la matrícula está activa (si solo quieres permitir marcar a activos)
        if ($matricula->estado == '0') {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante está retirado en este período. No se puede registrar asistencia.'
            ], 422);
        }

        // Buscar si ya existe registro para ese estudiante, fecha, grado y período
        $asistencia = Asistencia::where([
            'estudiante_id'      => $estudiante->id,
            'grado_id'           => $request->grado_id,
            'periodo_id'         => $request->periodo_id,
            'fecha'              => $fecha,
        ])->first();

        // Descripción y estado fijos para registros manuales
        $descripcion = 'Registro Manual';
        $estadoAsistencia = '0'; // Estado '0' para registro manual

        if ($asistencia) {
            // Actualizar - mantener la descripción existente si ya tiene "Registro Manual"
            // o agregarla si es diferente
            $descripcionActual = $asistencia->descripcion;
            if (strpos($descripcionActual, 'Registro Manual') === false) {
                $descripcionFinal = $descripcionActual ?
                    $descripcionActual . ' | ' . $descripcion :
                    $descripcion;
            } else {
                $descripcionFinal = $descripcionActual;
            }

            $asistencia->update([
                'tipo_asistencia_id' => $request->tipo_asistencia_id,
                'hora'               => $hora,
                'bimestre'           => $request->bimestre,
                'registrador_id'     => Auth::id(),
                'descripcion'        => $descripcionFinal,
                'estado'             => $estadoAsistencia, // Actualizar estado a '0'
            ]);
        } else {
            // Crear nuevo
            $asistencia = Asistencia::create([
                'estudiante_id'      => $estudiante->id,
                'grado_id'           => $request->grado_id,
                'periodo_id'         => $request->periodo_id,
                'bimestre'           => $request->bimestre,
                'tipo_asistencia_id' => $request->tipo_asistencia_id,
                'fecha'              => $fecha,
                'hora'               => $hora,
                'registrador_id'     => Auth::id(),
                'descripcion'        => $descripcion,
                'estado'             => $estadoAsistencia, // Estado '0' para registro manual
            ]);
        }

        // Cargar relación para devolver datos útiles
        $asistencia->load('tipoasistencia', 'periodo');

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada correctamente',
            'asistencia' => [
                'tipo_asistencia_id' => $asistencia->tipo_asistencia_id,
                'nombre_tipo'        => $asistencia->tipoasistencia->nombre ?? '—',
                'color'              => $asistencia->tipoasistencia->color ?? '#6B7280',
                'hora'               => substr($asistencia->hora, 0, 5),
                'periodo_id'         => $asistencia->periodo_id,
                'periodo_nombre'     => $asistencia->periodo->nombre ?? '—',
                'descripcion'        => $asistencia->descripcion,
                'estado'             => $asistencia->estado == '0' ? 'Registro Manual' : 'Registrado',
                'estado_codigo'      => $asistencia->estado,
            ]
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'fecha' => 'required|date_format:Y-m-d',
            'grado_grado_seccion' => 'required|string',
            'grado_nivel' => 'required|string',
            'bimestre' => 'required|integer|min:1|max:4',
            'asistencias' => 'required|array',
            'asistencias.*' => 'required|exists:tipo_asistencias,id',
            'horas.*' => 'nullable|date_format:H:i'
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['asistencias'] as $estudiante_id => $tipo_asistencia_id) {
                $hora = $request->input("horas.$estudiante_id", '00:00');

                Asistencia::create([
                    'estudiante_id' => $estudiante_id,
                    'grado_id' => $validated['grado_id'],
                    'fecha' => $validated['fecha'],
                    'bimestre' => $validated['bimestre'],
                    'tipo_asistencia_id' => $tipo_asistencia_id,
                    'hora' => $hora.':00', // Asegurar formato H:i:s
                    'registrador_id' => auth()->id(),
                    'descripcion' => 'Asistencia registrada manualmente'
                ]);
            }

            DB::commit();

            return redirect()
                ->route('asistencia.grado', [
                    'grado_grado_seccion' => $validated['grado_grado_seccion'],
                    'grado_nivel' => $validated['grado_nivel'],
                    'date' => \Carbon\Carbon::createFromFormat('Y-m-d', $validated['fecha'])->format('d-m-Y')
                ])
                ->with('success', 'Asistencias guardadas correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al guardar las asistencias: '.$e->getMessage());
        }
    }
    public function update(Request $request)
    {
        $validated = $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'fecha' => 'required|date_format:Y-m-d',
            'grado_grado_seccion' => 'required|string',
            'grado_nivel' => 'required|string',
            'bimestre' => 'required|integer|min:1|max:4',
            'asistencias' => 'required|array',
            'asistencias.*' => 'required|exists:tipo_asistencias,id',
            'horas.*' => 'nullable|date_format:H:i'
        ]);

        DB::beginTransaction();
        try {
            // Obtener todos los registros existentes con sus relaciones
            $registrosExistentes = Asistencia::with('tipoasistencia')
                ->where('grado_id', $validated['grado_id'])
                ->whereDate('fecha', $validated['fecha'])
                ->get()
                ->keyBy('estudiante_id');

            $actualizados = 0;
            $creados = 0;
            $cambiosDetallados = [];

            foreach ($validated['asistencias'] as $estudiante_id => $tipo_asistencia_id) {
                $hora = $request->input("horas.$estudiante_id", '00:00:00');

                if (isset($registrosExistentes[$estudiante_id])) {
                    $registro = $registrosExistentes[$estudiante_id];
                    $cambios = [];

                    // Verificar cambios en tipo_asistencia_id
                    if ($registro->tipo_asistencia_id != $tipo_asistencia_id) {
                        $cambios['tipo_asistencia_id'] = [
                            'old' => $registro->tipo_asistencia_id,
                            'new' => $tipo_asistencia_id,
                            'old_name' => optional($registro->tipoasistencia)->nombre,
                            'new_name' => Tipoasistencia::find($tipo_asistencia_id)->nombre
                        ];
                    }

                    // Verificar cambios en hora (comparando solo la parte H:i)
                    $horaExistente = substr($registro->hora, 0, 5);
                    $horaNueva = substr($hora, 0, 5);

                    if ($horaExistente != $horaNueva) {
                        $cambios['hora'] = [
                            'old' => $horaExistente,
                            'new' => $horaNueva
                        ];
                    }

                    // Solo actualizar si hay cambios
                    if (!empty($cambios)) {
                        $updateData = [
                            'registrador_id' => auth()->id(),
                            'descripcion' => 'Asistencia actualizada manualmente'
                        ];

                        if (isset($cambios['tipo_asistencia_id'])) {
                            $updateData['tipo_asistencia_id'] = $tipo_asistencia_id;
                        }

                        if (isset($cambios['hora'])) {
                            $updateData['hora'] = $hora;
                        }

                        $registro->update($updateData);
                        $actualizados++;

                        // Registrar cambios detallados
                        $cambiosDetallados[] = [
                            'estudiante_id' => $estudiante_id,
                            'cambios' => $cambios
                        ];
                    }
                } else {
                    // Crear nuevo registro si no existe
                    Asistencia::create([
                        'estudiante_id' => $estudiante_id,
                        'grado_id' => $validated['grado_id'],
                        'tipo_asistencia_id' => $tipo_asistencia_id,
                        'fecha' => $validated['fecha'],
                        'hora' => $hora,
                        'registrador_id' => auth()->id(),
                        'descripcion' => 'Asistencia registrada manualmente'
                    ]);
                    $creados++;
                }
            }

            DB::commit();

            // Preparar mensaje detallado
            $mensaje = $this->generarMensajeResultado($actualizados, $creados, $cambiosDetallados);

            return redirect()
                ->route('asistencia.grado', [
                    'grado_grado_seccion' => $validated['grado_grado_seccion'],
                    'grado_nivel' => $validated['grado_nivel'],
                    'date' => \Carbon\Carbon::createFromFormat('Y-m-d', $validated['fecha'])->format('d-m-Y')
                ])
                ->with([
                    'success' => $mensaje['resumen'],
                    'cambios_detallados' => $mensaje['detalles']
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar las asistencias: '.$e->getMessage());
        }
    }

    // Método auxiliar para generar mensajes detallados
    protected function generarMensajeResultado($actualizados, $creados, $cambiosDetallados)
    {
        $resumen = 'Proceso completado. ';

        if ($actualizados > 0) {
            $resumen .= "$actualizados registros actualizados. ";
        }
        if ($creados > 0) {
            $resumen .= "$creados nuevos registros creados. ";
        }
        if ($actualizados == 0 && $creados == 0) {
            $resumen = "No se realizaron cambios en los registros.";
        }

        $detalles = [];
        foreach ($cambiosDetallados as $cambio) {
            $detalle = "Estudiante ID {$cambio['estudiante_id']}: ";
            $cambios = [];

            foreach ($cambio['cambios'] as $campo => $valores) {
                if ($campo == 'tipo_asistencia_id') {
                    $cambios[] = "Tipo de asistencia cambió de '{$valores['old_name']}' a '{$valores['new_name']}'";
                } elseif ($campo == 'hora') {
                    $cambios[] = "Hora cambió de {$valores['old']} a {$valores['new']}";
                }
            }

            $detalle .= implode('; ', $cambios);
            $detalles[] = $detalle;
        }

        return [
            'resumen' => $resumen,
            'detalles' => $detalles
        ];
    }
    public function marcarTodosPuntualidad(Request $request)
    {
        try {
            $request->validate([
                'fecha' => 'required|date',
                'bimestre' => 'required|integer|between:1,4'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();

            // Obtener el período basado en la fecha
            $periodoFecha = Periodo::where('anio', $fechaCarbon->year)
                ->where('estado', '1')
                ->first();

            if (!$periodoFecha) {
                return response()->json([
                    'success' => false,
                    'message' => "No hay un período activo configurado para el año {$fechaCarbon->year}"
                ], 400);
            }

            // ========== VALIDACIÓN CRÍTICA: VERIFICAR REGISTROS BLOQUEADOS ==========
            // Verificar si existe AL MENOS UNA asistencia con estado '1' o '2'
            // para la fecha seleccionada en CUALQUIER grado
            $registrosBloqueadosGlobal = Asistencia::where('periodo_id', $periodoFecha->id)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            if ($registrosBloqueadosGlobal) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede marcar asistencia porque ya existen registros confirmados o validados para esta fecha en algunos grados.',
                    'codigo' => 'REGISTROS_BLOQUEADOS'
                ], 403);
            }

            // También verificar si el mes está bloqueado para algún grado
            $mesFecha = $fechaCarbon->format('m');
            $mesBloqueadoGlobal = Asistencia::where('periodo_id', $periodoFecha->id)
                ->whereMonth('fecha', $mesFecha)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            if ($mesBloqueadoGlobal) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede marcar asistencia porque el mes ya tiene registros confirmados o validados en algunos grados.',
                    'codigo' => 'MES_BLOQUEADO'
                ], 403);
            }
            // =====================================================================

            DB::beginTransaction();
            try {
                // Obtener todos los grados activos
                $grados = Grado::where('estado', '1')->get();
                $userId = Auth::id();
                $totalProcesados = 0;
                $totalGradosProcesados = 0;
                $gradosSinEstudiantes = [];
                $resultadosPorGrado = [];

                // Obtener el tipo de asistencia "Puntual"
                $tipoPuntual = Tipoasistencia::where('nombre', 'like', '%puntual%')
                    ->orWhere('nombre', 'like', '%presente%')
                    ->first();

                if (!$tipoPuntual) {
                    $tipoPuntual = Tipoasistencia::first();
                }

                foreach ($grados as $grado) {
                    // Verificar si este grado específico tiene registros bloqueados
                    $gradoTieneBloqueo = Asistencia::where('periodo_id', $periodoFecha->id)
                        ->where('grado_id', $grado->id)
                        ->whereDate('fecha', $fechaCarbon)
                        ->whereIn('estado', ['1', '2'])
                        ->exists();

                    if ($gradoTieneBloqueo) {
                        $resultadosPorGrado[] = [
                            'grado' => "{$grado->grado}° {$grado->seccion}",
                            'estado' => 'bloqueado',
                            'mensaje' => 'Tiene registros confirmados'
                        ];
                        continue;
                    }

                    // Obtener estudiantes matriculados activos en este grado
                    $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodoFecha) {
                        $query->where('grado_id', $grado->id)
                            ->where('periodo_id', $periodoFecha->id)
                            ->where('estado', '1');
                    })->get();

                    if ($estudiantes->isEmpty()) {
                        $gradosSinEstudiantes[] = "{$grado->grado}° {$grado->seccion}";
                        continue;
                    }

                    // Obtener estudiantes que YA TIENEN asistencia registrada para esta fecha en este grado
                    $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodoFecha->id)
                        ->where('grado_id', $grado->id)
                        ->whereDate('fecha', $fechaCarbon)
                        ->pluck('estudiante_id')
                        ->toArray();

                    // Filtrar solo los estudiantes que NO tienen asistencia
                    $estudiantesSinAsistencia = $estudiantes->filter(function($estudiante) use ($estudiantesConAsistencia) {
                        return !in_array($estudiante->id, $estudiantesConAsistencia);
                    });

                    $contadorGrado = 0;
                    foreach ($estudiantesSinAsistencia as $estudiante) {
                        Asistencia::create([
                            'estudiante_id' => $estudiante->id,
                            'grado_id' => $grado->id,
                            'periodo_id' => $periodoFecha->id,
                            'tipo_asistencia_id' => $tipoPuntual->id,
                            'fecha' => $fechaCarbon,
                            'hora' => now()->format('H:i'),
                            'bimestre' => $request->bimestre,
                            'registrador_id' => $userId,
                            'descripcion' => 'Marcado masivo global - puntual',
                            'estado' => '0'
                        ]);
                        $contadorGrado++;
                    }

                    if ($contadorGrado > 0) {
                        $totalProcesados += $contadorGrado;
                        $totalGradosProcesados++;
                        $resultadosPorGrado[] = [
                            'grado' => "{$grado->grado}° {$grado->seccion}",
                            'estado' => 'procesado',
                            'cantidad' => $contadorGrado,
                            'total_estudiantes' => $estudiantes->count(),
                            'ya_tenian' => count($estudiantesConAsistencia)
                        ];
                    } else {
                        $resultadosPorGrado[] = [
                            'grado' => "{$grado->grado}° {$grado->seccion}",
                            'estado' => 'sin_pendientes',
                            'mensaje' => 'Todos ya tenían asistencia'
                        ];
                    }
                }

                DB::commit();

                // Construir mensaje detallado
                $mensaje = "Proceso completado: ";
                $mensaje .= "{$totalProcesados} estudiantes marcados en {$totalGradosProcesados} grados. ";

                if (!empty($gradosSinEstudiantes)) {
                    $mensaje .= "Grados sin estudiantes: " . implode(', ', $gradosSinEstudiantes) . ". ";
                }

                return response()->json([
                    'success' => true,
                    'message' => $mensaje,
                    'total_afectados' => $totalProcesados,
                    'total_grados_procesados' => $totalGradosProcesados,
                    'detalle_por_grado' => $resultadosPorGrado,
                    'grados_sin_estudiantes' => $gradosSinEstudiantes
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al marcar asistencias: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la solicitud: ' . $e->getMessage()
            ], 400);
        }
    }
    public function marcarTodosTardanza(Request $request)
    {
        try {
            $request->validate([
                'fecha' => 'required|date',
                'bimestre' => 'required|integer|between:1,4'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();

            $periodoFecha = Periodo::where('anio', $fechaCarbon->year)
                ->where('estado', '1')
                ->first();

            if (!$periodoFecha) {
                return response()->json([
                    'success' => false,
                    'message' => "No hay período activo para el año {$fechaCarbon->year}"
                ], 400);
            }

            // Verificar registros bloqueados
            $registrosBloqueados = Asistencia::where('periodo_id', $periodoFecha->id)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            if ($registrosBloqueados) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede marcar porque existen registros confirmados'
                ], 403);
            }

            DB::beginTransaction();
            try {
                $grados = Grado::where('estado', '1')->get();
                $userId = Auth::id();
                $totalProcesados = 0;
                $totalGradosProcesados = 0;

                $tipoTardanza = Tipoasistencia::where('nombre', 'like', '%tardanza%')
                    ->orWhere('nombre', 'like', '%tarde%')
                    ->first();

                if (!$tipoTardanza) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontró tipo de asistencia para tardanza'
                    ], 400);
                }

                foreach ($grados as $grado) {
                    $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodoFecha) {
                        $query->where('grado_id', $grado->id)
                            ->where('periodo_id', $periodoFecha->id)
                            ->where('estado', '1');
                    })->get();

                    if ($estudiantes->isEmpty()) continue;

                    $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodoFecha->id)
                        ->where('grado_id', $grado->id)
                        ->whereDate('fecha', $fechaCarbon)
                        ->pluck('estudiante_id')
                        ->toArray();

                    $estudiantesSinAsistencia = $estudiantes->filter(function($estudiante) use ($estudiantesConAsistencia) {
                        return !in_array($estudiante->id, $estudiantesConAsistencia);
                    });

                    $contadorGrado = 0;
                    foreach ($estudiantesSinAsistencia as $estudiante) {
                        Asistencia::create([
                            'estudiante_id' => $estudiante->id,
                            'grado_id' => $grado->id,
                            'periodo_id' => $periodoFecha->id,
                            'tipo_asistencia_id' => $tipoTardanza->id,
                            'fecha' => $fechaCarbon,
                            'hora' => now()->format('H:i'),
                            'bimestre' => $request->bimestre,
                            'registrador_id' => $userId,
                            'descripcion' => 'Marcado masivo tardanza',
                            'estado' => '0'
                        ]);
                        $contadorGrado++;
                    }

                    if ($contadorGrado > 0) {
                        $totalProcesados += $contadorGrado;
                        $totalGradosProcesados++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Se marcaron {$totalProcesados} estudiantes con tardanza en {$totalGradosProcesados} grados",
                    'total_afectados' => $totalProcesados,
                    'total_grados_procesados' => $totalGradosProcesados
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    // En tu AsistenciaController.php, agrega este método:
    public function verificarBloqueoFecha(Request $request)
    {
        try {
            $fecha = Carbon::parse($request->fecha)->format('Y-m-d');
            $anioFecha = Carbon::parse($request->fecha)->year;

            $periodo = Periodo::where('anio', $anioFecha)
                ->where('estado', '1')
                ->first();

            if (!$periodo) {
                return response()->json([
                    'bloqueada' => false,
                    'mensaje' => 'No hay período activo para este año'
                ]);
            }

            // Verificar si existe algún registro bloqueado en esta fecha
            $gradosBloqueados = Grado::whereHas('asistencias', function($query) use ($periodo, $fecha) {
                $query->where('periodo_id', $periodo->id)
                    ->whereDate('fecha', $fecha)
                    ->whereIn('estado', ['1', '2']);
            })->get();

            $bloqueada = $gradosBloqueados->count() > 0;

            return response()->json([
                'bloqueada' => $bloqueada,
                'grados_bloqueados' => $bloqueada ? $gradosBloqueados->pluck('nombre_completo', 'id') : [],
                'fecha' => $fecha,
                'fecha_formateada' => Carbon::parse($fecha)->format('d/m/Y'),
                'total_grados' => $gradosBloqueados->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'bloqueada' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    public function reporteAsistencia(Request $request)
    {
        // Obtener períodos activos para el filtro
        $periodos = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->get();

        $grados = Grado::where('estado', 1)
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->get();

        $tiposAsistencia = Tipoasistencia::all();

        // Si se envió el formulario, procesar los resultados
        if ($request->has('grado_id')) {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'periodo_id' => 'required|exists:periodos,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
            ]);

            $query = Asistencia::with(['estudiante.user', 'grado', 'tipoasistencia', 'bimestre', 'periodo'])
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->where('periodo_id', $request->periodo_id); // Filtrar por período

            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            if ($request->filled('estudiante_id')) {
                $query->where('estudiante_id', $request->estudiante_id);
            }

            if ($request->filled('bimestre')) {
                $query->where('bimestre', $request->bimestre);
            }

            if ($request->filled('tipo_asistencia_id')) {
                $query->where('tipo_asistencia_id', $request->tipo_asistencia_id);
            }

            $asistencias = $query->orderBy('fecha', 'desc')
                                ->orderBy('estudiante_id')
                                ->get();

            // Calcular estadísticas
            $estadisticas = [];
            if ($asistencias->count() > 0) {
                $estadisticas = [
                    'total_asistencias' => $asistencias->count(),
                    'por_tipo_asistencia' => $asistencias->groupBy('tipo_asistencia_id')->map->count(),
                    'por_bimestre' => $asistencias->groupBy('bimestre')->map->count(),
                    'fecha_primera' => $asistencias->min('fecha'),
                    'fecha_ultima' => $asistencias->max('fecha'),
                    'total_estudiantes' => $asistencias->unique('estudiante_id')->count(),
                ];
            }

            return view('asistencia.reporte', compact(
                'periodos',
                'grados',
                'tiposAsistencia',
                'asistencias',
                'estadisticas'
            ));
        }

        return view('asistencia.reporte', [
            'periodos' => $periodos,
            'grados' => $grados,
            'tiposAsistencia' => $tiposAsistencia,
            'asistencias' => collect(),
            'estadisticas' => null
        ]);
    }
    public function estudiantesPorGrado(Request $request)
    {
        $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'periodo_id' => 'required|exists:periodos,id' // Ahora necesitamos también el periodo_id
        ]);

        $gradoId = $request->get('grado_id');
        $periodoId = $request->get('periodo_id');

        // Obtener estudiantes MATRICULADOS en el grado y período específicos
        $estudiantes = Matricula::where('grado_id', $gradoId)
            ->where('periodo_id', $periodoId)
            ->where('estado', '1') // Solo matriculados activos
            ->with(['estudiante.user'])
            ->get()
            ->map(function($matricula) {
                $estudiante = $matricula->estudiante;

                if (!$estudiante || !$estudiante->user) {
                    return null;
                }

                // Formato: Apellidos, Nombres
                $apellidos = trim($estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno);
                $nombres = $estudiante->user->nombre;

                return [
                    'id' => $estudiante->id,
                    'nombres_completos' => $apellidos . ', ' . $nombres,
                    'matricula_estado' => $matricula->estado
                ];
            })
            ->filter() // Filtrar valores nulos
            ->sortBy('nombres_completos')
            ->values();

        return response()->json($estudiantes);
    }
    public function bloqueoView(Request $request)
    {
        // Obtener todos los periodos activos
        $periodos = Periodo::where('estado', '1')->get();

        // Meses del año
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];

        // Obtener usuarios con rol admin o director
        $usuariosAutorizados = User::whereHas('roles', function($query) {
            $query->whereIn('nombre', ['admin', 'director']);
        })->where('estado', '1')->with('roles')->get();

        // Inicialización de variables
        $asistencias = collect();
        $periodoSeleccionado = null;
        $mesSeleccionado = null;
        $gradoSeleccionado = null;
        $periodoAnio = null;
        $distribucionEstados = collect();
        $contadoresEstados = [
            'libres' => 0,
            'bloqueados' => 0,
            'bloqueados_def' => 0,
            'total' => 0
        ];

        // Obtener grados para filtro adicional
        $grados = Grado::where('estado', 1)->get();

        if ($request->has('periodo_id') && $request->has('mes')) {
            $periodoSeleccionado = $request->input('periodo_id');
            $mesSeleccionado = $request->input('mes');
            $gradoSeleccionado = $request->input('grado_id');

            // Obtener el periodo para el año
            $periodo = Periodo::find($periodoSeleccionado);
            $periodoAnio = $periodo ? $periodo->anio : null;

            // Construir la consulta base
            $query = Asistencia::with([
                    'estudiante.user',
                    'grado',
                    'tipoasistencia',
                    'periodo'
                ])
                ->where('periodo_id', $periodoSeleccionado);

            if ($periodoAnio) {
                $query->whereYear('fecha', $periodoAnio)
                    ->whereMonth('fecha', $mesSeleccionado);
            }

            // Filtrar por grado si está seleccionado
            if ($gradoSeleccionado) {
                $query->where('grado_id', $gradoSeleccionado);
            }

            // Obtener distribución de estados para contadores sin ejecutar la query principal aún
            $distribucionEstados = (clone $query)
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->get()
                ->keyBy('estado');

            // Calcular contadores
            $contadoresEstados['libres'] = $distribucionEstados->get(0)->total ?? 0;
            $contadoresEstados['bloqueados'] = $distribucionEstados->get(1)->total ?? 0;
            $contadoresEstados['bloqueados_def'] = $distribucionEstados->get(2)->total ?? 0;
            $contadoresEstados['total'] = array_sum($contadoresEstados);

            // Ordenar y obtener resultados finales
            $asistencias = $query->orderBy('fecha', 'asc')
                ->orderBy('hora', 'asc')
                ->get();
        }

        return view('asistencia.bloqueo', compact(
            'periodos',
            'meses',
            'grados',
            'asistencias',
            'periodoSeleccionado',
            'mesSeleccionado',
            'gradoSeleccionado',
            'periodoAnio',
            'distribucionEstados',
            'contadoresEstados',
            'usuariosAutorizados'
        ));
    }
    public function bloquearMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id'
        ]);

        try {
            // 1. Obtener los IDs de los registros que cumplen los filtros
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '0'); // Nota: tu consulta usa '0' como string

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 0
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias libres (estado 0) para bloquear. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '1']); // Cambiar a estado 1

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias bloqueadas correctamente");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // Bloquear definitivamente todas las asistencias bloqueadas temporalmente (estado 1 -> 2)
    public function bloquearDefinitivoMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id'
        ]);

        try {
            // 1. Obtener los IDs de los registros que cumplen los filtros (estado 1)
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '1'); // Solo las bloqueadas temporalmente

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 1
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias bloqueadas temporalmente (estado 1) para bloquear definitivamente. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs (1 -> 2)
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '2']); // Cambiar a estado 2 (definitivo)

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias bloqueadas definitivamente");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    // Liberar todas las asistencias bloqueadas temporalmente (estado 1 -> 0)
    public function liberarMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id'
        ]);

        try {
            // 1. Obtener los IDs de los registros que cumplen los filtros (estado 1)
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '1'); // Solo las bloqueadas temporalmente

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 1
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias bloqueadas temporalmente (estado 1) para liberar. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs (1 -> 0)
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '0']);

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias liberadas correctamente");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    // Liberar de bloqueo definitivo a temporal (estado 2 -> 1)
    public function liberarDefinitivoMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id',
            'usuario_autorizador_id' => 'required|exists:users,id',
            'password_confirmation' => 'required|string'
        ]);

        try {
            // Validar que el usuario tenga rol de admin o director
            $usuarioAutorizador = User::with('roles')->find($request->usuario_autorizador_id);

            if (!$usuarioAutorizador) {
                return back()->withInput()->with('error', 'Usuario autorizador no encontrado.');
            }

            // Verificar que tenga rol admin o director
            $tieneRol = $usuarioAutorizador->roles->contains(function($role) {
                return in_array($role->nombre, ['admin', 'director']);
            });

            if (!$tieneRol) {
                return back()->withInput()->with('error', 'El usuario seleccionado no tiene permisos para realizar esta acción.');
            }

            // Verificar contraseña
            if (!Hash::check($request->password_confirmation, $usuarioAutorizador->password)) {
                return back()->withInput()->with('error', 'Contraseña incorrecta.');
            }

            // 1. Obtener los IDs de los registros que cumplen los filtros (estado 2)
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '2'); // Solo las bloqueadas definitivamente

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 2
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias bloqueadas definitivamente (estado 2) para liberar. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs (2 -> 1)
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '1']);

            // Registrar quien realizó la acción
            \Log::info('LIBERAR DEFINITIVO - Acción realizada por:', [
                'usuario_id' => $usuarioAutorizador->id,
                'usuario_nombre' => $usuarioAutorizador->nombre,
                'registros_afectados' => $updated,
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes
            ]);

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias cambiadas a bloqueo temporal. Autorizado por: {$usuarioAutorizador->nombre}");

        } catch (\Exception $e) {
            \Log::error('LIBERAR DEFINITIVO MASIVO - Error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
