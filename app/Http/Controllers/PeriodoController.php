<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Periodo;
use App\Models\Matricula;

class PeriodoController extends Controller
{
    //moduleID 18 = Periodo
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('18')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        $periodosActivos = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(10, ['*'], 'activos_page');

        $periodosInactivos = Periodo::where('estado', '0')
            ->orderBy('anio', 'desc')
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(10, ['*'], 'inactivos_page');

        return view('periodo.index', compact('periodosActivos', 'periodosInactivos'));
    }

    public function create()
    {
        return view('periodo.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:50',
            'estado' => 'required|in:0,1',
            'anio' => 'required|integer|min:2000|max:2100',
            'tipo_periodo' => 'required|in:año escolar,recuperación',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $periodo = Periodo::create($validatedData);

        return redirect()->route('periodo.index')->with('success', 'Periodo creado con éxito.');
    }

    public function edit($id)
    {
        $periodo = Periodo::find($id);
        if (!$periodo) {
            return redirect()->route('periodo.index')->with('error', 'Periodo no encontrado.');
        }
        return view('periodo.edit', compact('periodo'));
    }

    public function update(Request $request, $id)
    {
        $periodo = Periodo::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:50',
            'estado' => 'required|boolean',
            'anio' => 'sometimes|integer|min:2000|max:2100',
            'tipo_periodo' => 'sometimes|in:año escolar,recuperación',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'sometimes|date|after:fecha_inicio',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $periodo->update($request->only(['estado', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'anio', 'tipo_periodo']));

        return redirect()->route('periodo.index')
            ->with('success', 'Periodo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $periodo = Periodo::find($id);
        if (!$periodo) {
            return redirect()->route('periodo.index')->with('error', 'Periodo no encontrado.');
        }

        // Verificar si tiene matrículas asociadas
        if ($periodo->matriculas()->count() > 0) {
            return redirect()->route('periodo.index')->with('error', 'No se puede eliminar el periodo porque tiene matrículas asociadas.');
        }

        $periodo->delete();
        return redirect()->route('periodo.index')->with('success', 'Periodo eliminado con éxito.');
    }

    public function show($id)
    {
        $periodo = Periodo::with('matriculas.estudiante')->find($id);
        if (!$periodo) {
            return response()->json(['message' => 'Periodo not found'], 404);
        }
        return response()->json($periodo);
    }

    public function estudiantesPorPeriodo($periodo_id)
    {
        $matriculas = Matricula::with('estudiante')
            ->where('periodo_id', $periodo_id)
            ->get();

        $estudiantes = $matriculas->map(function ($matricula) {
            return $matricula->estudiante;
        });

        return response()->json($estudiantes);
    }
}
