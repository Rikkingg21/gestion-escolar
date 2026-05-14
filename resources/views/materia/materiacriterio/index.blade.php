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
            <a href="{{ route('materiacriterio.importarPeriodoAnterior') }}" class="btn btn-info shadow-sm">
                <i class="bi bi-arrow-down-circle me-1"></i> Importar desde otro Periodo
            </a>
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

                    @foreach($materiasAgrupadas as $materiaNombre => $grados)
                        @php
                            $materiaId = Str::slug($materiaNombre, '-') . '-' . $loop->index;
                            $totalCriteriosMateria = 0;
                            foreach($grados as $competencias) {
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
                                    @foreach($grados as $gradoNombre => $competencias)
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
