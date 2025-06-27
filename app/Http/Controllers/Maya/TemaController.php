<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\Maya\Tema;
use App\Models\Maya\Clase;
use App\Models\Maya\Semana;
use App\Models\Maya\Cursogradosecnivanio;

class TemaController extends Controller
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
        $clase_id = $request->clase_id;
        $clase = Clase::findOrFail($clase_id);

        $ultimoOrden = Tema::where('clase_id', $clase_id)->max('orden');
        // Obtener los temas ocupados para esta clase
        $ocupadoTemas = Tema::where('clase_id', $clase_id)
            ->pluck('orden')
            ->toArray();

        return view('modulos.tema.create', compact('clase', 'ocupadoTemas', 'ultimoOrden'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'clase_id' => 'required|exists:maya_clases,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'orden' => 'required|integer|min:1',
        ]);

        if (Tema::where('clase_id', $request->clase_id)->where('orden', $request->orden)->exists()) {
            return redirect()->back()->withErrors(['orden' => 'Ya existe un tema con este orden.']);
        }
        Tema::create([
            'clase_id' => $request->clase_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'orden' => $request->orden,
        ]);

        $semana = Semana::findOrFail($request->semana_id);
        $unidad = $semana->unidad;
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Tema creado correctamente.');
    }
    public function edit($id)
    {
        $tema = Tema::findOrFail($id);
        $clase = $tema->clase;
        $ocupadoTemas = Tema::where('clase_id', $clase->id)
            ->where('id', '!=', $tema->id) // Exclude the current tema
            ->pluck('orden')
            ->toArray();
        $semana = $clase->semana;
        $unidad = $semana->unidad;
        $bimestre = $unidad->bimestre;
        $anio = $bimestre->cursoGradoSecNivAnio->anio ?? date('Y');
        return view('modulos.tema.edit', compact('tema', 'clase', 'ocupadoTemas', 'anio'));
    }

    public function update(Request $request, Tema $tema)
    {
        $semana = Semana::findOrFail($request->semana_id);
        $unidad = $semana->unidad;
        $maya = $unidad ? $unidad->bimestre->cursoGradoSecNivAnio : null;
        $anio = $maya ? $maya->anio : date('Y');
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
        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Tema actualizado correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        $tema = Tema::findOrFail($id);
        $maya = Cursogradosecnivanio::find($tema->clase->semana->unidad->bimestre->cursoGradoSecNivAnio_id);
        $anio = $request->anio ?? $maya->anio ?? date('Y');
        $tema->delete();

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Tema eliminado correctamente.');
    }
}
