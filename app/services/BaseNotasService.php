<?php

namespace App\Services;

abstract class BaseNotasService
{
    // Umbrales de notas - Constantes compartidas
    const NOTA_AD = 3.5;

    const NOTA_A = 2.5;

    const NOTA_B = 1.5;

    /**
     * Convierte una nota numérica a valor cualitativo (ENUM)
     */
    protected function convertirACualitativo(float $nota): string
    {
        if ($nota >= self::NOTA_AD) {
            return 'AD';
        }
        if ($nota >= self::NOTA_A) {
            return 'A';
        }
        if ($nota >= self::NOTA_B) {
            return 'B';
        }

        return 'C';
    }

    /**
     * Convierte ENUM a nota numérica
     */
    public function convertirEnumANota(?string $enum): ?float
    {
        if ($enum === null) {
            return null;
        }

        return match ($enum) {
            'AD' => 4,
            'A' => 3,
            'B' => 2,
            'C' => 1,
            default => null,
        };
    }

    /**
     * Convierte una nota numérica a ENUM para nivel_logro_inicial
     */
    public function convertirNotaAEnum(float $nota): string
    {
        if ($nota >= self::NOTA_AD) {
            return 'AD';
        }
        if ($nota >= self::NOTA_A) {
            return 'A';
        }
        if ($nota >= self::NOTA_B) {
            return 'B';
        }

        return 'C';
    }

    /**
     * Redondea un promedio a 2 decimales
     */
    protected function redondearPromedio(float $valor): float
    {
        return round($valor, 2);
    }

    /**
     * Calcula el promedio desde un array de notas
     */
    protected function calcularPromedioDesdeArray(array $notas): float
    {
        if (empty($notas)) {
            return 0;
        }

        $suma = array_sum($notas);

        return $this->redondearPromedio($suma / count($notas));
    }

    /**
     * Calcula el promedio desde una suma y un total
     */
    protected function calcularPromedioDesdeSuma(float $suma, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return $this->redondearPromedio($suma / $total);
    }
}
