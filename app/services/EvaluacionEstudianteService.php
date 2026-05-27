<?php

namespace App\Services;

class EvaluacionEstudianteService
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
        return !$estaAprobada && $notaRecuperacion === null && !$tieneRegistroRecuperacion;
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

                $estaAprobada = $this->competenciaEstaAprobada($competencia['promedio_final']);
                $tieneRegistro = isset($recuperacionesInfo[$competencia['id']]['tiene_registro']);

                if ($this->competenciaRequiereRecuperacion($estaAprobada, $competencia['nota_recuperacion'], $tieneRegistro)) {
                    $totalRequierenRecuperacion++;
                }
            }
        }

        if ($totalCompetencias === 0) return 'sin_evaluacion';
        if ($totalRequierenRecuperacion === 0) return 'aprobado';
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
            $estaAprobada = $this->competenciaEstaAprobada($competencia['promedio_final'] ?? 0);
            $tieneRegistro = isset($recuperacionesInfo[$competenciaId]['tiene_registro']);
            $requiereRecuperacion = $this->competenciaRequiereRecuperacion(
                $estaAprobada,
                $competencia['nota_recuperacion'] ?? null,
                $tieneRegistro
            );

            $resultados[] = array_merge($competencia, [
                'esta_aprobada' => $estaAprobada,
                'tiene_recuperacion' => $competencia['tiene_recuperacion'] ?? false,
                'tiene_registro_recuperacion' => $tieneRegistro,
                'requiere_recuperacion' => $requiereRecuperacion
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

            foreach ($competenciasEnriquecidas as $competencia) {
                if ($competencia['esta_aprobada']) {
                    $competenciasAprobadas++;
                } else {
                    $competenciasDesaprobadas++;
                }

                if ($competencia['requiere_recuperacion']) {
                    $competenciasRequierenRecuperacion++;
                }
            }

            $materiaAprobada = $this->materiaEstaAprobada($materia['promedio']);

            $resultados[] = array_merge($materia, [
                'competencias' => $competenciasEnriquecidas,
                'competencias_aprobadas_count' => $competenciasAprobadas,
                'competencias_desaprobadas_count' => $competenciasDesaprobadas,
                'competencias_requieren_recuperacion_count' => $competenciasRequierenRecuperacion,
                'estado' => $materiaAprobada ? 'aprobado' : 'desaprobado'
            ]);
        }

        return $resultados;
    }
}
