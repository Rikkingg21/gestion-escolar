<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Estudiante;
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
        $curso = $bimestre->cursoGradoSecNivAnio;

        // Obtener datos necesarios
        $materia = $curso->materia;
        $grado = $curso->grado;
        $docente = $curso->docente;

        // Obtener estudiantes del mismo grado ordenados alfabéticamente
        $estudiantes = Estudiante::with(['user' => function($query) {
                $query->orderBy('apellido_paterno')
                    ->orderBy('apellido_materno')
                    ->orderBy('nombre');
            }])
            ->where('grado_id', $grado->id)
            ->where('estado', 'activo')
            ->get()
            ->sortBy(function($estudiante) {
                return $estudiante->user->apellido_paterno.' '.
                    $estudiante->user->apellido_materno.' '.
                    $estudiante->user->nombre;
            });

        // Obtener competencias con sus criterios
        $competencias = Materiacompetencia::where('materia_id', $materia->id)
            ->get()
            ->map(function($competencia) use ($grado) {
                $competencia->criterios = MateriaCriterio::where([
                    'materia_competencia_id' => $competencia->id,
                    'grado_id' => $grado->id
                ])->get();
                return $competencia;
            });

        // Obtener notas existentes para este bimestre
        $notasExistentes = Nota::where('bimestre_id', $bimestre->id)
            ->get()
            ->groupBy(['estudiante_id', 'materia_criterio_id']);

        return view('nota.index', compact(
            'bimestre',
            'curso',
            'materia',
            'grado',
            'docente',
            'estudiantes',
            'competencias',
            'notasExistentes'
        ));
    }

    public function store(Request $request)
    {

    }
}
