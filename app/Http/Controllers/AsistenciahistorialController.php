<?php
namespace App\Http\Controllers;

use App\Models\Asistencia\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Asistencia\Tipoasistencia;
use App\Models\Grado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsistenciahistorialController extends Controller
{
    //moduleID 16 = Mis Asistencias
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('16')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }
    public function calendarioAsistencia(Request $request, $anio = null, $bimestre = null)
    {
        // Obtener el ID del usuario de la sesión sub (si existe) o el actual
        $userId = session('sub_session_user_id') ?? auth()->id();

        // Buscar el usuario y su estudiante
        $user = User::find($userId);

        $estudiante = $user->estudiante;

        $anioActual = date('Y');

        // Obtener años con registros
        $aniosConRegistros = $estudiante->asistencias()
            ->select(DB::raw('YEAR(fecha) as anio'))
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();

        // Combinar año actual con años que tienen registros
        $aniosDisponibles = array_unique(array_merge([$anioActual], $aniosConRegistros));
        rsort($aniosDisponibles); // Ordenar de mayor a menor

        // Si no se proporciona año, usar el actual
        if (!$anio) {
            $anio = $anioActual;
        }

        // Si no se proporciona bimestre, usar 'todos'
        if (!$bimestre || $bimestre == 'todos') {
            $bimestre = null;
        }

        // Construir consulta base - CARGAR TODO EL AÑO
        $query = $estudiante->asistencias()
            ->with(['tipoasistencia', 'grado'])
            ->whereYear('fecha', $anio);

        // Aplicar filtro por bimestre (si no es 'todos')
        if ($bimestre) {
            $query->where('bimestre', $bimestre);
        }

        // Obtener asistencias para el calendario
        $asistenciasParaEventos = $query->get();
        $eventosCalendario = [];

        foreach ($asistenciasParaEventos as $asistencia) {
            // Convertir la fecha string a Carbon para formatearla
            $fechaCarbon = \Carbon\Carbon::parse($asistencia->fecha);

            $eventosCalendario[] = [
                'title' => $asistencia->tipoasistencia->nombre ?? 'Sin tipo',
                'start' => $fechaCarbon->toDateString(), // Ahora es seguro llamar a toDateString()
                'color' => $this->obtenerColorAsistencia($asistencia->tipo_asistencia_id),
                'extendedProps' => [
                    'tipo' => $asistencia->tipoasistencia->nombre ?? '',
                    'hora' => $asistencia->hora ? date('h:i A', strtotime($asistencia->hora)) : '',
                    'grado' => $asistencia->grado->grado . '° ' . $asistencia->grado->seccion,
                    'bimestre' => 'Bimestre ' . $asistencia->bimestre,
                    'descripcion' => $asistencia->descripcion,
                ]
            ];
        }

        $asistencias = $query->orderBy('fecha', 'desc')->get();

        // Calcular estadísticas
        $estadisticas = [
            'total' => $asistencias->count(),
            'puntual' => $asistencias->where('tipo_asistencia_id', 5)->count(),
            'tardanza' => $asistencias->where('tipo_asistencia_id', 1)->count(),
            'falta' => $asistencias->where('tipo_asistencia_id', 2)->count(),
            'justificada' => $asistencias->where('tipo_asistencia_id', 3)->count(),
        ];

        // Obtener bimestres disponibles para el año seleccionado
        $bimestresDisponibles = $estudiante->asistencias()
            ->whereYear('fecha', $anio)
            ->select('bimestre')
            ->distinct()
            ->orderBy('bimestre')
            ->pluck('bimestre');

        // Agregar opción "Todos los bimestres"
        $bimestresDisponibles = collect(['todos'])->merge($bimestresDisponibles);

        return view('asistencia.calendario', compact(
            'estudiante',
            'eventosCalendario',
            'estadisticas',
            'aniosDisponibles',
            'bimestresDisponibles',
            'anio',
            'bimestre'
        ));
    }

    private function obtenerColorAsistencia($tipoId)
    {
        return match($tipoId) {
            5 => '#28a745', // Puntual
            1 => '#dc3545', // Tardanza
            2 => '#ffc107', // Falta
            3 => '#6c757d', // Justificada
            4 => '#17a2b8', // Permiso
            default => '#007bff',
        };
    }
}
