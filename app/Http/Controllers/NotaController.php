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
            ->groupBy([
                'estudiante_id',
                'materia_criterio_id'
            ]);

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

    }
    public function GuardadoAutomatico(Request $request)
    {

    }
}
