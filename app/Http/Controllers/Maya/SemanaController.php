<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Semana;
use Illuminate\Http\Request;

class SemanaController extends Controller
{
    public function index($unidad_id)
    {
        $semanas = Semana::where('unidad_id', $unidad_id)->get();
        $unidad = \App\Models\Maya\Unidad::findOrFail($unidad_id);
        $bimestre_id = $unidad->bimestre_id;

        return view('semana.index', compact('semanas', 'unidad_id', 'bimestre_id'));
    }
    public function create($unidad_id)
    {
        $unidad = \App\Models\Maya\Unidad::findOrFail($unidad_id);
        // Obtener las semanas ocupadas para esta unidad
        $ocupadoSemanas = \App\Models\Maya\Semana::where('unidad_id', $unidad_id)
            ->pluck('nombre')
            ->toArray();

        return view('semana.create', compact('unidad', 'ocupadoSemanas'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'unidad_id' => 'required|exists:maya_unidades,id',
            'semana' => [
                'required',
                'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32',
                function($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Maya\Semana::where('unidad_id', $request->unidad_id)
                        ->where('nombre', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Esta semana ya está registrada para esta unidad.');
                    }
                }
            ],
        ]);

        \App\Models\Maya\Semana::create([
            'unidad_id' => $request->unidad_id,
            'nombre' => $request->semana,
        ]);

        return redirect()->route('semanas.index', $request->unidad_id)
            ->with('success', 'Semana creada correctamente.');
    }
    public function show(Semana $semana)
    {
        //
    }
    public function edit(Semana $semana)
    {
        $unidad = \App\Models\Maya\Unidad::findOrFail($semana->unidad_id);
        // Obtener las semanas ocupadas para esta unidad
        $ocupadoSemanas = \App\Models\Maya\Semana::where('unidad_id', $semana->unidad_id)
            ->pluck('nombre')
            ->toArray();

        return view('semana.edit', compact('semana', 'unidad', 'ocupadoSemanas'));
    }
    public function update(Request $request, Semana $semana)
    {
        $request->validate([
            'semana' => 'required|numeric|between:1,32',
            'unidad_id' => 'required|exists:maya_unidades,id',
        ]);

        $semana->update([
            'nombre' => $request->semana,
            'unidad_id' => $request->unidad_id,
        ]);

        return redirect()->route('semanas.index', $semana->unidad_id);
    }
    public function destroy($id)
    {
        $semana = Semana::findOrFail($id);
        $semana->delete();
        return redirect()->route('semanas.index', $semana->unidad_id)
            ->with('success', 'Semana eliminada correctamente.');
    }

}
