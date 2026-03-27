<?php

namespace App\Http\Controllers\Materia;

use App\Http\Controllers\Controller;
//use App\Models\Materia\Materia;
use App\Models\Materia\Materiacompetencia;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Materia;

class MateriaCompetenciaController extends Controller
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
        $materias = Materia::where('estado', '1')->orderBy('nombre')->get();

        $competenciasQuery = Materiacompetencia::with('materia')
            ->orderBy('materia_id')
            ->orderBy('nombre');

        // Filtro por materia
        if ($request->has('materia_id') && $request->materia_id) {
            $competenciasQuery->where('materia_id', $request->materia_id);
        }

        // Filtro por estado
        $estado = $request->get('estado', 'activas');
        if ($estado === 'activas') {
            $competenciasQuery->where('estado', '1');
        } elseif ($estado === 'inactivas') {
            $competenciasQuery->where('estado', '0');
        }

        $competencias = $competenciasQuery->paginate(10);

        return view('materia.materiacompetencia.index', compact('competencias', 'materias'));
    }

    public function create()
    {
        $materias = Materia::where('estado', '1')->orderBy('nombre')->get();
        return view('materia.materiacompetencia.create', compact('materias'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'competencias' => 'required|array|min:1',
            'competencias.*.nombre' => 'required|string|max:255',
            'competencias.*.descripcion' => 'nullable|string',
            'competencias.*.estado' => 'required|in:0,1',
        ]);

        try {
            $competenciasCreadas = 0;

            foreach ($request->competencias as $competenciaData) {
                // Verificar si la competencia ya existe en esta materia
                $existe = Materiacompetencia::where('materia_id', $request->materia_id)
                    ->where('nombre', $competenciaData['nombre'])
                    ->exists();

                if (!$existe) {
                    Materiacompetencia::create([
                        'materia_id' => $request->materia_id,
                        'nombre' => $competenciaData['nombre'],
                        'descripcion' => $competenciaData['descripcion'] ?? null,
                        'estado' => $competenciaData['estado'],
                    ]);
                    $competenciasCreadas++;
                }
            }

            if ($competenciasCreadas > 0) {
                return redirect()->route('materiacompetencia.index')
                    ->with('success', "{$competenciasCreadas} competencia(s) creada(s) exitosamente.");
            } else {
                return redirect()->back()
                    ->with('warning', 'No se crearon competencias nuevas. Puede que ya existan con los mismos nombres.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear las competencias: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function edit($id)
    {
        $competencia = Materiacompetencia::with('materia')->findOrFail($id);
        return view('materia.materiacompetencia.edit', compact('competencia'));
    }

    public function update(Request $request, $id)
    {
        $competencia = Materiacompetencia::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:0,1',
        ]);

        try {
            $competencia->update($request->all());

            return redirect()->route('materiacompetencia.index', $competencia->materia_id)
                ->with('success', 'Competencia actualizada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la competencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $competencia = Materiacompetencia::findOrFail($id);
            $materiaId = $competencia->materia_id;
            $competencia->delete();

            return redirect()->route('materiacompetencia.index', $materiaId)
                ->with('success', 'Competencia eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('materiacompetencia.index', $materiaId ?? 0)
                ->with('error', 'Error al eliminar la competencia: ' . $e->getMessage());
        }
    }
    public function importar()
    {
        $materias = Materia::where('estado', '1')->orderBy('nombre')->get();
        return view('materia.materiacompetencia.importar', compact('materias'));
    }
    public function importarCompetencia(Request $request)
    {
        // Paso 1: Validar si es solo validación o procesamiento final
        if ($request->has('accion')) {
            $accion = $request->input('accion');

            if ($accion === 'cancelar') {
                // Limpiar sesión y cancelar
                session()->forget('import_competencia_data');

                return redirect()->route('materiacompetencia.importar')
                    ->with('info', 'Importación cancelada.');
            }

            if ($accion === 'procesar') {
                return $this->procesarImportacionCompetencia($request);
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
            $competenciasProcesadas = [];

            // Validar cada fila
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $numeroFila = $i + 1;

                try {
                    // Validar campos obligatorios
                    if (empty($row[0]) || empty($row[1])) {
                        $errores[] = [
                            'fila' => $numeroFila,
                            'error' => "Faltan campos obligatorios (Materia y Nombre de competencia son requeridos)"
                        ];
                        continue;
                    }

                    $materiaNombre = trim($row[0]);
                    $competenciaNombre = trim($row[1]);
                    $competenciaDescripcion = trim($row[2] ?? '');

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

                    // Verificar si ya existe esta competencia en la misma materia
                    $competenciaExistente = Materiacompetencia::where('materia_id', $materia->id)
                        ->where('nombre', $competenciaNombre)
                        ->first();

                    if ($competenciaExistente) {
                        $duplicados[] = [
                            'fila' => $numeroFila,
                            'error' => "La competencia '$competenciaNombre' ya existe en la materia '$materiaNombre'"
                        ];
                        continue;
                    }

                    // Verificar duplicados dentro del mismo archivo
                    $claveCompetencia = $materia->id . '-' . $competenciaNombre;
                    if (in_array($claveCompetencia, $competenciasProcesadas)) {
                        $duplicados[] = [
                            'fila' => $numeroFila,
                            'error' => "Competencia duplicada en el archivo - '$competenciaNombre' en '$materiaNombre'"
                        ];
                        continue;
                    }

                    // Agregar a registros válidos
                    $registrosValidos[] = [
                        'fila' => $numeroFila,
                        'datos' => [
                            'materia' => $materiaNombre,
                            'competencia' => $competenciaNombre,
                            'descripcion' => $competenciaDescripcion,
                            'materia_id' => $materia->id
                        ]
                    ];

                    $competenciasProcesadas[] = $claveCompetencia;

                } catch (\Exception $e) {
                    $errores[] = [
                        'fila' => $numeroFila,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Guardar datos en sesión para el próximo paso
            session()->put('import_competencia_data', [
                'registros_validos' => $registrosValidos,
                'total_registros' => $totalRegistros,
                'errores' => $errores,
                'duplicados' => $duplicados,
                'archivo_temp' => $request->file('archivo_excel')->getRealPath()
            ]);

            // Devolver a la vista con datos de validación
            return redirect()->route('materiacompetencia.importar')
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
    private function procesarImportacionCompetencia(Request $request)
    {
        try {
            $importData = session()->get('import_competencia_data');

            if (!$importData) {
                return redirect()->route('materiacompetencia.importar')
                    ->with('error', 'No hay datos de importación para procesar. Por favor, valide el archivo nuevamente.');
            }

            $registrosValidos = $importData['registros_validos'];
            $exitosos = 0;
            $erroresProceso = [];

            // Procesar cada registro válido
            foreach ($registrosValidos as $registro) {
                try {
                    Materiacompetencia::create([
                        'materia_id' => $registro['datos']['materia_id'],
                        'nombre' => $registro['datos']['competencia'],
                        'descripcion' => $registro['datos']['descripcion'],
                        'estado' => '1',
                    ]);

                    $exitosos++;

                } catch (\Exception $e) {
                    $erroresProceso[] = [
                        'fila' => $registro['fila'],
                        'error' => 'Error al crear competencia: ' . $e->getMessage()
                    ];
                }
            }

            // Limpiar sesión
            session()->forget('import_competencia_data');

            // Preparar mensaje final
            $mensaje = "Importación completada: $exitosos competencias importadas exitosamente.";
            $tipoMensaje = 'success';

            if (count($erroresProceso) > 0) {
                $mensaje .= " Se produjeron " . count($erroresProceso) . " errores durante el procesamiento.";
                $tipoMensaje = 'warning';

                // Guardar errores de proceso en sesión
                session()->flash('errores_proceso', $erroresProceso);
            }

            return redirect()->route('materiacompetencia.importar')
                ->with($tipoMensaje, $mensaje)
                ->with('exitosos', $exitosos);

        } catch (\Exception $e) {
            return redirect()->route('materiacompetencia.importar')
                ->with('error', 'Error durante el procesamiento: ' . $e->getMessage());
        }
    }
}
