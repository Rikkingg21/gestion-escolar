@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-person-rolodex me-2"></i> Evaluación de Estudiantes
            </h1>
            <p class="text-muted mt-1 mb-0">{{ $grado->grado }}° {{ $grado->seccion }} - {{ $grado->nivel }}</p>
        </div>
        <a href="{{ route('grado.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i> Volver a Grados
        </a>
    </div>

    <!-- Selector de Año y Formato -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="bi bi-sliders2 me-2"></i>Configuración</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <form method="GET" action="{{ route('grado.estudiantes', $grado->id) }}" id="formAnio">
                        <label for="anio" class="form-label fw-bold"><i class="bi bi-calendar3 me-1"></i>Año Escolar</label>
                        <select name="anio" id="anio" class="form-select">
                            @foreach($aniosDisponibles as $anio)
                                <option value="{{ $anio }}" {{ $anioSeleccionado == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="bi bi-layout-three-columns me-1"></i>Formato de Notas</label>
                    <div class="d-flex align-items-center mt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="formatoNotas" style="width: 3.5em; height: 1.8em;">
                            <label class="form-check-label ms-2" id="formatoLabel">
                                <span class="badge bg-primary px-3 py-2">1-4</span>
                            </label>
                        </div>
                    </div>
                    <small class="text-muted">Cuantitativo (1-4) / Cualitativo (C,B,A,AD)</small>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info mb-0 h-100 d-flex align-items-center">
                        <div>
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Período Académico:</strong> {{ $periodoAcademico->nombre ?? 'N/A' }}
                            @if($periodoRecuperacion)
                                <span class="mx-2">|</span>
                                <i class="bi bi-arrow-repeat me-1"></i>
                                <strong>Recuperación:</strong> {{ $periodoRecuperacion->nombre }}
                            @else
                                <span class="mx-2">|</span>
                                <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No hay período de recuperación configurado</span>
                            @endif
                        </div>
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
            <div class="card-header py-3 bg-gradient-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-black">
                        <i class="bi bi-journal-check me-2"></i>
                        Estudiantes Matriculados {{ $anioSeleccionado }}
                    </h6>
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        {{ $estudiantesMatriculados->count() }} estudiantes
                    </span>
                </div>
            </div>
            <div class="card-body">
                @if($estudiantesMatriculados->count() > 0)
                <!-- Leyenda de estados -->
                <div class="alert alert-light border mb-4">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i> APROBADO</span>
                            <small class="text-muted d-block">100% competencias aprobadas</small>
                        </div>
                        <div class="col-md-3">
                            <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-exclamation-triangle me-1"></i> RECUPERACIÓN</span>
                            <small class="text-muted d-block">Requiere matricular recuperación</small>
                        </div>
                        <div class="col-md-3">
                            <span class="badge bg-info px-3 py-2"><i class="bi bi-hourglass-split me-1"></i> PENDIENTE</span>
                            <small class="text-muted d-block">Ya matriculado, esperando nota</small>
                        </div>
                        <div class="col-md-3">
                            <span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle me-1"></i> DESAPROBADO</span>
                            <small class="text-muted d-block">No cumple requisitos</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <div class="btn-group" role="group">
                            <input type="checkbox" class="btn-check" id="selectAllAprobados" autocomplete="off">
                            <label class="btn btn-outline-success" for="selectAllAprobados">
                                <i class="bi bi-check-all me-1"></i> Seleccionar Aprobados
                            </label>
                            <input type="checkbox" class="btn-check" id="selectAllRecuperacion" autocomplete="off">
                            <label class="btn btn-outline-warning" for="selectAllRecuperacion">
                                <i class="bi bi-arrow-repeat me-1"></i> Seleccionar Recuperación
                            </label>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="input-group" style="width: auto;">
                            <span class="input-group-text bg-light"><i class="bi bi-arrow-up-circle"></i></span>
                            <select class="form-select" id="nuevo_grado" name="nuevo_grado" style="width: auto;" required>
                                <option value="">Grado destino</option>
                                @php $siguienteGrado = (int)$grado->grado + 1; @endphp
                                <option value="{{ $siguienteGrado }}">{{ $siguienteGrado }}°</option>
                            </select>
                        </div>
                        <select class="form-select" id="nueva_seccion" name="nueva_seccion" style="width: 80px;" required>
                            <option value="">Sec.</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                        <select class="form-select" id="nuevo_nivel" name="nuevo_nivel" style="width: auto;" required>
                            <option value="">Nivel</option>
                            <option value="Primaria" {{ $grado->nivel == 'Primaria' ? 'selected' : '' }}>Primaria</option>
                            <option value="Secundaria" {{ $grado->nivel == 'Secundaria' ? 'selected' : '' }}>Secundaria</option>
                        </select>
                        <button type="button" id="ascenderBtn" class="btn btn-success" disabled>
                            <i class="bi bi-arrow-up-circle me-1"></i> Ascender (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">Sel.</th>
                                <th>DNI</th>
                                <th>Apellidos y Nombres</th>
                                <th width="30%" class="text-center">Rendimiento</th>
                                <th width="10%" class="text-center">Estado</th>
                                <th width="8%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesMatriculados as $estudiante)
                            <tr>
                                <td class="text-center">
                                    @if($estudiante->estado_final == 'aprobado')
                                        <input class="form-check-input estudiante-aprobado-checkbox" type="checkbox" value="{{ $estudiante->id }}" style="transform: scale(1.1);">
                                    @elseif($estudiante->estado_final == 'recuperacion')
                                        <input class="form-check-input estudiante-recuperacion-checkbox" type="checkbox" value="{{ $estudiante->id }}" style="transform: scale(1.1);">
                                    @else
                                        <span class="text-muted">---</span>
                                    @endif
                                </td>
                                <td><span class="font-monospace small">{{ $estudiante->user->dni }}</span></td>
                                <td>
                                    <div class="fw-bold">{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}</div>
                                    <small class="text-muted">{{ $estudiante->user->nombre }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="flex-grow-1" style="min-width: 80px;">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $estudiante->porcentaje_aprobacion }}%;"></div>
                                            </div>
                                        </div>
                                        <div class="text-nowrap">
                                            <span class="badge bg-success" style="font-size: 0.7rem;">{{ $estudiante->competencias_aprobadas }}</span>
                                            <span class="text-muted mx-1">/</span>
                                            <span class="badge bg-secondary" style="font-size: 0.7rem;">{{ $estudiante->total_competencias }}</span>
                                            @if($estudiante->competencias_pendientes > 0)
                                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;"><i class="bi bi-exclamation-triangle"></i>{{ $estudiante->competencias_pendientes }}</span>
                                            @endif
                                            @if($estudiante->competencias_pendientes_calificar > 0)
                                                <span class="badge bg-info ms-1" style="font-size: 0.7rem;"><i class="bi bi-hourglass-split"></i>{{ $estudiante->competencias_pendientes_calificar }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $estudiante->porcentaje_aprobacion }}% aprobadas</small>
                                </td>
                                <td>
                                    @if($estudiante->estado_final == 'aprobado')
                                        <span class="badge bg-success w-100 py-2"><i class="bi bi-check-circle"></i> APROBADO</span>
                                    @elseif($estudiante->estado_final == 'pendiente_calificar')
                                        <span class="badge bg-info w-100 py-2"><i class="bi bi-hourglass-split"></i> PENDIENTE CALIFICAR</span>
                                    @elseif($estudiante->estado_final == 'recuperacion')
                                        <span class="badge bg-warning text-dark w-100 py-2"><i class="bi bi-arrow-repeat"></i> RECUPERACIÓN</span>
                                    @elseif($estudiante->estado_final == 'desaprobado')
                                        <span class="badge bg-danger w-100 py-2"><i class="bi bi-x-circle"></i> DESAPROBADO</span>
                                    @else
                                        <span class="badge bg-secondary w-100 py-2"><i class="bi bi-question-circle"></i> SIN EVALUACIÓN</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-info btn-sm w-100"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal{{ $estudiante->id }}">
                                        <i class="bi bi-eye"></i>
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
        @if($estudiantesNoMatriculados->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-secondary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="bi bi-people me-2"></i>
                    Estudiantes Registrados sin Matrícula {{ $anioSeleccionado }}
                    <span class="badge bg-light text-dark ms-2">{{ $estudiantesNoMatriculados->count() }}</span>
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>DNI</th>
                                <th>Apellidos y Nombres</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesNoMatriculados as $index => $estudiante)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $estudiante->user->dni }}</td>
                                <td>{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
                                <td><span class="badge bg-warning text-dark">Sin matrícula</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </form>

    <!-- Botón flotante para matricular en recuperación masivamente -->
    @if($periodoRecuperacion && $estudiantesParaRecuperacion > 0)
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <button type="button" class="btn btn-warning btn-lg rounded-pill shadow-lg" id="btnMatricularRecuperacionMasivo" style="background: linear-gradient(45deg, #ffc107, #ff9800); border: none;">
            <i class="bi bi-arrow-repeat me-2"></i>
            Matricular Recuperación ({{ $estudiantesParaRecuperacion }})
        </button>
    </div>
    @endif

    <!-- MODALES: Detalle de cada estudiante matriculado -->
    @foreach($estudiantesMatriculados as $estudiante)
    @php
        // Cálculo robusto del estado global y registros de recuperación
        $tieneRegistrosRecuperacion = false;
        $estadoGlobalRecuperaciones = '1'; // Por defecto bloqueado
        $todasBloqueadas = true;

        foreach($estudiante->detalle_materias as $materia) {
            foreach($materia['competencias'] as $competencia) {
                if (($competencia['tiene_registro_recuperacion'] ?? false) ||
                    !empty($competencia['recuperacion_id'])) {

                    $tieneRegistrosRecuperacion = true;

                    $estadoActual = $competencia['recuperacion_estado'] ?? '0';

                    if ($estadoActual == '0') {
                        $estadoGlobalRecuperaciones = '0';
                        $todasBloqueadas = false;
                    }
                }
            }
        }

        // Si no hay ningún registro de recuperación, forzamos '0' (editable)
        if (!$tieneRegistrosRecuperacion) {
            $estadoGlobalRecuperaciones = '0';
        }
    @endphp

    <div class="modal fade" id="modal{{ $estudiante->id }}" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-gradient-info text-black">
                    <div>
                        <h5 class="modal-title">
                            <i class="bi bi-person-badge me-2"></i>
                            {{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}
                        </h5>
                        <p class="mb-0 mt-1 small opacity-75">DNI: {{ $estudiante->user->dni }}</p>
                    </div>
                    <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- Tarjeta de Resumen -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body bg-light rounded">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <span class="display-6 fw-bold text-success">{{ $estudiante->materias_aprobadas }}</span>
                                        <p class="text-muted mb-0">Materias Aprobadas</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <span class="display-6 fw-bold text-secondary">{{ $estudiante->total_materias }}</span>
                                        <p class="text-muted mb-0">Total Materias</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border-end">
                                        <span class="display-6 fw-bold text-danger">{{ $estudiante->materias_desaprobadas_count }}</span>
                                        <p class="text-muted mb-0">Materias Desaprobadas</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <span class="display-6 fw-bold text-warning">{{ $estudiante->total_competencias_recuperar }}</span>
                                    <p class="text-muted mb-0">Competencias a Recuperar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Competencias -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-table me-2"></i>Detalle de Competencias</h6>
                                @if($periodoRecuperacion && $estudiante->total_competencias_recuperar > 0 && $estadoGlobalRecuperaciones == '0')
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAllCompetencias{{ $estudiante->id }}">
                                        <label class="form-check-label" for="selectAllCompetencias{{ $estudiante->id }}">
                                            <i class="bi bi-check2-all"></i> Seleccionar todas
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%"></th>
                                            <th>Materia / Competencia</th>
                                            <th width="12%" class="text-center">Nota Original</th>
                                            <th width="12%" class="text-center">Recuperación</th>
                                            <th width="12%" class="text-center">Nota Final</th>
                                            <th width="12%" class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($estudiante->detalle_materias as $materia)
                                            <tr class="table-secondary">
                                                <td colspan="6" class="p-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="bi bi-book me-1"></i>
                                                            <strong>{{ $materia['materia_nombre'] }}</strong>
                                                            <span class="badge {{ $materia['estado'] == 'aprobado' ? 'bg-success' : 'bg-danger' }} ms-2">
                                                                {{ $materia['estado'] == 'aprobado' ? 'Aprobada' : 'Desaprobada' }}
                                                            </span>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="text-muted">
                                                                Promedio: {{ $materia['promedio'] }} ({{ $materia['promedio_cualitativo'] }}) |
                                                                Competencias: {{ $materia['competencias_aprobadas_count'] }}/{{ $materia['total_competencias'] }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach($materia['competencias'] as $competencia)
                                            @php
                                                // Datos de la competencia
                                                $notaOriginal = $competencia['promedio_original'];
                                                $notaOriginalCualitativa = $competencia['promedio_original_cualitativo'];

                                                // Datos de recuperación
                                                $notaRecuperacion = $competencia['nota_recuperacion'] ?? null;
                                                $notaRecuperacionCualitativa = $competencia['promedio_final_cualitativo'] ?? null;
                                                $tieneRegistroRecuperacion = $competencia['tiene_registro_recuperacion'] ?? false;

                                                // Nota final
                                                $notaFinal = $notaRecuperacion ?? $notaOriginal;
                                                $notaFinalCualitativa = $notaRecuperacionCualitativa ?? $notaOriginalCualitativa;

                                                // Estados
                                                $estaAprobadaOriginal = $notaOriginal >= 1.5;
                                                $estaAprobadaFinal = $notaFinal >= 1.5;
                                                $requiereRecuperacion = !$estaAprobadaOriginal && $notaRecuperacion === null && !$tieneRegistroRecuperacion;

                                                // Estado de la recuperación (0=editable, 1=bloqueado)
                                                $recuperacionEstado = $competencia['recuperacion_estado'] ?? '0';
                                                $recuperacionId = $competencia['recuperacion_id'] ?? '';
                                                $esEditable = $periodoRecuperacion && $recuperacionEstado == '0';
                                            @endphp
                                            <tr>
                                                <!-- Checkbox / Estado de selección -->
                                                <td class="text-center align-middle">
                                                    @if($periodoRecuperacion && $requiereRecuperacion && $recuperacionEstado == '0')
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input class="form-check-input competencia-recuperacion"
                                                                type="checkbox"
                                                                data-estudiante-id="{{ $estudiante->id }}"
                                                                data-materia-id="{{ $materia['materia_id'] }}"
                                                                data-materia-nombre="{{ $materia['materia_nombre'] }}"
                                                                data-competencia-id="{{ $competencia['id'] }}"
                                                                data-competencia-nombre="{{ $competencia['nombre'] }}"
                                                                data-nota-original="{{ $notaOriginal }}">
                                                        </div>
                                                    @elseif($tieneRegistroRecuperacion && $notaRecuperacion === null)
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="bi bi-hourglass-split"></i> Pendiente calificar
                                                        </span>
                                                    @elseif($notaRecuperacion !== null)
                                                        <span class="badge bg-info">
                                                            <i class="bi bi-check-circle"></i> Recuperado
                                                        </span>
                                                    @elseif($estaAprobadaOriginal)
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check"></i> Aprobada
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-exclamation-triangle"></i> Desaprobada
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- Materia / Competencia -->
                                                <td class="align-middle">
                                                    <div class="fw-bold">{{ $competencia['nombre'] }}</div>
                                                </td>

                                                <!-- NOTA ORIGINAL -->
                                                <td class="text-center align-middle nota-original" data-valor="{{ $notaOriginal }}">
                                                    <span class="badge bg-secondary px-3 py-2">{{ number_format($notaOriginal, 1) }}</span>
                                                    <br><small>({{ $notaOriginalCualitativa }})</small>
                                                </td>

                                                <!-- RECUPERACIÓN - Input editable solo si estado es '0' -->
                                                <td class="text-center align-middle">
                                                    @php
                                                        $notaRecuperacion = $competencia['nota_recuperacion'] ?? null;
                                                        $recuperacionEstado = $competencia['recuperacion_estado'] ?? '0';
                                                        $recuperacionId = $competencia['recuperacion_id'] ?? null;
                                                        $notaRecuperacionCualitativa = $competencia['promedio_final_cualitativo'] ?? null;
                                                    @endphp

                                                    @if($recuperacionEstado == '1')
                                                        <!-- === BLOQUEADO (solo lectura) === -->
                                                        @if($notaRecuperacion !== null)
                                                            <span class="badge bg-success px-3 py-2">{{ number_format($notaRecuperacion, 1) }}</span>
                                                            <br><small class="text-success">({{ $notaRecuperacionCualitativa }})</small>
                                                        @else
                                                            <span class="badge bg-secondary">
                                                                <i class="bi bi-lock"></i> Bloqueada
                                                            </span>
                                                            <br><small>Sin nota asignada</small>
                                                        @endif
                                                        <br><small class="text-muted"><i class="bi bi-lock"></i> Bloqueada</small>

                                                    @elseif($recuperacionId)
                                                        <!-- === EDITABLE (estado 0) - Mostrar select aunque ya tenga nota === -->
                                                        <div class="recuperacion-container" data-rec-id="{{ $recuperacionId }}">
                                                            <select class="form-select form-select-sm nota-recuperacion-select mb-1"
                                                                    data-rec-id="{{ $recuperacionId }}"
                                                                    data-estudiante-id="{{ $estudiante->id }}"
                                                                    data-competencia-id="{{ $competencia['id'] }}"
                                                                    style="min-width: 130px;">
                                                                <option value="">Seleccionar nota</option>
                                                                <option value="C" {{ $notaRecuperacion !== null && $competencia['promedio_final_cualitativo'] == 'C' ? 'selected' : '' }}>C (1.0 - 1.4)</option>
                                                                <option value="B" {{ $notaRecuperacion !== null && $competencia['promedio_final_cualitativo'] == 'B' ? 'selected' : '' }}>B (1.5 - 2.4)</option>
                                                                <option value="A" {{ $notaRecuperacion !== null && $competencia['promedio_final_cualitativo'] == 'A' ? 'selected' : '' }}>A (2.5 - 3.4)</option>
                                                                <option value="AD" {{ $notaRecuperacion !== null && $competencia['promedio_final_cualitativo'] == 'AD' ? 'selected' : '' }}>AD (3.5 - 4.0)</option>
                                                            </select>
                                                            <button class="btn btn-sm btn-primary w-100 guardar-nota-rec"
                                                                    data-rec-id="{{ $recuperacionId }}">
                                                                <i class="bi bi-save"></i> Guardar
                                                            </button>
                                                        </div>

                                                    @else
                                                        <!-- Sin registro de recuperación -->
                                                        <span class="text-muted">---</span>
                                                    @endif
                                                </td>

                                                <!-- NOTA FINAL -->
                                                <td class="text-center align-middle nota-final" data-valor="{{ $notaFinal }}">
                                                    <strong class="fs-5 {{ !$estaAprobadaFinal && !$estaAprobadaOriginal ? 'text-danger' : '' }}">{{ number_format($notaFinal, 1) }}</strong>
                                                    <br><small>({{ $notaFinalCualitativa }})</small>
                                                </td>

                                                <!-- Estado final de la competencia -->
                                                <td class="text-center align-middle">
                                                    @if($estaAprobadaOriginal && $notaRecuperacion === null)
                                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Aprobada</span>
                                                    @elseif($tieneRegistroRecuperacion && $notaRecuperacion === null)
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock-history"></i> Pendiente calificar</span>
                                                    @elseif($notaRecuperacion !== null && $estaAprobadaFinal)
                                                        <span class="badge bg-info"><i class="bi bi-arrow-repeat"></i> Recuperado (Aprobado)</span>
                                                    @elseif($notaRecuperacion !== null && !$estaAprobadaFinal)
                                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Recuperación reprobada</span>
                                                    @elseif(!$estaAprobadaOriginal)
                                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Requiere recuperación</span>
                                                    @else
                                                        <span class="badge bg-secondary"><i class="bi bi-question-circle"></i> Sin evaluación</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    @if($periodoRecuperacion)
                        <div class="d-flex gap-2 w-100">

                            <!-- Matricular Recuperación -->
                            @if($estudiante->total_competencias_recuperar > 0 && $estadoGlobalRecuperaciones == '0')
                                <button type="button" class="btn btn-warning flex-grow-1"
                                        onclick="matricularRecuperacion({{ $estudiante->id }}, {{ $periodoRecuperacion->id }}, {{ $periodoAcademico->id }})">
                                    <i class="bi bi-save me-1"></i>
                                    Matricular en Recuperación ({{ $estudiante->total_competencias_recuperar }})
                                </button>
                            @endif

                            <!-- Botón Bloquear / Liberar - SIEMPRE visible si hay registros -->
                            @if($tieneRegistrosRecuperacion)
                                <button type="button"
                                        class="btn {{ $estadoGlobalRecuperaciones == '0' ? 'btn-danger' : 'btn-success' }} flex-grow-1 btn-cambiar-estado"
                                        data-estudiante-id="{{ $estudiante->id }}"
                                        data-estado-actual="{{ $estadoGlobalRecuperaciones }}">
                                    <i class="bi {{ $estadoGlobalRecuperaciones == '0' ? 'bi-lock' : 'bi-unlock' }} me-1"></i>
                                    {{ $estadoGlobalRecuperaciones == '0' ? 'Bloquear Notas' : 'Liberar Notas' }}
                                </button>
                            @endif

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Cerrar
                            </button>
                        </div>
                    @else
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i> Cerrar
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<script>
// FUNCIONES DE CONVERSIÓN DE NOTAS
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

function actualizarFormatoNotas(modoCualitativo) {
    const notasOriginales = document.querySelectorAll('.nota-original');
    notasOriginales.forEach(el => {
        const valorOriginal = parseFloat(el.getAttribute('data-valor'));
        if (modoCualitativo) {
            const cuali = convertirNota(valorOriginal, true);
            el.innerHTML = `<span class="badge bg-secondary px-3 py-2">${cuali}</span><br><small>(${cuali})</small>`;
        } else {
            el.innerHTML = `<span class="badge bg-secondary px-3 py-2">${valorOriginal}</span><br><small>(${valorOriginal})</small>`;
        }
    });

    const notasFinales = document.querySelectorAll('.nota-final');
    notasFinales.forEach(el => {
        const valorFinal = parseFloat(el.getAttribute('data-valor'));
        if (modoCualitativo) {
            const cuali = convertirNota(valorFinal, true);
            el.innerHTML = `<strong class="fs-5">${cuali}</strong><br><small>(${cuali})</small>`;
        } else {
            el.innerHTML = `<strong class="fs-5">${valorFinal}</strong><br><small>(${valorFinal})</small>`;
        }
    });
}

// FUNCIONES DE SELECCIÓN
function seleccionarCompetenciasEstudiante(estudianteId, seleccionar) {
    const checkboxesComp = document.querySelectorAll(`#modal${estudianteId} .competencia-recuperacion`);
    checkboxesComp.forEach(cb => {
        cb.checked = seleccionar;
    });
    const selectAllComp = document.getElementById(`selectAllCompetencias${estudianteId}`);
    if (selectAllComp) selectAllComp.checked = seleccionar;
}

// FUNCIONES DE MATRÍCULA DE RECUPERACIÓN
async function matricularRecuperacion(estudianteId, periodoRecuperacionId, periodoAcademicoId) {
    const checkboxes = document.querySelectorAll(`#modal${estudianteId} .competencia-recuperacion:checked`);

    if (checkboxes.length === 0) {
        await Swal.fire({
            icon: 'warning',
            title: 'Sin selección',
            text: 'Seleccione al menos una competencia para recuperación',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    const competencias = Array.from(checkboxes).map(cb => ({
        materia_competencia_id: cb.dataset.competenciaId,
        materia_id: cb.dataset.materiaId,
        materia_nombre: cb.dataset.materiaNombre,
        competencia_nombre: cb.dataset.competenciaNombre,
        nota_original: cb.dataset.notaOriginal
    }));

    const result = await Swal.fire({
        title: '¿Confirmar matrícula?',
        text: `¿Matricular al estudiante en período de recuperación con ${competencias.length} competencia(s)?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, matricular',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await fetch('{{ route("estudiante.matricular.recuperacion.individual") }}', {
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
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Matriculado!',
                    text: data.message,
                    confirmButtonColor: '#28a745'
                });
                location.reload();
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la solicitud',
                confirmButtonColor: '#dc3545'
            });
        }
    }
}

async function matricularRecuperacionMasiva(periodoRecuperacionId, periodoAcademicoId) {
    if (!periodoRecuperacionId) {
        await Swal.fire({
            icon: 'error',
            title: 'No disponible',
            text: 'No hay período de recuperación configurado',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    const estudiantesSeleccionados = document.querySelectorAll('.estudiante-recuperacion-checkbox:checked');

    if (estudiantesSeleccionados.length === 0) {
        await Swal.fire({
            icon: 'warning',
            title: 'Sin selección',
            text: 'Seleccione al menos un estudiante en estado RECUPERACIÓN',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    const estudiantes = [];

    for (const checkbox of estudiantesSeleccionados) {
        const estudianteId = checkbox.value;
        const competenciasSeleccionadas = document.querySelectorAll(`#modal${estudianteId} .competencia-recuperacion:checked`);

        if (competenciasSeleccionadas.length > 0) {
            const competencias = Array.from(competenciasSeleccionadas).map(cb => ({
                materia_competencia_id: cb.dataset.competenciaId,
                materia_id: cb.dataset.materiaId,
                materia_nombre: cb.dataset.materiaNombre,
                competencia_nombre: cb.dataset.competenciaNombre,
                nota_original: cb.dataset.notaOriginal
            }));

            estudiantes.push({
                estudiante_id: estudianteId,
                periodo_recuperacion_id: periodoRecuperacionId,
                periodo_academico_id: periodoAcademicoId,
                competencias: competencias
            });
        }
    }

    if (estudiantes.length === 0) {
        await Swal.fire({
            icon: 'warning',
            title: 'Sin competencias',
            text: 'Los estudiantes seleccionados no tienen competencias marcadas para recuperación.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    const result = await Swal.fire({
        title: '¿Confirmar matrícula masiva?',
        text: `¿Matricular ${estudiantes.length} estudiante(s) en período de recuperación?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, matricular todos',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await fetch('{{ route("estudiante.matricular.recuperacion") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ estudiantes: estudiantes })
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Proceso completado!',
                    text: data.message,
                    confirmButtonColor: '#28a745'
                });
                location.reload();
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la solicitud',
                confirmButtonColor: '#dc3545'
            });
        }
    }
}

// FUNCIONES DE NOTAS DE RECUPERACIÓN
async function guardarNotaRecuperacion(recuperacionId, nivelLogro, estudianteId, competenciaId) {
    console.log('Intentando guardar - ID:', recuperacionId, 'Nota:', nivelLogro);

    if (!recuperacionId || recuperacionId === '') {
        await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'ID de recuperación no encontrado. Por favor, recargue la página y vuelva a intentar.',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    const result = await Swal.fire({
        title: '¿Guardar nota?',
        text: `¿Asignar la nota ${nivelLogro} a esta competencia?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: 'Guardando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await fetch('{{ route("estudiante.recuperacion.nota") }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    recuperacion_id: parseInt(recuperacionId),
                    nivel_logro_final: nivelLogro
                })
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Nota guardada!',
                    text: data.message,
                    confirmButtonColor: '#28a745'
                });
                location.reload();
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al guardar la nota: ' + error.message,
                confirmButtonColor: '#dc3545'
            });
        }
    }
}

// FUNCIONES DE CAMBIO DE ESTADO
async function cambiarEstadoNotasRecuperacion(estudianteId, nuevoEstado) {
    const accion = nuevoEstado == '1' ? 'bloquear' : 'liberar';
    const textoConfirmacion = nuevoEstado == '1'
        ? 'Una vez bloqueadas, no se podrán modificar las notas de recuperación.'
        : 'Las notas de recuperación podrán ser editadas nuevamente.';

    const result = await Swal.fire({
        title: `${accion === 'bloquear' ? '🔒 Bloquear' : '🔓 Liberar'} Notas de Recuperación`,
        html: `
            <p>¿Estás seguro de que deseas ${accion} las notas de recuperación?</p>
            <small class="text-muted">${textoConfirmacion}</small>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado == '1' ? '#d33' : '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await fetch('{{ route("estudiante.cambiar.estado.notas") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    periodo_recuperacion_id: {{ $periodoRecuperacion->id ?? 'null' }},
                    grado_id: {{ $grado->id }},
                    estudiante_id: estudianteId,
                    nuevo_estado: nuevoEstado
                })
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Completado!',
                    text: data.message,
                    confirmButtonColor: '#28a745'
                });
                location.reload();
            } else {
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la solicitud',
                confirmButtonColor: '#dc3545'
            });
        }
    }
}

// FUNCIONES DE ASCENSO DE ESTUDIANTES
async function ascenderEstudiantes() {
    const selectedCheckboxes = document.querySelectorAll('.estudiante-aprobado-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    const nuevoGrado = document.getElementById('nuevo_grado').value;
    const nuevaSeccion = document.getElementById('nueva_seccion').value;
    const nuevoNivel = document.getElementById('nuevo_nivel').value;

    if (selectedIds.length === 0) {
        await Swal.fire({
            icon: 'warning',
            title: 'Sin selección',
            text: 'No hay estudiantes seleccionados para ascender',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    if (!nuevoGrado || !nuevaSeccion || !nuevoNivel) {
        await Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Seleccione el grado, sección y nivel de destino',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    const result = await Swal.fire({
        title: '¿Confirmar ascenso?',
        text: `¿Ascender ${selectedIds.length} estudiante(s) al ${nuevoGrado}° "${nuevaSeccion}" - ${nuevoNivel}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, ascender',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        const form = document.getElementById('ascenderForm');
        const existingInputs = form.querySelectorAll('input[name="estudiantes[]"]');
        existingInputs.forEach(input => input.remove());

        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'estudiantes[]';
            input.value = id;
            form.appendChild(input);
        });

        form.submit();
    }
}

// INICIALIZACIÓN DE EVENTOS
function initEventos() {
    // Switch de formato de notas
    const formatoSwitch = document.getElementById('formatoNotas');
    const formatoLabel = document.getElementById('formatoLabel');
    let modoCualitativo = false;

    if (formatoSwitch) {
        formatoSwitch.addEventListener('change', function() {
            modoCualitativo = this.checked;
            actualizarFormatoNotas(modoCualitativo);
            formatoLabel.innerHTML = modoCualitativo
                ? '<span class="badge bg-success px-3 py-2">C,B,A,AD</span>'
                : '<span class="badge bg-primary px-3 py-2">1-4</span>';
        });
    }

    // Cambio de año
    document.getElementById('anio')?.addEventListener('change', function() {
        document.getElementById('formAnio').submit();
    });

    // Selección de estudiantes aprobados
    const selectAllAprobados = document.getElementById('selectAllAprobados');
    const checkboxesAprobados = document.querySelectorAll('.estudiante-aprobado-checkbox');

    function updateAscensoButtonState() {
        const ascenderBtn = document.getElementById('ascenderBtn');
        const selectedCountSpan = document.getElementById('selectedCount');
        const selectedCount = document.querySelectorAll('.estudiante-aprobado-checkbox:checked').length;
        const destinoCompleto = document.getElementById('nuevo_grado').value &&
                               document.getElementById('nueva_seccion').value &&
                               document.getElementById('nuevo_nivel').value;

        if (ascenderBtn) {
            ascenderBtn.disabled = selectedCount === 0 || !destinoCompleto;
        }
        if (selectedCountSpan) {
            selectedCountSpan.textContent = selectedCount;
        }
    }

    if (selectAllAprobados) {
        selectAllAprobados.addEventListener('change', function() {
            checkboxesAprobados.forEach(cb => cb.checked = selectAllAprobados.checked);
            updateAscensoButtonState();
        });
    }

    // Selección de estudiantes en recuperación
    const selectAllRecuperacion = document.getElementById('selectAllRecuperacion');
    const checkboxesRecuperacion = document.querySelectorAll('.estudiante-recuperacion-checkbox');

    if (selectAllRecuperacion) {
        selectAllRecuperacion.addEventListener('change', function() {
            checkboxesRecuperacion.forEach(cb => {
                cb.checked = selectAllRecuperacion.checked;
                if (cb.checked) seleccionarCompetenciasEstudiante(cb.value, true);
            });
        });
    }

    checkboxesRecuperacion.forEach(cb => {
        cb.addEventListener('change', function() {
            seleccionarCompetenciasEstudiante(this.value, this.checked);
        });
    });

    checkboxesAprobados.forEach(cb => cb.addEventListener('change', updateAscensoButtonState));

    // Inputs de ascenso
    const nuevoGrado = document.getElementById('nuevo_grado');
    const nuevaSeccion = document.getElementById('nueva_seccion');
    const nuevoNivel = document.getElementById('nuevo_nivel');

    [nuevoGrado, nuevaSeccion, nuevoNivel].forEach(input => {
        if (input) input.addEventListener('change', updateAscensoButtonState);
    });

    updateAscensoButtonState();

    // Seleccionar todas las competencias en cada modal
    @foreach($estudiantesMatriculados as $estudiante)
        const selectAllComp{{ $estudiante->id }} = document.getElementById('selectAllCompetencias{{ $estudiante->id }}');
        if (selectAllComp{{ $estudiante->id }}) {
            selectAllComp{{ $estudiante->id }}.addEventListener('change', function() {
                document.querySelectorAll(`#modal{{ $estudiante->id }} .competencia-recuperacion`).forEach(cb => cb.checked = this.checked);
            });
        }
    @endforeach

    // Botón de ascender
    const ascenderBtn = document.getElementById('ascenderBtn');
    if (ascenderBtn) {
        ascenderBtn.addEventListener('click', ascenderEstudiantes);
    }

    // Botón de matricular recuperación masivo
    const btnMasivo = document.getElementById('btnMatricularRecuperacionMasivo');
    if (btnMasivo) {
        btnMasivo.addEventListener('click', function() {
            matricularRecuperacionMasiva({{ $periodoRecuperacion->id ?? 'null' }}, {{ $periodoAcademico->id }});
        });
    }

    // Botones de guardar nota de recuperación
    const botonesGuardar = document.querySelectorAll('.guardar-nota-rec');
    console.log('Botones de guardar encontrados:', botonesGuardar.length);

    botonesGuardar.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const container = this.closest('.recuperacion-container');
            if (!container) {
                console.error('No se encontró el contenedor');
                return;
            }

            const select = container.querySelector('.nota-recuperacion-select');
            if (!select) {
                console.error('No se encontró el select');
                return;
            }

            const recuperacionId = select.getAttribute('data-rec-id');
            const nivelLogro = select.value;
            const estudianteId = select.getAttribute('data-estudiante-id');
            const competenciaId = select.getAttribute('data-competencia-id');

            console.log('Datos obtenidos:', { recuperacionId, nivelLogro, estudianteId, competenciaId });

            if (!nivelLogro) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nota no seleccionada',
                    text: 'Por favor, seleccione una nota para la recuperación',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            guardarNotaRecuperacion(recuperacionId, nivelLogro, estudianteId, competenciaId);
        });
    });

    // Botones de cambiar estado (bloquear/liberar)
    const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');
    console.log('Botones de cambiar estado encontrados:', botonesCambiarEstado.length);

    botonesCambiarEstado.forEach(btn => {
        btn.addEventListener('click', function() {
            const estudianteId = this.dataset.estudianteId;
            const estadoActual = this.dataset.estadoActual;
            const nuevoEstado = estadoActual === '0' ? '1' : '0';
            cambiarEstadoNotasRecuperacion(estudianteId, nuevoEstado);
        });
    });
}

// INICIALIZACIÓN PRINCIPAL
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando aplicación...');
    initEventos();
});
</script>
@endsection
