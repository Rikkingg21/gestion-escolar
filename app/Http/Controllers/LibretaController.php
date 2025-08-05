<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LibretaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('apoderado') && !$user->hasRole('estudiante')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }

public function index(Request $request)
{
    // Verificación de usuario y obtención de datos básicos
    $user = auth()->user();
    if (!$user || !$user->hasRole('estudiante')) {
        abort(403, 'Solo los estudiantes pueden ver su libreta.');
    }

    $estudiante = $user->estudiante;
    if (!$estudiante) {
        abort(404, 'No se encontró información de estudiante.');
    }

    // Consultas para filtros
    $grados = \App\Models\Nota::where('estudiante_id', $estudiante->id)
        ->where('publico', '1')
        ->with('criterio.grado')
        ->get()
        ->pluck('criterio.grado')
        ->unique('id')
        ->filter();

    $anios = \App\Models\Nota::where('estudiante_id', $estudiante->id)
        ->where('publico', '1')
        ->with('criterio')
        ->get()
        ->pluck('criterio.anio')
        ->unique()
        ->filter();

    $bimestres = \App\Models\Nota::where('estudiante_id', $estudiante->id)
        ->where('publico', '1')
        ->with('bimestre')
        ->get()
        ->pluck('bimestre')
        ->unique('id')
        ->filter();

    // Aplicación de filtros
    $grado_id = $request->input('grado_id');
    $bimestre_id = $request->input('bimestre_id');
    $anio = $request->input('anio');

    $notasQuery = \App\Models\Nota::where('estudiante_id', $estudiante->id)
        ->where('publico', '1')
        ->with(['criterio.materiaCompetencia', 'criterio.materia', 'bimestre']);

    if ($grado_id) {
        $notasQuery->whereHas('criterio', fn($q) => $q->where('grado_id', $grado_id));
    }
    if ($bimestre_id) {
        $notasQuery->where('bimestre_id', $bimestre_id);
    }
    if ($anio) {
        $notasQuery->whereHas('criterio', fn($q) => $q->where('anio', $anio));
    }

    $notas = $notasQuery->get();

    // Nueva estructura de agrupación con valoración de competencias
    $detalle = [];
    $competenciaGlobalCounter = 1; // Contador para N1, N2, etc.

    foreach ($notas->groupBy(fn($n) => $n->criterio->materia->nombre) as $materiaNombre => $notasMateria) {
        $materiaData = [
            'nombre' => $materiaNombre,
            'competencias' => [],
            'total_criterios' => 0
        ];

        foreach ($notasMateria->groupBy(fn($n) => $n->criterio->materiaCompetencia->nombre) as $compNombre => $notasComp) {
            $competenciaData = [
                'nombre' => $compNombre,
                'criterios' => [],
                'total_criterios' => 0,
                'codigo_valoracion' => 'N'.$competenciaGlobalCounter++,
                'total_puntos' => 0,
                'promedio_competencia' => 0,
                'valor_competencia' => 'D',
                'valor_competencia_class' => 'valor-d'
            ];

            foreach ($notasComp->groupBy(fn($n) => $n->criterio->nombre) as $critNombre => $notasCrit) {
                $promedio = round($notasCrit->avg('nota'), 2);
                $valor = $this->getValorLetra($promedio);

                $competenciaData['criterios'][] = [
                    'nombre' => $critNombre,
                    'promedio' => $promedio,
                    'valor' => $valor,
                    'valor_class' => $this->getValorClass($valor)
                ];

                $competenciaData['total_puntos'] += $promedio;
                $competenciaData['total_criterios']++;
            }

            // Calcular promedio y valoración de la competencia
            if ($competenciaData['total_criterios'] > 0) {
                $competenciaData['promedio_competencia'] = round($competenciaData['total_puntos'] / $competenciaData['total_criterios'], 2);
                $competenciaData['valor_competencia'] = $this->getValorLetra($competenciaData['promedio_competencia']);
                $competenciaData['valor_competencia_class'] = $this->getValorClass($competenciaData['valor_competencia']);
            }

            $materiaData['competencias'][] = $competenciaData;
            $materiaData['total_criterios'] += $competenciaData['total_criterios'] + 1; // +1 para la fila de valoración
        }

        $detalle[] = $materiaData;
    }

    $grado_selected = $grado_id ? \App\Models\Grado::find($grado_id) : null;
    $bimestre_selected = $bimestre_id ? Bimestre::find($bimestre_id) : null;

    return view('libreta.index', [
        'estudiante' => $estudiante,
        'detalle' => $detalle,
        'grados' => $grados,
        'bimestres' => $bimestres,
        'anios' => $anios,
        'grado_id' => $grado_id,
        'bimestre_id' => $bimestre_id,
        'anio' => $anio,
        'grado_selected' => $grado_selected,
        'bimestre_selected' => $bimestre_selected,
    ]);
}

protected function getValorLetra($promedio)
{
    if ($promedio >= 4) return 'AD';
    if ($promedio >= 3) return 'A';
    if ($promedio >= 2) return 'B';
    if ($promedio >= 1) return 'C';
    return 'D';
}

protected function getValorClass($valor)
{
    return 'valor-'.strtolower($valor);
}
}
