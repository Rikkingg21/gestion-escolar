<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Periodo;
use App\Models\Periodobimestre;

class PeriodobimestreController extends Controller
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

    public function index($nombre_periodo)
    {
        $periodo = Periodo::where('nombre', $nombre_periodo)->firstOrFail();
        $bimestres = Periodobimestre::where('periodo_id', $periodo->id)
            ->orderBy('fecha_inicio', 'asc')
            ->paginate(10);

        return view('periodobimestre.index', compact('periodo', 'bimestres'));
    }

    public function store(Request $request, $nombre_periodo)
    {
        $periodo = Periodo::where('nombre', $nombre_periodo)->firstOrFail();

        $request->validate([
            'bimestre' => 'required|string|max:100',
            'fecha_inicio' => 'required|date|after_or_equal:' . $periodo->fecha_inicio . '|before_or_equal:' . $periodo->fecha_fin,
            'fecha_fin' => 'required|date|after:fecha_inicio|before_or_equal:' . $periodo->fecha_fin,
            'tipo_bimestre' => 'required|in:A,R',
        ]);

        // Verificar que no haya superposición de fechas
        $superposicion = Periodobimestre::where('periodo_id', $periodo->id)
            ->where(function($query) use ($request) {
                $query->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin])
                      ->orWhereBetween('fecha_fin', [$request->fecha_inicio, $request->fecha_fin])
                      ->orWhere(function($q) use ($request) {
                          $q->where('fecha_inicio', '<=', $request->fecha_inicio)
                            ->where('fecha_fin', '>=', $request->fecha_fin);
                      });
            })->exists();

        if ($superposicion) {
            return redirect()->back()->with('error', 'Las fechas del bimestre se superponen con otro bimestre existente.');
        }

        Periodobimestre::create([
            'periodo_id' => $periodo->id,
            'bimestre' => $request->bimestre,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'tipo_bimestre' => $request->tipo_bimestre,
        ]);

        return redirect()->route('periodobimestre.index', $periodo->nombre)
            ->with('success', 'Bimestre creado exitosamente.');
    }

    public function update(Request $request, $nombre_periodo, $bimestre_id)
    {
        $periodo = Periodo::where('nombre', $nombre_periodo)->firstOrFail();
        $bimestre = Periodobimestre::where('id', $bimestre_id)
            ->where('periodo_id', $periodo->id)
            ->firstOrFail();

        $request->validate([
            'bimestre' => 'required|string|max:100',
            'fecha_inicio' => 'required|date|after_or_equal:' . $periodo->fecha_inicio . '|before_or_equal:' . $periodo->fecha_fin,
            'fecha_fin' => 'required|date|after:fecha_inicio|before_or_equal:' . $periodo->fecha_fin,
            'tipo_bimestre' => 'required|in:A,R',
        ]);

        // Verificar superposición excluyendo el bimestre actual
        $superposicion = Periodobimestre::where('periodo_id', $periodo->id)
            ->where('id', '!=', $bimestre->id)
            ->where(function($query) use ($request) {
                $query->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin])
                      ->orWhereBetween('fecha_fin', [$request->fecha_inicio, $request->fecha_fin])
                      ->orWhere(function($q) use ($request) {
                          $q->where('fecha_inicio', '<=', $request->fecha_inicio)
                            ->where('fecha_fin', '>=', $request->fecha_fin);
                      });
            })->exists();

        if ($superposicion) {
            return redirect()->back()->with('error', 'Las fechas del bimestre se superponen con otro bimestre existente.');
        }

        $bimestre->update([
            'bimestre' => $request->bimestre,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'tipo_bimestre' => $request->tipo_bimestre,
        ]);

        return redirect()->route('periodobimestre.index', $periodo->nombre)
            ->with('success', 'Bimestre actualizado exitosamente.');
    }

    public function destroy($nombre_periodo, $bimestre_id)
    {
        $periodo = Periodo::where('nombre', $nombre_periodo)->firstOrFail();
        $bimestre = Periodobimestre::where('id', $bimestre_id)
            ->where('periodo_id', $periodo->id)
            ->firstOrFail();

        $bimestre->delete();

        return redirect()->route('periodobimestre.index', $periodo->nombre)
            ->with('success', 'Bimestre eliminado exitosamente.');
    }
}
