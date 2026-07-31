<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use App\Models\Materia\Recuperacioncompetencia;
use App\Models\Matricula;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Nota;
use App\Models\Periodo;
use App\Services\EvaluacionEstudianteService;
use App\Services\ProcesarnotasCompetenciaService;
use App\Services\ProcesarnotasCriterioService;
use App\Services\ProcesarnotasMateriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GradoController extends Controller
{
    protected $criterioService;

    protected $competenciaService;

    protected $materiaService;

    protected $evaluacionService;

    // moduleID 10 = Grados
    public function __construct(
        ProcesarnotasCriterioService $criterioService,
        ProcesarnotasCompetenciaService $competenciaService,
        ProcesarnotasMateriaService $materiaService,
        EvaluacionEstudianteService $evaluacionService,
    ) {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('10')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
        $this->criterioService = $criterioService;
        $this->competenciaService = $competenciaService;
        $this->materiaService = $materiaService;
        $this->evaluacionService = $evaluacionService;
    }

    public function index()
    {
        $gradosActivos = Grado::where('estado', '1')
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->paginate(5, ['*'], 'activos');

        $gradosInactivos = Grado::where('estado', '0')
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->paginate(5, ['*'], 'inactivos');

        return view('grado.index', compact('gradosActivos', 'gradosInactivos'));
    }

    public function create()
    {
        $grados = Grado::orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->get();

        return view('grado.create', compact('grados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grado' => 'required|integer',
            'seccion' => 'required|string|max:1',
            'nivel' => 'required|string|max:255',
            'estado' => 'required|in:1,0',
        ]);

        $data = $request->all();
        $data['seccion'] = strtoupper($data['seccion']);

        Grado::create($data);

        return redirect()->route('grado.index')->with('success', 'Grado creado exitosamente.');
    }

    public function edit($id)
    {
        $grado = Grado::findOrFail($id);
        $grados = Grado::orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->get();

        return view('grado.edit', compact('grado', 'grados'));
    }

    public function update(Request $request, Grado $grado)
    {
        $request->validate([
            'grado' => 'required|integer',
            'seccion' => 'required|string|max:1',
            'nivel' => 'required|string|max:255',
            'estado' => 'required|in:1,0',
        ]);

        $data = $request->all();
        $data['seccion'] = strtoupper($data['seccion']);

        $grado->update($data);

        return redirect()->route('grado.index')->with('success', 'Grado actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $grado = Grado::findOrFail($id);

        // Verificar si el grado está activo
        if ($grado->estado == '1') {
            return redirect()->route('grado.index')->with('error', 'No se puede eliminar el grado porque está activo.');
        }

        $grado->delete();

        return redirect()->route('grado.index')->with('success', 'Grado eliminado correctamente.');
    }

    private function getDatosBase($gradoId)
    {
        $grado = Grado::findOrFail($gradoId);

        $aniosDisponibles = Periodo::where('estado', '1')
            ->select('anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');

        $anioSeleccionado = request()->get('anio', $aniosDisponibles->first());

        $periodoAcademico = Periodo::where('anio', $anioSeleccionado)
            ->where('tipo_periodo', 'año escolar')->where('estado', '1')->first();

        if (! $periodoAcademico) {
            return redirect()->back()->with('error', "No hay período académico para el año $anioSeleccionado");
        }

        $periodoRecuperacion = Periodo::where('anio', $anioSeleccionado)
            ->where('tipo_periodo', 'recuperación')->where('estado', '1')->first();

        return compact('grado', 'aniosDisponibles', 'anioSeleccionado', 'periodoAcademico', 'periodoRecuperacion');
    }

    public function getDatosEstudiante($estudianteId, Request $request)
    {
        try {
            $periodoAcademicoId = $request->periodo_academico_id;
            $periodoRecuperacionId = $request->periodo_recuperacion_id;

            $estudiante = Estudiante::with(['user'])->findOrFail($estudianteId);

            // Obtener datos actualizados (reutiliza tu lógica existente)
            $periodoAcademico = Periodo::find($periodoAcademicoId);
            $periodoRecuperacion = $periodoRecuperacionId ? Periodo::find($periodoRecuperacionId) : null;

            // Aquí llamas a tus servicios para obtener los datos actualizados
            // Similar a lo que haces en el método estudiantes()

            return response()->json([
                'success' => true,
                'estado_final' => $estudiante->estado_final,
                'materias_aprobadas' => $estudiante->materias_aprobadas,
                'total_materias' => $estudiante->total_materias,
                'materias_desaprobadas' => $estudiante->materias_desaprobadas_count,
                'total_competencias_recuperar' => $estudiante->total_competencias_recuperar,
                'porcentaje_aprobacion' => $estudiante->porcentaje_aprobacion,
                'competencias_aprobadas' => $estudiante->competencias_aprobadas,
                'total_competencias' => $estudiante->total_competencias,
                'competencias_pendientes' => $estudiante->competencias_pendientes,
                'competencias_pendientes_calificar' => $estudiante->competencias_pendientes_calificar,
                'competencias' => $this->getCompetenciasDetalle($estudiante, $periodoAcademico, $periodoRecuperacion),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Obtener materias del grado
    private function getMateriasGrado($periodoAcademico, $gradoId)
    {
        $materiasAsignadas = Cursogradosecnivanio::where('periodo_id', $periodoAcademico->id)
            ->where('grado_id', $gradoId)->with(['materia'])->get();

        $materiasArray = [];
        foreach ($materiasAsignadas as $asignacion) {
            $materiasArray[$asignacion->materia_id] = $asignacion->materia->nombre;
        }

        return $materiasArray;
    }

    // Obtener competencias del grado
    private function getCompetenciasGrado($materiaIds, $gradoId, $periodoAcademico)
    {
        $competenciasQuery = Materiacompetencia::whereIn('materia_id', $materiaIds)
            ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%transversal%'])
            ->whereHas('materiaCriterio', function ($query) use ($gradoId, $periodoAcademico) {
                $query->where('grado_id', $gradoId)
                    ->whereHas('periodoBimestre', function ($q) use ($periodoAcademico) {
                        $q->where('periodo_id', $periodoAcademico->id);
                    });
            })->get();

        $competenciasArray = [];
        $competenciasNombres = [];
        foreach ($competenciasQuery as $competencia) {
            $competenciasArray[$competencia->id] = $competencia->materia_id;
            $competenciasNombres[$competencia->id] = $competencia->nombre;
        }

        return [$competenciasArray, $competenciasNombres];
    }

    // Obtener recuperaciones por estudiante
    private function getRecuperacionesPorEstudiante($estudiantesIds, $competenciaIds, $periodoRecuperacion)
    {
        $recuperacionesPorEstudiante = [];

        if ($periodoRecuperacion) {
            $recuperaciones = Recuperacioncompetencia::whereIn('estudiante_id', $estudiantesIds)
                ->whereIn('materia_competencia_id', $competenciaIds)
                ->where('periodo_id', $periodoRecuperacion->id)
                ->get();

            foreach ($recuperaciones as $rec) {
                $recuperacionesPorEstudiante[$rec->estudiante_id][$rec->materia_competencia_id] = [
                    'nota' => $rec->nivel_logro_final ? $this->competenciaService->convertirEnumANota($rec->nivel_logro_final) : null,
                    'tiene_registro' => true,
                    'recuperacion_id' => $rec->id,
                    'estado' => $rec->estado ?? '0',
                ];
            }
        }

        return $recuperacionesPorEstudiante;
    }

    // Calcular estadísticas del estudiante
    private function calcularEstadisticasEstudiante($estudiante, $materias, $periodoRecuperacion)
    {
        $totalMaterias = count($materias);
        $materiasAprobadas = 0;
        $materiasDesaprobadas = 0;
        $totalComp = 0;
        $compAprobadas = 0;
        $compPendientes = 0;
        $compPendientesCalificar = 0;

        foreach ($materias as $materia) {
            if ($materia['estado'] === 'aprobado') {
                $materiasAprobadas++;
            } else {
                $materiasDesaprobadas++;
            }

            foreach ($materia['competencias'] as $competencia) {
                $totalComp++;
                if ($competencia['esta_aprobada'] ?? false) {
                    $compAprobadas++;
                }
                if (($competencia['requiere_recuperacion'] ?? false)) {
                    $compPendientes++;
                }
                if (($competencia['tiene_registro_recuperacion'] ?? false)) {
                    $compPendientesCalificar++;
                }
            }
        }

        $porcentaje = $totalComp > 0 ? round(($compAprobadas / $totalComp) * 100) : 0;

        // Estado final
        if ($totalComp === 0) {
            $estadoFinal = 'sin_evaluacion';
        } elseif ($totalComp === $compAprobadas && $compPendientesCalificar === 0) {
            $estadoFinal = 'aprobado';
        } elseif ($compPendientesCalificar > 0) {
            $estadoFinal = 'pendiente_calificar';
        } elseif ($compPendientes > 0) {
            $estadoFinal = 'recuperacion';
        } else {
            $estadoFinal = 'desaprobado';
        }

        // Competencias a recuperar
        $totalCompReqEstudiante = 0;
        foreach ($materias as $materia) {
            foreach ($materia['competencias'] as $competencia) {
                $notaOriginal = $competencia['promedio_original'];
                $tieneNotaRecuperacion = ($competencia['nota_recuperacion'] ?? null) !== null;
                $tieneRegistro = $competencia['tiene_registro_recuperacion'] ?? false;

                if ($notaOriginal < 1.5 && ! $tieneNotaRecuperacion && ! $tieneRegistro) {
                    $totalCompReqEstudiante++;
                }
            }
        }

        // Datos de recuperación
        $competenciasRecuperacionCount = 0;
        if ($periodoRecuperacion) {
            $competenciasRecuperacionCount = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
                ->where('periodo_id', $periodoRecuperacion->id)->count();
        }

        $estudiante->detalle_materias = $materias;
        $estudiante->total_materias = $totalMaterias;
        $estudiante->materias_aprobadas = $materiasAprobadas;
        $estudiante->materias_desaprobadas_count = $materiasDesaprobadas;
        $estudiante->total_competencias = $totalComp;
        $estudiante->competencias_aprobadas = $compAprobadas;
        $estudiante->competencias_pendientes = $compPendientes;
        $estudiante->competencias_pendientes_calificar = $compPendientesCalificar;
        $estudiante->porcentaje_aprobacion = $porcentaje;
        $estudiante->total_competencias_recuperar = $totalCompReqEstudiante;
        $estudiante->estado_final = $estadoFinal;
        $estudiante->competencias_recuperacion_count = $competenciasRecuperacionCount;

        return $estudiante;
    }

    // Mostrar estudiantes del grado
    public function estudiantes($id)
    {
        $datos = $this->getDatosBase($id);
        if ($datos instanceof \Illuminate\Http\RedirectResponse) {
            return $datos;
        }

        extract($datos);

        $estudiantesRegistrados = Estudiante::where('grado_id', $grado->id)
            ->where('estado', '1')->with(['user'])->get();

        $estudiantesMatriculadosIds = Matricula::where('periodo_id', $periodoAcademico->id)
            ->where('estado', '1')->pluck('estudiante_id')->toArray();

        $materiasArray = $this->getMateriasGrado($periodoAcademico, $grado->id);

        if (empty($materiasArray)) {
            return view('grado.gradoestudiantes', compact(
                'grado', 'aniosDisponibles', 'anioSeleccionado',
                'periodoAcademico', 'periodoRecuperacion',
                'estudiantesMatriculados', 'estudiantesNoMatriculados'
            ) + ['estudiantesMatriculados' => collect(), 'estudiantesNoMatriculados' => $estudiantesRegistrados]);
        }

        [$competenciasArray, $competenciasNombres] = $this->getCompetenciasGrado(array_keys($materiasArray), $grado->id, $periodoAcademico);

        if (empty($competenciasArray)) {
            return view('grado.gradoestudiantes', compact(
                'grado', 'aniosDisponibles', 'anioSeleccionado',
                'periodoAcademico', 'periodoRecuperacion'
            ) + ['estudiantesMatriculados' => collect(), 'estudiantesNoMatriculados' => $estudiantesRegistrados]);
        }

        // Criterios
        $criterios = Materiacriterio::whereIn('materia_competencia_id', array_keys($competenciasArray))
            ->where('grado_id', $grado->id)
            ->whereHas('periodoBimestre', function ($q) use ($periodoAcademico) {
                $q->where('periodo_id', $periodoAcademico->id);
            })->get();

        $criteriosArray = [];
        foreach ($criterios as $criterio) {
            $criteriosArray[$criterio->id] = [
                'competencia_id' => $criterio->materia_competencia_id,
                'materia_id' => $competenciasArray[$criterio->materia_competencia_id] ?? null,
            ];
        }

        // Notas
        $notasQuery = Nota::whereIn('estudiante_id', $estudiantesMatriculadosIds)
            ->whereIn('materia_criterio_id', array_keys($criteriosArray))
            ->where('periodo_id', $periodoAcademico->id)
            ->select('estudiante_id', 'materia_criterio_id', 'nota')
            ->get();

        $notasArray = [];
        foreach ($notasQuery as $nota) {
            if (isset($criteriosArray[$nota->materia_criterio_id])) {
                $criterioInfo = $criteriosArray[$nota->materia_criterio_id];
                $notasArray[] = [
                    'estudiante_id' => $nota->estudiante_id,
                    'materia_criterio_id' => $nota->materia_criterio_id,
                    'materia_competencia_id' => $criterioInfo['competencia_id'],
                    'materia_id' => $criterioInfo['materia_id'],
                    'nota' => $nota->nota,
                ];
            }
        }

        $recuperacionesPorEstudiante = $this->getRecuperacionesPorEstudiante($estudiantesMatriculadosIds, array_keys($competenciasArray), $periodoRecuperacion);

        // Procesar datos
        $criteriosProcesados = $this->criterioService->procesar($notasArray);
        $competenciasProcesadas = $this->competenciaService->procesar($criteriosProcesados, $recuperacionesPorEstudiante);
        $materiasProcesadas = $this->materiaService->procesar($competenciasProcesadas, $materiasArray, $competenciasNombres);

        // Resultados por estudiante
        $resultadosPorEstudiante = [];
        foreach ($materiasProcesadas as $materia) {
            $estId = $materia['estudiante_id'];
            if (! isset($resultadosPorEstudiante[$estId])) {
                $resultadosPorEstudiante[$estId] = [];
            }
            $recuperacionesInfoEstudiante = $recuperacionesPorEstudiante[$estId] ?? [];
            $materiaEnriquecida = $this->evaluacionService->enriquecerMaterias([$materia], $recuperacionesInfoEstudiante)[0];
            $resultadosPorEstudiante[$estId][] = $materiaEnriquecida;
        }

        // Construir estudiantes
        $estudiantesMatriculados = collect();
        foreach ($estudiantesRegistrados as $estudiante) {
            if (in_array($estudiante->id, $estudiantesMatriculadosIds)) {
                $materias = $resultadosPorEstudiante[$estudiante->id] ?? [];
                $estudiante = $this->calcularEstadisticasEstudiante($estudiante, $materias, $periodoRecuperacion);
                $estudiantesMatriculados->push($estudiante);
            }
        }

        $estudiantesNoMatriculados = $estudiantesRegistrados->filter(function ($e) use ($estudiantesMatriculadosIds) {
            return ! in_array($e->id, $estudiantesMatriculadosIds);
        });

        $estudiantesParaRecuperacion = $estudiantesMatriculados->filter(function ($e) {
            return $e->estado_final === 'recuperacion';
        })->count();

        return view('grado.gradoestudiantes', compact(
            'grado', 'aniosDisponibles', 'anioSeleccionado',
            'periodoAcademico', 'periodoRecuperacion',
            'estudiantesMatriculados', 'estudiantesNoMatriculados',
            'estudiantesParaRecuperacion'
        ));
    }

    // Matricular estudiantes en recuperación (masivo o individual)
    private function matricularRecuperacionBase($estudianteId, $periodoRecuperacionId, $periodoAcademicoId, $competencias)
    {
        DB::beginTransaction();

        try {
            $estudiante = Estudiante::find($estudianteId);
            if (! $estudiante) {
                return ['success' => false, 'message' => 'Estudiante no encontrado'];
            }

            $matriculaAcademica = Matricula::where('estudiante_id', $estudianteId)
                ->where('periodo_id', $periodoAcademicoId)->where('estado', '1')->first();

            if (! $matriculaAcademica) {
                return ['success' => false, 'message' => 'El estudiante no tiene matrícula en el período académico'];
            }

            // Crear matrícula en recuperación si no existe
            Matricula::firstOrCreate([
                'estudiante_id' => $estudianteId,
                'periodo_id' => $periodoRecuperacionId,
            ], [
                'grado_id' => $matriculaAcademica->grado_id,
                'estado' => '1',
            ]);

            $registradas = 0;
            $duplicadas = 0;

            foreach ($competencias as $competencia) {
                $existe = Recuperacioncompetencia::where('estudiante_id', $estudianteId)
                    ->where('materia_competencia_id', $competencia['materia_competencia_id'])
                    ->where('periodo_id', $periodoRecuperacionId)
                    ->exists();

                if (! $existe) {
                    $notaOriginal = floatval($competencia['nota_original']);
                    $valorEnum = $this->competenciaService->convertirNotaAEnum($notaOriginal);

                    Recuperacioncompetencia::create([
                        'estudiante_id' => $estudianteId,
                        'materia_competencia_id' => $competencia['materia_competencia_id'],
                        'materia_id' => $competencia['materia_id'],
                        'periodo_id' => $periodoRecuperacionId,
                        'nivel_logro_inicial' => $valorEnum,
                        'nivel_logro_final' => null,
                        'estado' => '0',
                    ]);
                    $registradas++;
                } else {
                    $duplicadas++;
                }
            }

            DB::commit();

            if ($registradas > 0) {
                return ['success' => true, 'message' => "Registradas: $registradas, duplicadas: $duplicadas"];
            }

            return ['success' => false, 'message' => "No se registraron nuevas competencias. $duplicadas ya existían"];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en matrícula recuperación: '.$e->getMessage());

            return ['success' => false, 'message' => 'Error interno: '.$e->getMessage()];
        }
    }

    // Matricular múltiples estudiantes en recuperación
    public function matricularRecuperacion(Request $request)
    {
        try {
            $request->validate([
                'estudiantes' => 'required|array',
                'estudiantes.*.estudiante_id' => 'required|exists:estudiantes,id',
                'estudiantes.*.periodo_recuperacion_id' => 'required|exists:periodos,id',
                'estudiantes.*.periodo_academico_id' => 'required|exists:periodos,id',
                'estudiantes.*.competencias' => 'required|array',
            ]);

            $resultados = [];
            foreach ($request->estudiantes as $data) {
                $resultados[] = $this->matricularRecuperacionBase(
                    $data['estudiante_id'],
                    $data['periodo_recuperacion_id'],
                    $data['periodo_academico_id'],
                    $data['competencias']
                );
            }

            $successCount = count(array_filter($resultados, fn ($r) => $r['success']));

            return response()->json([
                'success' => $successCount > 0,
                'message' => "Procesados: $successCount estudiante(s) correctamente",
                'detalles' => $resultados,
            ], $successCount > 0 ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error en matricularRecuperacion: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    // Matricular un estudiante individual en recuperación
    public function matricularRecuperacionIndividual(Request $request)
    {
        try {
            $request->validate([
                'estudiante_id' => 'required|exists:estudiantes,id',
                'periodo_recuperacion_id' => 'required|exists:periodos,id',
                'periodo_academico_id' => 'required|exists:periodos,id',
                'competencias' => 'required|array',
            ]);

            $resultado = $this->matricularRecuperacionBase(
                $request->estudiante_id,
                $request->periodo_recuperacion_id,
                $request->periodo_academico_id,
                $request->competencias
            );

            return response()->json($resultado, $resultado['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error en matricularRecuperacionIndividual: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    // Obtener competencias de recuperación de un estudiante
    public function getCompetenciasRecuperacion($estudianteId, $periodoRecuperacionId)
    {
        $competencias = Recuperacioncompetencia::where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoRecuperacionId)
            ->with(['materiaCompetencia', 'materia'])
            ->get();

        return response()->json($competencias);
    }

    // Actualizar nota de recuperación
    public function actualizarNotaRecuperacion(Request $request)
    {
        try {
            $request->validate([
                'recuperacion_id' => 'required|exists:estudiante_recuperacion_competencias,id',
                'nivel_logro_final' => 'required|string|in:C,B,A,AD',
            ]);

            $recuperacion = Recuperacioncompetencia::find($request->recuperacion_id);

            if ($recuperacion->estado == '1') {
                return response()->json(['success' => false, 'message' => 'Esta nota ya está bloqueada'], 400);
            }

            $recuperacion->nivel_logro_final = $request->nivel_logro_final;
            $recuperacion->save();

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente',
                'data' => [
                    'nivel_logro_final' => $request->nivel_logro_final,
                    'nota_numerica' => $this->competenciaService->convertirEnumANota($request->nivel_logro_final),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando nota: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    // Cambiar estado de notas de recuperación (bloquear/liberar)
    public function cambiarEstadoNotasRecuperacion(Request $request)
    {
        try {
            $request->validate([
                'periodo_recuperacion_id' => 'required|exists:periodos,id',
                'grado_id' => 'required|exists:grados,id',
                'nuevo_estado' => 'required|in:0,1',
                'estudiante_id' => 'nullable|exists:estudiantes,id',
            ]);

            $query = Recuperacioncompetencia::where('periodo_id', $request->periodo_recuperacion_id)
                ->whereHas('estudiante', function ($q) use ($request) {
                    $q->where('grado_id', $request->grado_id);
                    if ($request->filled('estudiante_id')) {
                        $q->where('id', $request->estudiante_id);
                    }
                });

            $actualizados = $query->update(['estado' => $request->nuevo_estado]);

            $accion = $request->nuevo_estado == '1' ? 'bloquearon' : 'liberaron';
            $mensaje = $request->filled('estudiante_id')
                ? "Se $accion las notas del estudiante"
                : "Se $accion $actualizados notas del grado";

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'nuevo_estado' => $request->nuevo_estado,
            ]);

        } catch (\Exception $e) {
            Log::error('Error cambiando estado: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    // Ascender estudiantes de grado
    public function estudiantesUpdateGrado(Request $request, $gradoId)
    {
        try {
            $request->validate([
                'nuevo_grado' => 'required|integer',
                'nueva_seccion' => 'required|string|max:1',
                'nuevo_nivel' => 'required|string',
                'estudiantes' => 'required|array',
                'estudiantes.*' => 'exists:estudiantes,id',
            ]);

            $nuevoGrado = Grado::firstOrCreate(
                [
                    'grado' => $request->nuevo_grado,
                    'seccion' => $request->nueva_seccion,
                    'nivel' => $request->nuevo_nivel,
                ],
                ['estado' => '1']
            );

            $estudiantesActualizados = Estudiante::whereIn('id', $request->estudiantes)->update(['grado_id' => $nuevoGrado->id]);

            // Verificar si la solicitud es AJAX/JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Estudiantes ascendidos al grado {$nuevoGrado->grado}° \"{$nuevoGrado->seccion}\" - {$nuevoGrado->nivel}",
                    'estudiantes_actualizados' => $estudiantesActualizados,
                    'nuevo_grado' => [
                        'id' => $nuevoGrado->id,
                        'grado' => $nuevoGrado->grado,
                        'seccion' => $nuevoGrado->seccion,
                        'nivel' => $nuevoGrado->nivel,
                    ],
                ]);
            }

            // Para solicitudes normales (no AJAX)
            return redirect()->route('grado.estudiantes', $gradoId)
                ->with('success', "Estudiantes ascendidos al grado {$nuevoGrado->grado}° \"{$nuevoGrado->seccion}\" - {$nuevoGrado->nivel}");

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al ascender estudiantes: '.$e->getMessage(),
                ], 500);
            }
            throw $e;
        }
    }
}
