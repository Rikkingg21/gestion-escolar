<?php

namespace App\Http\Controllers\Materia;

use App\Http\Controllers\Controller;
//use App\Models\Materia\Materia;
use App\Models\Materia\Materiacompetencia;
use Illuminate\Http\Request;

use App\Models\Materia;

class MateriaCompetenciaController extends Controller
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
        $materia = Materia::findOrFail($id);
        $competencias = Materiacompetencia::where('materia_id', $id)
            ->orderBy('nombre')
            ->paginate(5);

        return view('materia.materiacompetencia.index', compact('materia', 'competencias'));
    }

    public function create($id)
    {
        $materia = Materia::findOrFail($id);
        return view('materia.materiacompetencia.create', compact('materia'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        try {
            Materiacompetencia::create($request->all());

            return redirect()->route('materiacompetencia.index', $request->materia_id)
                ->with('success', 'Competencia creada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear la competencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $competencia = Materiacompetencia::with('materia')->findOrFail($id);
        return view('materia.materiacompetencia.edit', compact('competencia'));
    }

    public function update(Request $request, $id)
    {
        $competencia = Materiacompetencia::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $competencia->update($request->all());

            return redirect()->route('materiacompetencia.index', $competencia->materia_id)
                ->with('success', 'Competencia actualizada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la competencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $competencia = Materiacompetencia::findOrFail($id);
            $materiaId = $competencia->materia_id;
            $competencia->delete();

            return redirect()->route('materiacompetencia.index', $materiaId)
                ->with('success', 'Competencia eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('materiacompetencia.index', $materiaId ?? 0)
                ->with('error', 'Error al eliminar la competencia: ' . $e->getMessage());
        }
    }
}
