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
        // Obtener el curso relacionado al bimestre
        $curso = $bimestre->cursoGradoSecNivAnio()
            ->with(['grado', 'materia', 'docente.user'])
            ->first();

        if (!$curso) {
            abort(404, 'Curso no encontrado para el bimestre.');
        }

        // Obtener estudiantes del grado
        $estudiantes = Estudiante::where('grado_id', $curso->grado_id)
            ->with('user')
            ->get()
            ->sortBy(function($est) {
                return $est->user->apellido_paterno ?? '';
            })
            ->values();

        // Obtener competencias y criterios de la materia y grado
        $competencias = Materiacompetencia::where('materia_id', $curso->materia_id)
            ->with(['materiaCriterio' => function($q) use ($curso) {
                $q->where('grado_id', $curso->grado_id)
                ->where('anio', $curso->anio);
            }])
            ->get();

        // Reorganizar criterios por competencia para la vista
        foreach ($competencias as $comp) {
            $comp->criterios = $comp->materiaCriterio ?? collect();
        }

        // Obtener notas existentes para el bimestre, estudiantes y criterios
        $criteriosIds = $competencias->flatMap->criterios->pluck('id')->unique();
        $notasExistentes = Nota::where('bimestre_id', $bimestre->id)
            ->whereIn('materia_criterio_id', $criteriosIds)
            ->whereIn('estudiante_id', $estudiantes->pluck('id'))
            ->get()
            ->mapToGroups(function ($item) {
                return [$item['estudiante_id'] => [$item['materia_criterio_id'] => $item]];
            });

        // Docente asignado
        $docente = $curso->docente;

        // Materia y grado para la cabecera
        $materia = $curso->materia;
        $grado = $curso->grado;

        return view('nota.index', compact(
            'bimestre',
            'curso',
            'materia',
            'grado',
            'docente',
            'competencias',
            'estudiantes',
            'notasExistentes'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bimestre_id' => 'required|exists:maya_bimestres,id',
            'notas' => 'required|array',
            'notas.*.*' => 'nullable|numeric|min:1|max:4'
        ]);

        // Verificar que el bimestre existe
        $bimestre = Bimestre::findOrFail($request->bimestre_id);

        try {
            \DB::beginTransaction();

            $notasGuardadas = 0;
            $errores = [];

            foreach ($request->notas as $estudianteId => $criterios) {
                // Verificar que el estudiante existe
                if (!Estudiante::where('id', $estudianteId)->exists()) {
                    $errores[] = "Estudiante con ID $estudianteId no encontrado";
                    continue;
                }

                foreach ($criterios as $criterioId => $valorNota) {
                    // Verificar que el criterio existe
                    if (!Materiacriterio::where('id', $criterioId)->exists()) {
                        $errores[] = "Criterio con ID $criterioId no encontrado";
                        continue;
                    }

                    if (!is_null($valorNota)) {
                        Nota::updateOrCreate(
                            [
                                'estudiante_id' => $estudianteId,
                                'materia_criterio_id' => $criterioId,
                                'bimestre_id' => $request->bimestre_id
                            ],
                            [
                                'nota' => $valorNota,
                                'publico' => 0
                            ]
                        );
                        $notasGuardadas++;
                    }
                }
            }

            \DB::commit();

            $mensaje = "Se guardaron $notasGuardadas notas correctamente.";
            if (!empty($errores)) {
                $mensaje .= ' Pero ocurrieron algunos errores: ' . implode(', ', array_slice($errores, 0, 3));
                if (count($errores) > 3) {
                    $mensaje .= ' y ' . (count($errores) - 3) . ' más...';
                }
            }

            return redirect()
                ->route('nota.index', ['bimestre' => $request->bimestre_id])
                ->with(
                    !empty($errores) ? 'warning' : 'success',
                    $mensaje
                );

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Error al guardar notas: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al guardar las notas: ' . $e->getMessage());
        }
    }
}
