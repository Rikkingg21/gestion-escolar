@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-plus-circle me-2"></i> Crear Competencias
        </h1>
        <a href="{{ route('materiacompetencia.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a Competencias
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-list-check me-2"></i> Formulario de Competencias
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('materiacompetencia.store') }}" method="POST" id="competenciaForm">
                @csrf

                {{-- Selección de Materia --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="materia_id" class="form-label">Materia *</label>
                        <select class="form-select @error('materia_id') is-invalid @enderror"
                                id="materia_id" name="materia_id" required>
                            <option value="">Seleccionar materia</option>
                            @foreach($materias as $materia)
                                <option value="{{ $materia->id }}" {{ old('materia_id') == $materia->id ? 'selected' : '' }}>
                                    {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('materia_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Competencias Dinámicas --}}
                <div id="competencias-container">
                    {{-- Primera competencia --}}
                    <div class="competencia-item card mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Competencia #1</h6>
                            <button type="button" class="btn btn-sm btn-danger remove-competencia" disabled>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="competencias[0][nombre]" class="form-label">Nombre de la Competencia *</label>
                                    <input type="text" class="form-control"
                                           name="competencias[0][nombre]"
                                           value="{{ old('competencias.0.nombre') }}"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="competencias[0][estado]" class="form-label">Estado *</label>
                                    <select class="form-select" name="competencias[0][estado]" required>
                                        <option value="1" {{ old('competencias.0.estado', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('competencias.0.estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="competencias[0][descripcion]" class="form-label">Descripción</label>
                                    <textarea class="form-control"
                                              name="competencias[0][descripcion]"
                                              rows="2">{{ old('competencias.0.descripcion') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Botón para agregar más competencias --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <button type="button" id="add-competencia" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> Agregar Otra Competencia
                        </button>
                    </div>
                </div>

                {{-- Botones de acción --}}
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Todas las Competencias
                        </button>
                        <a href="{{ route('materiacompetencia.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let competenciaCount = 1;
    const container = document.getElementById('competencias-container');
    const addButton = document.getElementById('add-competencia');

    // Función para agregar nueva competencia
    addButton.addEventListener('click', function() {
        const newIndex = competenciaCount;
        const newCompetencia = document.createElement('div');
        newCompetencia.className = 'competencia-item card mb-3';
        newCompetencia.innerHTML = `
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Competencia #${newIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-danger remove-competencia">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="competencias[${newIndex}][nombre]" class="form-label">Nombre de la Competencia *</label>
                        <input type="text" class="form-control"
                               name="competencias[${newIndex}][nombre]"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label for="competencias[${newIndex}][estado]" class="form-label">Estado *</label>
                        <select class="form-select" name="competencias[${newIndex}][estado]" required>
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="competencias[${newIndex}][descripcion]" class="form-label">Descripción</label>
                        <textarea class="form-control"
                                  name="competencias[${newIndex}][descripcion]"
                                  rows="2"></textarea>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(newCompetencia);
        competenciaCount++;

        // Habilitar botones de eliminar en todas las competencias
        updateRemoveButtons();
    });

    // Función para actualizar botones de eliminar
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-competencia');
        removeButtons.forEach((button, index) => {
            // Habilitar todos los botones excepto el primero si hay más de uno
            if (removeButtons.length > 1) {
                button.disabled = false;
            } else {
                button.disabled = true;
            }

            // Remover event listeners existentes y agregar nuevo
            button.replaceWith(button.cloneNode(true));
        });

        // Agregar event listeners a los nuevos botones
        document.querySelectorAll('.remove-competencia').forEach(button => {
            button.addEventListener('click', function() {
                if (document.querySelectorAll('.competencia-item').length > 1) {
                    this.closest('.competencia-item').remove();
                    updateCompetenciaNumbers();
                    updateRemoveButtons();
                }
            });
        });
    }

    // Función para actualizar los números de las competencias
    function updateCompetenciaNumbers() {
        document.querySelectorAll('.competencia-item').forEach((item, index) => {
            const header = item.querySelector('.card-header h6');
            header.textContent = `Competencia #${index + 1}`;

            // Actualizar los índices en los names de los inputs
            const inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/competencias\[\d+\]/, `competencias[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        competenciaCount = document.querySelectorAll('.competencia-item').length;
    }

    // Validación del formulario
    document.getElementById('competenciaForm').addEventListener('submit', function(e) {
        const materiaSelect = document.getElementById('materia_id');
        if (!materiaSelect.value) {
            e.preventDefault();
            alert('Por favor selecciona una materia.');
            materiaSelect.focus();
            return false;
        }

        // Validar que al menos una competencia tenga nombre
        const competenciaNombres = document.querySelectorAll('input[name^="competencias"][name$="[nombre]"]');
        let hasValidCompetencia = false;
        competenciaNombres.forEach(input => {
            if (input.value.trim() !== '') {
                hasValidCompetencia = true;
            }
        });

        if (!hasValidCompetencia) {
            e.preventDefault();
            alert('Por favor ingresa al menos una competencia con nombre.');
            return false;
        }
    });

    // Inicializar botones de eliminar
    updateRemoveButtons();
});
</script>

<style>
.competencia-item {
    border-left: 4px solid #4e73df;
}

.competencia-item .card-header {
    background-color: #f8f9fa !important;
}

.remove-competencia:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
@endsection
