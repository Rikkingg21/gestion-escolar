<?php

namespace App\Services;

class ProcesarnotasMateriaService extends BaseNotasService
{
    public function procesar(array $competencias, array $materias = [], array $competenciasNombres = []): array
    {
        $grupos = [];

        foreach ($competencias as $competencia) {
            $key = $competencia['estudiante_id'].'_'.$competencia['materia_id'];

            if (! isset($grupos[$key])) {
                $grupos[$key] = [
                    'estudiante_id' => $competencia['estudiante_id'],
                    'materia_id' => $competencia['materia_id'],
                    'materia_nombre' => $materias[$competencia['materia_id']] ?? 'Materia',
                    'competencias' => [],
                    'suma_promedios' => 0,
                    'total_competencias' => 0,
                ];
            }

            // Incluir todos los datos de la competencia, incluyendo recuperacion_id
            $competenciaInfo = [
                'id' => $competencia['materia_competencia_id'],
                'nombre' => $competenciasNombres[$competencia['materia_competencia_id']] ?? 'Competencia',
                'promedio_original' => $competencia['promedio_original'],
                'promedio_original_cualitativo' => $competencia['promedio_original_cualitativo'],
                'nota_recuperacion' => $competencia['nota_recuperacion'],
                'promedio_final' => $competencia['promedio_final'],
                'promedio_final_cualitativo' => $competencia['promedio_final_cualitativo'],
                'tiene_recuperacion' => $competencia['tiene_recuperacion'] ?? false,
                'tiene_registro_recuperacion' => $competencia['tiene_registro_recuperacion'] ?? false,
                'recuperacion_estado' => $competencia['recuperacion_estado'] ?? null,
                'recuperacion_id' => $competencia['recuperacion_id'] ?? null,  // Agregar esta línea
            ];

            $grupos[$key]['competencias'][] = $competenciaInfo;
            $grupos[$key]['suma_promedios'] += $competencia['promedio_final'];
            $grupos[$key]['total_competencias']++;
        }

        $resultados = [];

        foreach ($grupos as $grupo) {
            $promedioMateria = $this->calcularPromedioDesdeSuma($grupo['suma_promedios'], $grupo['total_competencias']);

            $resultados[] = [
                'estudiante_id' => $grupo['estudiante_id'],
                'materia_id' => $grupo['materia_id'],
                'materia_nombre' => $grupo['materia_nombre'],
                'promedio' => $promedioMateria,
                'promedio_cualitativo' => $this->convertirACualitativo($promedioMateria),
                'competencias' => $grupo['competencias'],
                'total_competencias' => $grupo['total_competencias'],
            ];
        }

        return $resultados;
    }
}
