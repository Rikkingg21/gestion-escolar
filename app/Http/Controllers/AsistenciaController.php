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
use App\Models\Asistencia\Tipoasistencia;
use App\Models\Grado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{


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
            $fechaFormateada = \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            abort(400, 'Formato de fecha inválido. Use dd-mm-yyyy');
        }

        // Extraer grado y sección
        if (!preg_match('/^(\d+)([a-zA-Z]+)$/', $grado_grado_seccion, $matches)) {
            abort(400, 'Formato de grado/sección inválido. Ejemplo: 1a, 2b');
        }

        $gradoNumero = $matches[1];
        $gradoSeccion = $matches[2];

        $grado = Grado::where('grado', $gradoNumero)
                ->where('seccion', $gradoSeccion)
                ->where('nivel', $grado_nivel)
                ->firstOrFail();

        // Verificar si hay registros para esta fecha
        $existenRegistros = Asistencia::where('grado_id', $grado->id)
                            ->whereDate('fecha', $fechaFormateada)
                            ->exists();

        // Obtener estudiantes según si existen registros o no
        if ($existenRegistros) {
            // Si hay registros, obtener estudiantes con asistencia en esa fecha (activos e inactivos)
            $estudiantes = Estudiante::with(['user', 'asistencias' => function($query) use ($fechaFormateada) {
                    $query->whereDate('fecha', $fechaFormateada);
                }])
                ->where('grado_id', $grado->id)
                ->whereHas('asistencias', function($query) use ($fechaFormateada) {
                    $query->whereDate('fecha', $fechaFormateada);
                })
                ->get();
        } else {
            // Si no hay registros, obtener solo estudiantes activos
            $estudiantes = Estudiante::with(['user'])
                ->where('grado_id', $grado->id)
                ->where('estado', 1)
                ->get();
        }

        // Ordenar estudiantes por apellidos y nombre
        $estudiantes = $estudiantes->sortBy(function($estudiante) {
            return optional($estudiante->user)->apellido_paterno .
                optional($estudiante->user)->apellido_materno .
                optional($estudiante->user)->nombre;
        });

        $tiposAsistencia = Tipoasistencia::all();

        $bimestreActual = null;
        if ($existenRegistros) {
            $registroEjemplo = Asistencia::where('grado_id', $grado->id)
                ->whereDate('fecha', $fechaFormateada)
                ->first();
            $bimestreActual = $registroEjemplo ? $registroEjemplo->bimestre : null;
        }

        return view('asistencia.grado', [
            'grado' => $grado,
            'estudiantes' => $estudiantes,
            'fechaSeleccionada' => $date,
            'fechaFormateada' => $fechaFormateada,
            'tiposAsistencia' => $tiposAsistencia,
            'existenRegistros' => $existenRegistros,
            'bimestreActual' => $bimestreActual
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
}
