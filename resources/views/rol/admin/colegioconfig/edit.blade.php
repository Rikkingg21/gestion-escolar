@extends('layouts.app')
@section('title', 'Configurar IE')
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-building me-2"></i> Configuración del Colegio
        </h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
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
                                    <img src="{{ asset($colegio->logo_path) }}"
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
