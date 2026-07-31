<?php

use App\Models\Grado;
use App\Models\Metodopago\Tipopago;
use Illuminate\Support\Facades\Route;

Route::get('/grados-por-nivel/{nivel}', function ($nivel) {
    return response()->json(
        Grado::where('nivel', $nivel)
            ->select('id', 'grado')
            ->distinct()
            ->get()
    );
});

Route::get('/secciones-por-grado/{nivel}/{grado}', function ($nivel, $grado) {
    return response()->json(
        Grado::where('nivel', $nivel)
            ->where('grado', $grado)
            ->pluck('seccion')
            ->unique()
            ->values()
    );
});
Route::get('/tipo-pago/{id}', function ($id) {
    $tipoPago = Tipopago::find($id);

    return response()->json($tipoPago);
})->middleware('auth');
