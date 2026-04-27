@extends('layouts.app')
@section('title', 'Criterios de Evaluación')

@section('content')
<div class="container py-4">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-list-check me-2 text-primary"></i>Listado de Criterios de Evaluación
            </h2>
            <p class="text-muted mb-0">Gestión global de todos los criterios</p>
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
            <a href="{{ route('materiacriterio.importarPeriodoAnterior') }}" class="btn btn-info shadow-sm">
                <i class="bi bi-arrow-down-circle me-1"></i> Importar desde otro Periodo
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
            <form method="GET" action="{{ route('materiacriterio.index') }}" id="filtrosForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="periodo_id" class="form-label fw-semibold text-danger">
                            Periodo (Año Escolar) *
                        </label>
                        <select id="periodo_id" name="periodo_id" class="form-select shadow-sm" required>
                            <option value="">Seleccione un periodo</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}" {{ request('periodo_id') == $periodo->id ? 'selected' : '' }}>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="periodo_bimestre_id" class="form-label fw-semibold">Bimestre (Opcional)</label>
                        <select id="periodo_bimestre_id" name="periodo_bimestre_id" class="form-select shadow-sm"
                                {{ !request('periodo_id') ? 'disabled' : '' }}>
                            <option value="">Todos los bimestres</option>
                            @foreach($periodosBimestres ?? [] as $bimestre)
                                <option value="{{ $bimestre->id }}" {{ request('periodo_bimestre_id') == $bimestre->id ? 'selected' : '' }}>
                                    {{ $bimestre->sigla }} - Bimestre {{ $bimestre->bimestre }}
                                    ({{ \Carbon\Carbon::parse($bimestre->fecha_inicio)->format('d/m') }} -
                                    {{ \Carbon\Carbon::parse($bimestre->fecha_fin)->format('d/m') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="materia_id" class="form-label fw-semibold">Materia (Opcional)</label>
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
                        <label for="grado_id" class="form-label fw-semibold">Grado (Opcional)</label>
                        <select id="grado_id" name="grado_id" class="form-select shadow-sm">
                            <option value="">Todos los grados</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->nombreCompleto }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-success shadow-sm flex-fill" {{ !request('periodo_id') ? 'disabled' : '' }}>
                            <i class="bi bi-funnel-fill me-1"></i> Filtrar
                        </button>
                        @if(request()->anyFilled(['materia_id', 'grado_id', 'periodo_id', 'periodo_bimestre_id']))
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

    {{-- CONTENIDO PRINCIPAL --}}
    <div class="row">
        <div class="col-12">
            @if(!request('periodo_id'))
                {{-- Mensaje para seleccionar período --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-calendar-event display-1 text-warning mb-3 d-block"></i>
                        <h4 class="fw-bold text-secondary">Selecciona un período escolar</h4>
                        <p class="text-muted">Por favor, selecciona un período (año escolar) en el filtro superior para visualizar los criterios de evaluación.</p>
                    </div>
                </div>
            @elseif($criteriosAgrupados->count() > 0)
                {{-- Contador total --}}
                <div class="alert alert-info mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Total encontrado:</strong> {{ $criteriosAgrupados->flatten()->count() }} criterio(s)
                            @if(request('periodo_bimestre_id'))
                                <span class="badge bg-info ms-2">Bimestre filtrado</span>
                            @endif
                            @if(request('materia_id'))
                                <span class="badge bg-primary ms-2">Materia filtrada</span>
                            @endif
                            @if(request('grado_id'))
                                <span class="badge bg-secondary ms-2">Grado filtrado</span>
                            @endif
                        </div>
                        <div>
                            <i class="bi bi-chevron-expand me-1"></i>
                            <small>Haz clic en cada competencia o grado para expandir/colapsar</small>
                        </div>
                    </div>
                </div>

                {{-- ACORDEONES POR COMPETENCIA --}}
                <div class="accordion" id="accordionCompetencias">
                    @foreach($criteriosAgrupados as $competencia => $criterios)
                        @php
                            $competenciaId = Str::slug($competencia, '-') . '-' . $loop->index;
                            $totalCriterios = $criterios->count();
                            $materiaNombre = $criterios->first()->materia->nombre ?? 'N/A';
                            $rowColor = $criterios->first()->rowColor;
                        @endphp

                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h2 class="accordion-header" id="heading{{ $competenciaId }}">
                                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse{{ $competenciaId }}"
                                        style="background-color: {{ $rowColor }}0d; border-left: 4px solid {{ $rowColor }};">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div>
                                            <i class="bi bi-star-fill me-2" style="color: {{ $rowColor }};"></i>
                                            <strong class="fs-5 text-dark">{{ $competencia }}</strong>
                                            <span class="badge bg-secondary ms-2">{{ $materiaNombre }}</span>
                                        </div>
                                        <div>
                                            <span class="badge bg-primary rounded-pill">
                                                <i class="bi bi-list-check me-1"></i>{{ $totalCriterios }} criterio(s)
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </h2>

                            <div id="collapse{{ $competenciaId }}"
                                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                 data-bs-parent="#accordionCompetencias">
                                <div class="accordion-body p-0">
                                    {{-- ACORDEONES POR GRADO dentro de cada competencia --}}
                                    @php
                                        $criteriosPorGrado = $criterios->groupBy(function($criterio) {
                                            return $criterio->grado->nombreCompleto ?? 'Sin Grado';
                                        });
                                    @endphp

                                    {{-- Usar IDs únicos para cada acordeón de grado --}}
                                    @foreach($criteriosPorGrado as $gradoNombre => $criteriosGrado)
                                        @php
                                            $gradoUniqueId = uniqid('grado-');
                                            $totalCriteriosGrado = $criteriosGrado->count();
                                        @endphp

                                        <div class="border-bottom">
                                            {{-- Botón tipo "collapse" sin acordeón anidado --}}
                                            <div class="bg-light px-4 py-2 d-flex justify-content-between align-items-center"
                                                 style="cursor: pointer;"
                                                 data-bs-toggle="collapse"
                                                 data-bs-target="#{{ $gradoUniqueId }}">
                                                <div>
                                                    <i class="bi bi-mortarboard me-2 text-secondary"></i>
                                                    <strong class="text-secondary">{{ $gradoNombre }}</strong>
                                                </div>
                                                <div>
                                                    <span class="badge bg-secondary rounded-pill">
                                                        {{ $totalCriteriosGrado }} criterio(s)
                                                    </span>
                                                    <i class="bi bi-chevron-down ms-2"></i>
                                                </div>
                                            </div>

                                            {{-- Contenido colapsable del grado --}}
                                            <div id="{{ $gradoUniqueId }}" class="collapse">
                                                <div>
                                                    @foreach($criteriosGrado as $criterio)
                                                        <div class="px-4 py-3 border-bottom hover-shadow">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-6">
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
                                                                <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                                                    <div class="btn-group btn-group-sm">
                                                                        <a href="{{ route('materiacriterio.edit', $criterio->id) }}"
                                                                           class="btn btn-outline-primary"
                                                                           title="Editar">
                                                                            <i class="bi bi-pencil-square"></i> Editar
                                                                        </a>
                                                                        <form action="{{ route('materiacriterio.destroy', $criterio->id) }}"
                                                                              method="POST" class="d-inline">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit"
                                                                                    class="btn btn-outline-danger"
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
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-inbox display-1 d-block mb-3"></i>
                        <h5>No hay criterios registrados</h5>
                        <p class="mb-0">
                            @if(request()->anyFilled(['materia_id', 'grado_id', 'periodo_bimestre_id']))
                                No se encontraron criterios con los filtros aplicados para el período seleccionado.
                            @else
                                No hay criterios registrados para el período seleccionado.
                            @endif
                        </p>
                        <div class="mt-3">
                            <a href="{{ route('materiacriterio.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Agregar criterios
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const periodoSelect = document.getElementById('periodo_id');
        const bimestreSelect = document.getElementById('periodo_bimestre_id');
        const submitBtn = document.querySelector('button[type="submit"]');
        const form = document.getElementById('filtrosForm');

        // Función para actualizar estado del select de bimestres
        function updateBimestreSelect() {
            if (periodoSelect.value) {
                // Redirigir cuando cambia el período
                const materiaId = document.getElementById('materia_id').value;
                const gradoId = document.getElementById('grado_id').value;
                const bimestreId = bimestreSelect.value;

                let url = form.action + '?periodo_id=' + periodoSelect.value;
                if (materiaId) url += '&materia_id=' + materiaId;
                if (gradoId) url += '&grado_id=' + gradoId;
                if (bimestreId) url += '&periodo_bimestre_id=' + bimestreId;

                window.location.href = url;
            } else {
                bimestreSelect.disabled = true;
                bimestreSelect.value = '';
                submitBtn.disabled = true;
            }
        }

        // Evento cambio de período
        periodoSelect.addEventListener('change', updateBimestreSelect);

        // Si ya hay un período seleccionado, habilitar el bimestre
        if (periodoSelect.value) {
            bimestreSelect.disabled = false;
            submitBtn.disabled = false;
        }

        // Cambiar el ícono del chevron al colapsar/expandir grados
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
            button.addEventListener('click', function() {
                const chevron = this.querySelector('.bi-chevron-down, .bi-chevron-up');
                if (chevron) {
                    if (chevron.classList.contains('bi-chevron-down')) {
                        chevron.classList.remove('bi-chevron-down');
                        chevron.classList.add('bi-chevron-up');
                    } else {
                        chevron.classList.remove('bi-chevron-up');
                        chevron.classList.add('bi-chevron-down');
                    }
                }
            });
        });
    });
</script>

<style>
.hover-shadow {
    transition: background-color 0.2s ease;
}
.hover-shadow:hover {
    background-color: #f8f9fa;
}
.accordion-button:not(.collapsed) {
    box-shadow: none;
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}
[data-bs-toggle="collapse"] {
    cursor: pointer;
    transition: background-color 0.2s ease;
}
[data-bs-toggle="collapse"]:hover {
    background-color: #e9ecef;
}
</style>
@endsection
