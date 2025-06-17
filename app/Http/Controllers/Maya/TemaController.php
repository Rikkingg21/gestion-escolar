<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Tema;
use Illuminate\Http\Request;

class TemaController extends Controller
{
    public function index($clase_id)
    {
        $temas = Tema::where('clase_id', $clase_id)->orderBy('orden')->get();
        $clase = \App\Models\Maya\Clase::findOrFail($clase_id);
        $semana_id = $clase->semana_id;
        $unidad_id = $clase->semana->unidad_id;
        $bimestre_id = $clase->semana->unidad->bimestre_id;

        return view('tema.index', compact('temas', 'clase_id', 'semana_id', 'unidad_id', 'bimestre_id'));

    }

    public function create($clase_id)
    {
        $clase = \App\Models\Maya\Clase::findOrFail($clase_id);
        $ultimoOrden = Tema::where('clase_id', $clase_id)->max('orden');
        // Obtener los temas ocupados para esta clase
        $ocupadoTemas = \App\Models\Maya\Tema::where('clase_id', $clase_id)
            ->pluck('orden')
            ->toArray();

        return view('tema.create', compact('clase', 'ocupadoTemas', 'ultimoOrden'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'clase_id' => 'required|exists:maya_clases,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'orden' => 'required|integer|min:1',
        ]);

        // no puede haver varios temas con el mismo orden

        if (Tema::where('clase_id', $request->clase_id)->where('orden', $request->orden)->exists()) {
            return redirect()->back()->withErrors(['orden' => 'Ya existe un tema con este orden.']);
        }
        Tema::create([
            'clase_id' => $request->clase_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'orden' => $request->orden,
        ]);
        return redirect()->route('temas.index', $request->clase_id)
            ->with('success', 'Tema creado correctamente.');
    }
    public function edit(Tema $tema)
    {
        $clase = $tema->clase;
        $ocupadoTemas = \App\Models\Maya\Tema::where('clase_id', $clase->id)
            ->where('id', '!=', $tema->id) // Exclude the current tema
            ->pluck('orden')
            ->toArray();
        return view('tema.edit', compact('tema', 'clase', 'ocupadoTemas'));
    }

    public function update(Request $request, Tema $tema)
    {
        $request->validate([
            'clase_id' => 'required|exists:maya_clases,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'orden' => 'required|integer|min:1',
        ]);

        // no puede haver varios temas con el mismo orden
        if (Tema::where('clase_id', $request->clase_id)
                ->where('orden', $request->orden)
                ->where('id', '!=', $tema->id) // Exclude the current tema
                ->exists()) {
            return redirect()->back()->withErrors(['orden' => 'Ya existe un tema con este orden.']);
        }

        // Update the tema
        $tema->update([
            'clase_id' => $request->clase_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'orden' => $request->orden,
        ]);
        return redirect()->route('temas.index', $tema->clase_id)
            ->with('success', 'Tema actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tema = Tema::findOrFail($id);
        $clase_id = $tema->clase_id;
        $tema->delete();

        return redirect()->route('temas.index', $clase_id)
            ->with('success', 'Tema eliminado correctamente.');
    }
}
