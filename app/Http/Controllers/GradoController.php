<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use App\Models\Nota;
use App\Models\Matricula;
use App\Models\Materia\Materiacriterio;
use App\Models\Materia\Materiacompetencia;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Materia\Recuperacioncompetencia;
use App\Models\Periodo;
use App\Models\Estudiante;
use App\Services\ProcesarnotasCriterioService;
use App\Services\ProcesarnotasCompetenciaService;
use App\Services\ProcesarnotasMateriaService;
use App\Services\EvaluacionEstudianteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GradoController extends Controller
{
    protected $criterioService;
    protected $competenciaService;
    protected $materiaService;
    protected $evaluacionService;
    //moduleID 10 = Grados
    public function __construct(
        ProcesarnotasCriterioService $criterioService,
        ProcesarnotasCompetenciaService $competenciaService,
        ProcesarnotasMateriaService $materiaService,
        EvaluacionEstudianteService $evaluacionService,
    )
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('10')) {
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

    public function estudiantes($id)
    {
        $grado = Grado::findOrFail($id);

        // Obtener años disponibles
        $aniosDisponibles = Periodo::where('estado', '1')
            ->select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        $anioSeleccionado = request()->get('anio', $aniosDisponibles->first());

        // Período académico
        $periodoAcademico = Periodo::where('anio', $anioSeleccionado)
            ->where('tipo_periodo', 'año escolar')
            ->where('estado', '1')
            ->first();

        if (!$periodoAcademico) {
            return redirect()->back()->with('error', "No hay período académico para el año $anioSeleccionado");
        }

        // Período de recuperación (opcional)
        $periodoRecuperacion = Periodo::where('anio', $anioSeleccionado)
            ->where('tipo_periodo', 'recuperación')
            ->where('estado', '1')
            ->first();

        // Obtener estudiantes del grado
        $estudiantesRegistrados = Estudiante::where('grado_id', $grado->id)
            ->where('estado', '1')
            ->with(['user'])
            ->get();

        // Obtener IDs de estudiantes matriculados
        $estudiantesMatriculadosIds = Matricula::where('periodo_id', $periodoAcademico->id)
            ->where('estado', '1')
            ->pluck('estudiante_id')
            ->toArray();

        // Obtener materias del grado
        $materiasAsignadas = Cursogradosecnivanio::where('periodo_id', $periodoAcademico->id)
            ->where('grado_id', $grado->id)
            ->with(['materia'])
            ->get();

        // Crear array de materias [materia_id => nombre]
        $materiasArray = [];
        foreach ($materiasAsignadas as $asignacion) {
            $materiasArray[$asignacion->materia_id] = $asignacion->materia->nombre;
        }

        $materiaIds = array_keys($materiasArray);

        if (empty($materiaIds)) {
            $estudiantesMatriculados = collect();
            $estudiantesNoMatriculados = $estudiantesRegistrados;

            return view('grado.gradoestudiantes', compact(
                'grado',
                'aniosDisponibles',
                'anioSeleccionado',
                'periodoAcademico',
                'periodoRecuperacion',
                'estudiantesMatriculados',
                'estudiantesNoMatriculados'
            ));
        }

        // Obtener todas las competencias
        $competenciasQuery = Materiacompetencia::whereIn('materia_id', $materiaIds)
            ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%transversal%'])
            ->whereHas('materiaCriterio', function($query) use ($grado, $periodoAcademico) {
                $query->where('grado_id', $grado->id)
                    ->whereHas('periodoBimestre', function($q) use ($periodoAcademico) {
                        $q->where('periodo_id', $periodoAcademico->id);
                    });
            })
            ->get();

        $competenciasArray = [];
        $competenciasNombres = [];
        foreach ($competenciasQuery as $competencia) {
            $competenciasArray[$competencia->id] = $competencia->materia_id;
            $competenciasNombres[$competencia->id] = $competencia->nombre;
        }

        $competenciaIds = array_keys($competenciasArray);

        if (empty($competenciaIds)) {
            $estudiantesMatriculados = collect();
            $estudiantesNoMatriculados = $estudiantesRegistrados;

            return view('grado.gradoestudiantes', compact(
                'grado',
                'aniosDisponibles',
                'anioSeleccionado',
                'periodoAcademico',
                'periodoRecuperacion',
                'estudiantesMatriculados',
                'estudiantesNoMatriculados'
            ));
        }

        // Obtener criterios
        $criterios = Materiacriterio::whereIn('materia_competencia_id', $competenciaIds)
            ->where('grado_id', $grado->id)
            ->whereHas('periodoBimestre', function($q) use ($periodoAcademico) {
                $q->where('periodo_id', $periodoAcademico->id);
            })
            ->get();

        $criteriosArray = [];
        foreach ($criterios as $criterio) {
            $criteriosArray[$criterio->id] = [
                'competencia_id' => $criterio->materia_competencia_id,
                'materia_id' => $competenciasArray[$criterio->materia_competencia_id] ?? null
            ];
        }

        $criterioIds = array_keys($criteriosArray);

        // Obtener todas las notas
        $notasQuery = Nota::whereIn('estudiante_id', $estudiantesMatriculadosIds)
            ->whereIn('materia_criterio_id', $criterioIds)
            ->where('periodo_id', $periodoAcademico->id)
            ->select('estudiante_id', 'materia_criterio_id', 'nota')
            ->get();

        // Construir array para el servicio de criterios
        $notasArray = [];
        foreach ($notasQuery as $nota) {
            if (isset($criteriosArray[$nota->materia_criterio_id])) {
                $criterioInfo = $criteriosArray[$nota->materia_criterio_id];
                $notasArray[] = [
                    'estudiante_id' => $nota->estudiante_id,
                    'materia_criterio_id' => $nota->materia_criterio_id,
                    'materia_competencia_id' => $criterioInfo['competencia_id'],
                    'materia_id' => $criterioInfo['materia_id'],
                    'nota' => $nota->nota
                ];
            }
        }

        // Obtener recuperaciones
        $recuperacionesPorEstudiante = [];
        if ($periodoRecuperacion) {
            $recuperaciones = Recuperacioncompetencia::whereIn('estudiante_id', $estudiantesMatriculadosIds)
                ->whereIn('materia_competencia_id', $competenciaIds)
                ->where('periodo_id', $periodoRecuperacion->id)
                ->get();

            foreach ($recuperaciones as $rec) {
                $estId = $rec->estudiante_id;
                $compId = $rec->materia_competencia_id;
                if (!isset($recuperacionesPorEstudiante[$estId])) {
                    $recuperacionesPorEstudiante[$estId] = [];
                }

                $notaRecuperacion = null;
                if ($rec->nivel_logro_final) {
                    $notaRecuperacion = $this->competenciaService->convertirEnumANota($rec->nivel_logro_final);
                }

                $recuperacionesPorEstudiante[$estId][$compId] = [
                    'nota' => $notaRecuperacion,
                    'tiene_registro' => true
                ];
            }
        }

        // FLUJO: Procesar datos (solo cálculos)
        $criteriosProcesados = $this->criterioService->procesar($notasArray);
        $competenciasProcesadas = $this->competenciaService->procesar($criteriosProcesados, $recuperacionesPorEstudiante);
        $materiasProcesadas = $this->materiaService->procesar($competenciasProcesadas, $materiasArray, $competenciasNombres);

        // Aplicar lógica de negocio (evaluación)
        $resultadosPorEstudiante = [];
        foreach ($materiasProcesadas as $materia) {
            $estId = $materia['estudiante_id'];
            if (!isset($resultadosPorEstudiante[$estId])) {
                $resultadosPorEstudiante[$estId] = [];
            }

            // Enriquecer la materia con información de evaluación
            $recuperacionesInfoEstudiante = $recuperacionesPorEstudiante[$estId] ?? [];
            $materiaEnriquecida = $this->evaluacionService->enriquecerMaterias([$materia], $recuperacionesInfoEstudiante)[0];
            $resultadosPorEstudiante[$estId][] = $materiaEnriquecida;
        }

        // Construir estudiantes matriculados
        $estudiantesMatriculados = collect();
        foreach ($estudiantesRegistrados as $estudiante) {
            if (in_array($estudiante->id, $estudiantesMatriculadosIds)) {
                $materias = $resultadosPorEstudiante[$estudiante->id] ?? [];

                $totalMaterias = count($materias);
                $materiasAprobadas = 0;
                $materiasDesaprobadas = 0;

                // Calcular estadísticas de competencias
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
                        if ($competencia['esta_aprobada'] ?? false) $compAprobadas++;
                        if (($competencia['requiere_recuperacion'] ?? false)) $compPendientes++;
                        if (($competencia['tiene_registro_recuperacion'] ?? false)) $compPendientesCalificar++;
                    }
                }

                $porcentaje = $totalComp > 0 ? round(($compAprobadas / $totalComp) * 100) : 0;

                // Calcular estado final del estudiante
                $estadoFinal = 'sin_evaluacion';
                if ($totalComp > 0) {
                    if ($totalComp === $compAprobadas && $compPendientesCalificar === 0) {
                        $estadoFinal = 'aprobado';
                    } elseif ($compPendientesCalificar > 0) {
                        $estadoFinal = 'pendiente_calificar';
                    } elseif ($compPendientes > 0) {
                        $estadoFinal = 'recuperacion';
                    } else {
                        $estadoFinal = 'desaprobado';
                    }
                }

                // Calcular total de competencias a recuperar para el modal
                $totalCompReqEstudiante = 0;
                foreach ($materias as $materia) {
                    foreach ($materia['competencias'] as $competencia) {
                        $notaOriginal = $competencia['promedio_original'];
                        $tieneNotaRecuperacion = ($competencia['nota_recuperacion'] ?? null) !== null;
                        $tieneRegistro = $competencia['tiene_registro_recuperacion'] ?? false;

                        if ($notaOriginal < 1.5 && !$tieneNotaRecuperacion && !$tieneRegistro) {
                            $totalCompReqEstudiante++;
                        }
                    }
                }

                $estudiante->estado_aprobacion = $estadoGeneral = $estadoFinal;
                $estudiante->total_materias = $totalMaterias;
                $estudiante->materias_aprobadas = $materiasAprobadas;
                $estudiante->materias_desaprobadas_count = $materiasDesaprobadas;
                $estudiante->detalle_materias = $materias;
                $estudiante->total_competencias = $totalComp;
                $estudiante->competencias_aprobadas = $compAprobadas;
                $estudiante->competencias_pendientes = $compPendientes;
                $estudiante->competencias_pendientes_calificar = $compPendientesCalificar;
                $estudiante->porcentaje_aprobacion = $porcentaje;
                $estudiante->total_competencias_recuperar = $totalCompReqEstudiante;
                $estudiante->estado_final = $estadoFinal;

                // Verificar si tiene matrícula en recuperación
                $tieneMatriculaRecuperacion = Matricula::where('estudiante_id', $estudiante->id)
                    ->where('periodo_id', $periodoRecuperacion?->id)
                    ->exists();

                $tieneCompetenciasRecuperacion = false;
                $competenciasRecuperacionCount = 0;
                if ($periodoRecuperacion) {
                    $competenciasRecuperacionCount = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
                        ->where('periodo_id', $periodoRecuperacion->id)
                        ->count();
                    $tieneCompetenciasRecuperacion = $competenciasRecuperacionCount > 0;
                }

                $estudiante->tiene_matricula_recuperacion = $tieneMatriculaRecuperacion;
                $estudiante->tiene_competencias_recuperacion = $tieneCompetenciasRecuperacion;
                $estudiante->competencias_recuperacion_count = $competenciasRecuperacionCount;

                $estudiantesMatriculados->push($estudiante);
            }
        }

        // Calcular estudiantes que necesitan recuperación para el botón flotante
        $estudiantesParaRecuperacion = $estudiantesMatriculados->filter(function($est) {
            return $est->estado_final === 'recuperacion';
        })->count();

        // Estudiantes no matriculados
        $estudiantesNoMatriculados = $estudiantesRegistrados->filter(function($estudiante) use ($estudiantesMatriculadosIds) {
            return !in_array($estudiante->id, $estudiantesMatriculadosIds);
        });

        return view('grado.gradoestudiantes', compact(
            'grado', 'aniosDisponibles', 'anioSeleccionado',
            'periodoAcademico', 'periodoRecuperacion',
            'estudiantesMatriculados', 'estudiantesNoMatriculados',
            'estudiantesParaRecuperacion'
        ));
    }
    public function matricularRecuperacion(Request $request)
    {
        try {
            $request->validate([
                'estudiantes' => 'required|array',
                'estudiantes.*.estudiante_id' => 'required|exists:estudiantes,id',
                'estudiantes.*.periodo_recuperacion_id' => 'required|exists:periodos,id',
                'estudiantes.*.periodo_academico_id' => 'required|exists:periodos,id',
                'estudiantes.*.competencias' => 'required|array',
                'estudiantes.*.competencias.*.materia_competencia_id' => 'required',
                'estudiantes.*.competencias.*.materia_id' => 'required',
                'estudiantes.*.competencias.*.nota_original' => 'required|numeric',
            ]);

            $resultados = [];

            foreach ($request->estudiantes as $estudianteData) {
                $resultado = $this->procesarMatriculaRecuperacion(
                    $estudianteData['estudiante_id'],
                    $estudianteData['periodo_recuperacion_id'],
                    $estudianteData['periodo_academico_id'],
                    $estudianteData['competencias']
                );
                $resultados[] = $resultado;
            }

            $successCount = count(array_filter($resultados, function($r) { return $r['success']; }));
            $errorCount = count($resultados) - $successCount;

            if ($successCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Procesado: $successCount estudiante(s) matriculado(s) correctamente. Errores: $errorCount",
                    'detalles' => $resultados
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo matricular a ningún estudiante',
                    'detalles' => $resultados
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en matricularRecuperacion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    // Matricular un estudiante individual en período de recuperación (vía fetch desde modal)
    public function matricularRecuperacionIndividual(Request $request)
    {
        try {
            $request->validate([
                'estudiante_id' => 'required|exists:estudiantes,id',
                'periodo_recuperacion_id' => 'required|exists:periodos,id',
                'periodo_academico_id' => 'required|exists:periodos,id',
                'competencias' => 'required|array',
                'competencias.*.materia_competencia_id' => 'required',
                'competencias.*.materia_id' => 'required',
                'competencias.*.nota_original' => 'required|numeric',
            ]);

            $resultado = $this->procesarMatriculaRecuperacion(
                $request->estudiante_id,
                $request->periodo_recuperacion_id,
                $request->periodo_academico_id,
                $request->competencias
            );

            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $resultado['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en matricularRecuperacionIndividual: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getCompetenciasRecuperacion($estudianteId, $periodoRecuperacionId)
    {
        $competencias = Recuperacioncompetencia::where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoRecuperacionId)
            ->with(['materiaCompetencia', 'materia'])
            ->get();

        return response()->json($competencias);
    }
    public function actualizarNotaRecuperacion(Request $request)
    {
        try {
            $request->validate([
                'recuperacion_id' => 'required|exists:estudiante_recuperacion_competencias,id',
                'nivel_logro_final' => 'required|string',
            ]);

            $recuperacion = Recuperacioncompetencia::find($request->recuperacion_id);
            $recuperacion->nivel_logro_final = $request->nivel_logro_final;
            $recuperacion->save();

            return response()->json([
                'success' => true,
                'message' => 'Nota de recuperación actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    // Procesa la matrícula de un estudiante en recuperación
    private function procesarMatriculaRecuperacion($estudianteId, $periodoRecuperacionId, $periodoAcademicoId, $competencias)
    {
        try {
            DB::beginTransaction();

            // 1. Verificar si el estudiante existe
            $estudiante = Estudiante::find($estudianteId);
            if (!$estudiante) {
                return ['success' => false, 'message' => 'Estudiante no encontrado'];
            }

            // 2. Obtener la matrícula del período académico para saber el grado
            $matriculaAcademica = Matricula::where('estudiante_id', $estudianteId)
                ->where('periodo_id', $periodoAcademicoId)
                ->where('estado', '1')
                ->first();

            if (!$matriculaAcademica) {
                return ['success' => false, 'message' => 'El estudiante no tiene matrícula en el período académico'];
            }

            // 3. Verificar o crear matrícula en el período de recuperación
            $matriculaRecuperacion = Matricula::where('estudiante_id', $estudianteId)
                ->where('periodo_id', $periodoRecuperacionId)
                ->first();

            if (!$matriculaRecuperacion) {
                Matricula::create([
                    'estudiante_id' => $estudianteId,
                    'periodo_id' => $periodoRecuperacionId,
                    'grado_id' => $matriculaAcademica->grado_id,
                    'estado' => '1',
                ]);
            }

            // 4. Registrar las competencias a recuperar
            $registradas = 0;
            $duplicadas = 0;

            foreach ($competencias as $competencia) {
                // Verificar si ya existe registro de recuperación para esta competencia
                $existe = Recuperacioncompetencia::where('estudiante_id', $estudianteId)
                    ->where('materia_competencia_id', $competencia['materia_competencia_id'])
                    ->where('periodo_id', $periodoRecuperacionId)
                    ->exists();

                if (!$existe) {
                    // Convertir la nota original a ENUM usando el servicio base
                    $notaOriginal = floatval($competencia['nota_original']);
                    $valorEnum = $this->competenciaService->convertirNotaAEnum($notaOriginal);

                    Recuperacioncompetencia::create([
                        'estudiante_id' => $estudianteId,
                        'materia_competencia_id' => $competencia['materia_competencia_id'],
                        'materia_id' => $competencia['materia_id'],
                        'periodo_id' => $periodoRecuperacionId,
                        'nivel_logro_inicial' => $valorEnum,
                        'nivel_logro_final' => null,
                    ]);
                    $registradas++;
                } else {
                    $duplicadas++;
                }
            }

            DB::commit();

            if ($registradas > 0) {
                return [
                    'success' => true,
                    'message' => "Estudiante matriculado en recuperación. Competencias registradas: $registradas, duplicadas: $duplicadas"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "No se registraron nuevas competencias. $duplicadas ya existían"
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando matrícula de recuperación: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }
    public function estudiantesUpdateGrado(Request $request, $gradoId)
    {
        $request->validate([
            'nuevo_grado' => 'required|integer',
            'nueva_seccion' => 'required|string|max:1',
            'nuevo_nivel' => 'required|string',
            'estudiantes' => 'required|array',
            'estudiantes.*' => 'exists:estudiantes,id'
        ]);

        // Buscar o crear el nuevo grado
        $nuevoGrado = Grado::firstOrCreate(
            [
                'grado' => $request->nuevo_grado,
                'seccion' => $request->nueva_seccion,
                'nivel' => $request->nuevo_nivel
            ],
            ['estado' => '1']
        );

        // Actualizar los estudiantes seleccionados
        Estudiante::whereIn('id', $request->estudiantes)
            ->update(['grado_id' => $nuevoGrado->id]);

        return redirect()->route('grado.estudiantes', $gradoId)
            ->with('success', 'Estudiantes ascendidos correctamente al grado ' .
                $nuevoGrado->grado . '° "' . $nuevoGrado->seccion . '" - ' . $nuevoGrado->nivel);
    }
}
