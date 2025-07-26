@extends('layouts.app')

@section('content')

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-people-fill"></i> Administración de Mayas
        </h1>
        <!-- Si rol es admin o director que se muestre nueva maya -->
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
            <a href="{{ route('maya.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Nueva Maya
            </a>
        @endif
    </div>
<div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('maya.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="anio-select" class="form-label">Año académico</label>
                        <select name="anio" id="anio-select" class="form-select">
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}" {{ $anio == $anioSeleccionado ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="grado_id" class="form-label">Grado</label>
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
                        <label for="materia_id" class="form-label">Materia</label>
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
                    <div class="col-md-3">
                        <label for="docente_id" class="form-label">Docente</label>
                        <select name="docente_id" id="docente_id" class="form-select">
                            <option value="">Todos los docentes</option>
                            @foreach($docentes as $docente)
                                <option value="{{ $docente->id }}" {{ request('docente_id') == $docente->id ? 'selected' : '' }}>
                                    {{ $docente->user->apellido_paterno }} {{ $docente->user->apellido_materno }}, {{ $docente->user->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                        <a href="{{ route('maya.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="accordion" id="mayasAccordion">
                @forelse($mayas as $maya)
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="headingMaya{{ $maya->id }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseMaya{{ $maya->id }}" aria-expanded="false"
                                aria-controls="collapseMaya{{ $maya->id }}">
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $maya->materia->nombre }}</strong> -
                                        {{ $maya->grado->grado }}° {{ $maya->grado->seccion }} ({{ $maya->anio }})
                                    </div>
                                    <span class="badge bg-primary">
                                        {{ $maya->bimestres->count() }} Bimestre(s)
                                    </span>
                                </div>
                                <div class="text-muted mt-1">
                                    <i class="bi bi-person-vcard"></i>
                                    @if($maya->docente)
                                        {{ $maya->docente->user->apellido_paterno }}
                                        {{ $maya->docente->user->apellido_materno }},
                                        {{ $maya->docente->user->nombre }}
                                    @else
                                        <span class="text-danger">Docente no asignado</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="collapseMaya{{ $maya->id }}" class="accordion-collapse collapse"
                         aria-labelledby="headingMaya{{ $maya->id }}" data-bs-parent="#mayasAccordion">
                        <div class="accordion-body">
                            <div class="mb-3">
                                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
                                    <a href="{{ route('maya.edit', $maya->id) }}" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <form action="{{ route('maya.destroy', $maya->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Eliminar esta maya?')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('bimestre.create', ['curso_grado_sec_niv_anio_id' => $maya->id]) }}"
                                   class="btn btn-primary btn-sm crear-bimestre-btn"
                                   data-bimestres="{{ $maya->bimestres->count() }}">
                                    <i class="bi bi-plus-circle"></i> Crear Bimestre
                                </a>
                            </div>

                            <!-- Lista de Bimestres -->
                            @if($maya->bimestres->isNotEmpty())
                            <div class="accordion" id="bimestresAccordion{{ $maya->id }}">
                                @foreach ($maya->bimestres as $bimestre)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingBimestre{{ $bimestre->id }}">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#collapseBimestre{{ $bimestre->id }}"
                                                aria-expanded="false"
                                                aria-controls="collapseBimestre{{ $bimestre->id }}">
                                            <i class="bi bi-calendar-week me-2"></i>
                                            {{ $bimestre->nombre }} Bimestre
                                        </button>
                                    </h2>
                                    <div id="collapseBimestre{{ $bimestre->id }}"
                                         class="accordion-collapse collapse"
                                         aria-labelledby="headingBimestre{{ $bimestre->id }}"
                                         data-bs-parent="#bimestresAccordion{{ $maya->id }}">
                                        <div class="accordion-body">
                                            <div class="d-flex flex-wrap gap-2">
                                                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
                                                    <a href="{{ route('bimestre.edit', $bimestre->id) }}"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                    <form action="{{ route('bimestre.destroy', $bimestre->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm"
                                                                onclick="return confirm('¿Eliminar este bimestre?')">
                                                            <i class="bi bi-trash"></i> Eliminar
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('nota.index', $bimestre->id) }}"
                                                   class="btn btn-primary btn-sm">
                                                   <i class="bi bi-journal-check"></i> Calificar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="alert alert-info">
                                No hay bimestres registrados para esta maya curricular.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-warning">
                    No se encontraron mayas curriculares con los filtros seleccionados.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    // Cambiar año académico
    document.getElementById('anio-select').addEventListener('change', function() {
        window.location.href = '?anio=' + this.value;
    });

    // Deshabilitar botón "Crear bimestre" si hay 4 o más bimestres
    document.querySelectorAll('.crear-bimestre-btn').forEach(function(btn) {
        if (parseInt(btn.dataset.bimestres) >= 4) {
            btn.classList.add('disabled');
            btn.setAttribute('aria-disabled', 'true');
            btn.setAttribute('tabindex', '-1');
            btn.onclick = function(e) { e.preventDefault(); };
        }
    });

    // Función para guardar el estado de los acordeones
    function saveAccordionState(targetId) {
        // Guardar en localStorage
        localStorage.setItem('maya_last_open', targetId);

        // Guardar también en sessionStorage para navegación inmediata
        sessionStorage.setItem('maya_last_open', targetId);

        // Actualizar la URL con hash
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
        // Verificar si hay un hash en la URL
        const urlHash = window.location.hash;
        const storageTarget = sessionStorage.getItem('maya_last_open') || localStorage.getItem('maya_last_open');

        // Prioridad: 1. Hash URL, 2. sessionStorage, 3. localStorage
        const targetToOpen = urlHash ? `#${urlHash.replace('#', '')}` : (storageTarget || null);

        if (targetToOpen) {
            // Esperar a que se renderice todo el contenido
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
