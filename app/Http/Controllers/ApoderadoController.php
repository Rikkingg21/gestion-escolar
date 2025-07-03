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
        $term = $request->input('q');

        $apoderados = Apoderado::with(['user' => function($query) {
                $query->where('estado', '1'); // Solo usuarios activos
            }])
            ->whereHas('user', function($query) use ($term) {
                $query->where('estado', '1') // Solo usuarios activos
                    ->where(function($q) use ($term) {
                        $q->where('nombre', 'like', "%$term%")
                            ->orWhere('apellido_paterno', 'like', "%$term%")
                            ->orWhere('apellido_materno', 'like', "%$term%")
                            ->orWhere('dni', 'like', "%$term%");
                    });
            })
            ->paginate(10);

        $formattedApoderados = $apoderados->map(function($apoderado) {
            return [
                'id' => $apoderado->id,
                'nombre_completo' => $apoderado->user->nombre . ' ' . $apoderado->user->apellido_paterno,
                'dni' => $apoderado->user->dni,
                'text' => $apoderado->user->nombre . ' ' . $apoderado->user->apellido_paterno . ' (DNI: ' . $apoderado->user->dni . ')'
            ];
        });

        return response()->json([
            'items' => $formattedApoderados,
            'total_count' => $apoderados->total()
        ]);
    }
}
