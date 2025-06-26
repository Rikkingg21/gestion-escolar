<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Semana;
use Illuminate\Http\Request;

use App\Models\Maya\Unidad;
use App\Models\Maya\Cursogradosecnivanio;

class SemanaController extends Controller
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
        $unidad_id = $request->unidad_id;
        $unidad = Unidad::findOrFail($unidad_id);

        // 1. Obtener el bimestre de la unidad
        $bimestre = $unidad->bimestre;

        // 2. Obtener todas las unidades del bimestre
        $unidadesBimestre = $bimestre->unidades()->pluck('id');

        // 3. Obtener todas las semanas ocupadas en el bimestre
        $ocupadoSemanas = Semana::whereIn('unidad_id', $unidadesBimestre)
            ->pluck('nombre')
            ->toArray();

        return view('modulos.semana.create', compact('unidad', 'ocupadoSemanas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'unidad_id' => 'required|exists:maya_unidades,id',
            'semana' => [
                'required',
                'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32',
                function($attribute, $value, $fail) use ($request) {
                    $exists = Semana::where('unidad_id', $request->unidad_id)
                        ->where('nombre', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Esta semana ya está registrada para esta unidad.');
                    }
                }
            ],
        ]);

        Semana::create([
            'unidad_id' => $request->unidad_id,
            'nombre' => $request->semana,
        ]);

        $unidad = Unidad::find($request->unidad_id);
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Semana creada correctamente.');
    }
    public function edit($id)
    {
        $semana = Semana::findOrFail($id);
        $unidad = $semana->unidad; // Relación desde la semana
        $bimestre = $unidad->bimestre;

        // Todas las unidades del bimestre
        $unidadesBimestre = $bimestre->unidades()->pluck('id');

        // Semanas ocupadas en el bimestre, excepto la actual
        $ocupadoSemanas = Semana::whereIn('unidad_id', $unidadesBimestre)
            ->where('id', '!=', $semana->id)
            ->pluck('nombre')
            ->toArray();

        $anio = $bimestre->cursoGradoSecNivAnio->anio ?? date('Y');

        return view('modulos.semana.edit', compact('semana', 'unidad', 'ocupadoSemanas', 'anio'));
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

        // Obtener el año del curso relacionado
        $unidad = Unidad::find($semana->unidad_id);
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $request->anio ?? ($maya ? $maya->anio : date('Y'));

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Semana actualizada correctamente.');
    }
    public function destroy(Request $request, $id)
    {
        $semana = Semana::findOrFail($id);

        $maya = Cursogradosecnivanio::find($semana->unidad->bimestre->cursoGradoSecNivAnio_id);
        $anio = $request->anio ?? $maya->anio ?? date('Y');

        $semana->delete();
        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Semana eliminada correctamente.');
    }
}
