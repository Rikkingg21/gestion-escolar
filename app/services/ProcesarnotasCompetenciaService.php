<?php

namespace App\Services;

class ProcesarnotasCompetenciaService extends BaseNotasService
{
    /**
     * Procesa los promedios de criterios y calcula promedios por competencia
     *
     * @return array Cada elemento tiene: estudiante_id, materia_competencia_id,
     *               materia_id, promedio_original, promedio_original_cualitativo,
     *               nota_recuperacion (si existe), promedio_final
     */
    public function procesar(array $criterios, array $recuperaciones = []): array
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
                    'suma_promedios' => 0,
                    'total_criterios' => 0,
                    'nota_recuperacion' => null,
                    'tiene_recuperacion' => false,
                    'tiene_registro_recuperacion' => false
                ];
            }

            $grupos[$key]['suma_promedios'] += $criterio['promedio'];
            $grupos[$key]['total_criterios']++;
        }

        // Aplicar notas de recuperación si existen
        foreach ($grupos as $key => &$grupo) {
            $estId = $grupo['estudiante_id'];
            $compId = $grupo['materia_competencia_id'];

            if (isset($recuperaciones[$estId][$compId])) {
                $recuperacionInfo = $recuperaciones[$estId][$compId];

                // Verificar si tiene registro (aunque no tenga nota)
                if (isset($recuperacionInfo['tiene_registro']) && $recuperacionInfo['tiene_registro']) {
                    $grupo['tiene_registro_recuperacion'] = true;
                }

                // Verificar si tiene nota de recuperación
                if (isset($recuperacionInfo['nota']) && $recuperacionInfo['nota'] !== null) {
                    $grupo['tiene_recuperacion'] = true;
                    $grupo['nota_recuperacion'] = $recuperacionInfo['nota'];
                    // Reemplazar el promedio original con la nota de recuperación
                    $grupo['suma_promedios'] = $recuperacionInfo['nota'] * $grupo['total_criterios'];
                }
            }
        }

        // Calcular promedios finales
        $resultados = [];

        foreach ($grupos as $grupo) {
            $promedioOriginal = $this->calcularPromedioDesdeSuma($grupo['suma_promedios'], $grupo['total_criterios']);
            $promedioFinal = $grupo['nota_recuperacion'] ?? $promedioOriginal;

            $resultados[] = [
                'estudiante_id' => $grupo['estudiante_id'],
                'materia_competencia_id' => $grupo['materia_competencia_id'],
                'materia_id' => $grupo['materia_id'],
                'promedio_original' => $promedioOriginal,
                'promedio_original_cualitativo' => $this->convertirACualitativo($promedioOriginal),
                'nota_recuperacion' => $grupo['nota_recuperacion'],
                'promedio_final' => $promedioFinal,
                'promedio_final_cualitativo' => $this->convertirACualitativo($promedioFinal),
                'tiene_recuperacion' => $grupo['tiene_recuperacion'],
                'tiene_registro_recuperacion' => $grupo['tiene_registro_recuperacion']
            ];
        }

        return $resultados;
    }
}
