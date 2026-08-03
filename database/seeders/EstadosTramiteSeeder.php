<?php

namespace Database\Seeders;

use App\Models\Tramite\Estadotramite;
use Illuminate\Database\Seeder;

class EstadosTramiteSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['nombre' => 'Pendiente', 'color' => '#f59e0b', 'orden' => 1],
            ['nombre' => 'En proceso', 'color' => '#3b82f6', 'orden' => 2],
            ['nombre' => 'Completado', 'color' => '#10b981', 'orden' => 3],
            ['nombre' => 'Resuelto', 'color' => '#10b981', 'orden' => 4],
            ['nombre' => 'Finalizado', 'color' => '#10b981', 'orden' => 5],
        ];

        foreach ($estados as $estado) {
            Estadotramite::firstOrCreate(['nombre' => $estado['nombre']], $estado);
        }
    }
}
