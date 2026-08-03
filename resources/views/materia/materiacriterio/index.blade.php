@extends('layouts.app')
@section('title', 'Criterios de Evaluación')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h2 fw-bold mb-1">
                <i class="bi bi-list-check me-2 text-primary"></i>Criterios de Evaluación
            </h1>
            <p class="text-muted mb-0">Gestión de criterios por materia, grado, competencia y bimestre</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('materiacompetencia.index') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="{{ route('materiacriterio.importar') }}" class="btn btn-success shadow-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Importar Excel
            </a>
            <a href="{{ route('materiacriterio.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Criterio
            </a>
            <button type="button" class="btn btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalClonarCriterio">
                <i class="bi bi-copy me-1"></i> Clonar Criterio
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h5 class="mb-0 fw-semibold text-secondary">
                <i class="bi bi-funnel me-2"></i>Filtros de búsqueda
            </h5>
        </div>
        <div class="card-body pt-0">
            <form method="GET" action="{{ route('materiacriterio.index') }}" id="filtrosForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-danger">
                            <i class="bi bi-calendar-event me-1"></i>Periodo (Año Escolar) *
                        </label>
                        <select name="periodo_id" id="periodo_id" class="form-select shadow-sm" required>
                            <option value="">Seleccione un periodo</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}" {{ request('periodo_id') == $periodo->id ? 'selected' : '' }}>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                    @if($periodo->estado == 1) <span class="text-success">✓</span> @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-columns-gap me-1"></i>Bimestre
                        </label>
                        <select name="periodo_bimestre_id" id="periodo_bimestre_id" class="form-select shadow-sm"
                                {{ !request('periodo_id') ? 'disabled' : '' }}>
                            <option value="">Todos los bimestres</option>
                            @foreach($periodosBimestres ?? [] as $bimestre)
                                <option value="{{ $bimestre->id }}" {{ request('periodo_bimestre_id') == $bimestre->id ? 'selected' : '' }}>
                                    {{ $bimestre->sigla }} - {{ $bimestre->bimestre }}° Bimestre
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-search me-1"></i>Buscador rápido
                        </label>
                        <input type="text" id="buscadorRapido" class="form-control shadow-sm"
                               placeholder="Buscar por criterio, competencia o materia...">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-mortarboard me-1"></i>Grado (Filtro rápido)
                        </label>
                        <select id="filtroGradoRapido" class="form-select shadow-sm">
                            <option value="">Todos los grados</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->nombreCompleto }}">{{ $grado->nombreCompleto }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-success flex-fill" id="btnFiltrar" {{ !request('periodo_id') ? 'disabled' : '' }}>
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        @if(request()->anyFilled(['materia_id', 'grado_id', 'periodo_id', 'periodo_bimestre_id']))
                            <a href="{{ route('materiacriterio.index') }}" class="btn btn-outline-secondary flex-fill">
                                <i class="bi bi-eraser me-1"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">

            @if(!request('periodo_id'))
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-calendar2-week display-1 text-warning"></i>
                        <h4 class="fw-bold text-secondary mt-3">Selecciona un período escolar</h4>
                        <p class="text-muted">Selecciona un período (año escolar) en el filtro superior para visualizar los criterios.</p>
                    </div>
                </div>

            @elseif($criteriosAgrupados->count() > 0)

                <div class="row mb-4">
                    <div class="col-md-3 mb-2">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-white-50 small">TOTAL CRITERIOS</span>
                                        <h2 class="mb-0 fw-bold">{{ $criteriosAgrupados->flatten()->count() }}</h2>
                                    </div>
                                    <i class="bi bi-list-check fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-white-50 small">MATERIAS</span>
                                        <h2 class="mb-0 fw-bold" id="totalMaterias">0</h2>
                                    </div>
                                    <i class="bi bi-book fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-white-50 small">COMPETENCIAS</span>
                                        <h2 class="mb-0 fw-bold" id="totalCompetencias">0</h2>
                                    </div>
                                    <i class="bi bi-star fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card border-0 shadow-sm bg-warning text-white">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-white-50 small">GRADOS</span>
                                        <h2 class="mb-0 fw-bold" id="totalGrados">0</h2>
                                    </div>
                                    <i class="bi bi-mortarboard fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Periodo:</strong> {{ $periodos->firstWhere('id', request('periodo_id'))?->nombre ?? 'N/A' }}
                            @if(request('periodo_bimestre_id'))
                                <span class="badge bg-info ms-2">Bimestre filtrado</span>
                            @endif
                        </div>
                        <div>
                            <i class="bi bi-cursor me-1"></i>
                            <small>Haz clic para expandir/colapsar</small>
                        </div>
                    </div>
                </div>

                <div id="contenedorCriterios">
                    @php
                        $materiasAgrupadas = [];
                        foreach($criteriosAgrupados as $competencia => $criterios) {
                            foreach($criterios as $criterio) {
                                $materiaNombre = $criterio->materia->nombre ?? 'Sin Materia';
                                $gradoNombre = $criterio->grado->nombreCompleto ?? 'Sin Grado';

                                if (!isset($materiasAgrupadas[$materiaNombre])) {
                                    $materiasAgrupadas[$materiaNombre] = [];
                                }
                                if (!isset($materiasAgrupadas[$materiaNombre][$gradoNombre])) {
                                    $materiasAgrupadas[$materiaNombre][$gradoNombre] = [];
                                }
                                if (!isset($materiasAgrupadas[$materiaNombre][$gradoNombre][$competencia])) {
                                    $materiasAgrupadas[$materiaNombre][$gradoNombre][$competencia] = [];
                                }
                                $materiasAgrupadas[$materiaNombre][$gradoNombre][$competencia][] = $criterio;
                            }
                        }
                    @endphp

                    @foreach($materiasAgrupadas as $materiaNombre => $gradosPorMateria)
                        @php
                            $materiaId = Str::slug($materiaNombre, '-') . '-' . $loop->index;
                            $totalCriteriosMateria = 0;
                            foreach($gradosPorMateria as $competencias) {
                                foreach($competencias as $criterios) {
                                    $totalCriteriosMateria += count($criterios);
                                }
                            }
                        @endphp

                        <div class="card border-0 shadow-sm mb-3 materia-card" data-materia="{{ $materiaNombre }}">
                            <div class="card-header bg-white py-3" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#materia{{ $materiaId }}" aria-expanded="false">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-journal-bookmark-fill text-primary fs-4 me-2"></i>
                                        <strong class="fs-5">{{ $materiaNombre }}</strong>
                                        <span class="badge bg-primary ms-2">{{ $totalCriteriosMateria }} criterio(s)</span>
                                    </div>
                                    <i class="bi bi-chevron-down fs-5"></i>
                                </div>
                            </div>

                            <div id="materia{{ $materiaId }}" class="collapse">
                                <div class="card-body p-0">
                                    @foreach($gradosPorMateria as $gradoNombre => $competencias)
                                        @php
                                            $gradoId = Str::slug($gradoNombre, '-') . '-' . $loop->parent->index . '-' . $loop->index;
                                            $totalCriteriosGrado = 0;
                                            foreach($competencias as $criterios) {
                                                $totalCriteriosGrado += count($criterios);
                                            }
                                        @endphp

                                        <div class="border-bottom grado-card" data-grado="{{ $gradoNombre }}">
                                            <div class="bg-light px-4 py-2" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#grado{{ $gradoId }}" aria-expanded="false">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="bi bi-mortarboard text-secondary me-2"></i>
                                                        <strong class="text-secondary">{{ $gradoNombre }}</strong>
                                                        <span class="badge bg-secondary ms-2">{{ $totalCriteriosGrado }} criterio(s)</span>
                                                    </div>
                                                    <i class="bi bi-chevron-down"></i>
                                                </div>
                                            </div>

                                            <div id="grado{{ $gradoId }}" class="collapse">
                                                <div class="p-0">
                                                    @foreach($competencias as $competenciaNombre => $criterios)
                                                        @php
                                                            $competenciaId = Str::slug($competenciaNombre, '-') . '-' . $loop->parent->index . '-' . $loop->index;
                                                            $rowColor = $criterios[0]->rowColor ?? '#4e73df';
                                                        @endphp

                                                        <div class="border-bottom competencia-card" data-competencia="{{ $competenciaNombre }}">
                                                            <div class="px-4 py-2" style="cursor: pointer; background-color: {{ $rowColor }}08;" data-bs-toggle="collapse" data-bs-target="#competencia{{ $competenciaId }}" aria-expanded="false">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <i class="bi bi-star-fill me-2" style="color: {{ $rowColor }};"></i>
                                                                        <strong>{{ $competenciaNombre }}</strong>
                                                                        <span class="badge bg-info ms-2">{{ count($criterios) }} criterio(s)</span>
                                                                    </div>
                                                                    <i class="bi bi-chevron-down"></i>
                                                                </div>
                                                            </div>

                                                            <div id="competencia{{ $competenciaId }}" class="collapse">
                                                                <div class="px-4 py-2">
                                                                    @foreach($criterios as $criterio)
                                                                        <div class="criterio-item border-bottom py-2"
                                                                             data-criterio-nombre="{{ strtolower($criterio->nombre) }}"
                                                                             data-criterio-descripcion="{{ strtolower($criterio->descripcion ?? '') }}">
                                                                            <div class="row align-items-center">
                                                                                <div class="col-md-7">
                                                                                    <h6 class="fw-semibold mb-1">{{ $criterio->nombre }}</h6>
                                                                                    @if($criterio->descripcion)
                                                                                        <p class="text-muted small mb-0">{{ $criterio->descripcion }}</p>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <div class="d-flex gap-2 flex-wrap">
                                                                                        <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                                                            <i class="bi bi-calendar me-1"></i>
                                                                                            {{ $criterio->periodoBimestre->periodo->nombre ?? 'N/A' }}
                                                                                        </span>
                                                                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                                                                            <i class="bi bi-collection me-1"></i>
                                                                                            {{ $criterio->periodoBimestre->sigla ?? 'N/A' }}
                                                                                        </span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-2 text-md-end mt-2 mt-md-0">
                                                                                    <div class="btn-group btn-group-sm">
                                                                                        <a href="{{ route('materiacriterio.edit', $criterio->id) }}"
                                                                                           class="btn btn-outline-primary"
                                                                                           title="Editar">
                                                                                            <i class="bi bi-pencil-square"></i>
                                                                                        </a>
                                                                                        <form action="{{ route('materiacriterio.destroy', $criterio->id) }}"
                                                                                              method="POST" class="d-inline">
                                                                                            @csrf
                                                                                            @method('DELETE')
                                                                                            <button type="submit"
                                                                                                    class="btn btn-outline-danger"
                                                                                                    title="Eliminar"
                                                                                                    onclick="return confirm('¿Estás seguro?')">
                                                                                                <i class="bi bi-trash"></i>
                                                                                            </button>
                                                                                        </form>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="fw-bold text-secondary mt-3">No hay criterios registrados</h4>
                        <p class="text-muted mb-3">
                            @if(request()->anyFilled(['materia_id', 'grado_id', 'periodo_bimestre_id']))
                                No se encontraron criterios con los filtros aplicados.
                            @else
                                No hay criterios registrados para el período seleccionado.
                            @endif
                        </p>
                        <a href="{{ route('materiacriterio.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Agregar criterios
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="modalClonarCriterio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-copy me-2 text-primary"></i>Clonar Criterios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <i class="bi bi-info-circle text-primary me-2"></i>
                    Selecciona el período de origen y los períodos de destino. Los criterios se clonarán respetando la equivalencia de bimestres.
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i>Modo de clonación
                        </label>
                        <div class="border rounded p-3 bg-light">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modo_clonar" id="modoPeriodo" value="periodo" checked>
                                <label class="form-check-label" for="modoPeriodo">Por período</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modo_clonar" id="modoGrado" value="grado">
                                <label class="form-check-label" for="modoGrado">Por grado</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar2-arrow-up me-1"></i>Período de origen *
                        </label>
                        <select id="clonarPeriodoOrigen" class="form-select shadow-sm">
                            <option value="">Seleccione...</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }} ({{ $periodo->anio }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" id="columnaGradoOrigen" style="display: none;">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-mortarboard me-1"></i>Grado de origen *
                        </label>
                        <select id="clonarGradoOrigen" class="form-select shadow-sm">
                            <option value="">Todos los grados</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}">{{ $grado->nombreCompleto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" id="columnaGradosDestino" style="display: none;">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-mortarboard me-1"></i>Grados de destino * (multi)
                        </label>
                        <select id="clonarGradosDestino" class="form-select shadow-sm" multiple size="5">
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}">{{ $grado->nombreCompleto }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Mantén Ctrl para elegir varios</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar2-arrow-down me-1"></i>Períodos de destino * (multi)
                        </label>
                        <select id="clonarPeriodosDestino" class="form-select shadow-sm" multiple size="5">
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }} ({{ $periodo->anio }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Mantén Ctrl para elegir varios</small>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-book me-1"></i>Filtrar por materia
                        </label>
                        <select id="clonarFiltroMateria" class="form-select form-select-sm shadow-sm">
                            <option value="">Todos las materias</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-star me-1"></i>Filtrar por competencia
                        </label>
                        <select id="clonarFiltroCompetencia" class="form-select form-select-sm shadow-sm" disabled>
                            <option value="">Todas las competencias</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-collection me-1"></i>Filtrar por bimestre
                        </label>
                        <select id="clonarFiltroBimestre" class="form-select form-select-sm shadow-sm">
                            <option value="">Todos los bimestres</option>
                        </select>
                    </div>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="clonarSeleccionarTodos" checked>
                    <label class="form-check-label fw-semibold" for="clonarSeleccionarTodos">
                        <i class="bi bi-check2-square me-1"></i>Seleccionar todos los criterios
                    </label>
                </div>

                <div id="arbolCriteriosOrigen" class="border rounded p-3 bg-white" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-muted text-center my-5">
                        <i class="bi bi-arrow-left me-2"></i>Selecciona el período de origen para cargar los criterios
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnClonarCriterios" disabled>
                    <i class="bi bi-copy me-1"></i> Continuar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarDuplicados" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Se encontraron duplicados
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Algunos criterios ya existen en los períodos de destino.</p>
                <p class="mb-0"><strong id="textoDuplicados"></strong></p>
                <hr>
                <p class="mb-0 text-muted">¿Cómo deseas continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnSinDuplicar">
                    <i class="bi bi-check-circle me-1"></i> Continuar sin duplicar
                </button>
                <button type="button" class="btn btn-warning" id="btnConDuplicados">
                    <i class="bi bi-exclamation-triangle me-1"></i> Proceder con duplicados
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodoSelect = document.getElementById('periodo_id');
    const bimestreSelect = document.getElementById('periodo_bimestre_id');
    const btnFiltrar = document.getElementById('btnFiltrar');
    const form = document.getElementById('filtrosForm');
    const buscadorRapido = document.getElementById('buscadorRapido');
    const filtroGradoRapido = document.getElementById('filtroGradoRapido');
    const contenedor = document.getElementById('contenedorCriterios');

    function actualizarContadores() {
        if (!contenedor) return;

        const materias = document.querySelectorAll('.materia-card');
        const materiasVisibles = Array.from(materias).filter(m => m.style.display !== 'none');
        document.getElementById('totalMaterias').innerText = materiasVisibles.length;

        const competencias = document.querySelectorAll('.competencia-card');
        const competenciasVisibles = Array.from(competencias).filter(c => {
            const parentMateria = c.closest('.materia-card');
            return parentMateria && parentMateria.style.display !== 'none';
        });
        document.getElementById('totalCompetencias').innerText = competenciasVisibles.length;

        const grados = document.querySelectorAll('.grado-card');
        const gradosVisibles = Array.from(grados).filter(g => {
            const parentMateria = g.closest('.materia-card');
            return parentMateria && parentMateria.style.display !== 'none';
        });
        document.getElementById('totalGrados').innerText = gradosVisibles.length;
    }

    function realizarBusqueda() {
        const busqueda = buscadorRapido.value.toLowerCase().trim();
        const gradoFiltro = filtroGradoRapido.value;

        const materias = document.querySelectorAll('.materia-card');

        materias.forEach(materia => {
            const materiaNombre = materia.getAttribute('data-materia')?.toLowerCase() || '';
            let materiaVisible = false;

            const grados = materia.querySelectorAll('.grado-card');
            grados.forEach(grado => {
                const gradoNombre = grado.getAttribute('data-grado') || '';
                let gradoVisible = true;

                if (gradoFiltro && gradoNombre !== gradoFiltro) {
                    gradoVisible = false;
                }

                const competencias = grado.querySelectorAll('.competencia-card');
                let gradoTieneCoincidencia = false;

                competencias.forEach(competencia => {
                    const competenciaNombre = competencia.getAttribute('data-competencia')?.toLowerCase() || '';
                    const criterios = competencia.querySelectorAll('.criterio-item');
                    let competenciaVisible = false;

                    criterios.forEach(criterio => {
                        const criterioNombre = criterio.getAttribute('data-criterio-nombre') || '';
                        const criterioDescripcion = criterio.getAttribute('data-criterio-descripcion') || '';

                        const coincide = busqueda === '' ||
                                        materiaNombre.includes(busqueda) ||
                                        competenciaNombre.includes(busqueda) ||
                                        criterioNombre.includes(busqueda) ||
                                        criterioDescripcion.includes(busqueda);

                        if (coincide) {
                            competenciaVisible = true;
                            gradoTieneCoincidencia = true;
                            materiaVisible = true;
                        }

                        criterio.style.display = coincide ? '' : 'none';
                    });

                    competencia.style.display = competenciaVisible ? '' : 'none';
                });

                const tieneCompetenciasVisibles = Array.from(competencias).some(c => c.style.display !== 'none');
                grado.style.display = (gradoVisible && (tieneCompetenciasVisibles || busqueda === '')) ? '' : 'none';
            });

            const tieneGradosVisibles = Array.from(grados).some(g => g.style.display !== 'none');
            materia.style.display = (tieneGradosVisibles || materiaVisible) ? '' : 'none';
        });

        actualizarContadores();
    }

    if (buscadorRapido) {
        buscadorRapido.addEventListener('keyup', realizarBusqueda);
    }
    if (filtroGradoRapido) {
        filtroGradoRapido.addEventListener('change', realizarBusqueda);
    }

    function aplicarFiltros() {
        if (!periodoSelect.value) {
            alert('Por favor, seleccione un período');
            return;
        }

        const bimestreId = bimestreSelect.value;
        let url = form.action + '?periodo_id=' + periodoSelect.value;
        if (bimestreId) url += '&periodo_bimestre_id=' + bimestreId;

        window.location.href = url;
    }

    if (periodoSelect) {
        periodoSelect.addEventListener('change', function() {
            if (this.value) {
                bimestreSelect.value = '';
                aplicarFiltros();
            } else {
                bimestreSelect.disabled = true;
                if (btnFiltrar) btnFiltrar.disabled = true;
            }
        });
    }

    if (periodoSelect && periodoSelect.value) {
        if (bimestreSelect) bimestreSelect.disabled = false;
        if (btnFiltrar) btnFiltrar.disabled = false;
    }

    actualizarContadores();

    // Cambiar el ícono del chevron cuando se expande/colapsa
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
        button.addEventListener('click', function() {
            const chevron = this.querySelector('.bi-chevron-down, .bi-chevron-up');
            if (chevron) {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                if (isExpanded) {
                    chevron.classList.remove('bi-chevron-down');
                    chevron.classList.add('bi-chevron-up');
                } else {
                    chevron.classList.remove('bi-chevron-up');
                    chevron.classList.add('bi-chevron-down');
                }
            }
        });
    });

    // --- Clonar Criterios ---
    const clonarPeriodoOrigen = document.getElementById('clonarPeriodoOrigen');
    const clonarGradoOrigen = document.getElementById('clonarGradoOrigen');
    const clonarPeriodosDestino = document.getElementById('clonarPeriodosDestino');
    const clonarGradosDestino = document.getElementById('clonarGradosDestino');
    const columnaGradoOrigen = document.getElementById('columnaGradoOrigen');
    const columnaGradosDestino = document.getElementById('columnaGradosDestino');
    const arbolCriteriosOrigen = document.getElementById('arbolCriteriosOrigen');
    const btnClonarCriterios = document.getElementById('btnClonarCriterios');
    const clonarSeleccionarTodos = document.getElementById('clonarSeleccionarTodos');
    const csrfToken = '{{ csrf_token() }}';

    let criteriosSeleccionados = [];
    let arbolData = {};

    function modoClonarActual() {
        return document.querySelector('input[name="modo_clonar"]:checked')?.value || 'periodo';
    }

    function refrescarEstadoClonar() {
        const modo = modoClonarActual();
        const valido = clonarPeriodoOrigen.value
            && clonarPeriodosDestino.selectedOptions.length > 0
            && (modo === 'periodo' || clonarGradosDestino.selectedOptions.length > 0)
            && criteriosSeleccionados.length > 0;
        btnClonarCriterios.disabled = !valido;
        columnaGradoOrigen.style.display = modo === 'grado' ? '' : 'none';
        columnaGradosDestino.style.display = modo === 'grado' ? '' : 'none';
    }

    async function cargarArbolOrigen() {
        const periodoId = clonarPeriodoOrigen.value;
        arbolCriteriosOrigen.innerHTML = '<p class="text-muted text-center my-4">Cargando criterios...</p>';
        criteriosSeleccionados = [];
        clonarSeleccionarTodos.checked = true;

        if (!periodoId) {
            arbolCriteriosOrigen.innerHTML = '<p class="text-muted text-center my-5">Selecciona el período de origen</p>';
            refrescarEstadoClonar();
            return;
        }

        try {
            let url = '{{ route("materiacriterio.origen", ["periodo_id" => "PERIODO_ID"]) }}'.replace('PERIODO_ID', periodoId);

            const modo = modoClonarActual();
            if (modo === 'grado' && clonarGradoOrigen.value) {
                url += '/' + clonarGradoOrigen.value;
            }

            const res = await fetch(url);
            const data = await res.json();

            if (data.error) {
                arbolCriteriosOrigen.innerHTML = '<p class="text-danger text-center my-4">' + data.error + '</p>';
                refrescarEstadoClonar();
                return;
            }

            arbolData = data.arbol || {};
            const total = Object.values(arbolData).reduce((acc, comps) =>
                acc + Object.values(comps).reduce((acc2, crits) => acc2 + crits.length, 0), 0);

            if (total === 0) {
                arbolCriteriosOrigen.innerHTML = '<p class="text-muted text-center my-4">No hay criterios en el período de origen.</p>';
                refrescarEstadoClonar();
                return;
            }

            poblarFiltros();
            renderizarArbol();
            refrescarEstadoClonar();
        } catch (e) {
            arbolCriteriosOrigen.innerHTML = '<p class="text-danger text-center my-4">Error al cargar los criterios.</p>';
            refrescarEstadoClonar();
        }
    }

    function poblarFiltros() {
        const filtroMateria = document.getElementById('clonarFiltroMateria');
        const filtroCompetencia = document.getElementById('clonarFiltroCompetencia');
        const filtroBimestre = document.getElementById('clonarFiltroBimestre');

        filtroMateria.innerHTML = '<option value="">Todos las materias</option>';
        filtroCompetencia.innerHTML = '<option value="">Todas las competencias</option>';
        filtroBimestre.innerHTML = '<option value="">Todos los bimestres</option>';

        const materias = new Set();
        const bimestres = new Set();
        const materiasAprobadas = new Map(); // materia_id => nombre

        for (const [materiaNombre, competencias] of Object.entries(arbolData)) {
            materias.add(materiaNombre);
            for (const [compNombre, criterios] of Object.entries(competencias)) {
                criterios.forEach(c => {
                    if (c.materia_id) materiasAprobadas.set(String(c.materia_id), materiaNombre);
                    if (c.bimestre) bimestres.add(c.bimestre);
                });
            }
        }

        materias.forEach(nombre => {
            const opt = document.createElement('option');
            opt.value = nombre;
            opt.textContent = nombre;
            filtroMateria.appendChild(opt);
        });

        bimestres.forEach(sigla => {
            const opt = document.createElement('option');
            opt.value = sigla;
            opt.textContent = sigla;
            filtroBimestre.appendChild(opt);
        });

        filtroCompetencia.disabled = true;
        filtroMateria.value = '';
        filtroBimestre.value = '';
    }

    function poblarCompetencias() {
        const filtroMateria = document.getElementById('clonarFiltroMateria');
        const filtroCompetencia = document.getElementById('clonarFiltroCompetencia');
        const materiaSeleccionada = filtroMateria.value;

        filtroCompetencia.innerHTML = '<option value="">Todas las competencias</option>';

        if (!materiaSeleccionada) {
            filtroCompetencia.disabled = true;
            return;
        }

        const competencias = Object.keys(arbolData[materiaSeleccionada] || {});
        competencias.forEach(nombre => {
            const opt = document.createElement('option');
            opt.value = nombre;
            opt.textContent = nombre;
            filtroCompetencia.appendChild(opt);
        });
        filtroCompetencia.disabled = competencias.length === 0;
    }

    function renderizarArbol() {
        const filtroMateria = document.getElementById('clonarFiltroMateria');
        const filtroCompetencia = document.getElementById('clonarFiltroCompetencia');
        const filtroBimestre = document.getElementById('clonarFiltroBimestre');

        const fMateria = filtroMateria.value;
        const fCompetencia = filtroCompetencia.value;
        const fBimestre = filtroBimestre.value;

        let html = '<div class="fw-bold mb-2">Criterios de origen</div>';
        let index = 0;

        for (const [materiaNombre, competencias] of Object.entries(arbolData)) {
            if (fMateria && materiaNombre !== fMateria) continue;

            html += '<div class="border rounded mb-2">'
                + '<div class="bg-light px-3 py-2 fw-semibold d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#clonMateria' + index + '">'
                + '<span><i class="bi bi-journal-bookmark-fill text-primary me-2"></i>' + materiaNombre + '</span>'
                + '<span class="form-check form-switch m-0"><input class="form-check-input clonar-selectall-materia" type="checkbox" data-materia="' + materiaNombre + '" checked></span>'
                + '</div>'
                + '<div class="collapse show clonMateriaBody" id="clonMateria' + index + '" data-materia="' + materiaNombre + '">';

            for (const [competenciaNombre, criteriosArr] of Object.entries(competencias)) {
                if (fCompetencia && competenciaNombre !== fCompetencia) continue;

                const critsFiltrados = criteriosArr.filter(c => !fBimestre || c.bimestre === fBimestre);
                if (critsFiltrados.length === 0) continue;

                html += '<div class="px-3 py-1 competencia-nodo" data-materia="' + materiaNombre + '" data-competencia="' + competenciaNombre + '">'
                    + '<div class="fw-semibold text-secondary small py-1 d-flex justify-content-between align-items-center">'
                    + '<span><i class="bi bi-star-fill me-1"></i>' + competenciaNombre + '</span>'
                    + '<span class="form-check form-switch m-0"><input class="form-check-input clonar-selectall-competencia" type="checkbox" data-materia="' + materiaNombre + '" data-competencia="' + competenciaNombre + '" checked></span>'
                    + '</div>'
                    + '<div class="ps-3">';

                critsFiltrados.forEach(criterio => {
                    html += '<div class="form-check mb-1">'
                        + '<input class="form-check-input criterio-origen-check" type="checkbox" value="' + criterio.id + '" data-nombre="' + (criterio.nombre || '') + '" checked>'
                        + '<label class="form-check-label">' + criterio.nombre
                        + ' <span class="badge bg-secondary">' + criterio.grado + '</span>'
                        + ' <span class="badge bg-warning text-dark">' + criterio.bimestre + '</span>'
                        + '</label></div>';
                });

                html += '</div></div>';
            }

            html += '</div></div>';
            index++;
        }

        arbolCriteriosOrigen.innerHTML = html;

        arbolCriteriosOrigen.querySelectorAll('.criterio-origen-check').forEach(cb => {
            cb.addEventListener('change', recogerCriteriosSeleccionados);
        });
        arbolCriteriosOrigen.querySelectorAll('.clonar-selectall-materia').forEach(cb => {
            cb.addEventListener('change', function() {
                const materia = cb.getAttribute('data-materia');
                arbolCriteriosOrigen.querySelectorAll('.competencia-nodo').forEach(nodo => {
                    if (nodo.getAttribute('data-materia') === materia) {
                        const box = nodo.querySelector('.clonar-selectall-competencia');
                        if (box) box.checked = cb.checked;
                        nodo.querySelectorAll('.criterio-origen-check').forEach(c => c.checked = cb.checked);
                    }
                });
                recogerCriteriosSeleccionados();
            });
        });
        arbolCriteriosOrigen.querySelectorAll('.clonar-selectall-competencia').forEach(cb => {
            cb.addEventListener('change', function() {
                const materia = cb.getAttribute('data-materia');
                const competencia = cb.getAttribute('data-competencia');
                arbolCriteriosOrigen.querySelectorAll('.competencia-nodo').forEach(nodo => {
                    if (nodo.getAttribute('data-materia') === materia && nodo.getAttribute('data-competencia') === competencia) {
                        nodo.querySelectorAll('.criterio-origen-check').forEach(c => c.checked = cb.checked);
                    }
                });
                recogerCriteriosSeleccionados();
            });
        });
        arbolCriteriosOrigen.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const chevron = this.querySelector('.bi-chevron-down, .bi-chevron-up');
                if (chevron) {
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    chevron.classList.remove(isExpanded ? 'bi-chevron-up' : 'bi-chevron-down');
                    chevron.classList.add(isExpanded ? 'bi-chevron-down' : 'bi-chevron-up');
                }
            });
        });

        if (clonarSeleccionarTodos) clonarSeleccionarTodos.checked = true;
        recogerCriteriosSeleccionados();
    }

    function recogerCriteriosSeleccionados() {
        criteriosSeleccionados = Array.from(document.querySelectorAll('.criterio-origen-check:checked')).map(cb => cb.value);
        const totalCbs = document.querySelectorAll('.criterio-origen-check').length;
        const marcados = document.querySelectorAll('.criterio-origen-check:checked').length;
        if (clonarSeleccionarTodos) {
            clonarSeleccionarTodos.checked = totalCbs > 0 && marcados === totalCbs;
            clonarSeleccionarTodos.indeterminate = marcados > 0 && marcados < totalCbs;
        }
        refrescarEstadoClonar();
    }

    if (clonarSeleccionarTodos) {
        clonarSeleccionarTodos.addEventListener('change', function() {
            document.querySelectorAll('.criterio-origen-check').forEach(cb => {
                cb.checked = clonarSeleccionarTodos.checked;
            });
            recogerCriteriosSeleccionados();
        });
    }

    if (clonarPeriodoOrigen) {
        clonarPeriodoOrigen.addEventListener('change', cargarArbolOrigen);
    }
    if (clonarGradoOrigen) {
        clonarGradoOrigen.addEventListener('change', cargarArbolOrigen);
    }
    document.querySelectorAll('input[name="modo_clonar"]').forEach(radio => {
        radio.addEventListener('change', function() {
            refrescarEstadoClonar();
            cargarArbolOrigen();
        });
    });
    if (clonarPeriodosDestino) {
        clonarPeriodosDestino.addEventListener('change', refrescarEstadoClonar);
    }
    if (clonarGradosDestino) {
        clonarGradosDestino.addEventListener('change', refrescarEstadoClonar);
    }

    const filtroMateria = document.getElementById('clonarFiltroMateria');
    const filtroCompetencia = document.getElementById('clonarFiltroCompetencia');
    const filtroBimestre = document.getElementById('clonarFiltroBimestre');

    if (filtroMateria) {
        filtroMateria.addEventListener('change', function() {
            poblarCompetencias();
            renderizarArbol();
        });
    }
    if (filtroCompetencia) {
        filtroCompetencia.addEventListener('change', renderizarArbol);
    }
    if (filtroBimestre) {
        filtroBimestre.addEventListener('change', renderizarArbol);
    }

    async function ejecutarClonacion(modo) {
        const payload = {
            _token: csrfToken,
            modo: modo,
            periodo_origen_id: clonarPeriodoOrigen.value,
            criterio_ids: criteriosSeleccionados,
            periodo_destino_ids: Array.from(clonarPeriodosDestino.selectedOptions).map(o => o.value),
        };

        if (modoClonarActual() === 'grado' && clonarGradosDestino.selectedOptions.length > 0) {
            payload.grado_destino_ids = Array.from(clonarGradosDestino.selectedOptions).map(o => o.value);
        }

        try {
            const res = await fetch('{{ route("materiacriterio.clonar") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (!res.ok) {
                alert(data.error || 'Error al clonar criterios.');
                return;
            }

            if (data.fase === 1) {
                const sinDuplicados = (data.duplicados || 0) === 0;
                if (sinDuplicados) {
                    await ejecutarClonacion('sin_duplicados');
                } else {
                    document.getElementById('textoDuplicados').innerText =
                        data.nuevos + ' criterio(s) nuevo(s) y ' + data.duplicados + ' criterio(s) duplicado(s).';
                    const modalDuplicados = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarDuplicados'));
                    modalDuplicados.show();
                    window.__clonarModo = null;
                }
                return;
            }

            const msg = 'Clonación completada: ' + (data.creados || 0) + ' criterio(s) creado(s), '
                + (data.omitidos || 0) + ' omitido(s).';
            alert(msg);
            bootstrap.Modal.getInstance(document.getElementById('modalClonarCriterio'))?.hide();
            window.location.reload();
        } catch (e) {
            alert('Error de conexión al clonar criterios.');
        }
    }

    if (btnClonarCriterios) {
        btnClonarCriterios.addEventListener('click', function() {
            ejecutarClonacion(null);
        });
    }

    const btnSinDuplicar = document.getElementById('btnSinDuplicar');
    const btnConDuplicados = document.getElementById('btnConDuplicados');
    if (btnSinDuplicar) {
        btnSinDuplicar.addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmarDuplicados'))?.hide();
            ejecutarClonacion('sin_duplicados');
        });
    }
    if (btnConDuplicados) {
        btnConDuplicados.addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmarDuplicados'))?.hide();
            ejecutarClonacion('con_duplicados');
        });
    }

    refrescarEstadoClonar();
});
</script>

<style>
.hover-shadow {
    transition: background-color 0.2s ease;
}
.hover-shadow:hover {
    background-color: #f8f9fa;
}
.card-header {
    border-bottom: none;
    transition: background-color 0.2s ease;
}
.card-header:hover {
    background-color: #f8f9fa !important;
}
.grado-card [data-bs-toggle="collapse"]:hover {
    background-color: #e9ecef !important;
}
.criterio-item:last-child {
    border-bottom: none !important;
}
[data-bs-toggle="collapse"] {
    cursor: pointer;
    transition: background-color 0.2s ease;
}
[data-bs-toggle="collapse"]:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>
@endsection
