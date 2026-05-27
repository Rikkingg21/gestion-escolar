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
                'promedio' => $competencia['promedio'],
                'promedio_cualitativo' => $competencia['promedio_cualitativo'],
                'esta_aprobada' => $competencia['esta_aprobada'],
                'criterios' => $competencia['criterios'] ?? [],
                'total_criterios' => $competencia['total_criterios'] ?? 0
            ];

            $grupos[$key]['competencias'][] = $competenciaInfo;
            $grupos[$key]['suma_promedios'] += $competencia['promedio'];
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

            $estadoMateria = $promedioMateria >= self::NOTA_MINIMA_APROBACION
                ? 'aprobado'
                : 'desaprobado';

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
                'competencias' => $grupo['competencias'],
                'competencias_aprobadas_list' => array_values($competenciasAprobadas),
                'competencias_desaprobadas_list' => array_values($competenciasDesaprobadas)
            ];
        }

        return $resultados;
    }

    public function getEstadoGeneral(array $materias): string
    {
        $materiasAprobadas = 0;
        $materiasDesaprobadas = 0;

        foreach ($materias as $materia) {
            if ($materia['estado'] === 'aprobado') {
                $materiasAprobadas++;
            } else {
                $materiasDesaprobadas++;
            }
        }

        $totalMaterias = $materiasAprobadas + $materiasDesaprobadas;

        if ($totalMaterias === 0) return 'sin_evaluacion';
        if ($materiasDesaprobadas === 0) return 'aprobado';
        if ($materiasAprobadas > 0) return 'recuperacion';
        return 'desaprobado';
    }

    private function convertirACualitativo(float $nota): string
    {
        if ($nota >= 3.5) return 'AD';
        if ($nota >= 2.5) return 'A';
        if ($nota >= 1.5) return 'B';
        return 'C';
    }
}
