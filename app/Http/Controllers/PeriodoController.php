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
        // Usar paginate() en lugar de get()
        $periodosActivos = Periodo::where('estado', '1')
            ->orderBy('anio', 'desc')
            ->paginate(10, ['*'], 'activos_page'); // Nombre personalizado para la paginación

        $periodosInactivos = Periodo::where('estado', '0')
            ->orderBy('anio', 'desc')
            ->paginate(10, ['*'], 'inactivos_page'); // Nombre personalizado para la paginación

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
            'estado' => 'required|string|max:1',
            'anio' => 'required|integer',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $periodo = Periodo::create($validatedData);

        return redirect()->route('periodo.index')->with('success', 'Periodo creado con exito.');
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
            'estado' => 'required|boolean',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $periodo->update([
            'estado' => $request->estado,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('periodo.index')
            ->with('success', 'Estado y descripción del periodo actualizados correctamente.');
    }

    public function destroy($id)
    {
        $periodo = Periodo::find($id);
        if (!$periodo) {
            return redirect()->route('periodo.index')->with('error', 'Periodo no encontrado.');
        }
        $periodo->delete();
        return redirect()->route('periodo.index')->with('success', 'Periodo eliminado con exito.');
    }

    public function show($id)
    {
        $periodo = Periodo::find($id);
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
