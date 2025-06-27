<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\Maya\Clase;
use App\Models\Maya\Semana;
use App\Models\Maya\Cursogradosecnivanio;

class ClaseController extends Controller
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
        $semana_id = $request->semana_id;
        $semana = Semana::findOrFail($semana_id);

        // Obtener las clases ocupadas para esta semana
        $ocupadoClases = \App\Models\Maya\Clase::where('semana_id', $semana_id)
            ->pluck('fecha_clase')
            ->toArray();

        return view('modulos.clase.create', compact('semana', 'ocupadoClases'));
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

        // Obtener el ID de la unidad asociada a la semana
        $semana = Semana::findOrFail($request->semana_id);
        $unidad = $semana->unidad;
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Clase creada correctamente.');

    }
    public function edit($id)
    {
        $clase = Clase::findOrFail($id);
        $semana = $clase->semana;
        $unidad = $semana->unidad;
        $bimestre = $unidad->bimestre;
        $ocupadoClases = Clase::where('semana_id', $semana->id)
            ->where('id', '!=', $clase->id)
            ->pluck('fecha_clase')
            ->toArray();
        $anio = $bimestre->cursoGradoSecNivAnio->anio ?? date('Y');
        return view('modulos.clase.edit', compact('clase', 'semana', 'ocupadoClases', 'anio'));
    }
    public function update(Request $request, Clase $clase)
    {
                // Obtener el anio de la unidad asociada a la semana
        $semana = Semana::findOrFail($request->semana_id);
        $unidad = $semana->unidad;
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');
        $request->validate([
            'semana_id' => 'required|exists:maya_semanas,id',
            'fecha_clase' => 'required|date',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $clase->update([
            'semana_id' => $request->semana_id,
            'fecha_clase' => $request->fecha_clase,
            'descripcion' => $request->descripcion,
        ]);


        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Clase actualizada correctamente.');
    }
    public function destroy(Request $request, $id)
    {
        $clase = Clase::findOrFail($id);
        $maya = Cursogradosecnivanio::find($clase->semana->unidad->bimestre->cursoGradoSecNivAnio_id);
        $anio = $request->anio ?? $maya->anio ?? date('Y');

        $clase->delete();
        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Clase eliminada correctamente.');
    }
}
