<?php
use Illuminate\Support\Facades\Route;
use App\Models\Grado;

Route::get('/grados-por-nivel/{nivel}', function($nivel) {
    return response()->json(
        Grado::where('nivel', $nivel)
            ->select('id', 'grado')
            ->distinct()
            ->get()
    );
});

Route::get('/secciones-por-grado/{nivel}/{grado}', function($nivel, $grado) {
    return response()->json(
        Grado::where('nivel', $nivel)
            ->where('grado', $grado)
            ->pluck('seccion')
            ->unique()
            ->values()
    );
});
