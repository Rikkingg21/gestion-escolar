<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MateriaController extends Controller
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
        $materiasActivas = Materia::where('estado', 1)
            ->orderBy('nombre')
            ->paginate(5, ['*'], 'activos');

        $materiasInactivas = Materia::where('estado', 0)
            ->orderBy('nombre')
            ->paginate(5, ['*'], 'inactivos');
        return view('materia.index', compact('materiasActivas', 'materiasInactivas'));
    }

    public function create()
    {

        return view('materia.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'estado' => 'required|in:1,0',
        ]);

        $data = $request->all();
        $data['nombre'] = strtoupper($data['nombre']);

        Materia::create($data);

        return redirect()->route('materia.index')->with('success', 'Materia creada exitosamente.');
    }

    public function edit($id)
    {
        $materia = Materia::findOrFail($id);
        return view('materia.edit', compact('materia'));
    }

    public function update(Request $request, Materia $materia)
    {
        $request->validate([
        'nombre' => 'required|string|max:255',
        'estado' => 'required|in:1,0',
        ]);

        $data = $request->all();
        $data['nombre'] = strtoupper($data['nombre']);

        $materia->update($data);

        return redirect()->route('materia.index')->with('success', 'Materia actualizada exitosamente.');
    }


    public function destroy($id)
    {
        $materia = Materia::findOrFail($id);
        if ($materia->estado == 1) {
            return redirect()->route('materia.index')->with('error', 'No se puede eliminar la materia porque está activo.');
        }
        $materia->delete();
        return redirect()->route('materia.index')->with('success', 'Materia eliminada    correctamente.');
    }
}
