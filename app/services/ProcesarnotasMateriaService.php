<?php

namespace App\Services;

class ProcesarnotasMateriaService
{
    const NOTA_MINIMA_APROBACION = 1.5;

    public function procesar(array $competencias, array $materias = [], array $competenciasNombres = []): array
    {
        $grupos = [];

        foreach ($competencias as $competencia) {
            $key = $competencia['estudiante_id'] . '_' . $competencia['materia_id'];

            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'estudiante_id' => $competencia['estudiante_id'],
                    'materia_id' => $competencia['materia_id'],
                    'materia_nombre' => $materias[$competencia['materia_id']] ?? 'Materia',
                    'competencias' => [],
                    'suma_promedios' => 0,
                    'total_competencias' => 0,
                    'competencias_aprobadas_count' => 0,
                    'competencias_desaprobadas_count' => 0
                ];
            }

            $competenciaInfo = [
                'id' => $competencia['materia_competencia_id'],
                'nombre' => $competenciasNombres[$competencia['materia_competencia_id']] ?? 'Competencia',
                'promedio_original' => $competencia['promedio_original'],
                'promedio_original_cualitativo' => $competencia['promedio_original_cualitativo'],
                'nota_recuperacion' => $competencia['nota_recuperacion'],
                'nota_final' => $competencia['nota_final'],
                'nota_final_cualitativo' => $competencia['nota_final_cualitativo'],
                'esta_aprobada' => $competencia['esta_aprobada'],
                'tiene_recuperacion' => $competencia['tiene_recuperacion'],
                'requiere_recuperacion' => !$competencia['esta_aprobada'] && !$competencia['tiene_recuperacion'],
                'criterios' => $competencia['criterios'] ?? [],
                'total_criterios' => $competencia['total_criterios'] ?? 0
            ];

            $grupos[$key]['competencias'][] = $competenciaInfo;
            // Usar nota_final para el promedio de la materia
            $grupos[$key]['suma_promedios'] += $competencia['nota_final'];
            $grupos[$key]['total_competencias']++;

            if ($competencia['esta_aprobada']) {
                $grupos[$key]['competencias_aprobadas_count']++;
            } else {
                $grupos[$key]['competencias_desaprobadas_count']++;
            }
        }

        $resultados = [];

        foreach ($grupos as $grupo) {
            $promedioMateria = $grupo['total_competencias'] > 0
                ? round($grupo['suma_promedios'] / $grupo['total_competencias'], 2)
                : 0;

            // El estado de la materia se calcula con el promedio (puede estar aprobada aunque tenga competencias C)
            $estadoMateria = $promedioMateria >= self::NOTA_MINIMA_APROBACION
                ? 'aprobado'
                : 'desaprobado';

            // Las competencias que requieren recuperación son las que NO están aprobadas Y NO tienen recuperación
            $competenciasQueRequierenRecuperacion = array_filter($grupo['competencias'], function($comp) {
                return !$comp['esta_aprobada'] && !$comp['tiene_recuperacion'];
            });

            $competenciasAprobadas = array_filter($grupo['competencias'], function($comp) {
                return $comp['esta_aprobada'];
            });

            $competenciasDesaprobadas = array_filter($grupo['competencias'], function($comp) {
                return !$comp['esta_aprobada'];
            });

            $resultados[] = [
                'estudiante_id' => $grupo['estudiante_id'],
                'materia_id' => $grupo['materia_id'],
                'materia_nombre' => $grupo['materia_nombre'],
                'promedio' => $promedioMateria,
                'promedio_cualitativo' => $this->convertirACualitativo($promedioMateria),
                'estado' => $estadoMateria,
                'total_competencias' => $grupo['total_competencias'],
                'competencias_aprobadas_count' => $grupo['competencias_aprobadas_count'],
                'competencias_desaprobadas_count' => $grupo['competencias_desaprobadas_count'],
                'competencias_requieren_recuperacion_count' => count($competenciasQueRequierenRecuperacion),
                'competencias' => $grupo['competencias'],
                'competencias_aprobadas_list' => array_values($competenciasAprobadas),
                'competencias_desaprobadas_list' => array_values($competenciasDesaprobadas),
                'competencias_requieren_recuperacion_list' => array_values($competenciasQueRequierenRecuperacion)
            ];
        }

        return $resultados;
    }

    /**
     * Calcula el estado general del estudiante basado en competencias desaprobadas
     * Ahora: si tiene ALGUNA competencia desaprobada (sin recuperar), está en recuperación
     */
    public function getEstadoGeneral(array $materias): string
    {
        $totalCompetenciasRequierenRecuperacion = 0;
        $totalCompetencias = 0;

        foreach ($materias as $materia) {
            $totalCompetenciasRequierenRecuperacion += $materia['competencias_requieren_recuperacion_count'];
            $totalCompetencias += $materia['total_competencias'];
        }

        if ($totalCompetencias === 0) return 'sin_evaluacion';

        // Si no hay competencias que requieren recuperación, está aprobado
        if ($totalCompetenciasRequierenRecuperacion === 0) return 'aprobado';

        // Si tiene competencias que requieren recuperación
        return 'recuperacion';
    }

    private function convertirACualitativo(float $nota): string
    {
        if ($nota >= 3.5) return 'AD';
        if ($nota >= 2.5) return 'A';
        if ($nota >= 1.5) return 'B';
        return 'C';
    }
}
