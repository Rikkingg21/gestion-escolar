@extends('layouts.app')
@section('title', 'Crear Criterios de Evaluación')
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
        <ul class="mb-0">
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

                {{-- Selección de Período --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="form-label text-danger">Período Escolar *</label>
                        <select id="periodo_id" name="periodo_id" class="form-select" required>
                            <option value="">Seleccione un período escolar</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}">
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Selecciona el período escolar para todos los criterios</small>
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
                                <div class="col-md-12">
                                    <label for="criterios[0][nombre]" class="form-label">Nombre del Criterio *</label>
                                    <input type="text" class="form-control"
                                           name="criterios[0][nombre]"
                                           value="{{ old('criterios.0.nombre') }}"
                                           required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Bimestres *</label>
                                    <div class="border rounded p-3 bg-light" id="bimestres-container-0">
                                        <div class="text-muted text-center py-2">
                                            <i class="bi bi-hourglass-split me-1"></i>
                                            Selecciona un período escolar primero
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-none" id="bimestre-error-0">
                                        Por favor selecciona al menos un bimestre.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grados *</label>
                                    <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
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
    const periodoSelect = document.getElementById('periodo_id');

    let bimestresGlobales = []; // Almacenar bimestres cargados

    // Cargar competencias cuando se selecciona una materia
    materiaSelect.addEventListener('change', function() {
        const materiaId = this.value;

        if (materiaId) {
            competenciaSelect.disabled = false;
            competenciaSelect.innerHTML = '<option value="">Cargando competencias...</option>';

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

    // Cargar bimestres cuando se selecciona un período
    periodoSelect.addEventListener('change', function() {
        const periodoId = this.value;

        if (periodoId) {
            // Mostrar indicador de carga
            document.querySelectorAll('[id^="bimestres-container-"]').forEach(container => {
                container.innerHTML = `
                    <div class="text-muted text-center py-2">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Cargando bimestres...
                    </div>
                `;
            });

            // Hacer petición AJAX al controlador
            fetch(`/materiacriterio/bimestres/${periodoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    bimestresGlobales = data;
                    // Actualizar todos los contenedores de bimestres
                    updateAllBimestresContainers();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.querySelectorAll('[id^="bimestres-container-"]').forEach(container => {
                        container.innerHTML = `
                            <div class="text-danger text-center py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Error al cargar los bimestres. Por favor, recarga la página.
                            </div>
                        `;
                    });
                });
        } else {
            bimestresGlobales = [];
            updateAllBimestresContainers();
        }
    });

    // Función para actualizar todos los contenedores de bimestres
    function updateAllBimestresContainers() {
        document.querySelectorAll('.criterio-item').forEach((item, index) => {
            updateBimestresContainer(item, index);
        });
    }

    // Función para actualizar el contenedor de bimestres de un criterio específico
    function updateBimestresContainer(criterioItem, index) {
        const container = criterioItem.querySelector(`#bimestres-container-${index}`);
        if (!container) return;

        if (bimestresGlobales.length === 0) {
            container.innerHTML = `
                <div class="text-muted text-center py-2">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Selecciona un período escolar primero
                </div>
            `;
            return;
        }

        // Generar checkboxes de bimestres
        let html = '<div class="row">';
        bimestresGlobales.forEach((bimestre, bimestreIndex) => {
            html += `
                <div class="col-md-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input bimestre-checkbox"
                               type="checkbox"
                               name="criterios[${index}][periodos_bimestres][]"
                               value="${bimestre.id}"
                               id="bimestre_${bimestre.id}_${index}">
                        <label class="form-check-label" for="bimestre_${bimestre.id}_${index}">
                            <strong>${bimestre.sigla}</strong> ${bimestre.bimestre}
                            <br><small class="text-muted">
                                (${formatDate(bimestre.fecha_inicio)} - ${formatDate(bimestre.fecha_fin)})
                            </small>
                        </label>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        html += `
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary select-all-bimestres" data-index="${index}">
                    <i class="bi bi-check-all me-1"></i> Seleccionar todos
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary clear-bimestres" data-index="${index}">
                    <i class="bi bi-x-circle me-1"></i> Limpiar
                </button>
            </div>
        `;

        container.innerHTML = html;

        // Inicializar eventos de los botones de bimestres
        initializeBimestreButtons(index);
    }

    // Función para formatear fecha
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
    }

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
                    <div class="col-md-12">
                        <label for="criterios[${newIndex}][nombre]" class="form-label">Nombre del Criterio *</label>
                        <input type="text" class="form-control"
                               name="criterios[${newIndex}][nombre]"
                               required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Bimestres *</label>
                        <div class="border rounded p-3 bg-light" id="bimestres-container-${newIndex}">
                            <div class="text-muted text-center py-2">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Selecciona un período escolar primero
                            </div>
                        </div>
                        <div class="invalid-feedback d-none" id="bimestre-error-${newIndex}">
                            Por favor selecciona al menos un bimestre.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Grados *</label>
                        <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
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

        // Actualizar el contenedor de bimestres para el nuevo criterio
        if (bimestresGlobales.length > 0) {
            updateBimestresContainer(newCriterio, newIndex);
        }

        // Inicializar eventos para los nuevos botones
        initializeGradoButtons(newIndex);
        updateRemoveButtons();
    });

    // Función para inicializar botones de bimestres
    function initializeBimestreButtons(index) {
        const selectAllBtn = document.querySelector(`.select-all-bimestres[data-index="${index}"]`);
        const clearBtn = document.querySelector(`.clear-bimestres[data-index="${index}"]`);

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll(`.bimestre-checkbox[name="criterios[${index}][periodos_bimestres][]"]`);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll(`.bimestre-checkbox[name="criterios[${index}][periodos_bimestres][]"]`);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
        }
    }

    // Función para inicializar botones de grados
    function initializeGradoButtons(index) {
        const selectAllBtn = document.querySelector(`.select-all-grados[data-index="${index}"]`);
        const clearBtn = document.querySelector(`.clear-grados[data-index="${index}"]`);

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll(`.grado-checkbox[name="criterios[${index}][grados][]"]`);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll(`.grado-checkbox[name="criterios[${index}][grados][]"]`);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
        }
    }

    // Función para actualizar botones de eliminar
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-criterio');
        removeButtons.forEach((button, idx) => {
            if (removeButtons.length > 1) {
                button.disabled = false;
            } else {
                button.disabled = true;
            }

            button.replaceWith(button.cloneNode(true));
        });

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

            // Actualizar names de inputs
            const inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/criterios\[\d+\]/, `criterios[${index}]`);
                    input.setAttribute('name', newName);
                }
            });

            // Actualizar IDs de bimestres
            const bimestreCheckboxes = item.querySelectorAll('.bimestre-checkbox');
            bimestreCheckboxes.forEach(checkbox => {
                const oldId = checkbox.getAttribute('id');
                const newId = oldId.replace(/_\d+$/, `_${index}`);
                checkbox.setAttribute('id', newId);
                if (checkbox.nextElementSibling) {
                    checkbox.nextElementSibling.setAttribute('for', newId);
                }
            });

            // Actualizar IDs de grados
            const gradoCheckboxes = item.querySelectorAll('.grado-checkbox');
            gradoCheckboxes.forEach(checkbox => {
                const oldId = checkbox.getAttribute('id');
                const newId = oldId.replace(/_\d+$/, `_${index}`);
                checkbox.setAttribute('id', newId);
                if (checkbox.nextElementSibling) {
                    checkbox.nextElementSibling.setAttribute('for', newId);
                }
            });

            // Actualizar data-index de botones
            const selectAllBimestres = item.querySelector('.select-all-bimestres');
            const clearBimestres = item.querySelector('.clear-bimestres');
            const selectAllGrados = item.querySelector('.select-all-grados');
            const clearGrados = item.querySelector('.clear-grados');

            if (selectAllBimestres) selectAllBimestres.setAttribute('data-index', index);
            if (clearBimestres) clearBimestres.setAttribute('data-index', index);
            if (selectAllGrados) selectAllGrados.setAttribute('data-index', index);
            if (clearGrados) clearGrados.setAttribute('data-index', index);

            // Actualizar ID del contenedor de bimestres
            const bimestresContainer = item.querySelector('[id^="bimestres-container-"]');
            if (bimestresContainer) {
                bimestresContainer.setAttribute('id', `bimestres-container-${index}`);
            }
        });
        criterioCount = document.querySelectorAll('.criterio-item').length;
    }

    // Validación del formulario
    document.getElementById('criterioForm').addEventListener('submit', function(e) {
        if (!materiaSelect.value) {
            e.preventDefault();
            alert('Por favor selecciona una materia.');
            return false;
        }

        if (!competenciaSelect.value) {
            e.preventDefault();
            alert('Por favor selecciona una competencia.');
            return false;
        }

        if (!periodoSelect.value) {
            e.preventDefault();
            alert('Por favor selecciona un período escolar.');
            return false;
        }

        let hasValidCriterio = false;
        const criterioNombres = document.querySelectorAll('input[name^="criterios"][name$="[nombre]"]');
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

        // Validar bimestres
        let allHaveBimestres = true;
        document.querySelectorAll('.criterio-item').forEach((item, index) => {
            const bimestreCheckboxes = item.querySelectorAll('.bimestre-checkbox:checked');
            const errorDiv = document.getElementById(`bimestre-error-${index}`);

            if (bimestreCheckboxes.length === 0) {
                allHaveBimestres = false;
                if (errorDiv) errorDiv.classList.remove('d-none');
            } else {
                if (errorDiv) errorDiv.classList.add('d-none');
            }
        });

        if (!allHaveBimestres) {
            e.preventDefault();
            alert('Por favor selecciona al menos un bimestre para cada criterio.');
            return false;
        }

        // Validar grados
        let allHaveGrados = true;
        document.querySelectorAll('.criterio-item').forEach((item, index) => {
            const gradoCheckboxes = item.querySelectorAll('.grado-checkbox:checked');
            const errorDiv = document.getElementById(`grado-error-${index}`);

            if (gradoCheckboxes.length === 0) {
                allHaveGrados = false;
                if (errorDiv) errorDiv.classList.remove('d-none');
            } else {
                if (errorDiv) errorDiv.classList.add('d-none');
            }
        });

        if (!allHaveGrados) {
            e.preventDefault();
            alert('Por favor selecciona al menos un grado para cada criterio.');
            return false;
        }
    });

    // Inicializar
    initializeGradoButtons(0);
    updateRemoveButtons();

    // Si hay un período seleccionado en old(), cargar bimestres
    @if(old('periodo_id'))
        periodoSelect.value = "{{ old('periodo_id') }}";
        periodoSelect.dispatchEvent(new Event('change'));
    @endif

    // Si hay una materia seleccionada en old(), cargar competencias
    @if(old('materia_id'))
        materiaSelect.dispatchEvent(new Event('change'));
        setTimeout(() => {
            competenciaSelect.value = "{{ old('materia_competencia_id') }}";
        }, 500);
    @endif
});
</script>
@endsection
