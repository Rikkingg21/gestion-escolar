<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Estudiante;
use App\Models\Conducta;
use App\Models\Conductanota;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotaController extends Controller
{
        //moduleID 13 = Roles
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('13')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }

    public function index($curso_grado_sec_niv_anio_id, $bimestre)
    {
        // Validar que el bimestre sea válido
        if (!in_array($bimestre, ['1', '2', '3', '4'])) {
            abort(404, 'Bimestre no válido.');
        }

        // Cargar el curso con sus relaciones
        $curso = $this->cargarCurso($curso_grado_sec_niv_anio_id);

        if (!$curso) {
            abort(404, 'Curso no encontrado.');
        }

        // Cargar estudiantes
        $estudiantes = $this->cargarEstudiantes($curso, $bimestre);

        // Cargar competencias y criterios
        $competencias = $this->cargarCompetencias($curso, $bimestre);

        // Cargar notas existentes
        $notasExistentes = $this->cargarNotasExistentes($curso_grado_sec_niv_anio_id, $bimestre, $competencias, $estudiantes);

        // Cargar conductas
        $conductas = $this->cargarConductas();

        // Cargar notas de conducta
        $conductaNotas = $this->cargarConductaNotas($curso_grado_sec_niv_anio_id, $bimestre, $estudiantes);

        // Obtener estado actual
        $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $bimestre);

        return view('nota.index', [
            'curso_id' => $curso_grado_sec_niv_anio_id,
            'bimestre' => $bimestre,
            'curso' => $curso,
            'materia' => $curso->materia,
            'grado' => $curso->grado,
            'docente' => $curso->docente,
            'competencias' => $competencias,
            'estudiantesActivos' => $estudiantes['activos'],
            'estudiantesInactivos' => $estudiantes['inactivos'],
            'notasExistentes' => $notasExistentes,
            'estadoActual' => $estadoActual,
            'conductas' => $conductas,
            'conductaNotas' => $conductaNotas
        ]);
    }
    /**
     * Cargar el curso con sus relaciones
     */
    private function cargarCurso($curso_grado_sec_niv_anio_id)
    {
        return Cursogradosecnivanio::with([
                'grado',
                'materia.materiaCompetencia.materiaCriterio',
                'docente.user'
            ])
            ->find($curso_grado_sec_niv_anio_id);
    }

    /**
     * Cargar estudiantes activos e inactivos
     */
    private function cargarEstudiantes($curso, $bimestre)
    {
        return [
            'activos' => $this->cargarEstudiantesActivos($curso),
            'inactivos' => $this->cargarEstudiantesInactivos($curso, $bimestre)
        ];
    }

    /**
     * Cargar estudiantes activos
     */
    private function cargarEstudiantesActivos($curso)
    {
        return Estudiante::with(['user'])
            ->where('grado_id', $curso->grado_id)
            ->where('estado', '1')
            ->orderByRaw("
                (SELECT apellido_paterno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT apellido_materno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT nombre FROM users WHERE users.id = estudiantes.user_id)
            ")
            ->get();
    }

    /**
     * Cargar estudiantes inactivos con notas
     */
    private function cargarEstudiantesInactivos($curso, $bimestre)
    {
        return Estudiante::with(['user'])
            ->where('grado_id', $curso->grado_id)
            ->where('estado', '0')
            ->whereHas('notas', function($query) use ($curso, $bimestre) {
                $query->where('bimestre', $bimestre)
                    ->whereHas('criterio', function($q) use ($curso) {
                        $q->where('materia_id', $curso->materia_id);
                    });
            })
            ->orderByRaw("
                (SELECT apellido_paterno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT apellido_materno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT nombre FROM users WHERE users.id = estudiantes.user_id)
            ")
            ->get();
    }

    /**
     * Cargar competencias y criterios para el bimestre específico
     */
    private function cargarCompetencias($curso, $bimestre)
    {
        $competencias = $curso->materia->materiaCompetencia->map(function($competencia) use ($curso, $bimestre) {
            $competencia->criterios = $competencia->materiaCriterio
                ->where('grado_id', $curso->grado_id)
                ->where('anio', $curso->anio)
                ->where('bimestre', $bimestre)
                ->values();
            return $competencia;
        });

        return $competencias->filter(fn($c) => $c->criterios->isNotEmpty());
    }

    /**
     * Cargar notas existentes
     */
    private function cargarNotasExistentes($curso_id, $bimestre, $competencias, $estudiantes)
    {
        $criteriosIds = $competencias->flatMap->criterios->pluck('id');

        $estudianteIds = $estudiantes['activos']->pluck('id')
            ->merge($estudiantes['inactivos']->pluck('id'));

        return Nota::where('bimestre', $bimestre)
            ->whereIn('materia_criterio_id', $criteriosIds)
            ->whereIn('estudiante_id', $estudianteIds)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item['estudiante_id'].'-'.$item['materia_criterio_id'] => [
                        'nota' => $item['nota'],
                        'publico' => $item['publico']
                    ]
                ];
            });
    }

    /**
     * Cargar conductas activas
     */
    private function cargarConductas()
    {
        return Conducta::where('estado', "1")
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Cargar notas de conducta existentes
     */
    private function cargarConductaNotas($curso_id, $bimestre, $estudiantes)
    {
        $estudianteIds = $estudiantes['activos']->pluck('id')
            ->merge($estudiantes['inactivos']->pluck('id'));

        return Conductanota::where('bimestre', $bimestre)
            ->whereIn('estudiante_id', $estudianteIds)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item['estudiante_id'].'-'.$item['conducta_id'] => [
                        'nota' => $item['nota'],
                        'publico' => $item['publico']
                    ]
                ];
            });
    }

    /**
     * Obtener estado actual de las notas
     */
    private function obtenerEstadoActual($curso_id, $bimestre)
    {
        // Obtener el estado más alto de las notas para este curso y bimestre
        $notaEstado = Nota::whereHas('criterio', function($query) use ($curso_id) {
                $query->whereHas('materiaCompetencia.materia', function($q) use ($curso_id) {
                    $q->whereHas('cursoGradoSecNivAnio', function($cq) use ($curso_id) {
                        $cq->where('id', $curso_id);
                    });
                });
            })
            ->where('bimestre', $bimestre)
            ->max('publico');

        // Convertir a string y manejar null
        $estado = $notaEstado ? (string)$notaEstado : '0';

        return $estado;
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $curso_id = $request->curso_id;
            $bimestre = $request->bimestre;
            $notas = $request->notas ?? [];
            $estadoActual = $this->obtenerEstadoActual($curso_id, $bimestre);

            foreach ($notas as $estudiante_id => $criterios) {
                foreach ($criterios as $criterio_id => $nota) {
                    // Solo procesar si la nota tiene valor (no vacío)
                    if ($nota !== null && $nota !== '') {
                        $nota = intval($nota);

                        // Validar que la nota esté en el rango permitido (1-4)
                        if ($nota < 1 || $nota > 4) {
                            continue;
                        }

                        // Buscar si ya existe una nota
                        $notaExistente = Nota::where('estudiante_id', $estudiante_id)
                            ->where('materia_criterio_id', $criterio_id)
                            ->where('bimestre', $bimestre)
                            ->first();

                        if ($notaExistente) {
                            // Solo actualizar si el estado actual permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                $notaExistente->update([
                                    'nota' => $nota,
                                    // Mantener el estado 'publico' existente
                                ]);
                            }
                        } else {
                            // Crear nueva nota solo si se permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                Nota::create([
                                    'estudiante_id' => $estudiante_id,
                                    'materia_criterio_id' => $criterio_id,
                                    'bimestre' => $bimestre,
                                    'nota' => $nota,
                                    'publico' => $estadoActual // Usar el estado actual del bimestre
                                ]);
                            }
                        }
                    } else {
                        // Si la nota está vacía, eliminar solo si se permite edición
                        if ($this->puedeEditarNota($estadoActual)) {
                            $notaExistente = Nota::where('estudiante_id', $estudiante_id)
                                ->where('materia_criterio_id', $criterio_id)
                                ->where('bimestre', $bimestre)
                                ->first();

                            if ($notaExistente) {
                                $notaExistente->delete();
                            }
                        }
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_id,
                    'bimestre' => $bimestre
                ])
                ->with('success', 'Calificaciones guardadas exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_id,
                    'bimestre' => $bimestre
                ])
                ->with('error', 'Error al guardar las calificaciones: ' . $e->getMessage());
        }
    }
    public function storeConductaNotas(Request $request)
    {
        try {
            DB::beginTransaction();

            $curso_id = $request->curso_id;
            $bimestre = $request->bimestre;
            $notas_conducta = $request->notas_conducta ?? [];
            $estadoActual = $this->obtenerEstadoActual($curso_id, $bimestre);

            foreach ($notas_conducta as $estudiante_id => $conductas) {
                foreach ($conductas as $conducta_id => $nota) {
                    // Solo procesar si la nota tiene valor (no vacío)
                    if ($nota !== null && $nota !== '') {
                        $nota = intval($nota);

                        // Validar que la nota esté en el rango permitido (1-4)
                        if ($nota < 1 || $nota > 4) {
                            continue;
                        }

                        // Buscar si ya existe una nota de conducta
                        $notaConductaExistente = Conductanota::where('estudiante_id', $estudiante_id)
                            ->where('conducta_id', $conducta_id)
                            ->where('bimestre', $bimestre)
                            ->first();

                        if ($notaConductaExistente) {
                            // Solo actualizar si el estado actual permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                $notaConductaExistente->update([
                                    'nota' => $nota,
                                ]);
                            }
                        } else {
                            // Crear nueva nota solo si se permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                Conductanota::create([
                                    'estudiante_id' => $estudiante_id,
                                    'conducta_id' => $conducta_id,
                                    'bimestre' => $bimestre,
                                    'nota' => $nota,
                                    'publico' => $estadoActual // Usar el estado actual del bimestre
                                ]);
                            }
                        }
                    } else {
                        // Si la nota está vacía, eliminar solo si se permite edición
                        if ($this->puedeEditarNota($estadoActual)) {
                            $notaConductaExistente = Conductanota::where('estudiante_id', $estudiante_id)
                                ->where('conducta_id', $conducta_id)
                                ->where('bimestre', $bimestre)
                                ->first();

                            if ($notaConductaExistente) {
                                $notaConductaExistente->delete();
                            }
                        }
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_id,
                    'bimestre' => $bimestre
                ])
                ->with('success', 'Notas de conducta guardadas exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_id,
                    'bimestre' => $bimestre
                ])
                ->with('error', 'Error al guardar las notas de conducta: ' . $e->getMessage());
        }
    }
    public function publicar(Request $request, $curso_grado_sec_niv_anio_id, $bimestre)
    {

    }


    public function revertir(Request $request, $curso_grado_sec_niv_anio_id, $bimestre)
    {
        try {
            $user = auth()->user();

            // Solo admin/director puede revertir
            if (!$user->hasRole('admin') && !$user->hasRole('director')) {
                throw new \Exception('No tiene permisos para revertir la publicación.');
            }

            DB::beginTransaction();

            $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $bimestre);

            // Determinar el estado anterior según la lógica de reversión
            if ($estadoActual == '3') {
                $nuevoEstado = '2';
            } elseif ($estadoActual == '2') {
                $nuevoEstado = '1';
            } elseif ($estadoActual == '1') {
                $nuevoEstado = '0';
            } else {
                throw new \Exception('No se puede revertir desde el estado actual.');
            }

            // Actualizar notas de materia
            Nota::whereHas('criterio', function($query) use ($curso_grado_sec_niv_anio_id) {
                $query->whereHas('materiaCompetencia.materia', function($q) use ($curso_grado_sec_niv_anio_id) {
                    $q->whereHas('cursoGradoSecNivAnio', function($cq) use ($curso_grado_sec_niv_anio_id) {
                        $cq->where('id', $curso_grado_sec_niv_anio_id);
                    });
                });
            })
            ->where('bimestre', $bimestre)
            ->update(['publico' => $nuevoEstado]);

            // Actualizar notas de conducta
            Conductanota::whereIn('estudiante_id', function($query) use ($curso_grado_sec_niv_anio_id) {
                $query->select('estudiantes.id')
                    ->from('estudiantes')
                    ->join('cursogradosecnivanios', 'estudiantes.grado_id', '=', 'cursogradosecnivanios.grado_id')
                    ->where('cursogradosecnivanios.id', $curso_grado_sec_niv_anio_id);
            })
            ->where('bimestre', $bimestre)
            ->update(['publico' => $nuevoEstado]);

            DB::commit();

            $estados = ['0' => 'Privado', '1' => 'Publicado', '2' => 'Oficial', '3' => 'Extra Oficial'];
            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'bimestre' => $bimestre
                ])
                ->with('success', "Notas revertidas a estado: {$estados[$nuevoEstado]}");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'bimestre' => $bimestre
                ])
                ->with('error', 'Error al revertir publicación: ' . $e->getMessage());
        }
    }
    private function puedeEditarNota($estadoActual)
    {
        $user = auth()->user();

        // Admin y Director pueden editar en cualquier estado
        if ($user->hasRole('admin') || $user->hasRole('director')) {
            return true;
        }

        // Docente solo puede editar en estados 0 y 1
        if ($user->hasRole('docente')) {
            return in_array($estadoActual, ['0', '1']);
        }

        return false;
    }
}
