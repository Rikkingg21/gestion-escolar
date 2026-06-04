<?php

namespace App\Http\Controllers\Tramite;

use App\Http\Controllers\Controller;
use App\Models\Tramite\Tramite;
use App\Models\Tramite\Tramitetipo;
use Illuminate\Http\Request;

class TramiteController extends Controller
{
    public function index()
    {
        return view('tramite.mis-tramites.index', compact('tramites'));
    }

    public function store(Request $request)
    {
        // Validación y creación
    }

    public function show($id)
    {
        $tramite = Tramite::with(['tipoTramite', 'estadoTramite', 'estadoPago', 'user'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return view('tramite.mis-tramites.show', compact('tramite'));
    }

    public function seguimiento($id)
    {

    }
}
