<?php
namespace App\Http\Controllers;

use App\Models\Asistencia\Asistencia;
use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
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

        $availableYears = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->unique()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        return view('asistencia.index', [
            'grados' => $grados,
            'currentYear' => $currentYear,
            'availableYears' => $availableYears,
            'periodoActual' => $periodoActual,
            'fechaHoy' => $hoy,
        ]);
    }

    public function showDate($grado_grado_seccion, $grado_nivel, $date)
    {
        try {
            $fechaFormateada = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
            $anioFecha = Carbon::createFromFormat('d-m-Y', $date)->year;
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
                ->where('estado', '1'); // Activo
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
                ->where('estado', '0'); // Retirado
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
            'existenRegistros'                 => $existenRegistros,
            'bimestreActual'                   => $bimestreActual,
            'periodoActual'                    => $periodoFecha,
            'anioFecha'                        => $anioFecha,
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
                        'estado'             => '1',
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
        $request->validate([
            'fecha' => 'required|date',
            'bimestre' => 'required|integer|between:1,4',
        ]);

        $fecha = Carbon::parse($request->fecha)->startOfDay();
        $anioFecha = $fecha->year;
        $bimestre = $request->bimestre;

        // Obtener período basado en el año de la fecha
        $periodo = Periodo::where('anio', $anioFecha)
            ->where('estado', '1')
            ->first();

        if (!$periodo) {
            return response()->json([
                'success' => false,
                'error' => 'No hay un período activo para el año ' . $anioFecha
            ], 422);
        }

        // Obtener todos los grados activos
        $gradosActivos = Grado::where('estado', '1')->get();

        DB::beginTransaction();

        try {
            $registrosCreados = 0;
            $registrosOmitidos = 0;
            $noMatriculados = 0;
            $retirados = 0;
            $estudiantesAfectados = [];

            foreach ($gradosActivos as $grado) {
                // Obtener estudiantes matriculados ACTIVOS en este grado para el período
                $matriculasActivas = Matricula::with(['estudiante'])
                    ->where('grado_id', $grado->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('estado', '1') // Solo matriculados activos
                    ->get();

                foreach ($matriculasActivas as $matricula) {
                    $estudiante = $matricula->estudiante;

                    if (!$estudiante) {
                        continue; // Si no hay estudiante asociado
                    }

                    // Verificar si ya existe asistencia para esta fecha, grado y período
                    $existe = Asistencia::where([
                        'estudiante_id' => $estudiante->id,
                        'grado_id' => $grado->id,
                        'periodo_id' => $periodo->id,
                        'fecha' => $fecha,
                    ])->exists();

                    if ($existe) {
                        $registrosOmitidos++;
                        continue; // Ya existe registro
                    }

                    // Crear nuevo registro
                    Asistencia::create([
                        'estudiante_id'      => $estudiante->id,
                        'grado_id'           => $grado->id,
                        'periodo_id'         => $periodo->id,
                        'bimestre'           => $bimestre,
                        'tipo_asistencia_id' => 5, // ID para "Puntual"
                        'fecha'              => $fecha,
                        'hora'               => now()->toTimeString(),
                        'registrador_id'     => auth()->id(),
                        'descripcion'        => 'Registro automático masivo',
                        'estado'             => '1',
                    ]);

                    $registrosCreados++;

                    // Agregar a la lista de afectados
                    $estudiantesAfectados[] = [
                        'estudiante_id' => $estudiante->id,
                        'grado_id' => $grado->id,
                        'grado' => $grado->grado . '° ' . $grado->seccion,
                        'nivel' => $grado->nivel,
                    ];
                }

                // Contar estudiantes retirados para información
                $retirados += Matricula::where('grado_id', $grado->id)
                    ->where('periodo_id', $periodo->id)
                    ->where('estado', '0') // Retirados
                    ->count();
            }

            // Contar estudiantes no matriculados en ningún grado del período
            $totalEstudiantes = Estudiante::count();
            $matriculadosEnPeriodo = Matricula::where('periodo_id', $periodo->id)->count();
            $noMatriculados = $totalEstudiantes - $matriculadosEnPeriodo;

            DB::commit();

            return response()->json([
                'success' => true,
                'total_afectados' => $registrosCreados,
                'fecha' => $fecha->format('Y-m-d'),
                'bimestre' => $bimestre,
                'periodo' => [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'anio' => $periodo->anio,
                ],
                'resumen' => [
                    'registros_creados' => $registrosCreados,
                    'registros_omitidos' => $registrosOmitidos,
                    'estudiantes_retirados' => $retirados,
                    'estudiantes_no_matriculados' => $noMatriculados,
                    'grados_activos_procesados' => $gradosActivos->count(),
                ],
                'estudiantes_afectados' => $estudiantesAfectados,
                'mensaje' => $registrosCreados > 0
                    ? "Se marcaron {$registrosCreados} estudiantes como puntuales en {$gradosActivos->count()} grados activos."
                    : "No se crearon nuevos registros. Todos los estudiantes matriculados ya tenían asistencia registrada para esta fecha."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar: ' . $e->getMessage()
            ], 500);
        }
    }
    public function reporteAsistencia(Request $request)
    {
        $grados = Grado::where('estado', 1)
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->get();

        // Cargar tipos de asistencia sin el filtro de estado
        $tiposAsistencia = Tipoasistencia::all();

        // Si se envió el formulario, procesar los resultados
        if ($request->has('grado_id')) {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
            ]);

            $query = Asistencia::with(['estudiante.user', 'grado', 'tipoasistencia', 'bimestre'])
                ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

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

            return view('asistencia.reporte', compact('grados', 'tiposAsistencia', 'asistencias'));
        }

        return view('asistencia.reporte', [
            'grados' => $grados,
            'tiposAsistencia' => $tiposAsistencia,
            'asistencias' => collect() // Colección vacía para evitar errores
        ]);
    }
    public function estudiantesPorGrado(Request $request)
    {
        $gradoId = $request->get('grado_id');

        $estudiantes = Estudiante::where('grado_id', $gradoId)
            ->where('estado', 1)
            ->with('user')
            ->get()
            ->map(function($estudiante) {
                // Formato: Apellidos, Nombres
                $apellidos = trim($estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno);
                $nombres = $estudiante->user->nombre;

                return [
                    'id' => $estudiante->id,
                    'nombres_completos' => $apellidos . ', ' . $nombres
                ];
            });

        return response()->json($estudiantes);
    }
}
