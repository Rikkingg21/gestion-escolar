<?php

namespace App\Services;

class ProcesarnotasCriterioService
{
    // Umbral de notas - APROBADO a partir de 1.5 (equivalente a B)
    const NOTA_MINIMA_APROBACION = 1.5;
    const NOTA_AD = 3.5;
    const NOTA_A = 2.5;
    const NOTA_B = 1.5;
    const NOTA_C = 0;

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
                    'notas' => [],
                    'total_notas' => 0,
                    'suma_notas' => 0
                ];
            }

            $grupos[$key]['notas'][] = $nota['nota'];
            $grupos[$key]['suma_notas'] += $nota['nota'];
            $grupos[$key]['total_notas']++;
        }

        $resultados = [];

        foreach ($grupos as $grupo) {
            $promedio = $grupo['total_notas'] > 0
                ? round($grupo['suma_notas'] / $grupo['total_notas'], 2)
                : 0;

            $resultados[] = [
                'estudiante_id' => $grupo['estudiante_id'],
                'materia_criterio_id' => $grupo['materia_criterio_id'],
                'materia_competencia_id' => $grupo['materia_competencia_id'],
                'materia_id' => $grupo['materia_id'],
                'promedio' => $promedio,
                'promedio_cualitativo' => $this->convertirACualitativo($promedio),
                'notas' => $grupo['notas']
            ];
        }

        return $resultados;
    }

    private function convertirACualitativo(float $nota): string
    {
        if ($nota >= self::NOTA_AD) return 'AD';
        if ($nota >= self::NOTA_A) return 'A';
        if ($nota >= self::NOTA_B) return 'B';
        return 'C';
    }
}
