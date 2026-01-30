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
        // Obtener el año seleccionado (o el actual por defecto)
        $selectedYear = $request->input('year', now()->year);

        // Obtener años distintos que tienen registros de asistencia
        $yearsWithAttendance = Asistencia::select(DB::raw('YEAR(fecha) as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Si el año seleccionado tiene registros, mostrar todos los grados (sin filtrar por estado)
        if ($yearsWithAttendance->contains($selectedYear)) {
            $grados = Grado::withCount(['asistencias' => function($query) use ($selectedYear) {
                    $query->whereYear('fecha', $selectedYear);
                }])
                ->orderBy('nivel')
                ->orderBy('grado')
                ->orderBy('seccion')
                ->get();
        } else {
            // Si no hay registros para el año, mostrar solo grados activos
            $grados = Grado::where('estado', 1)
                ->orderBy('nivel')
                ->orderBy('grado')
                ->orderBy('seccion')
                ->get();
        }

        return view('asistencia.index', [
            'grados' => $grados,
            'currentYear' => $selectedYear,
            'availableYears' => $yearsWithAttendance
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
            'asistencias'               => 'required|array',
            'asistencias.*'             => 'nullable|exists:tipo_asistencias,id',
            'horas'                     => 'required|array',
            'horas.*'                   => 'nullable|date_format:H:i',
        ]);

        $fechaCarbon = Carbon::parse($fecha)->startOfDay();
        $bimestre    = $request->bimestre;
        $userId      = Auth::id();

        $procesadas  = 0;
        $creadas     = 0;
        $actualizadas = 0;
        $omitidas    = 0;

        foreach ($request->asistencias as $estudianteId => $tipoAsistenciaId) {
            // Si no se seleccionó nada → omitir
            if (empty($tipoAsistenciaId)) {
                $omitidas++;
                continue;
            }

            $hora = $request->horas[$estudianteId] ?? now()->format('H:i');

            $estudiante = Estudiante::find($estudianteId);
            if (!$estudiante || $estudiante->grado_id != $grado->id) {
                $omitidas++;
                continue;
            }

            // Buscar si ya existe
            $asistencia = Asistencia::where([
                'estudiante_id' => $estudianteId,
                'grado_id'      => $grado->id,
                'fecha'         => $fechaCarbon,
            ])->first();

            if ($asistencia) {
                // Actualizar
                $asistencia->update([
                    'tipo_asistencia_id' => $tipoAsistenciaId,
                    'hora'               => $hora,
                    'registrador_id'     => $userId,
                    'bimestre'           => $bimestre,
                    // 'descripcion'     => ... si lo agregas después
                ]);
                $actualizadas++;
            } else {
                // Crear
                Asistencia::create([
                    'estudiante_id'      => $estudianteId,
                    'grado_id'           => $grado->id,
                    'bimestre'           => $bimestre,
                    'tipo_asistencia_id' => $tipoAsistenciaId,
                    'fecha'              => $fechaCarbon,
                    'hora'               => $hora,
                    'registrador_id'     => $userId,
                    'descripcion'        => null,
                ]);
                $creadas++;
            }

            $procesadas++;
        }

        $fechaDMY = $fechaCarbon->format('d-m-Y');
        $gradoSeccion = $grado->grado . $grado->seccion;
        $nivel = strtolower($grado->nivel);

        return redirect()->route('asistencia.grado', [
            'grado_grado_seccion' => $gradoSeccion,
            'grado_nivel'         => $nivel,
            'date'                => $fechaDMY,
        ])->with('success', "Procesadas $procesadas asistencias: $creadas nuevas, $actualizadas actualizadas, $omitidas omitidas.");
    }
    /*
    public function registrarMultiple(Request $request, Grado $grado, string $fecha)
    {
        $request->validate([
            'bimestre'                  => 'required|integer|between:1,4',
            'asistencias'               => 'required|array',
            'asistencias.*'             => 'nullable|exists:tipo_asistencias,id',
            'horas'                     => 'required|array',
            'horas.*'                   => 'nullable|date_format:H:i',
        ]);

        $fechaCarbon = Carbon::parse($fecha)->startOfDay();
        $bimestre    = $request->bimestre;
        $userId      = Auth::id();

        $creadas = 0;
        $omitidas = 0;

        foreach ($request->asistencias as $estudianteId => $tipoAsistenciaId) {
            // Saltar si no se seleccionó asistencia
            if (!$tipoAsistenciaId) {
                $omitidas++;
                continue;
            }

            $hora = $request->horas[$estudianteId] ?? now()->format('H:i');

            // Verificar que el estudiante existe y pertenece al grado
            $estudiante = Estudiante::find($estudianteId);
            if (!$estudiante || $estudiante->grado_id != $grado->id) {
                $omitidas++;
                continue;
            }

            // Verificar que NO exista ya un registro (seguridad extra)
            $existe = Asistencia::where([
                'estudiante_id' => $estudianteId,
                'grado_id'      => $grado->id,
                'fecha'         => $fechaCarbon,
            ])->exists();

            if ($existe) {
                // Opcional: podrías loguear o ignorar silenciosamente
                $omitidas++;
                continue;
            }

            // Crear el nuevo registro
            Asistencia::create([
                'estudiante_id'      => $estudianteId,
                'grado_id'           => $grado->id,
                'bimestre'           => $bimestre,
                'tipo_asistencia_id' => $tipoAsistenciaId,
                'fecha'              => $fechaCarbon,
                'hora'               => $hora,
                'registrador_id'     => $userId,
                'descripcion'        => null, // o $request->input("descripcion.$estudianteId") si lo agregas después
            ]);

            $creadas++;
        }

        // Redirigir a la misma vista con mensaje de éxito
        $fechaDMY = $fechaCarbon->format('d-m-Y');
        $gradoSeccion = $grado->grado . $grado->seccion;
        $nivel = strtolower($grado->nivel);

        return redirect()->route('asistencia.grado', [
            'grado_grado_seccion' => $gradoSeccion,
            'grado_nivel'         => $nivel,
            'date'                => $fechaDMY,
        ])->with('success', "Se registraron $creadas asistencias nuevas. Omitidas: $omitidas.");
    }*/
    public function marcarIndividual(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'tipo_asistencia_id' => 'required|exists:tipo_asistencias,id',
            'fecha'              => 'required|date_format:d-m-Y',
            'hora'               => 'nullable|date_format:H:i',
            'grado_id'           => 'required|exists:grados,id',
            'bimestre'           => 'required|integer|between:1,4',
        ]);

        $fecha = Carbon::createFromFormat('d-m-Y', $request->fecha)->startOfDay();
        $hora  = $request->hora ?? now()->format('H:i');

        // Verificar que el estudiante pertenece al grado seleccionado
        if ($estudiante->grado_id !== (int) $request->grado_id) {
            return response()->json([
                'success' => false,
                'message' => 'El estudiante no pertenece a este grado.'
            ], 422);
        }

        // Buscar si ya existe registro para ese estudiante, fecha y grado
        $asistencia = Asistencia::where([
            'estudiante_id'      => $estudiante->id,
            'grado_id'           => $request->grado_id,
            'fecha'              => $fecha,
        ])->first();

        if ($asistencia) {
            // Actualizar
            $asistencia->update([
                'tipo_asistencia_id' => $request->tipo_asistencia_id,
                'hora'               => $hora,
                'registrador_id'     => Auth::id(),
                'descripcion'        => $request->descripcion ?? null,
            ]);
        } else {
            // Crear nuevo
            $asistencia = Asistencia::create([
                'estudiante_id'      => $estudiante->id,
                'grado_id'           => $request->grado_id,
                'bimestre'           => $request->bimestre,
                'tipo_asistencia_id' => $request->tipo_asistencia_id,
                'fecha'              => $fecha,
                'hora'               => $hora,
                'registrador_id'     => Auth::id(),
                'descripcion'        => null,
            ]);
        }

        // Cargar relación para devolver datos útiles
        $asistencia->load('tipoasistencia');

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada correctamente',
            'asistencia' => [
                'tipo_asistencia_id' => $asistencia->tipo_asistencia_id,
                'nombre_tipo'        => $asistencia->tipoasistencia->nombre ?? '—',
                'color'              => $asistencia->tipoasistencia->color ?? '#6B7280',
                'hora'               => substr($asistencia->hora, 0, 5),
                'estado'             => 'Registrado',
            ]
        ]);
    }
    /*
    public function actualizarMultiple(Request $request, Grado $grado, string $fecha)
    {
        $request->validate([
            'bimestre'                  => 'required|integer|between:1,4',
            'asistencias'               => 'required|array',
            'asistencias.*'             => 'nullable|exists:tipo_asistencias,id',
            'horas'                     => 'required|array',
            'horas.*'                   => 'nullable|date_format:H:i',
        ]);

        $fechaCarbon = Carbon::createFromFormat('d-m-Y', $fecha)->startOfDay();
        $bimestre    = $request->bimestre;
        $userId      = Auth::id();

        $actualizadas = 0;
        $creadas      = 0;

        foreach ($request->asistencias as $estudianteId => $tipoAsistenciaId) {
            if (!$tipoAsistenciaId) {
                continue;
            }

            $hora = $request->horas[$estudianteId] ?? now()->format('H:i');

            $estudiante = Estudiante::find($estudianteId);
            if (!$estudiante || $estudiante->grado_id != $grado->id) {
                continue;
            }

            $asistencia = Asistencia::where([
                'estudiante_id' => $estudianteId,
                'grado_id'      => $grado->id,
                'fecha'         => $fechaCarbon,
            ])->first();

            if ($asistencia) {
                $asistencia->update([
                    'tipo_asistencia_id' => $tipoAsistenciaId,
                    'hora'               => $hora,
                    'registrador_id'     => $userId,
                    'bimestre'           => $bimestre,
                ]);
                $actualizadas++;
            } else {
                Asistencia::create([
                    'estudiante_id'      => $estudianteId,
                    'grado_id'           => $grado->id,
                    'bimestre'           => $bimestre,
                    'tipo_asistencia_id' => $tipoAsistenciaId,
                    'fecha'              => $fechaCarbon,
                    'hora'               => $hora,
                    'registrador_id'     => $userId,
                ]);
                $creadas++;
            }
        }

        // ← Cambio importante aquí
        $fechaDMY = $fechaCarbon->format('d-m-Y');           // 30-12-2025
        $gradoSeccion = $grado->grado . $grado->seccion;     // ej: 1A
        $nivel = strtolower($grado->nivel);                  // ej: secundaria

        return redirect()->route('asistencia.grado', [
            'grado_grado_seccion' => $gradoSeccion,
            'grado_nivel'         => $nivel,
            'date'                => $fechaDMY,
        ])->with('success', "Se actualizaron $actualizadas y crearon $creadas registros de asistencia.");
    }*/

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
        $fecha_seleccionada = $request->input('fecha'); // Formato esperado: 'Y-m-d'
        $bimestre_seleccionado = $request->input('bimestre');

        // Validar que se haya proporcionado bimestre
        if (!$bimestre_seleccionado) {
            return response()->json([
                'success' => false,
                'error' => 'Debe seleccionar un bimestre'
            ], 400);
        }

        // Obtener grados activos
        $gradosActivos = Grado::where('estado', '1')->pluck('id')->toArray();

        // Obtener estudiantes activos
        $estudiantesActivos = Estudiante::whereIn('grado_id', $gradosActivos)
            ->where('estado', '1')
            ->with(['asistencias' => function($query) use ($fecha_seleccionada) {
                $query->whereDate('fecha', $fecha_seleccionada);
            }])
            ->get();

        DB::beginTransaction();
        try {
            $registrosCreados = 0;
            $registrosOmitidos = 0;
            $estudiantesSinRegistro = [];

            foreach ($estudiantesActivos as $estudiante) {
                // Verificar si ya tiene registro en esta fecha
                $tieneRegistro = $estudiante->asistencias->isNotEmpty();

                if ($tieneRegistro) {
                    // Si ya tiene registro, OMITIR (no hacer nada)
                    $registrosOmitidos++;
                    continue;
                }

                // Solo crear registro para estudiantes SIN registro previo
                Asistencia::create([
                    'estudiante_id' => $estudiante->id,
                    'grado_id' => $estudiante->grado_id,
                    'bimestre' => $bimestre_seleccionado,
                    'tipo_asistencia_id' => 5, // Puntualidad
                    'fecha' => $fecha_seleccionada,
                    'hora' => now()->format('H:i:s'),
                    'registrador_id' => auth()->id(),
                    'descripcion' => 'Registro automático de Puntualidad'
                ]);
                $registrosCreados++;

                $estudiantesSinRegistro[] = [
                    'id' => $estudiante->id,
                    'nombre' => $estudiante->user->nombre ?? 'N/A'
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'fecha' => $fecha_seleccionada,
                'bimestre' => $bimestre_seleccionado,
                'total_estudiantes_activos' => $estudiantesActivos->count(),
                'estudiantes_con_registro' => $registrosOmitidos,
                'registros_creados' => $registrosCreados,
                'estudiantes_afectados' => $estudiantesSinRegistro,
                'mensaje' => $registrosCreados > 0
                    ? "Se marcaron {$registrosCreados} estudiantes como puntuales. Se omitieron {$registrosOmitidos} que ya tenían registro."
                    : "No se crearon nuevos registros. Todos los estudiantes ya tenían asistencia registrada."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar las asistencias: ' . $e->getMessage(),
                'fecha' => $fecha_seleccionada,
                'bimestre' => $bimestre_seleccionado
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
