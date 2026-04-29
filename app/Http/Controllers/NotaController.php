<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Estudiante;
use App\Models\Conducta;
use App\Models\Conductanota;
use App\Models\Periodo;
use App\Models\Periodobimestre;
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
use Barryvdh\DomPDF\Facade\Pdf;

class NotaController extends Controller
{
        //moduleID 13 = Mayas
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
                // Obtener el ID del curso desde la ruta o el request
                $cursoId = $request->route('curso_grado_sec_niv_anio_id') ?? $request->input('curso_id');

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

    public function index($curso_grado_sec_niv_anio_id, $periodo_bimestre_id)
    {
        // 1. Validar parámetros
        $periodoBimestre = Periodobimestre::find($periodo_bimestre_id);
        if (!$periodoBimestre) {
            abort(404, 'Bimestre no encontrado.');
        }

        $user = auth()->user();

        // Cargar el curso primero
        $curso = $this->cargarCurso($curso_grado_sec_niv_anio_id);
        if (!$curso) {
            abort(404, 'Curso no encontrado.');
        }

        // Obtener el periodo del curso
        $periodo = $curso->periodo;

        // Obtener el estado actual
        $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $periodo_bimestre_id);

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
        $estudiantes = $this->cargarEstudiantes($curso, $periodo_bimestre_id);

        //Columnas principales - Cargar competencias con estado '1' (Activas) de la materia
        $competencias = $this->cargarCompetencias($curso, $periodo_bimestre_id);

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
        $notasExistentes = $this->cargarNotasExistentes($curso_grado_sec_niv_anio_id, $periodo_bimestre_id, $competencias, $estudiantes);
        $conductaNotas = $this->cargarConductaNotas($curso_grado_sec_niv_anio_id, $periodo_bimestre_id, $estudiantes);

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
            'periodo_bimestre_id' => $periodo_bimestre_id,
            'periodoBimestre' => $periodoBimestre,
            'curso' => $curso,
            'materia' => $curso->materia,
            'grado' => $curso->grado,
            'docente' => $curso->docente,
            'competencias' => $competencias,
            'estudiantesMatriculadosActivos' => $estudiantes['activos'],
            'estudiantesMatriculadosRetirados' => $estudiantes['retirados'],
            'notasExistentes' => $notasExistentes,
            'estadoActual' => $estadoActual,
            'conductas' => $conductas,
            'conductaNotas' => $conductaNotas,
            'periodo' => $periodo
        ]);
    }

    //Cargar el curso con sus relaciones
    private function cargarCurso($id)
    {
        return CursoGradoSecNivAnio::with(['materia', 'grado', 'docente.user', 'periodo'])
            ->find($id);
    }

    //Cargar estudiantes activos e inactivos
    private function cargarEstudiantes($curso, $periodo_bimestre_id)
    {
        return [
            'activos' => $this->cargarEstudiantesMatriculadosActivos($curso),
            'retirados' => $this->cargarEstudiantesMatriculadosRetirados($curso, $periodo_bimestre_id)
        ];
    }

    //Cargar estudiantes activos
    private function cargarEstudiantesMatriculadosActivos($curso)
    {
        return Estudiante::with(['user'])
            ->where('estado', '1') // Estado activo del estudiante
            ->whereHas('matriculas', function($query) use ($curso) {
                $query->where('estado', '1') // Matrícula activa
                    ->where('grado_id', $curso->grado_id)
                    ->where('periodo_id', $curso->periodo_id);
            })
            ->orderByRaw("
                (SELECT apellido_paterno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT apellido_materno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT nombre FROM users WHERE users.id = estudiantes.user_id)
            ")
            ->get();
    }

    private function cargarEstudiantesMatriculadosRetirados($curso, $periodo_bimestre_id)
    {
        return Estudiante::with(['user'])
            ->whereHas('matriculas', function($query) use ($curso) {
                $query->where('estado', '0')
                    ->where('grado_id', $curso->grado_id)
                    ->where('periodo_id', $curso->periodo_id);
            })
            ->where(function($query) use ($curso, $periodo_bimestre_id) {
                // Estudiantes que tienen notas en este bimestre y materia
                $query->whereHas('notas', function($q) use ($curso, $periodo_bimestre_id) {
                    $q->where('periodo_bimestre_id', $periodo_bimestre_id)
                        ->whereHas('criterio', function($criteriaQuery) use ($curso) {
                            $criteriaQuery->where('materia_id', $curso->materia_id);
                        });
                })
                // O estudiantes que están inactivos (estado 0)
                ->orWhere('estado', '0');
            })
            ->orderByRaw("
                (SELECT apellido_paterno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT apellido_materno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT nombre FROM users WHERE users.id = estudiantes.user_id)
            ")
            ->get()
            ->unique('id');
    }

    //Cargar competencias y criterios para el bimestre específico
    private function cargarCompetencias($curso, $periodo_bimestre_id)
    {
        $competencias = $curso->materia->materiaCompetencia->map(function($competencia) use ($curso, $periodo_bimestre_id) {
            $competencia->criterios = $competencia->materiaCriterio
                ->where('grado_id', $curso->grado_id)
                ->where('periodo_bimestre_id', $periodo_bimestre_id)
                ->values();
            return $competencia;
        });
        return $competencias->filter(fn($c) => $c->criterios->isNotEmpty());
    }

    //Cargar notas existentes
    private function cargarNotasExistentes($curso_id, $periodo_bimestre_id, $competencias, $estudiantes)
    {
        $criteriosIds = $competencias->flatMap->criterios->pluck('id');
        $estudianteIds = $estudiantes['activos']->pluck('id')
            ->merge($estudiantes['retirados']->pluck('id'));

        if ($criteriosIds->isEmpty() || $estudianteIds->isEmpty()) {
            return collect();
        }

        $notas = Nota::where('periodo_bimestre_id', $periodo_bimestre_id)
            ->whereIn('materia_criterio_id', $criteriosIds)
            ->whereIn('estudiante_id', $estudianteIds)
            ->get();

        return $notas->mapWithKeys(function ($item) {
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
    private function cargarConductaNotas($curso_grado_sec_niv_anio_id, $periodo_bimestre_id, $estudiantes)
    {
        $estudianteIds = $estudiantes['activos']->pluck('id')
            ->merge($estudiantes['retirados']->pluck('id'));

        if ($estudianteIds->isEmpty()) {
            return collect();
        }

        return Conductanota::where('periodo_bimestre_id', $periodo_bimestre_id)
            ->where('curso_grado_sec_niv_anio_id', $curso_grado_sec_niv_anio_id)
            ->whereIn('estudiante_id', $estudianteIds)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->estudiante_id . '-' . $item->conducta_id => [
                        'nota' => $item->nota ?? 0,
                        'publico' => $item->publico ?? false
                    ]
                ];
            });
    }
    //Obtener estado actual de las notas
    private function obtenerEstadoActual($curso_id, $periodo_bimestre_id)
    {
        $curso = CursoGradoSecNivAnio::find($curso_id);
        if (!$curso) {
            return '0';
        }

        // Cargar estudiantes
        $estudiantes = $this->cargarEstudiantes($curso, $periodo_bimestre_id);
        $estudianteIds = $estudiantes['activos']->pluck('id')
            ->merge($estudiantes['retirados']->pluck('id'));

        if ($estudianteIds->isEmpty()) {
            return '0';
        }

        // Cargar competencias para obtener criterios
        $competencias = $this->cargarCompetencias($curso, $periodo_bimestre_id);
        $criteriosIds = $competencias->flatMap->criterios->pluck('id');

        // Verificar si existen notas de materia
        $existenNotasMateria = false;
        if ($criteriosIds->isNotEmpty()) {
            $existenNotasMateria = Nota::where('periodo_bimestre_id', $periodo_bimestre_id)
                ->whereIn('materia_criterio_id', $criteriosIds)
                ->whereIn('estudiante_id', $estudianteIds)
                ->exists();
        }

        // Verificar si existen notas de conducta
        $existenNotasConducta = Conductanota::where('periodo_bimestre_id', $periodo_bimestre_id)
            ->where('curso_grado_sec_niv_anio_id', $curso_id)
            ->whereIn('estudiante_id', $estudianteIds)
            ->exists();

        // Si no hay notas de ningún tipo, retornar '0'
        if (!$existenNotasMateria && !$existenNotasConducta) {
            return '0';
        }

        // Obtener el estado más alto de las notas de materia
        $estadoMateria = 0;
        if ($criteriosIds->isNotEmpty()) {
            $estadoMateria = Nota::where('periodo_bimestre_id', $periodo_bimestre_id)
                ->whereIn('materia_criterio_id', $criteriosIds)
                ->whereIn('estudiante_id', $estudianteIds)
                ->max('publico') ?? 0;
        }

        // Obtener el estado más alto de las notas de conducta
        $estadoConducta = Conductanota::where('periodo_bimestre_id', $periodo_bimestre_id)
            ->where('curso_grado_sec_niv_anio_id', $curso_id)
            ->whereIn('estudiante_id', $estudianteIds)
            ->max('publico') ?? 0;

        // Tomar el estado más alto entre materia y conducta
        $estadoFinal = max((int)$estadoMateria, (int)$estadoConducta);

        return (string)$estadoFinal;
    }

    public function publicar(Request $request, $curso_grado_sec_niv_anio_id, $periodo_bimestre_id)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            // OBTENER EL CURSO
            $curso = CursoGradoSecNivAnio::find($curso_grado_sec_niv_anio_id);
            if (!$curso) {
                throw new \Exception('Curso no encontrado.');
            }

            // OBTENER EL PERIODO BIMESTRE
            $periodoBimestre = Periodobimestre::find($periodo_bimestre_id);
            if (!$periodoBimestre) {
                throw new \Exception('Bimestre no encontrado.');
            }

            $periodo_id = $curso->periodo_id;
            $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $periodo_bimestre_id);

            // Determinar el nuevo estado según el rol y estado actual
            if ($user->hasRole('admin')) {
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

            // Cargar estudiantes (activos y retirados)
            $estudiantes = $this->cargarEstudiantes($curso, $periodo_bimestre_id);
            $estudianteIds = $estudiantes['activos']->pluck('id')
                ->merge($estudiantes['retirados']->pluck('id'));

            // Cargar competencias para obtener criterios
            $competencias = $this->cargarCompetencias($curso, $periodo_bimestre_id);
            $criteriosIds = $competencias->flatMap->criterios->pluck('id');

            // Actualizar notas de materia
            $updatedNotas = Nota::where('periodo_bimestre_id', $periodo_bimestre_id)
                ->whereIn('materia_criterio_id', $criteriosIds)
                ->whereIn('estudiante_id', $estudianteIds)
                ->update(['publico' => $nuevoEstado]);

            // Actualizar notas de conducta
            $updatedConducta = Conductanota::where('periodo_bimestre_id', $periodo_bimestre_id)
                ->where('curso_grado_sec_niv_anio_id', $curso_grado_sec_niv_anio_id)
                ->whereIn('estudiante_id', $estudianteIds)
                ->update(['publico' => $nuevoEstado]);

            DB::commit();

            $estados = ['0' => 'Privado', '1' => 'Publicado', '2' => 'Oficial', '3' => 'Extra Oficial'];

            if ($updatedNotas == 0 && $updatedConducta == 0) {
                throw new \Exception('No se encontraron notas para actualizar. Primero debe guardar algunas calificaciones.');
            }

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'periodo_bimestre_id' => $periodo_bimestre_id
                ])
                ->with('success', "Notas cambiadas a estado: {$estados[$nuevoEstado]}");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'periodo_bimestre_id' => $periodo_bimestre_id
                ])
                ->with('error', 'Error al publicar notas: ' . $e->getMessage());
        }
    }
    public function revertir(Request $request, $curso_grado_sec_niv_anio_id, $periodo_bimestre_id)
    {
        try {
            $user = auth()->user();

            // Solo admin/director/docente puede revertir
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
                throw new \Exception('No tiene permisos para revertir la publicación.');
            }

            $sessionMain = session('sessionmain');
            if (!$sessionMain) {
                return redirect()
                    ->route('nota.index', [
                        'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                        'periodo_bimestre_id' => $periodo_bimestre_id
                    ])
                    ->with('error', 'No hay sesión principal activa. Inicie sesión principal para realizar esta acción.');
            }

            // Validar la contraseña
            $request->validate([
                'password' => 'required|string'
            ]);

            // Verificar la contraseña de la sesión principal
            if (!Hash::check($request->password, $sessionMain->password)) {
                return redirect()
                    ->route('nota.index', [
                        'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                        'periodo_bimestre_id' => $periodo_bimestre_id
                    ])
                    ->withErrors(['password' => 'Contraseña incorrecta'])
                    ->withInput();
            }

            DB::beginTransaction();

            // OBTENER EL CURSO
            $curso = CursoGradoSecNivAnio::find($curso_grado_sec_niv_anio_id);
            if (!$curso) {
                throw new \Exception('Curso no encontrado.');
            }

            // OBTENER EL PERIODO BIMESTRE
            $periodoBimestre = Periodobimestre::find($periodo_bimestre_id);
            if (!$periodoBimestre) {
                throw new \Exception('Bimestre no encontrado.');
            }

            $estadoActual = $this->obtenerEstadoActual($curso_grado_sec_niv_anio_id, $periodo_bimestre_id);

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

            // Cargar estudiantes (activos y retirados)
            $estudiantes = $this->cargarEstudiantes($curso, $periodo_bimestre_id);
            $estudianteIds = $estudiantes['activos']->pluck('id')
                ->merge($estudiantes['retirados']->pluck('id'));

            // Cargar competencias para obtener criterios
            $competencias = $this->cargarCompetencias($curso, $periodo_bimestre_id);
            $criteriosIds = $competencias->flatMap->criterios->pluck('id');

            // Revertir notas de materia
            $updatedNotas = Nota::where('periodo_bimestre_id', $periodo_bimestre_id)
                ->whereIn('materia_criterio_id', $criteriosIds)
                ->whereIn('estudiante_id', $estudianteIds)
                ->update(['publico' => $nuevoEstado]);

            // Revertir notas de conducta
            $updatedConducta = Conductanota::where('periodo_bimestre_id', $periodo_bimestre_id)
                ->where('curso_grado_sec_niv_anio_id', $curso_grado_sec_niv_anio_id)
                ->whereIn('estudiante_id', $estudianteIds)
                ->update(['publico' => $nuevoEstado]);

            DB::commit();

            $estados = ['0' => 'Privado', '1' => 'Publicado', '2' => 'Oficial', '3' => 'Extra Oficial'];

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'periodo_bimestre_id' => $periodo_bimestre_id
                ])
                ->with('success', "Notas revertidas a estado: {$estados[$nuevoEstado]}");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'periodo_bimestre_id' => $periodo_bimestre_id
                ])
                ->with('error', 'Error al revertir publicación: ' . $e->getMessage());
        }
    }
    public function guardarNotas(Request $request)
    {
        try {
            DB::beginTransaction();

            $curso_id = $request->curso_id;
            $periodo_bimestre_id = $request->periodo_bimestre_id;
            $notas_criterios = $request->notas ?? [];
            $notas_conductas = $request->conductas ?? [];
            $estadoActual = $this->obtenerEstadoActual($curso_id, $periodo_bimestre_id);

            // Obtener el curso para extraer periodo_id y asegurar que existe
            $curso = Cursogradosecnivanio::find($curso_id);
            if (!$curso) {
                throw new \Exception('Curso no encontrado');
            }

            // Obtener el periodo_bimestre
            $periodoBimestre = Periodobimestre::find($periodo_bimestre_id);
            if (!$periodoBimestre) {
                throw new \Exception('Período bimestre no encontrado');
            }

            $periodo_id = $curso->periodo_id;

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
                            ->where('periodo_bimestre_id', $periodo_bimestre_id)
                            ->first();

                        if ($notaExistente) {
                            // Solo actualizar si el estado actual permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                $notaExistente->update([
                                    'nota' => $nota,
                                ]);
                            }
                        } else {
                            // Crear nueva nota solo si se permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                Nota::create([
                                    'estudiante_id' => $estudiante_id,
                                    'materia_criterio_id' => $criterio_id,
                                    'periodo_id' => $periodo_id,
                                    'periodo_bimestre_id' => $periodo_bimestre_id,
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
                                ->where('periodo_bimestre_id', $periodo_bimestre_id)
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
                            ->where('curso_grado_sec_niv_anio_id', $curso_id)
                            ->where('periodo_bimestre_id', $periodo_bimestre_id)
                            ->first();

                        if ($notaConductaExistente) {
                            // Solo actualizar si el estado actual permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                $notaConductaExistente->update([
                                    'nota' => $nota,
                                    'periodo_id' => $periodo_id,
                                ]);
                            }
                        } else {
                            // Crear nueva nota solo si se permite edición
                            if ($this->puedeEditarNota($estadoActual)) {
                                Conductanota::create([
                                    'estudiante_id' => $estudiante_id,
                                    'conducta_id' => $conducta_id,
                                    'curso_grado_sec_niv_anio_id' => $curso_id,
                                    'periodo_id' => $periodo_id,
                                    'periodo_bimestre_id' => $periodo_bimestre_id,
                                    'nota' => $nota,
                                    'publico' => $estadoActual
                                ]);
                            }
                        }
                    } else {
                        // Si la nota está vacía, eliminar solo si se permite edición
                        if ($this->puedeEditarNota($estadoActual)) {
                            $notaConductaExistente = Conductanota::where('estudiante_id', $estudiante_id)
                                ->where('conducta_id', $conducta_id)
                                ->where('curso_grado_sec_niv_anio_id', $curso_id)
                                ->where('periodo_bimestre_id', $periodo_bimestre_id)
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
                    'periodo_bimestre_id' => $periodo_bimestre_id
                ])
                ->with('success', 'Notas guardadas exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_id ?? 0,
                    'periodo_bimestre_id' => $periodo_bimestre_id ?? 0
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

        // Docente solo puede editar en estados 0 y 1 (Privado y Publicado)
        // En estado 2 (Oficial) y 3 (Extra Oficial) NO puede editar
        if ($user->hasRole('docente')) {
            return in_array($estadoActual, ['0', '1']);
        }

        return false;
    }
    public function exportarExcel($curso_grado_sec_niv_anio_id, $periodo_bimestre_id)
    {
        try {
            // 1. Validar parámetros
            $periodoBimestre = Periodobimestre::find($periodo_bimestre_id);
            if (!$periodoBimestre) {
                abort(404, 'Bimestre no encontrado.');
            }

            // 2. Cargar el curso
            $curso = $this->cargarCurso($curso_grado_sec_niv_anio_id);
            if (!$curso) {
                abort(404, 'Curso no encontrado.');
            }

            // 3. Cargar todos los datos necesarios (igual que en index)
            $estudiantes = $this->cargarEstudiantes($curso, $periodo_bimestre_id);
            $competencias = $this->cargarCompetencias($curso, $periodo_bimestre_id);

            $competenciasNoTransversales = $competencias->filter(function($competencia) {
                return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') === false;
            });

            $competenciaTransversal = $competencias->first(function($competencia) {
                return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false;
            });

            $notasExistentes = $this->cargarNotasExistentes($curso_grado_sec_niv_anio_id, $periodo_bimestre_id, $competencias, $estudiantes);
            $conductas = $this->cargarConductas();
            $conductaNotas = $this->cargarConductaNotas($curso_grado_sec_niv_anio_id, $periodo_bimestre_id, $estudiantes);

            // 4. Obtener el formato actual
            $formato = request()->get('formato', 'cuantitativo');

            // 5. Generar nombre del archivo
            $nombreArchivo = 'Registro_Notas_'
                . str_replace(' ', '_', $curso->materia->nombre) . '_'
                . $curso->grado->nombreCompleto . '_'
                . $periodoBimestre->sigla . '_'
                . date('Ymd_His') . '.xls';

            // 6. Generar contenido Excel
            $excelContent = $this->generarContenidoExcel([
                'curso' => $curso,
                'materia' => $curso->materia,
                'grado' => $curso->grado,
                'docente' => $curso->docente,
                'periodoBimestre' => $periodoBimestre,
                'periodo' => $curso->periodo,
                'competencias' => $competencias,
                'competenciasNoTransversales' => $competenciasNoTransversales,
                'competenciaTransversal' => $competenciaTransversal,
                'estudiantesActivos' => $estudiantes['activos'],
                'estudiantesInactivos' => $estudiantes['retirados'],
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

        } catch (\Exception $e) {
            \Log::error('Error exportar Excel: ' . $e->getMessage());
            return redirect()
                ->route('nota.index', [
                    'curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id,
                    'periodo_bimestre_id' => $periodo_bimestre_id
                ])
                ->with('error', 'Error al exportar Excel: ' . $e->getMessage());
        }
    }

    private function generarContenidoExcel($datos)
    {
        ob_start();

        // Calcular número total de columnas dinámicamente
        $totalColumnas = 2; // N° y ESTUDIANTES

        // Sumar columnas de criterios
        foreach ($datos['competencias'] as $competencia) {
            if (!empty($competencia->criterios)) {
                $totalColumnas += count($competencia->criterios);
            }
        }

        // Sumar columnas SIAGIE
        if (!empty($datos['competenciasNoTransversales'])) {
            $totalColumnas += count($datos['competenciasNoTransversales']);
        }

        // Sumar columnas transversales
        if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
            $totalColumnas += count($datos['competenciaTransversal']->criterios);
        }

        // Sumar columnas conductas
        if (!empty($datos['conductas'])) {
            $totalColumnas += count($datos['conductas']);
        }

        // Inicio del documento HTML
        echo '<html>
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <style>
                    @page {
                        size: landscape;
                        margin: 0.5cm;
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 11px;
                        margin: 0;
                        padding: 5px;
                    }
                    table {
                        border-collapse: collapse;
                        width: 100%;
                        table-layout: auto;
                    }
                    th, td {
                        border: 1px solid #000000;
                        padding: 6px 4px;
                        vertical-align: middle;
                        font-family: Arial, sans-serif;
                        font-size: 11px;
                    }
                    th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                        text-align: center;
                    }
                    .titulo {
                        font-size: 14px;
                        font-weight: bold;
                        text-align: center;
                        padding: 8px;
                        font-family: Arial, sans-serif;
                    }
                    .subtitulo {
                        font-size: 10px;
                        padding: 6px;
                        text-align: left;
                        font-family: Arial, sans-serif;
                    }
                    .text-success { color: #28a745; font-weight: bold; }
                    .text-warning { color: #ffc107; font-weight: bold; }
                    .text-danger { color: #dc3545; font-weight: bold; }
                    .text-center { text-align: center; }
                    .text-left { text-align: left; }
                    .bg-light { background-color: #f8f9fa; }
                    .bg-gray { background-color: #e9ecef; }
                    .small { font-size: 9px; font-family: Arial, sans-serif; }
                    .bg-primary-light { background-color: #e8f4f8; }
                    .bg-siagie { background-color: #17a2b8; color: white; }
                    .bg-conducta { background-color: #ffc107; }

                    /* Anchos de columnas */
                    .col-numero { width: 35px; }
                    .col-estudiante { width: auto; }

                    /* Las celdas de criterios, promedios y conductas tendrán ancho fijo de 70px */
                    .col-criterio, .col-promedio, .col-conducta {
                        width: 70px;
                    }

                    /* Estilos para texto en celdas - permitir saltos de línea automáticos */
                    th, td {
                        word-wrap: break-word;
                        word-break: break-word;
                        white-space: normal;
                        line-height: 1.4;
                    }

                    /* Altura automática para todas las celdas */
                    th, td {
                        height: auto;
                    }

                    /* Nombres de estudiantes - sin salto de línea, ancho automático, sin scroll */
                    .nombre-estudiante {
                        white-space: nowrap;
                        display: inline-block;
                    }
                </style>
            </head>
            <body>';

        // TABLA PRINCIPAL
        echo '<table cellspacing="0" border="1">';

        // ========== FILA 1: TÍTULO PRINCIPAL ==========
        echo '<tr>';
        echo '<th colspan="' . $totalColumnas . '" class="titulo" style="font-family: Arial, sans-serif; font-size: 14px;">REGISTRO DE NOTAS - SISTEMA DE GESTIÓN ACADÉMICA</th>';
        echo '</tr>';

        // ========== FILA 2: INFORMACIÓN DEL CURSO ==========
        echo '<tr>';
        echo '<td colspan="' . $totalColumnas . '" class="subtitulo" style="font-family: Arial, sans-serif; font-size: 10px;">';
        echo '<strong>Materia:</strong> ' . htmlspecialchars($datos['materia']->nombre ?? 'N/A') . ' | ';
        echo '<strong>Grado:</strong> ' . htmlspecialchars($datos['grado']->nombreCompleto ?? 'N/A') . ' | ';
        echo '<strong>Docente:</strong> ' . htmlspecialchars($datos['docente']->user->full_name ?? ($datos['docente']->user->apellido_paterno ?? '') . ' ' . ($datos['docente']->user->apellido_materno ?? '') . ', ' . ($datos['docente']->user->nombre ?? 'No asignado')) . ' | ';
        echo '<strong>Período:</strong> ' . htmlspecialchars($datos['periodo']->nombre ?? 'N/A') . ' (' . ($datos['periodo']->anio ?? 'N/A') . ') | ';
        echo '<strong>Bimestre:</strong> ' . htmlspecialchars($datos['periodoBimestre']->sigla ?? 'N/A') . ' | ';
        echo '<strong>Formato:</strong> ' . ($datos['formato'] == 'cuantitativo' ? 'Cuantitativo (1-4)' : 'Cualitativo (AD, A, B, C)') . ' | ';
        echo '<strong>Generado:</strong> ' . $datos['fecha_generacion']->format('d/m/Y H:i:s');
        echo '</td>';
        echo '</tr>';

        // ========== FILA 3: ENCABEZADO PRINCIPAL ==========
        echo '<tr>';
        echo '<th rowspan="2" class="col-numero" style="font-family: Arial, sans-serif; font-size: 11px;">N°</th>';
        echo '<th rowspan="2" class="col-estudiante" style="text-align: left; font-family: Arial, sans-serif; font-size: 11px;">ESTUDIANTES</th>';

        // Encabezados de competencias (colspan pero sin ancho fijo - se ajusta al contenido de los hijos)
        foreach ($datos['competencias'] as $competencia) {
            if (!empty($competencia->criterios)) {
                $colspan = count($competencia->criterios);
                $nombreCompetencia = htmlspecialchars($competencia->nombre);
                echo '<th colspan="' . $colspan . '" class="bg-primary-light" style="word-wrap: break-word; white-space: normal; font-family: Arial, sans-serif; font-size: 11px;">' .
                    $nombreCompetencia . '<br><span class="small" style="font-family: Arial, sans-serif;">Competencia</span></th>';
            }
        }

        // Encabezados SIAGIE
        if (!empty($datos['competenciasNoTransversales'])) {
            $siagieCols = count($datos['competenciasNoTransversales']);
            echo '<th colspan="' . $siagieCols . '" class="bg-siagie" style="word-wrap: break-word; white-space: normal; font-family: Arial, sans-serif; font-size: 11px;">SIAGIE<br><span class="small" style="font-family: Arial, sans-serif;">Competencias</span></th>';
        }

        // Encabezados Transversales
        if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
            $transversalesCols = count($datos['competenciaTransversal']->criterios);
            echo '<th colspan="' . $transversalesCols . '" class="bg-siagie" style="word-wrap: break-word; white-space: normal; font-family: Arial, sans-serif; font-size: 11px;">SIAGIE<br><span class="small" style="font-family: Arial, sans-serif;">Transversales</span></th>';
        }

        // Encabezados Conductas
        if (!empty($datos['conductas'])) {
            $conductasCols = count($datos['conductas']);
            echo '<th colspan="' . $conductasCols . '" class="bg-conducta" style="word-wrap: break-word; white-space: normal; font-family: Arial, sans-serif; font-size: 11px;">CONDUCTAS</th>';
        }

        echo '</tr>';

        // ========== FILA 4: SUB-ENCABEZADOS ==========
        echo '<tr>';

        // Nombres de criterios - cada uno con ancho fijo de 70px
        foreach ($datos['competencias'] as $competencia) {
            if (!empty($competencia->criterios)) {
                foreach ($competencia->criterios as $criterio) {
                    $nombreCriterio = htmlspecialchars($criterio->nombre);
                    echo '<th class="small col-criterio" style="width: 70px; word-wrap: break-word; white-space: normal; line-height: 1.3; font-family: Arial, sans-serif; font-size: 11px;">' . $nombreCriterio . '</th>';
                }
            }
        }

        // Nombres SIAGIE (promedios) - cada uno con ancho fijo de 70px
        if (!empty($datos['competenciasNoTransversales'])) {
            foreach ($datos['competenciasNoTransversales'] as $competenciaNT) {
                $nombreCompetencia = htmlspecialchars($competenciaNT->nombre);
                echo '<th class="small bg-siagie col-promedio" style="width: 70px; word-wrap: break-word; white-space: normal; line-height: 1.3; font-family: Arial, sans-serif; font-size: 11px;">' .
                    $nombreCompetencia . '<br><span class="small" style="font-family: Arial, sans-serif;">Promedio</span></th>';
            }
        }

        // Nombres transversales - cada uno con ancho fijo de 70px
        if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
            foreach ($datos['competenciaTransversal']->criterios as $criterioTrans) {
                $nombreCriterio = htmlspecialchars($criterioTrans->nombre);
                echo '<th class="small bg-siagie col-promedio" style="width: 70px; word-wrap: break-word; white-space: normal; line-height: 1.3; font-family: Arial, sans-serif; font-size: 11px;">' .
                    $nombreCriterio . '<br><span class="small" style="font-family: Arial, sans-serif;">Transversal</span></th>';
            }
        }

        // Nombres conductas - cada uno con ancho fijo de 70px
        if (!empty($datos['conductas'])) {
            foreach ($datos['conductas'] as $conducta) {
                $nombreConducta = htmlspecialchars($conducta->nombre);
                echo '<th class="small bg-conducta col-conducta" style="width: 70px; word-wrap: break-word; white-space: normal; line-height: 1.3; font-family: Arial, sans-serif; font-size: 11px;">' . $nombreConducta . '</th>';
            }
        }

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

        // Función para obtener clase CSS
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

        // ========== DATOS DE ESTUDIANTES ACTIVOS ==========
        $numero = 1;
        foreach ($datos['estudiantesActivos'] as $estudiante) {
            echo '<tr>';

            // Número
            echo '<td class="text-center col-numero" style="font-family: Arial, sans-serif; font-size: 11px;">' . $numero++ . '</td>';

            // Nombre completo - ancho automático, sin salto de línea, sin scroll
            $nombreCompleto = ($estudiante->user->apellido_paterno ?? '') . ' ' .
                            ($estudiante->user->apellido_materno ?? '') . ', ' .
                            ($estudiante->user->nombre ?? '');
            echo '<td class="col-estudiante" style="text-align: left; padding: 6px 4px; white-space: nowrap; font-family: Arial, sans-serif; font-size: 11px;">';
            echo '<strong>' . htmlspecialchars($nombreCompleto) . '</strong>';
            echo '</td>';

            // Notas por criterio - ancho fijo 70px
            foreach ($datos['competencias'] as $competencia) {
                if (!empty($competencia->criterios)) {
                    foreach ($competencia->criterios as $criterio) {
                        $key = $estudiante->id . '-' . $criterio->id;
                        $nota = $datos['notasExistentes'][$key]['nota'] ?? null;
                        $notaFormateada = $formatearNota($nota);
                        $clase = $obtenerClaseNota($notaFormateada, $datos['formato']);
                        echo '<td class="text-center col-criterio" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $notaFormateada . '</strong></td>';
                    }
                }
            }

            // Promedios SIAGIE - ancho fijo 70px
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
                echo '<td class="text-center bg-light col-promedio" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $promedioFormateado . '</strong></td>';
            }

            // Transversales - ancho fijo 70px
            if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
                foreach ($datos['competenciaTransversal']->criterios as $criterioTrans) {
                    $keyTrans = $estudiante->id . '-' . $criterioTrans->id;
                    $notaTrans = $datos['notasExistentes'][$keyTrans]['nota'] ?? null;
                    $notaTransFormateada = $formatearNota($notaTrans);
                    $clase = $obtenerClaseNota($notaTransFormateada, $datos['formato']);
                    echo '<td class="text-center bg-light col-promedio" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $notaTransFormateada . '</strong></td>';
                }
            }

            // Conductas - ancho fijo 70px
            if (!empty($datos['conductas'])) {
                foreach ($datos['conductas'] as $conducta) {
                    $keyCond = $estudiante->id . '-' . $conducta->id;
                    $notaCond = $datos['conductaNotas'][$keyCond]['nota'] ?? null;
                    $notaCondFormateada = $formatearNota($notaCond);
                    $clase = $obtenerClaseNota($notaCondFormateada, $datos['formato']);
                    echo '<td class="text-center col-conducta" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $notaCondFormateada . '</strong></td>';
                }
            }

            echo '</tr>';
        }

        // ========== ESTUDIANTES INACTIVOS ==========
        if (!empty($datos['estudiantesInactivos']) && $datos['estudiantesInactivos']->count() > 0) {
            echo '<tr>';
            echo '<td colspan="' . $totalColumnas . '" class="bg-gray text-center" style="font-family: Arial, sans-serif; font-size: 11px;"><strong><i>ESTUDIANTES INACTIVOS CON NOTAS REGISTRADAS</i></strong></td>';
            echo '</tr>';

            foreach ($datos['estudiantesInactivos'] as $estudiante) {
                echo '<tr>';

                // Ícono
                echo '<td class="text-center col-numero" style="font-family: Arial, sans-serif; font-size: 11px;">●<\/td>';

                // Nombre con indicador inactivo - ancho automático, sin salto de línea
                $nombreCompleto = ($estudiante->user->apellido_paterno ?? '') . ' ' .
                                ($estudiante->user->apellido_materno ?? '') . ', ' .
                                ($estudiante->user->nombre ?? '');
                echo '<td class="col-estudiante" style="text-align: left; padding: 6px 4px; white-space: nowrap; font-family: Arial, sans-serif; font-size: 11px;">';
                echo htmlspecialchars($nombreCompleto);
                echo '<span class="small text-muted" style="font-family: Arial, sans-serif;"> (Inactivo)</span>';
                echo '<\/td>';

                // Notas por criterio - ancho fijo 70px
                foreach ($datos['competencias'] as $competencia) {
                    if (!empty($competencia->criterios)) {
                        foreach ($competencia->criterios as $criterio) {
                            $key = $estudiante->id . '-' . $criterio->id;
                            $nota = $datos['notasExistentes'][$key]['nota'] ?? null;
                            $notaFormateada = $formatearNota($nota);
                            $clase = $obtenerClaseNota($notaFormateada, $datos['formato']);
                            echo '<td class="text-center col-criterio" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $notaFormateada . '</strong><\/td>';
                        }
                    }
                }

                // Promedios SIAGIE - ancho fijo 70px
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
                    echo '<td class="text-center bg-light col-promedio" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $promedioFormateado . '</strong><\/td>';
                }

                // Transversales - ancho fijo 70px
                if (!empty($datos['competenciaTransversal']) && !empty($datos['competenciaTransversal']->criterios)) {
                    foreach ($datos['competenciaTransversal']->criterios as $criterioTrans) {
                        $keyTrans = $estudiante->id . '-' . $criterioTrans->id;
                        $notaTrans = $datos['notasExistentes'][$keyTrans]['nota'] ?? null;
                        $notaTransFormateada = $formatearNota($notaTrans);
                        $clase = $obtenerClaseNota($notaTransFormateada, $datos['formato']);
                        echo '<td class="text-center bg-light col-promedio" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $notaTransFormateada . '</strong><\/td>';
                    }
                }

                // Conductas - ancho fijo 70px
                if (!empty($datos['conductas'])) {
                    foreach ($datos['conductas'] as $conducta) {
                        $keyCond = $estudiante->id . '-' . $conducta->id;
                        $notaCond = $datos['conductaNotas'][$keyCond]['nota'] ?? null;
                        $notaCondFormateada = $formatearNota($notaCond);
                        $clase = $obtenerClaseNota($notaCondFormateada, $datos['formato']);
                        echo '<td class="text-center col-conducta" style="width: 70px; font-family: Arial, sans-serif; font-size: 11px;"><strong>' . $notaCondFormateada . '</strong><\/td>';
                    }
                }

                echo '</tr>';
            }
        }

        // ========== LEYENDA ==========
        echo '<tr>';
        echo '<td colspan="' . $totalColumnas . '" style="border: 1px solid #000000; padding: 8px; text-align: left; background-color: #f9f9f9; font-family: Arial, sans-serif; font-size: 11px;">';
        echo '<strong>Leyenda de Calificación:</strong><br>';
        echo '<span class="text-success" style="font-family: Arial, sans-serif;">' . ($datos['formato'] == 'cuantitativo' ? '3-4 (Logro Destacado)' : 'AD - A (Logro Destacado - Logro Esperado)') . '</span> | ';
        echo '<span class="text-warning" style="font-family: Arial, sans-serif;">' . ($datos['formato'] == 'cuantitativo' ? '2 (En Proceso)' : 'B (En Proceso)') . '</span> | ';
        echo '<span class="text-danger" style="font-family: Arial, sans-serif;">' . ($datos['formato'] == 'cuantitativo' ? '1 (En Inicio)' : 'C (En Inicio)') . '</span><br>';
        echo '<span class="small text-muted" style="font-family: Arial, sans-serif;">Los estudiantes inactivos solo aparecen si tienen notas registradas en el sistema.</span><br>';
        echo '<span class="small text-muted" style="font-family: Arial, sans-serif;">Documento generado automáticamente el ' . $datos['fecha_generacion']->format('d/m/Y H:i:s') . '</span>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</body></html>';

        return ob_get_clean();
    }

}
