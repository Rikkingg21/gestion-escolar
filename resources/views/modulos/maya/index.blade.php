@extends('layouts.app')
@section('title', 'Mayas')
@section('content')

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-diagram-3-fill text-primary me-2"></i> Administración de Mayas
        </h1>
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
            <a href="{{ route('maya.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Nueva Maya
            </a>
        @endif
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0 text-dark">
                <i class="bi bi-funnel me-2 text-primary"></i> Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('maya.index') }}" id="filtroForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="periodo_id" class="form-label fw-semibold">Periodo Académico</label>
                        <select name="periodo_id" id="periodo_id" class="form-select">
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}"
                                        {{ ($periodoSeleccionadoId ?? null) == $periodo->id ? 'selected' : '' }}
                                        data-anio="{{ $periodo->anio }}">
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                    @if($periodo->estado == 0)
                                        - Inactivo
                                    @else
                                        - Activo
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="grado_id" class="form-label fw-semibold">Grado</label>
                        <select name="grado_id" id="grado_id" class="form-select">
                            <option value="">Todos los grados</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->grado }}° {{ $grado->seccion }} - {{ $grado->nivel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="materia_id" class="form-label fw-semibold">Materia</label>
                        <select name="materia_id" id="materia_id" class="form-select">
                            <option value="">Todas las materias</option>
                            @foreach($materias as $materia)
                                <option value="{{ $materia->id }}" {{ request('materia_id') == $materia->id ? 'selected' : '' }}>
                                    {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if(isset($docentes))
                    <div class="col-md-2">
                        <label for="docente_id" class="form-label fw-semibold">Docente</label>
                        <select name="docente_id" id="docente_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($docentes as $docente)
                                <option value="{{ $docente->id }}" {{ request('docente_id') == $docente->id ? 'selected' : '' }}>
                                    {{ $docente->user->apellido_paterno }} {{ $docente->user->apellido_materno }}, {{ $docente->user->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-funnel me-2"></i> Aplicar Filtros
                        </button>
                        <a href="{{ route('maya.index') }}" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Mayas -->
    <div class="card shadow">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0 text-dark">
                <i class="bi bi-list-check me-2 text-primary"></i> Mayas Curriculares
                <span class="badge bg-primary ms-2">{{ $mayas->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="accordion" id="mayasAccordion">
                @forelse($mayas as $maya)
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="headingMaya{{ $maya->id }}">
                        <button class="accordion-button collapsed py-3" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseMaya{{ $maya->id }}"
                                aria-expanded="false" aria-controls="collapseMaya{{ $maya->id }}">
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="h6 mb-1 d-block text-dark">{{ $maya->materia->nombre }}</strong>
                                        <small class="text-muted">
                                            {{ $maya->grado->grado }}° {{ $maya->grado->seccion }} -
                                            {{ $maya->grado->nivel }}
                                            @if($maya->periodo)
                                                ({{ $maya->periodo->anio }})
                                            @endif
                                        </small>
                                    </div>
                                    <span class="badge bg-primary">
                                        {{ $maya->bimestres_disponibles->count() }} Bimestre(s)
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <i class="bi bi-person-vcard text-primary me-1"></i>
                                    <small>
                                        <strong class="text-dark">Docente:</strong>
                                        @if($maya->docente && $maya->docente->user)
                                            <span class="text-dark">
                                                {{ $maya->docente->user->apellido_paterno }}
                                                {{ $maya->docente->user->apellido_materno }},
                                                {{ $maya->docente->user->nombre }}
                                            </span>
                                        @else
                                            <span class="text-danger fw-semibold">No asignado</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="collapseMaya{{ $maya->id }}" class="accordion-collapse collapse"
                        aria-labelledby="headingMaya{{ $maya->id }}" data-bs-parent="#mayasAccordion">
                        <div class="accordion-body bg-light">
                            <!-- Información del periodo -->
                            @if($maya->periodo)
                            <div class="alert alert-info border-info mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-calendar-week me-2"></i>
                                    <div>
                                        <strong class="text-dark">Periodo:</strong> {{ $maya->periodo->nombre }}
                                        <span class="ms-2"><strong class="text-dark">Año:</strong> {{ $maya->periodo->anio }}</span>
                                        @if($maya->periodo->descripcion)
                                            <div class="mt-1">
                                                <small class="text-dark">{{ $maya->periodo->descripcion }}</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Acciones de administración -->
                            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
                            <div class="d-flex flex-wrap gap-2 mb-4 p-3 bg-white rounded border">
                                <a href="{{ route('maya.edit', $maya->id) }}" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil me-1"></i> Editar Maya
                                </a>
                                <form action="{{ route('maya.destroy', $maya->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Está seguro de eliminar esta maya?')">
                                        <i class="bi bi-trash me-1"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                            @endif

                            <!-- Bimestres Disponibles -->
                            @if($maya->bimestres_disponibles->isNotEmpty())
                            <div class="row g-2">
                                @foreach ($maya->bimestres_disponibles as $periodoBimestre)
                                @php
                                    $criteriosCount = App\Models\Materia\Materiacriterio::where('materia_id', $maya->materia_id)
                                        ->where('grado_id', $maya->grado_id)
                                        ->where('periodo_bimestre_id', $periodoBimestre->id)
                                        ->count();
                                    $notasCount = 0; // Puedes implementar esto si lo necesitas
                                @endphp

                                <div class="col-md-6 col-lg-4">
                                    <div class="card border h-100">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                @if($notasCount > 0)
                                                    <i class="bi bi-check-circle text-success fs-1"></i>
                                                @elseif($criteriosCount > 0)
                                                    <i class="bi bi-calendar-week text-primary fs-1"></i>
                                                @else
                                                    <i class="bi bi-calendar-x text-warning fs-1"></i>
                                                @endif
                                            </div>

                                            <h5 class="card-title text-dark">
                                                {{ $periodoBimestre->sigla }} - Bimestre {{ $periodoBimestre->bimestre }}
                                            </h5>

                                            <p class="card-text">
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($periodoBimestre->fecha_inicio)->format('d/m/Y') }} -
                                                    {{ \Carbon\Carbon::parse($periodoBimestre->fecha_fin)->format('d/m/Y') }}
                                                </small>
                                            </p>

                                            <p class="card-text">
                                                @if($criteriosCount > 0)
                                                    <small class="text-muted">
                                                        {{ $criteriosCount }} criterio(s)
                                                        @if($notasCount > 0)
                                                            <br>
                                                            <span class="text-success">
                                                                <i class="bi bi-check-circle me-1"></i>{{ $notasCount }} nota(s)
                                                            </span>
                                                        @endif
                                                    </small>
                                                @else
                                                    <small class="text-warning">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Sin criterios
                                                    </small>
                                                @endif
                                            </p>

                                            <!-- Enlace directo a calificar -->
                                            @if($criteriosCount > 0)
                                                <a href="{{ route('nota.index', [
                                                    'curso_grado_sec_niv_anio_id' => $maya->id,
                                                    'periodo_bimestre_id' => $periodoBimestre->id
                                                ]) }}" class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="bi bi-journal-check me-1"></i> Calificar
                                                </a>
                                            @endif

                                            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
                                                <a href="{{ route('materiacriterio.index', [
                                                    'materia_id' => $maya->materia_id,
                                                    'grado_id' => $maya->grado_id,
                                                    'periodo_id' => $periodoSeleccionado->id,
                                                    'periodo_bimestre_id' => $periodoBimestre->id
                                                ]) }}" class="btn btn-outline-info btn-sm w-100">
                                                    <i class="bi bi-list-check me-1"></i>
                                                    {{ $criteriosCount > 0 ? 'Gestionar' : 'Crear' }} Criterios
                                                </a>
                                            @endif
                                        </div>

                                        <!-- Estado del bimestre -->
                                        <div class="card-footer bg-transparent">
                                            @if($notasCount > 0)
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle me-1"></i> Notas registradas
                                                </small>
                                            @elseif($criteriosCount > 0)
                                                <small class="text-primary">
                                                    <i class="bi bi-clock me-1"></i> Listo para calificar
                                                </small>
                                            @else
                                                <small class="text-warning">
                                                    <i class="bi bi-exclamation-triangle me-1"></i> Sin criterios
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="alert alert-warning border-warning">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <div>
                                        <strong class="text-dark">Sin bimestres configurados</strong>
                                        <span class="text-dark d-block">
                                            No hay criterios definidos para esta combinación de materia y grado en el período {{ $periodoSeleccionado ? $periodoSeleccionado->nombre : '' }}.
                                        </span>
                                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
                                            <a href="{{ route('materiacriterio.create') }}?materia_id={{ $maya->materia_id }}&grado_id={{ $maya->grado_id }}&periodo_id={{ $periodoSeleccionadoId }}"
                                            class="btn btn-sm btn-primary mt-2">
                                                <i class="bi bi-plus-circle me-1"></i> Crear Criterios
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <div class="alert alert-warning mx-3">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <h5 class="mb-2">No se encontraron mayas curriculares</h5>
                        <p class="mb-3">
                            @if($periodoSeleccionado)
                                No hay resultados para el periodo <strong>{{ $periodoSeleccionado->nombre }}</strong>
                                con los filtros seleccionados.
                            @else
                                No hay resultados con los filtros seleccionados.
                            @endif
                        </p>
                        <a href="{{ route('maya.index') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> Ver Todas las Mayas
                        </a>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Pie de página con estadísticas -->
        @if($mayas->count() > 0)
        <div class="card-footer bg-light py-3">
            <div class="row text-center">
                <div class="col-md-3">
                    <small class="text-muted d-block">Total Mayas</small>
                    <span class="h5 text-dark">{{ $mayas->count() }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Total Bimestres</small>
                    <span class="h5 text-dark">{{ $mayas->sum(fn($m) => $m->bimestres_disponibles->count()) }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Periodo Actual</small>
                    <span class="h6 text-dark">{{ $periodoSeleccionado->nombre ?? 'No seleccionado' }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Año</small>
                    <span class="h6 text-dark">{{ $periodoSeleccionado->anio ?? date('Y') }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    // Función para guardar el estado de los acordeones
    function saveAccordionState(targetId) {
        localStorage.setItem('maya_last_open', targetId);
        sessionStorage.setItem('maya_last_open', targetId);
        history.replaceState(null, null, window.location.pathname + window.location.search + targetId);
    }

    // Función para abrir un acordeón específico
    function openAccordion(targetId) {
        const element = document.querySelector(targetId);
        if (element) {
            // Cerrar todos los acordeones primero
            document.querySelectorAll('.accordion-collapse').forEach(collapse => {
                if (collapse.id !== targetId.replace('#', '')) {
                    bootstrap.Collapse.getInstance(collapse)?.hide();
                }
            });

            // Abrir el acordeón seleccionado
            const bsCollapse = new bootstrap.Collapse(element, {toggle: true});

            // Desplazar la vista al acordeón
            setTimeout(() => {
                element.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            }, 300);
        }
    }

    // Eventos para los botones del acordeón
    document.querySelectorAll('.accordion-button').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            saveAccordionState(targetId);
        });
    });

    // Al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const urlHash = window.location.hash;
        const storageTarget = sessionStorage.getItem('maya_last_open') || localStorage.getItem('maya_last_open');
        const targetToOpen = urlHash ? `#${urlHash.replace('#', '')}` : (storageTarget || null);

        if (targetToOpen) {
            setTimeout(() => {
                openAccordion(targetToOpen);
            }, 100);
        }
    });

    // Manejar cambios en el hash de la URL
    window.addEventListener('hashchange', function() {
        const targetId = `#${window.location.hash.replace('#', '')}`;
        openAccordion(targetId);
    });
</script>
@endsection
