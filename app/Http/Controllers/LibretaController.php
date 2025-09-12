<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Asistencia\Asistencia;
use App\Models\Grado;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use App\Models\Colegio;
use App\Models\Conductanota;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function index(Request $request, $anio, $bimestre_nombre)
    {
        $user = auth()->user();
        $this->validarAccesoEstudiante($user);

        $estudiante = $this->obtenerEstudiante($user);
        $grado_id = $estudiante->grado_id;

        // Obtener bimestres y años disponibles
        $bimestres = $this->obtenerBimestresUnicos($grado_id, $anio);
        $anios = $this->obtenerAniosConNotas($estudiante);

        // Obtener cursos, materias y notas
        $cursos = $this->obtenerCursosEstudiante($grado_id, $anio);
        //$materias = $this->obtenerMateriasEstudiante($cursos);
        $notas = $this->obtenerNotasEstudiante($estudiante, $bimestre_nombre, $anio);
        $notasPorCriterio = $notas->keyBy(fn($n) => $n->criterio->id);

        $materiaIdsConNotas = $notas
            ->map(fn($n) => optional($n->criterio->materia)->id)
            ->filter()
            ->unique()
            ->values();
        if ($materiaIdsConNotas->isNotEmpty()) {
            $materias = $this->obtenerMateriasEstudiante($cursos)
                        ->filter(fn($m) => $materiaIdsConNotas->contains($m->id))
                        ->values();
        } else {
            // Si no hay notas, devolvemos una colección vacía para evitar mostrar todas las materias del grado
            $materias = collect();
        }
        $detalle = $this->cargarCompetencias($materias, $grado_id, $anio, $notasPorCriterio);

        // Bimestre y colegio seleccionados
        $bimestre_selected = $bimestre_nombre
        ? Bimestre::where('nombre', $bimestre_nombre)
            ->whereHas('cursoGradoSecNivAnio', function($q) use ($anio, $grado_id) {
                $q->where('anio', $anio)->where('grado_id', $grado_id);
            })->first()
        : null;
        $colegio = $this->cargarColegio();
        $grado_selected = Grado::find($grado_id);

        // --- Notas de Conducta ---
        $conductaNotas = Conductanota::selectRaw('conducta_id, AVG(nota) as promedio')
        ->where('estudiante_id', $estudiante->id)
        ->whereIn('publico', [1, 2])
        ->whereHas('bimestre', function($q) use ($bimestre_nombre, $anio, $grado_id) {
            $q->where('nombre', $bimestre_nombre)
            ->whereHas('cursoGradoSecNivAnio', function($q2) use ($anio, $grado_id) {
                $q2->where('anio', $anio)->where('grado_id', $grado_id);
            });
        })
        ->groupBy('conducta_id')
        ->with('conducta') // para que traiga el nombre de la conducta
        ->get();

        // --- Asistencias ---
        $asistencias = Asistencia::with('tipoasistencia')
        ->where('estudiante_id', $estudiante->id)
        ->where('grado_id', $grado_id)
        ->whereHas('bimestre', function($q) use ($bimestre_nombre, $anio, $grado_id) {
            $q->where('nombre', $bimestre_nombre)
              ->whereHas('cursoGradoSecNivAnio', function($q2) use ($anio, $grado_id) {
                  $q2->where('anio', $anio)->where('grado_id', $grado_id);
              });
        })
        ->whereYear('fecha', $anio)
        ->get();
        $resumenAsistencias = [
            'Puntualidad' => 0,
            'Tardanza' => 0,
            'Falta' => 0,
            'Falta Justificada' => 0,
            'Tardanza Injustificada' => 0,
        ];
        foreach ($asistencias as $asistencia) {
            $tipo = strtolower($asistencia->tipoasistencia->nombre ?? '');
            if (str_contains($tipo, 'puntual')) $resumenAsistencias['Puntualidad']++;
            elseif (str_contains($tipo, 'tardanza injustificada')) $resumenAsistencias['Tardanza Injustificada']++;
            elseif (str_contains($tipo, 'tardanza')) $resumenAsistencias['Tardanza']++;
            elseif (str_contains($tipo, 'falta justificada')) $resumenAsistencias['Falta Justificada']++;
            elseif (str_contains($tipo, 'falta')) $resumenAsistencias['Falta']++;
        }

        return view('libreta.index', [
            'estudiante' => $estudiante,
            'detalle' => $detalle,
            'bimestres' => $bimestres,
            'anios' => $anios,
            'bimestre_nombre' => $bimestre_nombre,
            'anio' => $anio,
            'bimestre_selected' => $bimestre_selected,
            'colegio' => $colegio,
            'grado_selected' => $grado_selected,
            'conductaNotas' => $conductaNotas,
            'asistencias' => $asistencias,
            'resumenAsistencias' => $resumenAsistencias,
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
    protected function obtenerBimestresUnicos($grado_id, $anio)
    {
        return Bimestre::with('cursoGradoSecNivAnio')
            ->whereHas('cursoGradoSecNivAnio', function($q) use ($grado_id, $anio) {
                $q->where('grado_id', $grado_id)
                ->where('anio', $anio);
            })
            ->orderBy('nombre')
            ->get()
            ->unique('nombre')
            ->values();
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

    protected function obtenerNotasEstudiante($estudiante, $bimestre_nombre, $anio)
    {
        $notasQuery = Nota::where('estudiante_id', $estudiante->id)
            ->whereIn('publico', ["1", "2"])
            ->whereHas('bimestre', function($q) use ($bimestre_nombre, $anio) {
                $q->where('nombre', $bimestre_nombre)
                ->whereHas('cursoGradoSecNivAnio', function($q2) use ($anio) {
                    $q2->where('anio', $anio);
                });
            })
            ->with([
                'criterio.materiaCompetencia',
                'criterio.materia',
                'bimestre.cursoGradoSecNivAnio'
            ]);

        $notas = $notasQuery->get();

        return $notas;
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
        return Colegio::configuracion();
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

    public function pdf(Request $request, $anio, $bimestre)
    {
        // Obtener los mismos datos que en el método index
        $data = $this->getLibretaData($anio, $bimestre);

        // Cargar la vista PDF
        $pdf = PDF::loadView('libreta.pdf', $data);

        // Configurar el PDF
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        // Descargar el PDF
        $filename = "libreta_{$data['estudiante']->user->apellido_paterno}_{$anio}_{$data['bimestre_selected']->nombre}.pdf";

        return $pdf->download($filename);
    }

    // Método auxiliar para obtener los datos (si no existe)
    private function getLibretaData($anio, $bimestre_id)
    {
        $user = auth()->user();
        $this->validarAccesoEstudiante($user);

        $estudiante = $this->obtenerEstudiante($user);
        $grado_id = $estudiante->grado_id;

        // Obtener bimestres y años disponibles
        $bimestres = $this->obtenerBimestresUnicos($grado_id, $anio);
        $anios = $this->obtenerAniosConNotas($estudiante);

        // Obtener cursos, materias y notas
        $cursos = $this->obtenerCursosEstudiante($grado_id, $anio);
        $materias = $this->obtenerMateriasEstudiante($cursos);
        $notas = $this->obtenerNotasEstudiante($estudiante, $bimestre_id, $anio);
        $notasPorCriterio = $notas->keyBy(fn($n) => $n->criterio->id);

        // Detalle de competencias y criterios
        $detalle = $this->cargarCompetencias($materias, $grado_id, $anio, $notasPorCriterio);

        // Bimestre y colegio seleccionados
        $bimestre_selected = $bimestre_id ? \App\Models\Maya\Bimestre::find($bimestre_id) : null;
        $colegio = $this->cargarColegio();
        $grado_selected = Grado::find($grado_id);

        // --- Notas de Conducta ---
        $conductaNotas = \App\Models\Conductanota::with(['conducta', 'bimestre.cursoGradoSecNivAnio'])
            ->where('estudiante_id', $estudiante->id)
            ->where('bimestre_id', $bimestre_id)
            ->whereIn('publico', ["1", "2"])
            ->whereHas('bimestre.cursoGradoSecNivAnio', function($q) use ($anio) {
                $q->where('anio', $anio);
            })
            ->get();

        // --- Asistencias ---
        $asistencias = Asistencia::with('tipoasistencia')
            ->where('estudiante_id', $estudiante->id)
            ->where('grado_id', $grado_id)
            ->where('bimestre', $bimestre_id)
            ->whereYear('fecha', $anio)
            ->get();
        $resumenAsistencias = [
            'Puntualidad' => 0,
            'Tardanza' => 0,
            'Falta' => 0,
            'Falta Justificada' => 0,
            'Tardanza Injustificada' => 0,
        ];
        foreach ($asistencias as $asistencia) {
            $tipo = strtolower($asistencia->tipoasistencia->nombre ?? '');
            if (str_contains($tipo, 'puntual')) $resumenAsistencias['Puntualidad']++;
            elseif (str_contains($tipo, 'tardanza injustificada')) $resumenAsistencias['Tardanza Injustificada']++;
            elseif (str_contains($tipo, 'tardanza')) $resumenAsistencias['Tardanza']++;
            elseif (str_contains($tipo, 'falta justificada')) $resumenAsistencias['Falta Justificada']++;
            elseif (str_contains($tipo, 'falta')) $resumenAsistencias['Falta']++;
        }

        return [
            'estudiante' => $estudiante,
            'detalle' => $detalle,
            'bimestres' => $bimestres,
            'anios' => $anios,
            'bimestre_id' => $bimestre_id,
            'anio' => $anio,
            'bimestre_selected' => $bimestre_selected,
            'colegio' => $colegio,
            'grado_selected' => $grado_selected,
            'conductaNotas' => $conductaNotas,
            'asistencias' => $asistencias,
            'resumenAsistencias' => $resumenAsistencias,
        ];
    }
}
