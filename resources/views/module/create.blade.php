@extends('layouts.app')
@section('title', 'Crear Módulo')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Crear Nuevo Módulo
                    </h5>
                    <a href="{{ route('module.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('module.store') }}" method="POST" id="formCrearModulo">
                        @csrf

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="nombre" class="form-label">
                                    <i class="bi bi-tag me-1 text-primary"></i>Nombre del Módulo *
                                </label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       id="nombre" name="nombre" value="{{ old('nombre') }}"
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
                                    <i class="bi bi-grid me-1 text-primary"></i>Icono (Bootstrap Icons) *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i id="iconoPreview" class="bi bi-question-circle text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('icono') is-invalid @enderror"
                                           id="icono" name="icono" value="{{ old('icono') }}"
                                           placeholder="Ej: bi bi-people, bi bi-gear, bi bi-bar-chart"
                                           required>
                                    @error('icono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Usa clases de Bootstrap Icons. Ej:
                                    <code>bi bi-people</code>,
                                    <code>bi bi-gear</code>,
                                    <code>bi bi-mortarboard</code>
                                </small>
                                <small class="form-text text-muted">
                                    Ver más iconos en #estado
                                    <code>
                                        <a href="https://icons.getbootstrap.com/" target="_blank">Aqui</a>
                                    </code>
                                </small>

                            </div>

                            <div class="col-12 mb-3">
                                <label for="ruta_base" class="form-label">
                                    <i class="bi bi-signpost me-1 text-primary"></i>Ruta Base *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">/</span>
                                    <input type="text" class="form-control @error('ruta_base') is-invalid @enderror"
                                           id="ruta_base" name="ruta_base" value="{{ old('ruta_base') }}"
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
                                    <i class="bi bi-power me-1 text-primary"></i>Estado *
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input @error('estado') is-invalid @enderror"
                                           type="checkbox" role="switch" id="estado" name="estado"
                                           value="1" {{ old('estado', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="estado">
                                        <span id="estadoTexto">Activo</span>
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

                        <!-- Preview del módulo -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">
                                            <i class="bi bi-eye me-1"></i>Vista Previa del Módulo
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i id="previewIcono" class="bi bi-question-circle fs-1 text-primary"></i>
                                            </div>
                                            <div>
                                                <h5 id="previewNombre" class="mb-1">Nombre del módulo</h5>
                                                <p id="previewDescripcion" class="text-muted mb-1">Descripción del módulo</p>
                                                <small class="text-muted">
                                                    Ruta: <code id="previewRuta">/ruta</code>
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
                                    <i class="bi bi-x me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="bi bi-check-lg me-1"></i>Guardar Módulo
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
    const $descripcion = $('#descripcion');
    const $estado = $('#estado');
    const $estadoTexto = $('#estadoTexto');

    // Elementos de preview
    const $previewNombre = $('#previewNombre');
    const $previewIcono = $('#previewIcono');
    const $previewRuta = $('#previewRuta');
    const $previewDescripcion = $('#previewDescripcion');
    const $iconoPreview = $('#iconoPreview');

    // Función para actualizar el preview
    function actualizarPreview() {
        // Preview del nombre
        $previewNombre.text($nombre.val() || 'Nombre del módulo');

        // Preview del icono
        const icono = $icono.val();
        if (icono) {
            $previewIcono.attr('class', icono + ' fs-1 text-primary');
            $iconoPreview.attr('class', icono + ' text-primary');
        } else {
            $previewIcono.attr('class', 'bi bi-question-circle fs-1 text-muted');
            $iconoPreview.attr('class', 'bi bi-question-circle text-muted');
        }

        // Preview de la ruta
        $previewRuta.text('/' + ($rutaBase.val() || 'ruta'));

        // Preview de la descripción
        $previewDescripcion.text($descripcion.val() || 'Descripción del módulo');

        // Preview del estado
        $estadoTexto.text($estado.is(':checked') ? 'Activo' : 'Inactivo');
    }

    // Actualizar preview en tiempo real
    $nombre.on('input', actualizarPreview);
    $icono.on('input', actualizarPreview);
    $rutaBase.on('input', actualizarPreview);
    $descripcion.on('input', actualizarPreview);
    $estado.on('change', actualizarPreview);

    // Botones de iconos sugeridos
    $('.icono-sugerido').on('click', function() {
        const icono = $(this).data('icono');
        $icono.val(icono);
        actualizarPreview();
    });

    // Validación del formulario al enviar
    $('#formCrearModulo').on('submit', function(e) {
        const $btnSubmit = $('#btnSubmit');

        // Deshabilitar botón y mostrar loading
        $btnSubmit.prop('disabled', true)
                 .html('<i class="bi bi-arrow-repeat spin me-1"></i>Guardando...');

        // Validación adicional
        if (!$nombre.val().trim()) {
            e.preventDefault();
            $btnSubmit.prop('disabled', false)
                     .html('<i class="bi bi-check-lg me-1"></i>Guardar Módulo');
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
