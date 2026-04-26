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
use App\Models\Periodobimestre;
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
        $fechaSeleccionada = $request->get('fecha', now()->format('Y-m-d'));
        $periodo_id = $request->get('periodo_id');
        $periodobimestre_id = $request->get('periodobimestre_id');

        // Compatibilidad con versión anterior (filtro por año)
        if ($request->has('year') && !$periodo_id) {
            $currentYear = $request->get('year');
            $periodoActual = Periodo::where('anio', $currentYear)->where('estado', '1')->first();
            if ($periodoActual) {
                $periodo_id = $periodoActual->id;
            }
        }

        // Obtener todos los periodos activos
        $periodos = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Determinar el periodo actual
        if ($periodo_id) {
            $periodoActual = Periodo::find($periodo_id);
        } else {
            $periodoActual = Periodo::where('estado', '1')
                ->where('fecha_inicio', '<=', $fechaSeleccionada)
                ->where('fecha_fin', '>=', $fechaSeleccionada)
                ->first();

            if (!$periodoActual) {
                $periodoActual = Periodo::where('estado', '1')
                    ->orderBy('anio', 'desc')
                    ->first();
            }
        }

        // Obtener bimestres del periodo
        $bimestres = collect();
        if ($periodoActual) {
            $bimestres = Periodobimestre::where('periodo_id', $periodoActual->id)
                ->orderBy('fecha_inicio', 'asc')
                ->get();
        }

        // Determinar bimestre actual
        $bimestreActual = null;
        if ($periodobimestre_id) {
            $bimestreActual = Periodobimestre::find($periodobimestre_id);
        } elseif ($periodoActual) {
            $bimestreActual = Periodobimestre::where('periodo_id', $periodoActual->id)
                ->where('fecha_inicio', '<=', $fechaSeleccionada)
                ->where('fecha_fin', '>=', $fechaSeleccionada)
                ->first();
        }

        // Ajustar fecha si está fuera del bimestre
        if ($bimestreActual && ($fechaSeleccionada < $bimestreActual->fecha_inicio || $fechaSeleccionada > $bimestreActual->fecha_fin)) {
            $fechaSeleccionada = $bimestreActual->fecha_inicio;
        }

        // Obtener grados con conteos de asistencias
        $grados = Grado::withCount([
            'asistencias as total_asistencias' => function($query) use ($periodoActual, $bimestreActual) {
                if ($periodoActual) {
                    $query->where('periodo_id', $periodoActual->id);
                }
                if ($bimestreActual) {
                    $query->where('periodobimestre_id', $bimestreActual->id);
                }
            },
            'asistencias as asistencias_hoy' => function($query) use ($periodoActual, $bimestreActual, $fechaSeleccionada) {
                if ($periodoActual) {
                    $query->where('periodo_id', $periodoActual->id);
                }
                if ($bimestreActual) {
                    $query->where('periodobimestre_id', $bimestreActual->id);
                }
                $query->whereDate('fecha', $fechaSeleccionada);
            },
            'matriculas as estudiantes_matriculados' => function($query) use ($periodoActual) {
                if ($periodoActual) {
                    $query->where('periodo_id', $periodoActual->id)
                        ->where('estado', '1');
                }
            }
        ])
        ->orderBy('nivel')
        ->orderBy('grado')
        ->orderBy('seccion')
        ->get();

        // Verificar registros bloqueados por grado
        foreach ($grados as $grado) {
            $grado->tiene_registros_bloqueados_hoy = Asistencia::where('periodo_id', $periodoActual?->id)
                ->where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaSeleccionada)
                ->whereIn('estado', ['1', '2'])
                ->exists();
        }

        $availableYears = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->unique()
            ->toArray();

        return view('asistencia.index', compact(
            'periodos',
            'periodoActual',
            'bimestres',
            'bimestreActual',
            'grados',
            'fechaSeleccionada',
            'availableYears'
        ));
    }
    // Método para obtener bimestres por periodo (AJAX)
    public function getBimestresByPeriodo($periodo_id)
    {
        $bimestres = Periodobimestre::where('periodo_id', $periodo_id)
            ->orderBy('fecha_inicio', 'asc')
            ->get(['id', 'bimestre', 'fecha_inicio', 'fecha_fin', 'tipo_bimestre']);

        return response()->json([
            'success' => true,
            'bimestres' => $bimestres
        ]);
    }
    // Método para obtener información de fecha y bimestre (AJAX)
    public function obtenerInfoFecha(Request $request)
    {
        $fecha = $request->get('fecha');
        $periodo_id = $request->get('periodo_id');
        $periodobimestre_id = $request->get('periodobimestre_id');

        $response = [
            'success' => true,
            'fecha_valida' => false,
            'periodobimestre' => null,
            'periodo' => null,
            'message' => ''
        ];

        $periodo = Periodo::find($periodo_id);
        if (!$periodo) {
            $response['success'] = false;
            $response['message'] = 'Período no válido';
            return response()->json($response);
        }

        if ($fecha < $periodo->fecha_inicio || $fecha > $periodo->fecha_fin) {
            $response['success'] = false;
            $response['message'] = "La fecha debe estar dentro del período {$periodo->nombre}";
            return response()->json($response);
        }

        $periodobimestre = Periodobimestre::find($periodobimestre_id);
        if (!$periodobimestre) {
            $response['success'] = false;
            $response['message'] = 'Bimestre no válido';
            return response()->json($response);
        }

        if ($fecha < $periodobimestre->fecha_inicio || $fecha > $periodobimestre->fecha_fin) {
            $response['success'] = false;
            $response['message'] = "La fecha debe estar dentro del bimestre {$periodobimestre->bimestre}";
            return response()->json($response);
        }

        $existenRegistros = Asistencia::where('periodo_id', $periodo_id)
            ->where('periodobimestre_id', $periodobimestre_id)
            ->whereDate('fecha', $fecha)
            ->exists();

        $response['fecha_valida'] = true;
        $response['periodobimestre'] = $periodobimestre;
        $response['periodo'] = $periodo;
        $response['existe_registro_pendiente'] = $existenRegistros;
        $response['message'] = $existenRegistros ? 'Ya existen registros para esta fecha' : 'Fecha válida para registrar asistencia';

        return response()->json($response);
    }
    public function obtenerBimestreYEstadoPorFecha(Request $request)
    {
        try {
            $fecha = Carbon::parse($request->fecha)->format('Y-m-d');

            $periodobimestre = Periodobimestre::where('fecha_inicio', '<=', $fecha)
                ->where('fecha_fin', '>=', $fecha)
                ->first();

            if (!$periodobimestre) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay bimestre activo para esta fecha'
                ]);
            }

            $periodo = $periodobimestre->periodo;

            $existeRegistroPendiente = Asistencia::where('periodo_id', $periodo->id)
                ->where('periodobimestre_id', $periodobimestre->id)
                ->whereDate('fecha', $fecha)
                ->where('estado', '0')
                ->exists();

            $totalEstudiantesActivos = Estudiante::whereHas('matriculas', function($query) use ($periodo) {
                $query->where('periodo_id', $periodo->id)
                    ->where('estado', '1');
            })->count();

            $totalAsistenciasFecha = Asistencia::where('periodo_id', $periodo->id)
                ->where('periodobimestre_id', $periodobimestre->id)
                ->whereDate('fecha', $fecha)
                ->count();

            $todosTienenAsistencia = ($totalEstudiantesActivos > 0 &&
                                    $totalAsistenciasFecha >= $totalEstudiantesActivos);

            $respuesta = [
                'success' => true,
                'periodobimestre_id' => $periodobimestre->id,
                'periodobimestre_nombre' => $periodobimestre->bimestre,
                'existe_registro_pendiente' => $existeRegistroPendiente,
                'todos_tienen_asistencia' => $todosTienenAsistencia,
                'total_estudiantes' => $totalEstudiantesActivos,
                'total_asistencias' => $totalAsistenciasFecha,
                'message' => 'Bimestre encontrado'
            ];

            if ($existeRegistroPendiente) {
                $totalPendientes = Asistencia::where('periodo_id', $periodo->id)
                    ->where('periodobimestre_id', $periodobimestre->id)
                    ->whereDate('fecha', $fecha)
                    ->where('estado', '0')
                    ->count();
                $respuesta['total_pendientes'] = $totalPendientes;
                $respuesta['message'] = "Existen {$totalPendientes} registro(s) pendiente(s) para esta fecha";
            }

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
                'periodobimestre_id' => 'required|exists:periodo_bimestres,id'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();
            $grado = Grado::findOrFail($request->grado_id);
            $userId = Auth::id();

            $periodobimestre = Periodobimestre::findOrFail($request->periodobimestre_id);
            $periodo = $periodobimestre->periodo;

            if ($fechaCarbon < $periodobimestre->fecha_inicio || $fechaCarbon > $periodobimestre->fecha_fin) {
                return response()->json([
                    'success' => false,
                    'message' => "La fecha debe estar dentro del bimestre {$periodobimestre->bimestre}"
                ], 400);
            }

            $tieneBloqueo = Asistencia::where('periodo_id', $periodo->id)
                ->where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            if ($tieneBloqueo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede realizar porque existen registros confirmados'
                ], 403);
            }

            DB::beginTransaction();
            try {
                $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodo) {
                    $query->where('grado_id', $grado->id)
                        ->where('periodo_id', $periodo->id)
                        ->where('estado', '1');
                })->get();

                $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodo->id)
                    ->where('periodobimestre_id', $periodobimestre->id)
                    ->where('grado_id', $grado->id)
                    ->whereDate('fecha', $fechaCarbon)
                    ->pluck('estudiante_id')
                    ->toArray();

                $estudiantesSinAsistencia = $estudiantes->filter(function($estudiante) use ($estudiantesConAsistencia) {
                    return !in_array($estudiante->id, $estudiantesConAsistencia);
                });

                if ($estudiantesSinAsistencia->isEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos los estudiantes ya tienen asistencia registrada'
                    ], 400);
                }

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
                        'periodobimestre_id' => $periodobimestre->id,
                        'tipo_asistencia_id' => $tipoPuntual->id,
                        'fecha' => $fechaCarbon,
                        'hora' => now()->format('H:i'),
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
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }
    public function marcarRestoDeEstudiantesConTardanza(Request $request)
    {
        try {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'fecha' => 'required|date',
                'periodobimestre_id' => 'required|exists:periodo_bimestres,id'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();
            $grado = Grado::findOrFail($request->grado_id);
            $userId = Auth::id();

            $periodobimestre = Periodobimestre::findOrFail($request->periodobimestre_id);
            $periodo = $periodobimestre->periodo;

            if ($fechaCarbon < $periodobimestre->fecha_inicio || $fechaCarbon > $periodobimestre->fecha_fin) {
                return response()->json([
                    'success' => false,
                    'message' => "La fecha debe estar dentro del bimestre {$periodobimestre->bimestre}"
                ], 400);
            }

            $tieneBloqueo = Asistencia::where('periodo_id', $periodo->id)
                ->where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('estado', ['1', '2'])
                ->exists();

            if ($tieneBloqueo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede realizar porque existen registros confirmados'
                ], 403);
            }

            DB::beginTransaction();
            try {
                $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodo) {
                    $query->where('grado_id', $grado->id)
                        ->where('periodo_id', $periodo->id)
                        ->where('estado', '1');
                })->get();

                $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodo->id)
                    ->where('periodobimestre_id', $periodobimestre->id)
                    ->where('grado_id', $grado->id)
                    ->whereDate('fecha', $fechaCarbon)
                    ->pluck('estudiante_id')
                    ->toArray();

                $estudiantesSinAsistencia = $estudiantes->filter(function($estudiante) use ($estudiantesConAsistencia) {
                    return !in_array($estudiante->id, $estudiantesConAsistencia);
                });

                if ($estudiantesSinAsistencia->isEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos los estudiantes ya tienen asistencia registrada'
                    ], 400);
                }

                $tipoTardanza = Tipoasistencia::where('nombre', 'like', '%tardanza%')
                    ->orWhere('nombre', 'like', '%tarde%')
                    ->first();

                if (!$tipoTardanza) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontró tipo de asistencia para tardanza'
                    ], 400);
                }

                $contador = 0;
                foreach ($estudiantesSinAsistencia as $estudiante) {
                    Asistencia::create([
                        'estudiante_id' => $estudiante->id,
                        'grado_id' => $grado->id,
                        'periodo_id' => $periodo->id,
                        'periodobimestre_id' => $periodobimestre->id,
                        'tipo_asistencia_id' => $tipoTardanza->id,
                        'fecha' => $fechaCarbon,
                        'hora' => now()->format('H:i'),
                        'registrador_id' => $userId,
                        'descripcion' => 'Marcado masivo - resto con tardanza',
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
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }
    public function marcarTodosPuntualidad(Request $request)
    {
        try {
            $request->validate([
                'fecha' => 'required|date',
                'periodobimestre_id' => 'required|exists:periodo_bimestres,id'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();

            $periodobimestre = Periodobimestre::findOrFail($request->periodobimestre_id);
            $periodo = $periodobimestre->periodo;

            if ($fechaCarbon < $periodobimestre->fecha_inicio || $fechaCarbon > $periodobimestre->fecha_fin) {
                return response()->json([
                    'success' => false,
                    'message' => "La fecha debe estar dentro del bimestre {$periodobimestre->bimestre}"
                ], 400);
            }

            $registrosBloqueados = Asistencia::where('periodo_id', $periodo->id)
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

                $tipoPuntual = Tipoasistencia::where('nombre', 'like', '%puntual%')
                    ->orWhere('nombre', 'like', '%presente%')
                    ->first();

                if (!$tipoPuntual) {
                    $tipoPuntual = Tipoasistencia::first();
                }

                foreach ($grados as $grado) {
                    $gradoTieneBloqueo = Asistencia::where('periodo_id', $periodo->id)
                        ->where('grado_id', $grado->id)
                        ->whereDate('fecha', $fechaCarbon)
                        ->whereIn('estado', ['1', '2'])
                        ->exists();

                    if ($gradoTieneBloqueo) {
                        continue;
                    }

                    $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodo) {
                        $query->where('grado_id', $grado->id)
                            ->where('periodo_id', $periodo->id)
                            ->where('estado', '1');
                    })->get();

                    if ($estudiantes->isEmpty()) continue;

                    $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodo->id)
                        ->where('periodobimestre_id', $periodobimestre->id)
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
                            'periodo_id' => $periodo->id,
                            'periodobimestre_id' => $periodobimestre->id,
                            'tipo_asistencia_id' => $tipoPuntual->id,
                            'fecha' => $fechaCarbon,
                            'hora' => now()->format('H:i'),
                            'registrador_id' => $userId,
                            'descripcion' => 'Marcado masivo global - puntual',
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
                    'message' => "Se marcaron {$totalProcesados} estudiantes en {$totalGradosProcesados} grados",
                    'total_afectados' => $totalProcesados,
                    'total_grados_procesados' => $totalGradosProcesados
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }
    public function marcarTodosTardanza(Request $request)
    {
        try {
            $request->validate([
                'fecha' => 'required|date',
                'periodobimestre_id' => 'required|exists:periodo_bimestres,id'
            ]);

            $fechaCarbon = Carbon::parse($request->fecha)->startOfDay();

            $periodobimestre = Periodobimestre::findOrFail($request->periodobimestre_id);
            $periodo = $periodobimestre->periodo;

            if ($fechaCarbon < $periodobimestre->fecha_inicio || $fechaCarbon > $periodobimestre->fecha_fin) {
                return response()->json([
                    'success' => false,
                    'message' => "La fecha debe estar dentro del bimestre {$periodobimestre->bimestre}"
                ], 400);
            }

            $registrosBloqueados = Asistencia::where('periodo_id', $periodo->id)
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
                    $estudiantes = Estudiante::whereHas('matriculas', function ($query) use ($grado, $periodo) {
                        $query->where('grado_id', $grado->id)
                            ->where('periodo_id', $periodo->id)
                            ->where('estado', '1');
                    })->get();

                    if ($estudiantes->isEmpty()) continue;

                    $estudiantesConAsistencia = Asistencia::where('periodo_id', $periodo->id)
                        ->where('periodobimestre_id', $periodobimestre->id)
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
                            'periodo_id' => $periodo->id,
                            'periodobimestre_id' => $periodobimestre->id,
                            'tipo_asistencia_id' => $tipoTardanza->id,
                            'fecha' => $fechaCarbon,
                            'hora' => now()->format('H:i'),
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
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }
    public function verificarBloqueoFecha(Request $request)
    {
        $fecha = $request->get('fecha');
        $grado_id = $request->get('grado_id');
        $periodo_id = $request->get('periodo_id');

        $mesBloqueado = Asistencia::where('periodo_id', $periodo_id)
            ->where('grado_id', $grado_id)
            ->whereMonth('fecha', Carbon::parse($fecha)->month)
            ->whereIn('estado', ['1', '2'])
            ->exists();

        return response()->json([
            'success' => true,
            'mes_bloqueado' => $mesBloqueado
        ]);
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

        if (!preg_match('/^(\d+)([a-zA-Z]+)$/', $grado_grado_seccion, $matches)) {
            abort(400, 'Formato de grado/sección inválido. Ejemplo: 1a, 2b');
        }

        $gradoNumero = $matches[1];
        $gradoSeccion = $matches[2];

        $grado = Grado::where('grado', $gradoNumero)
            ->where('seccion', $gradoSeccion)
            ->where('nivel', $grado_nivel)
            ->firstOrFail();

        $periodoFecha = Periodo::where('anio', $anioFecha)
            ->where('estado', '1')
            ->first();

        if (!$periodoFecha) {
            abort(400, "No hay un período activo configurado para el año {$anioFecha}");
        }

        $bimestreActual = Periodobimestre::where('periodo_id', $periodoFecha->id)
            ->where('fecha_inicio', '<=', $fechaFormateada)
            ->where('fecha_fin', '>=', $fechaFormateada)
            ->first();

        $bimestresPeriodo = Periodobimestre::where('periodo_id', $periodoFecha->id)
            ->orderBy('fecha_inicio', 'asc')
            ->get();

        // Validación de mes bloqueado
        $mesBloqueado = Asistencia::where('periodo_id', $periodoFecha->id)
            ->where('grado_id', $grado->id)
            ->whereMonth('fecha', $mesFecha)
            ->whereIn('estado', ['1', '2'])
            ->exists();

        $cantidadBloqueados = 0;
        if ($mesBloqueado) {
            $cantidadBloqueados = Asistencia::where('periodo_id', $periodoFecha->id)
                ->where('grado_id', $grado->id)
                ->whereMonth('fecha', $mesFecha)
                ->whereIn('estado', ['1', '2'])
                ->count();
        }

        // Obtener estudiantes activos con sus asistencias preprocesadas
        $estudiantesMatriculadosActivos = Estudiante::with(['user',
            'asistencias' => function ($query) use ($fechaFormateada, $grado, $periodoFecha, $bimestreActual) {
                $query->whereDate('fecha', $fechaFormateada)
                    ->where('grado_id', $grado->id)
                    ->where('periodo_id', $periodoFecha->id);
                if ($bimestreActual) {
                    $query->where('periodobimestre_id', $bimestreActual->id);
                }
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

        // Procesar estudiantes para la vista
        $estudiantesProcesados = $estudiantesMatriculadosActivos->map(function ($estudiante) {
            $asistencia = $estudiante->asistencias->first();
            return [
                'id' => $estudiante->id,
                'nombre_completo' => trim(
                    ($estudiante->user->apellido_paterno ?? '') . ' ' .
                    ($estudiante->user->apellido_materno ?? '') . ', ' .
                    ($estudiante->user->nombre ?? '')
                ),
                'tipo_asistencia_id' => $asistencia ? $asistencia->tipo_asistencia_id : null,
                'tipo_asistencia_nombre' => $asistencia && $asistencia->tipoasistencia ? $asistencia->tipoasistencia->nombre : null,
                'tipo_asistencia_color' => $asistencia && $asistencia->tipoasistencia ? $asistencia->tipoasistencia->color_hex : '#6B7280',
                'hora' => $asistencia ? substr($asistencia->hora, 0, 5) : now()->format('H:i'),
                'estado' => $asistencia ? 'Registrado' : 'Pendiente',
                'estado_clase' => $asistencia ? 'bg-success' : 'bg-warning',
            ];
        });

        // Procesar estudiantes retirados
        $estudiantesMatriculadosRetirados = Estudiante::with(['user',
            'asistencias' => function ($query) use ($fechaFormateada, $grado, $periodoFecha, $bimestreActual) {
                $query->whereDate('fecha', $fechaFormateada)
                    ->where('grado_id', $grado->id)
                    ->where('periodo_id', $periodoFecha->id);
                if ($bimestreActual) {
                    $query->where('periodobimestre_id', $bimestreActual->id);
                }
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

        $estudiantesRetiradosProcesados = $estudiantesMatriculadosRetirados->map(function ($estudiante) {
            $asistencia = $estudiante->asistencias->first();
            return [
                'id' => $estudiante->id,
                'nombre_completo' => trim(
                    ($estudiante->user->apellido_paterno ?? '') . ' ' .
                    ($estudiante->user->apellido_materno ?? '') . ', ' .
                    ($estudiante->user->nombre ?? '')
                ),
                'tipo_asistencia_id' => $asistencia ? $asistencia->tipo_asistencia_id : null,
                'tipo_asistencia_nombre' => $asistencia && $asistencia->tipoasistencia ? $asistencia->tipoasistencia->nombre : null,
                'hora' => $asistencia ? substr($asistencia->hora, 0, 5) : '--:--',
                'estado' => $asistencia ? 'Registrado (Retirado)' : 'Sin registro',
                'estado_clase' => $asistencia ? 'bg-secondary' : 'bg-light text-dark',
            ];
        });

        $resumenAsistencias = [];
        $tiposAsistencia = Tipoasistencia::all();

        foreach ($tiposAsistencia as $tipo) {
            $resumenAsistencias[] = [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'color_hex' => $tipo->color_hex ?? '#6B7280',
                'cantidad' => $estudiantesProcesados->filter(function($estudiante) use ($tipo) {
                    return $estudiante['tipo_asistencia_id'] == $tipo->id;
                })->count()
            ];
        }

        $totalRegistrados = $estudiantesProcesados->filter(function($estudiante) {
            return $estudiante['tipo_asistencia_id'] !== null;
        })->count();

        $totalEstudiantes = $estudiantesProcesados->count();
        $porcentajeRegistrados = $totalEstudiantes > 0 ? round(($totalRegistrados / $totalEstudiantes) * 100, 1) : 0;

        $tiposAsistenciaFormateados = $tiposAsistencia->map(function ($tipo) {
            return [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'color_hex' => $tipo->color_hex ?? '#6B7280',
            ];
        });

        $existenRegistros = Asistencia::where('grado_id', $grado->id)
            ->whereDate('fecha', $fechaFormateada)
            ->where('periodo_id', $periodoFecha->id)
            ->exists();

        if ($existenRegistros && !$bimestreActual) {
            $primerRegistro = Asistencia::where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaFormateada)
                ->where('periodo_id', $periodoFecha->id)
                ->first();
            if ($primerRegistro && $primerRegistro->periodobimestre_id) {
                $bimestreActual = Periodobimestre::find($primerRegistro->periodobimestre_id);
            }
        }

        // Procesar bimestres para selector
        $bimestresPeriodoProcesados = $bimestresPeriodo->map(function ($bimestre) {
            return [
                'id' => $bimestre->id,
                'nombre' => $bimestre->bimestre,
                'fecha_inicio' => $bimestre->fecha_inicio,
                'fecha_fin' => $bimestre->fecha_fin,
                'fecha_inicio_formateada' => Carbon::parse($bimestre->fecha_inicio)->format('d/m/Y'),
                'fecha_fin_formateada' => Carbon::parse($bimestre->fecha_fin)->format('d/m/Y'),
                'tipo' => $bimestre->tipo_bimestre == 'A' ? 'Académico' : 'Recuperación',
                'tipo_clase' => $bimestre->tipo_bimestre == 'A' ? 'bg-success' : 'bg-warning',
            ];
        });

        return view('asistencia.grado', [
            'grado' => $grado,
            'grado_nombre' => $grado->grado . '° ' . $grado->seccion,
            'grado_nivel' => $grado->nivel,
            'estudiantesActivos' => $estudiantesProcesados,
            'estudiantesRetirados' => $estudiantesRetiradosProcesados,
            'fechaSeleccionada' => $date,
            'fechaFormateada' => $fechaFormateada,
            'fechaLegible' => Carbon::createFromFormat('d-m-Y', $date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'tiposAsistencia' => $tiposAsistenciaFormateados,
            'existenRegistros' => $existenRegistros,
            'bimestreActual' => $bimestreActual,
            'bimestresPeriodo' => $bimestresPeriodoProcesados,
            'bimestreSeleccionadoId' => $bimestreActual ? $bimestreActual->id : null,
            'periodoActual' => $periodoFecha,
            'mesBloqueado' => $mesBloqueado,
            'cantidadBloqueados' => $cantidadBloqueados,
            // Nuevos datos de resumen
            'resumenAsistencias' => $resumenAsistencias,
            'totalRegistrados' => $totalRegistrados,
            'totalEstudiantes' => $totalEstudiantes,
            'porcentajeRegistrados' => $porcentajeRegistrados,
        ]);
    }
    public function marcarIndividual(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'tipo_asistencia_id'   => 'required|exists:tipo_asistencias,id',
            'fecha'                => 'required|date_format:d-m-Y',
            'hora'                 => 'nullable|date_format:H:i',
            'grado_id'             => 'required|exists:grados,id',
            'periodo_id'           => 'required|exists:periodos,id',
            'periodobimestre_id'   => 'required|exists:periodo_bimestres,id',
        ]);

        $fecha = Carbon::createFromFormat('d-m-Y', $request->fecha)->startOfDay();
        $hora  = $request->hora ?? now()->format('H:i');

        $periodo = Periodo::find($request->periodo_id);
        if (!$periodo) {
            return response()->json([
                'success' => false,
                'message' => 'El período especificado no existe.'
            ], 422);
        }

        // Verificar que el estudiante está matriculado
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

        if ($matricula->estado == '0') {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante está retirado en este período. No se puede registrar asistencia.'
            ], 422);
        }

        // Buscar si ya existe registro
        $asistencia = Asistencia::where([
            'estudiante_id' => $estudiante->id,
            'grado_id'      => $request->grado_id,
            'periodo_id'    => $request->periodo_id,
            'fecha'         => $fecha,
        ])->first();

        $descripcion = 'Registro Manual';
        $estadoAsistencia = '0';

        if ($asistencia) {
            $descripcionActual = $asistencia->descripcion;
            if (strpos($descripcionActual, 'Registro Manual') === false) {
                $descripcionFinal = $descripcionActual ? $descripcionActual . ' | ' . $descripcion : $descripcion;
            } else {
                $descripcionFinal = $descripcionActual;
            }

            $asistencia->update([
                'tipo_asistencia_id'   => $request->tipo_asistencia_id,
                'hora'                 => $hora,
                'periodobimestre_id'   => $request->periodobimestre_id,
                'registrador_id'       => Auth::id(),
                'descripcion'          => $descripcionFinal,
                'estado'               => $estadoAsistencia,
            ]);
        } else {
            $asistencia = Asistencia::create([
                'estudiante_id'        => $estudiante->id,
                'grado_id'             => $request->grado_id,
                'periodo_id'           => $request->periodo_id,
                'periodobimestre_id'   => $request->periodobimestre_id,
                'tipo_asistencia_id'   => $request->tipo_asistencia_id,
                'fecha'                => $fecha,
                'hora'                 => $hora,
                'registrador_id'       => Auth::id(),
                'descripcion'          => $descripcion,
                'estado'               => $estadoAsistencia,
            ]);
        }

        $asistencia->load('tipoasistencia', 'periodo', 'periodobimestre');

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
                'periodobimestre_id' => $asistencia->periodobimestre_id,
                'periodobimestre_nombre' => $asistencia->periodobimestre->bimestre ?? '—',
                'descripcion'        => $asistencia->descripcion,
                'estado'             => $asistencia->estado == '0' ? 'Registro Manual' : 'Registrado',
                'estado_codigo'      => $asistencia->estado,
            ]
        ]);
    }
    public function guardarMultiple(Request $request, Grado $grado, string $fecha)
    {
        $request->validate([
            'periodobimestre_id'        => 'required|exists:periodo_bimestres,id',
            'periodo_id'                => 'required|exists:periodos,id',
            'asistencias'               => 'required|array',
            'asistencias.*'             => 'nullable|exists:tipo_asistencias,id',
            'horas'                     => 'required|array',
            'horas.*'                   => 'nullable|date_format:H:i',
        ]);

        $fechaCarbon = Carbon::parse($fecha)->startOfDay();
        $periodobimestreId = $request->periodobimestre_id;
        $periodoId         = $request->periodo_id;
        $userId            = Auth::id();

        $periodo = Periodo::find($periodoId);
        if (!$periodo) {
            return back()->with('error', 'El período especificado no existe.');
        }

        $procesadas    = 0;
        $creadas       = 0;
        $actualizadas  = 0;
        $omitidas      = 0;
        $noMatriculados = 0;
        $retirados     = 0;

        DB::beginTransaction();

        try {
            foreach ($request->asistencias as $estudianteId => $tipoAsistenciaId) {
                if (empty($tipoAsistenciaId)) {
                    $asistenciaExistente = Asistencia::where([
                        'estudiante_id' => $estudianteId,
                        'grado_id'      => $grado->id,
                        'periodo_id'    => $periodoId,
                        'fecha'         => $fechaCarbon,
                    ])->first();

                    if ($asistenciaExistente) {
                        $asistenciaExistente->delete();
                        $actualizadas++;
                    }
                    $omitidas++;
                    continue;
                }

                $hora = $request->horas[$estudianteId] ?? now()->format('H:i');

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

                $asistencia = Asistencia::updateOrCreate(
                    [
                        'estudiante_id' => $estudianteId,
                        'grado_id'      => $grado->id,
                        'periodo_id'    => $periodoId,
                        'fecha'         => $fechaCarbon,
                    ],
                    [
                        'tipo_asistencia_id'   => $tipoAsistenciaId,
                        'hora'                 => $hora,
                        'periodobimestre_id'   => $periodobimestreId,
                        'registrador_id'       => $userId,
                        'estado'               => '0',
                    ]
                );

                if ($asistencia->wasRecentlyCreated) {
                    $asistencia->update(['descripcion' => 'Registro múltiple']);
                    $creadas++;
                } else {
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

        $mensaje = "Procesadas $procesadas asistencias: $creadas nuevas, $actualizadas actualizadas.";

        if ($omitidas > 0) $mensaje .= " $omitidas omitidas.";
        if ($noMatriculados > 0) $mensaje .= " $noMatriculados no matriculados.";
        if ($retirados > 0) $mensaje .= " $retirados retirados.";

        $fechaDMY = $fechaCarbon->format('d-m-Y');
        $gradoSeccion = $grado->grado . $grado->seccion;
        $nivel = strtolower($grado->nivel);

        // URL LIMPIA - SIN PARÁMETROS
        return redirect()->route('asistencia.grado', [
            'grado_grado_seccion' => $gradoSeccion,
            'grado_nivel'         => $nivel,
            'date'                => $fechaDMY,
        ])->with('success', $mensaje);
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
    /*
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
    }*/


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

        // Obtener bimestres para el filtro (si hay período seleccionado)
        $bimestres = collect();
        if ($request->has('periodo_id') && $request->periodo_id) {
            $bimestres = Periodobimestre::where('periodo_id', $request->periodo_id)
                ->orderBy('fecha_inicio', 'asc')
                ->get(['id', 'bimestre', 'fecha_inicio', 'fecha_fin', 'tipo_bimestre']);
        }

        // Si se envió el formulario, procesar los resultados
        if ($request->has('grado_id')) {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'periodo_id' => 'required|exists:periodos,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
            ]);

            $query = Asistencia::with(['estudiante.user', 'grado', 'tipoasistencia', 'periodobimestre', 'periodo'])
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->where('periodo_id', $request->periodo_id);

            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            if ($request->filled('estudiante_id')) {
                $query->where('estudiante_id', $request->estudiante_id);
            }

            // Cambiado: usar periodobimestre_id en lugar de bimestre
            if ($request->filled('periodobimestre_id')) {
                $query->where('periodobimestre_id', $request->periodobimestre_id);
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
                // Estadísticas por tipo de asistencia
                $porTipoAsistencia = [];
                foreach ($tiposAsistencia as $tipo) {
                    $porTipoAsistencia[$tipo->id] = [
                        'nombre' => $tipo->nombre,
                        'color_hex' => $tipo->color_hex ?? '#6B7280',
                        'count' => $asistencias->where('tipo_asistencia_id', $tipo->id)->count()
                    ];
                }

                // Estadísticas por bimestre (usando periodobimestre)
                $porBimestre = [];
                foreach ($bimestres as $bimestre) {
                    $porBimestre[$bimestre->id] = [
                        'nombre' => $bimestre->bimestre,
                        'fecha_inicio' => $bimestre->fecha_inicio,
                        'fecha_fin' => $bimestre->fecha_fin,
                        'count' => $asistencias->where('periodobimestre_id', $bimestre->id)->count()
                    ];
                }

                $estadisticas = [
                    'total_asistencias' => $asistencias->count(),
                    'por_tipo_asistencia' => $porTipoAsistencia,
                    'por_bimestre' => $porBimestre,
                    'fecha_primera' => $asistencias->min('fecha'),
                    'fecha_ultima' => $asistencias->max('fecha'),
                    'total_estudiantes' => $asistencias->unique('estudiante_id')->count(),
                ];
            }

            return view('asistencia.reporte', compact(
                'periodos',
                'grados',
                'tiposAsistencia',
                'bimestres',
                'asistencias',
                'estadisticas'
            ));
        }

        return view('asistencia.reporte', [
            'periodos' => $periodos,
            'grados' => $grados,
            'tiposAsistencia' => $tiposAsistencia,
            'bimestres' => $bimestres,
            'asistencias' => collect(),
            'estadisticas' => null
        ]);
    }
    public function estudiantesPorGrado(Request $request)
    {
        $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'periodo_id' => 'required|exists:periodos,id'
        ]);

        $gradoId = $request->get('grado_id');
        $periodoId = $request->get('periodo_id');

        $estudiantes = Matricula::where('grado_id', $gradoId)
            ->where('periodo_id', $periodoId)
            ->where('estado', '1')
            ->with(['estudiante.user'])
            ->get()
            ->map(function($matricula) {
                $estudiante = $matricula->estudiante;
                if (!$estudiante || !$estudiante->user) {
                    return null;
                }
                $apellidos = trim($estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno);
                $nombres = $estudiante->user->nombre;
                return [
                    'id' => $estudiante->id,
                    'nombres_completos' => $apellidos . ', ' . $nombres,
                    'matricula_estado' => $matricula->estado
                ];
            })
            ->filter()
            ->sortBy('nombres_completos')
            ->values();

        return response()->json($estudiantes);
    }
}
