@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
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
                        <i class="fas fa-chalkboard-teacher me-1"></i> Docente
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
                        <i class="fas fa-calendar-alt me-2"></i>Seleccionar Periodo Académico
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
                                <i class="fas fa-filter me-1"></i> Filtrar
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
                        <i class="fas fa-info-circle me-2"></i>Resumen del Periodo
                    </h5>
                </div>
                <div class="card-body">
                    @if($periodoSeleccionado)
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
                        <p class="text-muted mb-0">No hay periodos activos disponibles.</p>
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
                            <i class="fas fa-book-open me-2"></i>
                            {{ $data['materia_nombre'] }}
                            <small class="text-white-50">- {{ $data['grado_nombre'] }}</small>
                        </h4>
                        <p class="mb-0 mt-1">
                            <small>
                                <i class="fas fa-calendar me-1"></i> {{ $data['periodo_anio'] }}
                                | <i class="fas fa-users me-1"></i> {{ $data['total_estudiantes'] }} estudiantes
                            </small>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light toggle-section" data-target="#graficos-{{ $asignacionId }}">
                            <i class="fas fa-chart-line"></i> Gráficos
                        </button>
                        <button class="btn btn-sm btn-light toggle-section" data-target="#detalles-{{ $asignacionId }}">
                            <i class="fas fa-chart-bar"></i> Estadísticas
                        </button>
                        <button class="btn btn-sm btn-light toggle-section" data-target="#estudiantes-{{ $asignacionId }}">
                            <i class="fas fa-list"></i> Estudiantes
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
                                    <div style="height: 350px;">
                                        <canvas id="chartNotas{{ $asignacionId }}"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Progreso de Conducta</h6>
                                </div>
                                <div class="card-body">
                                    <div style="height: 350px;">
                                        <canvas id="chartConducta{{ $asignacionId }}"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles Estadísticos -->
            <div class="detalles-section d-none" id="detalles-{{ $asignacionId }}">
                <div class="card-body">
                    <!-- Resumen General -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i>Resumen General</h6>
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
                                    <h6 class="mb-0"><i class="fas fa-chart-line me-1 text-success"></i>Notas Académicas por Bimestre</h6>
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
                                    <h6 class="mb-0"><i class="fas fa-heart me-1 text-info"></i>Conducta por Bimestre</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead class="bg-light">
                                                <tr class="text-center">
                                                    <th>Bim.</th>
                                                    <th>Estudiantes</th>
                                                    <th>Promedio</th>
                                                    <th>Rango (Min-Max)</th>
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
                                                    <td>
                                                        @if(($stats['min'] ?? null) !== null && ($stats['max'] ?? null) !== null)
                                                            <span class="text-success">{{ $stats['min'] }}</span>
                                                            <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                            <span class="text-danger">{{ $stats['max'] }}</span>
                                                        @else
                                                            --
                                                        @endif
                                                    </td>
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
                                                    foreach($data['estadisticas_bimestres']['conducta'] as $stats) {
                                                        $totalRegistrosCond += $stats['total_conductas_registradas'] ?? 0;
                                                        $totalPosiblesCond += $stats['total_conductas_posibles'] ?? 0;
                                                    }
                                                    $porcentajeTotalCond = $totalPosiblesCond > 0 ? round(($totalRegistrosCond / $totalPosiblesCond) * 100, 1) : 0;
                                                @endphp
                                                <tr class="text-center fw-bold">
                                                    <td>Total</td>
                                                    <td colspan="2">{{ number_format($totalRegistrosCond) }}/{{ number_format($totalPosiblesCond) }}</td>
                                                    <td colspan="2">{{ $porcentajeTotalCond }}%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Estudiantes -->
            <div class="estudiantes-section d-none" id="estudiantes-{{ $asignacionId }}">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover">
                            <thead class="table-dark">
                                <tr class="text-center">
                                    <th>#</th>
                                    <th>DNI</th>
                                    <th>Nombre Completo</th>
                                    <th colspan="4" class="bg-light text-dark">Notas por Bimestre</th>
                                    <th>Prom.</th>
                                    <th colspan="4" class="bg-info bg-opacity-25 text-dark">Conducta por Bimestre</th>
                                    <th>Prom.</th>
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
                                    @foreach($siglas as $sigla)
                                        <th class="bg-info bg-opacity-25 text-dark">{{ $sigla }}</th>
                                    @endforeach
                                    <th class="bg-info bg-opacity-25 text-dark">Prom.</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['estudiantes'] as $estudiante)
                                <tr class="text-center">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><code>{{ $estudiante['dni'] }}</code></td>
                                    <td class="text-start">{{ $estudiante['nombre_completo'] }}</td>
                                    @foreach($estudiante['notas'] as $nota)
                                    <td>
                                        @if($nota !== null)
                                            <span class="badge {{ $nota >= 3 ? 'bg-success' : ($nota >= 2 ? 'bg-warning' : 'bg-danger') }}">
                                                {{ $nota }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">--</span>
                                        @endif
                                    </td>
                                    @endforeach
                                    <td>
                                        @if($estudiante['promedio_notas'] !== null)
                                            <strong class="{{ $estudiante['promedio_notas'] >= 3 ? 'text-success' : ($estudiante['promedio_notas'] >= 2 ? 'text-warning' : 'text-danger') }}">
                                                {{ $estudiante['promedio_notas'] }}
                                            </strong>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    @foreach($estudiante['conducta'] as $conducta)
                                    <td>
                                        @if($conducta !== null)
                                            <span class="badge {{ $conducta >= 3 ? 'bg-success' : ($conducta >= 2 ? 'bg-warning' : 'bg-danger') }}">
                                                {{ $conducta }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">--</span>
                                        @endif
                                    </td>
                                    @endforeach
                                    <td>
                                        @if($estudiante['promedio_conducta'] !== null)
                                            <strong class="{{ $estudiante['promedio_conducta'] >= 3 ? 'text-success' : ($estudiante['promedio_conducta'] >= 2 ? 'text-warning' : 'text-danger') }}">
                                                {{ $estudiante['promedio_conducta'] }}
                                            </strong>
                                        @else
                                            --
                                        @endif
                                    </td>
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
                                        $totalEstudiantesConNotas = 0;
                                        foreach($data['estadisticas_bimestres']['notas'] as $stats) {
                                            $totalEstudiantesConNotas += $stats['total_estudiantes_con_notas'] ?? 0;
                                        }
                                    @endphp
                                    <td colspan="4" class="text-center">
                                        {{ $data['estudiantes_con_notas'] }}/{{ $data['total_estudiantes'] }} estudiantes
                                    </td>
                                    <td class="text-center">{{ $data['promedio_general_notas'] ?? '--' }}</td>
                                    <td colspan="4" class="text-center">
                                        {{ $data['estudiantes_con_conducta'] }}/{{ $data['total_estudiantes'] }} estudiantes
                                    </td>
                                    <td class="text-center">{{ $data['promedio_general_conducta'] ?? '--' }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    @elseif($periodoSeleccionado && count($asignacionesData) == 0)
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No tiene asignaciones para el período seleccionado.
        </div>
    @endif
</div>

@if($periodoSeleccionado && count($asignacionesData) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const charts = {};
        const colores = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8AC926', '#1982C4', '#6A4C93', '#F15BB5'];

        // Inicializar gráficos
        @foreach($asignacionesData as $asignacionId => $data)
            // Gráfico de Notas
            @if(!empty($data['datos_grafico_notas']['datasets']))
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
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } },
                            tooltip: { mode: 'point', intersect: true }
                        },
                        scales: {
                            y: { min: 1, max: 4, title: { display: true, text: 'Notas' }, ticks: { stepSize: 0.5 } },
                            x: { title: { display: true, text: 'Bimestres' } }
                        }
                    }
                });
            }
            @endif

            // Gráfico de Conducta
            @if(!empty($data['datos_grafico_conducta']['datasets']))
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
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } },
                            tooltip: { mode: 'point', intersect: true }
                        },
                        scales: {
                            y: { min: 1, max: 4, title: { display: true, text: 'Notas' }, ticks: { stepSize: 0.5 } },
                            x: { title: { display: true, text: 'Bimestres' } }
                        }
                    }
                });
            }
            @endif
        @endforeach

        // Toggle sections
        document.querySelectorAll('.toggle-section').forEach(button => {
            button.addEventListener('click', function() {
                const target = this.dataset.target;
                const card = this.closest('.card');

                card.querySelectorAll('.graficos-section, .detalles-section, .estudiantes-section').forEach(section => {
                    section.classList.add('d-none');
                });

                document.querySelector(target).classList.remove('d-none');

                // Redimensionar gráficos
                setTimeout(() => {
                    Object.values(charts).forEach(chart => chart.resize());
                }, 100);
            });
        });
    });
</script>
@endif
@endsection
