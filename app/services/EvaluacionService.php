<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Periodo;
use App\Models\Matricula;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Materia\Materiacompetencia;
use App\Models\Nota;
use App\Models\Materia\Recuperacioncompetencia;
use Illuminate\Support\Collection;

class EvaluacionService
{
    // Umbrales de notas
    const NOTA_MINIMA_APROBACION = 2.1;
    const NOTA_AD = 3.5;
    const NOTA_A = 2.5;
    const NOTA_B = 1.5;
    const NOTA_C = 0;

    // Convierte nota numérica a calificación cualitativa
    public function convertirACualitativo(float $nota): string
    {
        if ($nota >= self::NOTA_AD) return 'AD';
        if ($nota >= self::NOTA_A) return 'A';
        if ($nota >= self::NOTA_B) return 'B';
        return 'C';
    }

    // Convierte calificación cualitativa a numérica
    public function convertirACuantitativo(string $cualitativo): float
    {
        return match ($cualitativo) {
            'AD' => 4,
            'A' => 3,
            'B' => 2,
            'C' => 1,
            default => 0,
        };
    }

    // Determina si una nota está aprobada
    public function estaAprobada(float $nota): bool
    {
        return $nota >= self::NOTA_MINIMA_APROBACION;
    }

    // Determina el estado de una materia basado en su promedio
    public function getEstadoMateria(float $promedio): string
    {
        if ($promedio >= self::NOTA_MINIMA_APROBACION) {
            return 'aprobado';
        }
        return 'desaprobado';
    }

    //Determina el estado general del estudiante
    public function getEstadoGeneral(int $materiasAprobadas, int $materiasDesaprobadas, int $totalMaterias): string
    {
        if ($totalMaterias === 0) return 'sin_evaluacion';
        if ($materiasDesaprobadas === 0) return 'aprobado';
        if ($materiasAprobadas > 0) return 'recuperacion';
        return 'desaprobado';
    }

    //Obtiene las materias de un grado para un período
    public function getMateriasPorGradoYPeriodo(int $gradoId, int $periodoId): Collection
    {
        return Cursogradosecnivanio::where('periodo_id', $periodoId)
            ->where('grado_id', $gradoId)
            ->with(['materia'])
            ->get();
    }

    // Obtiene las competencias de una materia (excluyendo transversales)
    public function getCompetenciasPorMateria(int $materiaId, int $gradoId, int $periodoId): Collection
    {
        return Materiacompetencia::where('materia_id', $materiaId)
            ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%transversal%'])
            ->whereHas('materiaCriterio', function($query) use ($gradoId, $periodoId) {
                $query->where('grado_id', $gradoId)
                    ->whereHas('periodoBimestre', function($q) use ($periodoId) {
                        $q->where('periodo_id', $periodoId);
                    });
            })
            ->with(['materiaCriterio' => function($query) use ($gradoId, $periodoId) {
                $query->where('grado_id', $gradoId)
                    ->whereHas('periodoBimestre', function($q) use ($periodoId) {
                        $q->where('periodo_id', $periodoId);
                    });
            }])
            ->get();
    }

    //Calcula el promedio de una competencia basado en las notas del estudiante
    public function calcularPromedioCompetencia(Estudiante $estudiante, $competencia, int $periodoId): float
    {
        $criterioIds = $competencia->materiaCriterio->pluck('id')->toArray();

        $notas = Nota::where('estudiante_id', $estudiante->id)
            ->whereIn('materia_criterio_id', $criterioIds)
            ->where('periodo_id', $periodoId)
            ->get();

        if ($notas->isEmpty()) {
            return 0;
        }

        return round($notas->avg('nota'), 2);
    }

    //Obtiene la nota de recuperación para una competencia
    public function getNotaRecuperacion(Estudiante $estudiante, int $competenciaId, int $periodoRecuperacionId): ?float
    {
        $recuperacion = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
            ->where('materia_competencia_id', $competenciaId)
            ->where('periodo_id', $periodoRecuperacionId)
            ->first();

        if ($recuperacion && $recuperacion->nivel_logro_final) {
            return (float) $recuperacion->nivel_logro_final;
        }

        return null;
    }

    //Evalúa todas las materias de un estudiante para un período
    public function evaluarEstudiante(Estudiante $estudiante, int $gradoId, int $periodoId, ?int $periodoRecuperacionId = null): array
    {
        $materiasAsignadas = $this->getMateriasPorGradoYPeriodo($gradoId, $periodoId);

        if ($materiasAsignadas->isEmpty()) {
            return $this->respuestaVacia();
        }

        $detalleMaterias = [];
        $materiasAprobadas = 0;
        $materiasDesaprobadas = 0;

        foreach ($materiasAsignadas as $asignacion) {
            $materia = $asignacion->materia;
            $competencias = $this->getCompetenciasPorMateria($materia->id, $gradoId, $periodoId);

            if ($competencias->isEmpty()) {
                $detalleMaterias[] = $this->materiaSinEvaluacion($materia);
                continue;
            }

            $resultadoMateria = $this->evaluarMateria($estudiante, $materia, $competencias, $periodoId, $periodoRecuperacionId);

            if ($resultadoMateria['estado'] === 'aprobado') {
                $materiasAprobadas++;
            } elseif ($resultadoMateria['estado'] === 'desaprobado') {
                $materiasDesaprobadas++;
            }

            $detalleMaterias[] = $resultadoMateria;
        }

        $totalMaterias = $materiasAprobadas + $materiasDesaprobadas;

        return [
            'estudiante_id' => $estudiante->id,
            'estudiante_nombre' => $estudiante->user->nombre_completo ?? $estudiante->id,
            'total_materias' => $totalMaterias,
            'materias_aprobadas' => $materiasAprobadas,
            'materias_desaprobadas' => $materiasDesaprobadas,
            'detalle_materias' => $detalleMaterias,
            'estado_general' => $this->getEstadoGeneral($materiasAprobadas, $materiasDesaprobadas, $totalMaterias),
            'puede_ascender' => $materiasDesaprobadas === 0 && $totalMaterias > 0,
            'requiere_recuperacion' => $materiasAprobadas > 0 && $materiasDesaprobadas > 0,
        ];
    }

    //Evalúa una materia específica
    private function evaluarMateria(Estudiante $estudiante, $materia, Collection $competencias, int $periodoId, ?int $periodoRecuperacionId): array
    {
        $sumaPromedios = 0;
        $totalCompetencias = 0;
        $competenciasDesaprobadas = [];
        $competenciasAprobadas = [];
        $todasCompetencias = []; // <-- NUEVO: Para guardar todas las competencias

        foreach ($competencias as $competencia) {
            $promedioOriginal = $this->calcularPromedioCompetencia($estudiante, $competencia, $periodoId);

            // Verificar si hay registro de recuperación
            $recuperacion = null;
            $tieneRecuperacion = false;
            $notaRecuperacion = null;

            if ($periodoRecuperacionId) {
                $recuperacion = Recuperacioncompetencia::where('estudiante_id', $estudiante->id)
                    ->where('materia_competencia_id', $competencia->id)
                    ->where('periodo_id', $periodoRecuperacionId)
                    ->first();

                $tieneRecuperacion = !is_null($recuperacion);

                // Si tiene nivel_logro_final, usarlo como nota de recuperación
                if ($recuperacion && $recuperacion->nivel_logro_final) {
                    $notaRecuperacion = $this->convertirEnumANota($recuperacion->nivel_logro_final);
                }
            }

            $notaFinal = $notaRecuperacion ?? $promedioOriginal;
            $estaAprobada = $this->estaAprobada($notaFinal);

            $sumaPromedios += $notaFinal;
            $totalCompetencias++;

            $competenciaInfo = [
                'id' => $competencia->id,
                'nombre' => $competencia->nombre,
                'promedio_original' => $promedioOriginal,
                'promedio_original_cualitativo' => $this->convertirACualitativo($promedioOriginal),
                'nota_recuperacion' => $notaRecuperacion,
                'nota_final' => $notaFinal,
                'nota_final_cualitativo' => $this->convertirACualitativo($notaFinal),
                'esta_aprobada' => $estaAprobada,
                'tiene_recuperacion' => $tieneRecuperacion,
                'recuperacion_pendiente' => $tieneRecuperacion && !$recuperacion?->nivel_logro_final,
            ];

            // Guardar en TODAS las competencias
            $todasCompetencias[] = $competenciaInfo;

            if ($estaAprobada) {
                $competenciasAprobadas[] = $competenciaInfo;
            } else {
                $competenciasDesaprobadas[] = $competenciaInfo;
            }
        }

        $promedioMateria = $totalCompetencias > 0 ? round($sumaPromedios / $totalCompetencias, 2) : 0;
        $estadoMateria = $this->getEstadoMateria($promedioMateria);

        return [
            'materia_id' => $materia->id,
            'materia_nombre' => $materia->nombre,
            'promedio' => $promedioMateria,
            'promedio_cualitativo' => $this->convertirACualitativo($promedioMateria),
            'estado' => $estadoMateria,
            'total_competencias' => $totalCompetencias,
            'competencias_aprobadas' => $competenciasAprobadas,
            'competencias_desaprobadas' => $competenciasDesaprobadas,
            'todas_competencias' => $todasCompetencias, // <-- NUEVO: Incluir todas las competencias
        ];
    }
    private function convertirEnumANota(string $enum): float
    {
        return match ($enum) {
            'AD' => 4,
            'A' => 3,
            'B' => 2,
            'C' => 1,
            '4' => 4,
            '3' => 3,
            '2' => 2,
            '1' => 1,
            default => 0,
        };
    }

    // Evalúa múltiples estudiantes de un grado
    public function evaluarGrado(Grado $grado, int $periodoId, ?int $periodoRecuperacionId = null): Collection
    {
        $estudiantes = Estudiante::where('grado_id', $grado->id)
            ->where('estado', '1')
            ->with(['user'])
            ->get();

        // Filtrar solo los que tienen matrícula en el período
        $estudiantesMatriculadosIds = Matricula::where('periodo_id', $periodoId)
            ->where('estado', '1')
            ->pluck('estudiante_id')
            ->toArray();

        $resultados = collect();

        foreach ($estudiantes as $estudiante) {
            $estaMatriculado = in_array($estudiante->id, $estudiantesMatriculadosIds);

            if ($estaMatriculado) {
                $evaluacion = $this->evaluarEstudiante($estudiante, $grado->id, $periodoId, $periodoRecuperacionId);
                $resultados->push((object) array_merge([
                    'estudiante' => $estudiante,
                    'esta_matriculado' => true,
                ], $evaluacion));
            } else {
                $resultados->push((object) [
                    'estudiante' => $estudiante,
                    'esta_matriculado' => false,
                    'estado_general' => 'sin_matricula',
                ]);
            }
        }

        return $resultados;
    }

    // Retorna respuesta vacía para cuando no hay materias
    private function respuestaVacia(): array
    {
        return [
            'total_materias' => 0,
            'materias_aprobadas' => 0,
            'materias_desaprobadas' => 0,
            'detalle_materias' => [],
            'estado_general' => 'sin_materias',
            'puede_ascender' => false,
            'requiere_recuperacion' => false,
        ];
    }

    // Retorna materia sin evaluación
    private function materiaSinEvaluacion($materia): array
    {
        return [
            'materia_id' => $materia->id,
            'materia_nombre' => $materia->nombre,
            'promedio' => null,
            'promedio_cualitativo' => 'N/E',
            'estado' => 'sin_evaluacion',
            'total_competencias' => 0,
            'competencias_aprobadas' => [],
            'competencias_desaprobadas' => [],
        ];
    }
}
