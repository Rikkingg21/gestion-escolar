@extends('layouts.app')
@section('title', 'Aula Virtual Docente')

@section('content')
@php
    $plataformas = [
        'meet'  => ['label' => 'Google Meet',  'icon' => 'bi-camera-video', 'color' => 'success'],
        'zoom'  => ['label' => 'Zoom',         'icon' => 'bi-camera-video', 'color' => 'primary'],
        'teams' => ['label' => 'Microsoft Teams', 'icon' => 'bi-people',    'color' => 'info'],
        'otro'  => ['label' => 'Otro',         'icon' => 'bi-link',         'color' => 'secondary'],
    ];
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            {{-- Clases Virtuales --}}
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-camera-video me-2"></i> Clases Virtuales
                    </h5>
                    <a href="{{ route('aula-virtual-docente.create') }}" class="btn btn-light btn-sm text-primary">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Clase
                    </a>
                </div>
                <div class="card-body">

                    {{-- Filtros --}}
                    <div class="card mb-4">
                        <div class="card-header bg-light py-3">
                            <h6 class="mb-0 text-dark">
                                <i class="bi bi-funnel me-2 text-primary"></i> Filtros de Búsqueda
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('aula-virtual-docente.index') }}">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="periodo_id" class="form-label fw-semibold">Periodo Académico</label>
                                        <select name="periodo_id" id="periodo_id" class="form-select">
                                            @forelse($periodos as $periodo)
                                                <option value="{{ $periodo->id }}"
                                                        {{ $periodoSeleccionadoId == $periodo->id ? 'selected' : '' }}>
                                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                                </option>
                                            @empty
                                                <option value="">Sin periodos disponibles</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="curso_grado_sec_niv_anio_id" class="form-label fw-semibold">Curso</label>
                                        <select name="curso_grado_sec_niv_anio_id" id="curso_grado_sec_niv_anio_id" class="form-select">
                                            <option value="">Todos los cursos</option>
                                            @foreach($cursos as $curso)
                                                <option value="{{ $curso->id }}"
                                                        {{ $cursoId == $curso->id ? 'selected' : '' }}>
                                                    {{ $curso->materia->nombre ?? 'Materia' }} - {{ $curso->grado->nombre_completo ?? 'Grado' }}
                                                    @if($curso->periodo)
                                                        ({{ $curso->periodo->anio }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end gap-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-funnel me-1"></i> Filtrar
                                        </button>
                                        <a href="{{ route('aula-virtual-docente.index') }}" class="btn btn-outline-secondary w-100">
                                            <i class="bi bi-eraser"></i>
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Listado de clases por curso --}}
                    @if($sesiones->isEmpty())
                        <div class="text-center py-5">
                            <div class="alert alert-warning mx-3 mb-0">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                <h5 class="mb-2">No hay clases virtuales programadas</h5>
                                <p class="mb-3">Crea tu primera clase para que tus estudiantes puedan ingresar.</p>
                                <a href="{{ route('aula-virtual-docente.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i> Nueva Clase
                                </a>
                            </div>
                        </div>
                    @else
                        @php
                            $sesionesPorCurso = $sesiones->groupBy('curso_grado_sec_niv_anio_id');
                        @endphp
                        <div class="accordion" id="clasesAccordion">
                            @foreach($cursos as $curso)
                                @php
                                    $sesionesCurso = $sesionesPorCurso->get($curso->id, collect());
                                @endphp
                                @if($sesionesCurso->isNotEmpty())
                                <div class="accordion-item border-bottom">
                                    <h2 class="accordion-header" id="headingCurso{{ $curso->id }}">
                                        <button class="accordion-button collapsed py-3" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapseCurso{{ $curso->id }}"
                                                aria-expanded="false" aria-controls="collapseCurso{{ $curso->id }}">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <div>
                                                    <strong class="h6 text-dark">
                                                        <i class="bi bi-journal-bookmark text-primary me-1"></i>
                                                        {{ $curso->materia->nombre ?? 'Materia' }}
                                                    </strong>
                                                    <small class="text-muted d-block">
                                                        {{ $curso->grado->nombre_completo ?? 'Grado' }}
                                                        @if($curso->periodo)
                                                            · {{ $curso->periodo->nombre }} ({{ $curso->periodo->anio }})
                                                        @endif
                                                        @if($curso->docente?->user)
                                                            · {{ $curso->docente->user->nombre_completo }}
                                                        @endif
                                                    </small>
                                                </div>
                                                <span class="badge bg-primary">{{ $sesionesCurso->count() }} clase(s)</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapseCurso{{ $curso->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="headingCurso{{ $curso->id }}" data-bs-parent="#clasesAccordion">
                                        <div class="accordion-body bg-light">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover align-middle bg-white">
                                                    <thead class="table-light">
                                                        <tr class="text-center">
                                                            <th style="width: 18%;">Clase</th>
                                                            <th style="width: 10%;">Fecha</th>
                                                            <th style="width: 8%;">Hora</th>
                                                            <th style="width: 12%;">Plataforma</th>
                                                            <th>Motivo</th>
                                                            <th style="width: 10%;">Enlace</th>
                                                            <th style="width: 10%;">Material</th>
                                                            <th style="width: 15%;">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($sesionesCurso as $sesion)
                                                        <tr>
                                                            <td>
                                                                <strong class="text-dark">{{ $sesion->titulo ?: 'Clase virtual' }}</strong>
                                                                @if($sesion->observaciones)
                                                                    <small class="text-muted d-block">{{ $sesion->observaciones }}</small>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                {{\Carbon\Carbon::parse($sesion->fecha)->format('d/m/Y')}}
                                                            </td>
                                                            <td class="text-center fw-semibold">{{ $sesion->hora }}</td>
                                                            <td class="text-center">
                                                                @php
                                                                    $plataforma = $plataformas[$sesion->plataforma] ?? $plataformas['otro'];
                                                                @endphp
                                                                <span class="badge bg-{{ $plataforma['color'] }}">
                                                                    <i class="{{ $plataforma['icon'] }} me-1"></i>{{ $plataforma['label'] }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <i class="bi bi-shield-check text-primary me-1"></i>{{ $sesion->motivo }}
                                                                </small>
                                                            </td>
                                                            <td class="text-center">
                                                                <a href="{{ $sesion->enlace }}" target="_blank" rel="noopener"
                                                                   class="btn btn-sm btn-success">
                                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Ingresar
                                                                </a>
                                                            </td>
                                                            <td class="text-center">
                                                                @if($sesion->enlace_material)
                                                                    <a href="{{ $sesion->enlace_material }}" target="_blank" rel="noopener"
                                                                       class="btn btn-sm btn-outline-primary">
                                                                        <i class="bi bi-folder2-open me-1"></i> Ver material
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted">--</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="btn-group" role="group">
                                                                    <a href="{{ route('aula-virtual-docente.edit', $sesion->id) }}"
                                                                       class="btn btn-sm btn-warning" title="Editar">
                                                                        <i class="bi bi-pencil me-1"></i> Editar
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-danger"
                                                                            onclick="eliminarSesion({{ $sesion->id }}, '{{ addslashes($sesion->titulo ?: 'Clase virtual') }}')"
                                                                            title="Eliminar">
                                                                        <i class="bi bi-trash me-1"></i> Eliminar
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Mi Material de Trabajo --}}
            @if($docente)
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-folder2-open me-2"></i> Mi Material de Trabajo
                    </h5>
                    @if($material?->enlace_google_drive)
                        <a href="{{ $material->enlace_google_drive }}" target="_blank" rel="noopener"
                           class="btn btn-success btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Abrir Drive
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <form action="{{ route('aula-virtual-docente.material') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="enlace_google_drive" class="form-label fw-semibold">
                                    <i class="bi bi-google me-1"></i> Enlace de Google Drive
                                </label>
                                <input type="url" name="enlace_google_drive" id="enlace_google_drive"
                                       class="form-control @error('enlace_google_drive') is-invalid @enderror"
                                       value="{{ old('enlace_google_drive', $material?->enlace_google_drive) }}"
                                       placeholder="https://drive.google.com/..." >
                                @error('enlace_google_drive')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Comparte aquí la carpeta de Drive con tu material de trabajo para que tus estudiantes la vean.
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Guardar enlace
                                </button>
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal"
                                        data-bs-target="#modalLimpiarCache" id="btnLimpiarCache">
                                    <i class="bi bi-broom me-1"></i> Limpiar caché
                                </button>
                            </div>
                        </div>
                    </form>
                    @if($material?->enlace_google_drive)
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Si al abrir tu carpeta aparece el error <strong>500 de Google Drive</strong>, usa el botón
                            <strong>Limpiar caché</strong> para recargar el enlace con una versión nueva.
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Formulario para eliminar sesión --}}
<form id="formEliminarSesion" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- Modal Limpiar caché --}}
<div class="modal fade" id="modalLimpiarCache" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark">
                    <i class="bi bi-broom me-2"></i> Limpiar caché del navegador
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>
                    Si Google Drive te muestra el <strong>error 500</strong>, el problema suele ser la caché
                    guardada. Sigue estos pasos:
                </p>
                <ol class="mb-3">
                    <li>Presiona <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>Supr</kbd> en tu teclado.</li>
                    <li>Elige el rango <em>"Todo el tiempo"</em>.</li>
                    <li>Marca <strong>Imágenes y archivos almacenados en caché</strong>.</li>
                    <li>Haz clic en <strong>Borrar datos</strong>.</li>
                </ol>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    También puedes reabrir tu Drive con una versión nueva del enlace pulsando el botón de abajo.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning text-dark" id="btnReabrirDrive">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Reabrir Drive (sin caché)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function eliminarSesion(id, titulo) {
        if (confirm(`¿Estás seguro de eliminar la clase "${titulo}"? Esta acción no se puede deshacer.`)) {
            const form = document.getElementById('formEliminarSesion');
            form.action = "{{ url('aula-virtual-docente') }}/" + id;
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const btnReabrir = document.getElementById('btnReabrirDrive');
        const btnLimpiar = document.getElementById('btnLimpiarCache');
        const enlace = document.getElementById('enlace_google_drive');

        const abrirConCacheLimpia = function () {
            const url = enlace ? enlace.value.trim() : '';
            if (!url) {
                alert('Primero guarda tu enlace de Google Drive.');
                return;
            }
            const separador = url.includes('?') ? '&' : '?';
            const cacheBuster = `${separador}v=${Date.now()}`;
            window.open(url + cacheBuster, '_blank', 'noopener');
        };

        if (btnReabrir) {
            btnReabrir.addEventListener('click', abrirConCacheLimpia);
        }
    });
</script>
@endsection