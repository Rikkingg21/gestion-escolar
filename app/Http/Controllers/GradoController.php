<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    public function index()
    {
        $grados = Grado::orderBy('nivel')
        ->orderBy('grado')
        ->orderBy('seccion')
        ->paginate(5);

        return view('grado.index', compact('grados'));
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
            'nivel' => 'required|string|max:255'
        ]);

        $data = $request->all();
        $data['seccion'] = strtoupper($data['seccion']);

        Grado::create($data);

        return redirect()->route('grados.index')->with('success', 'Grado creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Grado $grado)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Grado $grado)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Grado $grado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grado $grado)
    {
        //
    }
}
