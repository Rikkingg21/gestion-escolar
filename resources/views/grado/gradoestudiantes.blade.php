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

    <!-- Selector de Año -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">Seleccionar Año Escolar</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
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
                <div class="col-md-8">
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

        <!-- SECCIÓN 1: ESTUDIANTES REGISTRADOS EN EL GRADO -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-secondary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="bi bi-people me-2"></i>
                    Estudiantes Registrados en el Grado ({{ $grado->grado }}° {{ $grado->seccion }})
                    <span class="badge bg-light text-dark ms-2">{{ $estudiantesRegistrados->count() }} estudiantes</span>
                </h6>
            </div>
            <div class="card-body">
                @if($estudiantesRegistrados->count() > 0)
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
                            @foreach($estudiantesRegistrados as $index => $estudiante)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $estudiante->user->dni }}</td>
                                <td>{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
                                <td>
                                    <span class="badge bg-success">Activo</span>
                                </td>
                                <td>
                                    @php
                                        $estaMatriculado = $estudiantesMatriculados->contains('id', $estudiante->id);
                                    @endphp
                                    @if($estaMatriculado)
                                        <span class="badge bg-primary">
                                            <i class="bi bi-check-circle"></i> Matriculado
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-exclamation-triangle"></i> Sin matrícula
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-3">
                    <p class="text-muted">No hay estudiantes registrados en este grado</p>
                </div>
                @endif
            </div>
        </div>

        <!-- SECCIÓN 2: ESTUDIANTES MATRICULADOS EN EL AÑO SELECCIONADO (SOLO APROBADOS PUEDEN ASCENDER) -->
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
                    <strong>Nota mínima aprobatoria: 2.1</strong> |
                    Solo los estudiantes con estado <strong>APROBADO</strong> pueden ser ascendidos al siguiente grado.
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label" for="selectAll">
                            Seleccionar todos los estudiantes APROBADOS
                        </label>
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
                                               name="estudiantes[]"
                                               value="{{ $estudiante->id }}"
                                               class="estudiante-checkbox"
                                               data-estado="aprobado">
                                    @else
                                        <input type="checkbox" disabled class="estudiante-checkbox-disabled">
                                        <small class="text-muted d-block">No aplica</small>
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
                    <p class="text-muted">No se encontraron estudiantes con matrícula activa para este año escolar.</p>
                </div>
                @endif
            </div>
        </div>
    </form>

    <!-- MODALES: Detalle de cada estudiante matriculado -->
    @foreach($estudiantesMatriculados as $estudiante)
    <div class="modal fade" id="modal{{ $estudiante->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
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

                    <!-- Materias Desaprobadas con Detalle -->
                    @if($estudiante->materias_desaprobadas_count > 0)
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle-fill"></i> Materias y Competencias Desaprobadas (promedio &lt; 2.1)</h6>
                            @foreach($estudiante->detalle_materias as $materia)
                                @if($materia['estado'] == 'desaprobado' && count($materia['competencias_desaprobadas']) > 0)
                                    <div class="border-bottom pb-2 mb-2">
                                        <strong class="text-danger">{{ $materia['materia_nombre'] }}</strong>
                                        <span class="badge bg-danger float-end">Promedio: {{ $materia['promedio'] }}</span>
                                        <ul class="mt-2 mb-0">
                                            @foreach($materia['competencias_desaprobadas'] as $comp)
                                                <li>
                                                    {{ $comp['nombre'] }}
                                                    @if(isset($comp['nota_recuperacion']))
                                                        <span class="badge bg-warning text-dark ms-2">
                                                            Nota original: {{ $comp['promedio_original'] }} →
                                                            Recuperación: {{ $comp['nota_recuperacion'] }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger ms-2">Promedio: {{ $comp['nota_final'] }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- Sugerencia de Recuperación -->
                        @if($periodoRecuperacion && $estudiante->estado_aprobacion == 'recuperacion')
                            <div class="alert alert-warning">
                                <i class="bi bi-arrow-repeat"></i>
                                <strong>Período de Recuperación Disponible:</strong>
                                El estudiante puede recuperar las competencias desaprobadas en el período
                                <strong>{{ $periodoRecuperacion->nombre }}</strong>.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i>
                            <strong>¡Todas las materias aprobadas!</strong> El estudiante ha alcanzado el promedio mínimo requerido (2.1) en todas las materias.
                        </div>
                    @endif

                    <!-- Materias Aprobadas (resumen) -->
                    @php
                        $materiasAprobadasList = array_filter($estudiante->detalle_materias, function($m) {
                            return $m['estado'] == 'aprobado' || $m['estado'] == 'recuperado';
                        });
                    @endphp
                    @if(count($materiasAprobadasList) > 0)
                        <div class="mt-3">
                            <h6><i class="bi bi-check-circle text-success"></i> Materias Aprobadas:</h6>
                            <div class="row">
                                @foreach($materiasAprobadasList as $materia)
                                    <div class="col-md-6 mb-1">
                                        <span class="badge bg-success w-100 text-start">
                                            {{ $materia['materia_nombre'] }} ({{ $materia['promedio'] }})
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
document.addEventListener('DOMContentLoaded', function() {
    // Cambio de año
    const anioSelect = document.getElementById('anio');
    if (anioSelect) {
        anioSelect.addEventListener('change', function() {
            document.getElementById('formAnio').submit();
        });
    }

    // Seleccionar todos los aprobados
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.estudiante-checkbox');
    const ascenderBtn = document.getElementById('ascenderBtn');
    const nuevoGrado = document.getElementById('nuevo_grado');
    const nuevaSeccion = document.getElementById('nueva_seccion');
    const nuevoNivel = document.getElementById('nuevo_nivel');

    function updateButtonState() {
        const selectedCount = document.querySelectorAll('.estudiante-checkbox:checked').length;
        const destinoCompleto = nuevoGrado.value && nuevaSeccion.value && nuevoNivel.value;

        ascenderBtn.disabled = selectedCount === 0 || !destinoCompleto;
        ascenderBtn.innerHTML = `<i class="bi bi-arrow-up-circle me-2"></i> Ascender (${selectedCount})`;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateButtonState();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateButtonState));

    [nuevoGrado, nuevaSeccion, nuevoNivel].forEach(input => {
        if (input) input.addEventListener('change', updateButtonState);
    });

    updateButtonState();

    // Ascender estudiantes
    if (ascenderBtn) {
        ascenderBtn.addEventListener('click', function() {
            const selectedCount = document.querySelectorAll('.estudiante-checkbox:checked').length;

            if (selectedCount === 0) {
                alert('No hay estudiantes seleccionados');
                return;
            }

            if (!nuevoGrado.value || !nuevaSeccion.value || !nuevoNivel.value) {
                alert('Seleccione el grado, sección y nivel de destino');
                return;
            }

            const confirmMsg = `¿Ascender ${selectedCount} estudiante(s) al ${nuevoGrado.value}° "${nuevaSeccion.value}" - ${nuevoNivel.value}?`;
            if (confirm(confirmMsg)) {
                document.getElementById('ascenderForm').submit();
            }
        });
    }
});
</script>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .estudiante-checkbox-disabled {
        opacity: 0.5;
    }
    .badge {
        font-size: 0.85rem;
    }
</style>
@endsection
