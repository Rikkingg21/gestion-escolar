@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-list-check me-2 text-primary"></i>Listado de Criterios de Evaluación
            </h2>
            <p class="text-muted mb-0">Gestión global de todos los criterios</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('materiacriterio.importar') }}" class="btn btn-success shadow-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Importar Excel
            </a>
            <a href="" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Criterio
            </a>
        </div>
    </div>

    {{-- FILTROS AVANZADOS --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom-0">
            <h5 class="mb-0 fw-semibold text-secondary">
                <i class="bi bi-funnel me-2"></i>Filtros de búsqueda
            </h5>
        </div>
        <div class="card-body pt-0">
            <form method="GET" action="{{ route('materiacriterio.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="materia_id" class="form-label fw-semibold">Materia</label>
                        <select id="materia_id" name="materia_id" class="form-select shadow-sm">
                            <option value="">Todas las materias</option>
                            @foreach($materias as $materia)
                                <option value="{{ $materia->id }}" {{ request('materia_id') == $materia->id ? 'selected' : '' }}>
                                    {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="grado_id" class="form-label fw-semibold">Grado</label>
                        <select id="grado_id" name="grado_id" class="form-select shadow-sm">
                            <option value="">Todos los grados</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->nombreCompleto }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="anio" class="form-label fw-semibold">Año</label>
                        <select id="anio" name="anio" class="form-select shadow-sm">
                            <option value="">Todos los años</option>
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}" {{ request('anio') == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="bimestre" class="form-label fw-semibold">Bimestre</label>
                        <select id="bimestre" name="bimestre" class="form-select shadow-sm">
                            <option value="">Todos</option>
                            @foreach($bimestres as $bimestre)
                                <option value="{{ $bimestre }}" {{ request('bimestre') == $bimestre ? 'selected' : '' }}>
                                    Bimestre {{ $bimestre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-success shadow-sm flex-fill">
                            <i class="bi bi-funnel-fill me-1"></i> Filtrar
                        </button>
                        @if(request()->anyFilled(['materia_id', 'grado_id', 'anio', 'bimestre']))
                            <a href="{{ route('materiacriterio.index') }}" class="btn btn-outline-secondary flex-fill shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ALERTA DE ÉXITO --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ESTADÍSTICAS --}}
    @if(request()->anyFilled(['materia_id', 'grado_id', 'anio', 'bimestre']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Filtros aplicados:</strong>
                        @if(request('materia_id'))
                            <span class="badge bg-primary ms-2">Materia: {{ $materias->where('id', request('materia_id'))->first()->nombre ?? 'N/A' }}</span>
                        @endif
                        @if(request('grado_id'))
                            <span class="badge bg-secondary ms-2">Grado: {{ $grados->where('id', request('grado_id'))->first()->nombreCompleto ?? 'N/A' }}</span>
                        @endif
                        @if(request('anio'))
                            <span class="badge bg-info ms-2">Año: {{ request('anio') }}</span>
                        @endif
                        @if(request('bimestre'))
                            <span class="badge bg-warning ms-2">Bimestre: {{ request('bimestre') }}</span>
                        @endif
                    </div>
                    <small class="text-muted">
                        {{ $criteriosAgrupados->flatten()->count() }} criterio(s) encontrado(s)
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TABLA ORGANIZADA POR COMPETENCIAS Y GRADOS --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @forelse($criteriosAgrupados as $competencia => $criterios)
                {{-- TARJETA DE COMPETENCIA --}}
                <div class="competencia-card border-bottom">
                    <div class="competencia-header px-4 py-3" style="background-color: {{ $criterios->first()->rowColor }}22; border-left: 4px solid {{ $criterios->first()->rowColor }};">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">
                                    <i class="bi bi-star-fill me-2" style="color: {{ $criterios->first()->rowColor }};"></i>
                                    {{ $competencia }}
                                </h5>
                                <small class="text-muted">
                                    {{ $criterios->count() }} criterio(s) -
                                    Materia: <strong>{{ $criterios->first()->materia->nombre ?? 'N/A' }}</strong>
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary">{{ $criterios->first()->materia->nombre ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- AGRUPAR CRITERIOS POR GRADO --}}
                    @php
                        $criteriosPorGrado = $criterios->groupBy(function($criterio) {
                            return $criterio->grado->nombreCompleto ?? 'Sin Grado';
                        });
                    @endphp

                    @foreach($criteriosPorGrado as $gradoNombre => $criteriosGrado)
                        <div class="grado-section">
                            <div class="grado-header px-4 py-2 bg-light">
                                <h6 class="mb-0 fw-semibold text-secondary">
                                    <i class="bi bi-mortarboard me-2"></i>
                                    Grado: {{ $gradoNombre }}
                                </h6>
                            </div>

                            <div class="criterios-list">
                                @foreach($criteriosGrado as $criterio)
                                    <div class="criterio-item px-4 py-3 border-bottom">
                                        <div class="row align-items-center">
                                            <div class="col-md-5">
                                                <h6 class="fw-semibold mb-1 text-dark">{{ $criterio->nombre }}</h6>
                                                @if($criterio->descripcion)
                                                    <p class="text-muted small mb-0">{{ $criterio->descripcion }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                        <i class="bi bi-calendar me-1"></i>Año {{ $criterio->anio }}
                                                    </span>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                                        <i class="bi bi-collection me-1"></i>Bimestre {{ $criterio->bimestre }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('materiacriterio.edit', $criterio->id) }}"
                                                       class="btn btn-outline-primary rounded-2"
                                                       title="Editar">
                                                        <i class="bi bi-pencil-square"></i> Editar
                                                    </a>
                                                    <form action="{{ route('materiacriterio.destroy', $criterio->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-outline-danger rounded-2"
                                                                title="Eliminar"
                                                                onclick="return confirm('¿Estás seguro de eliminar este criterio?')">
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
                    @endforeach
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-list-check display-4 d-block mb-3"></i>
                    <h5>No hay criterios registrados</h5>
                    <p class="mb-0">
                        @if(request()->anyFilled(['materia_id', 'grado_id', 'anio', 'bimestre']))
                            No se encontraron criterios con los filtros aplicados.
                        @else
                            Comienza agregando nuevos criterios o importa desde Excel.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<style>
.competencia-card {
    background: white;
}

.competencia-card:last-child {
    border-bottom: none !important;
}

.competencia-header {
    border-bottom: 1px solid #e9ecef;
}

.grado-section {
    border-left: 3px solid #dee2e6;
    margin-left: 1rem;
}

.grado-header {
    border-bottom: 1px solid #f8f9fa;
    font-size: 0.9rem;
}

.criterio-item {
    transition: background-color 0.2s ease;
}

.criterio-item:hover {
    background-color: #f8f9fa;
}

.criterio-item:last-child {
    border-bottom: none !important;
}

.badge {
    font-size: 0.75em;
    padding: 0.5em 0.75em;
}

.btn-group .btn {
    border-radius: 6px !important;
    margin: 0 2px;
}

/* Responsive */
@media (max-width: 768px) {
    .grado-section {
        margin-left: 0.5rem;
    }

    .criterio-item .row > div {
        margin-bottom: 0.5rem;
    }

    .criterio-item .text-end {
        text-align: left !important;
    }
}
</style>
@endsection
