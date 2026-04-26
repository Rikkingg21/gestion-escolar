<?php
namespace App\Http\Controllers;

use App\Models\Asistencia\Asistencia;
use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\User;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use App\Models\Periodo;
use App\Models\Periodobimestre;
use App\Models\Matricula;
use Carbon\Carbon;
use App\Models\Asistencia\Tipoasistencia;
use App\Models\Grado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AsistenciabloqueoController extends Controller
{
    //moduleID 14 = Asistencia
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('14')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }

    public function bloqueoView(Request $request)
    {
        // Obtener todos los periodos activos
        $periodos = Periodo::where('estado', '1')->get();

        // Meses del año
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];

        // Obtener usuarios con rol admin o director
        $usuariosAutorizados = User::whereHas('roles', function($query) {
            $query->whereIn('nombre', ['admin', 'director']);
        })->where('estado', '1')->with('roles')->get();

        // Inicialización de variables
        $asistencias = collect();
        $periodoSeleccionado = null;
        $mesSeleccionado = null;
        $gradoSeleccionado = null;
        $periodoAnio = null;
        $distribucionEstados = collect();
        $contadoresEstados = [
            'libres' => 0,
            'bloqueados' => 0,
            'bloqueados_def' => 0,
            'total' => 0
        ];

        // Obtener grados para filtro adicional
        $grados = Grado::where('estado', 1)->get();

        if ($request->has('periodo_id') && $request->has('mes')) {
            $periodoSeleccionado = $request->input('periodo_id');
            $mesSeleccionado = $request->input('mes');
            $gradoSeleccionado = $request->input('grado_id');

            // Obtener el periodo para el año
            $periodo = Periodo::find($periodoSeleccionado);
            $periodoAnio = $periodo ? $periodo->anio : null;

            // Construir la consulta base
            $query = Asistencia::with([
                    'estudiante.user',
                    'grado',
                    'tipoasistencia',
                    'periodo'
                ])
                ->where('periodo_id', $periodoSeleccionado);

            if ($periodoAnio) {
                $query->whereYear('fecha', $periodoAnio)
                    ->whereMonth('fecha', $mesSeleccionado);
            }

            // Filtrar por grado si está seleccionado
            if ($gradoSeleccionado) {
                $query->where('grado_id', $gradoSeleccionado);
            }

            // Obtener distribución de estados para contadores sin ejecutar la query principal aún
            $distribucionEstados = (clone $query)
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->get()
                ->keyBy('estado');

            // Calcular contadores
            $contadoresEstados['libres'] = $distribucionEstados->get(0)->total ?? 0;
            $contadoresEstados['bloqueados'] = $distribucionEstados->get(1)->total ?? 0;
            $contadoresEstados['bloqueados_def'] = $distribucionEstados->get(2)->total ?? 0;
            $contadoresEstados['total'] = array_sum($contadoresEstados);

            // Ordenar y obtener resultados finales
            $asistencias = $query->orderBy('fecha', 'asc')
                ->orderBy('hora', 'asc')
                ->get();
        }

        return view('asistencia.bloqueo', compact(
            'periodos',
            'meses',
            'grados',
            'asistencias',
            'periodoSeleccionado',
            'mesSeleccionado',
            'gradoSeleccionado',
            'periodoAnio',
            'distribucionEstados',
            'contadoresEstados',
            'usuariosAutorizados'
        ));
    }
    public function bloquearMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id'
        ]);

        try {
            // 1. Obtener los IDs de los registros que cumplen los filtros
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '0'); // Nota: tu consulta usa '0' como string

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 0
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias libres (estado 0) para bloquear. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '1']); // Cambiar a estado 1

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias bloqueadas correctamente");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // Bloquear definitivamente todas las asistencias bloqueadas temporalmente (estado 1 -> 2)
    public function bloquearDefinitivoMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id'
        ]);

        try {
            // 1. Obtener los IDs de los registros que cumplen los filtros (estado 1)
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '1'); // Solo las bloqueadas temporalmente

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 1
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias bloqueadas temporalmente (estado 1) para bloquear definitivamente. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs (1 -> 2)
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '2']); // Cambiar a estado 2 (definitivo)

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias bloqueadas definitivamente");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    // Liberar todas las asistencias bloqueadas temporalmente (estado 1 -> 0)
    public function liberarMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id'
        ]);

        try {
            // 1. Obtener los IDs de los registros que cumplen los filtros (estado 1)
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '1'); // Solo las bloqueadas temporalmente

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 1
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias bloqueadas temporalmente (estado 1) para liberar. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs (1 -> 0)
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '0']);

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias liberadas correctamente");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    // Liberar de bloqueo definitivo a temporal (estado 2 -> 1)
    public function liberarDefinitivoMasivo(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'mes' => 'required|string|size:2',
            'grado_id' => 'nullable|exists:grados,id',
            'usuario_autorizador_id' => 'required|exists:users,id',
            'password_confirmation' => 'required|string'
        ]);

        try {
            // Validar que el usuario tenga rol de admin o director
            $usuarioAutorizador = User::with('roles')->find($request->usuario_autorizador_id);

            if (!$usuarioAutorizador) {
                return back()->withInput()->with('error', 'Usuario autorizador no encontrado.');
            }

            // Verificar que tenga rol admin o director
            $tieneRol = $usuarioAutorizador->roles->contains(function($role) {
                return in_array($role->nombre, ['admin', 'director']);
            });

            if (!$tieneRol) {
                return back()->withInput()->with('error', 'El usuario seleccionado no tiene permisos para realizar esta acción.');
            }

            // Verificar contraseña
            if (!Hash::check($request->password_confirmation, $usuarioAutorizador->password)) {
                return back()->withInput()->with('error', 'Contraseña incorrecta.');
            }

            // 1. Obtener los IDs de los registros que cumplen los filtros (estado 2)
            $query = Asistencia::where('periodo_id', $request->periodo_id)
                ->whereMonth('fecha', $request->mes)
                ->where('estado', '2'); // Solo las bloqueadas definitivamente

            // Si hay filtro por grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // 2. Obtener solo los IDs en una lista
            $ids = $query->pluck('id')->toArray();

            // 3. Verificar si hay registros para actualizar
            if (empty($ids)) {
                // Obtener información para mensaje más detallado
                $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                    ->whereMonth('fecha', $request->mes)
                    ->count();

                if ($request->filled('grado_id')) {
                    $totalRegistros = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes)
                        ->where('grado_id', $request->grado_id)
                        ->count();
                }

                if ($totalRegistros == 0) {
                    return back()->withInput()->with('info', 'No se encontraron registros para los filtros seleccionados.');
                } else {
                    // Hay registros pero no están en estado 2
                    $distribucion = Asistencia::where('periodo_id', $request->periodo_id)
                        ->whereMonth('fecha', $request->mes);

                    if ($request->filled('grado_id')) {
                        $distribucion->where('grado_id', $request->grado_id);
                    }

                    $distribucion = $distribucion->selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get();

                    $mensaje = "No hay asistencias bloqueadas definitivamente (estado 2) para liberar. ";
                    if ($distribucion->isNotEmpty()) {
                        $mensaje .= "Distribución actual: ";
                        foreach ($distribucion as $item) {
                            $estado = is_string($item->estado) ? (int)$item->estado : $item->estado;
                            $estadoTexto = match($estado) {
                                0 => 'Libres',
                                1 => 'Bloqueadas temporales',
                                2 => 'Bloqueadas definitivas',
                                default => "Estado {$item->estado}"
                            };
                            $mensaje .= "{$estadoTexto}: {$item->total} registros, ";
                        }
                        $mensaje = rtrim($mensaje, ', ');
                    }

                    return back()->withInput()->with('info', $mensaje);
                }
            }

            // 4. Actualizar SOLO los registros con esos IDs (2 -> 1)
            $updated = Asistencia::whereIn('id', $ids)->update(['estado' => '1']);

            // Registrar quien realizó la acción
            Log::info('LIBERAR DEFINITIVO - Acción realizada por:', [
                'usuario_id' => $usuarioAutorizador->id,
                'usuario_nombre' => $usuarioAutorizador->nombre,
                'registros_afectados' => $updated,
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes
            ]);

            // 5. Redirigir con mensaje de éxito
            return redirect()->route('bloqueo.view', [
                'periodo_id' => $request->periodo_id,
                'mes' => $request->mes,
                'grado_id' => $request->grado_id
            ])->with('success', "{$updated} asistencias cambiadas a bloqueo temporal. Autorizado por: {$usuarioAutorizador->nombre}");

        } catch (\Exception $e) {
            Log::error('LIBERAR DEFINITIVO MASIVO - Error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
