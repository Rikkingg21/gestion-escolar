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
    //moduleID 15 = Libreta
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('15')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
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

        // Si es anual, mostrar datos del primer bimestre como referencia
        $bimestre_para_datos = ($bimestre_nombre === 'anual' && $bimestres->isNotEmpty())
            ? $bimestres->first()->nombre
            : $bimestre_nombre;

        // Obtener cursos y materias
        $cursos = $this->obtenerCursosEstudiante($grado_id, $anio);
        $materias = $this->obtenerMateriasEstudiante($cursos);

        // Obtener notas para el bimestre seleccionado (o primer bimestre si es anual)
        $notas = $this->obtenerNotasEstudiante($estudiante, $bimestre_para_datos, $anio);
        $notasPorCriterio = $notas->keyBy(fn($n) => $n->criterio->id);

        // Cargar competencias
        $detalle = $this->cargarCompetencias($materias, $grado_id, $anio, $notasPorCriterio, $bimestre_para_datos);

        // Bimestre seleccionado
        $bimestre_selected = $bimestre_nombre ? (object)[
            'nombre' => $bimestre_nombre
        ] : null;

        $colegio = $this->cargarColegio();
        $grado_selected = $estudiante->grado;
        $nivel_selected = $grado_selected?->nivel;
        $seccion_selected = $grado_selected?->seccion;

        // --- Notas de Conducta ---
        $conductaNotas = Conductanota::selectRaw('conducta_id, AVG(nota) as promedio')
            ->where('estudiante_id', $estudiante->id)
            ->whereIn('publico', [1, 2])
            ->when($bimestre_nombre !== 'anual', function($query) use ($bimestre_nombre) {
                return $query->where('bimestre', $bimestre_nombre);
            })
            ->groupBy('conducta_id')
            ->with('conducta')
            ->get();

        // --- Asistencias ---
        $asistencias = Asistencia::with('tipoasistencia')
            ->where('estudiante_id', $estudiante->id)
            ->where('grado_id', $grado_id)
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
            'nivel_selected' => $nivel_selected,
            'seccion_selected' => $seccion_selected,
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
        // Obtener bimestres únicos desde Materiacriterio
        return Materiacriterio::where('grado_id', $grado_id)
            ->when($anio, fn($q) => $q->where('anio', $anio))
            ->select('bimestre')
            ->distinct()
            ->orderBy('bimestre')
            ->get()
            ->map(function($item) {
                return (object)[
                    'nombre' => $item->bimestre
                ];
            });
    }

    protected function obtenerAniosConNotas($estudiante)
    {
        $notas = Nota::where('estudiante_id', $estudiante->id)
            ->whereIn('publico', ['1', '2'])
            ->with('criterio')
            ->get();

        return $notas
            ->map(fn($nota) => $nota->criterio?->anio)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
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
            ->whereIn('publico', ["1", "2", "3"])
            ->whereHas('criterio', function($q) use ($bimestre_nombre, $anio) {
                $q->where('bimestre', $bimestre_nombre)
                ->where('anio', $anio);
            })
            ->with([
                'criterio.materiaCompetencia',
                'criterio.materia',
                'criterio.grado'
            ]);

        $notas = $notasQuery->get();

        return $notas;
    }

    protected function cargarCompetencias($materias, $grado_id, $anio, $notasPorCriterio, $bimestre_nombre = null)
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
                    $competenciaData = $this->cargarCriterios($competencia, $competenciaData, $grado_id, $anio, $notasPorCriterio, $bimestre_nombre);
                    $competenciaData = $this->calcularPromedios($competenciaData);

                    // Solo agregar la competencia si tiene criterios con notas en este bimestre
                    if ($competenciaData['total_criterios'] > 0 || !empty($competenciaData['criterios'])) {
                        $materiaData['competencias'][] = $competenciaData;
                        $materiaData['total_criterios'] += max($competenciaData['total_criterios'], 1) + 1;
                    }
                }
            }

            // Solo agregar la materia si tiene competencias con criterios
            if (!empty($materiaData['competencias'])) {
                $detalle[] = $materiaData;
            }
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

    protected function cargarCriterios($competencia, $competenciaData, $grado_id, $anio, $notasPorCriterio, $bimestre_nombre = null)
    {
        $criterios = $competencia->materiaCriterio->where('grado_id', $grado_id);

        if ($anio) {
            $criterios = $criterios->where('anio', $anio);
        }

        // FILTRO CRUCIAL: Filtrar por bimestre
        if ($bimestre_nombre) {
            $criterios = $criterios->where('bimestre', $bimestre_nombre);
        }

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
        return 'D';
    }

    protected function getValorClass($valor)
    {
        return 'valor-'.strtolower($valor);
    }

    public function pdf(Request $request, $anio, $bimestre)
    {
        // Verificar si es anual
        if ($bimestre === 'anual') {
            return $this->generateAnnualPDF($anio);
        }

        // Lógica existente para PDF de bimestre individual
        $data = $this->getLibretaData($anio, $bimestre);

        $pdf = PDF::loadView('libreta.pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $filename = "libreta_{$data['estudiante']->user->apellido_paterno}_{$data['estudiante']->user->apellido_materno}_{$data['estudiante']->user->nombre}_{$anio}_{$data['bimestre_selected']->nombre}.pdf";

        return $pdf->download($filename);
    }
    private function generateAnnualPDF($anio)
    {
        $user = auth()->user();
        $this->validarAccesoEstudiante($user);
        $estudiante = $this->obtenerEstudiante($user);

        // Obtener todos los bimestres disponibles para ese año
        $bimestres = $this->obtenerBimestresUnicos($estudiante->grado_id, $anio);

        $allBimestresData = [];

        foreach ($bimestres as $bimestre) {
            $allBimestresData[$bimestre->nombre] = $this->getLibretaData($anio, $bimestre->nombre);
        }

        $data = [
            'anio' => $anio,
            'bimestre_selected' => (object)['nombre' => 'AÑO COMPLETO'],
            'allBimestresData' => $allBimestresData,
            'colegio' => $this->cargarColegio(),
            'estudiante' => $estudiante,
            'grado_selected' => $estudiante->grado,
            'nivel_selected' => $estudiante->grado?->nivel,
            'seccion_selected' => $estudiante->grado?->seccion,
        ];

        $pdf = PDF::loadView('libreta.pdf_anual', $data);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $filename = "libreta_anual_{$estudiante->user->apellido_paterno}_{$estudiante->user->apellido_materno}_{$estudiante->user->nombre}_{$anio}.pdf";

        return $pdf->download($filename);
    }

    // Método auxiliar para obtener los datos (actualizado)
    private function getLibretaData($anio, $bimestre_nombre)
    {
        $user = auth()->user();
        $this->validarAccesoEstudiante($user);

        $estudiante = $this->obtenerEstudiante($user);
        $grado_id = $estudiante->grado_id;

        // Obtener bimestres y años disponibles
        $bimestres = $this->obtenerBimestresUnicos($grado_id, $anio);
        $anios = $this->obtenerAniosConNotas($estudiante);

        // Obtener cursos y materias - SIN FILTRAR POR NOTAS
        $cursos = $this->obtenerCursosEstudiante($grado_id, $anio);
        $materias = $this->obtenerMateriasEstudiante($cursos);

        // Obtener notas para el bimestre seleccionado
        $notas = $this->obtenerNotasEstudiante($estudiante, $bimestre_nombre, $anio);
        $notasPorCriterio = $notas->keyBy(fn($n) => $n->criterio->id);

        // Cargar competencias con TODAS las materias
        $detalle = $this->cargarCompetencias($materias, $grado_id, $anio, $notasPorCriterio, $bimestre_nombre);

        // Bimestre seleccionado
        $bimestre_selected = $bimestre_nombre ? (object)[
            'nombre' => $bimestre_nombre
        ] : null;

        $colegio = $this->cargarColegio();
        $grado_selected = $estudiante->grado;
        $nivel_selected = $grado_selected?->nivel;
        $seccion_selected = $grado_selected?->seccion;

        // --- Notas de Conducta ---
        $conductaNotas = Conductanota::selectRaw('conducta_id, AVG(nota) as promedio')
            ->where('estudiante_id', $estudiante->id)
            ->whereIn('publico', [1, 2])
            ->where('bimestre', $bimestre_nombre)
            ->groupBy('conducta_id')
            ->with('conducta')
            ->get();

        // --- Asistencias ---
        $asistencias = Asistencia::with('tipoasistencia')
            ->where('estudiante_id', $estudiante->id)
            ->where('grado_id', $grado_id)
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
            'bimestre_nombre' => $bimestre_nombre,
            'anio' => $anio,
            'bimestre_selected' => $bimestre_selected,
            'nivel_selected' => $nivel_selected,
            'seccion_selected' => $seccion_selected,
            'colegio' => $colegio,
            'grado_selected' => $grado_selected,
            'conductaNotas' => $conductaNotas,
            'asistencias' => $asistencias,
            'resumenAsistencias' => $resumenAsistencias,
        ];
    }
}
