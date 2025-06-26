<?php

namespace App\Http\Controllers\Maya;
use App\Http\Controllers\Controller;

use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Unidad;
use Illuminate\Http\Request;


class BimestreController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('docente')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }
    public function create(Request $request)
    {
        $curso_grado_sec_niv_anio_id = $request->curso_grado_sec_niv_anio_id;

        // Obtener los bimestres ocupados (nombres: 1,2,3,4)
        $ocupadoBimestres = Bimestre::where('curso_grado_sec_niv_anio_id', $curso_grado_sec_niv_anio_id)
            ->pluck('nombre')
            ->toArray();

        // Obtener todos los cursos para mostrar info en la vista
        $cursos = Cursogradosecnivanio::with(['materia', 'grado'])->get();

        return view('modulos.bimestre.create', compact('curso_grado_sec_niv_anio_id', 'ocupadoBimestres', 'cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'curso_grado_sec_niv_anio_id' => 'required|exists:maya_curso_grado_sec_niv_anios,id',
            'bimestre' => [
                'required',
                'in:1,2,3,4',
                function($attribute, $value, $fail) use ($request) {
                    $exists = Bimestre::where('curso_grado_sec_niv_anio_id', $request->curso_grado_sec_niv_anio_id)
                        ->where('nombre', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Este bimestre ya está registrado para este curso.');
                    }
                }
            ],
        ]);

        $data = [
            'curso_grado_sec_niv_anio_id' => $request->curso_grado_sec_niv_anio_id,
            'nombre' => $request->bimestre,
        ];

        $bimestre = Bimestre::create($data);

        // Obtener el año del curso relacionado
        $maya = Cursogradosecnivanio::find($bimestre->curso_grado_sec_niv_anio_id);
        $anio = $maya ? $maya->anio : date('Y');

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Bimestre creado correctamente.');
    }

    public function edit($id)
    {
        $maya = Cursogradosecnivanio::findOrFail($id);
        $bimestre = Bimestre::findOrFail($id);
        $cursos = Cursogradosecnivanio::with(['materia', 'grado'])->get();
        $anio = $bimestre->cursoGradoSecNivAnio->anio ?? date('Y');
        $ocupadoBimestres = Bimestre::where('curso_grado_sec_niv_anio_id', $bimestre->curso_grado_sec_niv_anio_id)
            ->where('id', '!=', $bimestre->id)
            ->pluck('nombre')
            ->toArray();

        return view('modulos.bimestre.edit', compact('maya', 'bimestre', 'cursos', 'ocupadoBimestres', 'anio'));
    }

    public function update(Request $request, $id)
    {
        $bimestre = Bimestre::findOrFail($id);

        $request->validate([
            'curso_grado_sec_niv_anio_id' => 'required|exists:maya_curso_grado_sec_niv_anios,id',
            'bimestre' => [
                'required',
                'in:1,2,3,4',
                function($attribute, $value, $fail) use ($request, $bimestre) {
                    $exists = Bimestre::where('curso_grado_sec_niv_anio_id', $request->curso_grado_sec_niv_anio_id)
                        ->where('nombre', $value)
                        ->where('id', '!=', $bimestre->id)
                        ->exists();
                    if ($exists) {
                        $fail('Este bimestre ya está registrado para este curso.');
                    }
                }
            ],
        ]);

        $data = [
            'curso_grado_sec_niv_anio_id' => $request->curso_grado_sec_niv_anio_id,
            'nombre' => $request->bimestre,
        ];

        $bimestre->update($data);

        // Obtener el año del curso relacionado
        $maya = Cursogradosecnivanio::find($bimestre->curso_grado_sec_niv_anio_id);
        $anio = $maya ? $maya->anio : date('Y');

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Bimestre actualizado correctamente.');
    }

    public function destroy($id)
    {
        $bimestre = Bimestre::findOrFail($id);
        // Obtener el año del curso relacionado para redirigir correctamente
        $maya = Cursogradosecnivanio::find($bimestre->curso_grado_sec_niv_anio_id);
        $anio = $maya ? $maya->anio : date('Y');
        $bimestre->delete();

        return redirect()->route('maya.index', ['anio' => $anio])
            ->with('success', 'Bimestre eliminado correctamente.');
    }

}
