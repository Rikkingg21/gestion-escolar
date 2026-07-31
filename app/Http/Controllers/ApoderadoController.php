<?php

namespace App\Http\Controllers;

use App\Models\Apoderado;
use Illuminate\Http\Request;

class ApoderadoController extends Controller
{
    public function search(Request $request)
    {
        $term = $request->input('q');

        $apoderados = Apoderado::with(['user' => function ($query) {
            $query->where('estado', '1'); // Solo usuarios activos
        }])
            ->whereHas('user', function ($query) use ($term) {
                $query->where('estado', '1') // Solo usuarios activos
                    ->where(function ($q) use ($term) {
                        $q->where('nombre', 'like', "%$term%")
                            ->orWhere('apellido_paterno', 'like', "%$term%")
                            ->orWhere('apellido_materno', 'like', "%$term%")
                            ->orWhere('dni', 'like', "%$term%");
                    });
            })
            ->paginate(10);

        $formattedApoderados = $apoderados->map(function ($apoderado) {
            return [
                'id' => $apoderado->id,
                'nombre_completo' => $apoderado->user->nombre.' '.$apoderado->user->apellido_paterno,
                'dni' => $apoderado->user->dni,
                'text' => $apoderado->user->nombre.' '.$apoderado->user->apellido_paterno.' (DNI: '.$apoderado->user->dni.')',
            ];
        });

        return response()->json([
            'items' => $formattedApoderados,
            'total_count' => $apoderados->total(),
        ]);
    }
}
