<?php
namespace App\Http\Controllers;

use App\Models\Asistencia\Asistencia;
use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use App\Models\Asistencia\Tipoasistencia;
use App\Models\Grado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasRole('admin') && !$user->hasRole('director') && !$user->hasRole('auxiliar')) {
                abort(403, 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        // Obtener el año seleccionado (o el actual por defecto)
        $selectedYear = $request->input('year', now()->year);

        // Obtener años distintos que tienen registros de asistencia
        $yearsWithAttendance = Asistencia::select(DB::raw('YEAR(fecha) as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Si el año seleccionado tiene registros, mostrar todos los grados (sin filtrar por estado)
        if ($yearsWithAttendance->contains($selectedYear)) {
            $grados = Grado::withCount(['asistencias' => function($query) use ($selectedYear) {
                    $query->whereYear('fecha', $selectedYear);
                }])
                ->orderBy('nivel')
                ->orderBy('grado')
                ->orderBy('seccion')
                ->get();
        } else {
            // Si no hay registros para el año, mostrar solo grados activos
            $grados = Grado::where('estado', 1)
                ->orderBy('nivel')
                ->orderBy('grado')
                ->orderBy('seccion')
                ->get();
        }

        return view('asistencia.index', [
            'grados' => $grados,
            'currentYear' => $selectedYear,
            'availableYears' => $yearsWithAttendance
        ]);
    }
    public function showDate($grado_grado_seccion, $grado_nivel, $date)
    {
        try {
            $fechaFormateada = \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            abort(400, 'Formato de fecha inválido. Use dd-mm-yyyy');
        }

        // Extraer grado y sección de forma más robusta
        if (!preg_match('/^(\d+)([a-zA-Z]+)$/', $grado_grado_seccion, $matches)) {
            abort(400, 'Formato de grado/sección inválido. Ejemplo: 1a, 2b');
        }

        $gradoNumero = $matches[1];
        $gradoSeccion = $matches[2];

        $grado = Grado::where('grado', $gradoNumero)
                ->where('seccion', $gradoSeccion)
                ->where('nivel', $grado_nivel)
                ->firstOrFail();

        // Consulta más eficiente usando withCount
        $estudiantes = Estudiante::with(['user'])
                    ->withCount(['asistencias' => function($q) use ($fechaFormateada) {
                        $q->whereDate('fecha', $fechaFormateada);
                    }])
                    ->where('grado_id', $grado->id)
                    ->where('estado', 1)
                    ->get()
                    ->sortBy(function($estudiante) {
                        return optional($estudiante->user)->apellido_paterno.
                                optional($estudiante->user)->apellido_materno.
                                optional($estudiante->user)->nombre;
                    });

        return view('asistencia.grado', [
            'grado' => $grado,
            'estudiantes' => $estudiantes,
            'fechaSeleccionada' => $date,
            'fechaFormateada' => $fechaFormateada,
            'tiposAsistencia' => Tipoasistencia::all()
        ]);
    }

    public function showManual(Request $request, $curso, $bimestre)
    {
        $curso = \App\Models\Maya\Cursogradosecnivanio::with(['grado', 'materia'])->findOrFail($curso);
        $bimestre = \App\Models\Maya\Bimestre::findOrFail($bimestre);

        // Obtener la fecha del request o usar la fecha actual
        $fecha = $request->input('fecha', now()->toDateString());

        // Cargar estudiantes con sus asistencias para la fecha seleccionada
        $estudiantes = \App\Models\Estudiante::with(['asistencias' => function($query) use ($bimestre, $fecha) {
            $query->where('bimestre_id', $bimestre->id)
                ->whereDate('fecha', $fecha);
        }])
        ->where('grado_id', $curso->grado_id)
        ->get()
        ->sortBy(function($estudiante) {
            return optional($estudiante->user)->apellido_paterno.
                optional($estudiante->user)->apellido_materno.
                optional($estudiante->user)->nombre;
        });

        $tipos = \App\Models\Asistencia\Tipoasistencia::all();

        return view('asistencia.manual', [
            'curso' => $curso,
            'bimestre' => $bimestre,
            'estudiantes' => $estudiantes,
            'tipos' => $tipos,
            'fechaSeleccionada' => $fecha
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'fecha' => 'required|date_format:Y-m-d',
            'asistencias' => 'required|array',
            'asistencias.*' => 'required|exists:tipo_asistencias,id',
            'horas.*' => 'nullable|date_format:H:i'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->asistencias as $estudiante_id => $tipo_asistencia_id) {
                Asistencia::updateOrCreate(
                    [
                        'estudiante_id' => $estudiante_id,
                        'fecha' => $request->fecha,
                        'grado_id' => $request->grado_id
                    ],
                    [
                        'tipo_asistencia_id' => $tipo_asistencia_id,
                        'hora' => $request->input("horas.$estudiante_id", '00:00:00'),
                        'registrador_id' => auth()->id(),
                        'descripcion' => 'Asistencia registrada manualmente'
                    ]
                );
            }

            DB::commit();

            return redirect()
                ->route('asistencia.grado', [
                    'grado_grado_seccion' => $request->grado_grado_seccion,
                    'grado_nivel' => $request->grado_nivel,
                    'date' => \Carbon\Carbon::createFromFormat('Y-m-d', $request->fecha)->format('d-m-Y')
                ])
                ->with('success', 'Asistencias guardadas correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al guardar las asistencias: '.$e->getMessage());
        }
    }
}
