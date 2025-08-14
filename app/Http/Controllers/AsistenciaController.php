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

        // Extraer grado y sección
        if (!preg_match('/^(\d+)([a-zA-Z]+)$/', $grado_grado_seccion, $matches)) {
            abort(400, 'Formato de grado/sección inválido. Ejemplo: 1a, 2b');
        }

        $gradoNumero = $matches[1];
        $gradoSeccion = $matches[2];

        $grado = Grado::where('grado', $gradoNumero)
                ->where('seccion', $gradoSeccion)
                ->where('nivel', $grado_nivel)
                ->firstOrFail();

        // Obtener estudiantes activos con sus asistencias para la fecha seleccionada
        $estudiantes = Estudiante::with(['user', 'asistencias' => function($query) use ($fechaFormateada) {
                        $query->whereDate('fecha', $fechaFormateada);
                    }])
                    ->where('grado_id', $grado->id)
                    ->where('estado', 1)
                    ->get()
                    ->sortBy(function($estudiante) {
                        return optional($estudiante->user)->apellido_paterno.
                            optional($estudiante->user)->apellido_materno.
                            optional($estudiante->user)->nombre;
                    });

        // Verificar si hay registros para esta fecha
        $existenRegistros = Asistencia::where('grado_id', $grado->id)
                            ->whereDate('fecha', $fechaFormateada)
                            ->exists();

        $tiposAsistencia = Tipoasistencia::all();

        return view('asistencia.grado', [
            'grado' => $grado,
            'estudiantes' => $estudiantes,
            'fechaSeleccionada' => $date,
            'fechaFormateada' => $fechaFormateada,
            'tiposAsistencia' => $tiposAsistencia,
            'existenRegistros' => $existenRegistros
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grado_id' => 'required|exists:grados,id',
            'fecha' => 'required|date_format:Y-m-d',
            'grado_grado_seccion' => 'required|string',
            'grado_nivel' => 'required|string',
            'asistencias' => 'required|array',
            'asistencias.*' => 'required|exists:tipo_asistencias,id',
            'horas.*' => 'nullable|date_format:H:i'
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['asistencias'] as $estudiante_id => $tipo_asistencia_id) {
                Asistencia::updateOrCreate(
                    [
                        'estudiante_id' => $estudiante_id,
                        'fecha' => $validated['fecha'],
                        'grado_id' => $validated['grado_id']
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
                    'grado_grado_seccion' => $validated['grado_grado_seccion'],
                    'grado_nivel' => $validated['grado_nivel'],
                    'date' => \Carbon\Carbon::createFromFormat('Y-m-d', $validated['fecha'])->format('d-m-Y')
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
