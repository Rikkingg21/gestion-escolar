<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Unidad;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    public function index($bimestre_id)
    {
        $unidades = Unidad::where('bimestre_id', $bimestre_id)->get();
        $bimestre = \App\Models\Maya\Bimestre::findOrFail($bimestre_id);
        $curso_grado_sec_niv_anio_id = $bimestre->curso_grado_sec_niv_anio_id;

        return view('unidad.index', compact('unidades', 'bimestre_id', 'curso_grado_sec_niv_anio_id'));
    }
    public function create($bimestre_id)
    {
        $bimestre = \App\Models\Maya\Bimestre::findOrFail($bimestre_id);
        // Obtener las unidades ocupados para este bimestre
        $ocupadoUnidades = \App\Models\Maya\Unidad::where('bimestre_id', $bimestre_id)
            ->pluck('nombre')
            ->toArray();

        return view('unidad.create', compact('bimestre', 'ocupadoUnidades'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'bimestre_id' => 'required|exists:maya_bimestres,id',
            'unidad' => [
                'required',
                'in: 1,2,3,4,5,6,7,8',
                function($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Maya\Unidad::where('bimestre_id', $request->bimestre_id)
                        ->where('nombre', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Esta unidad ya está registrada para este bimestre.');
                    }
                }
            ],
        ]);

        \App\Models\Maya\Unidad::create([
            'bimestre_id' => $request->bimestre_id,
            'nombre' => $request->unidad,
        ]);

        return redirect()->route('unidades.index', $request->bimestre_id)
            ->with('success', 'Unidad creada correctamente.');
    }

    public function show(Unidad $unidad)
    {
        //
    }

    public function edit(Unidad $unidad)
    {
        // Obtener el bimestre relacionado
        $bimestre = $unidad->bimestre; // Usando la relación definida en el modelo

        // Unidades ocupadas (excluyendo la actual)
        $ocupadoUnidades = Unidad::where('bimestre_id', $unidad->bimestre_id)
            ->where('id', '!=', $unidad->id)
            ->pluck('nombre')  // Cambiado de 'nombre' a 'numero' para coincidir con tu vista
            ->toArray();

        return view('unidad.edit', compact('unidad', 'bimestre', 'ocupadoUnidades'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unidad $unidad)
    {
        $request->validate([
            'unidad' => 'required|numeric|between:1,8',
            'bimestre_id' => 'required|exists:maya_bimestres,id'
        ]);
        $unidad->update([
            'nombre' => $request->unidad, // Mantienes 'nombre' como campo en DB
            'bimestre_id' => $request->bimestre_id
        ]);

        return redirect()->route('unidades.index', $unidad->bimestre_id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $unidad = Unidad::findOrFail($id);
        $unidad->delete();
        return redirect()->route('unidades.index', $unidad->bimestre_id)
            ->with('success', 'Unidad eliminada correctamente.');
    }
}
