<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Conducta;
use App\Models\Periodo;
use App\Models\Periodobimestre;
use App\Models\Conductaperiodobimestre;
use App\Models\Conductaperiodobimestrenota;
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
        // Sección 1: Conductas
        $conductasActivas = Conducta::where('estado', "1")->get();
        $conductasInactivas = Conducta::where('estado', "0")->get();

        // Periodos ACTIVOS (estado = 1) del año actual y anterior
        $anioActual = date('Y');
        $anioAnterior = $anioActual - 1;

        $periodosActivos = Periodo::where('tipo_periodo', 'año escolar')
            ->where('estado', '1')
            ->whereIn('anio', [$anioActual, $anioAnterior])
            ->with(['periodobimestres' => function($query) {
                $query->where('tipo_bimestre', 'A')
                      ->orderBy('bimestre', 'asc');
            }, 'periodobimestres.conductas'])
            ->orderBy('anio', 'desc')
            ->get();

        // Periodos INACTIVOS (estado = 0) para mostrar en lista
        $periodosInactivos = Periodo::where('tipo_periodo', 'año escolar')
            ->where('estado', '0')
            ->orderBy('anio', 'desc')
            ->get();

        return view('conducta.index', compact(
            'conductasActivas',
            'conductasInactivas',
            'periodosActivos',
            'periodosInactivos'
        ));
    }
    public function asignarConductas(Request $request)
    {
        $request->validate([
            'periodo_bimestre_id' => 'required|exists:periodo_bimestres,id',
            'conducta_ids' => 'array',
            'conducta_ids.*' => 'exists:conductas,id'
        ]);

        $periodoBimestreId = $request->periodo_bimestre_id;
        $nuevasConductas = $request->conducta_ids ?? [];

        DB::beginTransaction();

        try {
            // Obtener las conductas actualmente asignadas
            $conductasActuales = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                ->whereNull('deleted_at')
                ->pluck('conducta_id')
                ->toArray();

            // Obtener las relaciones que tienen notas (no se pueden eliminar)
            $relacionesIdsConNotas = Conductaperiodobimestrenota::where('periodo_bimestre_id', $periodoBimestreId)
                ->distinct()
                ->pluck('conducta_periodo_bimestre_id')
                ->toArray();

            // Obtener los IDs de conductas que tienen notas
            $relacionesConNotas = Conductaperiodobimestre::whereIn('id', $relacionesIdsConNotas)
                ->pluck('conducta_id')
                ->toArray();

            // Conductas que hay que agregar
            $conductasAAgregar = array_diff($nuevasConductas, $conductasActuales);

            // Conductas que hay que eliminar (excluyendo las que tienen notas)
            $conductasAEliminar = array_diff($conductasActuales, $nuevasConductas);
            $conductasAEliminar = array_diff($conductasAEliminar, $relacionesConNotas);

            // Agregar nuevas conductas
            foreach ($conductasAAgregar as $conductaId) {
                $existente = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                    ->where('conducta_id', $conductaId)
                    ->withTrashed()
                    ->first();

                if ($existente && $existente->trashed()) {
                    $existente->restore();
                } elseif (!$existente) {
                    Conductaperiodobimestre::create([
                        'periodo_bimestre_id' => $periodoBimestreId,
                        'conducta_id' => $conductaId
                    ]);
                }
            }

            // Eliminar conductas (solo las que no tienen notas)
            foreach ($conductasAEliminar as $conductaId) {
                $relacion = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                    ->where('conducta_id', $conductaId)
                    ->whereNull('deleted_at')
                    ->first();

                if ($relacion) {
                    $relacion->delete();
                }
            }

            DB::commit();

            $mensaje = 'Conductas asignadas correctamente.';
            if (!empty($relacionesConNotas)) {
                $mensaje .= ' Las conductas con notas registradas no se pueden desmarcar.';
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar: ' . $e->getMessage()
            ], 500);
        }
    }
    // Método para migrar conductas usando siglas (B1, B2, B3, B4)
    public function migrarConductas(Request $request)
    {
        $request->validate([
            'periodo_origen_id' => 'required|exists:periodos,id',
            'periodo_destino_id' => 'required|exists:periodos,id'
        ]);

        $periodoOrigen = Periodo::with(['periodobimestres' => function($query) {
            $query->where('tipo_bimestre', 'A')->with('conductas');
        }])->findOrFail($request->periodo_origen_id);

        $periodoDestino = Periodo::with(['periodobimestres' => function($query) {
            $query->where('tipo_bimestre', 'A');
        }])->findOrFail($request->periodo_destino_id);

        DB::beginTransaction();

        try {
            // Migrar conductas usando siglas
            foreach ($periodoOrigen->periodobimestres as $bimestreOrigen) {
                // Buscar por sigla
                $bimestreDestino = $periodoDestino->periodobimestres
                    ->where('sigla', $bimestreOrigen->sigla)
                    ->first();

                if ($bimestreDestino) {
                    $nuevasConductas = $bimestreOrigen->conductas->pluck('id')->toArray();
                    $periodoBimestreId = $bimestreDestino->id;

                    // Obtener las conductas actualmente asignadas (activas, no soft delete)
                    $conductasActuales = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                        ->whereNull('deleted_at')
                        ->pluck('conducta_id')
                        ->toArray();

                    // Conductas que hay que agregar (están en nuevas pero no en actuales)
                    $conductasAAgregar = array_diff($nuevasConductas, $conductasActuales);

                    // Conductas que hay que eliminar (están en actuales pero no en nuevas)
                    $conductasAEliminar = array_diff($conductasActuales, $nuevasConductas);

                    // Agregar nuevas conductas
                    foreach ($conductasAAgregar as $conductaId) {
                        // Verificar si ya existe un registro soft deleted
                        $existente = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                            ->where('conducta_id', $conductaId)
                            ->withTrashed()
                            ->first();

                        if ($existente && $existente->trashed()) {
                            // Restaurar (esto actualizará updated_at automáticamente)
                            $existente->restore();
                        } elseif (!$existente) {
                            // Crear nuevo (created_at y updated_at se llenan automáticamente)
                            Conductaperiodobimestre::create([
                                'periodo_bimestre_id' => $periodoBimestreId,
                                'conducta_id' => $conductaId
                            ]);
                        }
                    }

                    // Eliminar conductas (soft delete - actualiza deleted_at)
                    foreach ($conductasAEliminar as $conductaId) {
                        $relacion = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                            ->where('conducta_id', $conductaId)
                            ->whereNull('deleted_at')
                            ->first();

                        if ($relacion) {
                            $relacion->delete(); // Soft delete (actualiza deleted_at)
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('conducta.index')->with('success', 'Conductas migradas correctamente usando siglas y Soft Delete');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('conducta.index')->with('error', 'Error al migrar: ' . $e->getMessage());
        }
    }
    public function eliminarConductaBimestre(Request $request)
    {
        $request->validate([
            'periodo_bimestre_id' => 'required|exists:periodo_bimestres,id',
            'conducta_id' => 'required|exists:conductas,id'
        ]);

        try {
            // Buscar la relación en la tabla pivote
            $relacion = Conductaperiodobimestre::where('periodo_bimestre_id', $request->periodo_bimestre_id)
                ->where('conducta_id', $request->conducta_id)
                ->first();

            if (!$relacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'La relación no existe o ya fue eliminada'
                ], 404);
            }

            // Verificar si existen notas asociadas a esta relación
            $existenNotas = Conductaperiodobimestrenota::where('conducta_periodo_bimestre_id', $relacion->id)
                ->exists();

            if ($existenNotas) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la conducta porque ya tiene notas registradas. Primero debe eliminar las notas asociadas.'
                ], 409); // 409 Conflict
            }

            // Si no hay notas asociadas, aplicar Soft Delete
            $relacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conducta desasignada del bimestre correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getConductasAsignadas($periodoBimestreId)
    {
        try {
            $periodoBimestre = Periodobimestre::with('conductas')->findOrFail($periodoBimestreId);
            $conductasAsignadas = $periodoBimestre->conductas->pluck('id')->toArray();

            // Obtener las relaciones y verificar notas directamente con una subconsulta
            $relaciones = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                ->with('conducta')
                ->get();

            $conductasConNotas = [];
            $conductasInactivasAsignadas = [];

            foreach ($relaciones as $relacion) {
                // Verificar si tiene notas
                $tieneNotas = Conductaperiodobimestrenota::where('conducta_periodo_bimestre_id', $relacion->id)
                    ->exists();

                if ($tieneNotas) {
                    $conductasConNotas[] = $relacion->conducta_id;
                }

                // Verificar si es inactiva
                if ($relacion->conducta && $relacion->conducta->estado == '0') {
                    $conductasInactivasAsignadas[] = $relacion->conducta_id;
                }
            }

            return response()->json([
                'success' => true,
                'conductas_asignadas' => $conductasAsignadas,
                'conductas_con_notas' => $conductasConNotas,
                'conductas_inactivas_asignadas' => $conductasInactivasAsignadas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function verificarNotasConducta($periodoBimestreId, $conductaId)
    {
        try {
            // Buscar la relación
            $relacion = Conductaperiodobimestre::where('periodo_bimestre_id', $periodoBimestreId)
                ->where('conducta_id', $conductaId)
                ->first();

            if (!$relacion) {
                return response()->json([
                    'tiene_notas' => false,
                    'cantidad_notas' => 0
                ]);
            }

            // Contar notas asociadas
            $cantidadNotas = Conductaperiodobimestrenota::where('conducta_periodo_bimestre_id', $relacion->id)
                ->count();

            return response()->json([
                'tiene_notas' => $cantidadNotas > 0,
                'cantidad_notas' => $cantidadNotas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'tiene_notas' => false,
                'cantidad_notas' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function showPeriodoInactivo($periodo_id)
    {
        $periodo = Periodo::where('tipo_periodo', 'año escolar')
            ->where('estado', '0')
            ->with(['periodobimestres' => function($query) {
                $query->where('tipo_bimestre', 'A')
                      ->orderBy('bimestre', 'asc');
            }, 'periodobimestres.conductas'])
            ->findOrFail($periodo_id);

        return view('conducta.periodo_inactivo', compact('periodo'));
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
        try {
            // Contar las relaciones activas
            $cantidadRelaciones = Conductaperiodobimestre::where('conducta_id', $conducta->id)
                ->whereNull('deleted_at')
                ->count();

            if ($cantidadRelaciones > 0) {
                // Si tiene relaciones, no se puede eliminar
                return redirect()->route('conducta.index')
                    ->with('error', "No se puede eliminar la conducta '{$conducta->nombre}' porque está asignada a {$cantidadRelaciones} bimestre(s). Primero desasigne la conducta de todos los bimestres.");
            }

            // Si no tiene relaciones, proceder con la eliminación
            $conducta->delete();

            return redirect()->route('conducta.index')
                ->with('success', "Conducta '{$conducta->nombre}' eliminada exitosamente.");

        } catch (\Exception $e) {
            return redirect()->route('conducta.index')
                ->with('error', 'Error al eliminar la conducta: ' . $e->getMessage());
        }
    }
}
