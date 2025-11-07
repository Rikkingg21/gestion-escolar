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

    public function index($id)
    {
        $materia = Materia::findOrFail($id);
        $competencias = Materiacompetencia::where('materia_id', $id)
            ->orderBy('nombre')
            ->paginate(5);

        return view('materia.materiacompetencia.index', compact('materia', 'competencias'));
    }

    public function create($id)
    {
        $materia = Materia::findOrFail($id);
        return view('materia.materiacompetencia.create', compact('materia'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:0,1',
        ]);

        try {
            Materiacompetencia::create($request->all());

            return redirect()->route('materiacompetencia.index', $request->materia_id)
                ->with('success', 'Competencia creada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear la competencia: ' . $e->getMessage())
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
        $materias = Materia::where('estado', 1)->orderBy('nombre')->get();
        return view('materia.materiacompetencia.importar', compact('materias'));
    }
    public function importarCompetencia(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('archivo_excel'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $exitosos = 0;
            $errores = [];
            $duplicados = [];
            $competenciasProcesadas = [];

            // Saltar la primera fila (encabezados)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $numeroFila = $i + 1;

                try {
                    // Validar que todos los campos necesarios estén presentes
                    if (empty($row[0]) || empty($row[1])) {
                        $errores[] = "Fila $numeroFila: Faltan campos obligatorios (Materia y Nombre de competencia son requeridos)";
                        continue;
                    }

                    $materiaNombre = trim($row[0]);
                    $competenciaNombre = trim($row[1]);
                    $competenciaDescripcion = trim($row[2] ?? '');

                    // Buscar la materia por nombre
                    $materia = Materia::where('nombre', $materiaNombre)
                        ->where('estado', '1')
                        ->first();

                    if (!$materia) {
                        $errores[] = "Fila $numeroFila: La materia '$materiaNombre' no existe o no está activa";
                        continue;
                    }

                    // Verificar si ya existe esta competencia en la misma materia
                    $competenciaExistente = Materiacompetencia::where('materia_id', $materia->id)
                        ->where('nombre', $competenciaNombre)
                        ->first();

                    if ($competenciaExistente) {
                        $duplicados[] = "Fila $numeroFila: La competencia '$competenciaNombre' ya existe en la materia '$materiaNombre'";
                        continue;
                    }

                    // Verificar duplicados dentro del mismo archivo
                    $claveCompetencia = $materia->id . '-' . $competenciaNombre;
                    if (in_array($claveCompetencia, $competenciasProcesadas)) {
                        $duplicados[] = "Fila $numeroFila: Competencia duplicada en el archivo - '$competenciaNombre' en '$materiaNombre'";
                        continue;
                    }

                    // Crear la competencia
                    Materiacompetencia::create([
                        'materia_id' => $materia->id,
                        'nombre' => $competenciaNombre,
                        'descripcion' => $competenciaDescripcion,
                        'estado' => '1',
                    ]);

                    $competenciasProcesadas[] = $claveCompetencia;
                    $exitosos++;

                } catch (\Exception $e) {
                    $errores[] = "Fila $numeroFila: " . $e->getMessage();
                }
            }

            // Preparar mensajes para el usuario
            $mensaje = "Importación completada: $exitosos competencias importadas exitosamente.";

            if (count($duplicados) > 0) {
                $mensaje .= " Se encontraron " . count($duplicados) . " competencias duplicadas.";
            }

            if (count($errores) > 0) {
                $mensaje .= " Se produjeron " . count($errores) . " errores.";
            }

            $tipoMensaje = (count($errores) > 0) ? 'warning' : 'success';

            return redirect()->route('materiacompetencia.importar')
                ->with($tipoMensaje, $mensaje)
                ->with('duplicados', $duplicados)
                ->with('errores', $errores);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage())
                ->withInput();
        }
    }
}
