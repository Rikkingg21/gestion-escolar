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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NotasExport;
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
            // Verificar acceso al módulo 13
            if (!auth()->user()->canAccessModule('13')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            $user = auth()->user();
            $rolUsuario = $user->roles->first()->nombre;

            // Solo verificar asignación de docente si el usuario tiene rol "docente"
            if ($rolUsuario === 'docente') {
                // Obtener el ID del curso desde la ruta
                $cursoId = $request->route('curso_grado_sec_niv_anio_id');

                if ($cursoId) {
                    $curso = Cursogradosecnivanio::with('docente.user')->find($cursoId);

                    if (!$curso) {
                        abort(404, 'Curso no encontrado.');
                    }

                    // Verificar si el docente está asignado al curso
                    if (!$curso->docente || !$curso->docente->user ||
                        auth()->id() !== $curso->docente->user->id) {
                        abort(403, 'No está asignado como docente de este curso.');
                    }
                } else {
                    abort(400, 'ID de curso no proporcionado.');
                }
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

        // Lógica: ¿Puede guardar las notas?
        $puedeGuardar = false;
        if (($user->hasRole('admin') || $user->hasRole('director') || $user->hasRole('docente')) && in_array($estadoActual, ['0'])) {
            $puedeGuardar = true;
        }

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
        } elseif ($user->hasRole('docente') && in_array($estadoActual, ['0'])) {
            $puedePublicar = true;
            $textoBotonPublicar = "Publicar Notas";
        }

        // logica para revertir la publicación
        $puedeRevertir = false;
        if (($user->hasRole('admin')) && in_array($estadoActual, ['1','2','3'])) {
            $puedeRevertir = true;
        }elseif ($user->hasRole('director') && in_array($estadoActual, ['1','2'])) {
            $puedeRevertir = true;
        }elseif ($user->hasRole('docente') && in_array($estadoActual, ['1'])) {
            $puedeRevertir = true;
        }

        //Columnas principales - Cargar estudiantes
        $estudiantes = $this->cargarEstudiantes($curso, $bimestre);

        //Columnas principales - Cargar competencias con estado '1' (Activas) de la materia
        $competencias = $this->cargarCompetencias($curso, $bimestre);

        // 5. Columnas principales - Cargar SIAGIE
        // Filtrar competencias NO transversales para SIAGIE
        $competenciasNoTransversales = $competencias->filter(function($competencia) {
            return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') === false;
        });

        //Sub columnas de SIAGIE
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

        // Columnas principales - Cargar conductas activas
        $conductas = $this->cargarConductas();

        //Datos de subcolumnas - Cargar estado de notas (tanto para criterios y conducta)
        $notasExistentes = $this->cargarNotasExistentes($curso_grado_sec_niv_anio_id, $bimestre, $competencias, $estudiantes);
        $conductaNotas = $this->cargarConductaNotas($curso_grado_sec_niv_anio_id, $bimestre, $estudiantes);

        //Datos de SIAGIE - Promedios de lo que está llamando
        // (Los cálculos se harán en la vista o en un método adicional según sea necesario)

        return view('nota.index', [
            'user' => $user,
            'estadosNotas' => $estadosNotasConfig,
            'puedeGuardar' => $puedeGuardar,
            'puedePublicar' => $puedePublicar,
            'puedeRevertir' => $puedeRevertir,
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
            } elseif ($user->hasRole('director') || $user->hasRole('docente')) {
                // Director puede avanzar hasta estado '2'
                if ($estadoActual == '0') {
                    $nuevoEstado = '1';
                } elseif ($estadoActual == '1') {
                    $nuevoEstado = '2';
                } else {
                    throw new \Exception('Director solo puede publicar hasta estado Oficial.');
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

            // Solo admin/director/docente puede revertir
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
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
public function exportarExcel($curso_grado_sec_niv_anio_id, $bimestre)
{
    // 1. Validar parámetros (igual que en index)
    if (!in_array($bimestre, ['1', '2', '3', '4'])) {
        abort(404, 'Bimestre no válido.');
    }

    // 2. Cargar el curso
    $curso = $this->cargarCurso($curso_grado_sec_niv_anio_id);
    if (!$curso) {
        abort(404, 'Curso no encontrado.');
    }

    // 3. Cargar todos los datos necesarios (igual que en index)
    $estudiantes = $this->cargarEstudiantes($curso, $bimestre);
    $competencias = $this->cargarCompetencias($curso, $bimestre);
    $competenciasNoTransversales = $competencias->filter(function($competencia) {
        return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') === false;
    });

    $competenciaTransversal = $competencias->first(function($competencia) {
        return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false;
    });

    $notasExistentes = $this->cargarNotasExistentes($curso_grado_sec_niv_anio_id, $bimestre, $competencias, $estudiantes);
    $conductas = $this->cargarConductas();
    $conductaNotas = $this->cargarConductaNotas($curso_grado_sec_niv_anio_id, $bimestre, $estudiantes);

    // 4. Obtener el formato actual
    $formato = request()->get('formato', 'cuantitativo');

    // 5. Generar nombre del archivo
    $nombreArchivo = 'Registro_Notas_'
        . str_replace(' ', '_', $curso->materia->nombre) . '_'
        . $curso->grado->nombre . '_'
        . 'Bimestre_' . $bimestre . '_'
        . date('Ymd_His') . '.xls';

    // 6. Generar contenido Excel
    $excelContent = $this->generarContenidoExcel([
        'curso' => $curso,
        'materia' => $curso->materia,
        'grado' => $curso->grado,
        'docente' => $curso->docente,
        'bimestre' => $bimestre,
        'competencias' => $competencias,
        'competenciasNoTransversales' => $competenciasNoTransversales,
        'competenciaTransversal' => $competenciaTransversal,
        'estudiantesActivos' => $estudiantes['activos'],
        'estudiantesInactivos' => $estudiantes['inactivos'],
        'notasExistentes' => $notasExistentes,
        'conductas' => $conductas,
        'conductaNotas' => $conductaNotas,
        'formato' => $formato,
        'fecha_generacion' => now(),
    ]);

    // 7. Descargar archivo
    return response()->streamDownload(function () use ($excelContent) {
        echo $excelContent;
    }, $nombreArchivo, [
        'Content-Type' => 'application/vnd.ms-excel',
        'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
        'Cache-Control' => 'max-age=0',
    ]);
}

/**
 * Generar contenido Excel en formato HTML (Excel puede abrir HTML)
 */
private function generarContenidoExcel($datos)
{
    ob_start();

    // Inicio del documento HTML que Excel puede abrir
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
          <head>
            <meta charset="UTF-8">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Registro de Notas</x:Name>
                            <x:WorksheetOptions>
                                <x:Print>
                                    <x:ValidPrinterInfo/>
                                    <x:PaperSizeIndex>9</x:PaperSizeIndex>
                                    <x:HorizontalResolution>600</x:HorizontalResolution>
                                    <x:VerticalResolution>600</x:VerticalResolution>
                                </x:Print>
                                <x:Selected/>
                                <x:Panes>
                                    <x:Pane>
                                        <x:Number>3</x:Number>
                                        <x:ActiveRow>1</x:ActiveRow>
                                        <x:ActiveCol>1</x:ActiveCol>
                                    </x:Pane>
                                </x:Panes>
                                <x:ProtectContents>False</x:ProtectContents>
                                <x:ProtectObjects>False</x:ProtectObjects>
                                <x:ProtectScenarios>False</x:ProtectScenarios>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]>
            <style>
                table {
                    border-collapse: collapse;
                    font-family: Arial, sans-serif;
                    font-size: 11px;
                    width: 100%;
                }
                th, td {
                    border: 1px solid #000000;
                    padding: 4px;
                    text-align: center;
                    vertical-align: middle;
                }
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                    color: #000000;
                }
                .titulo {
                    font-size: 16px;
                    font-weight: bold;
                    text-align: center;
                    padding: 10px;
                }
                .subtitulo {
                    font-size: 12px;
                    padding: 5px;
                }
                .encabezado-competencias {
                    background-color: #e8f4f8 !important;
                }
                .encabezado-siagie {
                    background-color: #17a2b8 !important;
                    color: white !important;
                }
                .encabezado-transversales {
                    background-color: #17a2b8 !important;
                    color: white !important;
                }
                .encabezado-conductas {
                    background-color: #ffc107 !important;
                }
                .text-success { color: #28a745; }
                .text-warning { color: #ffc107; }
                .text-danger { color: #dc3545; }
                .text-center { text-align: center; }
                .text-left { text-align: left; }
                .bg-light { background-color: #f8f9fa; }
                .bg-gray { background-color: #e9ecef; }
                .nowrap { white-space: nowrap; }
            </style>
          </head>
          <body>';

    // Título principal
    echo '<table width="100%">
            <tr>
                <td colspan="100" class="titulo">REGISTRO DE NOTAS</td>
            </tr>
            <tr>
                <td colspan="100" class="subtitulo">
                    Materia: ' . htmlspecialchars($datos['materia']->nombre ?? 'N/A') . ' |
                    Grado: ' . htmlspecialchars($datos['grado']->nombre ?? 'N/A') . ' |
                    Bimestre: ' . htmlspecialchars($datos['bimestre']) . ' |
                    Formato: ' . ($datos['formato'] == 'cuantitativo' ? 'Cuantitativo (1-4)' : 'Cualitativo (AD, A, B, C)') . ' |
                    Generado: ' . $datos['fecha_generacion']->format('d/m/Y H:i:s') . '
                </td>
            </tr>
          </table><br>';

    // Inicio de tabla principal
    echo '<table width="100%" cellspacing="0">';

    // Primera fila de encabezados (con rowspan/colspan)
    echo '<tr>';
    echo '<th rowspan="3" width="30">N°</th>';
    echo '<th rowspan="3" width="200" class="text-left">ESTUDIANTES</th>';

    // Encabezados de competencias
    $totalCompetenciasCols = 0;
    foreach ($datos['competencias'] as $competencia) {
        if (!empty($competencia->criterios)) {
            $colspan = count($competencia->criterios);
            $totalCompetenciasCols += $colspan;
            echo '<th colspan="' . $colspan . '" class="encabezado-competencias">' .
                 htmlspecialchars($competencia->nombre) . '<br><small>Competencia</small></th>';
        }
    }

    // Encabezados SIAGIE
    $siagieCols = 0;
    if (!empty($datos['competenciasNoTransversales'])) {
        $siagieCols = count($datos['competenciasNoTransversales']);
        echo '<th colspan="' . $siagieCols . '" class="encabezado-siagie">SIAGIE<br><small>Competencias</small></th>';
    }

    // Encabezados Transversales
    $transversalesCols = 0;
    if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
        $transversalesCols = count($datos['competenciaTransversal']->criterios);
        echo '<th colspan="' . $transversalesCols . '" class="encabezado-transversales">SIAGIE<br><small>Transversales</small></th>';
    }

    // Encabezados Conductas
    $conductasCols = 0;
    if (!empty($datos['conductas'])) {
        $conductasCols = count($datos['conductas']);
        echo '<th colspan="' . $conductasCols . '" class="encabezado-conductas">CONDUCTAS</th>';
    }

    echo '</tr>';

    // Segunda fila de encabezados (nombres de criterios)
    echo '<tr>';

    // Nombres de criterios por competencia
    foreach ($datos['competencias'] as $competencia) {
        if (!empty($competencia->criterios)) {
            foreach ($competencia->criterios as $criterio) {
                echo '<th class="small">' . htmlspecialchars($criterio->nombre) . '</th>';
            }
        }
    }

    // Nombres de competencias SIAGIE
    if (!empty($datos['competenciasNoTransversales'])) {
        foreach ($datos['competenciasNoTransversales'] as $competenciaNT) {
            echo '<th class="small encabezado-siagie">' .
                 htmlspecialchars($competenciaNT->nombre) . '<br><small>Promedio</small></th>';
        }
    }

    // Nombres de criterios transversales
    if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
        foreach ($datos['competenciaTransversal']->criterios as $criterioTrans) {
            echo '<th class="small encabezado-transversales">' .
                 htmlspecialchars($criterioTrans->nombre) . '<br><small>Transversal</small></th>';
        }
    }

    // Nombres de conductas
    if (!empty($datos['conductas'])) {
        foreach ($datos['conductas'] as $conducta) {
            echo '<th class="small encabezado-conductas">' . htmlspecialchars($conducta->nombre) . '</th>';
        }
    }

    echo '</tr>';

    // Tercera fila (vacía para estructura)
    echo '<tr>';
    // No se necesitan más encabezados aquí
    echo '</tr>';

    // Función para formatear nota
    $formatearNota = function($nota) use ($datos) {
        if ($nota === null || $nota === '') {
            return '-';
        }

        if ($datos['formato'] === 'cualitativo') {
            $notaNum = floatval($nota);
            if ($notaNum >= 3.5) return 'AD';
            if ($notaNum >= 2.5) return 'A';
            if ($notaNum >= 1.5) return 'B';
            if ($notaNum >= 1) return 'C';
            return '-';
        }

        return $nota;
    };

    // Función para obtener clase CSS según nota
    $obtenerClaseNota = function($nota, $formato) {
        if ($nota === null || $nota === '' || $nota === '-') {
            return '';
        }

        if ($formato === 'cuantitativo') {
            $notaNum = floatval($nota);
            if ($notaNum >= 3) return 'text-success';
            if ($notaNum == 2) return 'text-warning';
            if ($notaNum == 1) return 'text-danger';
        } else {
            if ($nota === 'AD' || $nota === 'A') return 'text-success';
            if ($nota === 'B') return 'text-warning';
            if ($nota === 'C') return 'text-danger';
        }

        return '';
    };

    // Datos de estudiantes activos
    $numero = 1;
    foreach ($datos['estudiantesActivos'] as $estudiante) {
        echo '<tr>';

        // Número y nombre
        echo '<td class="text-center">' . $numero++ . '</td>';
        echo '<td class="text-left nowrap"><strong>' .
             htmlspecialchars(($estudiante->user->apellido_paterno ?? '') . ' ' .
             ($estudiante->user->apellido_materno ?? '') . ', ' .
             ($estudiante->user->nombre ?? '')) . '</strong></td>';

        // Notas por criterio
        foreach ($datos['competencias'] as $competencia) {
            if (!empty($competencia->criterios)) {
                foreach ($competencia->criterios as $criterio) {
                    $key = $estudiante->id . '-' . $criterio->id;
                    $nota = $datos['notasExistentes'][$key]['nota'] ?? null;
                    $notaFormateada = $formatearNota($nota);
                    $clase = $obtenerClaseNota($notaFormateada, $datos['formato']);
                    echo '<td class="text-center ' . $clase . '"><strong>' . $notaFormateada . '</strong></td>';
                }
            }
        }

        // Promedios SIAGIE
        foreach ($datos['competenciasNoTransversales'] as $competenciaNT) {
            $suma = 0;
            $count = 0;
            if (!empty($competenciaNT->criterios)) {
                foreach ($competenciaNT->criterios as $criterio) {
                    $key = $estudiante->id . '-' . $criterio->id;
                    if (isset($datos['notasExistentes'][$key]['nota'])) {
                        $suma += $datos['notasExistentes'][$key]['nota'];
                        $count++;
                    }
                }
            }
            $promedio = $count > 0 ? round($suma / $count, 1) : null;
            $promedioFormateado = $formatearNota($promedio);
            $clase = $obtenerClaseNota($promedioFormateado, $datos['formato']);
            echo '<td class="text-center bg-light ' . $clase . '"><strong>' . $promedioFormateado . '</strong></td>';
        }

        // Transversales
        if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
            foreach ($datos['competenciaTransversal']->criterios as $criterioTrans) {
                $keyTrans = $estudiante->id . '-' . $criterioTrans->id;
                $notaTrans = $datos['notasExistentes'][$keyTrans]['nota'] ?? null;
                $notaTransFormateada = $formatearNota($notaTrans);
                $clase = $obtenerClaseNota($notaTransFormateada, $datos['formato']);
                echo '<td class="text-center bg-light ' . $clase . '"><strong>' . $notaTransFormateada . '</strong></td>';
            }
        }

        // Conductas
        if (!empty($datos['conductas'])) {
            foreach ($datos['conductas'] as $conducta) {
                $keyCond = $estudiante->id . '-' . $conducta->id;
                $notaCond = $datos['conductaNotas'][$keyCond]['nota'] ?? null;
                $notaCondFormateada = $formatearNota($notaCond);
                $clase = $obtenerClaseNota($notaCondFormateada, $datos['formato']);
                echo '<td class="text-center ' . $clase . '"><strong>' . $notaCondFormateada . '</strong></td>';
            }
        }

        echo '</tr>';
    }

    // Estudiantes inactivos
    if (!empty($datos['estudiantesInactivos']) && $datos['estudiantesInactivos']->count() > 0) {
        echo '<tr><td colspan="' . (2 + $totalCompetenciasCols + $siagieCols + $transversalesCols + $conductasCols) .
             '" class="bg-gray text-center"><strong><i>ESTUDIANTES INACTIVOS CON NOTAS REGISTRADAS</i></strong></td></tr>';

        foreach ($datos['estudiantesInactivos'] as $estudiante) {
            echo '<tr class="text-muted">';

            // Ícono de inactivo
            echo '<td class="text-center"><i>●</i></td>';
            echo '<td class="text-left nowrap">' .
                 htmlspecialchars(($estudiante->user->apellido_paterno ?? '') . ' ' .
                 ($estudiante->user->apellido_materno ?? '') . ', ' .
                 ($estudiante->user->nombre ?? '')) . '<br><small>Inactivo</small></td>';

            // Notas por criterio
            foreach ($datos['competencias'] as $competencia) {
                if (!empty($competencia->criterios)) {
                    foreach ($competencia->criterios as $criterio) {
                        $key = $estudiante->id . '-' . $criterio->id;
                        $nota = $datos['notasExistentes'][$key]['nota'] ?? null;
                        $notaFormateada = $formatearNota($nota);
                        $clase = $obtenerClaseNota($notaFormateada, $datos['formato']);
                        echo '<td class="text-center ' . $clase . '"><strong>' . $notaFormateada . '</strong></td>';
                    }
                }
            }

            // Promedios SIAGIE
            foreach ($datos['competenciasNoTransversales'] as $competenciaNT) {
                $suma = 0;
                $count = 0;
                if (!empty($competenciaNT->criterios)) {
                    foreach ($competenciaNT->criterios as $criterio) {
                        $key = $estudiante->id . '-' . $criterio->id;
                        if (isset($datos['notasExistentes'][$key]['nota'])) {
                            $suma += $datos['notasExistentes'][$key]['nota'];
                            $count++;
                        }
                    }
                }
                $promedio = $count > 0 ? round($suma / $count, 1) : null;
                $promedioFormateado = $formatearNota($promedio);
                $clase = $obtenerClaseNota($promedioFormateado, $datos['formato']);
                echo '<td class="text-center bg-light ' . $clase . '"><strong>' . $promedioFormateado . '</strong></td>';
            }

            // Transversales
            if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
                foreach ($datos['competenciaTransversal']->criterios as $criterioTrans) {
                    $keyTrans = $estudiante->id . '-' . $criterioTrans->id;
                    $notaTrans = $datos['notasExistentes'][$keyTrans]['nota'] ?? null;
                    $notaTransFormateada = $formatearNota($notaTrans);
                    $clase = $obtenerClaseNota($notaTransFormateada, $datos['formato']);
                    echo '<td class="text-center bg-light ' . $clase . '"><strong>' . $notaTransFormateada . '</strong></td>';
                }
            }

            // Conductas
            if (!empty($datos['conductas'])) {
                foreach ($datos['conductas'] as $conducta) {
                    $keyCond = $estudiante->id . '-' . $conducta->id;
                    $notaCond = $datos['conductaNotas'][$keyCond]['nota'] ?? null;
                    $notaCondFormateada = $formatearNota($notaCond);
                    $clase = $obtenerClaseNota($notaCondFormateada, $datos['formato']);
                    echo '<td class="text-center ' . $clase . '"><strong>' . $notaCondFormateada . '</strong></td>';
                }
            }

            echo '</tr>';
        }
    }

    echo '</table>';

    // Leyenda
    echo '<br><table width="100%">
            <tr>
                <td class="subtitulo">
                    <strong>Leyenda:</strong>
                    <span class="text-success">' . ($datos['formato'] == 'cuantitativo' ? '3-4' : 'A-AD') . ' (Satisfactorio)</span> |
                    <span class="text-warning">' . ($datos['formato'] == 'cuantitativo' ? '2' : 'B') . ' (En proceso)</span> |
                    <span class="text-danger">' . ($datos['formato'] == 'cuantitativo' ? '1' : 'C') . ' (En inicio)</span>
                </td>
            </tr>
            <tr>
                <td class="subtitulo">Sistema de Gestión Académica - Documento generado automáticamente</td>
            </tr>
          </table>';

    echo '</body></html>';

    return ob_get_clean();
}
}
