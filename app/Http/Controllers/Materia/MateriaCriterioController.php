<?php

namespace App\Http\Controllers\Materia;

use App\Http\Controllers\Controller;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Periodobimestre;
use App\Models\Grado;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MateriaCriterioController extends Controller
{
    //moduleID 11 = Materias
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->canAccessModule('11')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Validar que se haya seleccionado un período
        if (!$request->has('periodo_id') || !$request->periodo_id) {
            // Si no hay período seleccionado, mostrar un mensaje y no cargar criterios
            $materias = Materia::where('estado', 1)->orderBy('nombre')->get();
            $grados = Grado::where('estado', 1)->orderBy('grado')->orderBy('seccion')->get();
            $periodos = Periodo::where('estado', 1)
                ->where('tipo_periodo', 'año escolar')
                ->orderBy('anio', 'desc')
                ->get();

            $criteriosAgrupados = collect(); // Vacío

            return view('materia.materiacriterio.index', compact(
                'criteriosAgrupados',
                'materias',
                'grados',
                'periodos'
            ));
        }

        $materias = Materia::where('estado', 1)->orderBy('nombre')->get();
        $grados = Grado::where('estado', 1)->orderBy('grado')->orderBy('seccion')->get();

        // Obtener períodos de tipo 'año escolar'
        $periodos = Periodo::where('estado', 1)
            ->where('tipo_periodo', 'año escolar')
            ->orderBy('anio', 'desc')
            ->get();

        // Obtener periodos_bimestres según el período seleccionado
        $periodosBimestres = Periodobimestre::where('periodo_id', $request->periodo_id)
            ->orderBy('bimestre')
            ->get();

        // Query base con eager loading
        $criteriosQuery = Materiacriterio::with([
            'materia',
            'grado',
            'materiaCompetencia',
            'periodoBimestre',
            'periodoBimestre.periodo'
        ]);

        // Aplicar filtros obligatorios
        if ($request->has('periodo_bimestre_id') && $request->periodo_bimestre_id) {
            $criteriosQuery->where('periodo_bimestre_id', $request->periodo_bimestre_id);
        } else {
            // Si no hay bimestre específico, filtrar por todos los bimestres del período
            $criteriosQuery->whereHas('periodoBimestre', function($query) use ($request) {
                $query->where('periodo_id', $request->periodo_id);
            });
        }

        // Aplicar filtros opcionales
        if ($request->has('materia_id') && $request->materia_id) {
            $criteriosQuery->where('materia_id', $request->materia_id);
        }

        if ($request->has('grado_id') && $request->grado_id) {
            $criteriosQuery->where('grado_id', $request->grado_id);
        }

        $criterios = $criteriosQuery->orderBy('materia_id')
            ->orderBy('grado_id')
            ->get();

        // Agrupar por competencia
        $criteriosAgrupados = $criterios->groupBy(function($criterio) {
            return $criterio->materiaCompetencia->nombre ?? 'Sin Competencia';
        });

        // Asignar colores
        $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];
        $colorIndex = 0;

        foreach ($criteriosAgrupados as $competencia => $criteriosGrupo) {
            foreach ($criteriosGrupo as $criterio) {
                $criterio->rowColor = $colors[$colorIndex % count($colors)];
            }
            $colorIndex++;
        }

        return view('materia.materiacriterio.index', compact(
            'criteriosAgrupados',
            'materias',
            'grados',
            'periodos',
            'periodosBimestres'
        ));
    }

    public function create()
    {
        $materias = Materia::where('estado', '1')->orderBy('nombre')->get();

        $grados = Grado::where('estado', 1)
                    ->orderByRaw("
                        CASE
                            WHEN nivel = 'Primaria' THEN 1
                            WHEN nivel = 'Secundaria' THEN 2
                            ELSE 3
                        END,
                        grado ASC,
                        seccion ASC
                    ")
                    ->get();

        // Obtener períodos activos de tipo 'año escolar'
        $periodos = Periodo::where('estado', 1)
                    ->where('tipo_periodo', 'año escolar')
                    ->orderBy('anio', 'desc')
                    ->get();

        return view('materia.materiacriterio.create', compact(
            'materias',
            'grados',
            'periodos'
        ));
    }
    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'materia_competencia_id' => 'required|exists:materia_competencias,id',
            'criterios' => 'required|array|min:1',
            'criterios.*.nombre' => 'required|string|max:255',
            'criterios.*.descripcion' => 'nullable|string',
            'criterios.*.periodos_bimestres' => 'required|array|min:1',
            'criterios.*.periodos_bimestres.*' => 'exists:periodo_bimestres,id',
            'criterios.*.grados' => 'required|array|min:1',
            'criterios.*.grados.*' => 'exists:grados,id',
        ]);

        try {
            DB::beginTransaction();

            $criteriosCreados = 0;

            foreach ($request->criterios as $criterioData) {
                foreach ($criterioData['grados'] as $gradoId) {
                    foreach ($criterioData['periodos_bimestres'] as $periodoBimestreId) {
                        // Verificar si el criterio ya existe
                        $existe = Materiacriterio::where('materia_competencia_id', $request->materia_competencia_id)
                            ->where('grado_id', $gradoId)
                            ->where('periodo_bimestre_id', $periodoBimestreId)
                            ->where('nombre', $criterioData['nombre'])
                            ->exists();

                        if (!$existe) {
                            Materiacriterio::create([
                                'materia_competencia_id' => $request->materia_competencia_id,
                                'materia_id' => $request->materia_id,
                                'grado_id' => $gradoId,
                                'periodo_bimestre_id' => $periodoBimestreId,
                                'nombre' => $criterioData['nombre'],
                                'descripcion' => $criterioData['descripcion'] ?? null,
                            ]);
                            $criteriosCreados++;
                        }
                    }
                }
            }

            DB::commit();

            if ($criteriosCreados > 0) {
                return redirect()->route('materiacriterio.index')
                    ->with('success', "{$criteriosCreados} criterio(s) creado(s) exitosamente.");
            } else {
                return redirect()->back()
                    ->with('warning', 'No se crearon criterios nuevos. Puede que ya existan con la misma configuración.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al crear los criterios: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function getBimestres($periodo_id)
    {
        try {
            $bimestres = Periodobimestre::where('periodo_id', $periodo_id)
                        ->with('periodo')
                        ->orderBy('bimestre')
                        ->get();

            return response()->json($bimestres);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function edit($id)
    {
        // Obtener el criterio específico
        $criterio = MateriaCriterio::with(['periodoBimestre.periodo'])->findOrFail($id);

        // Obtener datos para los selects
        $materias = Materia::where('estado', '1')->orderBy('nombre')->get();

        $grados = Grado::where('estado', 1)
                    ->orderByRaw("
                        CASE
                            WHEN nivel = 'Primaria' THEN 1
                            WHEN nivel = 'Secundaria' THEN 2
                            ELSE 3
                        END,
                        grado ASC,
                        seccion ASC
                    ")
                    ->get();

        $competencias = MateriaCompetencia::where('materia_id', $criterio->materia_id)
                        ->where('estado', "1")
                        ->orderBy('nombre')
                        ->get();

        // Obtener períodos activos
        $periodos = Periodo::where('estado', 1)
                    ->where('tipo_periodo', 'año escolar')
                    ->orderBy('anio', 'desc')
                    ->get();

        // Obtener bimestres del período actual del criterio
        $bimestresDelPeriodo = Periodobimestre::where('periodo_id', $criterio->periodoBimestre->periodo_id)
                                ->orderBy('bimestre')
                                ->get();

        return view('materia.materiacriterio.edit', compact(
            'criterio',
            'materias',
            'grados',
            'competencias',
            'periodos',
            'bimestresDelPeriodo'
        ));
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:500',
            'descripcion' => 'nullable|string',
            'grado_id' => 'required|exists:grados,id',
            'periodo_id' => 'required|exists:periodos,id',
            'periodo_bimestre_id' => 'required|exists:periodo_bimestres,id'
        ]);

        try {
            $criterio = MateriaCriterio::findOrFail($id);

            $criterio->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'grado_id' => $request->grado_id,
                'periodo_bimestre_id' => $request->periodo_bimestre_id
                // No actualizamos materia_id ni materia_competencia_id
            ]);

            return redirect()
                ->route('materiacriterio.index')
                ->with('success', 'Criterio actualizado exitosamente.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al actualizar el criterio: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $criterio = MateriaCriterio::findOrFail($id);

            // Eliminar todos los criterios relacionados (mismo nombre, materia, competencia y período)
            $deletedCount = MateriaCriterio::where('nombre', $criterio->nombre)
                ->where('materia_id', $criterio->materia_id)
                ->where('materia_competencia_id', $criterio->materia_competencia_id)
                ->where('periodo_bimestre_id', $criterio->periodo_bimestre_id)
                ->delete();

            DB::commit();

            return redirect()->route('materiacriterio.index')
                ->with('success', "Criterio eliminado exitosamente. Se eliminaron {$deletedCount} registro(s).");

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('materiacriterio.index')
                ->with('error', 'Error al eliminar el criterio: ' . $e->getMessage());
        }
    }
    public function importar()
    {
        $materias = Materia::where('estado', '1')->orderBy('nombre')->get();
        $periodos = Periodo::where('estado', '1')
                    ->where('tipo_periodo', 'año escolar')
                    ->orderBy('anio', 'desc')
                    ->get();

        return view('materia.materiacriterio.importar', compact('materias', 'periodos'));
    }
    public function importarCriterio(Request $request)
    {
        // Paso 1: Validar si es solo validación o procesamiento final
        if ($request->has('accion')) {
            $accion = $request->input('accion');

            if ($accion === 'cancelar') {
                session()->forget('import_data');
                return redirect()->route('materiacriterio.importar')
                    ->with('info', 'Importación cancelada.');
            }

            if ($accion === 'procesar') {
                return $this->procesarImportacion($request);
            }
        }

        // Validación inicial del archivo y período
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls|max:2048',
            'periodo_id' => 'required|exists:periodos,id'
        ]);

        try {
            $periodoId = $request->periodo_id;

            // Obtener todos los bimestres del período seleccionado
            $bimestresDisponibles = Periodobimestre::where('periodo_id', $periodoId)
                ->get()
                ->keyBy('sigla'); // Indexar por sigla para búsqueda rápida

            $spreadsheet = IOFactory::load($request->file('archivo_excel'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $totalRegistros = count($rows) - 1;
            $errores = [];
            $duplicados = [];
            $registrosValidos = [];
            $criteriosProcesados = [];

            // Validar cada fila
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $numeroFila = $i + 1;

                try {
                    // Validar campos obligatorios
                    if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[4]) ||
                        empty($row[5]) || empty($row[6]) || empty($row[7])) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "Faltan campos obligatorios (Materia, Competencia, Nombre, Grado, Sección, Nivel y Sigla son requeridos)"
                        ];
                        continue;
                    }

                    $materiaNombre = trim($row[0]);
                    $competenciaNombre = trim($row[1]);
                    $criterioNombre = trim($row[2]);
                    $criterioDescripcion = trim($row[3] ?? '');
                    $gradoNumero = trim($row[4]);
                    $seccion = trim($row[5]);
                    $nivel = trim($row[6]);
                    $siglaPeriodoBimestre = trim($row[7]);

                    // Validar formato del grado
                    if (!is_numeric($gradoNumero)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "El grado '$gradoNumero' debe ser un número"
                        ];
                        continue;
                    }

                    // Buscar el periodo_bimestre por sigla
                    if (!isset($bimestresDisponibles[$siglaPeriodoBimestre])) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "La sigla '$siglaPeriodoBimestre' no existe en el período seleccionado"
                        ];
                        continue;
                    }

                    $periodoBimestre = $bimestresDisponibles[$siglaPeriodoBimestre];
                    $bimestreId = $periodoBimestre->id;

                    // Buscar la materia
                    $materia = Materia::where('nombre', $materiaNombre)
                        ->where('estado', '1')
                        ->first();

                    if (!$materia) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "La materia '$materiaNombre' no existe o no está activa"
                        ];
                        continue;
                    }

                    // Buscar la competencia
                    $competencia = Materiacompetencia::where('nombre', $competenciaNombre)
                        ->where('materia_id', $materia->id)
                        ->where('estado', '1')
                        ->first();

                    if (!$competencia) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "La competencia '$competenciaNombre' no existe en la materia '$materiaNombre' o no está activa"
                        ];
                        continue;
                    }

                    // Buscar el grado
                    $grado = Grado::where('grado', $gradoNumero)
                        ->where('seccion', $seccion)
                        ->where('nivel', $nivel)
                        ->where('estado', '1')
                        ->first();

                    if (!$grado) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "El grado " . $gradoNumero . "° '" . $seccion . "' - " . $nivel . " no existe o no está activo"
                        ];
                        continue;
                    }

                    // Verificar duplicados en base de datos
                    $criterioExistente = Materiacriterio::where('materia_competencia_id', $competencia->id)
                        ->where('grado_id', $grado->id)
                        ->where('periodo_bimestre_id', $bimestreId)
                        ->where('nombre', $criterioNombre)
                        ->first();

                    if ($criterioExistente) {
                        $duplicados[] = [
                            'fila' => $numeroFila,
                            'error' => "El criterio '$criterioNombre' ya existe para la competencia '$competenciaNombre', grado " . $grado->nombreCompleto . " y bimestre '$siglaPeriodoBimestre'"
                        ];
                        continue;
                    }

                    // Verificar duplicados dentro del archivo
                    $claveCriterio = $competencia->id . '-' . $grado->id . '-' . $bimestreId . '-' . $criterioNombre;
                    if (in_array($claveCriterio, $criteriosProcesados)) {
                        $duplicados[] = [
                            'fila' => $numeroFila,
                            'error' => "Criterio duplicado en el archivo - '$criterioNombre' para competencia '$competenciaNombre', grado " . $grado->nombreCompleto . " y bimestre '$siglaPeriodoBimestre'"
                        ];
                        continue;
                    }

                    // Agregar a registros válidos
                    $registrosValidos[] = [
                        'fila' => $numeroFila,
                        'datos' => [
                            'materia' => $materiaNombre,
                            'competencia' => $competenciaNombre,
                            'criterio' => $criterioNombre,
                            'descripcion' => $criterioDescripcion,
                            'grado' => $grado->nombreCompleto ?? ($gradoNumero . '° ' . $seccion . ' - ' . $nivel),
                            'sigla' => $siglaPeriodoBimestre,
                            'bimestre_info' => $periodoBimestre->bimestre,
                            'materia_id' => $materia->id,
                            'competencia_id' => $competencia->id,
                            'grado_id' => $grado->id,
                            'periodo_bimestre_id' => $bimestreId
                        ]
                    ];

                    $criteriosProcesados[] = $claveCriterio;

                } catch (\Exception $e) {
                    $errores[] = [
                        'fila' => $numeroFila,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Guardar datos en sesión para el próximo paso
            session()->put('import_data', [
                'registros_validos' => $registrosValidos,
                'total_registros' => $totalRegistros,
                'errores' => $errores,
                'duplicados' => $duplicados,
                'periodo_id' => $periodoId
            ]);

            // Devolver a la vista con datos de validación
            return redirect()->route('materiacriterio.importar')
                ->with('validacion_completa', true)
                ->with('total_registros', $totalRegistros)
                ->with('registros_validos', count($registrosValidos))
                ->with('errores_validacion', $errores)
                ->with('duplicados_validacion', $duplicados)
                ->with('datos_validos', $registrosValidos);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage())
                ->withInput();
        }
    }
    private function procesarImportacion(Request $request)
    {
        try {
            $importData = session()->get('import_data');

            if (!$importData) {
                return redirect()->route('materiacriterio.importar')
                    ->with('error', 'No hay datos de importación para procesar. Por favor, valide el archivo nuevamente.');
            }

            $registrosValidos = $importData['registros_validos'];
            $exitosos = 0;
            $erroresProceso = [];

            // Procesar cada registro válido
            foreach ($registrosValidos as $registro) {
                try {
                    Materiacriterio::create([
                        'materia_competencia_id' => $registro['datos']['competencia_id'],
                        'materia_id' => $registro['datos']['materia_id'],
                        'grado_id' => $registro['datos']['grado_id'],
                        'periodo_bimestre_id' => $registro['datos']['periodo_bimestre_id'],
                        'nombre' => $registro['datos']['criterio'],
                        'descripcion' => $registro['datos']['descripcion'],
                    ]);

                    $exitosos++;

                } catch (\Exception $e) {
                    $erroresProceso[] = [
                        'fila' => $registro['fila'],
                        'error' => 'Error al crear criterio: ' . $e->getMessage()
                    ];
                }
            }

            // Limpiar sesión
            session()->forget('import_data');

            // Preparar mensaje final
            $mensaje = "Importación completada: $exitosos criterios importados exitosamente.";
            $tipoMensaje = 'success';

            if (count($erroresProceso) > 0) {
                $mensaje .= " Se produjeron " . count($erroresProceso) . " errores durante el procesamiento.";
                $tipoMensaje = 'warning';

                // Guardar errores de proceso en sesión
                session()->flash('errores_proceso', $erroresProceso);
            }

            return redirect()->route('materiacriterio.importar')
                ->with($tipoMensaje, $mensaje)
                ->with('exitosos', $exitosos);

        } catch (\Exception $e) {
            return redirect()->route('materiacriterio.importar')
                ->with('error', 'Error durante el procesamiento: ' . $e->getMessage());
        }
    }
}
