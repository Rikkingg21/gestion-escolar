<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\Maya\Criterio;
use App\Models\Maya\Tema;
use App\Models\Maya\Clase;
use App\Models\Maya\Semana;

use App\Models\Maya\Cursogradosecnivanio;

class CriterioController extends Controller
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
        $tema_id = $request->tema_id;
        $tema = Tema::findOrFail($tema_id);

        // Obtener datos del tema actual
        $ultimoOrden = Criterio::where('tema_id', $tema_id)->max('orden');

        $ocupadoCriterios = Criterio::where('tema_id', $tema_id)
                            ->pluck('orden')
                            ->toArray();
        return view('.modulos.criterio.create', compact('tema', 'ultimoOrden', 'ocupadoCriterios'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'tema_id' => 'required|exists:maya_temas,id',
            'descripcion' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'orden' => 'required|integer|min:1',
        ]);

        // no puede haver varios criterios con el mismo orden
        if (Criterio::where('tema_id', $request->tema_id)->where('orden', $request->orden)->exists()) {
            return redirect()->back()->withErrors(['orden' => 'Ya existe un criterio con este orden.']);
        }
        Criterio::create([
            'tema_id' => $request->tema_id,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'orden' => $request->orden,
        ]);

        $semana = Semana::findOrFail($request->semana_id);
        $unidad = $semana->unidad;
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Criterio creado correctamente.');
    }
    public function edit($id)
    {
        $criterio = Criterio::findOrFail($id);
        $tema = $criterio->tema;
        $clase = $tema->clase;
        $semana = $clase->semana;
        $unidad = $semana->unidad;
        $bimestre = $unidad->bimestre;
        $anio = $bimestre->cursoGradoSecNivAnio->anio ?? date('Y');

        $ocupadoCriterios = Criterio::where('tema_id', $tema->id)
            ->where('id', '!=', $criterio->id) // Exclude the current criterio
            ->pluck('orden')
            ->toArray();

       return view('modulos.criterio.edit', compact('criterio', 'tema', 'ocupadoCriterios', 'anio'));
    }
    public function update(Request $request, Criterio $criterio)
    {
        $semana = Semana::findOrFail($request->semana_id);
        $unidad = $semana->unidad;
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');

        $request->validate([
            'tema_id' => 'required|exists:maya_temas,id',
            'descripcion' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'orden' => 'required|integer|min:1',
        ]);

        // no puede haver varios criterios con el mismo orden
        if (Criterio::where('tema_id', $request->tema_id)->where('orden', $request->orden)->where('id', '!=', $criterio->id)->exists()) {
            return redirect()->back()->withErrors(['orden' => 'Ya existe un criterio con este orden.']);
        }

        $criterio->update([
            'tema_id' => $request->tema_id,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'orden' => $request->orden,
        ]);
        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Criterio actualizado correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        $criterio = Criterio::findOrFail($id);
        $maya = Cursogradosecnivanio::find($criterio->tema->clase->semana->unidad->bimestre->cursoGradoSecNivAnio_id);
        $anio = $request->anio ?? $maya->anio ?? date('Y');

        $criterio->delete();
        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Criterio eliminado correctamente.');
    }
}
