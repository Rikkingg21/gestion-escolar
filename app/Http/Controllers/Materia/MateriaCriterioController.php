<?php

namespace App\Http\Controllers\Materia;

use App\Http\Controllers\Controller;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Grado;

class MateriaCriterioController extends Controller
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

    public function index($id, Request $request)
{
    $currentYear = date('Y');
    $selectedYear = $request->input('anio', $currentYear);

    // Determinar el tipo de ID recibido (materia, grado o competencia)
    $tipoFiltro = $this->determinarTipoFiltro($id); // Nueva función helper

    // Consulta base con eager loading
    $query = MateriaCriterio::with(['materia', 'grado', 'materiaCompetencia'])
        ->where($tipoFiltro['campo'], $id)
        ->where('anio', $selectedYear);

    // Aplicar filtro de grado si existe
    if ($request->has('grado_id') && $request->grado_id != '') {
        $query->where('grado_id', $request->grado_id);
    }

    // Ordenar usando joins en lugar de subconsultas
    $query->join('grados', 'grados.id', '=', 'materia_criterios.grado_id')
        ->orderByRaw("
            CASE grados.nivel
                WHEN 'Primaria' THEN 1
                WHEN 'Secundaria' THEN 2
                ELSE 3
            END,
            grados.grado ASC,
            grados.seccion ASC
        ")
        ->select('materia_criterios.*'); // Para evitar ambigüedad

    $materiaCriterios = $query->get();
    $materia = Materia::find($tipoFiltro['campo'] == 'materia_id' ? $id : null);

    // Obtener años disponibles para el filtro
    $anios = MateriaCriterio::where($tipoFiltro['campo'], $id)
                ->distinct()
                ->pluck('anio')
                ->sort();

    // Obtener grados disponibles para el filtro
    $gradosDisponibles = Grado::whereIn('id', function($query) use ($id, $tipoFiltro) {
            $query->select('grado_id')
                ->from('materia_criterios')
                ->where($tipoFiltro['campo'], $id);
        })
        ->orderByRaw("
            CASE nivel
                WHEN 'Primaria' THEN 1
                WHEN 'Secundaria' THEN 2
                ELSE 3
            END,
            grado ASC,
            seccion ASC
        ")
        ->get();

    return view('materia.materiacriterio.index', [
        'materiaCriterios' => $materiaCriterios,
        'materia' => $materia,
        'id' => $id,
        'anios' => $anios,
        'gradosDisponibles' => $gradosDisponibles,
        'selectedYear' => $selectedYear,
        'tipoFiltro' => $tipoFiltro['tipo'] // Para usar en la vista si es necesario
    ]);
}

// Función helper para determinar el tipo de filtro
protected function determinarTipoFiltro($id)
{
    // Aquí deberías implementar lógica para determinar si el ID es de materia, grado o competencia
    // Esto es un ejemplo básico - ajusta según tu necesidad
    if (Materia::where('id', $id)->exists()) {
        return ['campo' => 'materia_id', 'tipo' => 'materia'];
    } elseif (Grado::where('id', $id)->exists()) {
        return ['campo' => 'grado_id', 'tipo' => 'grado'];
    } else {
        return ['campo' => 'materia_competencia_id', 'tipo' => 'competencia'];
    }
}

    public function create($id)
    {
        // Obtener la materia principal
        $materia = Materia::findOrFail($id);

        // Obtener grados ordenados: primero primaria, luego secundaria, ordenados por grado y sección
        $grados = Grado::where('estado', 1)
                    ->orderByRaw("
                        CASE
                            WHEN nivel = 'Primaria' THEN 1
                            WHEN nivel = 'Secundaria' THEN 2
                            ELSE 3
                        END,
                        grado ASC,
                        seccion ASC
                    ")
                    ->get();

        $competencias = MateriaCompetencia::where('materia_id', $id)->get();

        $competencia = null;
        if(request()->has('competencia_id')) {
            $competencia = MateriaCompetencia::find(request('competencia_id'));
        }

        return view('materia.materiacriterio.create', [
            'materia' => $materia,
            'grados' => $grados,
            'competencias' => $competencias,
            'competencia' => $competencia,
            'anios' => range(date('Y') - 1, date('Y') + 1)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'materia_competencia_id' => 'required|exists:materia_competencias,id',
            'grados' => 'required|array',
            'grados.*' => 'exists:grados,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'anio' => 'required|numeric|min:2020|max:2030'
        ]);

        // Crear un criterio por cada grado seleccionado
        foreach ($request->grados as $grado_id) {
            MateriaCriterio::create([
                'materia_id' => $request->materia_id,
                'materia_competencia_id' => $request->materia_competencia_id,
                'grado_id' => $grado_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'anio' => $request->anio
            ]);
        }

        return redirect()
            ->route('materiacriterio.index', $request->materia_id)
            ->with('success', 'Criterios creados exitosamente para los grados seleccionados');
    }


    public function edit($id)
    {
        // Obtener el criterio específico
        $criterio = MateriaCriterio::findOrFail($id);

        // Obtener todos los criterios con el mismo nombre, materia y competencia (para edición múltiple)
        $criteriosRelacionados = MateriaCriterio::where('nombre', $criterio->nombre)
            ->where('materia_id', $criterio->materia_id)
            ->where('materia_competencia_id', $criterio->materia_competencia_id)
            ->where('anio', $criterio->anio)
            ->get();

        // Obtener datos para los selects
        $materia = Materia::findOrFail($criterio->materia_id);
        $grados = Grado::where('estado', 1)
                    ->orderByRaw("
                        CASE
                            WHEN nivel = 'Primaria' THEN 1
                            WHEN nivel = 'Secundaria' THEN 2
                            ELSE 3
                        END,
                        grado ASC,
                        seccion ASC
                    ")
                    ->get();

        $competencias = MateriaCompetencia::where('materia_id', $criterio->materia_id)->get();

        return view('materia.materiacriterio.edit', [
            'criterio' => $criterio,
            'criteriosRelacionados' => $criteriosRelacionados,
            'materia' => $materia,
            'grados' => $grados,
            'competencias' => $competencias,
            'anios' => range(date('Y') - 1, date('Y') + 1),
            'gradosSeleccionados' => $criteriosRelacionados->pluck('grado_id')->toArray()
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'materia_competencia_id' => 'required|exists:materia_competencias,id',
            'grados' => 'required|array',
            'grados.*' => 'exists:grados,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'anio' => 'required|numeric|min:2020|max:2030'
        ]);

        // 1. Actualizar el criterio específico que se está editando
        $criterio = MateriaCriterio::findOrFail($id);
        $criterio->update([
            'materia_competencia_id' => $request->materia_competencia_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'anio' => $request->anio,
            'grado_id' => $request->grados[0] // Tomamos el primer grado seleccionado
        ]);

        // 2. Manejar grados adicionales seleccionados
        if (count($request->grados) > 1) {
            // Obtener los grados adicionales (excluyendo el primero que ya actualizamos)
            $gradosAdicionales = array_slice($request->grados, 1);

            // Crear nuevos criterios para los grados adicionales
            foreach ($gradosAdicionales as $grado_id) {
                MateriaCriterio::create([
                    'materia_id' => $request->materia_id,
                    'materia_competencia_id' => $request->materia_competencia_id,
                    'grado_id' => $grado_id,
                    'nombre' => $request->nombre,
                    'descripcion' => $request->descripcion,
                    'anio' => $request->anio
                ]);
            }
        }

        return redirect()
            ->route('materiacriterio.index', $request->materia_id)
            ->with('success', 'Criterio actualizado exitosamente');
    }

    public function destroy($id)
    {
        try {
            $criterio = Materiacriterio::findOrFail($id);
            $competenciaId = $criterio->materia_competencia_id;
            $criterio->delete();

            return redirect()->route('materiacriterio.index', $competenciaId)
                ->with('success', 'Criterio eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('materiacriterio.index', $competenciaId ?? 0)
                ->with('error', 'Error al eliminar el criterio: ' . $e->getMessage());
        }
    }
}
