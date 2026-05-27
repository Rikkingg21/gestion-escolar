<?php

namespace App\Services;

use App\Models\Materia\Recuperacioncompetencia;
use Illuminate\Support\Collection;

class ProcesarnotasCompetenciaService
{
    const NOTA_MINIMA_APROBACION = 1.5;
    const NOTA_AD = 3.5;
    const NOTA_A = 2.5;
    const NOTA_B = 1.5;
    const NOTA_C = 0;

    /**
     * Procesa los promedios de criterios y calcula promedios por competencia
     *
     * @param array $criterios Array de criterios procesados
     * @param int|null $periodoRecuperacionId ID del período de recuperación (opcional)
     * @param array $recuperaciones Array de recuperaciones [estudiante_id][competencia_id] => nota
     */
    public function procesar(array $criterios, ?int $periodoRecuperacionId = null, array $recuperaciones = []): array
    {
        // Agrupar por estudiante y competencia
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
                    'suma_promedios' => 0,
                    'tiene_recuperacion' => false,
                    'nota_recuperacion' => null
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

        // Aplicar notas de recuperación si existen
        foreach ($grupos as $key => &$grupo) {
            $estId = $grupo['estudiante_id'];
            $compId = $grupo['materia_competencia_id'];

            if (isset($recuperaciones[$estId][$compId])) {
                $notaRecuperacion = $recuperaciones[$estId][$compId];
                if ($notaRecuperacion !== null) {
                    $grupo['tiene_recuperacion'] = true;
                    $grupo['nota_recuperacion'] = $notaRecuperacion;
                    // Reemplazar el promedio original con la nota de recuperación
                    $grupo['suma_promedios'] = $notaRecuperacion * $grupo['total_criterios'];
                }
            }
        }

        // Calcular promedios finales por competencia
        $resultados = [];

        foreach ($grupos as $grupo) {
            $promedioOriginal = $grupo['total_criterios'] > 0
                ? round($grupo['suma_promedios'] / $grupo['total_criterios'], 2)
                : 0;

            // Si tiene recuperación, la nota final es la de recuperación
            $notaFinal = $grupo['nota_recuperacion'] ?? $promedioOriginal;
            $estaAprobada = $notaFinal >= self::NOTA_MINIMA_APROBACION;

            $resultados[] = [
                'estudiante_id' => $grupo['estudiante_id'],
                'materia_competencia_id' => $grupo['materia_competencia_id'],
                'materia_id' => $grupo['materia_id'],
                'promedio_original' => $promedioOriginal,
                'promedio_original_cualitativo' => $this->convertirACualitativo($promedioOriginal),
                'nota_recuperacion' => $grupo['nota_recuperacion'],
                'nota_final' => $notaFinal,
                'nota_final_cualitativo' => $this->convertirACualitativo($notaFinal),
                'esta_aprobada' => $estaAprobada,
                'tiene_recuperacion' => $grupo['tiene_recuperacion'],
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

    /**
     * Convierte ENUM a nota numérica
     */
    public function convertirEnumANota(?string $enum): ?float
    {
        if ($enum === null) return null;
        return match ($enum) {
            'AD' => 4,
            'A' => 3,
            'B' => 2,
            'C' => 1,
            default => null,
        };
    }
}
