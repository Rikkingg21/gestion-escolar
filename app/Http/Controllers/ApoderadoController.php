<?php

namespace App\Http\Controllers;

use App\Models\Apoderado;
use Illuminate\Http\Request;


class ApoderadoController extends Controller
{
    public function index()
    {
        $apoderado = Apoderado::with(['user', 'grado'])->get();

        // Opción 2: Si usas paquetes como Spatie Laravel Permissions
        // $estudiantes = User::role('estudiante')->with('estudiante')->get();

        return view('apoderado.index', compact('apoderados'));
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
    public function show(Apoderado $apoderado)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Apoderado $apoderado)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Apoderado $apoderado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Apoderado $apoderado)
    {
        //
    }
    public function search(Request $request)
    {
        $search = $request->input('search');

        $apoderados = Apoderado::with(['user' => function($query) use ($search) {
                $query->where('dni', 'like', "%$search%")
                    ->orWhere('nombre', 'like', "%$search%")
                    ->orWhere('apellido_paterno', 'like', "%$search%")
                    ->orWhere('apellido_materno', 'like', "%$search%");
            }])
            ->whereHas('user')
            ->limit(10)
            ->get();

        return response()->json($apoderados);
    }
}
