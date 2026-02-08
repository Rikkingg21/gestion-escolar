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
                                        {{ $periodo->anio }} - {{ $periodo->semestre ?? 'Periodo' }}
                                        @if($periodo->estado == 1)
                                            <span class="text-success">(Activo)</span>
                                        @endif
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
                                    <h3 class="text-primary mb-0">{{ $asignaciones->count() }}</h3>
                                    <small class="text-muted">Materias</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h3 class="text-success mb-0">{{ $grados->count() }}</h3>
                                    <small class="text-muted">Grados</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    @php
                                        $totalEstudiantes = 0;
                                        foreach($estudiantesPorGrado as $estudiantes) {
                                            $totalEstudiantes += $estudiantes->count();
                                        }
                                    @endphp
                                    <h3 class="text-warning mb-0">{{ $totalEstudiantes }}</h3>
                                    <small class="text-muted">Estudiantes</small>
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

    @if($periodoSeleccionado)
        <!-- Contenedor principal agrupado por asignación -->
        <div id="asignacionesContainer">
            @foreach($asignaciones as $asignacion)
                @php
                    $progreso = $progresoEstudiantes[$asignacion->id] ?? null;
                    $progresoCond = $progresoConducta[$asignacion->id] ?? null;
                    $estudiantesGrado = $estudiantesPorGrado->get($asignacion->grado_id, collect());

                    $estudiantesConNotas = $progreso ? count($progreso['progreso'] ?? []) : 0;
                    $estudiantesConConducta = $progresoCond ? count($progresoCond['progreso'] ?? []) : 0;
                    $totalEstudiantes = $estudiantesGrado->count();

                    // Obtener datos para gráficos
                    $datosGraficoNotas = $datosGraficos['estudiantes_lineas'][$asignacion->id] ?? null;
                    $datosGraficoConducta = $datosGraficosConducta['conducta_lineas'][$asignacion->id] ?? null;
                @endphp

                <div class="asignacion-group mb-5" id="asignacion-{{ $asignacion->id }}">
                    <!-- Cabecera de la asignación -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light border-0 shadow-sm">
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="mb-0 text-primary">
                                                <i class="fas fa-book-open me-2"></i>
                                                {{ $asignacion->materia->nombre }}
                                                <small class="text-muted">- {{ $asignacion->grado->nombreCompleto }}</small>
                                            </h4>
                                            <p class="mb-0 text-muted">
                                                <small>
                                                    <i class="fas fa-calendar me-1"></i> {{ $periodoSeleccionado->anio }}
                                                    | <i class="fas fa-users me-1"></i> {{ $totalEstudiantes }} estudiantes
                                                </small>
                                            </p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary toggle-section"
                                                    data-target="#graficos-{{ $asignacion->id }}">
                                                <i class="fas fa-chart-line"></i> Gráficos
                                            </button>
                                            <button class="btn btn-sm btn-outline-success toggle-section"
                                                    data-target="#detalles-{{ $asignacion->id }}">
                                                <i class="fas fa-chart-bar"></i> Estadísticas
                                            </button>
                                            <button class="btn btn-sm btn-outline-info toggle-section"
                                                    data-target="#estudiantes-{{ $asignacion->id }}">
                                                <i class="fas fa-list"></i> Estudiantes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos (Notas y Conducta) -->
                    <div class="row mb-4 graficos-section" id="graficos-{{ $asignacion->id }}">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Gráficos de Progreso
                                    </h5>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary toggle-grafico active"
                                                data-grafico="notas-{{ $asignacion->id }}">
                                            Notas Académicas
                                        </button>
                                        <button type="button" class="btn btn-outline-success toggle-grafico"
                                                data-grafico="conducta-{{ $asignacion->id }}">
                                            Conducta
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Gráfico de Notas Académicas -->
                                    <div id="grafico-notas-{{ $asignacion->id }}" class="grafico-container">
                                        @if($datosGraficoNotas)
                                            <div class="chart-container" style="height: 400px;">
                                                <canvas id="chartNotas{{ $asignacion->id }}"></canvas>
                                            </div>
                                            <div class="mt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">Leyenda de Estudiantes</h6>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input toggle-estudiantes"
                                                               type="checkbox"
                                                               id="toggleEstudiantes{{ $asignacion->id }}"
                                                               checked>
                                                        <label class="form-check-label" for="toggleEstudiantes{{ $asignacion->id }}">
                                                            Mostrar todos
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2"
                                                     id="leyenda-notas-{{ $asignacion->id }}">
                                                    @foreach($datosGraficoNotas['datasets'] as $index => $dataset)
                                                        <div class="col">
                                                            <div class="d-flex align-items-center estudiante-leyenda"
                                                                 data-estudiante-id="{{ $dataset['estudiante_id'] }}"
                                                                 data-dataset-index="{{ $index }}">
                                                                <div class="form-check">
                                                                    <input class="form-check-input toggle-estudiante"
                                                                           type="checkbox"
                                                                           id="estudiante{{ $asignacion->id }}_{{ $index }}"
                                                                           checked
                                                                           data-chart-id="chartNotas{{ $asignacion->id }}"
                                                                           data-dataset-index="{{ $index }}">
                                                                    <label class="form-check-label d-flex align-items-center"
                                                                           for="estudiante{{ $asignacion->id }}_{{ $index }}">
                                                                        <div class="color-box me-2"
                                                                             style="width: 12px; height: 12px; background-color: {{ $dataset['borderColor'] }}; border-radius: 2px;"></div>
                                                                        <small class="text-truncate" title="{{ $dataset['label'] }}">
                                                                            {{ $dataset['label'] }}
                                                                            @if($dataset['dni'])
                                                                                <br><span class="text-muted">DNI: {{ $dataset['dni'] }}</span>
                                                                            @endif
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-center py-5">
                                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No hay suficientes datos para generar el gráfico de notas.</p>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Gráfico de Conducta -->
                                    <div id="grafico-conducta-{{ $asignacion->id }}" class="grafico-container d-none">
                                        @if($datosGraficoConducta && !empty($datosGraficoConducta['datasets']))
                                            <div class="chart-container" style="height: 400px;">
                                                <canvas id="chartConducta{{ $asignacion->id }}"></canvas>
                                            </div>
                                            <div class="mt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">Leyenda de Estudiantes</h6>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input toggle-estudiantes"
                                                               type="checkbox"
                                                               id="toggleConductaEstudiantes{{ $asignacion->id }}"
                                                               checked>
                                                        <label class="form-check-label" for="toggleConductaEstudiantes{{ $asignacion->id }}">
                                                            Mostrar todos
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2"
                                                     id="leyenda-conducta-{{ $asignacion->id }}">
                                                    @foreach($datosGraficoConducta['datasets'] as $index => $dataset)
                                                        <div class="col">
                                                            <div class="d-flex align-items-center estudiante-leyenda"
                                                                 data-estudiante-id="{{ $dataset['estudiante_id'] }}"
                                                                 data-dataset-index="{{ $index }}">
                                                                <div class="form-check">
                                                                    <input class="form-check-input toggle-estudiante"
                                                                           type="checkbox"
                                                                           id="conductaEstudiante{{ $asignacion->id }}_{{ $index }}"
                                                                           checked
                                                                           data-chart-id="chartConducta{{ $asignacion->id }}"
                                                                           data-dataset-index="{{ $index }}">
                                                                    <label class="form-check-label d-flex align-items-center"
                                                                           for="conductaEstudiante{{ $asignacion->id }}_{{ $index }}">
                                                                        <div class="color-box me-2"
                                                                             style="width: 12px; height: 12px; background-color: {{ $dataset['borderColor'] }}; border-radius: 2px;"></div>
                                                                        <small class="text-truncate" title="{{ $dataset['label'] }}">
                                                                            {{ $dataset['label'] }}
                                                                            @if($dataset['dni'])
                                                                                <br><span class="text-muted">DNI: {{ $dataset['dni'] }}</span>
                                                                            @endif
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-center py-5">
                                                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No hay suficientes datos para generar el gráfico de conducta.</p>
                                                <!-- DEBUG: Agregar información -->
                                                <small class="text-muted">
                                                    Datos disponibles: {{ isset($datosGraficoConducta) ? 'Sí' : 'No' }}
                                                    @if(isset($datosGraficoConducta))
                                                        | Estudiantes: {{ count($datosGraficoConducta['datasets'] ?? []) }}
                                                    @endif
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles Estadísticos -->
                    <div class="row mb-4 detalles-section d-none" id="detalles-{{ $asignacion->id }}">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Estadísticas Detalladas - {{ $asignacion->materia->nombre }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @php
                                        // Calcular estadísticas por bimestre para notas
                                        $estadisticasBimestres = [];
                                        $resumenNotas = ['total_estudiantes' => 0, 'suma_promedios' => 0, 'con_datos' => 0];
                                        $resumenConducta = ['total_estudiantes' => 0, 'suma_promedios' => 0, 'con_datos' => 0];

                                        for ($bimestre = 1; $bimestre <= 4; $bimestre++) {
                                            $notasBimestre = [];
                                            $conductasBimestre = [];

                                            if ($progreso && isset($progreso['progreso'])) {
                                                foreach ($progreso['progreso'] as $estudianteData) {
                                                    if (isset($estudianteData['datos'][$bimestre]) && $estudianteData['datos'][$bimestre] !== null) {
                                                        $notasBimestre[] = $estudianteData['datos'][$bimestre];
                                                    }
                                                }
                                            }

                                            if ($progresoCond && isset($progresoCond['progreso'])) {
                                                foreach ($progresoCond['progreso'] as $estudianteData) {
                                                    if (isset($estudianteData['datos'][$bimestre]) && $estudianteData['datos'][$bimestre] !== null) {
                                                        $conductasBimestre[] = $estudianteData['datos'][$bimestre];
                                                    }
                                                }
                                            }

                                            // Notas
                                            $estadisticasBimestres['notas'][$bimestre] = [
                                                'total' => count($notasBimestre),
                                                'promedio' => count($notasBimestre) > 0 ? round(array_sum($notasBimestre) / count($notasBimestre), 2) : null,
                                                'min' => count($notasBimestre) > 0 ? min($notasBimestre) : null,
                                                'max' => count($notasBimestre) > 0 ? max($notasBimestre) : null
                                            ];

                                            // Conducta
                                            $estadisticasBimestres['conducta'][$bimestre] = [
                                                'total' => count($conductasBimestre),
                                                'promedio' => count($conductasBimestre) > 0 ? round(array_sum($conductasBimestre) / count($conductasBimestre), 2) : null,
                                                'min' => count($conductasBimestre) > 0 ? min($conductasBimestre) : null,
                                                'max' => count($conductasBimestre) > 0 ? max($conductasBimestre) : null
                                            ];

                                            // Resumen para promedios generales
                                            if ($estadisticasBimestres['notas'][$bimestre]['promedio'] !== null) {
                                                $resumenNotas['suma_promedios'] += $estadisticasBimestres['notas'][$bimestre]['promedio'];
                                                $resumenNotas['con_datos']++;
                                            }

                                            if ($estadisticasBimestres['conducta'][$bimestre]['promedio'] !== null) {
                                                $resumenConducta['suma_promedios'] += $estadisticasBimestres['conducta'][$bimestre]['promedio'];
                                                $resumenConducta['con_datos']++;
                                            }
                                        }

                                        $resumenNotas['total_estudiantes'] = $totalEstudiantes;
                                        $resumenConducta['total_estudiantes'] = $totalEstudiantes;

                                        $promedioGeneralNotas = $resumenNotas['con_datos'] > 0 ?
                                            round($resumenNotas['suma_promedios'] / $resumenNotas['con_datos'], 2) : null;
                                        $promedioGeneralConducta = $resumenConducta['con_datos'] > 0 ?
                                            round($resumenConducta['suma_promedios'] / $resumenConducta['con_datos'], 2) : null;
                                    @endphp

                                    <!-- Tabla de Resumen General -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card border">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i>Resumen General</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm mb-0">
                                                            <thead class="bg-light">
                                                                <tr>
                                                                    <th style="width: 25%">Concepto</th>
                                                                    <th style="width: 25%">Notas Académicas</th>
                                                                    <th style="width: 25%">Conducta</th>
                                                                    <th style="width: 25%">Total/General</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="fw-semibold">Estudiantes</td>
                                                                    <td>
                                                                        <span class="badge bg-light text-dark">
                                                                            {{ $estudiantesConNotas }}/{{ $totalEstudiantes }}
                                                                        </span>
                                                                        <small class="text-muted ms-2">
                                                                            @if($totalEstudiantes > 0)
                                                                                {{ round(($estudiantesConNotas / $totalEstudiantes) * 100, 1) }}%
                                                                            @else
                                                                                0%
                                                                            @endif
                                                                        </small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-light text-dark">
                                                                            {{ $estudiantesConConducta }}/{{ $totalEstudiantes }}
                                                                        </span>
                                                                        <small class="text-muted ms-2">
                                                                            @if($totalEstudiantes > 0)
                                                                                {{ round(($estudiantesConConducta / $totalEstudiantes) * 100, 1) }}%
                                                                            @else
                                                                                0%
                                                                            @endif
                                                                        </small>
                                                                    </td>
                                                                    <td class="fw-bold">
                                                                        {{ $totalEstudiantes }} estudiantes
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-semibold">Promedio General</td>
                                                                    <td>
                                                                        @if($promedioGeneralNotas !== null)
                                                                            @php
                                                                                $colorNota = $promedioGeneralNotas >= 3 ? 'text-success' :
                                                                                            ($promedioGeneralNotas >= 2 ? 'text-warning' : 'text-danger');
                                                                            @endphp
                                                                            <span class="fw-bold {{ $colorNota }}">
                                                                                {{ $promedioGeneralNotas }} / 4
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($promedioGeneralConducta !== null)
                                                                            @php
                                                                                $colorConducta = $promedioGeneralConducta >= 3 ? 'text-success' :
                                                                                            ($promedioGeneralConducta >= 2 ? 'text-warning' : 'text-danger');
                                                                            @endphp
                                                                            <span class="fw-bold {{ $colorConducta }}">
                                                                                {{ $promedioGeneralConducta }} / 4
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $totalRegistros = $estudiantesConNotas + $estudiantesConConducta;
                                                                            $maxRegistros = $totalEstudiantes * 2;
                                                                            $porcentajeCompletitud = $maxRegistros > 0 ?
                                                                                round(($totalRegistros / $maxRegistros) * 100, 1) : 0;
                                                                            $completitudClass = $porcentajeCompletitud >= 80 ? 'text-success' :
                                                                                ($porcentajeCompletitud >= 50 ? 'text-warning' : 'text-danger');
                                                                        @endphp
                                                                        <span class="fw-bold {{ $completitudClass }}">
                                                                            {{ $porcentajeCompletitud }}% completitud
                                                                        </span>
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            {{ $totalRegistros }}/{{ $maxRegistros }} registros
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-semibold">Progreso</td>
                                                                    <td colspan="3">
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="flex-grow-1 me-3">
                                                                                <div class="progress" style="height: 12px;">
                                                                                    @if($totalEstudiantes > 0)
                                                                                        <div class="progress-bar bg-success"
                                                                                            style="width: {{ ($estudiantesConNotas / $totalEstudiantes) * 100 }}%"
                                                                                            title="Notas: {{ $estudiantesConNotas }}/{{ $totalEstudiantes }}">
                                                                                            <small>Notas</small>
                                                                                        </div>
                                                                                        <div class="progress-bar bg-info"
                                                                                            style="width: {{ ($estudiantesConConducta / $totalEstudiantes) * 100 }}%"
                                                                                            title="Conducta: {{ $estudiantesConConducta }}/{{ $totalEstudiantes }}">
                                                                                            <small>Conducta</small>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                            <div class="flex-shrink-0">
                                                                                <small class="text-muted">
                                                                                    @if($totalEstudiantes > 0)
                                                                                        {{ $estudiantesConNotas + $estudiantesConConducta }}/{{ $totalEstudiantes * 2 }} registros
                                                                                    @else
                                                                                        0/0 registros
                                                                                    @endif
                                                                                </small>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tablas de Estadísticas por Bimestre -->
                                    <div class="row">
                                        <!-- Tabla de Notas por Bimestre -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="card border h-100">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-chart-line me-1 text-success"></i>
                                                        Notas Académicas por Bimestre
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm mb-0">
                                                            <thead class="bg-light">
                                                                <tr>
                                                                    <th style="width: 15%">Bimestre</th>
                                                                    <th style="width: 20%">Estudiantes</th>
                                                                    <th style="width: 20%">Promedio</th>
                                                                    <th style="width: 25%">Rango (Min-Max)</th>
                                                                    <th style="width: 20%">Estado</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @for($bimestre = 1; $bimestre <= 4; $bimestre++)
                                                                    @php
                                                                        $stats = $estadisticasBimestres['notas'][$bimestre] ?? null;
                                                                        $promedio = $stats['promedio'] ?? null;
                                                                        $porcentaje = $totalEstudiantes > 0 ?
                                                                            round(($stats['total'] ?? 0) / $totalEstudiantes * 100, 1) : 0;
                                                                        $colorClass = $promedio >= 3 ? 'text-success' :
                                                                                    ($promedio >= 2 ? 'text-warning' : 'text-danger');
                                                                        $badgeClass = $promedio >= 3 ? 'bg-success' :
                                                                                    ($promedio >= 2 ? 'bg-warning' : 'bg-danger');
                                                                        $estadoClass = ($stats['total'] ?? 0) == $totalEstudiantes ? 'bg-success' :
                                                                                    (($stats['total'] ?? 0) > 0 ? 'bg-warning' : 'bg-danger');
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="fw-bold text-center">
                                                                            B{{ $bimestre }}
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <span class="badge bg-light text-dark">
                                                                                    {{ $stats['total'] ?? 0 }}/{{ $totalEstudiantes }}
                                                                                </span>
                                                                                <small class="text-muted">
                                                                                    {{ $porcentaje }}%
                                                                                </small>
                                                                            </div>
                                                                        </td>
                                                                        <td class="{{ $colorClass }} fw-bold text-center">
                                                                            {{ $promedio ?? '--' }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if($stats && $stats['min'] !== null && $stats['max'] !== null)
                                                                                <span class="text-success">{{ $stats['min'] }}</span>
                                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                                <span class="text-danger">{{ $stats['max'] }}</span>
                                                                            @else
                                                                                <span class="text-muted">--</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if($stats && $stats['total'] > 0)
                                                                                @if($stats['total'] == $totalEstudiantes)
                                                                                    <span class="badge bg-success">Completo</span>
                                                                                @else
                                                                                    <span class="badge bg-warning">Parcial</span>
                                                                                @endif
                                                                            @else
                                                                                <span class="badge bg-danger">Sin datos</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endfor
                                                            </tbody>
                                                            <tfoot class="bg-light">
                                                                <tr>
                                                                    <td class="fw-bold">Total</td>
                                                                    <td>
                                                                        <span class="badge bg-light text-dark">
                                                                            {{ $estudiantesConNotas }}/{{ $totalEstudiantes }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="fw-bold text-center">
                                                                        {{ $promedioGeneralNotas ?? '--' }}
                                                                    </td>
                                                                    <td colspan="2" class="text-center">
                                                                        <small class="text-muted">
                                                                            Promedio de {{ $resumenNotas['con_datos'] }}/4 bimestres
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tabla de Conducta por Bimestre -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="card border h-100">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-heart me-1 text-info"></i>
                                                        Conducta por Bimestre
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm mb-0">
                                                            <thead class="bg-light">
                                                                <tr>
                                                                    <th style="width: 15%">Bimestre</th>
                                                                    <th style="width: 20%">Estudiantes</th>
                                                                    <th style="width: 20%">Promedio</th>
                                                                    <th style="width: 25%">Rango (Min-Max)</th>
                                                                    <th style="width: 20%">Estado</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @for($bimestre = 1; $bimestre <= 4; $bimestre++)
                                                                    @php
                                                                        $stats = $estadisticasBimestres['conducta'][$bimestre] ?? null;
                                                                        $promedio = $stats['promedio'] ?? null;
                                                                        $porcentaje = $totalEstudiantes > 0 ?
                                                                            round(($stats['total'] ?? 0) / $totalEstudiantes * 100, 1) : 0;
                                                                        $colorClass = $promedio >= 3 ? 'text-success' :
                                                                                    ($promedio >= 2 ? 'text-warning' : 'text-danger');
                                                                        $badgeClass = $promedio >= 3 ? 'bg-success' :
                                                                                    ($promedio >= 2 ? 'bg-warning' : 'bg-danger');
                                                                        $estadoClass = ($stats['total'] ?? 0) == $totalEstudiantes ? 'bg-success' :
                                                                                    (($stats['total'] ?? 0) > 0 ? 'bg-warning' : 'bg-danger');
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="fw-bold text-center">
                                                                            B{{ $bimestre }}
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <span class="badge bg-light text-dark">
                                                                                    {{ $stats['total'] ?? 0 }}/{{ $totalEstudiantes }}
                                                                                </span>
                                                                                <small class="text-muted">
                                                                                    {{ $porcentaje }}%
                                                                                </small>
                                                                            </div>
                                                                        </td>
                                                                        <td class="{{ $colorClass }} fw-bold text-center">
                                                                            {{ $promedio ?? '--' }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if($stats && $stats['min'] !== null && $stats['max'] !== null)
                                                                                <span class="text-success">{{ $stats['min'] }}</span>
                                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                                <span class="text-danger">{{ $stats['max'] }}</span>
                                                                            @else
                                                                                <span class="text-muted">--</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if($stats && $stats['total'] > 0)
                                                                                @if($stats['total'] == $totalEstudiantes)
                                                                                    <span class="badge bg-success">Completo</span>
                                                                                @else
                                                                                    <span class="badge bg-warning">Parcial</span>
                                                                                @endif
                                                                            @else
                                                                                <span class="badge bg-danger">Sin datos</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endfor
                                                            </tbody>
                                                            <tfoot class="bg-light">
                                                                <tr>
                                                                    <td class="fw-bold">Total</td>
                                                                    <td>
                                                                        <span class="badge bg-light text-dark">
                                                                            {{ $estudiantesConConducta }}/{{ $totalEstudiantes }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="fw-bold text-center">
                                                                        {{ $promedioGeneralConducta ?? '--' }}
                                                                    </td>
                                                                    <td colspan="2" class="text-center">
                                                                        <small class="text-muted">
                                                                            Promedio de {{ $resumenConducta['con_datos'] }}/4 bimestres
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Resumen Comparativo -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card border">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0"><i class="fas fa-balance-scale me-1"></i>Comparación Notas vs Conducta</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm mb-0">
                                                            <thead class="bg-light">
                                                                <tr>
                                                                    <th style="width: 25%">Indicador</th>
                                                                    <th style="width: 25%">Notas Académicas</th>
                                                                    <th style="width: 25%">Conducta</th>
                                                                    <th style="width: 25%">Diferencia</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="fw-semibold">Completitud</td>
                                                                    <td>
                                                                        @if($totalEstudiantes > 0)
                                                                            {{ round(($estudiantesConNotas / $totalEstudiantes) * 100, 1) }}%
                                                                        @else
                                                                            0%
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($totalEstudiantes > 0)
                                                                            {{ round(($estudiantesConConducta / $totalEstudiantes) * 100, 1) }}%
                                                                        @else
                                                                            0%
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        @if($totalEstudiantes > 0)
                                                                            @php
                                                                                $diferencia = round((($estudiantesConNotas - $estudiantesConConducta) / $totalEstudiantes) * 100, 1);
                                                                                $diffClass = $diferencia > 0 ? 'text-success' :
                                                                                            ($diferencia < 0 ? 'text-danger' : 'text-muted');
                                                                            @endphp
                                                                            <span class="{{ $diffClass }}">
                                                                                {{ $diferencia > 0 ? '+' : '' }}{{ $diferencia }}%
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-semibold">Promedio General</td>
                                                                    <td>{{ $promedioGeneralNotas ?? '--' }}</td>
                                                                    <td>{{ $promedioGeneralConducta ?? '--' }}</td>
                                                                    <td class="text-center">
                                                                        @if($promedioGeneralNotas !== null && $promedioGeneralConducta !== null)
                                                                            @php
                                                                                $diferencia = round($promedioGeneralNotas - $promedioGeneralConducta, 2);
                                                                                $diffClass = $diferencia > 0 ? 'text-success' :
                                                                                            ($diferencia < 0 ? 'text-danger' : 'text-muted');
                                                                            @endphp
                                                                            <span class="{{ $diffClass }}">
                                                                                {{ $diferencia > 0 ? '+' : '' }}{{ $diferencia }}
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-semibold">Consistencia</td>
                                                                    <td>
                                                                        @if($resumenNotas['con_datos'] > 0)
                                                                            {{ $resumenNotas['con_datos'] }}/4 bimestres
                                                                        @else
                                                                            Sin datos
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($resumenConducta['con_datos'] > 0)
                                                                            {{ $resumenConducta['con_datos'] }}/4 bimestres
                                                                        @else
                                                                            Sin datos
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        @if($resumenNotas['con_datos'] !== null && $resumenConducta['con_datos'] !== null)
                                                                            @php
                                                                                $diferencia = $resumenNotas['con_datos'] - $resumenConducta['con_datos'];
                                                                                $diffClass = $diferencia > 0 ? 'text-success' :
                                                                                            ($diferencia < 0 ? 'text-danger' : 'text-muted');
                                                                            @endphp
                                                                            <span class="{{ $diffClass }}">
                                                                                {{ $diferencia > 0 ? '+' : '' }}{{ $diferencia }} bimestres
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Estudiantes -->
                    <div class="row estudiantes-section d-none" id="estudiantes-{{ $asignacion->id }}">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>Lista de Estudiantes
                                        <small class="text-muted">({{ $totalEstudiantes }} estudiantes)</small>
                                    </h5>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary toggle-estudiante-view active"
                                                data-view="todos">
                                            Todos
                                        </button>
                                        <button type="button" class="btn btn-outline-success toggle-estudiante-view"
                                                data-view="con-notas">
                                            Con Notas
                                        </button>
                                        <button type="button" class="btn btn-outline-info toggle-estudiante-view"
                                                data-view="con-conducta">
                                            Con Conducta
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover" id="tablaEstudiantes{{ $asignacion->id }}">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>DNI</th>
                                                    <th>Nombre Completo</th>
                                                    <th class="text-center">Notas</th>
                                                    <th class="text-center">Conducta</th>
                                                    <th class="text-center">Prom. Notas</th>
                                                    <th class="text-center">Prom. Conducta</th>
                                                    <th class="text-center">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($estudiantesGrado as $index => $estudiante)
                                                    @php
                                                        $progresoEst = $progreso['progreso'][$estudiante->id] ?? null;
                                                        $progresoCondEst = $progresoCond['progreso'][$estudiante->id] ?? null;

                                                        $tieneNotas = $progresoEst ? ($progresoEst['total_bimestres_con_datos'] ?? 0) > 0 : false;
                                                        $tieneConducta = $progresoCondEst ? ($progresoCondEst['total_bimestres_con_datos'] ?? 0) > 0 : false;

                                                        $promedioNotas = $progresoEst['promedio_general'] ?? null;
                                                        $promedioConducta = $progresoCondEst['promedio_general'] ?? null;

                                                        $colorNota = $promedioNotas >= 3 ? 'text-success' :
                                                                    ($promedioNotas >= 2 ? 'text-warning' : 'text-danger');
                                                        $colorConducta = $promedioConducta >= 3 ? 'text-success' :
                                                                       ($promedioConducta >= 2 ? 'text-warning' : 'text-danger');

                                                        $estadoClass = $tieneNotas && $tieneConducta ? 'table-success' :
                                                                      ($tieneNotas || $tieneConducta ? 'table-warning' : 'table-danger');
                                                    @endphp
                                                    <tr class="estudiante-row {{ $estadoClass }}"
                                                        data-tiene-notas="{{ $tieneNotas ? '1' : '0' }}"
                                                        data-tiene-conducta="{{ $tieneConducta ? '1' : '0' }}">
                                                        <td class="text-muted">{{ $index + 1 }}</td>
                                                        <td>
                                                            <code>{{ $estudiante->user->dni ?? 'N/A' }}</code>
                                                        </td>
                                                        <td>
                                                            <strong>{{ $estudiante->user->nombre }}</strong>
                                                            {{ $estudiante->user->apellido_paterno }}
                                                            @if($estudiante->user->apellido_materno)
                                                                {{ $estudiante->user->apellido_materno }}
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($tieneNotas)
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check"></i>
                                                                    {{ $progresoEst['total_bimestres_con_datos'] ?? 0 }}/4
                                                                </span>
                                                            @else
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-times"></i> 0/4
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($tieneConducta)
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check"></i>
                                                                    {{ $progresoCondEst['total_bimestres_con_datos'] ?? 0 }}/4
                                                                </span>
                                                            @else
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-times"></i> 0/4
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($promedioNotas !== null)
                                                                <span class="fw-bold {{ $colorNota }}">
                                                                    {{ $promedioNotas }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($promedioConducta !== null)
                                                                <span class="fw-bold {{ $colorConducta }}">
                                                                    {{ $promedioConducta }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($tieneNotas && $tieneConducta)
                                                                <span class="badge bg-success">Completo</span>
                                                            @elseif($tieneNotas || $tieneConducta)
                                                                <span class="badge bg-warning">Parcial</span>
                                                            @else
                                                                <span class="badge bg-danger">Sin datos</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Mensaje cuando no hay periodo seleccionado -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay periodos académicos activos</h4>
                        <p class="text-muted">Contacta con la administración para más información.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if($periodoSeleccionado)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Charts storage
            const charts = {};

            // Inicializar todos los gráficos
            @foreach($asignaciones as $asignacion)
                @if(isset($datosGraficos['estudiantes_lineas'][$asignacion->id]))
                    @php
                        $graficoNotas = $datosGraficos['estudiantes_lineas'][$asignacion->id];
                    @endphp
                    const ctxNotas{{ $asignacion->id }} = document.getElementById('chartNotas{{ $asignacion->id }}')?.getContext('2d');
                    if (ctxNotas{{ $asignacion->id }}) {
                        charts['chartNotas{{ $asignacion->id }}'] = new Chart(ctxNotas{{ $asignacion->id }}, {
                            type: 'line',
                            data: {
                                labels: @json($graficoNotas['labels']),
                                datasets: @json($graficoNotas['datasets'])
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
                                                if (context.parsed.y !== null) {
                                                    label += context.parsed.y + ' / 4';
                                                }
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 4,
                                        min: 0,
                                        ticks: {
                                            stepSize: 0.5,
                                            callback: function(value) {
                                                return value + ' / 4';
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: 'Calificación (Escala 1-4)'
                                        },
                                        grid: {
                                            color: function(context) {
                                                if (context.tick.value === 0) {
                                                    return 'rgba(0,0,0,0.1)';
                                                }
                                                return 'rgba(0,0,0,0.05)';
                                            }
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Bimestres'
                                        },
                                        grid: { display: false }
                                    }
                                },
                                interaction: {
                                    intersect: false,
                                    mode: 'nearest'
                                },
                                elements: {
                                    point: {
                                        radius: 4,
                                        hoverRadius: 6
                                    },
                                    line: {
                                        tension: 0.2
                                    }
                                }
                            }
                        });
                    }
                @endif

                @if(isset($datosGraficosConducta['conducta_lineas'][$asignacion->id]))
                    @php
                        $graficoConducta = $datosGraficosConducta['conducta_lineas'][$asignacion->id];
                    @endphp
                    const ctxConducta{{ $asignacion->id }} = document.getElementById('chartConducta{{ $asignacion->id }}')?.getContext('2d');
                    if (ctxConducta{{ $asignacion->id }}) {
                        charts['chartConducta{{ $asignacion->id }}'] = new Chart(ctxConducta{{ $asignacion->id }}, {
                            type: 'line',
                            data: {
                                labels: @json($graficoConducta['labels']),
                                datasets: @json($graficoConducta['datasets'])
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
                                                if (context.parsed.y !== null) {
                                                    label += context.parsed.y + ' / 4';
                                                }
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 4,
                                        min: 0,
                                        ticks: {
                                            stepSize: 0.5,
                                            callback: function(value) {
                                                return value + ' / 4';
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: 'Calificación Conducta (Escala 1-4)'
                                        },
                                        grid: {
                                            color: function(context) {
                                                if (context.tick.value === 0) {
                                                    return 'rgba(0,0,0,0.1)';
                                                }
                                                return 'rgba(0,0,0,0.05)';
                                            }
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Bimestres'
                                        },
                                        grid: { display: false }
                                    }
                                },
                                interaction: {
                                    intersect: false,
                                    mode: 'nearest'
                                },
                                elements: {
                                    point: {
                                        radius: 4,
                                        hoverRadius: 6
                                    },
                                    line: {
                                        tension: 0.2
                                    }
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
                    const section = document.querySelector(target);

                    // Toggle active class
                    document.querySelectorAll('.toggle-section').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');

                    // Hide all sections
                    document.querySelectorAll('.graficos-section, .detalles-section, .estudiantes-section').forEach(sec => {
                        sec.classList.add('d-none');
                    });

                    // Show target section
                    section.classList.remove('d-none');
                });
            });

            // Toggle between gráficos de notas y conducta
            document.querySelectorAll('.toggle-grafico').forEach(button => {
                button.addEventListener('click', function() {
                    const graficoType = this.dataset.grafico.split('-')[0]; // "notas" o "conducta"
                    const asignacionId = this.dataset.grafico.split('-')[1]; // el ID
                    const containerId = `grafico-${graficoType}-${asignacionId}`;

                    // Toggle active class
                    const buttonGroup = this.closest('.btn-group');
                    if (buttonGroup) {
                        buttonGroup.querySelectorAll('.toggle-grafico').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                    }

                    // Encontrar todos los contenedores de gráficos en esta card
                    const card = this.closest('.card');
                    if (card) {
                        const cardBody = card.querySelector('.card-body');
                        if (cardBody) {
                            // Hide all gráficos
                            cardBody.querySelectorAll('.grafico-container').forEach(container => {
                                container.classList.add('d-none');
                            });

                            // Show target gráfico
                            const targetContainer = cardBody.querySelector(`#${containerId}`);
                            if (targetContainer) {
                                targetContainer.classList.remove('d-none');

                                // Redibujar el gráfico si existe
                                const canvas = targetContainer.querySelector('canvas');
                                if (canvas) {
                                    const chartId = canvas.id;
                                    const chart = charts[chartId];
                                    if (chart) {
                                        // Darle tiempo al DOM para actualizar
                                        setTimeout(() => {
                                            chart.resize();
                                            chart.update();
                                        }, 10);
                                    }
                                }
                            }
                        }
                    }
                });
            });

            // Toggle individual student visibility in charts
            document.querySelectorAll('.toggle-estudiante').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const chartId = this.dataset.chartId;
                    const datasetIndex = parseInt(this.dataset.datasetIndex);
                    const chart = charts[chartId];

                    if (chart) {
                        // Toggle dataset visibility
                        chart.setDatasetVisibility(datasetIndex, this.checked);
                        chart.update();

                        // Update "Mostrar todos" switch
                        const container = this.closest('.estudiante-leyenda')?.closest('.row');
                        if (container) {
                            const allChecked = Array.from(container.querySelectorAll('.toggle-estudiante'))
                                .every(cb => cb.checked);
                            const toggleAll = container.parentElement.querySelector('.toggle-estudiantes');
                            if (toggleAll) {
                                toggleAll.checked = allChecked;
                            }
                        }
                    }
                });
            });

            // Toggle all students visibility
            document.querySelectorAll('.toggle-estudiantes').forEach(switchEl => {
                switchEl.addEventListener('change', function() {
                    const container = this.closest('.card-body') || this.closest('.mt-3');
                    const checkboxes = container?.querySelectorAll('.toggle-estudiante');
                    const chartId = checkboxes?.[0]?.dataset.chartId;
                    const chart = charts[chartId];

                    if (chart && checkboxes) {
                        checkboxes.forEach((checkbox, index) => {
                            checkbox.checked = this.checked;
                            if (chart) {
                                chart.setDatasetVisibility(index, this.checked);
                            }
                        });
                        if (chart) chart.update();
                    }
                });
            });

            // Toggle student table view
            document.querySelectorAll('.toggle-estudiante-view').forEach(button => {
                button.addEventListener('click', function() {
                    const view = this.dataset.view;
                    const table = this.closest('.card').querySelector('table');

                    // Toggle active class
                    this.closest('.btn-group').querySelectorAll('.toggle-estudiante-view').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');

                    // Filter rows
                    table.querySelectorAll('.estudiante-row').forEach(row => {
                        const tieneNotas = row.dataset.tieneNotas === '1';
                        const tieneConducta = row.dataset.tieneConducta === '1';

                        let shouldShow = true;

                        switch(view) {
                            case 'con-notas':
                                shouldShow = tieneNotas;
                                break;
                            case 'con-conducta':
                                shouldShow = tieneConducta;
                                break;
                            // 'todos' shows all
                        }

                        row.style.display = shouldShow ? '' : 'none';
                    });
                });
            });
        });
    </script>
@endif
@endsection
