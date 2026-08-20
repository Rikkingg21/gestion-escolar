@extends('layouts.app')
@section('title', 'Aula Virtual')

@section('content')
@php
    $plataformas = [
        'meet'  => ['label' => 'Google Meet',  'icon' => 'bi-camera-video', 'color' => 'success'],
        'zoom'  => ['label' => 'Zoom',         'icon' => 'bi-camera-video', 'color' => 'primary'],
        'teams' => ['label' => 'Microsoft Teams', 'icon' => 'bi-people',    'color' => 'info'],
        'otro'  => ['label' => 'Otro',         'icon' => 'bi-link',         'color' => 'secondary'],
    ];

    $sesionesPorMateria = $sesiones->groupBy(fn ($s) => $s->curso?->materia?->nombre ?? 'Sin materia');
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-camera-video me-2"></i> Aula Virtual
                    </h5>
                    @if($grado)
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-mortarboard me-1"></i>{{ $grado->nombre_completo }}
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    @if(!$grado)
                        <div class="text-center py-5">
                            <div class="alert alert-warning mx-3 mb-0">
                                <i class="bi bi-exclamation-triangle fs-1 d-block mb-3"></i>
                                <h5 class="mb-2">No se encontró tu grado</h5>
                                <p class="mb-3">
                                    No tienes una matrícula activa asignada. Comunícate con tu institución
                                    para regularizar tu matrícula.
                                </p>
                            </div>
                        </div>
                    @elseif($sesiones->isEmpty())
                        <div class="text-center py-5">
                            <div class="alert alert-info mx-3 mb-0">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                <h5 class="mb-2">Aún no hay clases virtuales</h5>
                                <p class="mb-3">
                                    Tus docentes aún no han publicado clases virtuales para tu grado.
                                    Vuelve a revisar más tarde.
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- Clases por materia --}}
                        <div class="row g-4">
                            @foreach($sesionesPorMateria as $materia => $clasesMateria)
                            <div class="col-12">
                                <h6 class="text-primary fw-bold text-uppercase mb-3">
                                    <i class="bi bi-journal-bookmark me-2"></i>{{ $materia }}
                                </h6>
                                <div class="row g-3">
                                    @foreach($clasesMateria as $sesion)
                                    <div class="col-12 col-lg-6">
                                        <div class="card border h-100 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="card-title mb-1 text-dark">
                                                            {{ $sesion->titulo ?: 'Clase virtual' }}
                                                        </h6>
                                                        <small class="text-muted">
                                                            @if($sesion->docente?->user)
                                                                <i class="bi bi-person-vcard me-1"></i>
                                                                {{ $sesion->docente->user->nombre_completo }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                    @php
                                                        $plataforma = $plataformas[$sesion->plataforma] ?? $plataformas['otro'];
                                                    @endphp
                                                    <span class="badge bg-{{ $plataforma['color'] }}">
                                                        <i class="{{ $plataforma['icon'] }} me-1"></i>{{ $plataforma['label'] }}
                                                    </span>
                                                </div>

                                                <div class="d-flex flex-wrap gap-3 mb-3">
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar-event me-1 text-primary"></i>
                                                        {{\Carbon\Carbon::parse($sesion->fecha)->format('d/m/Y')}}
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock me-1 text-primary"></i>{{ $sesion->hora }} h
                                                    </small>
                                                </div>

                                                <div class="alert alert-light border py-2 px-3 mb-3">
                                                    <small>
                                                        <i class="bi bi-shield-check text-primary me-1"></i>
                                                        <strong>Motivo:</strong> {{ $sesion->motivo }}
                                                    </small>
                                                </div>

                                                @if($sesion->observaciones)
                                                    <small class="text-muted d-block mb-3">
                                                        <i class="bi bi-chat-left-text me-1"></i>{{ $sesion->observaciones }}
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-grid gap-2">
                                                    <a href="{{ $sesion->enlace }}" target="_blank" rel="noopener"
                                                       class="btn btn-success">
                                                        <i class="bi bi-box-arrow-up-right me-2"></i> Ingresar a la clase
                                                    </a>
                                                    @if($sesion->enlace_material)
                                                        <a href="{{ $sesion->enlace_material }}" target="_blank" rel="noopener"
                                                           class="btn btn-outline-primary">
                                                            <i class="bi bi-folder2-open me-2"></i> Ver material de la clase
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Material de trabajo de los docentes --}}
                        @if($materiales->isNotEmpty())
                            <hr class="my-4">
                            <h5 class="text-dark mb-3">
                                <i class="bi bi-folder2-open text-primary me-2"></i> Material de Trabajo de tus Docentes
                            </h5>
                            <div class="row g-3">
                                @foreach($materiales as $material)
                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="card border h-100">
                                        <div class="card-body d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-person-circle text-primary fs-1"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-dark">
                                                    {{ $material->docente?->user?->nombre_completo ?? 'Docente' }}
                                                </h6>
                                                <small class="text-muted d-block mb-2">
                                                    <i class="bi bi-folder me-1"></i> Carpeta de Google Drive
                                                </small>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ $material->enlace_google_drive }}" target="_blank" rel="noopener"
                                                       class="btn btn-success">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> Abrir Drive
                                                    </a>
                                                    <button type="button" class="btn btn-outline-warning"
                                                            data-bs-toggle="modal" data-bs-target="#modalLimpiarCache"
                                                            onclick="document.getElementById('driveUrlActual').value='{{ $material->enlace_google_drive }}'">
                                                        <i class="bi bi-broom"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

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
                    También puedes abrir tu Drive con una versión nueva del enlace usando el botón de abajo.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning text-dark" id="btnReabrirDriveEstudiante">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Abrir Drive (sin caché)
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="driveUrlActual" value="">

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnReabrir = document.getElementById('btnReabrirDriveEstudiante');

        if (btnReabrir) {
            btnReabrir.addEventListener('click', function () {
                const url = document.getElementById('driveUrlActual').value;
                if (!url) {
                    alert('Selecciona primero un enlace de Drive.');
                    return;
                }
                const separador = url.includes('?') ? '&' : '?';
                window.open(url + `${separador}v=${Date.now()}`, '_blank', 'noopener');
            });
        }
    });
</script>
@endsection