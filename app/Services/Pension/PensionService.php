<?php

namespace App\Services\Pension;

use App\Models\Matricula;
use App\Models\Pension\Pension;
use App\Models\Pension\PensionConfig;
use App\Models\Tramite\Estadopago;

class PensionService
{
    /**
     * Genera las cuotas de una matrícula según la config del periodo+grado.
     */
    public function generarCuotasParaMatricula(Matricula $matricula): int
    {
        $config = PensionConfig::where('periodo_id', $matricula->periodo_id)
            ->where('grado_id', $matricula->grado_id)
            ->where('estado', '1')
            ->first();

        if (! $config) {
            return 0;
        }

        $existentes = Pension::where('matricula_id', $matricula->id)
            ->pluck('config_cuota_id');

        $creadas = 0;

        foreach ($config->cuotas as $cuota) {
            if ($existentes->contains($cuota->id)) {
                continue;
            }

            Pension::create([
                'matricula_id' => $matricula->id,
                'config_cuota_id' => $cuota->id,
                'concepto' => $cuota->concepto,
                'mes' => $cuota->mes,
                'anio' => $cuota->anio,
                'fecha_vencimiento' => $cuota->fecha_vencimiento,
                'monto' => $cuota->monto,
                'monto_pagado' => 0,
                'estado' => Pension::ESTADO_PENDIENTE,
            ]);

            $creadas++;
        }

        return $creadas;
    }

    /**
     * Genera cuotas para todas las matrículas activas de un periodo+grado.
     */
    public function generarCuotasParaConfig(PensionConfig $config): int
    {
        $matriculas = Matricula::where('periodo_id', $config->periodo_id)
            ->where('grado_id', $config->grado_id)
            ->where('estado', '1')
            ->get();

        $total = 0;

        foreach ($matriculas as $matricula) {
            $total += $this->generarCuotasParaMatricula($matricula);
        }

        return $total;
    }

    /**
     * Sincroniza el estado de una pensión según sus registros de pago aprobados.
     */
    public function sincronizarEstadoPension(Pension $pension): void
    {
        $aprobado = Estadopago::where('nombre', 'LIKE', '%Aprobado%')->first();
        $totalAprobado = 0;

        if ($aprobado) {
            $totalAprobado = $pension->pagoRegistros()
                ->where('estado_pago_id', $aprobado->id)
                ->sum('monto');
        }

        $pension->monto_pagado = $totalAprobado;

        if ($pension->monto > 0 && $totalAprobado >= $pension->monto) {
            $pension->estado = Pension::ESTADO_PAGADO;
        } elseif ($pension->estado !== Pension::ESTADO_ANULADO) {
            $pension->estado = Pension::ESTADO_PENDIENTE;
        }

        $pension->save();
    }
}
