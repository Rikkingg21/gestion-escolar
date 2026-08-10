@extends('layouts.app')
@section('title', 'Panel del Docente')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Dashboard Docente</h1>
                    <p class="mb-0 text-muted">
                        Bienvenido/a, {{ $docente->user->nombre ?? 'Docente' }}
                        @if($docente->titulo)
                            - {{ $docente->titulo }}
                        @endif
                    </p>
                </div>
                <div>
                    <span class="badge bg-primary">
                        <i class="bi bi-person-video3 me-1"></i> Docente
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtro de Periodo -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar3 me-2"></i>Seleccionar Periodo Académico
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ request()->url() }}" class="row g-3">
                        <div class="col-md-8">
                            <select name="periodo_id" class="form-select" onchange="this.form.submit()">
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->id }}"
                                        {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                        {{ $periodo->nombre }}
                                        @if($periodo->estado == 1) (Activo) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Resumen del Periodo
                    </h5>
                </div>
                <div class="card-body">
                    @if($periodoSeleccionado && count($asignacionesData) > 0)
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h3 class="text-primary mb-0">{{ count($asignacionesData) }}</h3>
                                    <small class="text-muted">Materias</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    @php
                                        $totalEstudiantes = 0;
                                        foreach($asignacionesData as $data) {
                                            $totalEstudiantes += $data['total_estudiantes'];
                                        }
                                    @endphp
                                    <h3 class="text-success mb-0">{{ $totalEstudiantes }}</h3>
                                    <small class="text-muted">Estudiantes</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    @php
                                        $totalNotasRegistradas = 0;
                                        $totalNotasPosibles = 0;
                                        foreach($asignacionesData as $data) {
                                            foreach($data['estadisticas_bimestres']['notas'] as $stats) {
                                                $totalNotasRegistradas += $stats['total_notas_registradas'] ?? 0;
                                                $totalNotasPosibles += $stats['total_notas_posibles'] ?? 0;
                                            }
                                        }
                                        $porcentajeGlobal = $totalNotasPosibles > 0 ? round(($totalNotasRegistradas / $totalNotasPosibles) * 100, 1) : 0;
                                    @endphp
                                    <h3 class="text-warning mb-0">{{ $porcentajeGlobal }}%</h3>
                                    <small class="text-muted">Notas Registradas</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0 text-center">No hay datos disponibles</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($periodoSeleccionado && count($asignacionesData) > 0)
        @foreach($asignacionesData as $asignacionId => $data)
        <div class="card mb-5 shadow" id="asignacion-{{ $asignacionId }}">
            <!-- Cabecera de la asignación -->
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <i class="bi bi-book me-2"></i>
                            {{ $data['materia_nombre'] }}
                            <small class="text-white-50">- {{ $data['grado_nombre'] }}</small>
                        </h4>
                        <p class="mb-0 mt-1">
                            <small>
                                <i class="bi bi-calendar me-1"></i> {{ $data['periodo_anio'] }}
                                | <i class="bi bi-people me-1"></i> {{ $data['total_estudiantes'] }} estudiantes
                            </small>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light toggle-section" data-target="#graficos-{{ $asignacionId }}">
                            <i class="bi bi-graph-up"></i> Gráficos
                        </button>
                        <button class="btn btn-sm btn-light toggle-section" data-target="#detalles-{{ $asignacionId }}">
                            <i class="bi bi-bar-chart"></i> Estadísticas
                        </button>
                        <button class="btn btn-sm btn-light toggle-section" data-target="#estudiantes-{{ $asignacionId }}">
                            <i class="bi bi-list"></i> Estudiantes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="graficos-section" id="graficos-{{ $asignacionId }}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Progreso Académico</h6>
                                </div>
                                <div class="card-body">
                                    @if(!empty($data['datos_grafico_notas']['datasets']) && count(array_filter($data['datos_grafico_notas']['datasets'][0]['data'] ?? [])) > 0)
                                        <div style="height: 350px;">
                                            <canvas id="chartNotas{{ $asignacionId }}"></canvas>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="bi bi-graph-up fs-1 text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No hay datos de notas disponibles</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Progreso de Conducta</h6>
                                </div>
                                <div class="card-body">
                                    @if(!empty($data['datos_grafico_conducta']['datasets']) && count(array_filter($data['datos_grafico_conducta']['datasets'][0]['data'] ?? [])) > 0)
                                        <div style="height: 350px;">
                                            <canvas id="chartConducta{{ $asignacionId }}"></canvas>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="bi bi-heart fs-1 text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No hay datos de conducta disponibles</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles Estadísticos -->
            <div class="detalles-section d-none" id="detalles-{{ $asignacionId }}">
                <div class="card-body">
                    @if($data['total_estudiantes'] > 0)
                        <!-- Resumen General (igual) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>Resumen General</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-2">
                                                    <small class="text-muted">Estudiantes con Notas</small>
                                                    <h4 class="mb-0 text-primary">{{ $data['estudiantes_con_notas'] }}/{{ $data['total_estudiantes'] }}</h4>
                                                    <small>{{ round(($data['estudiantes_con_notas'] / max($data['total_estudiantes'], 1)) * 100, 1) }}%</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-2">
                                                    <small class="text-muted">Estudiantes con Conducta</small>
                                                    <h4 class="mb-0 text-success">{{ $data['estudiantes_con_conducta'] }}/{{ $data['total_estudiantes'] }}</h4>
                                                    <small>{{ round(($data['estudiantes_con_conducta'] / max($data['total_estudiantes'], 1)) * 100, 1) }}%</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-2">
                                                    <small class="text-muted">Promedio General Notas</small>
                                                    <h4 class="mb-0 {{ ($data['promedio_general_notas'] ?? 0) >= 3 ? 'text-success' : (($data['promedio_general_notas'] ?? 0) >= 2 ? 'text-warning' : 'text-danger') }}">
                                                        {{ $data['promedio_general_notas'] ?? '--' }}
                                                    </h4>
                                                    <small>/ 4</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-2">
                                                    <small class="text-muted">Promedio General Conducta</small>
                                                    <h4 class="mb-0 {{ ($data['promedio_general_conducta'] ?? 0) >= 3 ? 'text-success' : (($data['promedio_general_conducta'] ?? 0) >= 2 ? 'text-warning' : 'text-danger') }}">
                                                        {{ $data['promedio_general_conducta'] ?? '--' }}
                                                    </h4>
                                                    <small>/ 4</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Notas por Bimestre -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-graph-up me-1 text-success"></i>Notas Académicas por Bimestre</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm mb-0">
                                                <thead class="bg-light">
                                                    <tr class="text-center">
                                                        <th>Bim.</th>
                                                        <th>Estudiantes</th>
                                                        <th>Promedio</th>
                                                        <th>Criterios</th>
                                                        <th>% Avance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data['estadisticas_bimestres']['notas'] as $bimestre => $stats)
                                                    <tr class="text-center">
                                                        <td class="fw-bold">{{ $bimestre }}</td>
                                                        <td>
                                                            {{ $stats['total_estudiantes_con_notas'] ?? 0 }}/{{ $data['total_estudiantes'] }}
                                                            <br>
                                                            <small class="text-muted">{{ $stats['porcentaje_avance'] ?? 0 }}%</small>
                                                        </td>
                                                        <td class="{{ ($stats['promedio'] ?? 0) >= 3 ? 'text-success' : ((($stats['promedio'] ?? 0) >= 2 ? 'text-warning' : 'text-danger')) }} fw-bold">
                                                            {{ $stats['promedio'] ?? '--' }}
                                                        </td>
                                                        <td>{{ $stats['criterios_en_bimestre'] ?? 0 }}</td>
                                                        <td>
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar {{ ($stats['porcentaje_avance'] ?? 0) >= 80 ? 'bg-success' : ((($stats['porcentaje_avance'] ?? 0) >= 50 ? 'bg-warning' : 'bg-danger')) }}"
                                                                    style="width: {{ $stats['porcentaje_avance'] ?? 0 }}%"></div>
                                                            </div>
                                                            <small>{{ $stats['porcentaje_avance'] ?? 0 }}%</small>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light">
                                                    @php
                                                        $totalRegistros = 0;
                                                        $totalPosibles = 0;
                                                        foreach($data['estadisticas_bimestres']['notas'] as $stats) {
                                                            $totalRegistros += $stats['total_notas_registradas'] ?? 0;
                                                            $totalPosibles += $stats['total_notas_posibles'] ?? 0;
                                                        }
                                                        $porcentajeTotal = $totalPosibles > 0 ? round(($totalRegistros / $totalPosibles) * 100, 1) : 0;
                                                    @endphp
                                                    <tr class="text-center fw-bold">
                                                        <td>Total</td>
                                                        <td colspan="2">{{ number_format($totalRegistros) }}/{{ number_format($totalPosibles) }}</td>
                                                        <td colspan="2">{{ $porcentajeTotal }}%</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de Conducta por Bimestre -->
                            <div class="col-md-6 mb-4">
                                <div class="card border h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-heart me-1 text-info"></i>Conducta por Bimestre</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm mb-0">
                                                <thead class="bg-light">
                                                    <tr class="text-center">
                                                        <th>Bim.</th>
                                                        <th>Estudiantes</th>
                                                        <th>Promedio</th>
                                                        <th>Conductas</th>
                                                        <th>% Avance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data['estadisticas_bimestres']['conducta'] as $bimestre => $stats)
                                                    <tr class="text-center">
                                                        <td class="fw-bold">{{ $bimestre }}</td>
                                                        <td>
                                                            {{ $stats['total_estudiantes_con_conducta'] ?? 0 }}/{{ $data['total_estudiantes'] }}
                                                            <br>
                                                            <small class="text-muted">{{ $stats['porcentaje_estudiantes'] ?? 0 }}%</small>
                                                        </td>
                                                        <td class="{{ ($stats['promedio'] ?? 0) >= 3 ? 'text-success' : ((($stats['promedio'] ?? 0) >= 2 ? 'text-warning' : 'text-danger')) }} fw-bold">
                                                            {{ $stats['promedio'] ?? '--' }}
                                                        </td>
                                                        <td>{{ $stats['total_conductas_en_bimestre'] ?? $stats['total_conductas_posibles'] ?? 0 }}</td>
                                                        <td>
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar {{ ($stats['porcentaje_avance'] ?? 0) >= 80 ? 'bg-success' : ((($stats['porcentaje_avance'] ?? 0) >= 50 ? 'bg-warning' : 'bg-danger')) }}"
                                                                    style="width: {{ $stats['porcentaje_avance'] ?? 0 }}%"></div>
                                                            </div>
                                                            <small>{{ $stats['porcentaje_avance'] ?? 0 }}%</small>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light">
                                                    @php
                                                        $totalRegistrosCond = 0;
                                                        $totalPosiblesCond = 0;
                                                        $totalConductas = 0;
                                                        foreach($data['estadisticas_bimestres']['conducta'] as $stats) {
                                                            $totalRegistrosCond += $stats['total_conductas_registradas'] ?? 0;
                                                            $totalPosiblesCond += $stats['total_conductas_posibles'] ?? 0;
                                                            $totalConductas += $stats['total_conductas_en_bimestre'] ?? $stats['total_conductas_posibles'] ?? 0;
                                                        }
                                                        $porcentajeTotalCond = $totalPosiblesCond > 0 ? round(($totalRegistrosCond / $totalPosiblesCond) * 100, 1) : 0;
                                                    @endphp
                                                    <tr class="text-center fw-bold">
                                                        <td>Total</td>
                                                        <td colspan="2">{{ number_format($totalRegistrosCond) }}/{{ number_format($totalPosiblesCond) }}</td>
                                                        <td>{{ $totalConductas }}</td>
                                                        <td>{{ $porcentajeTotalCond }}%</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-bar-chart fs-1 text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay datos estadísticos disponibles</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Lista de Estudiantes -->
            <div class="estudiantes-section d-none" id="estudiantes-{{ $asignacionId }}">
                <div class="card-body">
                    @if(count($data['estudiantes']) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover">
                                <thead class="table-dark">
                                    <tr class="text-center">
                                        <th>#</th>
                                        <th>DNI</th>
                                        <th>Nombre Completo</th>
                                        <th colspan="4" class="bg-light text-dark">Notas por Bimestre</th>
                                        <th>Prom.</th>
                                        <th>Completitud</th>
                                        <th colspan="4" class="bg-info bg-opacity-25 text-dark">Conducta por Bimestre</th>
                                        <th>Prom.</th>
                                        <th>Completitud</th>
                                        <th>Estado</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        @php
                                            $siglas = array_keys($data['estadisticas_bimestres']['notas'] ?? []);
                                        @endphp
                                        @foreach($siglas as $sigla)
                                            <th class="bg-light text-dark">{{ $sigla }}</th>
                                        @endforeach
                                        <th class="bg-light text-dark">Prom.</th>
                                        <th class="bg-light text-dark">(reg/pos)</th>
                                        @foreach($siglas as $sigla)
                                            <th class="bg-info bg-opacity-25 text-dark">{{ $sigla }}</th>
                                        @endforeach
                                        <th class="bg-info bg-opacity-25 text-dark">Prom.</th>
                                        <th class="bg-info bg-opacity-25 text-dark">(reg/pos)</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['estudiantes'] as $estudiante)
                                    @php
                                        $tieneNotasIncompletas = $estudiante['porcentaje_notas_completas'] < 100;
                                        $tieneConductaIncompleta = $estudiante['porcentaje_conducta_completa'] < 100;
                                    @endphp
                                    <tr class="text-center">
                                        <td>{{ $loop->iteration }}</td>
                                        <td><code>{{ $estudiante['dni'] }}</code></td>
                                        <td class="text-start @if($tieneNotasIncompletas || $tieneConductaIncompleta) incompleto-total @endif">
                                            {{ $estudiante['nombre_completo'] }}
                                            @if($tieneNotasIncompletas || $tieneConductaIncompleta)
                                                <i class="bi bi-exclamation-triangle text-warning ms-1"
                                                title="Faltan datos por completar"></i>
                                            @endif
                                        </td>

                                        {{-- Notas por bimestre --}}
                                        @foreach($estudiante['notas'] as $bimestre => $nota)
                                        <td class="@if($nota === null && $tieneNotasIncompletas) incompleto-notas @endif">
                                            @if($nota !== null)
                                                <span class="badge {{ $nota >= 3 ? 'bg-success' : ($nota >= 2 ? 'bg-warning' : 'bg-danger') }}">
                                                    {{ $nota }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary"
                                                    title="Falta registrar nota para {{ $bimestre }}">
                                                    <i class="bi bi-clock me-1"></i> Pendiente
                                                </span>
                                            @endif
                                        </td>
                                        @endforeach

                                        {{-- Promedio notas --}}
                                        <td>
                                            @if($estudiante['promedio_notas'] !== null)
                                                <strong class="{{ $estudiante['promedio_notas'] >= 3 ? 'text-success' : ($estudiante['promedio_notas'] >= 2 ? 'text-warning' : 'text-danger') }}">
                                                    {{ $estudiante['promedio_notas'] }}
                                                </strong>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>

                                        {{-- Completitud de notas --}}
                                        <td class="@if($tieneNotasIncompletas) incompleto-notas @endif">
                                            @php
                                                $completitudNotas = $estudiante['porcentaje_notas_completas'];
                                                $completitudColor = $completitudNotas >= 80 ? 'success' : ($completitudNotas >= 50 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $completitudColor }}"
                                                title="{{ $estudiante['notas_completas_texto'] }} criterios registrados de {{ $estudiante['total_criterios_posibles'] }} posibles">
                                                @if($tieneNotasIncompletas)
                                                    <i class="bi bi-exclamation-circle me-1"></i>
                                                @endif
                                                {{ $estudiante['notas_completas_texto'] }}
                                                ({{ $completitudNotas }}%)
                                            </span>
                                        </td>

                                        {{-- Conducta por bimestre --}}
                                        @foreach($estudiante['conducta'] as $bimestre => $conducta)
                                        <td class="@if($conducta === null && $tieneConductaIncompleta) incompleto-conducta @endif">
                                            @if($conducta !== null)
                                                <span class="badge {{ $conducta >= 3 ? 'bg-success' : ($conducta >= 2 ? 'bg-warning' : 'bg-danger') }}">
                                                    {{ $conducta }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary"
                                                    title="Falta registrar conducta para {{ $bimestre }}">
                                                    <i class="bi bi-clock me-1"></i> Pendiente
                                                </span>
                                            @endif
                                        </td>
                                        @endforeach

                                        {{-- Promedio conducta --}}
                                        <td>
                                            @if($estudiante['promedio_conducta'] !== null)
                                                <strong class="{{ $estudiante['promedio_conducta'] >= 3 ? 'text-success' : ($estudiante['promedio_conducta'] >= 2 ? 'text-warning' : 'text-danger') }}">
                                                    {{ $estudiante['promedio_conducta'] }}
                                                </strong>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>

                                        {{-- Completitud de conducta --}}
                                        <td class="@if($tieneConductaIncompleta) incompleto-conducta @endif">
                                            @php
                                                $completitudConducta = $estudiante['porcentaje_conducta_completa'];
                                                $completitudColorCond = $completitudConducta >= 80 ? 'success' : ($completitudConducta >= 50 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $completitudColorCond }}"
                                                title="{{ $estudiante['conducta_completa_texto'] }} conductas registradas de {{ $estudiante['total_conductas_posibles'] }} posibles">
                                                @if($tieneConductaIncompleta)
                                                    <i class="bi bi-exclamation-circle me-1"></i>
                                                @endif
                                                {{ $estudiante['conducta_completa_texto'] }}
                                                ({{ $completitudConducta }}%)
                                            </span>
                                        </td>

                                        {{-- Estado --}}
                                        <td>
                                            <span class="badge bg-{{ $estudiante['estado_clase'] }}">
                                                {{ $estudiante['estado_texto'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="fw-bold">
                                        <td colspan="3" class="text-end">Totales:</td>
                                        @php
                                            $totalCriteriosRegistrados = 0;
                                            $totalCriteriosPosibles = 0;
                                            $estudiantesConNotasIncompletas = 0;
                                            foreach($data['estudiantes'] as $est) {
                                                $totalCriteriosRegistrados += $est['total_criterios_registrados'];
                                                $totalCriteriosPosibles += $est['total_criterios_posibles'];
                                                if($est['porcentaje_notas_completas'] < 100) $estudiantesConNotasIncompletas++;
                                            }
                                            $porcentajeTotalNotas = $totalCriteriosPosibles > 0 ? round(($totalCriteriosRegistrados / $totalCriteriosPosibles) * 100, 1) : 0;
                                            $notasCompletasTodos = $porcentajeTotalNotas == 100;
                                        @endphp
                                        <td colspan="5" class="text-center">
                                            <span class="badge {{ $notasCompletasTodos ? 'bg-success' : 'bg-warning' }}">
                                                @if(!$notasCompletasTodos)
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                @endif
                                                Notas: {{ $totalCriteriosRegistrados }}/{{ $totalCriteriosPosibles }} ({{ $porcentajeTotalNotas }}%)
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $estudiantesConNotasIncompletas }} estudiante(s) con notas incompletas</small>
                                        </td>
                                        @php
                                            $totalConductasRegistradas = 0;
                                            $totalConductasPosibles = 0;
                                            $estudiantesConConductaIncompleta = 0;
                                            foreach($data['estudiantes'] as $est) {
                                                $totalConductasRegistradas += $est['total_conductas_registradas'];
                                                $totalConductasPosibles += $est['total_conductas_posibles'];
                                                if($est['porcentaje_conducta_completa'] < 100) $estudiantesConConductaIncompleta++;
                                            }
                                            $porcentajeTotalConducta = $totalConductasPosibles > 0 ? round(($totalConductasRegistradas / $totalConductasPosibles) * 100, 1) : 0;
                                            $conductaCompletaTodos = $porcentajeTotalConducta == 100;
                                        @endphp
                                        <td colspan="5" class="text-center">
                                            <span class="badge {{ $conductaCompletaTodos ? 'bg-success' : 'bg-warning' }}">
                                                @if(!$conductaCompletaTodos)
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                @endif
                                                Conducta: {{ $totalConductasRegistradas }}/{{ $totalConductasPosibles }} ({{ $porcentajeTotalConducta }}%)
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $estudiantesConConductaIncompleta }} estudiante(s) con conducta incompleta</small>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-people fs-1 text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay estudiantes registrados</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    @elseif($periodoSeleccionado && count($asignacionesData) == 0)
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-info-circle fs-1 mb-3"></i>
            <h5>No tiene asignaciones para el período seleccionado</h5>
            <p class="mb-0">No se encontraron materias asignadas a usted en este período académico.</p>
        </div>
    @endif
</div>

@if($periodoSeleccionado && count($asignacionesData) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const charts = {};
        const colores = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8AC926', '#1982C4', '#6A4C93', '#F15BB5'];

        @foreach($asignacionesData as $asignacionId => $data)
            @if(!empty($data['datos_grafico_notas']['datasets']) && count(array_filter($data['datos_grafico_notas']['datasets'][0]['data'] ?? [])) > 0)
            const ctxNotas{{ $asignacionId }} = document.getElementById('chartNotas{{ $asignacionId }}')?.getContext('2d');
            if (ctxNotas{{ $asignacionId }}) {
                charts['chartNotas{{ $asignacionId }}'] = new Chart(ctxNotas{{ $asignacionId }}, {
                    type: 'line',
                    data: {
                        labels: @json($data['datos_grafico_notas']['labels']),
                        datasets: @json($data['datos_grafico_notas']['datasets']).map((dataset, index) => ({
                            label: dataset.label,
                            data: dataset.data,
                            borderColor: colores[index % colores.length],
                            backgroundColor: colores[index % colores.length] + '20',
                            tension: 0,
                            fill: false,
                            pointBackgroundColor: colores[index % colores.length],
                            pointBorderColor: '#fff',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } }, tooltip: { mode: 'point', intersect: true } },
                        scales: { y: { min: 1, max: 4, title: { display: true, text: 'Notas' }, ticks: { stepSize: 0.5 } }, x: { title: { display: true, text: 'Bimestres' } } }
                    }
                });
            }
            @endif

            @if(!empty($data['datos_grafico_conducta']['datasets']) && count(array_filter($data['datos_grafico_conducta']['datasets'][0]['data'] ?? [])) > 0)
            const ctxConducta{{ $asignacionId }} = document.getElementById('chartConducta{{ $asignacionId }}')?.getContext('2d');
            if (ctxConducta{{ $asignacionId }}) {
                charts['chartConducta{{ $asignacionId }}'] = new Chart(ctxConducta{{ $asignacionId }}, {
                    type: 'line',
                    data: {
                        labels: @json($data['datos_grafico_conducta']['labels']),
                        datasets: @json($data['datos_grafico_conducta']['datasets']).map((dataset, index) => ({
                            label: dataset.label,
                            data: dataset.data,
                            borderColor: colores[index % colores.length],
                            backgroundColor: colores[index % colores.length] + '20',
                            tension: 0,
                            fill: false,
                            pointBackgroundColor: colores[index % colores.length],
                            pointBorderColor: '#fff',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } }, tooltip: { mode: 'point', intersect: true } },
                        scales: { y: { min: 1, max: 4, title: { display: true, text: 'Notas' }, ticks: { stepSize: 0.5 } }, x: { title: { display: true, text: 'Bimestres' } } }
                    }
                });
            }
            @endif
        @endforeach

        document.querySelectorAll('.toggle-section').forEach(button => {
            button.addEventListener('click', function() {
                const target = this.dataset.target;
                const card = this.closest('.card');
                card.querySelectorAll('.graficos-section, .detalles-section, .estudiantes-section').forEach(section => {
                    section.classList.add('d-none');
                });
                document.querySelector(target).classList.remove('d-none');
                setTimeout(() => {
                    Object.values(charts).forEach(chart => chart.resize());
                }, 100);
            });
        });
    });
</script>
@endif
<style>
    @keyframes blink {
        0% { opacity: 1; background-color: #ffebee; }
        50% { opacity: 0.6; background-color: #ffcdd2; }
        100% { opacity: 1; background-color: #ffebee; }
    }

    @keyframes blink-border {
        0% { border-left-color: transparent; }
        50% { border-left-color: #f44336; }
        100% { border-left-color: transparent; }
    }

    .incompleto-notas {
        animation: blink 1.5s ease-in-out infinite;
    }

    .incompleto-conducta {
        animation: blink-border 1.5s ease-in-out infinite;
        border-left: 3px solid transparent;
        border-right: 3px solid transparent;
    }

    .incompleto-total {
        animation: blink 2s ease-in-out infinite;
        font-weight: bold;
    }

    .tooltip-inner {
        max-width: 300px;
        text-align: left;
    }
</style>
@endsection
