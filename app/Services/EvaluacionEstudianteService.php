<?php

namespace App\Services;

class EvaluacionEstudianteService extends BaseNotasService
{
    const NOTA_MINIMA_APROBACION = 1.5;

    /**
     * Evalúa si una competencia está aprobada
     */
    public function competenciaEstaAprobada(float $promedioFinal): bool
    {
        return $promedioFinal >= self::NOTA_MINIMA_APROBACION;
    }

    /**
     * Evalúa si una competencia requiere recuperación
     */
    public function competenciaRequiereRecuperacion(bool $estaAprobada, ?float $notaRecuperacion, bool $tieneRegistroRecuperacion): bool
    {
        return ! $estaAprobada && $notaRecuperacion === null && ! $tieneRegistroRecuperacion;
    }

    /**
     * Evalúa si una materia está aprobada
     */
    public function materiaEstaAprobada(float $promedio): bool
    {
        return $promedio >= self::NOTA_MINIMA_APROBACION;
    }

    /**
     * Evalúa el estado general del estudiante
     */
    public function getEstadoGeneral(array $materias, array $recuperacionesInfo = []): string
    {
        $totalCompetencias = 0;
        $totalRequierenRecuperacion = 0;

        foreach ($materias as $materia) {
            foreach ($materia['competencias'] as $competencia) {
                $totalCompetencias++;

                $estaAprobada = $competencia['esta_aprobada'] ?? false;
                $tieneRegistro = $competencia['tiene_registro_recuperacion'] ?? false;
                $notaRecuperacion = $competencia['nota_recuperacion'] ?? null;

                if ($this->competenciaRequiereRecuperacion($estaAprobada, $notaRecuperacion, $tieneRegistro)) {
                    $totalRequierenRecuperacion++;
                }
            }
        }

        if ($totalCompetencias === 0) {
            return 'sin_evaluacion';
        }
        if ($totalRequierenRecuperacion === 0) {
            return 'aprobado';
        }

        return 'recuperacion';
    }

    /**
     * Enriquecer datos de competencias con información de evaluación
     */
    public function enriquecerCompetencias(array $competencias, array $recuperacionesInfo = []): array
    {
        $resultados = [];

        foreach ($competencias as $competencia) {
            $competenciaId = $competencia['id'];

            $notaFinal = $competencia['promedio_final'] ?? $competencia['promedio_original'];
            $estaAprobada = $this->competenciaEstaAprobada($notaFinal);

            $tieneNotaRecuperacion = ($competencia['nota_recuperacion'] ?? null) !== null;
            $tieneRegistro = isset($recuperacionesInfo[$competenciaId]['tiene_registro']);

            $requiereRecuperacion = $this->competenciaRequiereRecuperacion(
                $estaAprobada,
                $competencia['nota_recuperacion'] ?? null,
                $tieneRegistro
            );

            $resultados[] = array_merge($competencia, [
                'esta_aprobada' => $estaAprobada,
                'tiene_recuperacion' => $tieneNotaRecuperacion,
                'tiene_registro_recuperacion' => $tieneRegistro && ! $tieneNotaRecuperacion,
                'requiere_recuperacion' => $requiereRecuperacion,
            ]);
        }

        return $resultados;
    }

    /**
     * Enriquecer datos de materias con información de evaluación
     */
    public function enriquecerMaterias(array $materias, array $recuperacionesInfo = []): array
    {
        $resultados = [];

        foreach ($materias as $materia) {
            $competenciasEnriquecidas = $this->enriquecerCompetencias($materia['competencias'], $recuperacionesInfo);

            $competenciasAprobadas = 0;
            $competenciasDesaprobadas = 0;
            $competenciasRequierenRecuperacion = 0;
            $competenciasPendientesCalificar = 0;

            foreach ($competenciasEnriquecidas as $competencia) {
                if ($competencia['esta_aprobada']) {
                    $competenciasAprobadas++;
                } else {
                    $competenciasDesaprobadas++;
                }

                if ($competencia['requiere_recuperacion']) {
                    $competenciasRequierenRecuperacion++;
                }

                if ($competencia['tiene_registro_recuperacion']) {
                    $competenciasPendientesCalificar++;
                }
            }

            $sumaNotas = 0;
            foreach ($competenciasEnriquecidas as $competencia) {
                $sumaNotas += $competencia['promedio_final'];
            }
            $promedioMateria = count($competenciasEnriquecidas) > 0
                ? round($sumaNotas / count($competenciasEnriquecidas), 2)
                : 0;

            $materiaAprobada = $this->materiaEstaAprobada($promedioMateria);

            $resultados[] = array_merge($materia, [
                'competencias' => $competenciasEnriquecidas,
                'promedio' => $promedioMateria,
                'promedio_cualitativo' => $this->convertirACualitativo($promedioMateria),
                'competencias_aprobadas_count' => $competenciasAprobadas,
                'competencias_desaprobadas_count' => $competenciasDesaprobadas,
                'competencias_requieren_recuperacion_count' => $competenciasRequierenRecuperacion,
                'competencias_pendientes_calificar_count' => $competenciasPendientesCalificar,
                'estado' => $materiaAprobada ? 'aprobado' : 'desaprobado',
            ]);
        }

        return $resultados;
    }
}
