@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-person-rolodex me-2"></i> Evaluación de Estudiantes - {{ $grado->grado }}° {{ $grado->seccion }}
        </h1>
        <a href="{{ route('grado.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i> Volver a Grados
        </a>
    </div>

    <!-- Selector de Año y Formato -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">Configuración</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <form method="GET" action="{{ route('grado.estudiantes', $grado->id) }}" id="formAnio">
                        <label for="anio" class="form-label"><strong>Año:</strong></label>
                        <select name="anio" id="anio" class="form-select">
                            @foreach($aniosDisponibles as $anio)
                                <option value="{{ $anio }}"
                                    {{ $anioSeleccionado == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Formato de Notas:</strong></label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="formatoNotas" style="width: 3em; height: 1.5em;">
                        <label class="form-check-label" id="formatoLabel">
                            <span class="badge bg-primary">1-4</span>
                        </label>
                    </div>
                    <small class="text-muted">Cuantitativo (1-4) / Cualitativo (C,B,A,AD)</small>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="bi bi-info-circle"></i>
                            Período Académico: <strong>{{ $periodoAcademico->nombre ?? 'N/A' }}</strong>
                            @if($periodoRecuperacion)
                                | Período de Recuperación: <strong>{{ $periodoRecuperacion->nombre }}</strong>
                            @else
                                | <span class="text-warning">No hay período de recuperación configurado</span>
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario para ascender estudiantes -->
    <form id="ascenderForm" action="{{ route('grado.estudiantesupdategrado', $grado->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="periodo_id" value="{{ $periodoAcademico->id }}">
        <input type="hidden" name="anio" value="{{ $anioSeleccionado }}">

        <!-- SECCIÓN: ESTUDIANTES MATRICULADOS -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="bi bi-journal-check me-2"></i>
                    Estudiantes Matriculados en {{ $anioSeleccionado }} - Rendimiento Académico
                    <span class="badge bg-light text-dark ms-2">{{ $estudiantesMatriculados->count() }} estudiantes</span>
                </h6>
            </div>
            <div class="card-body">
                @if($estudiantesMatriculados->count() > 0)
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i>
                    <strong>Nota mínima aprobatoria: Equivalente a 1.5 (B)</strong> |
                    <span class="text-success"><i class="bi bi-check-circle"></i> APROBADO</span> - Puede ascender |
                    <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> RECUPERACIÓN</span> - Necesita recuperar |
                    <span class="text-danger"><i class="bi bi-x-circle"></i> DESAPROBADO</span> - No puede ascender
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="selectAllAprobados">
                            <label class="form-check-label" for="selectAllAprobados">
                                Seleccionar todos APROBADOS (para ascender)
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="selectAllRecuperacion">
                            <label class="form-check-label" for="selectAllRecuperacion">
                                Seleccionar todos RECUPERACIÓN (para matricular)
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="nuevo_grado" class="me-2"><strong>Grado destino:</strong></label>
                        <select class="form-control d-inline-block w-auto" id="nuevo_grado" name="nuevo_grado" required>
                            <option value="">Seleccionar</option>
                            @php
                                $gradoActual = (int)$grado->grado;
                                $siguienteGrado = $gradoActual + 1;
                            @endphp
                            <option value="{{ $siguienteGrado }}">{{ $siguienteGrado }}°</option>
                        </select>

                        <select class="form-control d-inline-block w-auto ms-2" id="nueva_seccion" name="nueva_seccion" required>
                            <option value="">Sección</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>

                        <select class="form-control d-inline-block w-auto ms-2" id="nuevo_nivel" name="nuevo_nivel" required>
                            <option value="">Nivel</option>
                            <option value="Primaria" {{ $grado->nivel == 'Primaria' ? 'selected' : '' }}>Primaria</option>
                            <option value="Secundaria" {{ $grado->nivel == 'Secundaria' ? 'selected' : '' }}>Secundaria</option>
                        </select>

                        <button type="button" id="ascenderBtn" class="btn btn-success ms-3" disabled>
                            <i class="bi bi-arrow-up-circle me-2"></i> Ascender seleccionados
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-success">
                            <tr>
                                <th width="5%">Sel.</th>
                                <th>DNI</th>
                                <th>Apellidos y Nombres</th>
                                <th>Materias Aprobadas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesMatriculados as $estudiante)
                            <tr>
                                <td class="text-center">
                                    @if($estudiante->estado_aprobacion == 'aprobado')
                                        <input type="checkbox"
                                               name="estudiantes_ascender[]"
                                               value="{{ $estudiante->id }}"
                                               class="estudiante-aprobado-checkbox"
                                               data-estado="aprobado">
                                    @elseif($estudiante->estado_aprobacion == 'recuperacion')
                                        <input type="checkbox"
                                               name="estudiantes_recuperacion[]"
                                               value="{{ $estudiante->id }}"
                                               class="estudiante-recuperacion-checkbox"
                                               data-estado="recuperacion">
                                    @else
                                        <input type="checkbox" disabled>
                                    @endif
                                </td>
                                <td>{{ $estudiante->user->dni }}</td>
                                <td>
                                    {{ $estudiante->user->apellido_paterno }}
                                    {{ $estudiante->user->apellido_materno }},
                                    {{ $estudiante->user->nombre }}
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="badge bg-success">{{ $estudiante->materias_aprobadas }}</span>
                                        <span class="small text-muted">de {{ $estudiante->total_materias }} materias</span>
                                        @if($estudiante->materias_desaprobadas_count > 0)
                                            <span class="badge bg-danger mt-1">{{ $estudiante->materias_desaprobadas_count }} desaprobadas</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($estudiante->estado_aprobacion == 'aprobado')
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> APROBADO
                                        </span>
                                    @elseif($estudiante->estado_aprobacion == 'recuperacion')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-exclamation-triangle"></i> RECUPERACIÓN
                                        </span>
                                    @elseif($estudiante->estado_aprobacion == 'desaprobado')
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle"></i> DESAPROBADO
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-question-circle"></i> SIN EVALUACIÓN
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal{{ $estudiante->id }}">
                                        <i class="bi bi-eye"></i> Detalle
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No hay estudiantes matriculados en {{ $anioSeleccionado }}</h4>
                </div>
                @endif
            </div>
        </div>

        <!-- SECCIÓN: ESTUDIANTES REGISTRADOS (NO MATRICULADOS) -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-secondary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="bi bi-people me-2"></i>
                    Estudiantes Registrados en el Grado ({{ $grado->grado }}° {{ $grado->seccion }})
                    <span class="badge bg-light text-dark ms-2">{{ $estudiantesNoMatriculados->count() }} estudiantes</span>
                </h6>
            </div>
            <div class="card-body">
                @if($estudiantesNoMatriculados->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-secondary">
                            <tr>
                                <th>#</th>
                                <th>DNI</th>
                                <th>Apellidos y Nombres</th>
                                <th>Estado</th>
                                <th>Matrícula {{ $anioSeleccionado }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesNoMatriculados as $index => $estudiante)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $estudiante->user->dni }}</td>
                                <td>{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
                                <td><span class="badge bg-success">Activo</span></td>
                                <td><span class="badge bg-warning text-dark">Sin matrícula</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-3">
                    <p class="text-muted">Todos los estudiantes están matriculados</p>
                </div>
                @endif
            </div>
        </div>
    </form>

    <!-- Botón flotante para matricular en recuperación masivamente -->
    @if($periodoRecuperacion && $estudiantesMatriculados->where('estado_aprobacion', 'recuperacion')->count() > 0)
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <button type="button" class="btn btn-warning btn-lg rounded-pill shadow" id="btnMatricularRecuperacionMasivo">
            <i class="bi bi-arrow-repeat me-2"></i>
            Matricular Recuperación ({{ $estudiantesMatriculados->where('estado_aprobacion', 'recuperacion')->count() }})
        </button>
    </div>
    @endif

    <!-- MODALES: Detalle de cada estudiante matriculado -->
    @foreach($estudiantesMatriculados as $estudiante)
    <div class="modal fade" id="modal{{ $estudiante->id }}" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge me-2"></i>
                        {{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Resumen -->
                    <div class="alert alert-info">
                        <strong>Resumen académico {{ $anioSeleccionado }}:</strong><br>
                        {{ $estudiante->materias_aprobadas }} de {{ $estudiante->total_materias }} materias aprobadas
                        @if($estudiante->materias_desaprobadas_count > 0)
                            <span class="text-danger">({{ $estudiante->materias_desaprobadas_count }} desaprobadas)</span>
                        @endif
                    </div>

                    <!-- Botón para seleccionar todas las competencias -->
                    @if($periodoRecuperacion && $estudiante->estado_aprobacion == 'recuperacion')
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllCompetencias{{ $estudiante->id }}">
                            <label class="form-check-label" for="selectAllCompetencias{{ $estudiante->id }}">
                                <strong>Seleccionar todas las competencias desaprobadas</strong>
                            </label>
                        </div>
                    </div>
                    @endif

                    <!-- Tabla de Competencias (solo materias desaprobadas) -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tablaCompetencias{{ $estudiante->id }}">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">Sel.</th>
                                    <th>Materia</th>
                                    <th>Competencia</th>
                                    <th>Nota Original</th>
                                    <th>Nota Recuperación</th>
                                    <th>Estado Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($estudiante->detalle_materias as $materia)
                                    {{-- Solo mostrar materias DESAPROBADAS --}}
                                    @if($materia['estado'] == 'desaprobado')
                                        @foreach($materia['competencias_desaprobadas_list'] as $competencia)
                                        <tr>
                                            <td class="text-center">
                                                @if($periodoRecuperacion && !$competencia['esta_aprobada'] && !isset($competencia['tiene_recuperacion']))
                                                    <input type="checkbox"
                                                           class="competencia-recuperacion"
                                                           data-estudiante-id="{{ $estudiante->id }}"
                                                           data-materia-id="{{ $materia['materia_id'] }}"
                                                           data-materia-nombre="{{ $materia['materia_nombre'] }}"
                                                           data-competencia-id="{{ $competencia['id'] }}"
                                                           data-competencia-nombre="{{ $competencia['nombre'] ?? 'Competencia' }}"
                                                           data-nota-original="{{ $competencia['promedio'] }}">
                                                @elseif(isset($competencia['tiene_recuperacion']) && $competencia['tiene_recuperacion'])
                                                    <span class="badge bg-warning text-dark">Ya matriculado</span>
                                                @else
                                                    <span class="text-muted">No aplica</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $materia['materia_nombre'] }}</strong>
                                                <br>
                                                <small class="text-muted">Promedio materia: {{ $materia['promedio'] }} ({{ $materia['promedio_cualitativo'] }})</small>
                                            </td>
                                            <td>
                                                {{ $competencia['nombre'] }}<br>
                                                <small class="text-muted">ID: {{ $competencia['id'] }}</small>
                                                <br>
                                                @if(isset($competencia['criterios']) && count($competencia['criterios']) > 0)
                                                    <small class="text-info">
                                                        <i class="bi bi-list"></i>
                                                        {{ count($competencia['criterios']) }} criterio(s)
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="nota-original" data-valor="{{ $competencia['promedio'] }}">
                                                {{ number_format($competencia['promedio'], 1) }}
                                                <br>
                                                <small class="text-muted">
                                                    ({{ $competencia['promedio_cualitativo'] }})
                                                </small>
                                            </td>
                                            <td>
                                                @if(isset($competencia['nota_recuperacion']) && $competencia['nota_recuperacion'])
                                                    {{ number_format($competencia['nota_recuperacion'], 1) }}
                                                    <br>
                                                    <small class="text-success">
                                                        (@php
                                                            $nota = $competencia['nota_recuperacion'];
                                                            if ($nota >= 3.5) echo 'AD';
                                                            elseif ($nota >= 2.5) echo 'A';
                                                            elseif ($nota >= 1.5) echo 'B';
                                                            else echo 'C';
                                                        @endphp)
                                                    </small>
                                                @elseif(isset($competencia['tiene_recuperacion']) && $competencia['tiene_recuperacion'])
                                                    <span class="text-warning">Pendiente</span>
                                                @else
                                                    <span class="text-muted">---</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($competencia['esta_aprobada'])
                                                    <span class="badge bg-success">Aprobado</span>
                                                @elseif(isset($competencia['tiene_recuperacion']) && $competencia['tiene_recuperacion'])
                                                    <span class="badge bg-warning text-dark">En recuperación</span>
                                                @else
                                                    <span class="badge bg-danger">Desaprobado</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Botón para matricular en recuperación (individual) -->
                    @if($periodoRecuperacion && $estudiante->estado_aprobacion == 'recuperacion')
                        <div class="alert alert-warning mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-arrow-repeat"></i>
                                    <strong>Período de Recuperación Disponible:</strong>
                                    Seleccione las competencias que el estudiante debe recuperar
                                </div>
                                <button type="button"
                                        class="btn btn-warning"
                                        onclick="matricularRecuperacion({{ $estudiante->id }}, {{ $periodoRecuperacion->id }}, {{ $periodoAcademico->id }})">
                                    <i class="bi bi-save"></i> Matricular en Recuperación
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Materias Aprobadas (resumen) -->
                    @php
                        $materiasAprobadasList = array_filter($estudiante->detalle_materias, function($m) {
                            return $m['estado'] == 'aprobado';
                        });
                    @endphp
                    @if(count($materiasAprobadasList) > 0)
                        <div class="mt-3">
                            <h6><i class="bi bi-check-circle text-success"></i> Materias Aprobadas:</h6>
                            <div class="row">
                                @foreach($materiasAprobadasList as $materia)
                                    <div class="col-md-6 mb-1">
                                        <span class="badge bg-success w-100 text-start">
                                            {{ $materia['materia_nombre'] }}
                                            ({{ $materia['promedio'] }} - {{ $materia['promedio_cualitativo'] }})
                                            <small class="text-white-50">
                                                {{ $materia['competencias_aprobadas_count'] }}/{{ $materia['total_competencias'] }} competencias
                                            </small>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<script>
// Conversión de notas
function convertirNota(valor, toCualitativo = true) {
    const num = parseFloat(valor);
    if (toCualitativo) {
        if (num >= 3.5) return 'AD';
        if (num >= 2.5) return 'A';
        if (num >= 1.5) return 'B';
        return 'C';
    } else {
        if (valor === 'AD') return 4;
        if (valor === 'A') return 3;
        if (valor === 'B') return 2;
        if (valor === 'C') return 1;
        return num;
    }
}

// Función para actualizar formato de notas
function actualizarFormatoNotas(modoCualitativo) {
    const notasOriginales = document.querySelectorAll('.nota-original');
    notasOriginales.forEach(el => {
        const valorOriginal = parseFloat(el.getAttribute('data-valor') || el.innerText);
        if (modoCualitativo) {
            el.innerHTML = convertirNota(valorOriginal, true) + '<br><small class="text-muted">(' + convertirNota(valorOriginal, true) + ')</small>';
        } else {
            el.innerHTML = valorOriginal + '<br><small class="text-muted">(' + valorOriginal + ')</small>';
        }
    });
}

// Función para seleccionar automáticamente las competencias de un estudiante
function seleccionarCompetenciasEstudiante(estudianteId, seleccionar) {
    const checkboxesComp = document.querySelectorAll(`#modal${estudianteId} .competencia-recuperacion`);
    checkboxesComp.forEach(cb => {
        cb.checked = seleccionar;
    });

    // Actualizar el checkbox "Seleccionar todas" del modal
    const selectAllComp = document.getElementById(`selectAllCompetencias${estudianteId}`);
    if (selectAllComp) {
        selectAllComp.checked = seleccionar;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Switch de formato
    const formatoSwitch = document.getElementById('formatoNotas');
    const formatoLabel = document.getElementById('formatoLabel');
    let modoCualitativo = false;

    if (formatoSwitch) {
        formatoSwitch.addEventListener('change', function() {
            modoCualitativo = this.checked;
            actualizarFormatoNotas(modoCualitativo);
            formatoLabel.innerHTML = modoCualitativo ?
                '<span class="badge bg-success">C,B,A,AD</span>' :
                '<span class="badge bg-primary">1-4</span>';
        });
    }

    // Cambio de año
    const anioSelect = document.getElementById('anio');
    if (anioSelect) {
        anioSelect.addEventListener('change', function() {
            document.getElementById('formAnio').submit();
        });
    }

    // Seleccionar todos los aprobados para ascender
    const selectAllAprobados = document.getElementById('selectAllAprobados');
    const checkboxesAprobados = document.querySelectorAll('.estudiante-aprobado-checkbox');
    const ascenderBtn = document.getElementById('ascenderBtn');
    const nuevoGrado = document.getElementById('nuevo_grado');
    const nuevaSeccion = document.getElementById('nueva_seccion');
    const nuevoNivel = document.getElementById('nuevo_nivel');

    // Seleccionar todos los de recuperación
    const selectAllRecuperacion = document.getElementById('selectAllRecuperacion');
    const checkboxesRecuperacion = document.querySelectorAll('.estudiante-recuperacion-checkbox');

    function updateButtonState() {
        const selectedCount = document.querySelectorAll('.estudiante-aprobado-checkbox:checked').length;
        const destinoCompleto = nuevoGrado.value && nuevaSeccion.value && nuevoNivel.value;
        ascenderBtn.disabled = selectedCount === 0 || !destinoCompleto;
        ascenderBtn.innerHTML = `<i class="bi bi-arrow-up-circle me-2"></i> Ascender (${selectedCount})`;
    }

    if (selectAllAprobados) {
        selectAllAprobados.addEventListener('change', function() {
            checkboxesAprobados.forEach(cb => cb.checked = selectAllAprobados.checked);
            updateButtonState();
        });
    }

    // Cuando se selecciona un estudiante de recuperación, seleccionar automáticamente sus competencias
    if (selectAllRecuperacion) {
        selectAllRecuperacion.addEventListener('change', function() {
            checkboxesRecuperacion.forEach(cb => {
                cb.checked = selectAllRecuperacion.checked;
                if (cb.checked) {
                    // Seleccionar todas las competencias de ese estudiante
                    const estudianteId = cb.value;
                    seleccionarCompetenciasEstudiante(estudianteId, true);
                }
            });
        });
    }

    // Evento individual para cada checkbox de recuperación
    checkboxesRecuperacion.forEach(cb => {
        cb.addEventListener('change', function() {
            const estudianteId = this.value;
            if (this.checked) {
                seleccionarCompetenciasEstudiante(estudianteId, true);
            } else {
                seleccionarCompetenciasEstudiante(estudianteId, false);
            }
        });
    });

    checkboxesAprobados.forEach(cb => cb.addEventListener('change', updateButtonState));
    [nuevoGrado, nuevaSeccion, nuevoNivel].forEach(input => {
        if (input) input.addEventListener('change', updateButtonState);
    });
    updateButtonState();

    // Ascender estudiantes con SweetAlert
    if (ascenderBtn) {
        ascenderBtn.addEventListener('click', function() {
            const selectedCount = document.querySelectorAll('.estudiante-aprobado-checkbox:checked').length;
            if (selectedCount === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin selección',
                    text: 'No hay estudiantes seleccionados para ascender',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            if (!nuevoGrado.value || !nuevaSeccion.value || !nuevoNivel.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Seleccione el grado, sección y nivel de destino',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            Swal.fire({
                title: '¿Confirmar ascenso?',
                text: `¿Ascender ${selectedCount} estudiante(s) al ${nuevoGrado.value}° "${nuevaSeccion.value}" - ${nuevoNivel.value}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, ascender',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('ascenderForm').submit();
                }
            });
        });
    }

    // Seleccionar todas las competencias en cada modal
    @foreach($estudiantesMatriculados as $estudiante)
        const selectAllComp{{ $estudiante->id }} = document.getElementById('selectAllCompetencias{{ $estudiante->id }}');
        if (selectAllComp{{ $estudiante->id }}) {
            selectAllComp{{ $estudiante->id }}.addEventListener('change', function() {
                const checkboxesComp = document.querySelectorAll(`#modal{{ $estudiante->id }} .competencia-recuperacion`);
                checkboxesComp.forEach(cb => cb.checked = selectAllComp{{ $estudiante->id }}.checked);
            });
        }
    @endforeach

    // Botón de matricular recuperación masivo
    const btnMasivo = document.getElementById('btnMatricularRecuperacionMasivo');
    if (btnMasivo) {
        btnMasivo.addEventListener('click', function() {
            matricularRecuperacionMasiva({{ $periodoRecuperacion->id ?? 'null' }}, {{ $periodoAcademico->id }});
        });
    }
});

// Función para matricular en recuperación (individual) con SweetAlert
function matricularRecuperacion(estudianteId, periodoRecuperacionId, periodoAcademicoId) {
    const checkboxes = document.querySelectorAll(`#modal${estudianteId} .competencia-recuperacion:checked`);

    if (checkboxes.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin selección',
            text: 'Seleccione al menos una competencia para recuperación',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    const competencias = [];
    checkboxes.forEach(cb => {
        competencias.push({
            materia_competencia_id: cb.dataset.competenciaId,
            materia_id: cb.dataset.materiaId,
            materia_nombre: cb.dataset.materiaNombre,
            competencia_nombre: cb.dataset.competenciaNombre,
            nota_original: cb.dataset.notaOriginal
        });
    });

    Swal.fire({
        title: '¿Confirmar matrícula?',
        text: `¿Matricular al estudiante en período de recuperación con ${competencias.length} competencia(s)?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, matricular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Guardando datos de recuperación',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('{{ route("estudiante.matricular.recuperacion.individual") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    estudiante_id: estudianteId,
                    periodo_recuperacion_id: periodoRecuperacionId,
                    periodo_academico_id: periodoAcademicoId,
                    competencias: competencias
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Matriculado!',
                        text: data.message,
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

// Función para matricular recuperación masiva con SweetAlert
function matricularRecuperacionMasiva(periodoRecuperacionId, periodoAcademicoId) {
    if (!periodoRecuperacionId) {
        Swal.fire({
            icon: 'error',
            title: 'No disponible',
            text: 'No hay período de recuperación configurado para este año',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    const estudiantesSeleccionados = document.querySelectorAll('.estudiante-recuperacion-checkbox:checked');

    if (estudiantesSeleccionados.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin selección',
            text: 'Seleccione al menos un estudiante en estado RECUPERACIÓN',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // Recopilar datos de todos los estudiantes seleccionados
    const estudiantes = [];

    estudiantesSeleccionados.forEach(checkbox => {
        const estudianteId = checkbox.value;
        // Obtener las competencias seleccionadas para este estudiante desde su modal
        const competenciasSeleccionadas = document.querySelectorAll(`#modal${estudianteId} .competencia-recuperacion:checked`);

        if (competenciasSeleccionadas.length > 0) {
            const competencias = [];
            competenciasSeleccionadas.forEach(cb => {
                competencias.push({
                    materia_competencia_id: cb.dataset.competenciaId,
                    materia_id: cb.dataset.materiaId,
                    materia_nombre: cb.dataset.materiaNombre,
                    competencia_nombre: cb.dataset.competenciaNombre,
                    nota_original: cb.dataset.notaOriginal
                });
            });

            estudiantes.push({
                estudiante_id: estudianteId,
                periodo_recuperacion_id: periodoRecuperacionId,
                periodo_academico_id: periodoAcademicoId,
                competencias: competencias
            });
        }
    });

    if (estudiantes.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin competencias',
            text: 'Los estudiantes seleccionados no tienen competencias marcadas para recuperación. Abra el modal de cada estudiante y seleccione las competencias.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    Swal.fire({
        title: '¿Confirmar matrícula masiva?',
        text: `¿Matricular ${estudiantes.length} estudiante(s) en período de recuperación?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, matricular todos',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Guardando datos de recuperación',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('{{ route("estudiante.matricular.recuperacion") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    estudiantes: estudiantes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Proceso completado!',
                        text: data.message,
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}
</script>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .badge {
        font-size: 0.85rem;
    }
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    .position-fixed {
        position: fixed;
        bottom: 20px;
        right: 20px;
    }
</style>
@endsection
