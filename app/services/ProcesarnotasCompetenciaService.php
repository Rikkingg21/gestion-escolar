<?php

namespace App\Services;

class ProcesarnotasCompetenciaService
{
    const NOTA_MINIMA_APROBACION = 1.5;
    const NOTA_AD = 3.5;
    const NOTA_A = 2.5;
    const NOTA_B = 1.5;
    const NOTA_C = 0;

    public function procesar(array $criterios): array
    {
        $grupos = [];

        foreach ($criterios as $criterio) {
            $key = $criterio['estudiante_id'] . '_' . $criterio['materia_competencia_id'];

            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'estudiante_id' => $criterio['estudiante_id'],
                    'materia_competencia_id' => $criterio['materia_competencia_id'],
                    'materia_id' => $criterio['materia_id'],
                    'criterios' => [],
                    'total_criterios' => 0,
                    'suma_promedios' => 0
                ];
            }

            $grupos[$key]['criterios'][] = [
                'materia_criterio_id' => $criterio['materia_criterio_id'],
                'promedio' => $criterio['promedio'],
                'promedio_cualitativo' => $criterio['promedio_cualitativo']
            ];

            $grupos[$key]['suma_promedios'] += $criterio['promedio'];
            $grupos[$key]['total_criterios']++;
        }

        $resultados = [];

        foreach ($grupos as $grupo) {
            $promedio = $grupo['total_criterios'] > 0
                ? round($grupo['suma_promedios'] / $grupo['total_criterios'], 2)
                : 0;

            $resultados[] = [
                'estudiante_id' => $grupo['estudiante_id'],
                'materia_competencia_id' => $grupo['materia_competencia_id'],
                'materia_id' => $grupo['materia_id'],
                'nombre' => '',  // Se llenará desde el controlador
                'promedio' => $promedio,
                'promedio_cualitativo' => $this->convertirACualitativo($promedio),
                'esta_aprobada' => $promedio >= self::NOTA_MINIMA_APROBACION,
                'criterios' => $grupo['criterios'],
                'total_criterios' => $grupo['total_criterios']
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
