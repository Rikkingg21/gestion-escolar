<?php

namespace App\Http\Controllers\Maya;

use App\Http\Controllers\Controller;
use App\Models\Maya\Cursogradosecnivanio;
use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Grado;
use App\Models\Docente;
use App\Models\Materia\Materiacriterio;
use App\Models\Periodo;
use App\Models\User;

class MayaController extends Controller
{
    //moduleID 13 = Roles
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('13')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        $user = auth()->user();

        // Si el usuario tiene rol admin o director se muestran periodos con estado '1' y '0'
        if ($user->hasRole('admin') || $user->hasRole('director')) {
            $periodos = Periodo::all()
                ->sortByDesc('anio')
                ->sortBy('nombre')
                ->values();
        } else {
            $periodos = Periodo::where('estado', 1)
                ->orderBy('anio', 'desc')
                ->orderBy('nombre')
                ->get();
        }

        // Obtener el periodo seleccionado (por defecto el periodo activo o el primero)
        $periodoSeleccionadoId = $request->get('periodo_id');
        if ($periodos->isEmpty()) {
            return view('modulos.maya.index', [
                'periodos' => $periodos,
                'mayas' => collect(),
                'periodoSeleccionado' => null,
                'periodoSeleccionadoId' => null,
                'grados' => [],
                'materias' => [],
                'docentes' => null,
                'filters' => []
            ]);
        }
        // Si no se proporcionó un periodo_id y hay periodos disponibles
        if (!$periodoSeleccionadoId) {
            // Intentar encontrar un periodo activo (estado = 1)
            $periodoActivo = $periodos->firstWhere('estado', 1);

            // Si no hay periodo activo y el usuario es admin/director, usar el primero (aunque esté inactivo)
            if (!$periodoActivo && ($user->hasRole('admin') || $user->hasRole('director'))) {
                $periodoActivo = $periodos->first();
            }

            $periodoSeleccionadoId = $periodoActivo ? $periodoActivo->id : $periodos->first()->id;
        }


        // Obtener el periodo seleccionado
        $periodoSeleccionado = Periodo::find($periodoSeleccionadoId);

        // Obtener datos para los filtros con caché de grados con estado activo
        $grados = cache()->remember('grados_filter_activos', 3600, function() {
            return Grado::where('estado', '1')
                        ->orderBy('grado')
                        ->orderBy('seccion')
                        ->get();
        });

        $materias = cache()->remember('materias_filter', 3600, function() {
            return Materia::orderBy('nombre')->get();
        });

        // Solo cargar docentes si es admin/director
        $docentes = null;
        if ($user->hasRole('admin') || $user->hasRole('director')) {
            $docentes = Docente::with(['user' => function($query) {
                $query->select('id', 'nombre', 'apellido_paterno', 'apellido_materno');
            }])->get(['id', 'user_id']);
        }

        // Construir consulta base optimizada - filtrar por periodo
        $query = Cursogradosecnivanio::with([
                'grado' => function($q) {
                    $q->select('id', 'grado', 'seccion', 'nivel');
                },
                'materia' => function($q) {
                    $q->select('id', 'nombre');
                },
                'docente.user' => function($q) {
                    $q->select('id', 'nombre', 'apellido_paterno', 'apellido_materno');
                },
                'periodo' => function($q) {
                    $q->select('id', 'nombre', 'anio');
                }
            ])
            ->where('periodo_id', $periodoSeleccionadoId);

        // Aplicar filtros dinámicos
        $filters = $request->only(['grado_id', 'materia_id', 'docente_id']);

        if (!empty($filters['grado_id'])) {
            $query->where('grado_id', $filters['grado_id']);
        }

        if (!empty($filters['materia_id'])) {
            $query->where('materia_id', $filters['materia_id']);
        }

        if (($user->hasRole('admin') || $user->hasRole('director')) && !empty($filters['docente_id'])) {
            $query->where('docente_designado_id', $filters['docente_id']);
        }

        // Filtro para docentes
        if ($user->hasRole('docente')) {
            $query->where('docente_designado_id', $user->docente->id ?? 0);
        }

        // Ordenar resultados
        $mayas = $query->orderBy('grado_id')
                    ->orderBy('materia_id')
                    ->get();

        // Obtener bimestres disponibles para cada maya - ahora basado en el periodo
        foreach ($mayas as $maya) {
            $anioPeriodo = $periodoSeleccionado ? $periodoSeleccionado->anio : date('Y');

            $maya->bimestres_disponibles = Materiacriterio::where('materia_id', $maya->materia_id)
                ->where('grado_id', $maya->grado_id)
                ->where('anio', $anioPeriodo)
                ->select('bimestre')
                ->distinct()
                ->orderBy('bimestre')
                ->pluck('bimestre');

            // También podemos contar cuántos bimestres tienen notas registradas
            $maya->bimestres_con_notas = 0;
            if ($maya->bimestres_disponibles->isNotEmpty()) {
                // Aquí podrías agregar lógica para contar bimestres con notas
                // Por ejemplo:
                // $maya->bimestres_con_notas = Nota::whereHas('criterio', function($q) use ($maya) {
                //     $q->where('materia_id', $maya->materia_id)
                //       ->where('grado_id', $maya->grado_id);
                // })->distinct('bimestre')->count();
            }
        }

        return view('modulos.maya.index', [
            'mayas' => $mayas,
            'periodos' => $periodos,
            'periodoSeleccionado' => $periodoSeleccionado,
            'periodoSeleccionadoId' => $periodoSeleccionadoId,
            'grados' => $grados,
            'materias' => $materias,
            'docentes' => $docentes,
            'filters' => $filters
        ]);
    }
    public function create()
    {
        $materias = Materia::where('estado', 1)->orderBy('nombre')->get();

        $docentes = Docente::where('estado', 1)->orderBy('id')->get();

        $grados = Grado::where('estado', 1)->get();

        $grados = $grados->sort(function ($a, $b) {
            $ordenNivel = [
                'Inicial' => 1,
                'Primaria' => 2,
                'Secundaria' => 3,
            ];

            $nivelA = $ordenNivel[$a->nivel] ?? 99;
            $nivelB = $ordenNivel[$b->nivel] ?? 99;

            if ($nivelA !== $nivelB) {
                return $nivelA <=> $nivelB;
            }

            if ($a->grado !== $b->grado) {
                return $a->grado <=> $b->grado;
            }

            return $a->seccion <=> $b->seccion;
        })->values();

        $periodos = Periodo::where('estado', 1)->orderBy('nombre')->get();

        return view('modulos.maya.create', compact('materias', 'docentes', 'grados', 'periodos'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'docente_designado_id' => 'required|exists:docentes,id',
            'grado_id' => 'required|exists:grados,id',
            'anio' => 'required|integer',
            'periodo_id' => 'required|exists:periodos,id',
        ]);
        $data = $request->all();
        $data['materia_id'] = strtoupper($data['materia_id']);
        $data['docente_designado_id'] = strtoupper($data['docente_designado_id']);
        $data['grado_id'] = strtoupper($data['grado_id']);
        $data['anio'] = strtoupper($data['anio']);
        $data['periodo_id'] = strtoupper($data['periodo_id']);
        $maya = Cursogradosecnivanio::create($data);
        return redirect()->route('maya.index', ['anio' => $maya->anio])
            ->with('success', 'Maya creada exitosamente.');
    }

    public function edit($id)
    {
        $maya = Cursogradosecnivanio::findOrFail($id);

        // Cargar materias activas
        $materias = Materia::where('estado', 1)->orderBy('nombre')->get();

        // Cargar docentes activos
        $docentes = Docente::where('estado', 1)->orderBy('id')->get();

        // Cargar grados activos y ordenarlos de forma personalizada
        $grados = Grado::where('estado', 1)->get(); // Obtener todos los grados activos primero

        // Ordenar la colección de grados
        $grados = $grados->sort(function ($a, $b) {
            // Define un orden personalizado para los niveles si el orden alfabético no es el deseado
            $ordenNivel = [
                'Inicial' => 1,
                'Primaria' => 2,
                'Secundaria' => 3,
                // Agrega otros niveles si los tienes y define su orden
            ];

            // Comparar por Nivel (usando el orden personalizado, si existe)
            $nivelA = $ordenNivel[$a->nivel] ?? 99;
            $nivelB = $ordenNivel[$b->nivel] ?? 99;

            if ($nivelA !== $nivelB) {
                return $nivelA <=> $nivelB;
            }

            // Si los niveles son iguales, comparar por Grado (numéricamente)
            if ($a->grado !== $b->grado) {
                return $a->grado <=> $b->grado;
            }

            // Si los grados también son iguales, comparar por Sección (alfabéticamente)
            return $a->seccion <=> $b->seccion;
        })->values(); // Re-indexa la colección después de ordenar

        $periodos = Periodo::where('estado', 1)->orderBy('nombre')->get();

        return view('modulos.maya.edit', compact('maya', 'materias', 'docentes', 'grados', 'periodos'));
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'docente_designado_id' => 'required|exists:docentes,id',
            'grado_id' => 'required|exists:grados,id',
            'anio' => 'required|integer',
            'periodo_id' => 'required|exists:periodos,id',
        ]);
        $maya = Cursogradosecnivanio::findOrFail($id);
        $data = $request->all();
        $data['materia_id'] = strtoupper($data['materia_id']);
        $data['docente_designado_id'] = strtoupper($data['docente_designado_id']);
        $data['grado_id'] = strtoupper($data['grado_id']);
        $data['anio'] = strtoupper($data['anio']);
        $data['periodo_id'] = strtoupper($data['periodo_id']);
        $maya->update($data);
        return redirect()->route('maya.index', ['anio' => $maya->anio])
            ->with('success', 'Maya actualizada exitosamente.');
    }
    public function destroy($id)
    {
        $maya = Cursogradosecnivanio::findOrFail($id);
        $anio = $maya->anio;
        $maya->delete();
        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Maya eliminada exitosamente.');
    }
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $anios = Cursogradosecnivanio::select('anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');
        $anioSeleccionado = $request->get('anio', date('Y'));

        if ($user->hasRole('docente')) {
            $mayas = Cursogradosecnivanio::where('anio', $anioSeleccionado)
                ->where('docente_designado_id', $user->docente->id ?? 0)
                ->orderBy('id')
                ->get();
        } else {
            $mayas = Cursogradosecnivanio::where('anio', $anioSeleccionado)
                ->orderBy('id')
                ->get();
        }

        return view('modulos.maya.index', compact('mayas', 'anios', 'anioSeleccionado'));
    }
}
