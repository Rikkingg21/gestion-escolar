<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Criterio;
use Illuminate\Http\Request;

class CriterioController extends Controller
{
    public function index($tema_id)
    {
        $criterios = Criterio::where('tema_id', $tema_id)->orderBy('orden')->get();
        $tema = \App\Models\Maya\Tema::findOrFail($tema_id);
        $clase_id = $tema->clase_id;
        $semana_id = $tema->clase->semana_id;
        $unidad_id = $tema->clase->semana->unidad_id;
        $bimestre_id = $tema->clase->semana->unidad->bimestre_id;

        return view('criterio.index', compact('criterios', 'tema_id', 'clase_id', 'semana_id', 'unidad_id', 'bimestre_id'));
    }

    public function create($tema_id)
    {
        $tema = \App\Models\Maya\Tema::with(['clase.semana.unidad'])->findOrFail($tema_id);
        $unidad = $tema->clase->semana->unidad;

        // Obtener datos del tema actual
        $ultimoOrden = \App\Models\Maya\Criterio::where('tema_id', $tema_id)
                        ->max('orden') ?? 0;

        $ocupadoCriterios = \App\Models\Maya\Criterio::where('tema_id', $tema_id)
                            ->pluck('orden')
                            ->toArray();

        // Calcular peso ocupado en la UNIDAD completa
        $pesoOcupado = \App\Models\Maya\Criterio::whereHas('tema.clase.semana', function($q) use ($unidad) {
                            $q->where('unidad_id', $unidad->id);
                        })->sum('peso');

        return view('criterio.create', [
            'tema' => $tema,
            'unidad' => $unidad,
            'ultimoOrden' => $ultimoOrden,
            'ocupadoCriterios' => $ocupadoCriterios,
            'pesoOcupado' => $pesoOcupado,
            'pesoDisponible' => 100 - $pesoOcupado
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'tema_id' => 'required|exists:maya_temas,id',
            'descripcion' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'peso' => 'required|numeric|min:0',
            'orden' => 'required|integer|min:1',
        ]);

        // no puede haver varios criterios con el mismo orden
        if (Criterio::where('tema_id', $request->tema_id)->where('orden', $request->orden)->exists()) {
            return redirect()->back()->withErrors(['orden' => 'Ya existe un criterio con este orden.']);
        }
        // peso del citerio no puede ser mayor a 100 en total por unidad
        $totalPeso = Criterio::where('tema_id', $request->tema_id)->sum('peso');
        if ($totalPeso + $request->peso > 100) {
            return redirect()->back()->withErrors(['peso' => 'El peso total de los criterios no puede exceder 100.']);
        }
        Criterio::create([
            'tema_id' => $request->tema_id,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'peso' => $request->peso,
            'orden' => $request->orden,
        ]);
        return redirect()->route('criterios.index', $request->tema_id)
            ->with('success', 'Criterio creado correctamente.');
    }
    public function edit(Criterio $criterio)
    {
        $tema = $criterio->tema;
        $ocupadoCriterios = Criterio::where('tema_id', $tema->id)
            ->where('id', '!=', $criterio->id) // Exclude the current criterio
            ->pluck('orden')
            ->toArray();
        // Obtener peso ocupado por los criterios de este tema en relacion a la unidad
        $pesoOcupado = Criterio::where('tema_id', $tema->id)->sum('peso');

        return view('criterio.edit', compact('criterio', 'tema', 'ocupadoCriterios', 'pesoOcupado'));
    }
    public function update(Request $request, Criterio $criterio)
    {
        $request->validate([
            'tema_id' => 'required|exists:maya_temas,id',
            'descripcion' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'peso' => 'required|numeric|min:0',
            'orden' => 'required|integer|min:1',
        ]);

        // no puede haver varios criterios con el mismo orden
        if (Criterio::where('tema_id', $request->tema_id)->where('orden', $request->orden)->where('id', '!=', $criterio->id)->exists()) {
            return redirect()->back()->withErrors(['orden' => 'Ya existe un criterio con este orden.']);
        }
        // peso del citerio no puede ser mayor a 100 en total por unidad
        $totalPeso = Criterio::where('tema_id', $request->tema_id)->sum('peso') - $criterio->peso;
        if ($totalPeso + $request->peso > 100) {
            return redirect()->back()->withErrors(['peso' => 'El peso total de los criterios no puede exceder 100.']);
        }
        $criterio->update([
            'tema_id' => $request->tema_id,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'peso' => $request->peso,
            'orden' => $request->orden,
        ]);
        return redirect()->route('criterios.index', $request->tema_id)
            ->with('success', 'Criterio actualizado correctamente.');
    }

    public function destroy($id)
    {
        $criterio = Criterio::findOrFail($id);
        $tema_id = $criterio->tema_id;
        $criterio->delete();
        return redirect()->route('criterios.index', $tema_id)
            ->with('success', 'Criterio eliminado correctamente.');
    }
}
