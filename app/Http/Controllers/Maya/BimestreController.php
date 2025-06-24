<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Maya\Bimestre;
use Illuminate\Http\Request;

class BimestreController extends Controller
{

    public function create(Request $request)
    {
        $curso_grado_sec_niv_anio_id = $request->curso_grado_sec_niv_anio_id;

        // Obtener los bimestres ocupados (nombres: 1,2,3,4)
        $ocupadoBimestres = Bimestre::where('curso_grado_sec_niv_anio_id', $curso_grado_sec_niv_anio_id)
            ->pluck('nombre')
            ->toArray();

        // Obtener todos los cursos para mostrar info en la vista
        $cursos = Cursogradosecnivanio::with(['materia', 'grado'])->get();

        return view('modulos.bimestre.create', compact('curso_grado_sec_niv_anio_id', 'ocupadoBimestres', 'cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'curso_grado_sec_niv_anio_id' => 'required|exists:maya_curso_grado_sec_niv_anios,id',
            'bimestre' => [
                'required',
                'in:1,2,3,4',
                function($attribute, $value, $fail) use ($request) {
                    $exists = Bimestre::where('curso_grado_sec_niv_anio_id', $request->curso_grado_sec_niv_anio_id)
                        ->where('nombre', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Este bimestre ya está registrado para este curso.');
                    }
                }
            ],
        ]);

        Bimestre::create([
            'curso_grado_sec_niv_anio_id' => $request->curso_grado_sec_niv_anio_id,
            'nombre' => $request->bimestre,
        ]);

        return redirect()->route('maya.index')
            ->with('success', 'Bimestre creado correctamente.');
    }

    public function show(Bimestre $bimestre)
    {
        //
    }

    public function edit($id)
    {
        $bimestre = Bimestre::findOrFail($id);
        $cursos = Cursogradosecnivanio::with(['materia', 'grado'])->get();
        // Obtener los bimestres ocupados para este curso, excepto el actual
        $ocupadoBimestres = \App\Models\Maya\Bimestre::where('curso_grado_sec_niv_anio_id', $bimestre->curso_grado_sec_niv_anio_id)
            ->where('id', '!=', $bimestre->id)
            ->pluck('nombre')
            ->toArray();
        return view('modulos.bimestre.edit', compact('bimestre', 'cursos', 'ocupadoBimestres'));
    }

    public function update(Request $request, $id)
    {
        $bimestre = Bimestre::findOrFail($id);
        $request->validate([
            'curso_grado_sec_niv_anio_id' => 'required|exists:maya_curso_grado_sec_niv_anios,id',
            'bimestre' => [
                'required',
                'in:1,2,3,4',
                // Único por curso, excepto el actual
                function($attribute, $value, $fail) use ($request, $bimestre) {
                    $exists = Bimestre::where('curso_grado_sec_niv_anio_id', $request->curso_grado_sec_niv_anio_id)
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

        return redirect()->route('maya.index')
            ->with('success', 'Bimestre actualizado correctamente.');
    }
    public function destroy($id)
    {
        $bimestre =Bimestre::findOrFail($id);
        $bimestre->delete();
        return redirect()->route('modulos.maya.index', $bimestre->curso_grado_sec_niv_anio_id)
            ->with('success', 'Bimestre eliminado correctamente.');
    }

}
