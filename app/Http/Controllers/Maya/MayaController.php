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
    public function index(Request $request)
    {
        $anios = Cursogradosecnivanio::select('anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');

        // Obtener el año seleccionado o el actual por defecto
        $anioSeleccionado = $request->get('anio', date('Y'));

        // Filtrar por año seleccionado (sin paginación)
        $mayas = Cursogradosecnivanio::where('anio', $anioSeleccionado)
            ->orderBy('id')
            ->get();

        return view('modulos.maya.index', compact('mayas', 'anios', 'anioSeleccionado'));
    }
    public function create()
    {
        // Cargar materias activas
        $materias = Materia::where('estado', 1)->orderBy('nombre')->get();
        // Cargar docentes activos
        $docentes = Docente::where('estado', 1)->orderBy('id')->get();
        // Cargar grados activos
        $grados = Grado::where('estado', 1)->orderBy('grado')->get();

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
        // Cargar grados activos
        $grados = Grado::where('estado', 1)->orderBy('grado')->get();

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
                ->paginate(10); // Cambiado de get() a paginate()
        } else {
            $mayas = Cursogradosecnivanio::where('anio', $anioSeleccionado)
                ->orderBy('id')
                ->paginate(10); // Cambiado de get() a paginate()
        }

        return view('modulos.maya.index', compact('mayas', 'anios', 'anioSeleccionado'));
    }
}
