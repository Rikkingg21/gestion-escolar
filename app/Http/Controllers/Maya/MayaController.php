<?php

namespace App\Http\Controllers\Maya;

use App\Http\Controllers\Controller;
use App\Models\Maya\Cursogradosecnivanio;
use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Grado;
use App\Models\Docente;
use App\Models\User;

class MayaController extends Controller
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
public function index(Request $request)
{
    $user = auth()->user();
    $anios = Cursogradosecnivanio::select('anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');
    $anioSeleccionado = $request->get('anio', date('Y'));

    // Obtener datos para los filtros
    $grados = Grado::orderBy('grado')->orderBy('seccion')->get();
    $materias = Materia::orderBy('nombre')->get();

    if ($user->hasRole('admin') || $user->hasRole('director')) {
        $docentes = Docente::with('user')->get();
    }

    // Construir consulta base
    $query = Cursogradosecnivanio::with(['grado', 'materia', 'docente.user'])
                ->where('anio', $anioSeleccionado);

    // Aplicar filtros
    if ($request->filled('grado_id')) {
        $query->where('grado_id', $request->grado_id);
    }

    if ($request->filled('materia_id')) {
        $query->where('materia_id', $request->materia_id);
    }

    if (($user->hasRole('admin') || $user->hasRole('director')) && $request->filled('docente_id')) {
        $query->where('docente_designado_id', $request->docente_id);
    }

    // Si es docente, solo mostrar sus mayas
    if ($user->hasRole('docente')) {
        $query->where('docente_designado_id', $user->docente->id ?? 0);
    }

    $mayas = $query->orderBy('id')->paginate(10);

    return view('modulos.maya.index', compact(
        'mayas',
        'anios',
        'anioSeleccionado',
        'grados',
        'materias',
        'docentes' ?? null
    ));
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

        return view('modulos.maya.create', compact('materias', 'docentes', 'grados'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'docente_designado_id' => 'required|exists:docentes,id',
            'grado_id' => 'required|exists:grados,id',
            'anio' => 'required|integer',
        ]);
        $data = $request->all();
        $data['materia_id'] = strtoupper($data['materia_id']);
        $data['docente_designado_id'] = strtoupper($data['docente_designado_id']);
        $data['grado_id'] = strtoupper($data['grado_id']);
        $data['anio'] = strtoupper($data['anio']);
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

        return view('modulos.maya.edit', compact('maya', 'materias', 'docentes', 'grados'));
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'docente_designado_id' => 'required|exists:docentes,id',
            'grado_id' => 'required|exists:grados,id',
            'anio' => 'required|integer',
        ]);
        $maya = Cursogradosecnivanio::findOrFail($id);
        $data = $request->all();
        $data['materia_id'] = strtoupper($data['materia_id']);
        $data['docente_designado_id'] = strtoupper($data['docente_designado_id']);
        $data['grado_id'] = strtoupper($data['grado_id']);
        $data['anio'] = strtoupper($data['anio']);
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
