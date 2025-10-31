<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Conducta;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConductaController extends Controller
{
    //moduleID 12 = Conducta
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('12')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function index()
    {
        $conductasActivas = Conducta::where('estado', "1")->get();
        $conductasInactivas = Conducta::where('estado', "0")->get();

        return view('conducta.index', compact('conductasActivas', 'conductasInactivas'));
    }
    public function create()
    {
        return view('conducta.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'estado' => 'required|boolean',
        ]);

        Conducta::create($request->all());

        return redirect()->route('conducta.index')->with('success', 'Conducta creada exitosamente.');
    }
    public function edit(Conducta $conducta)
    {
        return view('conducta.edit', compact('conducta'));
    }
    public function update(Request $request, Conducta $conducta)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'estado' => 'required|boolean',
        ]);

        $conducta->update($request->all());

        return redirect()->route('conducta.index')->with('success', 'Conducta actualizada exitosamente.');
    }
    public function destroy(Conducta $conducta)
    {
        $conducta->delete();
        return redirect()->route('conducta.index')->with('success', 'Conducta eliminada exitosamente.');
    }
}
