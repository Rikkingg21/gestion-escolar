<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use App\Models\Nota;
use App\Models\Matricula;
use App\Models\Materia\Materiacriterio;
use App\Models\Materia\Materiacompetencia;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Periodo;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    //moduleID 10 = Grados
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('10')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
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

        // Obtener años disponibles (distinct años de períodos)
        $aniosDisponibles = Periodo::where('estado', '1')
            ->select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        // Año seleccionado (por defecto el más reciente)
        $anioSeleccionado = request()->get('anio', $aniosDisponibles->first());

        // Obtener período de tipo "año académico" para el año seleccionado
        $periodoAcademico = Periodo::where('anio', $anioSeleccionado)
            ->where('tipo_periodo', 'año escolar')
            ->where('estado', '1')
            ->first();

        if (!$periodoAcademico) {
            return redirect()->back()->with('error', "No hay período académico para el año $anioSeleccionado");
        }

        // Obtener período de recuperación para el mismo año (si existe) - PUEDE SER NULL
        $periodoRecuperacion = Periodo::where('anio', $anioSeleccionado)
            ->where('tipo_periodo', 'recuperación')
            ->where('estado', '1')
            ->first();

        // 1. ESTUDIANTES REGISTRADOS EN EL GRADO
        $estudiantesRegistrados = Estudiante::where('grado_id', $id)
            ->where('estado', '1')
            ->with(['user'])
            ->get();

        // 2. ESTUDIANTES MATRICULADOS EN EL PERÍODO ACADÉMICO
        $estudiantesMatriculadosIds = Matricula::where('periodo_id', $periodoAcademico->id)
            ->where('estado', '1')
            ->pluck('estudiante_id')
            ->toArray();

        $estudiantesMatriculados = Estudiante::whereIn('id', $estudiantesMatriculadosIds)
            ->where('grado_id', $id)
            ->where('estado', '1')
            ->with(['user'])
            ->get();

        // Si no hay matriculados, mostrar vista con datos básicos
        if ($estudiantesMatriculados->isEmpty()) {
            return view('grado.gradoestudiantes', compact(
                'grado',
                'aniosDisponibles',
                'anioSeleccionado',
                'periodoAcademico',
                'periodoRecuperacion',
                'estudiantesRegistrados',
                'estudiantesMatriculados'
            ));
        }

        // Obtener materias del grado en el período académico
        $materiasAsignadas = Cursogradosecnivanio::where('periodo_id', $periodoAcademico->id)
            ->where('grado_id', $grado->id)
            ->with(['materia'])
            ->get();

        if ($materiasAsignadas->isEmpty()) {
            foreach ($estudiantesMatriculados as $estudiante) {
                $estudiante->estado_aprobacion = 'sin_materias';
                $estudiante->total_materias = 0;
                $estudiante->materias_aprobadas = 0;
                $estudiante->materias_desaprobadas_count = 0;
                $estudiante->detalle_materias = [];
            }
            return view('grado.gradoestudiantes', compact(
                'grado',
                'aniosDisponibles',
                'anioSeleccionado',
                'periodoAcademico',
                'periodoRecuperacion',
                'estudiantesRegistrados',
                'estudiantesMatriculados'
            ));
        }

        // Obtener IDs de materias
        $materiaIds = $materiasAsignadas->pluck('materia_id')->toArray();

        // Obtener todas las competencias académicas (excluyendo transversales)
        $todasCompetencias = Materiacompetencia::whereIn('materia_id', $materiaIds)
            ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%transversal%'])
            ->whereHas('materiaCriterio', function($query) use ($grado, $periodoAcademico) {
                $query->where('grado_id', $grado->id)
                    ->whereHas('periodoBimestre', function($q) use ($periodoAcademico) {
                        $q->where('periodo_id', $periodoAcademico->id);
                    });
            })
            ->get();

        $competenciaIds = $todasCompetencias->pluck('id')->toArray();

        // Obtener todas las notas de los estudiantes matriculados
        $todasNotas = Nota::whereIn('estudiante_id', $estudiantesMatriculados->pluck('id')->toArray())
            ->whereIn('materia_criterio_id', function($query) use ($competenciaIds) {
                $query->select('id')
                    ->from('materia_criterios')
                    ->whereIn('materia_competencia_id', $competenciaIds);
            })
            ->where('periodo_id', $periodoAcademico->id)
            ->select('estudiante_id', 'materia_criterio_id', 'nota')
            ->get();

        // Agrupar notas por estudiante
        $notasPorEstudiante = [];
        foreach ($todasNotas as $nota) {
            $notasPorEstudiante[$nota->estudiante_id][] = $nota;
        }

        // Si HAY período de recuperación, obtener las notas de recuperación
        $recuperacionesPorEstudiante = [];
        if ($periodoRecuperacion) {
            // Obtener competencias que tienen criterios en el período de recuperación
            $competenciasRecuperacion = Materiacompetencia::whereIn('materia_id', $materiaIds)
                ->whereHas('materiaCriterio', function($query) use ($grado, $periodoRecuperacion) {
                    $query->where('grado_id', $grado->id)
                        ->whereHas('periodoBimestre', function($q) use ($periodoRecuperacion) {
                            $q->where('periodo_id', $periodoRecuperacion->id);
                        });
                })
                ->get();

            if ($competenciasRecuperacion->isNotEmpty()) {
                $competenciaRecIds = $competenciasRecuperacion->pluck('id')->toArray();

                $notasRecuperacion = Nota::whereIn('estudiante_id', $estudiantesMatriculados->pluck('id')->toArray())
                    ->whereIn('materia_criterio_id', function($query) use ($competenciaRecIds) {
                        $query->select('id')
                            ->from('materia_criterios')
                            ->whereIn('materia_competencia_id', $competenciaRecIds);
                    })
                    ->where('periodo_id', $periodoRecuperacion->id)
                    ->select('estudiante_id', 'materia_criterio_id', 'nota')
                    ->get();

                foreach ($notasRecuperacion as $nota) {
                    $recuperacionesPorEstudiante[$nota->estudiante_id][] = $nota;
                }
            }
        }

        // Procesar cada estudiante matriculado
        foreach ($estudiantesMatriculados as $estudiante) {
            $materiasAprobadas = 0;
            $materiasDesaprobadas = 0;
            $detalleMaterias = [];

            foreach ($materiasAsignadas as $asignacion) {
                $materia = $asignacion->materia;

                // Filtrar competencias de esta materia
                $competenciasMateria = $todasCompetencias->where('materia_id', $materia->id);

                if ($competenciasMateria->isEmpty()) {
                    $detalleMaterias[] = [
                        'materia_nombre' => $materia->nombre,
                        'promedio' => null,
                        'estado' => 'sin_evaluacion',
                        'competencias_desaprobadas' => []
                    ];
                    continue;
                }

                // Calcular promedio de la materia
                $sumaPromedios = 0;
                $totalComp = 0;
                $competenciasDesaprobadas = [];

                foreach ($competenciasMateria as $competencia) {
                    // Obtener criterios de esta competencia
                    $criterioIds = $competencia->materiaCriterio->pluck('id')->toArray();

                    // Calcular promedio de la competencia (notas académicas)
                    $notasEstudiante = $notasPorEstudiante[$estudiante->id] ?? [];
                    $sumaNotas = 0;
                    $totalNotas = 0;

                    foreach ($notasEstudiante as $nota) {
                        if (in_array($nota->materia_criterio_id, $criterioIds)) {
                            $sumaNotas += $nota->nota;
                            $totalNotas++;
                        }
                    }

                    $promedioCompetencia = $totalNotas > 0 ? $sumaNotas / $totalNotas : 0;
                    $sumaPromedios += $promedioCompetencia;
                    $totalComp++;

                    // Verificar si hay nota de recuperación para esta competencia (solo si existe período de recuperación)
                    $notaRecuperacion = null;
                    if ($periodoRecuperacion && isset($recuperacionesPorEstudiante[$estudiante->id])) {
                        foreach ($recuperacionesPorEstudiante[$estudiante->id] as $notaRec) {
                            $criterioRec = Materiacriterio::find($notaRec->materia_criterio_id);
                            if ($criterioRec && $criterioRec->materia_competencia_id == $competencia->id) {
                                $notaRecuperacion = $notaRec->nota;
                                break;
                            }
                        }
                    }

                    // Nota final (si hay recuperación, se usa esa; si no, la original)
                    $notaFinal = $notaRecuperacion ?? $promedioCompetencia;

                    if ($notaFinal < 2.1) {
                        $competenciasDesaprobadas[] = [
                            'id' => $competencia->id,
                            'nombre' => $competencia->nombre,
                            'promedio_original' => round($promedioCompetencia, 2),
                            'nota_recuperacion' => $notaRecuperacion ? round($notaRecuperacion, 2) : null,
                            'nota_final' => round($notaFinal, 2)
                        ];
                    }
                }

                $promedioMateria = $totalComp > 0 ? round($sumaPromedios / $totalComp, 2) : 0;

                if ($promedioMateria >= 2.1) {
                    $materiasAprobadas++;
                    $estado = 'aprobado';
                } else {
                    $materiasDesaprobadas++;
                    $estado = 'desaprobado';
                }

                $detalleMaterias[] = [
                    'materia_nombre' => $materia->nombre,
                    'promedio' => $promedioMateria,
                    'estado' => $estado,
                    'competencias_desaprobadas' => $competenciasDesaprobadas
                ];
            }

            $totalMaterias = $materiasAprobadas + $materiasDesaprobadas;

            // Estado general del estudiante
            if ($totalMaterias == 0) {
                $estadoGeneral = 'sin_evaluacion';
            } elseif ($materiasDesaprobadas == 0) {
                $estadoGeneral = 'aprobado';
            } elseif ($materiasAprobadas > 0) {
                $estadoGeneral = 'recuperacion';
            } else {
                $estadoGeneral = 'desaprobado';
            }

            $estudiante->estado_aprobacion = $estadoGeneral;
            $estudiante->total_materias = $totalMaterias;
            $estudiante->materias_aprobadas = $materiasAprobadas;
            $estudiante->materias_desaprobadas_count = $materiasDesaprobadas;
            $estudiante->detalle_materias = $detalleMaterias;
        }

        return view('grado.gradoestudiantes', compact(
            'grado',
            'aniosDisponibles',
            'anioSeleccionado',
            'periodoAcademico',
            'periodoRecuperacion',
            'estudiantesRegistrados',
            'estudiantesMatriculados'
        ));
    }

    private function calcularPromedioConRecuperacion($estudiante, $competenciasMateria, $periodoAcademico, $periodoRecuperacion)
    {
        $sumaPromedios = 0;
        $totalComp = 0;

        foreach ($competenciasMateria as $competencia) {
            $criterioIds = $competencia->materiaCriterio->pluck('id')->toArray();

            // Notas académicas originales
            $notasAcademicas = Nota::where('estudiante_id', $estudiante->id)
                ->whereIn('materia_criterio_id', $criterioIds)
                ->where('periodo_id', $periodoAcademico->id)
                ->get();

            $promedioAcademico = $notasAcademicas->avg('nota') ?? 0;

            // Nota de recuperación
            $notaRecuperacion = Nota::where('estudiante_id', $estudiante->id)
                ->whereIn('materia_criterio_id', $criterioIds)
                ->where('periodo_id', $periodoRecuperacion->id)
                ->first();

            $notaFinal = $notaRecuperacion ? $notaRecuperacion->nota : $promedioAcademico;
            $sumaPromedios += $notaFinal;
            $totalComp++;
        }

        return $totalComp > 0 ? round($sumaPromedios / $totalComp, 2) : 0;
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
