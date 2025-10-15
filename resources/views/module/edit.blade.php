@extends('layouts.app')
@section('title', 'Editar Módulo')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Editar Módulo
                    </h5>
                    <a href="{{ route('module.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('module.update', $module->id) }}" method="POST" id="formEditarModulo">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="nombre" class="form-label">
                                    <i class="fas fa-tag me-1 text-primary"></i>Nombre del Módulo *
                                </label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       id="nombre" name="nombre" value="{{ old('nombre', $module->nombre) }}"
                                       placeholder="Ej: Gestión Académica, Configuración, Reportes"
                                       required autofocus>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Nombre descriptivo del módulo que se mostrará en el sistema.
                                </small>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="icono" class="form-label">
                                    <i class="fas fa-icons me-1 text-primary"></i>Icono (Bootstrap Icons) *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i id="iconoPreview" class="{{ $module->icono }} text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('icono') is-invalid @enderror"
                                           id="icono" name="icono" value="{{ old('icono', $module->icono) }}"
                                           placeholder="Ej: bi-people, bi-gear, bi-graph-up"
                                           required>
                                    @error('icono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Usa clases de Bootstrap Icons. Ej:
                                    <code>bi-people</code>,
                                    <code>bi-gear</code>,
                                    <code>bi-book</code>
                                </small>
                                <small class="form-text text-muted">
                                    Ver más iconos en
                                    <code>
                                        <a href="https://icons.getbootstrap.com/" target="_blank">Aquí</a>
                                    </code>
                                </small>

                                <!-- Iconos sugeridos Bootstrap -->
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-2">Iconos sugeridos:</small>
                                    <div class="d-flex flex-wrap gap-2">
                                        @php
                                            $iconosSugeridos = [
                                                'bi-people' => 'Usuarios',
                                                'bi-book' => 'Académico',
                                                'bi-person-badge' => 'Docentes',
                                                'bi-person' => 'Estudiantes',
                                                'bi-journal' => 'Cursos',
                                                'bi-calendar' => 'Calendario',
                                                'bi-graph-up' => 'Reportes',
                                                'bi-gear' => 'Configuración',
                                                'bi-house' => 'Inicio',
                                                'bi-speedometer' => 'Dashboard'
                                            ];
                                        @endphp
                                        @foreach($iconosSugeridos as $icono => $titulo)
                                            <button type="button" class="btn btn-outline-secondary btn-sm icono-sugerido"
                                                    data-icono="{{ $icono }}" data-bs-toggle="tooltip" title="{{ $titulo }}">
                                                <i class="{{ $icono }} me-1"></i>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="ruta_base" class="form-label">
                                    <i class="fas fa-route me-1 text-primary"></i>Ruta Base *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">/</span>
                                    <input type="text" class="form-control @error('ruta_base') is-invalid @enderror"
                                           id="ruta_base" name="ruta_base" value="{{ old('ruta_base', $module->ruta_base) }}"
                                           placeholder="Ej: academico, configuracion, reportes"
                                           required>
                                    @error('ruta_base')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Ruta principal del módulo en la aplicación (sin la barra inicial).
                                </small>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="estado" class="form-label">
                                    <i class="fas fa-power-off me-1 text-primary"></i>Estado *
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input @error('estado') is-invalid @enderror"
                                           type="checkbox" role="switch" id="estado" name="estado"
                                           value="1" {{ old('estado', $module->estado) == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="estado">
                                        <span id="estadoTexto">{{ $module->estado == '1' ? 'Activo' : 'Inactivo' }}</span>
                                        <small class="text-muted d-block">
                                            Los módulos inactivos no estarán disponibles en el sistema.
                                        </small>
                                    </label>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Información del módulo -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading mb-2">
                                        <i class="fas fa-info-circle me-1"></i>Información del Módulo
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small><strong>ID:</strong> {{ $module->id }}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <small><strong>Creado:</strong> {{ $module->created_at->format('d/m/Y') }}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <small><strong>Roles asignados:</strong> {{ $module->roles()->count() }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview del módulo -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-eye me-1"></i>Vista Previa del Módulo
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i id="previewIcono" class="{{ $module->icono }} fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h5 id="previewNombre" class="mb-1">{{ $module->nombre }}</h5>
                                                <p id="previewDescripcion" class="text-muted mb-1">Módulo del sistema</p>
                                                <small class="text-muted">
                                                    Ruta: <code id="previewRuta">/{{ $module->ruta_base }}</code>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <a href="{{ route('module.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="fas fa-save me-1"></i>Actualizar Módulo
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Elementos del preview
    const $nombre = $('#nombre');
    const $icono = $('#icono');
    const $rutaBase = $('#ruta_base');
    const $estado = $('#estado');
    const $estadoTexto = $('#estadoTexto');

    // Elementos de preview
    const $previewNombre = $('#previewNombre');
    const $previewIcono = $('#previewIcono');
    const $previewRuta = $('#previewRuta');
    const $iconoPreview = $('#iconoPreview');

    // Función para actualizar el preview
    function actualizarPreview() {
        // Preview del nombre
        $previewNombre.text($nombre.val() || 'Nombre del módulo');

        // Preview del icono
        const icono = $icono.val();
        if (icono) {
            $previewIcono.attr('class', icono + ' fa-2x text-primary');
            $iconoPreview.attr('class', icono + ' text-primary');
        } else {
            $previewIcono.attr('class', 'bi-question-circle fa-2x text-muted');
            $iconoPreview.attr('class', 'bi-question-circle text-muted');
        }

        // Preview de la ruta
        $previewRuta.text('/' + ($rutaBase.val() || 'ruta'));

        // Preview del estado
        $estadoTexto.text($estado.is(':checked') ? 'Activo' : 'Inactivo');
    }

    // Actualizar preview en tiempo real
    $nombre.on('input', actualizarPreview);
    $icono.on('input', actualizarPreview);
    $rutaBase.on('input', actualizarPreview);
    $estado.on('change', actualizarPreview);

    // Botones de iconos sugeridos
    $('.icono-sugerido').on('click', function() {
        const icono = $(this).data('icono');
        $icono.val(icono);
        actualizarPreview();
    });

    // Validación del formulario al enviar
    $('#formEditarModulo').on('submit', function(e) {
        const $btnSubmit = $('#btnSubmit');

        // Deshabilitar botón y mostrar loading
        $btnSubmit.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...');

        // Validación adicional
        if (!$nombre.val().trim()) {
            e.preventDefault();
            $btnSubmit.prop('disabled', false)
                     .html('<i class="fas fa-save me-1"></i>Actualizar Módulo');
            $nombre.focus();
        }
    });

    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Inicializar preview
    actualizarPreview();
});
</script>
@endsection
