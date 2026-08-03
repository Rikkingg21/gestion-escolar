<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'estudiante_notas' => [
            ['idx_notas_periodo_estudiante_criterio', ['periodo_bimestre_id', 'materia_criterio_id', 'estudiante_id']],
            ['idx_notas_estudiante', ['estudiante_id']],
        ],
        'materia_criterios' => [
            ['idx_criterios_materia_grado_periodo', ['materia_id', 'grado_id', 'periodo_bimestre_id']],
            ['idx_criterios_periodo', ['periodo_bimestre_id']],
        ],
        'conducta_periodo_bimestre_notas' => [
            ['idx_cndn_periodo_curso', ['periodo_bimestre_id', 'curso_grado_sec_niv_anio_id']],
            ['idx_cndn_estudiante', ['estudiante_id']],
            ['idx_cndn_conducta_periodo', ['conducta_periodo_bimestre_id']],
        ],
        'conducta_periodo_bimestres' => [
            ['idx_cpb_periodo', ['periodo_bimestre_id']],
            ['idx_cpb_conducta', ['conducta_id']],
        ],
        'matriculas' => [
            ['idx_matriculas_grado_periodo_estado', ['grado_id', 'periodo_id', 'estado']],
            ['idx_matriculas_estudiante', ['estudiante_id']],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as [$name, $columns]) {
                if (Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($name, $columns) {
                    $table->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as [$name, $columns]) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                }
            }
        }
    }
};
