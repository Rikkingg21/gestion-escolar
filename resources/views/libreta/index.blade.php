@extends('layouts.app')

@section('content')
<div class="container-fluid mt-3">
    <div class="card border-2 border-primary rounded-3 mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center py-3 border-bottom border-2 border-white">
            <h4 class="mb-0 text-black"><strong>Libreta de Calificaciones</strong></h4>
        </div>
        <div class="card-body bg-light">
            <form id="pdfForm" action="{{ route('libreta.pdf', ['anio' => $anio_param, 'bimestre' => $bimestre_param]) }}" method="POST">
                @csrf
                <!-- Campo oculto para el tipo de PDF -->
                <input type="hidden" name="tipo_pdf" id="tipoPdf" value="cuantitativo">

                <div class="row g-3 align-items-end mb-4">
                    <!-- Periodo -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="anio" class="form-label fw-bold text-primary">
                            <i class="fas fa-calendar-alt me-2"></i>Año Académico
                        </label>
                        <select name="anio" id="anio" class="form-select border-2 border-primary shadow-sm" onchange="cambiarPeriodo(this.value)">
                            <option value="">-- Seleccione Año --</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->anio }}"
                                    {{ $anio_param == $periodo->anio ? 'selected' : '' }}>
                                    {{ $periodo->anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bimestre -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="bimestre" class="form-label fw-bold text-success">
                            <i class="fas fa-chart-line me-2"></i>Bimestre
                        </label>
                        <select name="bimestre" id="bimestre" class="form-select border-2 border-success shadow-sm" onchange="cambiarBimestre(this.value)">
                            <option value="anual">Anual</option>
                            @for($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ $bimestre_param == $i ? 'selected' : '' }}>
                                    Bimestre {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <!-- Botón PDF -->
                    <div class="col-12 col-md-4 text-end">
                        <button type="button" class="btn btn-danger btn-lg w-100 shadow-lg py-2 mt-3 mt-md-0"
                                data-bs-toggle="modal" data-bs-target="#pdfModal">
                            <i class="fas fa-file-pdf me-2"></i> Descargar PDF
                        </button>
                    </div>
                </div>
            </form>

            <!-- Modal para seleccionar tipo de PDF -->
            <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="pdfModalLabel">
                                <i class="fas fa-file-pdf me-2"></i>Seleccionar Formato del PDF
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-4">¿En qué formato desea generar el reporte de calificaciones?</p>

                            <div class="row g-3">
                                <!-- Opción Cuantitativo -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-2 border-primary option-card"
                                        data-tipo="cuantitativo" onclick="seleccionarTipo('cuantitativo')">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="fas fa-calculator fa-3x text-primary"></i>
                                            </div>
                                            <h5 class="card-title fw-bold text-primary">Cuantitativo</h5>
                                            <p class="card-text text-muted small">
                                                Calificaciones en números (1-4)
                                            </p>
                                            <div class="mt-2">
                                                <span class="badge bg-primary">Ejemplo: 3.5</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Opción Cualitativo -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-2 border-success option-card"
                                        data-tipo="cualitativo" onclick="seleccionarTipo('cualitativo')">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="fas fa-chart-bar fa-3x text-success"></i>
                                            </div>
                                            <h5 class="card-title fw-bold text-success">Cualitativo</h5>
                                            <p class="card-text text-muted small">
                                                Calificaciones en letras (A-D)
                                            </p>
                                            <div class="mt-2">
                                                <span class="badge bg-success">Ejemplo: A</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Nota informativa -->
                            <div class="alert alert-info mt-4 mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>
                                    <strong>Cuantitativo:</strong> Escala numérica del 1 al 4<br>
                                    <strong>Cualitativo:</strong> C = 1, B = 2, A = 3, AD = 4
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" id="btnGenerarPdf" onclick="generarPdf()" disabled>
                                <i class="fas fa-download me-2"></i>Generar PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN: Datos del estudiante en el periodo -->
            @if($matricula_actual && $periodo_actual)
            <div class="table border border-2 border-dark rounded-3 p-4 mb-4 bg-white">
                <!-- Encabezado de la libreta -->
                <div class="text-center mb-4">
                    <div class="h3 fw-bold text-primary border-bottom border-2 border-primary pb-2">
                        LIBRETA DE CALIFICACIONES DEL ESTUDIANTE (sec EBR)
                    </div>
                    <div class="h5 fw-bold text-success mt-2">
                        {{ $periodo_actual->anio }} -
                        @if($bimestre_param == 'anual')
                            EVALUACIÓN ANUAL
                        @else
                            {{ $bimestre_param }}° BIMESTRE
                        @endif
                    </div>
                </div>

                <!-- Contenido de la libreta -->
                <div class="row align-items-center">
                    <!-- Logo -->
                    <div class="col-sm-3 text-center border-end border-2 border-dark pe-3">
                        @if($colegio->logo_path)
                        <img src="{{ Storage::url($colegio->logo_path) }}" alt="Logo del colegio" style="height: 300px" class="img-thumbnail border-0">
                        @else
                        <div class="border border-2 border-secondary rounded-3 p-4 mb-3 bg-light">
                            <i class="fas fa-school fa-3x text-muted"></i>
                            <div class="mt-2 text-muted small">LOGO</div>
                        </div>
                        @endif
                    </div>

                    <!-- Datos -->
                    <div class="col-sm-9 ps-4">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0 text-center">
                                <!-- UGEL -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td width="35%" class="border-1 fw-bold text-dark ps-0">
                                        UGEL:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $colegio->ugel ?? 'Tacna' }}</strong>
                                    </td>
                                </tr>
                                <!-- II.EE -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        II.EE:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $colegio->nombre ?? 'NO REGISTRADO' }}</strong>
                                    </td>
                                </tr>
                                <!-- NIVEL -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        NIVEL:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $matricula_actual->grado->nivel ?? 'No disponible' }}</strong>
                                    </td>
                                </tr>
                                <!-- GRADO -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        GRADO:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $matricula_actual->grado->grado ?? 'No disponible' }}°</strong>
                                    </td>
                                </tr>
                                <!-- SECCIÓN -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        SECCIÓN:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>"{{ $matricula_actual->grado->seccion ?? 'No disponible' }}"</strong>
                                    </td>
                                </tr>
                                <!-- ESTUDIANTE -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        ESTUDIANTE:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong class="text-primary">
                                            {{ $estudiante->user->apellido_paterno }}
                                            {{ $estudiante->user->apellido_materno }},
                                            {{ $estudiante->user->nombre }}
                                        </strong>
                                    </td>
                                </tr>
                                <!-- DNI ESTUDIANTE -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        DNI:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $estudiante->user->dni }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Calificaciones de Materias -->
            @if(count($materias_regulares) > 0 || count($competencias_transversales) > 0)
            <div class="mt-4">
                <div class="border border-2 border-success rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-success mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Calificaciones Académicas
                        </h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="switchCualitativo" checked>
                            <label class="form-check-label fw-bold" for="switchCualitativo">
                                <span id="labelSwitchCualitativo">Cuantitativo</span>
                            </label>
                        </div>
                    </div>

                    <!-- Mostrar Materias Regulares -->
                    @if(count($materias_procesadas) > 0)
                    <div class="card mb-4 border shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">
                                <i class="fas fa-table me-2"></i>Calificaciones Regulares
                            </h5>
                            @if($promedio_general_materias > 0)
                            <div class="badge bg-success fs-6">
                                Promedio General: {{ number_format($promedio_general_materias, 1) }}
                            </div>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-primary">
                                    <tr class="text-center align-middle">
                                        <th style="width: 20%;" class="fw-bold">Materia</th>
                                        <th style="width: 20%;" class="fw-bold">Competencia</th>
                                        <th style="width: 25%;" class="fw-bold">Criterio</th>
                                        <th style="width: 5%;" class="fw-bold">Bimestre</th>
                                        <th style="width: 5%;" class="fw-bold">CRIT</th>
                                        <th style="width: 15%;" class="fw-bold">Calificación</th>
                                        <th style="width: 10%;" class="fw-bold">Promedio Materia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $criterioCounter = 0; $competenciaCounter = 0; @endphp
                                    @foreach($materias_procesadas as $materiaIndex => $materia)
                                        @php $competenciaInMateriaCounter = 0; @endphp
                                        @foreach($materia['competencias'] as $competenciaIndex => $competencia)
                                            @php
                                                $competenciaCounter++;
                                                $competenciaInMateriaCounter++;
                                            @endphp

                                            <!-- Mostrar criterios de la competencia -->
                                            @foreach($competencia['criterios'] as $criterioIndex => $criterio)
                                                @php $criterioCounter++; @endphp
                                                <tr>
                                                    <!-- Columna Materia -->
                                                    @if($competenciaIndex === 0 && $criterioIndex === 0)
                                                    <td rowspan="{{ $materia['rowspan'] }}" class="align-middle bg-light text-center">
                                                        <div class="fw-bold text-primary">
                                                            {{ $materia['nombre'] }}
                                                        </div>
                                                    </td>
                                                    @endif

                                                    <!-- Columna Competencia -->
                                                    @if($criterioIndex === 0)
                                                    <td rowspan="{{ $competencia['criterios_count'] + 1 }}"
                                                        class="align-middle bg-success bg-opacity-10">
                                                        <div class="fw-semibold text-success">
                                                            {{ $competencia['nombre'] }}
                                                        </div>
                                                    </td>
                                                    @endif

                                                    <!-- Columna Criterio -->
                                                    <td class="align-middle">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bullet-excel me-2">
                                                                {{ $criterio['criterio_nombre'] }}
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <!-- Columna Bimestre -->
                                                    <td class="text-center align-middle">
                                                        @if($criterio['nota'] && $criterio['nota']['bimestre'])
                                                        <span class="text-dark">
                                                            B{{ $criterio['nota']['bimestre'] }}
                                                        </span>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>

                                                    <!-- Columna CRIT -->
                                                    <td class="text-center align-middle fw-bold text-info">
                                                        C{{ $criterioCounter }}
                                                    </td>

                                                    <!-- Columna Calificación -->
                                                    <td class="text-center align-middle fw-bold fs-5">
                                                        @if($criterio['nota'])
                                                            <span class="px-3 py-1 nota-valor">
                                                                {{ $criterio['nota']['valor'] }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                                N/A
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <!-- Columna Promedio Materia -->
                                                    @if($competenciaIndex === 0 && $criterioIndex === 0)
                                                    <td rowspan="{{ $materia['rowspan'] }}" class="text-center align-middle">
                                                        @if($materia['promedio'] > 0)
                                                            @php
                                                                $materiaTextClass = $materia['promedio'] >= 3.5 ? 'text-success' :
                                                                                    ($materia['promedio'] >= 2.5 ? 'text-warning' : 'text-danger');
                                                            @endphp
                                                            <div class="d-flex flex-column align-items-center justify-content-center">
                                                                <span class="fs-5 px-4 py-2 mb-1 fw-bold nota-promedio {{ $materiaTextClass }}">
                                                                    {{ number_format($materia['promedio'], 0) }}
                                                                </span>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calculator me-1"></i>Promedio
                                                                </small>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    @endif
                                                </tr>
                                            @endforeach

                                            <!-- Fila de Valoración de Competencia -->
                                            <tr class="bg-warning bg-opacity-10">
                                                <!-- Columna Criterio (para la fila de valoración) -->
                                                <td class="align-middle fw-bold text-warning">
                                                    <i class="fas fa-star me-2"></i>Valoración Competencia
                                                </td>

                                                <!-- Columna Bimestre -->
                                                <td class="text-center align-middle">
                                                    @if($competencia['ultimo_criterio'] && $competencia['ultimo_criterio']['nota'] && $competencia['ultimo_criterio']['nota']['bimestre'])
                                                    <span class="text-dark">
                                                        B{{ $competencia['ultimo_criterio']['nota']['bimestre'] }}
                                                    </span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>

                                                <!-- Columna CRIT (muestra N1, N2, etc.) -->
                                                <td class="text-center align-middle fw-bold text-success">
                                                    N{{ $competenciaCounter }}
                                                </td>

                                                <!-- Columna Calificación (muestra el promedio) -->
                                                <td class="text-center align-middle">
                                                    @if($competencia['promedio'] > 0)
                                                    @php
                                                        $compTextClass = $competencia['promedio'] >= 3.5 ? 'text-success' :
                                                                        ($competencia['promedio'] >= 2.5 ? 'text-warning' : 'text-danger');
                                                    @endphp
                                                    <span class="fw-bold fs-5 nota-promedio {{ $compTextClass }}">
                                                        {{ number_format($competencia['promedio'], 0) }}
                                                    </span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="7" class="text-center py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-start">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Escala de calificación: 1-4
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted">
                                                        <i class="fas fa-hashtag me-1"></i>
                                                        Total CRIT (C): <strong class="text-info">{{ $numero_criterio_global }}</strong> |
                                                        Total Valoraciones (N): <strong class="text-success">{{ $numero_competencia_global }}</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Mostrar Competencias Transversales -->
                    @if(count($competencias_transversales) > 0)
                    <div class="mb-4 border border-2 border-info rounded-2 p-3">
                        <!-- Encabezado de Competencias Transversales -->
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-1 border-info">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exchange-alt text-info me-2"></i>
                                <h6 class="fw-bold text-info mb-0">COMPETENCIAS TRANSVERSALES</h6>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-2">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="fw-bold" style="width: 60%">CRITERIO</th>
                                            <th class="fw-bold text-center" style="width: 20%">BIMESTRE</th>
                                            <th class="fw-bold text-center" style="width: 20%">NOTA PROMEDIO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($criterios_transversales as $criterioNombre => $data)
                                        @php
                                            $promedioCriterio = $promedios_por_criterio[$criterioNombre] ?? 0;
                                            $bimestresUnicos = array_unique($data['bimestres']);
                                            $bimestreTexto = count($bimestresUnicos) > 0 ?
                                                implode(', ', $bimestresUnicos) : '-';
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ $criterioNombre }}
                                            </td>
                                            <td class="text-center">
                                                @if($bimestreTexto != '-')
                                                    {{ $bimestreTexto }}
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-bold">
                                                @if($promedioCriterio > 0)
                                                    <span class="nota-promedio">
                                                        {{ number_format($promedioCriterio, 0) }}
                                                    </span>
                                                <small class="text-muted d-block">
                                                    ({{ count($data['notas']) }} evaluación(es))
                                                </small>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach

                                        <!-- Fila de Valoración General -->
                                        <tr class="table-info">
                                            <td colspan="2" class="fw-bold text-end">
                                                <i class="fas fa-star-half-alt text-info me-1"></i>Valoración General de Competencias Transversales
                                            </td>
                                            <td class="text-center fw-bold">
                                                <span class="nota-promedio">{{ number_format($promedio_general_transversales, 0) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Detalles de Competencias Transversales -->
                        <div class="mt-3 pt-3 border-top border-1">
                            <div class="accordion" id="accordionTransversalesDetalles">
                                <div class="accordion-item border border-1 border-secondary rounded-2">
                                    <h2 class="accordion-header" id="headingTransversalesDetalles">
                                        <button class="accordion-button collapsed bg-light py-2 text-dark fw-bold fs-6" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapseTransversalesDetalles"
                                                aria-expanded="false" aria-controls="collapseTransversalesDetalles">
                                                Ver detalles de criterios
                                        </button>
                                    </h2>
                                    <div id="collapseTransversalesDetalles" class="accordion-collapse collapse"
                                        aria-labelledby="headingTransversalesDetalles" data-bs-parent="#accordionTransversalesDetalles">
                                        <div class="accordion-body bg-white rounded-2 mt-2 p-3">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="fw-bold">Materia</th>
                                                            <th class="fw-bold">Competencia</th>
                                                            <th class="fw-bold">Criterio</th>
                                                            <th class="fw-bold text-center">Bimestre</th>
                                                            <th class="fw-bold text-center">Nota</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($competencias_transversales as $transversal)
                                                            @foreach($transversal['competencia']['criterios'] as $criterio)
                                                            <tr>
                                                                <td class="fw-semibold">
                                                                    {{ $transversal['materia_nombre'] }}
                                                                </td>
                                                                <td class="text-info">
                                                                    {{ $transversal['competencia']['competencia_nombre'] }}
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-arrow-circle-right text-info me-1" style="font-size: 0.7rem;"></i>
                                                                    {{ $criterio['criterio_nombre'] }}
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($criterio['nota'] && $criterio['nota']['bimestre'])
                                                                        B{{ $criterio['nota']['bimestre'] }}
                                                                    @else
                                                                    <span class="text-muted"> - </span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center fw-bold">
                                                                    @if($criterio['nota'])
                                                                        <span class="nota-valor">{{ $criterio['nota']['valor'] }}</span>
                                                                    @else
                                                                    <span class="text-muted"> - </span>
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
                            </div>

                            <!-- Nota sobre Competencias Transversales -->
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <small class="mb-0">
                                    <strong>Nota:</strong> Las competencias transversales se evalúan de forma independiente y
                                    <strong>no se incluyen</strong> en el cálculo del promedio regular de las materias.
                                    Su promedio general se calcula únicamente entre las competencias transversales.
                                </small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Sección de Calificaciones de Conducta -->
            @if($notas_conducta->count() > 0)
            <div class="mt-4">
                <div class="border border-2 border-info rounded-3 p-3">
                    <h5 class="fw-bold text-info mb-3">
                        <i class="fas fa-user-check me-2"></i>Calificaciones de Conducta
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-bordered bg-white">
                            <thead class="table-info">
                                <tr>
                                    <th class="fw-bold">Conducta</th>
                                    <th class="fw-bold text-center">Bimestre</th>
                                    <th class="fw-bold text-center">Calificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notas_conducta as $notaConducta)
                                <tr>
                                    <td class="fw-semibold">{{ $notaConducta['conducta_nombre'] }}</td>
                                    <td class="text-center">
                                        @if($notaConducta['bimestre'])
                                            B{{ $notaConducta['bimestre'] }}
                                        @else
                                            <span class="text-muted"> - </span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold fs-5">
                                        <span class="nota-valor">{{ $notaConducta['valor'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Sección de Asistencias -->
            @if($asistencias->count() > 0)
            <div class="mt-4">
                <div class="border border-2 border-warning rounded-3 p-3">
                    <h5 class="fw-bold text-warning mb-3">
                        <i class="fas fa-calendar-check me-2"></i>Registro de Asistencias
                    </h5>

                    <!-- Resumen de Asistencias -->
                    @if(count($resumen_asistencias['tipos']) > 0)
                    <div class="mb-4 border border-1 border-secondary rounded-2 p-3 bg-light">
                        <h6 class="fw-bold text-dark mb-3">Resumen General</h6>
                        <table class="table table-border text-center mb-4">
                            <thead>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                            </thead>
                            <tbody>
                                @foreach($resumen_asistencias['tipos'] as $tipo)
                                <tr>
                                    <td>{{ $tipo['tipo_nombre'] }}</td>
                                    <td>{{ $tipo['cantidad'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <!-- Lista Detallada de Asistencias -->
                    <div class="accordion mb-3" id="accordionAsistenciasDetalladas">
                        <div class="accordion-item border border-1 border-secondary rounded-2">
                            <h2 class="accordion-header" id="headingAsistenciasDetalladas">
                                <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAsistenciasDetalladas" aria-expanded="false" aria-controls="collapseAsistenciasDetalladas">
                                    Registros Detallados
                                </button>
                            </h2>
                            <div id="collapseAsistenciasDetalladas" class="accordion-collapse collapse" aria-labelledby="headingAsistenciasDetalladas" data-bs-parent="#accordionAsistenciasDetalladas">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive p-3">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="border-1">Fecha</th>
                                                    <th class="border-1">Hora</th>
                                                    <th class="border-1">Tipo</th>
                                                    <th class="border-1">Descripción</th>
                                                    <th class="border-1">Bimestre</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($asistencias as $asistencia)
                                                <tr class="border-bottom border-1 border-light">
                                                    <td class="border-1">
                                                        <i class="fas fa-calendar-day text-primary me-1"></i>
                                                        {{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}
                                                    </td>
                                                    <td class="border-1">
                                                        @if($asistencia->hora)
                                                        <i class="fas fa-clock text-info me-1"></i>
                                                        {{ \Carbon\Carbon::parse($asistencia->hora)->format('h:i A') }}
                                                        @else
                                                        <span class="text-muted"> 0</span>
                                                        @endif
                                                    </td>
                                                    <td class="border-1">
                                                        <span class="badge
                                                            @if($asistencia->tipoasistencia)
                                                                @if(str_contains(strtolower($asistencia->tipoasistencia->nombre), 'tardanza')) bg-warning
                                                                @elseif(str_contains(strtolower($asistencia->tipoasistencia->nombre), 'falta')) bg-danger
                                                                @else bg-success
                                                                @endif
                                                            @else bg-secondary
                                                            @endif">
                                                            {{ $asistencia->tipoasistencia->nombre ?? 'Sin tipo' }}
                                                        </span>
                                                    </td>
                                                    <td class="border-1">
                                                        @if($asistencia->descripcion)
                                                        <small>{{ $asistencia->descripcion }}</small>
                                                        @else
                                                        <span class="text-muted"> 0</span>
                                                        @endif
                                                    </td>
                                                    <td class="border-1">
                                                        <span class="badge bg-info">Bim {{ $asistencia->bimestre }}</span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                         <a href="{{ route('asistencia.calendario', ['anio' => $anio_param, 'bimestre' => $bimestre_param]) }}">Ver más detalles aquí</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($matricula_actual)
            <!-- Mensaje si no hay asistencias pero sí matrícula -->
            <div class="mt-4">
                <div class="alert alert-warning border-2 border-warning">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-times fa-2x me-3 text-warning"></i>
                        <div>
                            <h5 class="mb-1 text-warning">No hay registros de asistencia</h5>
                            <p class="mb-0">No se encontraron registros de asistencia para el periodo seleccionado.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @else
            <!-- Mensaje si no hay matrícula -->
            <div class="alert alert-warning border border-2 border-warning rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                    <div>
                        <h5 class="mb-1 text-warning">No se encontró matrícula</h5>
                        <p class="mb-0">El estudiante no tiene matrícula registrada para el año {{ $anio_param }}.</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<script>
function cambiarPeriodo(anio) {
    if (!anio) return;
    const baseUrl = "{{ route('libreta.index', ['anio' => 'ANIO_PLACEHOLDER', 'bimestre' => '1']) }}";
    const url = baseUrl.replace('ANIO_PLACEHOLDER', anio);
    window.location.href = url;
}

function cambiarBimestre(bimestre) {
    if (!bimestre) return;
    const baseUrl = "{{ route('libreta.index', ['anio' => $anio_param, 'bimestre' => 'BIMESTRE_PLACEHOLDER']) }}";
    const url = baseUrl.replace('BIMESTRE_PLACEHOLDER', bimestre);
    window.location.href = url;
}

// Cambiar entre cuantitativo y cualitativo
document.addEventListener('DOMContentLoaded', function() {
    const switchCualitativo = document.getElementById('switchCualitativo');
    const labelSwitch = document.getElementById('labelSwitchCualitativo');
    function valorCualitativo(valor) {
        switch (parseInt(valor)) {
            case 1: return 'C';
            case 2: return 'B';
            case 3: return 'A';
            case 4: return 'AD';
            default: return valor;
        }
    }
    function actualizarNotas() {
        const esCualitativo = !switchCualitativo.checked;
        document.querySelectorAll('.nota-valor').forEach(function(span) {
            const original = span.getAttribute('data-original');
            if (!original) {
                span.setAttribute('data-original', span.textContent.trim());
            }
            const valor = span.getAttribute('data-original');
            span.textContent = esCualitativo ? valorCualitativo(valor) : valor;
        });
        document.querySelectorAll('.nota-promedio').forEach(function(span) {
            const original = span.getAttribute('data-original');
            if (!original) {
                span.setAttribute('data-original', span.textContent.trim());
            }
            const valor = span.getAttribute('data-original');
            span.textContent = esCualitativo ? valorCualitativo(valor) : valor;
        });
        labelSwitch.textContent = esCualitativo ? 'Cualitativo' : 'Cuantitativo';
    }
    if (switchCualitativo) {
        switchCualitativo.addEventListener('change', actualizarNotas);
        actualizarNotas();
    }
});
</script>
<script>
let tipoSeleccionado = null;

function seleccionarTipo(tipo) {
    // Remover selección anterior
    document.querySelectorAll('.option-card').forEach(card => {
        card.classList.remove('selected');
        card.style.backgroundColor = '#fff';
    });

    // Agregar selección actual
    const card = document.querySelector(`.option-card[data-tipo="${tipo}"]`);
    card.classList.add('selected');
    card.style.backgroundColor = '#f0f8ff';

    // Actualizar variable y habilitar botón
    tipoSeleccionado = tipo;
    document.getElementById('tipoPdf').value = tipo;
    document.getElementById('btnGenerarPdf').disabled = false;

    // Cambiar color del botón según selección
    const btn = document.getElementById('btnGenerarPdf');
    if (tipo === 'cualitativo') {
        btn.className = 'btn btn-success';
        btn.innerHTML = '<i class="fas fa-download me-2"></i>Generar PDF Cualitativo';
    } else {
        btn.className = 'btn btn-primary';
        btn.innerHTML = '<i class="fas fa-download me-2"></i>Generar PDF Cuantitativo';
    }
}

function generarPdf() {
    if (!tipoSeleccionado) {
        alert('Por favor seleccione un formato para el PDF');
        return;
    }

    // Mostrar loading
    const btn = document.getElementById('btnGenerarPdf');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando...';
    btn.disabled = true;

    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('pdfModal'));
    modal.hide();

    // Enviar formulario
    setTimeout(() => {
        document.getElementById('pdfForm').submit();

        // Restaurar botón después de 3 segundos
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            tipoSeleccionado = null;

            // Limpiar selección
            document.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected');
                card.style.backgroundColor = '#fff';
            });
        }, 3000);
    }, 500);
}
</script>
@endsection
