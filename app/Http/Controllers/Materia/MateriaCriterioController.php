<?php

namespace App\Http\Controllers\Materia;

use App\Http\Controllers\Controller;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use Illuminate\Http\Request;

class MateriaCriterioController extends Controller
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

    public function index($id)
    {
        $competencia = Materiacompetencia::findOrFail($id);
        $criterios = Materiacriterio::where('materia_competencia_id', $id)
            ->orderBy('nombre')
            ->paginate(5);

        return view('materia.materiacriterio.index', compact('competencia', 'criterios'));
    }

    public function create($id)
    {
        $competencia = Materiacompetencia::findOrFail($id);
        return view('materia.materiacriterio.create', compact('competencia'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'materia_competencia_id' => 'required|exists:materia_competencias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        try {
            Materiacriterio::create($request->all());

            return redirect()->route('materiacriterio.index', $request->materia_competencia_id)
                ->with('success', 'Criterio creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el criterio: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $criterio = Materiacriterio::with('materiaCompetencia')->findOrFail($id);
        return view('materia.materiacriterio.edit', compact('criterio'));
    }

    public function update(Request $request, $id)
    {
        $criterio = Materiacriterio::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $criterio->update($request->all());

            return redirect()->route('materiacriterio.index', $criterio->materia_competencia_id)
                ->with('success', 'Criterio actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el criterio: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $criterio = Materiacriterio::findOrFail($id);
            $competenciaId = $criterio->materia_competencia_id;
            $criterio->delete();

            return redirect()->route('materiacriterio.index', $competenciaId)
                ->with('success', 'Criterio eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('materiacriterio.index', $competenciaId ?? 0)
                ->with('error', 'Error al eliminar el criterio: ' . $e->getMessage());
        }
    }
}
