<?php

namespace App\Http\Controllers\AulaVirtual;

use App\Http\Controllers\Controller;
use App\Models\AulaVirtual\Aulavirtualsesion;
use App\Models\AulaVirtual\Materialtrabajo;
use App\Models\Maya\Cursogradosecnivanio;

class AulavirtualestudianteController extends Controller
{
    // moduleID 23 = Aula Virtual Estudiante
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('23')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $estudiante = auth()->user()->estudiante;

        $grado = null;
        if ($estudiante) {
            $matricula = $estudiante->matriculas()
                ->where('estado', '1')
                ->whereHas('periodo', fn ($query) => $query->where('estado', '1'))
                ->latest('id')
                ->first();

            if ($matricula?->grado) {
                $grado = $matricula->grado;
            } elseif ($estudiante->grado) {
                $grado = $estudiante->grado;
            }
        }

        if (! $grado) {
            return view('aula-virtual.estudiante.index', [
                'grado' => null,
                'sesiones' => collect(),
                'materiales' => collect(),
            ]);
        }

        $cursos = Cursogradosecnivanio::where('grado_id', $grado->id)
            ->whereHas('periodo', fn ($query) => $query->where('estado', '1'))
            ->get();

        $sesiones = Aulavirtualsesion::with(['curso.grado', 'curso.materia', 'docente.user'])
            ->whereIn('curso_grado_sec_niv_anio_id', $cursos->pluck('id'))
            ->where('estado', '1')
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();

        $docentesIds = $cursos->pluck('docente_designado_id')->filter()->unique();

        $materiales = Materialtrabajo::with('docente.user')
            ->whereIn('docente_id', $docentesIds)
            ->whereNotNull('enlace_google_drive')
            ->get();

        return view('aula-virtual.estudiante.index', compact('grado', 'sesiones', 'materiales'));
    }
}
