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
        // Cargar relaciones con validación
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

        // Obtener estudiantes
        $estudiantes = Estudiante::with(['user'])
            ->where('grado_id', $curso->grado_id)
            ->orderByRaw("
                (SELECT apellido_paterno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT apellido_materno FROM users WHERE users.id = estudiantes.user_id),
                (SELECT nombre FROM users WHERE users.id = estudiantes.user_id)
            ")
            ->get();

        // Preparar competencias con criterios
        $competencias = $curso->materia->materiaCompetencia->map(function($competencia) use ($curso) {
            $competencia->criterios = $competencia->materiaCriterio
                ->where('grado_id', $curso->grado_id)
                ->where('anio', $curso->anio)
                ->values();
            return $competencia;
        })->filter(fn($c) => $c->criterios->isNotEmpty());

        // Obtener notas existentes
        $criteriosIds = $competencias->flatMap->criterios->pluck('id');
        $notasExistentes = Nota::where('bimestre_id', $bimestre->id)
                ->whereIn('materia_criterio_id', $criteriosIds)
                ->whereIn('estudiante_id', $estudiantes->pluck('id'))
                ->get()
                ->mapWithKeys(function ($item) {
                    return [
                        $item['estudiante_id'].'-'.$item['materia_criterio_id'] => $item['nota']
                    ];
                });

        return view('nota.index', [
            'bimestre' => $bimestre,
            'curso' => $curso,
            'materia' => $curso->materia,
            'grado' => $curso->grado,
            'docente' => $curso->docente,
            'competencias' => $competencias,
            'estudiantes' => $estudiantes,
            'notasExistentes' => $notasExistentes
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
                                'nota' => (int) $valor_nota, // Aseguramos que sea entero
                                'publico' => '0' // No publicadas por defecto
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
        Nota::where('bimestre_id', $bimestre->id)->update(['publico' => '1']); // Usamos string '1'
        return redirect()->back()->with('success', 'Notas publicadas correctamente.');
    }
}
