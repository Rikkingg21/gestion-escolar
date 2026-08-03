<?php

namespace App\Http\Controllers;

use App\Models\Apoderado;
use Illuminate\Http\Request;

class ApoderadoController extends Controller
{
    // moduleID 7 = Usuarios (la búsqueda se usa al gestionar usuarios)
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('7')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $term = trim($request->input('q'));

        if ($term === '') {
            return response()->json([
                'items' => [],
                'total_count' => 0,
            ]);
        }

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
