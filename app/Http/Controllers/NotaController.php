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
use Illuminate\Support\Facades\Hash;

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
        // 1. Validar parámetros
        if (!in_array($bimestre, ['1', '2', '3', '4'])) {
            abort(404, 'Bimestre no válido.');
        }

        $user = auth()->user();

        // Cargar el curso primero
        $curso = $this->cargarCurso($curso_grado_sec_niv_anio_id);
        if (!$curso) {
            abort(404, 'Curso no encontrado.');
        }

        // Obtener el estado actual
        $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $bimestre);

        // Configuración de etiquetas
        $estadosNotasConfig = [
            '0' => ['Privado', 'secondary'],
            '1' => ['Publicado', 'info'],
            '2' => ['Oficial', 'success'],
            '3' => ['Extra Oficial', 'warning']
        ];

        // Determinar si es el docente asignado a este curso
        $esDocenteDelCurso = $user->hasRole('docente') &&
                            $user->docente &&
                            ($curso->docente_id == $user->docente->id);

        // Lógica: ¿Puede editar las notas?
        $puedeEditar = $user->hasRole('admin') ||
                    $user->hasRole('director') ||
                    ($esDocenteDelCurso && in_array($estadoActual, ['0', '1']));

        // ¿Puede cambiar el estado (Publicar)?
        $puedePublicar = false;
        $textoBotonPublicar = '';

        if ($user->hasRole('admin') && in_array($estadoActual, ['0', '1', '2'])) {
            $puedePublicar = true;
            $textoBotonPublicar = match ($estadoActual) {
                '0' => "Publicar Notas",
                '1' => "Marcar como Oficial",
                '2' => "Marcar como Extra Oficial",
            };
        } elseif ($user->hasRole('director') && in_array($estadoActual, ['0', '1'])) {
            $puedePublicar = true;
            $textoBotonPublicar = match ($estadoActual) {
                '0' => "Publicar Notas",
                '1' => "Marcar como Oficial",
            };
        } elseif ($esDocenteDelCurso && $estadoActual == '0') {
            $puedePublicar = true;
            $textoBotonPublicar = "Publicar Notas";
        }

        // 2. Columnas principales - Cargar estudiantes
        $estudiantes = $this->cargarEstudiantes($curso, $bimestre);

        // 3. Columnas principales - Cargar competencias con estado '1' (Activas) de la materia
        $competencias = $this->cargarCompetencias($curso, $bimestre);

        // 4. Sub columnas - Cargar criterios relacionadas con las competencias
        // (Ya está incluido en cargarCompetencias())

        // 5. Columnas principales - Cargar SIAGIE
        // Filtrar competencias NO transversales para SIAGIE
        $competenciasNoTransversales = $competencias->filter(function($competencia) {
            return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') === false;
        });

        // 6. Sub columnas de SIAGIE
        // Encontrar la competencia TRANSVERSALES y dividir en sus criterios
        $competenciaTransversal = $competencias->first(function($competencia) {
            return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false;
        });

        // Inicializar arrays para cálculos de promedios SIAGIE
        $sumasPorCompetencia = [];
        $contadoresPorCompetencia = [];
        $notasTransversales = [];

        // Inicializar arrays para cada competencia
        foreach($competencias as $competencia) {
            $sumasPorCompetencia[$competencia->id] = 0;
            $contadoresPorCompetencia[$competencia->id] = 0;
        }

        // Inicializar array para notas de cada criterio transversal
        if($competenciaTransversal) {
            foreach($competenciaTransversal->criterios as $criterio) {
                $notasTransversales[$criterio->id] = null;
            }
        }

        // Calcular total de columnas SIAGIE
        $numCompetenciasNoTransversales = $competenciasNoTransversales->count();
        $numCriteriosTransversales = $competenciaTransversal ? $competenciaTransversal->criterios->count() : 0;
        $totalColumnasSIAGIE = $numCompetenciasNoTransversales + $numCriteriosTransversales;

        // 7. Columnas principales - Cargar conductas activas
        $conductas = $this->cargarConductas();

        // 8. Datos de subcolumnas - Cargar estado de notas (tanto para criterios y conducta)
        $notasExistentes = $this->cargarNotasExistentes($curso_grado_sec_niv_anio_id, $bimestre, $competencias, $estudiantes);
        $conductaNotas = $this->cargarConductaNotas($curso_grado_sec_niv_anio_id, $bimestre, $estudiantes);

        // 9. Datos de SIAGIE - Promedios de lo que está llamando
        // (Los cálculos se harán en la vista o en un método adicional según sea necesario)

        return view('nota.index', [
            'user' => $user,
            'estadosNotas' => $estadosNotasConfig,
            'puedeEditar' => $puedeEditar,
            'puedePublicar' => $puedePublicar,
            'textoBotonPublicar' => $textoBotonPublicar,
            'competenciaTransversal' => $competenciaTransversal,
            'competenciasNoTransversales' => $competenciasNoTransversales,
            'totalColumnasSIAGIE' => $totalColumnasSIAGIE,
            'sumasPorCompetencia' => $sumasPorCompetencia,
            'contadoresPorCompetencia' => $contadoresPorCompetencia,
            'notasTransversales' => $notasTransversales,
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

    //Cargar el curso con sus relaciones
    private function cargarCurso($curso_grado_sec_niv_anio_id)
    {
        return Cursogradosecnivanio::with([
                'grado',
                'materia.materiaCompetencia.materiaCriterio',
                'docente.user'
            ])
            ->find($curso_grado_sec_niv_anio_id);
    }


     //Cargar estudiantes activos e inactivos
    private function cargarEstudiantes($curso, $bimestre)
    {
        return [
            'activos' => $this->cargarEstudiantesActivos($curso),
            'inactivos' => $this->cargarEstudiantesInactivos($curso, $bimestre)
        ];
    }


    //Cargar estudiantes activos

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


    //Cargar estudiantes inactivos con notas

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


    //Cargar competencias y criterios para el bimestre específico
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


    //Cargar notas existentes
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


    //Cargar conductas activas
    private function cargarConductas()
    {
        return Conducta::where('estado', "1")
            ->orderBy('nombre')
            ->get();
    }

    //Cargar notas de conducta existentes
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

    //Obtener estado actual de las notas
    private function obtenerEstadoActual($curso_id, $bimestre)
    {
        // Primero verificar si existen notas para este curso y bimestre
        $existenNotas = Nota::whereHas('criterio', function($query) use ($curso_id) {
                $query->whereHas('materiaCompetencia.materia', function($q) use ($curso_id) {
                    $q->whereHas('cursoGradoSecNivAnio', function($cq) use ($curso_id) {
                        $cq->where('id', $curso_id);
                    });
                });
            })
            ->where('bimestre', $bimestre)
            ->exists();

        if (!$existenNotas) {
            return '0';
        }

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

        $estado = $notaEstado ? (string)$notaEstado : '0';

        return $estado;
    }

    public function publicar(Request $request, $curso_grado_sec_niv_anio_id, $bimestre)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $bimestre);

            // Determinar el nuevo estado según el rol y estado actual
            if ($user->hasRole('admin')) {
                // Admin puede avanzar a cualquier estado
                if ($estadoActual == '0') {
                    $nuevoEstado = '1';
                } elseif ($estadoActual == '1') {
                    $nuevoEstado = '2';
                } elseif ($estadoActual == '2') {
                    $nuevoEstado = '3';
                } else {
                    throw new \Exception('Estado actual no válido para publicación.');
                }
            } elseif ($user->hasRole('director')) {
                // Director puede avanzar hasta estado '2'
                if ($estadoActual == '0') {
                    $nuevoEstado = '1';
                } elseif ($estadoActual == '1') {
                    $nuevoEstado = '2';
                } else {
                    throw new \Exception('Director solo puede publicar hasta estado Oficial.');
                }
            } elseif ($user->hasRole('docente')) {
                // Docente puede avanzar hasta estado '1'
                if ($estadoActual == '0') {
                    $nuevoEstado = '1';
                } else {
                    throw new \Exception('Docente solo puede publicar hasta estado Publicado.');
                }
            } else {
                throw new \Exception('No tiene permisos para publicar notas.');
            }

            // Verificar si existen notas antes de actualizar - CORREGIDO
            $notasCount = Nota::whereHas('criterio', function($query) use ($curso_grado_sec_niv_anio_id) {
                $query->whereHas('materiaCompetencia.materia', function($q) use ($curso_grado_sec_niv_anio_id) {
                    $q->whereHas('cursoGradoSecNivAnio', function($cq) use ($curso_grado_sec_niv_anio_id) {
                        $cq->where('id', $curso_grado_sec_niv_anio_id);
                    });
                });
            })
            ->where('bimestre', $bimestre)
            ->count();

            // CORREGIDO: Usar el nombre correcto de la tabla
            $conductaCount = Conductanota::whereIn('estudiante_id', function($query) use ($curso_grado_sec_niv_anio_id) {
                $query->select('estudiantes.id')
                    ->from('estudiantes')
                    ->join('maya_curso_grado_sec_niv_anios', 'estudiantes.grado_id', '=', 'maya_curso_grado_sec_niv_anios.grado_id')
                    ->where('maya_curso_grado_sec_niv_anios.id', $curso_grado_sec_niv_anio_id);
            })
            ->where('bimestre', $bimestre)
            ->count();

            // Actualizar notas de materia
            $updatedNotas = Nota::whereHas('criterio', function($query) use ($curso_grado_sec_niv_anio_id) {
                $query->whereHas('materiaCompetencia.materia', function($q) use ($curso_grado_sec_niv_anio_id) {
                    $q->whereHas('cursoGradoSecNivAnio', function($cq) use ($curso_grado_sec_niv_anio_id) {
                        $cq->where('id', $curso_grado_sec_niv_anio_id);
                    });
                });
            })
            ->where('bimestre', $bimestre)
            ->update(['publico' => $nuevoEstado]);

            // Actualizar notas de conducta - CORREGIDO
            $updatedConducta = Conductanota::whereIn('estudiante_id', function($query) use ($curso_grado_sec_niv_anio_id) {
                $query->select('estudiantes.id')
                    ->from('estudiantes')
                    ->join('maya_curso_grado_sec_niv_anios', 'estudiantes.grado_id', '=', 'maya_curso_grado_sec_niv_anios.grado_id')
                    ->where('maya_curso_grado_sec_niv_anios.id', $curso_grado_sec_niv_anio_id);
            })
            ->where('bimestre', $bimestre)
            ->update(['publico' => $nuevoEstado]);

            DB::commit();

            $estados = ['0' => 'Privado', '1' => 'Publicado', '2' => 'Oficial', '3' => 'Extra Oficial'];

            if ($updatedNotas == 0 && $updatedConducta == 0) {
                throw new \Exception('No se encontraron notas para actualizar. Primero debe guardar algunas calificaciones.');
            }

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'bimestre' => $bimestre
                ])
                ->with('success', "Notas cambiadas a estado: {$estados[$nuevoEstado]}");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'bimestre' => $bimestre
                ])
                ->with('error', 'Error al publicar notas: ' . $e->getMessage());
        }
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
                throw new \Exception('No se puede revertir desde el estado actual: ' . $estadoActual);
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

            // Actualizar notas de conducta - CORREGIDO
            Conductanota::whereIn('estudiante_id', function($query) use ($curso_grado_sec_niv_anio_id) {
                $query->select('estudiantes.id')
                    ->from('estudiantes')
                    ->join('maya_curso_grado_sec_niv_anios', 'estudiantes.grado_id', '=', 'maya_curso_grado_sec_niv_anios.grado_id')
                    ->where('maya_curso_grado_sec_niv_anios.id', $curso_grado_sec_niv_anio_id);
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
    public function guardarNotas(Request $request)
    {
        try {
            DB::beginTransaction();

            $curso_id = $request->curso_id;
            $bimestre = $request->bimestre;
            $notas_criterios = $request->notas ?? [];
            $notas_conductas = $request->conductas ?? [];
            $estadoActual = $this->obtenerEstadoActual($curso_id, $bimestre);

            // 1. Procesar notas de criterios
            foreach ($notas_criterios as $estudiante_id => $criterios) {
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

            // 2. Procesar notas de conductas
            foreach ($notas_conductas as $estudiante_id => $conductas) {
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
                ->with('success', 'Notas guardadas exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_id,
                    'bimestre' => $bimestre
                ])
                ->with('error', 'Error al guardar las notas: ' . $e->getMessage());
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
    public function showRevertirForm($curso_grado_sec_niv_anio_id, $bimestre)
    {
        $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $bimestre);

        return view('nota.revertir-form', [
            'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
            'bimestre' => $bimestre,
            'estadoActual' => $estadoActual
        ]);
    }
}
