<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Bimestre;
use Illuminate\Http\Request;

class BimestreController extends Controller
{
    public function index($curso_grado_sec_niv_anio_id)
    {
        $bimestres = \App\Models\Maya\Bimestre::where('curso_grado_sec_niv_anio_id', $curso_grado_sec_niv_anio_id)->get();
        return view('bimestre.index', compact('bimestres', 'curso_grado_sec_niv_anio_id'));
    }
    public function create($curso_grado_sec_niv_anio_id)
    {
        $cursos = \App\Models\Maya\Cursogradosecnivanio::with(['materia', 'grado'])->get();
        // Obtener los bimestres ocupados para este curso
        $ocupadoBimestres = \App\Models\Maya\Bimestre::where('curso_grado_sec_niv_anio_id', $curso_grado_sec_niv_anio_id)
            ->pluck('nombre')
            ->toArray();
        return view('bimestre.create', compact('cursos', 'curso_grado_sec_niv_anio_id', 'ocupadoBimestres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'curso_grado_sec_niv_anio_id' => 'required|exists:maya_curso_grado_sec_niv_anios,id',
            'bimestre' => [
                'required',
                'in:1,2,3,4',
                function($attribute, $value, $fail) use ($request) {
                    $exists = \App\Models\Maya\Bimestre::where('curso_grado_sec_niv_anio_id', $request->curso_grado_sec_niv_anio_id)
                        ->where('nombre', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Este bimestre ya está registrado para este curso.');
                    }
                }
            ],
        ]);

        \App\Models\Maya\Bimestre::create([
            'curso_grado_sec_niv_anio_id' => $request->curso_grado_sec_niv_anio_id,
            'nombre' => $request->bimestre,
        ]);

        return redirect()->route('bimestres.index', $request->curso_grado_sec_niv_anio_id)
            ->with('success', 'Bimestre creado correctamente.');
    }

    public function show(Bimestre $bimestre)
    {
        //
    }

    public function edit(Bimestre $bimestre)
    {
        $cursos = \App\Models\Maya\Cursogradosecnivanio::with(['materia', 'grado'])->get();
        // Obtener los bimestres ocupados para este curso, excepto el actual
        $ocupadoBimestres = \App\Models\Maya\Bimestre::where('curso_grado_sec_niv_anio_id', $bimestre->curso_grado_sec_niv_anio_id)
            ->where('id', '!=', $bimestre->id)
            ->pluck('nombre')
            ->toArray();
        return view('bimestre.edit', compact('bimestre', 'cursos', 'ocupadoBimestres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bimestre $bimestre)
    {
        $request->validate([
            'curso_grado_sec_niv_anio_id' => 'required|exists:maya_curso_grado_sec_niv_anios,id',
            'bimestre' => [
                'required',
                'in:1,2,3,4',
                // Único por curso, excepto el actual
                function($attribute, $value, $fail) use ($request, $bimestre) {
                    $exists = \App\Models\Maya\Bimestre::where('curso_grado_sec_niv_anio_id', $request->curso_grado_sec_niv_anio_id)
                        ->where('nombre', $value)
                        ->where('id', '!=', $bimestre->id)
                        ->exists();
                    if ($exists) {
                        $fail('Este bimestre ya está registrado para este curso.');
                    }
                }
            ],
        ]);

        $bimestre->update([
            'curso_grado_sec_niv_anio_id' => $request->curso_grado_sec_niv_anio_id,
            'nombre' => $request->bimestre,
        ]);

        return redirect()->route('bimestres.index', $request->curso_grado_sec_niv_anio_id)
            ->with('success', 'Bimestre actualizado correctamente.');
    }
    public function destroy($id)
    {
        $bimestre =Bimestre::findOrFail($id);
        $bimestre->delete();
        return redirect()->route('bimestres.index', $bimestre->curso_grado_sec_niv_anio_id)
            ->with('success', 'Bimestre eliminado correctamente.');
    }
}
