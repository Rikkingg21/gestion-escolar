<?php

namespace Database\Seeders;

use App\Models\Tramite\Estadopago;
use Illuminate\Database\Seeder;

class EstadosPagoSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['nombre' => 'Pendiente', 'color' => '#f59e0b', 'orden' => 1],
            ['nombre' => 'En revisión', 'color' => '#8b5cf6', 'orden' => 2],
            ['nombre' => 'Aprobado', 'color' => '#10b981', 'orden' => 3],
            ['nombre' => 'Rechazado', 'color' => '#ef4444', 'orden' => 4],
            ['nombre' => 'No requiere pago', 'color' => '#6b7280', 'orden' => 5],
        ];

        foreach ($estados as $estado) {
            Estadopago::firstOrCreate(['nombre' => $estado['nombre']], $estado);
        }
    }
}
