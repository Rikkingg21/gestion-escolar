<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use Illuminate\Http\Request;

class GradoController extends Controller
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
    public function index()
    {
        $gradosActivos = Grado::where('estado', 1)
            ->orderBy('nivel')
            ->orderBy('grado')
            ->orderBy('seccion')
            ->paginate(5, ['*'], 'activos');

        $gradosInactivos = Grado::where('estado', 0)
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
        if ($grado->estado == 1) {
            return redirect()->route('grado.index')->with('error', 'No se puede eliminar el grado porque está activo.');
        }

        $grado->delete();

        return redirect()->route('grado.index')->with('success', 'Grado eliminado correctamente.');
    }
}
