@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-plus-circle me-2"></i> Crear Criterios de Evaluación
        </h1>
        <a href="{{ route('materiacriterio.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a Criterios
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
                <i class="bi bi-card-checklist me-2"></i> Formulario de Criterios
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('materiacriterio.store') }}" method="POST" id="criterioForm">
                @csrf

                {{-- Selección de Materia y Competencia --}}
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

                    <div class="col-md-6">
                        <label for="materia_competencia_id" class="form-label">Competencia *</label>
                        <select class="form-select @error('materia_competencia_id') is-invalid @enderror"
                                id="materia_competencia_id" name="materia_competencia_id" required disabled>
                            <option value="">Primero selecciona una materia</option>
                        </select>
                        @error('materia_competencia_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Criterios Dinámicos --}}
                <div id="criterios-container">
                    {{-- Primer criterio --}}
                    <div class="criterio-item card mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Criterio #1</h6>
                            <button type="button" class="btn btn-sm btn-danger remove-criterio" disabled>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="criterios[0][nombre]" class="form-label">Nombre del Criterio *</label>
                                    <input type="text" class="form-control"
                                           name="criterios[0][nombre]"
                                           value="{{ old('criterios.0.nombre') }}"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="criterios[0][anio]" class="form-label">Año *</label>
                                    <select class="form-select" name="criterios[0][anio]" required>
                                        @foreach($anios as $anio)
                                            <option value="{{ $anio }}" {{ old('criterios.0.anio', date('Y')) == $anio ? 'selected' : '' }}>
                                                {{ $anio }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Bimestres *</label>
                                    <div class="border rounded p-3 bg-light">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input bimestre-checkbox"
                                                           type="checkbox"
                                                           name="criterios[0][bimestres][]"
                                                           value="1"
                                                           id="bimestre1_0"
                                                           {{ is_array(old('criterios.0.bimestres')) && in_array('1', old('criterios.0.bimestres')) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="bimestre1_0">
                                                        Bimestre 1
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input bimestre-checkbox"
                                                           type="checkbox"
                                                           name="criterios[0][bimestres][]"
                                                           value="2"
                                                           id="bimestre2_0"
                                                           {{ is_array(old('criterios.0.bimestres')) && in_array('2', old('criterios.0.bimestres')) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="bimestre2_0">
                                                        Bimestre 2
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input bimestre-checkbox"
                                                           type="checkbox"
                                                           name="criterios[0][bimestres][]"
                                                           value="3"
                                                           id="bimestre3_0"
                                                           {{ is_array(old('criterios.0.bimestres')) && in_array('3', old('criterios.0.bimestres')) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="bimestre3_0">
                                                        Bimestre 3
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input bimestre-checkbox"
                                                           type="checkbox"
                                                           name="criterios[0][bimestres][]"
                                                           value="4"
                                                           id="bimestre4_0"
                                                           {{ is_array(old('criterios.0.bimestres')) && in_array('4', old('criterios.0.bimestres')) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="bimestre4_0">
                                                        Bimestre 4
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary select-all-bimestres" data-index="0">
                                                <i class="bi bi-check-all me-1"></i> Seleccionar todos
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary clear-bimestres" data-index="0">
                                                <i class="bi bi-x-circle me-1"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-none" id="bimestre-error-0">
                                        Por favor selecciona al menos un bimestre.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grados *</label>
                                    <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                        <div class="row">
                                            @php
                                                $gradosPorNivel = $grados->groupBy('nivel');
                                            @endphp
                                            @foreach($gradosPorNivel as $nivel => $gradosNivel)
                                                <div class="col-md-6 mb-2">
                                                    <h6 class="text-primary small">{{ $nivel }}</h6>
                                                    @foreach($gradosNivel as $grado)
                                                        <div class="form-check">
                                                            <input class="form-check-input grado-checkbox"
                                                                   type="checkbox"
                                                                   name="criterios[0][grados][]"
                                                                   value="{{ $grado->id }}"
                                                                   id="grado_{{ $grado->id }}_0"
                                                                   {{ is_array(old('criterios.0.grados')) && in_array($grado->id, old('criterios.0.grados')) ? 'checked' : '' }}>
                                                            <label class="form-check-label small" for="grado_{{ $grado->id }}_0">
                                                                {{ $grado->grado }}° "{{ $grado->seccion }}"
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary select-all-grados" data-index="0">
                                                <i class="bi bi-check-all me-1"></i> Seleccionar todos
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary clear-grados" data-index="0">
                                                <i class="bi bi-x-circle me-1"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-none" id="grado-error-0">
                                        Por favor selecciona al menos un grado.
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="criterios[0][descripcion]" class="form-label">Descripción</label>
                                    <textarea class="form-control"
                                              name="criterios[0][descripcion]"
                                              rows="2">{{ old('criterios.0.descripcion') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Botón para agregar más criterios --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <button type="button" id="add-criterio" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> Agregar Otro Criterio
                        </button>
                    </div>
                </div>

                {{-- Botones de acción --}}
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Todos los Criterios
                        </button>
                        <a href="{{ route('materiacriterio.index') }}" class="btn btn-secondary">
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
    let criterioCount = 1;
    const container = document.getElementById('criterios-container');
    const addButton = document.getElementById('add-criterio');
    const materiaSelect = document.getElementById('materia_id');
    const competenciaSelect = document.getElementById('materia_competencia_id');

    // Cargar competencias cuando se selecciona una materia
    materiaSelect.addEventListener('change', function() {
        const materiaId = this.value;

        if (materiaId) {
            competenciaSelect.disabled = false;

            // Limpiar opciones anteriores
            competenciaSelect.innerHTML = '<option value="">Cargando competencias...</option>';

            // Hacer petición AJAX para obtener competencias
            fetch(`/api/competencias-por-materia/${materiaId}`)
                .then(response => response.json())
                .then(data => {
                    competenciaSelect.innerHTML = '<option value="">Seleccionar competencia</option>';
                    data.forEach(competencia => {
                        const option = document.createElement('option');
                        option.value = competencia.id;
                        option.textContent = competencia.nombre;
                        competenciaSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    competenciaSelect.innerHTML = '<option value="">Error al cargar competencias</option>';
                });
        } else {
            competenciaSelect.disabled = true;
            competenciaSelect.innerHTML = '<option value="">Primero selecciona una materia</option>';
        }
    });

    // Función para agregar nuevo criterio
    addButton.addEventListener('click', function() {
        const newIndex = criterioCount;
        const newCriterio = document.createElement('div');
        newCriterio.className = 'criterio-item card mb-3';
        newCriterio.innerHTML = `
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Criterio #${newIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-danger remove-criterio">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="criterios[${newIndex}][nombre]" class="form-label">Nombre del Criterio *</label>
                        <input type="text" class="form-control"
                               name="criterios[${newIndex}][nombre]"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label for="criterios[${newIndex}][anio]" class="form-label">Año *</label>
                        <select class="form-select" name="criterios[${newIndex}][anio]" required>
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}" {{ $anio == date('Y') ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Bimestres *</label>
                        <div class="border rounded p-3 bg-light">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input bimestre-checkbox"
                                               type="checkbox"
                                               name="criterios[${newIndex}][bimestres][]"
                                               value="1"
                                               id="bimestre1_${newIndex}">
                                        <label class="form-check-label" for="bimestre1_${newIndex}">
                                            Bimestre 1
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input bimestre-checkbox"
                                               type="checkbox"
                                               name="criterios[${newIndex}][bimestres][]"
                                               value="2"
                                               id="bimestre2_${newIndex}">
                                        <label class="form-check-label" for="bimestre2_${newIndex}">
                                            Bimestre 2
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input bimestre-checkbox"
                                               type="checkbox"
                                               name="criterios[${newIndex}][bimestres][]"
                                               value="3"
                                               id="bimestre3_${newIndex}">
                                        <label class="form-check-label" for="bimestre3_${newIndex}">
                                            Bimestre 3
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input bimestre-checkbox"
                                               type="checkbox"
                                               name="criterios[${newIndex}][bimestres][]"
                                               value="4"
                                               id="bimestre4_${newIndex}">
                                        <label class="form-check-label" for="bimestre4_${newIndex}">
                                            Bimestre 4
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary select-all-bimestres" data-index="${newIndex}">
                                    <i class="bi bi-check-all me-1"></i> Seleccionar todos
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary clear-bimestres" data-index="${newIndex}">
                                    <i class="bi bi-x-circle me-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                        <div class="invalid-feedback d-none" id="bimestre-error-${newIndex}">
                            Por favor selecciona al menos un bimestre.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Grados *</label>
                        <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                            <div class="row">
                                @php
                                    $gradosPorNivel = $grados->groupBy('nivel');
                                @endphp
                                @foreach($gradosPorNivel as $nivel => $gradosNivel)
                                    <div class="col-md-6 mb-2">
                                        <h6 class="text-primary small">{{ $nivel }}</h6>
                                        @foreach($gradosNivel as $grado)
                                            <div class="form-check">
                                                <input class="form-check-input grado-checkbox"
                                                       type="checkbox"
                                                       name="criterios[${newIndex}][grados][]"
                                                       value="{{ $grado->id }}"
                                                       id="grado_{{ $grado->id }}_${newIndex}">
                                                <label class="form-check-label small" for="grado_{{ $grado->id }}_${newIndex}">
                                                    {{ $grado->grado }}° "{{ $grado->seccion }}"
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary select-all-grados" data-index="${newIndex}">
                                    <i class="bi bi-check-all me-1"></i> Seleccionar todos
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary clear-grados" data-index="${newIndex}">
                                    <i class="bi bi-x-circle me-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                        <div class="invalid-feedback d-none" id="grado-error-${newIndex}">
                            Por favor selecciona al menos un grado.
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="criterios[${newIndex}][descripcion]" class="form-label">Descripción</label>
                        <textarea class="form-control"
                                  name="criterios[${newIndex}][descripcion]"
                                  rows="2"></textarea>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(newCriterio);
        criterioCount++;

        // Inicializar eventos para los nuevos botones
        initializeBimestreButtons(newIndex);
        initializeGradoButtons(newIndex);
        // Habilitar botones de eliminar
        updateRemoveButtons();
    });

    // Función para inicializar botones de bimestres
    function initializeBimestreButtons(index) {
        // Seleccionar todos los bimestres
        document.querySelector(`.select-all-bimestres[data-index="${index}"]`).addEventListener('click', function() {
            const checkboxes = document.querySelectorAll(`.bimestre-checkbox[name="criterios[${index}][bimestres][]"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });

        // Limpiar todos los bimestres
        document.querySelector(`.clear-bimestres[data-index="${index}"]`).addEventListener('click', function() {
            const checkboxes = document.querySelectorAll(`.bimestre-checkbox[name="criterios[${index}][bimestres][]"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    }

    // Función para inicializar botones de grados
    function initializeGradoButtons(index) {
        // Seleccionar todos los grados
        document.querySelector(`.select-all-grados[data-index="${index}"]`).addEventListener('click', function() {
            const checkboxes = document.querySelectorAll(`.grado-checkbox[name="criterios[${index}][grados][]"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });

        // Limpiar todos los grados
        document.querySelector(`.clear-grados[data-index="${index}"]`).addEventListener('click', function() {
            const checkboxes = document.querySelectorAll(`.grado-checkbox[name="criterios[${index}][grados][]"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    }

    // Inicializar botones para el primer criterio
    initializeBimestreButtons(0);
    initializeGradoButtons(0);

    // Función para actualizar botones de eliminar
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-criterio');
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
        document.querySelectorAll('.remove-criterio').forEach(button => {
            button.addEventListener('click', function() {
                if (document.querySelectorAll('.criterio-item').length > 1) {
                    this.closest('.criterio-item').remove();
                    updateCriterioNumbers();
                    updateRemoveButtons();
                }
            });
        });
    }

    // Función para actualizar los números de los criterios
    function updateCriterioNumbers() {
        document.querySelectorAll('.criterio-item').forEach((item, index) => {
            const header = item.querySelector('.card-header h6');
            header.textContent = `Criterio #${index + 1}`;

            // Actualizar los índices en los names de los inputs
            const inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/criterios\[\d+\]/, `criterios[${index}]`);
                    input.setAttribute('name', newName);
                }
            });

            // Actualizar los IDs y data-index de los checkboxes y botones
            const bimestreCheckboxes = item.querySelectorAll('.bimestre-checkbox');
            bimestreCheckboxes.forEach((checkbox, checkboxIndex) => {
                const newId = `bimestre${checkboxIndex + 1}_${index}`;
                checkbox.setAttribute('id', newId);
                checkbox.nextElementSibling.setAttribute('for', newId);
            });

            const gradoCheckboxes = item.querySelectorAll('.grado-checkbox');
            gradoCheckboxes.forEach(checkbox => {
                const oldId = checkbox.getAttribute('id');
                const newId = oldId.replace(/_\d+$/, `_${index}`);
                checkbox.setAttribute('id', newId);
                checkbox.nextElementSibling.setAttribute('for', newId);
            });

            const selectAllBimestres = item.querySelector('.select-all-bimestres');
            const clearBimestres = item.querySelector('.clear-bimestres');
            const selectAllGrados = item.querySelector('.select-all-grados');
            const clearGrados = item.querySelector('.clear-grados');

            if (selectAllBimestres) selectAllBimestres.setAttribute('data-index', index);
            if (clearBimestres) clearBimestres.setAttribute('data-index', index);
            if (selectAllGrados) selectAllGrados.setAttribute('data-index', index);
            if (clearGrados) clearGrados.setAttribute('data-index', index);

            // Actualizar los IDs de los mensajes de error
            const bimestreError = item.querySelector('#bimestre-error-\\d+');
            const gradoError = item.querySelector('#grado-error-\\d+');

            if (bimestreError) bimestreError.setAttribute('id', `bimestre-error-${index}`);
            if (gradoError) gradoError.setAttribute('id', `grado-error-${index}`);
        });
        criterioCount = document.querySelectorAll('.criterio-item').length;
    }

    // Validación del formulario
    document.getElementById('criterioForm').addEventListener('submit', function(e) {
        const materiaSelect = document.getElementById('materia_id');
        const competenciaSelect = document.getElementById('materia_competencia_id');

        if (!materiaSelect.value) {
            e.preventDefault();
            alert('Por favor selecciona una materia.');
            materiaSelect.focus();
            return false;
        }

        if (!competenciaSelect.value) {
            e.preventDefault();
            alert('Por favor selecciona una competencia.');
            competenciaSelect.focus();
            return false;
        }

        // Validar que al menos un criterio tenga nombre
        const criterioNombres = document.querySelectorAll('input[name^="criterios"][name$="[nombre]"]');
        let hasValidCriterio = false;
        criterioNombres.forEach(input => {
            if (input.value.trim() !== '') {
                hasValidCriterio = true;
            }
        });

        if (!hasValidCriterio) {
            e.preventDefault();
            alert('Por favor ingresa al menos un criterio con nombre.');
            return false;
        }

        // Validar que cada criterio tenga al menos un bimestre seleccionado
        let allHaveBimestres = true;
        document.querySelectorAll('.criterio-item').forEach((item, index) => {
            const bimestreCheckboxes = item.querySelectorAll('.bimestre-checkbox:checked');
            const errorDiv = document.getElementById(`bimestre-error-${index}`);

            if (bimestreCheckboxes.length === 0) {
                allHaveBimestres = false;
                if (errorDiv) {
                    errorDiv.classList.remove('d-none');
                }
            } else {
                if (errorDiv) {
                    errorDiv.classList.add('d-none');
                }
            }
        });

        if (!allHaveBimestres) {
            e.preventDefault();
            alert('Por favor selecciona al menos un bimestre para cada criterio.');
            return false;
        }

        // Validar que cada criterio tenga al menos un grado seleccionado
        let allHaveGrados = true;
        document.querySelectorAll('.criterio-item').forEach((item, index) => {
            const gradoCheckboxes = item.querySelectorAll('.grado-checkbox:checked');
            const errorDiv = document.getElementById(`grado-error-${index}`);

            if (gradoCheckboxes.length === 0) {
                allHaveGrados = false;
                if (errorDiv) {
                    errorDiv.classList.remove('d-none');
                }
            } else {
                if (errorDiv) {
                    errorDiv.classList.add('d-none');
                }
            }
        });

        if (!allHaveGrados) {
            e.preventDefault();
            alert('Por favor selecciona al menos un grado para cada criterio.');
            return false;
        }
    });

    // Inicializar botones de eliminar
    updateRemoveButtons();

    // Si hay una materia seleccionada en old(), cargar sus competencias
    @if(old('materia_id'))
        materiaSelect.dispatchEvent(new Event('change'));
        // Esperar un momento para seleccionar la competencia
        setTimeout(() => {
            competenciaSelect.value = "{{ old('materia_competencia_id') }}";
        }, 500);
    @endif
});
</script>

<style>
.criterio-item {
    border-left: 4px solid #1cc88a;
}

.criterio-item .card-header {
    background-color: #f8f9fa !important;
}

.remove-criterio:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.form-check {
    margin-bottom: 0.3rem;
}

.border.rounded {
    border-color: #dee2e6 !important;
}

.text-primary.small {
    font-weight: 600;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.2rem;
}

/* Scroll personalizado para el contenedor de grados */
.bg-light[style*="max-height"]::-webkit-scrollbar {
    width: 6px;
}

.bg-light[style*="max-height"]::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.bg-light[style*="max-height"]::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.bg-light[style*="max-height"]::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
@endsection
