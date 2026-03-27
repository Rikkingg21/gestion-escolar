<?php

namespace App\Http\Controllers\Materia;

use App\Http\Controllers\Controller;
use App\Models\Materia\Materiacompetencia;
use App\Models\Materia\Materiacriterio;
use Illuminate\Http\Request;
use App\Models\Materia;
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
        $materias = Materia::where('estado', 1)->orderBy('nombre')->get();
        $grados = Grado::where('estado', 1)->orderBy('grado')->orderBy('seccion')->get();

        // Obtener años únicos de los criterios
        $anios = Materiacriterio::select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        // Obtener bimestres únicos
        $bimestres = Materiacriterio::select('bimestre')
            ->distinct()
            ->orderBy('bimestre')
            ->pluck('bimestre');

        $criteriosQuery = Materiacriterio::with(['materia', 'grado', 'materiaCompetencia'])
            ->orderBy('materia_id')
            ->orderBy('anio', 'desc')
            ->orderBy('bimestre')
            ->orderBy('grado_id');

        // Aplicar filtros
        if ($request->has('materia_id') && $request->materia_id) {
            $criteriosQuery->where('materia_id', $request->materia_id);
        }

        if ($request->has('grado_id') && $request->grado_id) {
            $criteriosQuery->where('grado_id', $request->grado_id);
        }

        if ($request->has('anio') && $request->anio) {
            $criteriosQuery->where('anio', $request->anio);
        }

        if ($request->has('bimestre') && $request->bimestre) {
            $criteriosQuery->where('bimestre', $request->bimestre);
        }

        $criterios = $criteriosQuery->get();

        // Agrupar por competencia (manteniendo tu lógica actual)
        $criteriosAgrupados = $criterios->groupBy(function($criterio) {
            return $criterio->materiaCompetencia->nombre ?? 'Sin Competencia';
        });

        // Asignar colores (manteniendo tu lógica actual)
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
            'anios',
            'bimestres'
        ));
    }
    // Función helper para determinar el tipo de filtro
    protected function determinarTipoFiltro($id)
    {
        // Aquí deberías implementar lógica para determinar si el ID es de materia, grado o competencia
        // Esto es un ejemplo básico - ajusta según tu necesidad
        if (Materia::where('id', $id)->exists()) {
            return ['campo' => 'materia_id', 'tipo' => 'materia'];
        } elseif (Grado::where('id', $id)->exists()) {
            return ['campo' => 'grado_id', 'tipo' => 'grado'];
        } else {
            return ['campo' => 'materia_competencia_id', 'tipo' => 'competencia'];
        }
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

    $anios = range(date('Y') - 1, date('Y') + 1);

    return view('materia.materiacriterio.create', compact(
        'materias',
        'grados',
        'anios'
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
        'criterios.*.anio' => 'required|numeric|min:2020|max:2030',
        'criterios.*.bimestres' => 'required|array|min:1', // Cambiado a bimestres (plural)
        'criterios.*.bimestres.*' => 'in:1,2,3,4', // Validación para cada bimestre
        'criterios.*.grados' => 'required|array|min:1',
        'criterios.*.grados.*' => 'exists:grados,id',
    ]);

    try {
        $criteriosCreados = 0;

        foreach ($request->criterios as $criterioData) {
            foreach ($criterioData['grados'] as $gradoId) {
                foreach ($criterioData['bimestres'] as $bimestre) { // Ahora iteramos sobre bimestres
                    // Verificar si el criterio ya existe para esta competencia, grado, año y bimestre
                    $existe = Materiacriterio::where('materia_competencia_id', $request->materia_competencia_id)
                        ->where('grado_id', $gradoId)
                        ->where('anio', $criterioData['anio'])
                        ->where('bimestre', $bimestre) // Usamos $bimestre del array
                        ->where('nombre', $criterioData['nombre'])
                        ->exists();

                    if (!$existe) {
                        Materiacriterio::create([
                            'materia_competencia_id' => $request->materia_competencia_id,
                            'materia_id' => $request->materia_id,
                            'grado_id' => $gradoId,
                            'anio' => $criterioData['anio'],
                            'bimestre' => $bimestre, // Usamos $bimestre del array
                            'nombre' => $criterioData['nombre'],
                            'descripcion' => $criterioData['descripcion'] ?? null,
                        ]);
                        $criteriosCreados++;
                    }
                }
            }
        }

        if ($criteriosCreados > 0) {
            return redirect()->route('materiacriterio.index')
                ->with('success', "{$criteriosCreados} criterio(s) creado(s) exitosamente.");
        } else {
            return redirect()->back()
                ->with('warning', 'No se crearon criterios nuevos. Puede que ya existan con la misma configuración.')
                ->withInput();
        }

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Error al crear los criterios: ' . $e->getMessage())
            ->withInput();
    }
}


    public function edit($id)
    {
        // Obtener el criterio específico
        $criterio = MateriaCriterio::findOrFail($id);

        // Obtener todos los criterios con el mismo nombre, materia y competencia (para edición múltiple)
        $criteriosRelacionados = MateriaCriterio::where('nombre', $criterio->nombre)
            ->where('materia_id', $criterio->materia_id)
            ->where('materia_competencia_id', $criterio->materia_competencia_id)
            ->where('anio', $criterio->anio)
            ->get();

        // Obtener datos para los selects
        $materia = Materia::findOrFail($criterio->materia_id);
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
        ->get();

        return view('materia.materiacriterio.edit', [
            'criterio' => $criterio,
            'criteriosRelacionados' => $criteriosRelacionados,
            'materia' => $materia,
            'grados' => $grados,
            'competencias' => $competencias,
            'anios' => range(date('Y') - 1, date('Y') + 1),
            'gradosSeleccionados' => $criteriosRelacionados->pluck('grado_id')->toArray()
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'materia_competencia_id' => 'required|exists:materia_competencias,id',
            'grados' => 'required|array',
            'grados.*' => 'exists:grados,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'anio' => 'required|numeric|min:2020|max:2030'
        ]);

        // 1. Actualizar el criterio específico que se está editando
        $criterio = MateriaCriterio::findOrFail($id);
        $criterio->update([
            'materia_competencia_id' => $request->materia_competencia_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'anio' => $request->anio,
            'grado_id' => $request->grados[0] // Tomamos el primer grado seleccionado
        ]);

        // 2. Manejar grados adicionales seleccionados
        if (count($request->grados) > 1) {
            // Obtener los grados adicionales (excluyendo el primero que ya actualizamos)
            $gradosAdicionales = array_slice($request->grados, 1);

            // Crear nuevos criterios para los grados adicionales
            foreach ($gradosAdicionales as $grado_id) {
                MateriaCriterio::create([
                    'materia_id' => $request->materia_id,
                    'materia_competencia_id' => $request->materia_competencia_id,
                    'grado_id' => $grado_id,
                    'nombre' => $request->nombre,
                    'descripcion' => $request->descripcion,
                    'anio' => $request->anio
                ]);
            }
        }

        return redirect()
            ->route('materiacriterio.index', $request->materia_id)
            ->with('success', 'Criterio actualizado exitosamente');
    }

    public function destroy($id)
    {
        try {
            $criterio = Materiacriterio::findOrFail($id);
            $competenciaId = $criterio->materia_competencia_id;
            $criterio->delete();

            return redirect()->route('materiacriterio.index', $competenciaId)
                ->with('success', 'Criterio eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('materiacriterio.index', $competenciaId ?? 0)
                ->with('error', 'Error al eliminar el criterio: ' . $e->getMessage());
        }
    }
    public function importar()
    {
        $materias = Materia::where('estado', '1')->orderBy('nombre')->get();
        return view('materia.materiacriterio.importar', compact('materias'));
    }
    public function importarCriterio(Request $request)
    {
        // Paso 1: Validar si es solo validación o procesamiento final
        if ($request->has('accion')) {
            $accion = $request->input('accion');

            if ($accion === 'cancelar') {
                // Limpiar sesión y cancelar
                session()->forget('import_data');

                return redirect()->route('materiacriterio.importar')
                    ->with('info', 'Importación cancelada.');
            }

            if ($accion === 'procesar') {
                return $this->procesarImportacion($request);
            }
        }

        // Paso 1: Validación inicial del archivo
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        try {
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
                        empty($row[5]) || empty($row[6]) || empty($row[7]) || empty($row[8])) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "Faltan campos obligatorios (Materia, Competencia, Nombre, Grado, Sección, Nivel, Año y Bimestre son requeridos)"
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
                    $anio = trim($row[7]);
                    $bimestre = trim($row[8]);

                    // Validar formato del año
                    if (!is_numeric($anio) || strlen($anio) != 4) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "El año '$anio' no tiene un formato válido (debe ser 4 dígitos)"
                        ];
                        continue;
                    }

                    // Validar formato del grado
                    if (!is_numeric($gradoNumero)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "El grado '$gradoNumero' debe ser un número"
                        ];
                        continue;
                    }

                    // Validar bimestre
                    $bimestresValidos = ['1', '2', '3', '4'];
                    if (!in_array($bimestre, $bimestresValidos)) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "El bimestre '$bimestre' no es válido. Debe ser: 1, 2, 3 o 4"
                        ];
                        continue;
                    }

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
                        ->where('anio', $anio)
                        ->where('bimestre', $bimestre)
                        ->where('nombre', $criterioNombre)
                        ->first();

                    if ($criterioExistente) {
                        $duplicados[] = [
                            'fila' => $numeroFila,
                            'error' => "El criterio '$criterioNombre' ya existe para la competencia '$competenciaNombre', grado " . $grado->nombreCompleto . ", año '$anio' y bimestre '$bimestre'"
                        ];
                        continue;
                    }

                    // Verificar duplicados dentro del archivo
                    $claveCriterio = $competencia->id . '-' . $grado->id . '-' . $anio . '-' . $bimestre . '-' . $criterioNombre;
                    if (in_array($claveCriterio, $criteriosProcesados)) {
                        $duplicados[] = [
                            'fila' => $numeroFila,
                            'error' => "Criterio duplicado en el archivo - '$criterioNombre' para competencia '$competenciaNombre', grado " . $grado->nombreCompleto . ", año '$anio' y bimestre '$bimestre'"
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
                            'anio' => $anio,
                            'bimestre' => $bimestre,
                            'materia_id' => $materia->id,
                            'competencia_id' => $competencia->id,
                            'grado_id' => $grado->id
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
                'archivo_temp' => $request->file('archivo_excel')->getRealPath()
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

    // Método privado para procesar la importación
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
                        'anio' => $registro['datos']['anio'],
                        'bimestre' => $registro['datos']['bimestre'],
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
