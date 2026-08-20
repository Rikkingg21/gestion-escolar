@extends('layouts.app')
@section('title', 'Configurar IE')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-building me-2"></i> Configuración del Colegio
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
            <form method="POST" action="{{ route('colegioconfig.update', $colegio->id) }}" enctype="multipart/form-data" id="logoForm">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre del Colegio *</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                               id="nombre" name="nombre" value="{{ old('nombre', $colegio->nombre) }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="director_actual" class="form-label">Director Actual</label>
                        <input type="text" class="form-control @error('director_actual') is-invalid @enderror"
                               id="director_actual" name="director_actual"
                               value="{{ old('director_actual', $colegio->director_actual) }}">
                        @error('director_actual')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="direccion" class="form-label">Dirección *</label>
                        <textarea class="form-control @error('direccion') is-invalid @enderror"
                                  id="direccion" name="direccion" rows="2" required>{{ old('direccion', $colegio->direccion) }}</textarea>
                        @error('direccion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                               id="telefono" name="telefono"
                               value="{{ old('telefono', $colegio->telefono) }}">
                        @error('telefono')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email"
                               value="{{ old('email', $colegio->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="ruc" class="form-label">RUC</label>
                        <input type="text" class="form-control @error('ruc') is-invalid @enderror"
                               id="ruc" name="ruc" maxlength="11"
                               value="{{ old('ruc', $colegio->ruc) }}">
                        @error('ruc')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <label for="logo" class="form-label">Logo del Colegio</label>
                        <input type="file" class="form-control @error('logo') is-invalid @enderror"
                               id="logo" name="logo" accept="image/*" onchange="previewImage(event)">
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- SECCIÓN DE TIPO DE INSTITUCIÓN -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-toggles me-2"></i>Tipo de Institución
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch form-check-inline me-5">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="es_privado" name="es_privado"
                                           value="1" @checked($colegio->es_privado)>
                                    <label class="form-check-label" for="es_privado">
                                        <i class="bi bi-shield-lock me-1"></i> Institución Privada
                                    </label>
                                </div>

                                <div class="form-check form-switch form-check-inline" id="pensionesWrapper" style="display: none;">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="pensiones_activo" name="pensiones_activo"
                                           value="1" @checked($colegio->pensiones_activo)>
                                    <label class="form-check-label" for="pensiones_activo">
                                        <i class="bi bi-cash-coin me-1"></i> Activar Módulo de Pensiones
                                    </label>
                                </div>

                                <div class="text-muted small mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Si la institución es privada podrás habilitar los módulos de pensiones
                                    (Pensiones Admin y Pensiones) para asignarlos a los roles.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE PASARELA DE PAGOS (CULQI) -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-credit-card me-2"></i>Pasarela de Pagos (Culqi)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="usa_pasarela_pagos" name="usa_pasarela_pagos"
                                           value="1" @checked($colegio->usa_pasarela_pagos)>
                                    <label class="form-check-label" for="usa_pasarela_pagos">
                                        <i class="bi bi-toggle-on me-1"></i> Activar cobros online con Culqi
                                    </label>
                                </div>

                                <div id="pasarelaConfig" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="culqi_public_key" class="form-label">Llave pública (pk_...)</label>
                                            <input type="text" class="form-control" id="culqi_public_key"
                                                   name="culqi_public_key" maxlength="255"
                                                   value="{{ $colegio->culqi_public_key }}"
                                                   placeholder="pk_test_...">
                                            <div class="form-text">Dejar en blanco para conservar la actual.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="culqi_secret_key" class="form-label">Llave secreta (sk_...)</label>
                                            <input type="password" class="form-control" id="culqi_secret_key"
                                                   name="culqi_secret_key" maxlength="255"
                                                   value="{{ $colegio->culqi_secret_key ? '********' : '' }}"
                                                   placeholder="sk_test_...">
                                            <div class="form-text">
                                                @if($colegio->culqi_secret_key)
                                                    Hay una llave secreta guardada. Escribe una nueva solo si deseas reemplazarla.
                                                @else
                                                    No hay llave secreta guardada.
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="culqi_modo_prueba" name="culqi_modo_prueba"
                                               value="1" @checked($colegio->culqi_modo_prueba)>
                                        <label class="form-check-label" for="culqi_modo_prueba">
                                            <i class="bi bi-bug me-1"></i> Modo prueba (sandbox)
                                        </label>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            En modo prueba los cobros se realizan contra la API de prueba de Culqi
                                            (llaves <code>pk_test_</code> / <code>sk_test_</code>). Desactívalo al salir a producción.
                                        </div>
                                    </div>
                                </div>

                                <div class="text-muted small mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Al activar la pasarela, los trámites con pago podrán cobrarse online.
                                    Los montos se cobran en céntimos (S/ 1.00 = 100 céntimos).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE COMPARACIÓN DE LOGOS -->
                <div class="row mb-4">
                    <!-- Columna para el logo actual -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="bi bi-image me-2"></i>Logo Actual
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                @if($colegio->logo_path)
                                    <img src="{{ Storage::url($colegio->logo_path) }}"
                                         alt="Logo actual del colegio"
                                         style="max-height: 200px; max-width: 100%;"
                                         class="img-fluid rounded">
                                    <div class="mt-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="eliminar_logo" name="eliminar_logo">
                                            <label class="form-check-label" for="eliminar_logo">
                                                <i class="bi bi-trash me-1"></i> Eliminar logo actual
                                            </label>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">No hay logo cargado actualmente</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Columna para la previsualización del nuevo logo -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="bi bi-eye me-2"></i>Vista Previa del Nuevo Logo
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <div id="newLogoPreview" style="display: none;">
                                    <img id="preview" src="#"
                                         alt="Previsualización del nuevo logo"
                                         style="max-height: 200px; max-width: 100%;"
                                         class="img-fluid rounded">
                                    <p class="text-muted small mt-3">
                                        Esta es una previsualización. El logo se actualizará al guardar.
                                    </p>
                                </div>
                                <div id="noPreview" class="text-center py-5">
                                    <i class="bi bi-upload text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3">
                                        Selecciona un nuevo logo para ver la previsualización aquí
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-2"></i> Guardar Configuración
                    </button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('newLogoPreview');
    const noPreview = document.getElementById('noPreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
            noPreview.style.display = 'none';
        }

        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.style.display = 'none';
        noPreview.style.display = 'block';
        preview.src = '#';
    }
}

// Mostrar advertencia si se selecciona eliminar logo
document.addEventListener('DOMContentLoaded', function() {
    const eliminarCheckbox = document.getElementById('eliminar_logo');
    if (eliminarCheckbox) {
        eliminarCheckbox.addEventListener('change', function() {
            if (this.checked) {
                if (!confirm('¿Estás seguro de que quieres eliminar el logo actual?\n\nSe reemplazará por el nuevo logo si has seleccionado uno, o quedará vacío.')) {
                    this.checked = false;
                }
            }
        });
    }

    // Mostrar/ocultar la opción de activar pensiones según si la IE es privada
    const esPrivado = document.getElementById('es_privado');
    const pensionesWrapper = document.getElementById('pensionesWrapper');
    const pensionesActivo = document.getElementById('pensiones_activo');

    function togglePensiones() {
        if (esPrivado && pensionesWrapper) {
            pensionesWrapper.style.display = esPrivado.checked ? 'inline-block' : 'none';
            if (!esPrivado.checked && pensionesActivo) {
                pensionesActivo.checked = false;
            }
        }
    }

    if (esPrivado) {
        esPrivado.addEventListener('change', togglePensiones);
        togglePensiones();
    }

    // Mostrar/ocultar la configuración de la pasarela según el switch
    const usaPasarela = document.getElementById('usa_pasarela_pagos');
    const pasarelaConfig = document.getElementById('pasarelaConfig');

    function togglePasarela() {
        if (usaPasarela && pasarelaConfig) {
            pasarelaConfig.style.display = usaPasarela.checked ? 'block' : 'none';
        }
    }

    if (usaPasarela) {
        usaPasarela.addEventListener('change', togglePasarela);
        togglePasarela();
    }

    // Inicializar estado de previsualización
    const logoInput = document.getElementById('logo');
    if (logoInput.files.length === 0) {
        document.getElementById('newLogoPreview').style.display = 'none';
        document.getElementById('noPreview').style.display = 'block';
    }
});
</script>

<style>
.img-thumbnail {
    border: 1px solid #ddd;
    padding: 5px;
    background-color: #f8f9fa;
}

.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}

.card-header {
    border-bottom: 1px solid #e3e6f0;
}

.img-fluid {
    transition: transform 0.3s ease;
}

.img-fluid:hover {
    transform: scale(1.02);
}
</style>
@endsection
