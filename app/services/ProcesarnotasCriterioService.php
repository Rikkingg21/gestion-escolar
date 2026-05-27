<?php

namespace App\Services;

class ProcesarnotasCriterioService extends BaseNotasService
{
    /**
     * Procesa las notas por criterio y calcula el promedio de cada uno
     *
     * @return array Cada elemento tiene: estudiante_id, materia_criterio_id,
     *               materia_competencia_id, materia_id, promedio, promedio_cualitativo
     */
    public function procesar(array $notas): array
    {
        $grupos = [];

        foreach ($notas as $nota) {
            $key = $nota['estudiante_id'] . '_' . $nota['materia_criterio_id'];

            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'estudiante_id' => $nota['estudiante_id'],
                    'materia_criterio_id' => $nota['materia_criterio_id'],
                    'materia_competencia_id' => $nota['materia_competencia_id'],
                    'materia_id' => $nota['materia_id'],
                    'suma_notas' => 0,
                    'total_notas' => 0
                ];
            }

            $grupos[$key]['suma_notas'] += $nota['nota'];
            $grupos[$key]['total_notas']++;
        }

        $resultados = [];

        foreach ($grupos as $grupo) {
            $promedio = $this->calcularPromedioDesdeSuma($grupo['suma_notas'], $grupo['total_notas']);

            $resultados[] = [
                'estudiante_id' => $grupo['estudiante_id'],
                'materia_criterio_id' => $grupo['materia_criterio_id'],
                'materia_competencia_id' => $grupo['materia_competencia_id'],
                'materia_id' => $grupo['materia_id'],
                'promedio' => $promedio,
                'promedio_cualitativo' => $this->convertirACualitativo($promedio)
            ];
        }

        return $resultados;
    }
}
