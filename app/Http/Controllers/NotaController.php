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

    public function index(Bimestre $bimestre)
    {
        // Cargar el curso con sus relaciones
        $curso = $this->cargarCurso($bimestre);

        if (!$curso) {
            abort(404, 'Curso no encontrado para el bimestre.');
        }

        // Cargar estudiantes
        $estudiantes = $this->cargarEstudiantes($curso, $bimestre);

        // Cargar competencias y criterios
        $competencias = $this->cargarCompetencias($curso);

        // Cargar notas existentes
        $notasExistentes = $this->cargarNotasExistentes($bimestre, $competencias, $estudiantes);

        // Cargar conductas
        $conductas = $this->cargarConductas();

        // Cargar notas de conducta
        $conductaNotas = $this->cargarConductaNotas($bimestre, $estudiantes);

        // Obtener estado actual
        $estadoActual = $this->obtenerEstadoActual($bimestre);

        return view('nota.index', [
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
    private function cargarCurso(Bimestre $bimestre)
    {
        $bimestre->load([
            'cursoGradoSecNivAnio' => function($query) {
                $query->with([
                    'grado',
                    'materia.materiaCompetencia.materiaCriterio',
                    'docente.user'
                ]);
            }
        ]);

        return $bimestre->cursoGradoSecNivAnio;
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
            ->whereHas('notas', function($query) use ($bimestre) {
                $query->where('bimestre_id', $bimestre->id);
            })
            ->orderByRaw("
                (SELECT apellido_paterno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT apellido_materno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT nombre FROM users WHERE users.id = estudiantes.user_id)
            ")
            ->get();
    }

    /**
     * Cargar competencias y criterios
     */
    private function cargarCompetencias($curso)
    {
        $competencias = $curso->materia->materiaCompetencia->map(function($competencia) use ($curso) {
            $competencia->criterios = $competencia->materiaCriterio
                ->where('grado_id', $curso->grado_id)
                ->where('anio', $curso->anio)
                ->values();
            return $competencia;
        });

        return $competencias->filter(fn($c) => $c->criterios->isNotEmpty());
    }

    /**
     * Cargar notas existentes
     */
    private function cargarNotasExistentes($bimestre, $competencias, $estudiantes)
    {
        $criteriosIds = $competencias->flatMap->criterios->pluck('id');

        $estudianteIds = $estudiantes['activos']->pluck('id')
            ->merge($estudiantes['inactivos']->pluck('id'));

        return Nota::where('bimestre_id', $bimestre->id)
            ->whereIn('materia_criterio_id', $criteriosIds)
            ->whereIn('estudiante_id', $estudianteIds)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item['estudiante_id'].'-'.$item['materia_criterio_id'] => $item['nota']
                ];
            });
    }

    /**
     * Cargar conductas activas
     */
    private function cargarConductas()
    {
        return Conducta::where('estado', "1") // Solo conductas activas
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Cargar notas de conducta existentes
     */
    private function cargarConductaNotas($bimestre, $estudiantes)
    {
        $estudianteIds = $estudiantes['activos']->pluck('id')
            ->merge($estudiantes['inactivos']->pluck('id'));

        return Conductanota::where('bimestre_id', $bimestre->id)
            ->whereIn('estudiante_id', $estudianteIds)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item['estudiante_id'].'-'.$item['conducta_id'] => $item['nota']
                ];
            });
    }

    /**
     * Obtener estado actual de las notas
     */
    private function obtenerEstadoActual($bimestre)
    {
        return Nota::where('bimestre_id', $bimestre->id)
            ->value('publico') ?? '0';
    }

    public function store(Request $request)
    {
        // Validación de datos
        $validated = $request->validate([
            'bimestre_id' => 'required|exists:maya_bimestres,id',
            'notas' => 'required|array',
            'notas.*' => 'required|array',
            'notas.*.*' => 'nullable|numeric|min:1|max:4',
        ]);

        try {
            \DB::beginTransaction();

            // Verificar permisos según el estado actual de las notas
            $bimestreId = $validated['bimestre_id'];
            $user = auth()->user();

            // Obtener el estado actual de las notas del bimestre
            $estadoActual = Nota::where('bimestre_id', $bimestreId)
                ->value('publico') ?? '0';

            // Si las notas están en fase oficial (2), solo admin puede editar
            if ($estadoActual == '2' && !$user->hasRole('admin')) {
                return redirect()->back()->with('error', 'No tienes permisos para editar notas en fase oficial.');
            }

            // Si las notas están en fase pre-oficial (1), solo admin y director pueden editar
            if ($estadoActual == '1' && !$user->hasRole('admin') && !$user->hasRole('director')) {
                return redirect()->back()->with('error', 'No tienes permisos para editar notas en fase pre-oficial.');
            }

            foreach ($validated['notas'] as $estudiante_id => $criterios) {
                foreach ($criterios as $criterio_id => $valor_nota) {
                    // Solo actualizamos si hay un valor
                    if (!is_null($valor_nota)) {
                        Nota::updateOrCreate(
                            [
                                'estudiante_id' => $estudiante_id,
                                'materia_criterio_id' => $criterio_id,
                                'bimestre_id' => $validated['bimestre_id'],
                            ],
                            [
                                'nota' => (int) $valor_nota,
                                'publico' => $estadoActual // Mantener el estado actual
                            ]
                        );
                    }
                }
            }

            \DB::commit();

            return redirect()->back()->with('success', 'Notas guardadas correctamente.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Error al guardar las notas: ' . $e->getMessage());
        }
    }
    public function storeConductaNotas(Request $request)
    {
        // Cambia 'conducta_notas' por 'notas_conducta'
        $validated = $request->validate([
            'bimestre_id' => 'required|exists:maya_bimestres,id',
            'notas_conducta' => 'required|array',
            'notas_conducta.*' => 'required|array',
            'notas_conducta.*.*' => 'nullable|numeric|min:1|max:4',
        ]);

        try {
            \DB::beginTransaction();

            foreach ($validated['notas_conducta'] as $estudiante_id => $conductas) {
                foreach ($conductas as $conducta_id => $valor_nota) {
                    if (!is_null($valor_nota)) {
                        \App\Models\Conductanota::updateOrCreate(
                            [
                                'estudiante_id' => $estudiante_id,
                                'conducta_id' => $conducta_id,
                                'bimestre_id' => $validated['bimestre_id'],
                            ],
                            [
                                'nota' => (int) $valor_nota,
                            ]
                        );
                    }
                }
            }

            \DB::commit();

            return redirect()->back()->with('success', 'Notas de conducta guardadas correctamente.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Error al guardar las notas de conducta: ' . $e->getMessage());
        }
    }

    public function publicar(Bimestre $bimestre)
    {
        $user = auth()->user();
        $estadoActual = Nota::where('bimestre_id', $bimestre->id)
            ->value('publico') ?? '0';

        // Determinar el próximo estado basado en el estado actual
        if ($estadoActual == '0') {
            // Cambiar a fase pre-oficial (0 → 1)
            // Docente, director y admin pueden publicar a pre-oficial
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
                return redirect()->back()->with('error', 'No tienes permisos para publicar notas en fase pre-oficial.');
            }

            Nota::where('bimestre_id', $bimestre->id)->update(['publico' => '1']);
            return redirect()->back()->with('success', 'Notas publicadas en fase pre-oficial correctamente.');
        }
        elseif ($estadoActual == '1') {
            // Cambiar a fase oficial (1 → 2)
            // Director y admin pueden publicar a oficial
            if (!$user->hasRole('admin') && !$user->hasRole('director')) {
                return redirect()->back()->with('error', 'No tienes permisos para oficializar notas.');
            }

            Nota::where('bimestre_id', $bimestre->id)->update(['publico' => '2']);
            return redirect()->back()->with('success', 'Notas oficializadas correctamente.');
        }
        else {
            return redirect()->back()->with('error', 'Las notas ya están en su estado final (oficial).');
        }
    }

    public function revertir(Bimestre $bimestre)
    {
        $user = auth()->user();
        $estadoActual = Nota::where('bimestre_id', $bimestre->id)
            ->value('publico') ?? '0';

        if ($estadoActual == '1') {
            // Revertir a privado (1 → 0)
            // Director y admin pueden revertir de "1" a "0"
            if (!$user->hasRole('admin') && !$user->hasRole('director')) {
                return redirect()->back()->with('error', 'No tienes permisos para revertir notas a privado.');
            }

            Nota::where('bimestre_id', $bimestre->id)->update(['publico' => '0']);
            return redirect()->back()->with('success', 'Notas revertidas a privado correctamente.');
        }
        elseif ($estadoActual == '2') {
            // Revertir a pre-oficial (2 → 1) o a privado (2 → 0)
            // Solo admin puede revertir de "2"
            if (!$user->hasRole('admin')) {
                return redirect()->back()->with('error', 'No tienes permisos para revertir notas oficializadas.');
            }

            // Determinar a qué estado revertir basado en la solicitud
            // Podrías agregar un parámetro para especificar el destino
            // Por ahora, revertimos a pre-oficial (2 → 1)
            Nota::where('bimestre_id', $bimestre->id)->update(['publico' => '1']);
            return redirect()->back()->with('success', 'Notas revertidas a fase pre-oficial correctamente.');
        }
        else {
            return redirect()->back()->with('error', 'Las notas ya están en estado privado.');
        }
    }
}
