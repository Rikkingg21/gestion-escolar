<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Clase;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index($semana_id)
    {
        $clases = Clase::where('semana_id', $semana_id)->get();
        $semana = \App\Models\Maya\Semana::findOrFail($semana_id);
        $unidad_id = $semana->unidad_id;
        $bimestre_id = $semana->unidad->bimestre_id;

        return view('clase.index', compact('clases', 'semana_id', 'unidad_id', 'bimestre_id'));
    }
    public function create($semana_id)
    {
        $semana = \App\Models\Maya\Semana::findOrFail($semana_id);
        // Obtener las clases ocupadas para esta semana
        $ocupadoClases = \App\Models\Maya\Clase::where('semana_id', $semana_id)
            ->pluck('fecha_clase')
            ->toArray();

        return view('clase.create', compact('semana', 'ocupadoClases'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'semana_id' => 'required|exists:maya_semanas,id',
            'fecha_clase' => 'required|date',
            'descripcion' => 'nullable|string|max:255',
        ]);

        // puede haver varias clases en la misma fecha
        Clase::create([
            'semana_id' => $request->semana_id,
            'fecha_clase' => $request->fecha_clase,
            'descripcion' => $request->descripcion,
        ]);
        return redirect()->route('clases.index', $request->semana_id)
            ->with('success', 'Clase creada correctamente.');

    }
    public function edit(Clase $clase)
    {
        $semana = $clase->semana;
        $ocupadoClases = \App\Models\Maya\Clase::where('semana_id', $semana->id)
            ->where('id', '!=', $clase->id) // Exclude the current class
            ->pluck('fecha_clase')
            ->toArray();
        return view('clase.edit', compact('clase', 'semana', 'ocupadoClases'));
    }
    public function update(Request $request, Clase $clase)
    {
        $request->validate([
            'semana_id' => 'required|exists:maya_semanas,id',
            'fecha_clase' => 'required|date',
            'descripcion' => 'nullable|string|max:255',
        ]);

        // Update the class
        $clase->update([
            'semana_id' => $request->semana_id,
            'fecha_clase' => $request->fecha_clase,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('clases.index', $clase->semana_id)
            ->with('success', 'Clase actualizada correctamente.');
    }
    public function destroy($id)
    {
        $clase = Clase::findOrFail($id);
        $semana_id = $clase->semana_id;
        $clase->delete();

        return redirect()->route('clases.index', $semana_id)
            ->with('success', 'Clase eliminada correctamente.');
    }
}
