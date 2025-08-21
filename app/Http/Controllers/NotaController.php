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

class NotaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }

    public function index(Bimestre $bimestre)
    {
        // Cargar relaciones con validación (código existente)
        $bimestre->load([
            'cursoGradoSecNivAnio' => function($query) {
                $query->with([
                    'grado',
                    'materia.materiaCompetencia.materiaCriterio',
                    'docente.user'
                ]);
            }
        ]);

        $curso = $bimestre->cursoGradoSecNivAnio;

        if (!$curso) {
            abort(404, 'Curso no encontrado para el bimestre.');
        }

        // Obtener estudiantes activos
        $estudiantesActivos = Estudiante::with(['user'])
            ->where('grado_id', $curso->grado_id)
            ->where('estado', '1') // Solo estudiantes activos
            ->orderByRaw("
                (SELECT apellido_paterno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT apellido_materno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT nombre FROM users WHERE users.id = estudiantes.user_id)
            ")
            ->get();

        // Obtener estudiantes inactivos que tienen notas en este bimestre
        $estudiantesInactivosConNotas = Estudiante::with(['user'])
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

        // Preparar competencias con criterios (código existente)
        $competencias = $curso->materia->materiaCompetencia->map(function($competencia) use ($curso) {
            $competencia->criterios = $competencia->materiaCriterio
                ->where('grado_id', $curso->grado_id)
                ->where('anio', $curso->anio)
                ->values();
            return $competencia;
        })->filter(fn($c) => $c->criterios->isNotEmpty());

        // Obtener notas existentes (código existente)
        $criteriosIds = $competencias->flatMap->criterios->pluck('id');
        $notasExistentes = Nota::where('bimestre_id', $bimestre->id)
                ->whereIn('materia_criterio_id', $criteriosIds)
                ->whereIn('estudiante_id',
                    $estudiantesActivos->pluck('id')
                        ->merge($estudiantesInactivosConNotas->pluck('id'))
                )
                ->get()
                ->mapWithKeys(function ($item) {
                    return [
                        $item['estudiante_id'].'-'.$item['materia_criterio_id'] => $item['nota']
                    ];
                });
        $estadoActual = Nota::where('bimestre_id', $bimestre->id)
        ->value('publico') ?? '0';

        return view('nota.index', [
            'bimestre' => $bimestre,
            'curso' => $curso,
            'materia' => $curso->materia,
            'grado' => $curso->grado,
            'docente' => $curso->docente,
            'competencias' => $competencias,
            'estudiantesActivos' => $estudiantesActivos,
            'estudiantesInactivos' => $estudiantesInactivosConNotas,
            'notasExistentes' => $notasExistentes,
            'estadoActual' => $estadoActual
        ]);
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
