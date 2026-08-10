@extends('layouts.app')
@section('title', 'Crear Criterios de Evaluación')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle me-2"></i> Crear Criterios de Evaluación
                    </h5>
                    <a href="{{ route('materiacriterio.index') }}" class="btn btn-light btn-sm text-primary">
                        <i class="bi bi-arrow-left me-1"></i> Volver a Criterios
                    </a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

            <form action="{{ route('materiacriterio.store') }}" method="POST" id="criterioForm">
                @csrf

                {{-- Selección de Período (primero) --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="form-label text-danger">Período Escolar *</label>
                        <select id="periodo_id" name="periodo_id" class="form-select" required>
                            <option value="">Seleccione un período escolar</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}" {{ old('periodo_id') == $periodo->id ? 'selected' : '' }}>
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
                                <div class="col-md-6">
                                    <label for="criterios[0][materia_id]" class="form-label">Materia *</label>
                                    <select class="form-select materia-select @error('criterios.0.materia_id') is-invalid @enderror"
                                            name="criterios[0][materia_id]" required>
                                        <option value="">Seleccionar materia</option>
                                        @foreach($materias as $materia)
                                            <option value="{{ $materia->id }}" {{ old('criterios.0.materia_id') == $materia->id ? 'selected' : '' }}>
                                                {{ $materia->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('criterios.0.materia_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="criterios[0][materia_competencia_id]" class="form-label">Competencia *</label>
                                    <select class="form-select competencia-select @error('criterios.0.materia_competencia_id') is-invalid @enderror"
                                            name="criterios[0][materia_competencia_id]" required disabled>
                                        <option value="">Primero selecciona una materia</option>
                                    </select>
                                    @error('criterios.0.materia_competencia_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="criterios[0][nombre]" class="form-label">Nombre del Criterio *</label>
                                    <input type="text" class="form-control @error('criterios.0.nombre') is-invalid @enderror"
                                           name="criterios[0][nombre]"
                                           value="{{ old('criterios.0.nombre') }}"
                                           required>
                                    @error('criterios.0.nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                        <small class="text-muted ms-2">El nuevo criterio copia la materia, competencia, bimestres y grados del último grupo.</small>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let criterioCount = 1;
    const container = document.getElementById('criterios-container');
    const addButton = document.getElementById('add-criterio');
    const periodoSelect = document.getElementById('periodo_id');

    let bimestresGlobales = []; // Almacenar bimestres cargados

    // ===== Delegación de eventos sobre el contenedor =====

    // Cargar competencias cuando se selecciona una materia en cualquier grupo
    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('materia-select')) {
            cargarCompetencias(e.target);
        }
    });

    // Botones select-all / limpiar y eliminar grupo
    container.addEventListener('click', function(e) {
        const selectorBtn = e.target.closest('.select-all-bimestres, .clear-bimestres, .select-all-grados, .clear-grados');
        if (selectorBtn) {
            const item = selectorBtn.closest('.criterio-item');
            const selectAll = selectorBtn.classList.contains('select-all-bimestres')
                || selectorBtn.classList.contains('select-all-grados');
            const esBimestre = selectorBtn.classList.contains('select-all-bimestres')
                || selectorBtn.classList.contains('clear-bimestres');
            item.querySelectorAll(esBimestre ? '.bimestre-checkbox' : '.grado-checkbox')
                .forEach(cb => cb.checked = selectAll);
            return;
        }

        const removeBtn = e.target.closest('.remove-criterio');
        if (removeBtn && container.querySelectorAll('.criterio-item').length > 1) {
            removeBtn.closest('.criterio-item').remove();
            updateCriterioNumbers();
            updateRemoveButtons();
        }
    });

    // Cargar competencias de una materia específica
    function cargarCompetencias(materiaSelect) {
        const item = materiaSelect.closest('.criterio-item');
        const competenciaSelect = item.querySelector('.competencia-select');
        const materiaId = materiaSelect.value;

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
                .catch(() => {
                    competenciaSelect.innerHTML = '<option value="">Error al cargar competencias</option>';
                });
        } else {
            competenciaSelect.disabled = true;
            competenciaSelect.innerHTML = '<option value="">Primero selecciona una materia</option>';
        }
    }

    // Cargar bimestres del período seleccionado
    function cargarBimestres(periodoId, callback) {
        if (!periodoId) {
            bimestresGlobales = [];
            updateAllBimestresContainers();
            return;
        }

        document.querySelectorAll('[id^="bimestres-container-"]').forEach(containerEl => {
            containerEl.innerHTML = `
                <div class="text-muted text-center py-2">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Cargando bimestres...
                </div>
            `;
        });

        fetch(`/materiacriterio/bimestres/${periodoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                bimestresGlobales = data;
                updateAllBimestresContainers();
                if (typeof callback === 'function') {
                    callback();
                }
            })
            .catch(() => {
                document.querySelectorAll('[id^="bimestres-container-"]').forEach(containerEl => {
                    containerEl.innerHTML = `
                        <div class="text-danger text-center py-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Error al cargar los bimestres. Por favor, recarga la página.
                        </div>
                    `;
                });
            });
    }

    // Cargar bimestres cuando cambia el período
    periodoSelect.addEventListener('change', function() {
        cargarBimestres(this.value);
    });

    // Actualizar todos los contenedores de bimestres
    function updateAllBimestresContainers() {
        container.querySelectorAll('.criterio-item').forEach((item, index) => {
            updateBimestresContainer(item, index);
        });
    }

    // Actualizar el contenedor de bimestres de un criterio específico
    function updateBimestresContainer(criterioItem, index) {
        const containerEl = criterioItem.querySelector(`#bimestres-container-${index}`);
        if (!containerEl) return;

        if (bimestresGlobales.length === 0) {
            containerEl.innerHTML = `
                <div class="text-muted text-center py-2">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Selecciona un período escolar primero
                </div>
            `;
            return;
        }

        let html = '<div class="row">';
        bimestresGlobales.forEach(bimestre => {
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

        containerEl.innerHTML = html;
    }

    // Formatear fecha
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
    }

    // Agregar nuevo criterio (clona el último grupo copiando materia, competencia, bimestres y grados)
    addButton.addEventListener('click', function() {
        const items = container.querySelectorAll('.criterio-item');
        const lastItem = items[items.length - 1];
        const newItem = lastItem.cloneNode(true);

        const nombreInput = newItem.querySelector('input[name$="[nombre]"]');
        const descripcionTextarea = newItem.querySelector('textarea[name$="[descripcion]"]');
        if (nombreInput) nombreInput.value = '';
        if (descripcionTextarea) descripcionTextarea.value = '';

        const sourceMateria = lastItem.querySelector('.materia-select');
        const sourceCompetencia = lastItem.querySelector('.competencia-select');
        const newMateria = newItem.querySelector('.materia-select');
        const newCompetencia = newItem.querySelector('.competencia-select');

        if (sourceMateria && newMateria) newMateria.value = sourceMateria.value;
        if (sourceCompetencia && newCompetencia) newCompetencia.value = sourceCompetencia.value;

        newItem.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        newItem.querySelectorAll('.invalid-feedback:not(.d-none)').forEach(el => el.classList.add('d-none'));

        container.appendChild(newItem);
        updateCriterioNumbers();
        updateRemoveButtons();
    });

    // Actualizar botones de eliminar (solo se puede eliminar si hay más de un grupo)
    function updateRemoveButtons() {
        const items = container.querySelectorAll('.criterio-item');
        items.forEach(item => {
            const btn = item.querySelector('.remove-criterio');
            if (btn) {
                btn.disabled = items.length <= 1;
            }
        });
    }

    // Renumerar names, IDs e índices de los grupos
    function updateCriterioNumbers() {
        const items = container.querySelectorAll('.criterio-item');
        items.forEach((item, index) => {
            const header = item.querySelector('.card-header h6');
            if (header) {
                header.textContent = `Criterio #${index + 1}`;
            }

            item.querySelectorAll('input, select, textarea').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/criterios\[\d+\]/, `criterios[${index}]`));
                }
            });

            item.querySelectorAll('.bimestre-checkbox, .grado-checkbox').forEach(checkbox => {
                const oldId = checkbox.getAttribute('id');
                if (oldId) {
                    const newId = oldId.replace(/_\d+$/, `_${index}`);
                    checkbox.setAttribute('id', newId);
                    if (checkbox.nextElementSibling) {
                        checkbox.nextElementSibling.setAttribute('for', newId);
                    }
                }
            });

            item.querySelectorAll('.select-all-bimestres, .clear-bimestres, .select-all-grados, .clear-grados')
                .forEach(btn => btn.setAttribute('data-index', index));

            const bimestresContainer = item.querySelector('[id^="bimestres-container-"]');
            if (bimestresContainer) {
                bimestresContainer.setAttribute('id', `bimestres-container-${index}`);
            }

            const bimestreError = item.querySelector('[id^="bimestre-error-"]');
            if (bimestreError) {
                bimestreError.setAttribute('id', `bimestre-error-${index}`);
            }

            const gradoError = item.querySelector('[id^="grado-error-"]');
            if (gradoError) {
                gradoError.setAttribute('id', `grado-error-${index}`);
            }
        });
        criterioCount = items.length;
    }

    // Validación del formulario
    document.getElementById('criterioForm').addEventListener('submit', function(e) {
        if (!periodoSelect.value) {
            e.preventDefault();
            alert('Por favor selecciona un período escolar.');
            return false;
        }

        let allValid = true;
        container.querySelectorAll('.criterio-item').forEach(item => {
            const materia = item.querySelector('.materia-select');
            const competencia = item.querySelector('.competencia-select');
            const nombre = item.querySelector('input[name$="[nombre]"]');
            const bimestres = item.querySelectorAll('.bimestre-checkbox:checked');
            const grados = item.querySelectorAll('.grado-checkbox:checked');

            if (!materia.value || !competencia.value || !nombre.value.trim()
                || bimestres.length === 0 || grados.length === 0) {
                allValid = false;
            }
        });

        if (!allValid) {
            e.preventDefault();
            alert('Cada criterio requiere materia, competencia, nombre, al menos un bimestre y un grado.');
            return false;
        }
    });

    // Inicializar
    updateRemoveButtons();

    // Restaurar valores de old() en el primer grupo tras un error de validación
    const oldPeriodoId = @json(old('periodo_id'));
    const oldMateria0 = @json(old('criterios.0.materia_id'));
    const oldCompetencia0 = @json(old('criterios.0.materia_competencia_id'));
    const oldBimestres0 = @json(old('criterios.0.periodos_bimestres'));

    if (oldPeriodoId) {
        periodoSelect.value = oldPeriodoId;
        cargarBimestres(oldPeriodoId, function() {
            if (Array.isArray(oldBimestres0)) {
                const firstItem = container.querySelector('.criterio-item');
                firstItem.querySelectorAll('.bimestre-checkbox').forEach(cb => {
                    if (oldBimestres0.includes(cb.value)) {
                        cb.checked = true;
                    }
                });
            }
        });
    }

    if (oldMateria0) {
        const firstItem = container.querySelector('.criterio-item');
        const materiaSelect = firstItem.querySelector('.materia-select');
        materiaSelect.value = oldMateria0;
        cargarCompetencias(materiaSelect);
        setTimeout(() => {
            firstItem.querySelector('.competencia-select').value = oldCompetencia0;
        }, 400);
    }
});
</script>
@endsection
