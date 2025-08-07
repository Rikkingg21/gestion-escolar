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
        $user = auth()->user();
        $this->validarAccesoEstudiante($user);

        $estudiante = $this->obtenerEstudiante($user);
        $bimestre_id = $request->input('bimestre_id');
        $anio = $request->input('anio');

        $bimestres = $this->obtenerBimestresConNotas($estudiante);
        $anios = $this->obtenerAniosConNotas($estudiante);
        $grado_id = $estudiante->grado_id;

        $cursos = $this->obtenerCursosEstudiante($grado_id, $anio);
        $materias = $this->obtenerMateriasEstudiante($cursos);
        $notas = $this->obtenerNotasEstudiante($estudiante, $bimestre_id, $anio);
        $notasPorCriterio = $notas->keyBy(fn($n) => $n->criterio->id);

        $detalle = $this->cargarCompetencias($materias, $grado_id, $anio, $notasPorCriterio);
        $bimestre_selected = $bimestre_id ? Bimestre::find($bimestre_id) : null;
        $colegio = $this->cargarColegio();

        return view('libreta.index', [
            'estudiante' => $estudiante,
            'detalle' => $detalle,
            'bimestres' => $bimestres,
            'anios' => $anios,
            'bimestre_id' => $bimestre_id,
            'anio' => $anio,
            'bimestre_selected' => $bimestre_selected,
            'colegio' => $colegio,
        ]);
    }

    protected function validarAccesoEstudiante($user)
    {
        if (!$user || !$user->hasRole('estudiante')) {
            abort(403, 'Solo los estudiantes pueden ver su libreta.');
        }
    }

    protected function obtenerEstudiante($user)
    {
        $estudiante = $user->estudiante;
        if (!$estudiante) {
            abort(404, 'No se encontró información de estudiante.');
        }
        return $estudiante;
    }

    protected function obtenerBimestresConNotas($estudiante)
    {
        return Nota::where('estudiante_id', $estudiante->id)
            ->where('publico', '1')
            ->with('bimestre')
            ->get()
            ->pluck('bimestre')
            ->unique('id')
            ->filter();
    }

    protected function obtenerAniosConNotas($estudiante)
    {
        return Nota::where('estudiante_id', $estudiante->id)
            ->where('publico', '1')
            ->with('criterio')
            ->get()
            ->pluck('criterio.anio')
            ->unique()
            ->filter();
    }

    protected function obtenerCursosEstudiante($grado_id, $anio)
    {
        return Cursogradosecnivanio::with(['materia', 'materia.materiaCompetencia.materiaCriterio'])
            ->where('grado_id', $grado_id)
            ->when($anio, fn($q) => $q->where('anio', $anio))
            ->get();
    }

    protected function obtenerMateriasEstudiante($cursos)
    {
        return $cursos->pluck('materia')->unique('id')->filter();
    }

    protected function obtenerNotasEstudiante($estudiante, $bimestre_id, $anio)
    {
        $notasQuery = Nota::where('estudiante_id', $estudiante->id)
            ->where('publico', '1')
            ->with(['criterio.materiaCompetencia', 'criterio.materia', 'bimestre']);

        if ($bimestre_id) {
            $notasQuery->where('bimestre_id', $bimestre_id);
        }
        if ($anio) {
            $notasQuery->whereHas('criterio', fn($q) => $q->where('anio', $anio));
        }

        return $notasQuery->get();
    }

    protected function cargarCompetencias($materias, $grado_id, $anio, $notasPorCriterio)
    {
        $detalle = [];
        $competenciaGlobalCounter = 1;

        foreach ($materias as $materia) {
            $materiaData = $this->prepararDatosMateria($materia);
            $competencias = $materia->materiaCompetencia;

            if ($competencias->isEmpty()) {
                $materiaData = $this->agregarCompetenciaVacia($materiaData);
            } else {
                foreach ($competencias as $competencia) {
                    $competenciaData = $this->prepararDatosCompetencia($competencia, $competenciaGlobalCounter++);
                    $competenciaData = $this->cargarCriterios($competencia, $competenciaData, $grado_id, $anio, $notasPorCriterio);
                    $competenciaData = $this->calcularPromedios($competenciaData);

                    $materiaData['competencias'][] = $competenciaData;
                    $materiaData['total_criterios'] += max($competenciaData['total_criterios'], 1) + 1;
                }
            }

            $detalle[] = $materiaData;
        }

        return $detalle;
    }

    protected function prepararDatosMateria($materia)
    {
        return [
            'nombre' => $materia->nombre,
            'competencias' => [],
            'total_criterios' => 0
        ];
    }

    protected function agregarCompetenciaVacia($materiaData)
    {
        $materiaData['competencias'][] = [
            'nombre' => 'Aún no hay registro',
            'criterios' => [
                [
                    'nombre' => 'Aún no hay registro',
                    'promedio' => null,
                    'valor' => 'Sin registro',
                    'valor_class' => 'text-muted'
                ]
            ],
            'total_criterios' => 1,
            'codigo_valoracion' => '',
            'total_puntos' => 0,
            'promedio_competencia' => 0,
            'valor_competencia' => '',
            'valor_competencia_class' => '',
        ];
        $materiaData['total_criterios'] = 2;

        return $materiaData;
    }

    protected function prepararDatosCompetencia($competencia, $counter)
    {
        return [
            'nombre' => $competencia->nombre,
            'criterios' => [],
            'total_criterios' => 0,
            'codigo_valoracion' => 'N'.$counter,
            'total_puntos' => 0,
            'promedio_competencia' => 0,
            'valor_competencia' => 'D',
            'valor_competencia_class' => 'valor-d'
        ];
    }

    protected function cargarCriterios($competencia, $competenciaData, $grado_id, $anio, $notasPorCriterio)
    {
        $criterios = $competencia->materiaCriterio->where('grado_id', $grado_id);
        if ($anio) $criterios = $criterios->where('anio', $anio);

        if ($criterios->isEmpty()) {
            $competenciaData['criterios'][] = [
                'nombre' => 'Aún no hay registro',
                'promedio' => null,
                'valor' => 'Sin registro',
                'valor_class' => 'text-muted'
            ];
            $competenciaData['total_criterios'] = 1;
        } else {
            foreach ($criterios as $criterio) {
                $competenciaData = $this->agregarCriterio($competenciaData, $criterio, $notasPorCriterio);
            }
        }

        return $competenciaData;
    }

    protected function agregarCriterio($competenciaData, $criterio, $notasPorCriterio)
    {
        $nota = $notasPorCriterio[$criterio->id] ?? null;

        if ($nota) {
            $promedio = round($nota->nota, 2);
            $valor = $this->getValorLetra($promedio);
        } else {
            $promedio = null;
            $valor = 'Sin registro';
        }

        $competenciaData['criterios'][] = [
            'nombre' => $criterio->nombre,
            'promedio' => $promedio,
            'valor' => $valor,
            'valor_class' => $nota ? $this->getValorClass($valor) : 'text-muted'
        ];

        $competenciaData['total_puntos'] += $promedio ?? 0;
        $competenciaData['total_criterios']++;

        return $competenciaData;
    }

    protected function calcularPromedios($competenciaData)
    {
        if ($competenciaData['total_criterios'] > 0 && $competenciaData['total_puntos'] > 0) {
            $competenciaData['promedio_competencia'] = round($competenciaData['total_puntos'] / $competenciaData['total_criterios'], 2);
            $competenciaData['valor_competencia'] = $this->getValorLetra($competenciaData['promedio_competencia']);
            $competenciaData['valor_competencia_class'] = $this->getValorClass($competenciaData['valor_competencia']);
        } else {
            $competenciaData['valor_competencia'] = 'Sin registro';
            $competenciaData['valor_competencia_class'] = 'text-muted';
        }

        return $competenciaData;
    }

    protected function cargarColegio()
    {
        return \App\Models\Colegio::configuracion();
    }

    protected function getValorLetra($promedio)
    {
        if ($promedio >= 4) return 'AD';
        if ($promedio >= 3) return 'A';
        if ($promedio >= 2) return 'B';
        if ($promedio >= 1) return 'C';
    }

    protected function getValorClass($valor)
    {
        return 'valor-'.strtolower($valor);
    }
}
