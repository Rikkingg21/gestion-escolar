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
        ]);

        $data = $request->all();
        $data['nombre'] = strtoupper($data['nombre']);

        Materia::create($data);

        return redirect()->route('materias.index')->with('success', 'Materia creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Materia $materia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Materia $materia)
    {
        return view ('materia.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Materia $materia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Materia $materia)
    {
        //
    }
}
