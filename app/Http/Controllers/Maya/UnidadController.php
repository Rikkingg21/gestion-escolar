<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Unidad;
use Illuminate\Http\Request;

use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;

class UnidadController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }
    public function create(Request $request)
    {
        $bimestre_id = $request->bimestre_id;
        $bimestre = Bimestre::findOrFail($bimestre_id);
        // Obtener las unidades ocupados para este bimestre
        $ocupadoUnidades = Unidad::where('bimestre_id', $bimestre_id)
            ->pluck('nombre')
            ->toArray();

        return view('modulos.unidad.create', compact('bimestre', 'ocupadoUnidades'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'bimestre_id' => 'required|exists:maya_bimestres,id',
            'unidad' => [
                'required',
                'in:1,2,3,4,5,6,7,8',
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

        $unidad = Unidad::create([
            'bimestre_id' => $request->bimestre_id,
            'nombre' => $request->unidad,
        ]);

        // Obtener el año del curso relacionado
        $bimestre = Bimestre::find($unidad->bimestre_id);
        $maya = $bimestre ? $bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Unidad creada correctamente.');
    }
    public function edit($id)
    {
        $unidad = Unidad::findOrFail($id);
        $bimestre = $unidad->bimestre; // Relación desde la unidad
        $anio = $bimestre->cursoGradoSecNivAnio->anio ?? date('Y');
        $ocupadoUnidades = Unidad::where('bimestre_id', $unidad->bimestre_id)
            ->where('id', '!=', $unidad->id)
            ->pluck('nombre')
            ->toArray();

        return view('modulos.unidad.edit', compact('bimestre', 'unidad', 'ocupadoUnidades', 'anio'));
    }
    public function update(Request $request, Unidad $unidad)
    {
        $request->validate([
            'unidad' => 'required|numeric|between:1,8',
            'bimestre_id' => 'required|exists:maya_bimestres,id'
        ]);
        $unidad->update([
            'nombre' => $request->unidad,
            'bimestre_id' => $request->bimestre_id
        ]);

        // Obtener el año del curso relacionado
        $bimestre = Bimestre::find($unidad->bimestre_id);
        $maya = $bimestre ? $bimestre->cursoGradoSecNivAnio : null;
        $anio = $request->anio ?? ($maya ? $maya->anio : date('Y'));

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Unidad actualizada correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        $unidad = Unidad::findOrFail($id);

        $maya = Cursogradosecnivanio::find($unidad->curso_grado_sec_niv_anio_id);
        $anio = $request->anio ?? $maya->anio ?? date('Y');

        $unidad->delete();

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Unidad eliminada correctamente.');
    }
}
