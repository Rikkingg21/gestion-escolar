<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    //moduleID 10 = Grados
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('10')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        $gradosActivos = Grado::where('estado', '1')
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->paginate(5, ['*'], 'activos');

        $gradosInactivos = Grado::where('estado', '0')
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->paginate(5, ['*'], 'inactivos');

        return view('grado.index', compact('gradosActivos', 'gradosInactivos'));
    }

    public function create()
    {
        $grados = Grado::orderBy('nivel')
        ->orderBy('grado')
        ->orderBy('seccion')
        ->get();

        return view('grado.create', compact('grados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grado' => 'required|integer',
            'seccion' => 'required|string|max:1',
            'nivel' => 'required|string|max:255',
            'estado' => 'required|in:1,0',
        ]);

        $data = $request->all();
        $data['seccion'] = strtoupper($data['seccion']);

        Grado::create($data);

        return redirect()->route('grado.index')->with('success', 'Grado creado exitosamente.');
    }

    public function edit($id)
    {
        $grado = Grado::findOrFail($id);
        $grados = Grado::orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->get();

        return view('grado.edit', compact('grado', 'grados'));
    }

    public function update(Request $request, Grado $grado)
    {
        $request->validate([
            'grado' => 'required|integer',
            'seccion' => 'required|string|max:1',
            'nivel' => 'required|string|max:255',
            'estado' => 'required|in:1,0',
        ]);

        $data = $request->all();
        $data['seccion'] = strtoupper($data['seccion']);

        $grado->update($data);

        return redirect()->route('grado.index')->with('success', 'Grado actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $grado = Grado::findOrFail($id);

        // Verificar si el grado está activo
        if ($grado->estado == '1') {
            return redirect()->route('grado.index')->with('error', 'No se puede eliminar el grado porque está activo.');
        }

        $grado->delete();

        return redirect()->route('grado.index')->with('success', 'Grado eliminado correctamente.');
    }

    public function estudiantes($id)
    {
        $grado = Grado::findOrFail($id);

        $estudiantes = Estudiante::where('grado_id', $id)
            ->where('estado', '1')
            ->with(['user', 'apoderado.user'])
            ->get();

        return view('grado.gradoestudiantes', compact('grado', 'estudiantes'));
    }
    public function estudiantesUpdateGrado(Request $request, $gradoId)
    {
        $request->validate([
            'nuevo_grado' => 'required|integer',
            'nueva_seccion' => 'required|string|max:1',
            'nuevo_nivel' => 'required|string',
            'estudiantes' => 'required|array',
            'estudiantes.*' => 'exists:estudiantes,id'
        ]);

        // Buscar o crear el nuevo grado
        $nuevoGrado = Grado::firstOrCreate(
            [
                'grado' => $request->nuevo_grado,
                'seccion' => $request->nueva_seccion,
                'nivel' => $request->nuevo_nivel
            ],
            ['estado' => '1']
        );

        // Actualizar los estudiantes seleccionados
        Estudiante::whereIn('id', $request->estudiantes)
            ->update(['grado_id' => $nuevoGrado->id]);

        return redirect()->route('grado.estudiantes', $gradoId)
            ->with('success', 'Estudiantes ascendidos correctamente al grado ' .
                $nuevoGrado->grado . '° "' . $nuevoGrado->seccion . '" - ' . $nuevoGrado->nivel);
    }
}
