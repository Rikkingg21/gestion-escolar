<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\User;
use App\Models\Apoderado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstudianteController extends Controller
{
    public function index()
    {
        $estudiantes = Estudiante::with(['user', 'grado', 'apoderado.user'])->get();
        return view('estudiantes.index', compact('estudiantes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Estudiante $estudiante)
    {
        //
    }

    public function edit(Estudiante $estudiante)
    {
        // Cargar datos necesarios
        $user = $estudiante->user;
        $grados = Grado::all();
        $apoderados = Apoderado::with('user')->get();
        $fechaNacimiento = $user->fecha_nacimiento ? $user->fecha_nacimiento->format('Y-m-d') : null;

        return view('estudiantes.edit', compact(
            'estudiante',
            'user',
            'grados',
            'apoderados',
            'fechaNacimiento'
        ));
    }

    public function update(Request $request, Estudiante $estudiante)
    {
        $validated = $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'fecha_nacimiento' => 'nullable|date',
            'sin_apoderado' => 'nullable|boolean',
            'apoderado_id' => 'nullable|required_unless:sin_apoderado,1|exists:apoderados,id',
            'parentesco' => 'nullable|string|max:50'
        ]);

        $apoderadoData = [
            'grado_id' => $validated['grado_id'],
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
            'apoderado_id' => $request->has('sin_apoderado') ? null : $validated['apoderado_id']
        ];

        $estudiante->update($apoderadoData);

        // Actualizar parentesco si hay apoderado
        if ($request->filled('apoderado_id')) {
            Apoderado::where('id', $validated['apoderado_id'])
                ->update(['parentesco' => $validated['parentesco']]);
        }

        return redirect()->route('estudiantes.index')
            ->with('success', 'Estudiante actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Estudiante $estudiante)
    {
        //
    }
}
