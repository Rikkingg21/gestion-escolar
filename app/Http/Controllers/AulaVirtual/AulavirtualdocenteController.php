<?php

namespace App\Http\Controllers\AulaVirtual;

use App\Http\Controllers\Controller;
use App\Models\AulaVirtual\Aulavirtualsesion;
use App\Models\AulaVirtual\Materialtrabajo;
use App\Models\Docente;
use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Periodo;
use Illuminate\Http\Request;

class AulavirtualdocenteController extends Controller
{
    // moduleID 22 = Aula Virtual Docente
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->canAccessModule('22')) {
                abort(403, 'No tienes permiso para acceder a este módulo.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $gestor = $this->isGestor();
        $docente = $this->currentDocente();

        if ($gestor) {
            $periodos = Periodo::where('tipo_periodo', 'año escolar')
                ->orderBy('anio', 'desc')
                ->orderBy('nombre')
                ->get();
        } elseif ($docente) {
            $periodosIds = Cursogradosecnivanio::where('docente_designado_id', $docente->id)
                ->distinct()
                ->pluck('periodo_id');

            $periodos = Periodo::where('tipo_periodo', 'año escolar')
                ->whereIn('id', $periodosIds)
                ->orderBy('anio', 'desc')
                ->orderBy('nombre')
                ->get();
        } else {
            $periodos = collect();
        }

        $periodoSeleccionadoId = $request->get('periodo_id') ?: $periodos->first()?->id;

        $cursosQuery = Cursogradosecnivanio::with(['grado', 'materia', 'periodo', 'docente.user'])
            ->where('periodo_id', $periodoSeleccionadoId);

        if (! $gestor && $docente) {
            $cursosQuery->where('docente_designado_id', $docente->id);
        }

        $cursos = $cursosQuery->orderBy('materia_id')->get();

        $cursoId = $request->get('curso_grado_sec_niv_anio_id');

        $sesiones = Aulavirtualsesion::with(['curso.grado', 'curso.materia', 'docente.user'])
            ->whereIn('curso_grado_sec_niv_anio_id', $cursos->pluck('id'))
            ->where('estado', '1')
            ->when($cursoId, fn ($query) => $query->where('curso_grado_sec_niv_anio_id', $cursoId))
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();

        $material = $docente
            ? Materialtrabajo::firstOrCreate(['docente_id' => $docente->id])
            : null;

        return view('aula-virtual.docente.index', compact(
            'periodos',
            'periodoSeleccionadoId',
            'cursos',
            'cursoId',
            'sesiones',
            'material',
            'docente',
            'gestor'
        ));
    }

    public function create()
    {
        $cursos = $this->cursosDisponibles();

        return view('aula-virtual.docente.create', compact('cursos'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSesion($request);

        $curso = Cursogradosecnivanio::findOrFail($validated['curso_grado_sec_niv_anio_id']);

        $this->authorizeCurso($curso);

        Aulavirtualsesion::create([
            'curso_grado_sec_niv_anio_id' => $curso->id,
            'docente_id' => $curso->docente_designado_id,
            'titulo' => $validated['titulo'] ?? null,
            'plataforma' => $validated['plataforma'] ?? null,
            'enlace' => $validated['enlace'],
            'enlace_material' => $validated['enlace_material'] ?? null,
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'motivo' => $validated['motivo'],
            'observaciones' => $validated['observaciones'] ?? null,
            'estado' => $validated['estado'],
        ]);

        return redirect()->route('aula-virtual-docente.index')
            ->with('success', 'Clase virtual creada exitosamente.');
    }

    public function edit($id)
    {
        $sesion = Aulavirtualsesion::findOrFail($id);

        $this->authorizeSesion($sesion);

        $cursos = $this->cursosDisponibles();

        return view('aula-virtual.docente.edit', compact('sesion', 'cursos'));
    }

    public function update(Request $request, $id)
    {
        $sesion = Aulavirtualsesion::findOrFail($id);

        $this->authorizeSesion($sesion);

        $validated = $this->validateSesion($request);

        $curso = Cursogradosecnivanio::findOrFail($validated['curso_grado_sec_niv_anio_id']);

        $this->authorizeCurso($curso);

        $sesion->update([
            'curso_grado_sec_niv_anio_id' => $curso->id,
            'docente_id' => $curso->docente_designado_id,
            'titulo' => $validated['titulo'] ?? null,
            'plataforma' => $validated['plataforma'] ?? null,
            'enlace' => $validated['enlace'],
            'enlace_material' => $validated['enlace_material'] ?? null,
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'motivo' => $validated['motivo'],
            'observaciones' => $validated['observaciones'] ?? null,
            'estado' => $validated['estado'],
        ]);

        return redirect()->route('aula-virtual-docente.index')
            ->with('success', 'Clase virtual actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $sesion = Aulavirtualsesion::findOrFail($id);

        $this->authorizeSesion($sesion);

        $sesion->delete();

        return redirect()->route('aula-virtual-docente.index')
            ->with('success', 'Clase virtual eliminada exitosamente.');
    }

    public function materialGuardar(Request $request)
    {
        $docente = $this->currentDocente();

        if (! $docente) {
            abort(403, 'No tienes un perfil de docente asociado.');
        }

        $request->validate([
            'enlace_google_drive' => 'nullable|url|max:500',
        ]);

        Materialtrabajo::updateOrCreate(
            ['docente_id' => $docente->id],
            ['enlace_google_drive' => $request->enlace_google_drive]
        );

        return redirect()->back()
            ->with('success', 'Material de trabajo actualizado exitosamente.');
    }

    protected function validateSesion(Request $request): array
    {
        return $request->validate([
            'curso_grado_sec_niv_anio_id' => 'required|exists:maya_curso_grado_sec_niv_anios,id',
            'titulo' => 'nullable|string|max:255',
            'plataforma' => 'nullable|string|in:meet,zoom,teams,otro',
            'enlace' => 'required|url|max:500',
            'enlace_material' => 'nullable|url|max:500',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'motivo' => 'required|string|max:1000',
            'observaciones' => 'nullable|string|max:2000',
            'estado' => 'required|in:1,0',
        ]);
    }

    protected function isGestor(): bool
    {
        $user = auth()->user();

        return $user->hasRole('admin') || $user->hasRole('director');
    }

    protected function currentDocente(): ?Docente
    {
        return Docente::where('user_id', auth()->id())->first();
    }

    protected function cursosDisponibles()
    {
        $query = Cursogradosecnivanio::with(['grado', 'materia', 'periodo'])
            ->whereHas('periodo', fn ($q) => $q
                ->where('estado', '1')
                ->where('tipo_periodo', 'año escolar'));

        if (! $this->isGestor()) {
            $docente = $this->currentDocente();

            if (! $docente) {
                return collect();
            }

            $query->where('docente_designado_id', $docente->id);
        }

        return $query->orderBy('materia_id')->orderBy('grado_id')->get();
    }

    protected function authorizeCurso(Cursogradosecnivanio $curso): void
    {
        if ($this->isGestor()) {
            return;
        }

        $docente = $this->currentDocente();

        if (! $docente || $curso->docente_designado_id !== $docente->id) {
            abort(403, 'No tienes permiso para gestionar clases en este curso.');
        }
    }

    protected function authorizeSesion(Aulavirtualsesion $sesion): void
    {
        if ($this->isGestor()) {
            return;
        }

        $docente = $this->currentDocente();

        if (! $docente || $sesion->docente_id !== $docente->id) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }
    }
}
