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
                $data = $asignacionesData[$asignacion->id] ?? null;
            @endphp

            @if($data)
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
                                                {{ $data['materia_nombre'] }}
                                                <small class="text-muted">- {{ $data['grado_nombre'] }}</small>
                                            </h4>
                                            <p class="mb-0 text-muted">
                                                <small>
                                                    <i class="fas fa-calendar me-1"></i> {{ $data['periodo_anio'] }}
                                                    | <i class="fas fa-users me-1"></i> {{ $data['total_estudiantes'] }} estudiantes
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
                                        @if($data['datos_grafico_notas'])
                                            <div class="chart-container" style="height: 400px;">
                                                <canvas id="chartNotas{{ $asignacion->id }}"></canvas>
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
                                        @if($data['datos_grafico_conducta'] && !empty($data['datos_grafico_conducta']['datasets']))
                                            <div class="chart-container" style="height: 400px;">
                                                <canvas id="chartConducta{{ $asignacion->id }}"></canvas>
                                            </div>
                                        @else
                                            <div class="text-center py-5">
                                                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No hay suficientes datos para generar el gráfico de conducta.</p>
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
                                        <i class="fas fa-chart-bar me-2"></i>Estadísticas Detalladas - {{ $data['materia_nombre'] }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!--Resumen general-->
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
                                                                            {{ $data['estudiantes_con_notas'] }}/{{ $data['total_estudiantes'] }}
                                                                        </span>
                                                                        <small class="text-muted ms-2">
                                                                            @if($data['total_estudiantes'] > 0)
                                                                                {{ round(($data['estudiantes_con_notas'] / $data['total_estudiantes']) * 100, 1) }}%
                                                                            @else
                                                                                0%
                                                                            @endif
                                                                        </small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-light text-dark">
                                                                            {{ $data['estudiantes_con_conducta'] }}/{{ $data['total_estudiantes'] }}
                                                                        </span>
                                                                        <small class="text-muted ms-2">
                                                                            @if($data['total_estudiantes'] > 0)
                                                                                {{ round(($data['estudiantes_con_conducta'] / $data['total_estudiantes']) * 100, 1) }}%
                                                                            @else
                                                                                0%
                                                                            @endif
                                                                        </small>
                                                                    </td>
                                                                    <td class="fw-bold">
                                                                        {{ $data['total_estudiantes'] }} estudiantes
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <td class="fw-semibold">Promedio General</td>
                                                                    <td>
                                                                        @if($data['promedio_general_notas'] !== null)
                                                                            @php
                                                                                $colorNota = $data['promedio_general_notas'] >= 3 ? 'text-success' :
                                                                                            ($data['promedio_general_notas'] >= 2 ? 'text-warning' : 'text-danger');
                                                                            @endphp
                                                                            <span class="fw-bold {{ $colorNota }}">
                                                                                {{ $data['promedio_general_notas'] }} / 4
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($data['promedio_general_conducta'] !== null)
                                                                            @php
                                                                                $colorConducta = $data['promedio_general_conducta'] >= 3 ? 'text-success' :
                                                                                                ($data['promedio_general_conducta'] >= 2 ? 'text-warning' : 'text-danger');
                                                                            @endphp
                                                                            <span class="fw-bold {{ $colorConducta }}">
                                                                                {{ $data['promedio_general_conducta'] }} / 4
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $promedioGeneral = null;
                                                                            $conteoPromedios = 0;

                                                                            if ($data['promedio_general_notas'] !== null) {
                                                                                $promedioGeneral = $data['promedio_general_notas'];
                                                                                $conteoPromedios++;
                                                                            }
                                                                            if ($data['promedio_general_conducta'] !== null) {
                                                                                $promedioGeneral = $promedioGeneral ?
                                                                                    ($promedioGeneral + $data['promedio_general_conducta']) :
                                                                                    $data['promedio_general_conducta'];
                                                                                $conteoPromedios++;
                                                                            }

                                                                            $promedioTotal = $conteoPromedios > 0 ?
                                                                                round($promedioGeneral / $conteoPromedios, 2) : null;
                                                                        @endphp

                                                                        @if($promedioTotal !== null)
                                                                            <span class="fw-bold {{ $promedioTotal >= 3 ? 'text-success' : ($promedioTotal >= 2 ? 'text-warning' : 'text-danger') }}">
                                                                                {{ $promedioTotal }} / 4
                                                                            </span>
                                                                            <br>
                                                                            <small class="text-muted">Promedio general</small>
                                                                        @else
                                                                            <span class="text-muted">--</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <td class="fw-semibold">Registros Completados</td>
                                                                    <td>
                                                                        @php
                                                                            $totalRegistrosNotas = 0;
                                                                            $totalPosiblesNotas = 0;
                                                                            foreach($data['estadisticas_bimestres']['notas'] as $stats) {
                                                                                $totalRegistrosNotas += $stats['total_notas_registradas'] ?? 0;
                                                                                $totalPosiblesNotas += $stats['total_notas_posibles'] ?? 0;
                                                                            }
                                                                            $porcentajeNotas = $totalPosiblesNotas > 0 ?
                                                                                round(($totalRegistrosNotas / $totalPosiblesNotas) * 100, 1) : 0;
                                                                        @endphp
                                                                        <span class="fw-bold {{ $porcentajeNotas >= 80 ? 'text-success' : ($porcentajeNotas >= 50 ? 'text-warning' : 'text-danger') }}">
                                                                            {{ $porcentajeNotas }}%
                                                                        </span>
                                                                        <br>
                                                                        <small class="text-muted">{{ number_format($totalRegistrosNotas) }}/{{ number_format($totalPosiblesNotas) }} registros</small>
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $totalRegistrosConducta = 0;
                                                                            $totalPosiblesConducta = 0;
                                                                            foreach($data['estadisticas_bimestres']['conducta'] as $stats) {
                                                                                $totalRegistrosConducta += $stats['total_conductas_registradas'] ?? 0;
                                                                                $totalPosiblesConducta += $stats['total_conductas_posibles'] ?? 0;
                                                                            }
                                                                            $porcentajeConducta = $totalPosiblesConducta > 0 ?
                                                                                round(($totalRegistrosConducta / $totalPosiblesConducta) * 100, 1) : 0;
                                                                        @endphp
                                                                        <span class="fw-bold {{ $porcentajeConducta >= 80 ? 'text-success' : ($porcentajeConducta >= 50 ? 'text-warning' : 'text-danger') }}">
                                                                            {{ $porcentajeConducta }}%
                                                                        </span>
                                                                        <br>
                                                                        <small class="text-muted">{{ number_format($totalRegistrosConducta) }}/{{ number_format($totalPosiblesConducta) }} registros</small>
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $totalRegistros = $totalRegistrosNotas + $totalRegistrosConducta;
                                                                            $totalPosibles = $totalPosiblesNotas + $totalPosiblesConducta;
                                                                            $porcentajeTotal = $totalPosibles > 0 ?
                                                                                round(($totalRegistros / $totalPosibles) * 100, 1) : 0;
                                                                        @endphp
                                                                        <span class="fw-bold {{ $porcentajeTotal >= 80 ? 'text-success' : ($porcentajeTotal >= 50 ? 'text-warning' : 'text-danger') }}">
                                                                            {{ $porcentajeTotal }}%
                                                                        </span>
                                                                        <br>
                                                                        <small class="text-muted">{{ number_format($totalRegistros) }}/{{ number_format($totalPosibles) }} total</small>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <td class="fw-semibold">Bimestres con Datos</td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-info">
                                                                            {{ $data['resumen_notas']['con_datos'] }}/4 bimestres
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-info">
                                                                            {{ $data['resumen_conducta']['con_datos'] }}/4 bimestres
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        @php
                                                                            $bimestresConDatos = max(
                                                                                $data['resumen_notas']['con_datos'],
                                                                                $data['resumen_conducta']['con_datos']
                                                                            );
                                                                        @endphp
                                                                        <span class="badge bg-info">
                                                                            {{ $bimestresConDatos }}/4 bimestres
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Tabla de Notas y Conducta por Bimestre -->
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
                                                                    <th style="width: 8%">Bim.</th>
                                                                    <th style="width: 12%">Estudiantes</th>
                                                                    <th style="width: 10%">Promedio</th>
                                                                    <th style="width: 10%">Criterios</th>
                                                                    <th style="width: 12%">Notas Regist.</th>
                                                                    <th style="width: 12%">Notas Posibles</th>
                                                                    <th style="width: 12%">% Avance</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($data['estadisticas_bimestres']['notas'] as $bimestre => $stats)
                                                                    @php
                                                                        $promedio = $stats['promedio'] ?? null;
                                                                        $porcentajeAvance = $stats['porcentaje_avance'] ?? 0;
                                                                        $porcentajeEstudiantes = $data['total_estudiantes'] > 0 ?
                                                                            round(($stats['total_estudiantes_con_notas'] ?? 0) / $data['total_estudiantes'] * 100, 1) : 0;

                                                                        $colorClass = $promedio >= 3 ? 'text-success' :
                                                                                    ($promedio >= 2 ? 'text-warning' : 'text-danger');

                                                                        $colorAvance = $porcentajeAvance >= 80 ? 'text-success' :
                                                                                    ($porcentajeAvance >= 50 ? 'text-warning' : 'text-danger');
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="fw-bold text-center">B{{ $bimestre }}</td>
                                                                        <td>
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <span class="badge bg-light text-dark">
                                                                                    {{ $stats['total_estudiantes_con_notas'] ?? 0 }}/{{ $data['total_estudiantes'] }}
                                                                                </span>
                                                                                <small class="text-muted">{{ $porcentajeEstudiantes }}%</small>
                                                                            </div>
                                                                        </td>
                                                                        <td class="{{ $colorClass }} fw-bold text-center">
                                                                            {{ $promedio ?? '--' }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-info">{{ $stats['criterios_en_bimestre'] ?? 0 }}</span>
                                                                        </td>
                                                                        <td class="text-center fw-bold">
                                                                            {{ number_format($stats['total_notas_registradas'] ?? 0) }}
                                                                        </td>
                                                                        <td class="text-center fw-bold">
                                                                            {{ number_format($stats['total_notas_posibles'] ?? 0) }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="fw-bold {{ $colorAvance }}">
                                                                                {{ $porcentajeAvance }}%
                                                                            </span>
                                                                            <div class="progress mt-1" style="height: 4px;">
                                                                                <div class="progress-bar {{ $porcentajeAvance >= 80 ? 'bg-success' : ($porcentajeAvance >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                                                    style="width: {{ $porcentajeAvance }}%"></div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot class="bg-light">
                                                                @php
                                                                    $totalNotasRegistradas = 0;
                                                                    $totalNotasPosibles = 0;
                                                                    foreach($data['estadisticas_bimestres']['notas'] as $stats) {
                                                                        $totalNotasRegistradas += $stats['total_notas_registradas'] ?? 0;
                                                                        $totalNotasPosibles += $stats['total_notas_posibles'] ?? 0;
                                                                    }
                                                                    $porcentajeGeneral = $totalNotasPosibles > 0 ? round(($totalNotasRegistradas / $totalNotasPosibles) * 100, 1) : 0;
                                                                @endphp
                                                                <tr>
                                                                    <td class="fw-bold">Totales</td>
                                                                    <td>
                                                                        <span class="badge bg-light text-dark">
                                                                            {{ $data['estudiantes_con_notas'] }}/{{ $data['total_estudiantes'] }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="fw-bold text-center">{{ $data['promedio_general_notas'] ?? '--' }}</td>
                                                                    <td class="fw-bold text-center">{{ $data['total_criterios'] }}</td>
                                                                    <td class="fw-bold text-center">{{ number_format($totalNotasRegistradas) }}</td>
                                                                    <td class="fw-bold text-center">{{ number_format($totalNotasPosibles) }}</td>
                                                                    <td class="text-center">
                                                                        <span class="fw-bold {{ $porcentajeGeneral >= 80 ? 'text-success' : ($porcentajeGeneral >= 50 ? 'text-warning' : 'text-danger') }}">
                                                                            {{ $porcentajeGeneral }}%
                                                                        </span>
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
                                                                    <th style="width: 8%">Bim.</th>
                                                                    <th style="width: 12%">Estudiantes</th>
                                                                    <th style="width: 10%">Promedio</th>
                                                                    <th style="width: 12%">Conductas Regist.</th>
                                                                    <th style="width: 12%">Conductas Posibles</th>
                                                                    <th style="width: 12%">% Avance</th>
                                                                    <th style="width: 15%">Rango (Min-Max)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($data['estadisticas_bimestres']['conducta'] as $bimestre => $stats)
                                                                    @php
                                                                        $promedio = $stats['promedio'] ?? null;
                                                                        $porcentajeAvance = $stats['porcentaje_avance'] ?? 0;

                                                                        $colorClass = $promedio >= 3 ? 'text-success' :
                                                                                    ($promedio >= 2 ? 'text-warning' : 'text-danger');

                                                                        $colorAvance = $porcentajeAvance >= 80 ? 'text-success' :
                                                                                    ($porcentajeAvance >= 50 ? 'text-warning' : 'text-danger');
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="fw-bold text-center">B{{ $bimestre }}</td>
                                                                        <td>
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <span class="badge bg-light text-dark">
                                                                                    {{ $stats['total_estudiantes_con_conducta'] ?? 0 }}/{{ $data['total_estudiantes'] }}
                                                                                </span>
                                                                                <small class="text-muted">{{ $stats['porcentaje_estudiantes'] ?? 0 }}%</small>
                                                                            </div>
                                                                        </td>
                                                                        <td class="{{ $colorClass }} fw-bold text-center">
                                                                            {{ $promedio ?? '--' }}
                                                                        </td>
                                                                        <td class="text-center fw-bold">
                                                                            {{ number_format($stats['total_conductas_registradas'] ?? 0) }}
                                                                        </td>
                                                                        <td class="text-center fw-bold">
                                                                            {{ number_format($stats['total_conductas_posibles'] ?? 0) }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="fw-bold {{ $colorAvance }}">
                                                                                {{ $porcentajeAvance }}%
                                                                            </span>
                                                                            <div class="progress mt-1" style="height: 4px;">
                                                                                <div class="progress-bar {{ $porcentajeAvance >= 80 ? 'bg-success' : ($porcentajeAvance >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                                                    style="width: {{ $porcentajeAvance }}%"></div>
                                                                            </div>
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
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot class="bg-light">
                                                                @php
                                                                    $totalConductasRegistradas = 0;
                                                                    $totalConductasPosibles = 0;
                                                                    foreach($data['estadisticas_bimestres']['conducta'] as $stats) {
                                                                        $totalConductasRegistradas += $stats['total_conductas_registradas'] ?? 0;
                                                                        $totalConductasPosibles += $stats['total_conductas_posibles'] ?? 0;
                                                                    }
                                                                    $porcentajeGeneralConducta = $totalConductasPosibles > 0 ? round(($totalConductasRegistradas / $totalConductasPosibles) * 100, 1) : 0;
                                                                @endphp
                                                                <tr>
                                                                    <td class="fw-bold">Totales</td>
                                                                    <td>
                                                                        <span class="badge bg-light text-dark">
                                                                            {{ $data['estudiantes_con_conducta'] }}/{{ $data['total_estudiantes'] }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="fw-bold text-center">{{ $data['promedio_general_conducta'] ?? '--' }}</td>
                                                                    <td class="fw-bold text-center">{{ number_format($totalConductasRegistradas) }}</td>
                                                                    <td class="fw-bold text-center">{{ number_format($totalConductasPosibles) }}</td>
                                                                    <td class="text-center">
                                                                        <span class="fw-bold {{ $porcentajeGeneralConducta >= 80 ? 'text-success' : ($porcentajeGeneralConducta >= 50 ? 'text-warning' : 'text-danger') }}">
                                                                            {{ $porcentajeGeneralConducta }}%
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <small class="text-muted">{{ $data['resumen_conducta']['con_datos'] }}/4 bim.</small>
                                                                    </td>
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
                        </div>
                    </div>
                    <!-- Lista de Estudiantes -->
                    <div class="row estudiantes-section d-none" id="estudiantes-{{ $asignacion->id }}">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>Lista de Estudiantes
                                        <small class="text-muted">({{ $data['total_estudiantes'] }} estudiantes)</small>
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
                                                    <th class="text-center" colspan="5">Notas Académicas por Bimestre</th>
                                                    <th class="text-center" colspan="5">Conducta por Bimestre</th>
                                                    <th class="text-center">Estado</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    {{-- Notas por bimestre --}}
                                                    <th class="text-center bg-light">B1</th>
                                                    <th class="text-center bg-light">B2</th>
                                                    <th class="text-center bg-light">B3</th>
                                                    <th class="text-center bg-light">B4</th>
                                                    <th class="text-center bg-light">Prom.</th>
                                                    {{-- Conducta por bimestre --}}
                                                    <th class="text-center bg-info bg-opacity-25">B1</th>
                                                    <th class="text-center bg-info bg-opacity-25">B2</th>
                                                    <th class="text-center bg-info bg-opacity-25">B3</th>
                                                    <th class="text-center bg-info bg-opacity-25">B4</th>
                                                    <th class="text-center bg-info bg-opacity-25">Prom.</th>
                                                    <th class="text-center">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data['estudiantes'] as $estudiante)
                                                    @php
                                                        // Obtener datos completos del progreso para este estudiante
                                                        $progresoEst = $data['progreso']['progreso'][$estudiante['id']] ?? null;
                                                        $progresoCondEst = $data['progreso_cond']['progreso'][$estudiante['id']] ?? null;

                                                        // Obtener criterios por bimestre (notas posibles)
                                                        $criteriosPorBimestre = $data['criterios_por_bimestre'] ?? [1=>0,2=>0,3=>0,4=>0];

                                                        // Calcular notas posibles para este estudiante
                                                        $notasPosibles = [];
                                                        $notasRegistradas = [];
                                                        $conductasPosibles = $data['total_estudiantes'] > 0 ? 4 : 0; // 4 bimestres posibles
                                                        $conductasRegistradas = 0;

                                                        for($b = 1; $b <= 4; $b++) {
                                                            // Notas posibles = criterios en este bimestre
                                                            $notasPosibles[$b] = $criteriosPorBimestre[$b] ?? 0;

                                                            // Notas registradas = criterios que tiene el estudiante en este bimestre
                                                            $notasRegistradas[$b] = $progresoEst['criterios_por_bimestre'][$b] ?? 0;

                                                            // Conducta
                                                            if($progresoCondEst && isset($progresoCondEst['conductas_por_bimestre'][$b])) {
                                                                $conductasRegistradas += $progresoCondEst['conductas_por_bimestre'][$b];
                                                            }
                                                        }

                                                        $totalNotasPosibles = array_sum($notasPosibles);
                                                        $totalNotasRegistradas = array_sum($notasRegistradas);
                                                        $porcentajeNotas = $totalNotasPosibles > 0 ?
                                                            round(($totalNotasRegistradas / $totalNotasPosibles) * 100, 1) : 0;

                                                        $porcentajeConducta = $conductasPosibles > 0 ?
                                                            round(($conductasRegistradas / $conductasPosibles) * 100, 1) : 0;
                                                    @endphp

                                                    <tr class="estudiante-row {{ $estudiante['estado_clase'] }}"
                                                        data-tiene-notas="{{ $estudiante['tiene_notas'] ? '1' : '0' }}"
                                                        data-tiene-conducta="{{ $estudiante['tiene_conducta'] ? '1' : '0' }}">

                                                        <td class="text-muted">{{ $estudiante['index'] }}</td>
                                                        <td><code>{{ $estudiante['dni'] }}</code></td>
                                                        <td>
                                                            <strong>{{ $estudiante['nombre_completo'] }}</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                Notas: {{ $totalNotasRegistradas }}/{{ $totalNotasPosibles }}
                                                                ({{ $porcentajeNotas }}%)
                                                            </small>
                                                        </td>

                                                        {{-- Notas por bimestre --}}
                                                        @for($b = 1; $b <= 4; $b++)
                                                            <td class="text-center">
                                                                @if(isset($progresoEst['datos'][$b]) && $progresoEst['datos'][$b] !== null)
                                                                    @php
                                                                        $nota = $progresoEst['datos'][$b];
                                                                        $colorNota = $nota >= 3 ? 'text-success' : ($nota >= 2 ? 'text-warning' : 'text-danger');
                                                                        $registradas = $progresoEst['criterios_por_bimestre'][$b] ?? 0;
                                                                        $posibles = $criteriosPorBimestre[$b] ?? 0;
                                                                    @endphp
                                                                    <span class="fw-bold {{ $colorNota }}">{{ $nota }}</span>
                                                                    <br>
                                                                    <small class="text-muted" title="Criterios registrados/posibles">
                                                                        {{ $registradas }}/{{ $posibles }}
                                                                    </small>
                                                                @else
                                                                    <span class="text-muted">--</span>
                                                                    <br>
                                                                    <small class="text-muted">0/{{ $criteriosPorBimestre[$b] ?? 0 }}</small>
                                                                @endif
                                                            </td>
                                                        @endfor

                                                        {{-- Promedio de notas --}}
                                                        <td class="text-center">
                                                            @if($estudiante['promedio_notas'] !== null)
                                                                <span class="fw-bold {{ $estudiante['color_nota'] }}">
                                                                    {{ $estudiante['promedio_notas'] }}
                                                                </span>
                                                                <br>
                                                                <small class="text-muted">
                                                                    {{ $estudiante['bimestres_notas'] }}/4 bim.
                                                                </small>
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </td>

                                                        {{-- Conducta por bimestre --}}
                                                        @for($b = 1; $b <= 4; $b++)
                                                            <td class="text-center">
                                                                @if(isset($progresoCondEst['datos'][$b]) && $progresoCondEst['datos'][$b] !== null)
                                                                    @php
                                                                        $conducta = $progresoCondEst['datos'][$b];
                                                                        $colorConducta = $conducta >= 3 ? 'text-success' : ($conducta >= 2 ? 'text-warning' : 'text-danger');
                                                                        $registradas = $progresoCondEst['conductas_por_bimestre'][$b] ?? 0;

                                                                    @endphp
                                                                    <span class="fw-bold {{ $colorConducta }}">{{ $conducta }}</span>
                                                                    <br>
                                                                    <small class="text-muted" title="Conductas registradas">
                                                                        {{ $registradas }} cond.
                                                                    </small>
                                                                @else
                                                                    <span class="text-muted">--</span>
                                                                    <br>
                                                                    <small class="text-muted">0 cond.</small>
                                                                @endif
                                                            </td>
                                                        @endfor

                                                        {{-- Promedio de conducta --}}
                                                        <td class="text-center">
                                                            @if($estudiante['promedio_conducta'] !== null)
                                                                <span class="fw-bold {{ $estudiante['color_conducta'] }}">
                                                                    {{ $estudiante['promedio_conducta'] }}
                                                                </span>
                                                                <br>
                                                                <small class="text-muted">
                                                                    {{ $estudiante['bimestres_conducta'] }}/4 bim.
                                                                </small>
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </td>

                                                        {{-- Estado --}}
                                                        <td class="text-center">
                                                            <span class="badge bg-{{ $estudiante['estado_texto'] == 'Completo' ? 'success' : ($estudiante['estado_texto'] == 'Parcial' ? 'warning' : 'danger') }}">
                                                                {{ $estudiante['estado_texto'] }}
                                                            </span>
                                                            <br>
                                                            <small class="text-muted">
                                                                {{ $porcentajeNotas }}% | {{ $porcentajeConducta }}%
                                                            </small>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-light">
                                                @php
                                                    // Calcular totales para el footer
                                                    $totalEstudiantes = count($data['estudiantes']);
                                                    $totalNotasRegistradasGeneral = 0;
                                                    $totalNotasPosiblesGeneral = 0;
                                                    $totalConductasRegistradasGeneral = 0;
                                                    $totalConductasPosiblesGeneral = $totalEstudiantes * 4; // 4 bimestres posibles por estudiante

                                                    foreach($data['estudiantes'] as $estudiante) {
                                                        $progresoEst = $data['progreso']['progreso'][$estudiante['id']] ?? null;
                                                        $progresoCondEst = $data['progreso_cond']['progreso'][$estudiante['id']] ?? null;

                                                        for($b = 1; $b <= 4; $b++) {
                                                            $totalNotasRegistradasGeneral += $progresoEst['criterios_por_bimestre'][$b] ?? 0;
                                                            $totalNotasPosiblesGeneral += $data['criterios_por_bimestre'][$b] ?? 0;
                                                            $totalConductasRegistradasGeneral += $progresoCondEst['conductas_por_bimestre'][$b] ?? 0;
                                                        }
                                                    }

                                                    $porcentajeNotasGeneral = $totalNotasPosiblesGeneral > 0 ?
                                                        round(($totalNotasRegistradasGeneral / $totalNotasPosiblesGeneral) * 100, 1) : 0;
                                                    $porcentajeConductaGeneral = $totalConductasPosiblesGeneral > 0 ?
                                                        round(($totalConductasRegistradasGeneral / $totalConductasPosiblesGeneral) * 100, 1) : 0;
                                                @endphp
                                                <tr>
                                                    <td colspan="3" class="fw-bold text-end">Totales:</td>
                                                    <td colspan="5" class="text-center">
                                                        <span class="fw-bold {{ $porcentajeNotasGeneral >= 80 ? 'text-success' : ($porcentajeNotasGeneral >= 50 ? 'text-warning' : 'text-danger') }}">
                                                            {{ $totalNotasRegistradasGeneral }}/{{ $totalNotasPosiblesGeneral }} registros
                                                            ({{ $porcentajeNotasGeneral }}%)
                                                        </span>
                                                    </td>
                                                    <td colspan="5" class="text-center">
                                                        <span class="fw-bold {{ $porcentajeConductaGeneral >= 80 ? 'text-success' : ($porcentajeConductaGeneral >= 50 ? 'text-warning' : 'text-danger') }}">
                                                            {{ $totalConductasRegistradasGeneral }}/{{ $totalConductasPosiblesGeneral }} registros
                                                            ({{ $porcentajeConductaGeneral }}%)
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-info">
                                                            {{ round(($porcentajeNotasGeneral + $porcentajeConductaGeneral) / 2, 1) }}% total
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
</div>

@if($periodoSeleccionado)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Charts storage
            const charts = {};

            // Paleta de colores para los estudiantes (la misma del dashboard estudiante)
            const colores = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#8AC926', '#1982C4',
                '#6A4C93', '#F15BB5', '#00BBF9', '#00F5D4'
            ];

            // Inicializar todos los gráficos de notas académicas
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
                                datasets: @json($graficoNotas['datasets']).map((dataset, index) => ({
                                    ...dataset,
                                    borderColor: colores[index % colores.length],
                                    backgroundColor: colores[index % colores.length] + '40',
                                    tension: 0.3,
                                    fill: false,
                                    pointBackgroundColor: colores[index % colores.length],
                                    pointBorderColor: '#fff',
                                    pointRadius: 5,
                                    pointHoverRadius: 7,
                                    spanGaps: true
                                }))
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Progreso Académico - {{ $graficoNotas['materia'] }} ({{ $graficoNotas['grado'] }})',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    },
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            boxWidth: 12,
                                            padding: 10,
                                            usePointStyle: true,
                                            pointStyle: 'circle'
                                        },
                                        onClick: function(e, legendItem, legend) {
                                            const index = legendItem.datasetIndex;
                                            const ci = this.chart;
                                            const meta = ci.getDatasetMeta(index);
                                            meta.hidden = meta.hidden === null ? !ci.data.datasets[index].hidden : null;
                                            ci.update();
                                        }
                                    },
                                    tooltip: {
                                        mode: 'nearest', // Cambiado de 'index' a 'nearest'
                                        intersect: true,  // Cambiado de false a true
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
                                            },
                                            title: function(tooltipItems) {
                                                // Mostrar el bimestre como título del tooltip
                                                const bimestre = tooltipItems[0].label;
                                                return 'Bimestre: ' + bimestre;
                                            }
                                        },
                                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        padding: 10,
                                        displayColors: true,
                                        boxPadding: 5
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: false,
                                        min: 1,
                                        max: 4,
                                        title: {
                                            display: true,
                                            text: 'Notas (1-4)'
                                        },
                                        ticks: {
                                            stepSize: 0.5,
                                            callback: function(value) {
                                                if (value === 2.5) {
                                                    return value.toFixed(1) + ' (Mínimo)';
                                                }
                                                return value.toFixed(1);
                                            }
                                        },
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.1)'
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Bimestres'
                                        },
                                        grid: {
                                            display: false
                                        }
                                    }
                                },
                                interaction: {
                                    mode: 'nearest', // También aquí
                                    intersect: true
                                },
                                elements: {
                                    point: {
                                        hoverBackgroundColor: '#fff',
                                        hoverBorderWidth: 2,
                                        hoverRadius: 8
                                    },
                                    line: {
                                        borderWidth: 2,
                                        hoverBorderWidth: 3
                                    }
                                },
                                hover: {
                                    mode: 'nearest',
                                    intersect: true
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
                                datasets: @json($graficoConducta['datasets']).map((dataset, index) => ({
                                    ...dataset,
                                    borderColor: colores[index % colores.length],
                                    backgroundColor: colores[index % colores.length] + '40',
                                    tension: 0.3,
                                    fill: false,
                                    pointBackgroundColor: colores[index % colores.length],
                                    pointBorderColor: '#fff',
                                    pointRadius: 5,
                                    pointHoverRadius: 7,
                                    spanGaps: true
                                }))
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Progreso de Conducta - {{ $graficoConducta['materia'] }} ({{ $graficoConducta['grado'] }})',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    },
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            boxWidth: 12,
                                            padding: 10,
                                            usePointStyle: true,
                                            pointStyle: 'circle'
                                        },
                                        onClick: function(e, legendItem, legend) {
                                            const index = legendItem.datasetIndex;
                                            const ci = this.chart;
                                            const meta = ci.getDatasetMeta(index);
                                            meta.hidden = meta.hidden === null ? !ci.data.datasets[index].hidden : null;
                                            ci.update();
                                        }
                                    },
                                    tooltip: {
                                        mode: 'nearest', // Cambiado de 'index' a 'nearest'
                                        intersect: true,  // Cambiado de false a true
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
                                            },
                                            title: function(tooltipItems) {
                                                const bimestre = tooltipItems[0].label;
                                                return 'Bimestre: ' + bimestre;
                                            }
                                        },
                                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        padding: 10,
                                        displayColors: true,
                                        boxPadding: 5
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: false,
                                        min: 1,
                                        max: 4,
                                        title: {
                                            display: true,
                                            text: 'Notas (1-4)'
                                        },
                                        ticks: {
                                            stepSize: 0.5,
                                            callback: function(value) {
                                                if (value === 2.5) {
                                                    return value.toFixed(1) + ' (Mínimo)';
                                                }
                                                return value.toFixed(1);
                                            }
                                        },
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.1)'
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Bimestres'
                                        },
                                        grid: {
                                            display: false
                                        }
                                    }
                                },
                                interaction: {
                                    mode: 'nearest', // También aquí
                                    intersect: true
                                },
                                elements: {
                                    point: {
                                        hoverBackgroundColor: '#fff',
                                        hoverBorderWidth: 2,
                                        hoverRadius: 8
                                    },
                                    line: {
                                        borderWidth: 2,
                                        hoverBorderWidth: 3
                                    }
                                },
                                hover: {
                                    mode: 'nearest',
                                    intersect: true
                                }
                            }
                        });
                    }
                @endif
            @endforeach

            // Toggle sections (mantener la misma lógica)
            document.querySelectorAll('.toggle-section').forEach(button => {
                button.addEventListener('click', function() {
                    const target = this.dataset.target;
                    const section = document.querySelector(target);

                    document.querySelectorAll('.toggle-section').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');

                    document.querySelectorAll('.graficos-section, .detalles-section, .estudiantes-section').forEach(sec => {
                        sec.classList.add('d-none');
                    });

                    section.classList.remove('d-none');
                });
            });

            // Toggle between gráficos de notas y conducta
            document.querySelectorAll('.toggle-grafico').forEach(button => {
                button.addEventListener('click', function() {
                    const graficoType = this.dataset.grafico.split('-')[0];
                    const asignacionId = this.dataset.grafico.split('-')[1];
                    const containerId = `grafico-${graficoType}-${asignacionId}`;

                    const buttonGroup = this.closest('.btn-group');
                    if (buttonGroup) {
                        buttonGroup.querySelectorAll('.toggle-grafico').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                    }

                    const card = this.closest('.card');
                    if (card) {
                        const cardBody = card.querySelector('.card-body');
                        if (cardBody) {
                            cardBody.querySelectorAll('.grafico-container').forEach(container => {
                                container.classList.add('d-none');
                            });

                            const targetContainer = cardBody.querySelector(`#${containerId}`);
                            if (targetContainer) {
                                targetContainer.classList.remove('d-none');

                                const canvas = targetContainer.querySelector('canvas');
                                if (canvas) {
                                    const chartId = canvas.id;
                                    const chart = charts[chartId];
                                    if (chart) {
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

            // Toggle student table view (mantener esta funcionalidad si la necesitas)
            document.querySelectorAll('.toggle-estudiante-view').forEach(button => {
                button.addEventListener('click', function() {
                    const view = this.dataset.view;
                    const table = this.closest('.card').querySelector('table');

                    this.closest('.btn-group').querySelectorAll('.toggle-estudiante-view').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');

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
                        }

                        row.style.display = shouldShow ? '' : 'none';
                    });
                });
            });

            // Función para redimensionar gráficos cuando cambia el tamaño de la ventana
            window.addEventListener('resize', function() {
                Object.values(charts).forEach(chart => {
                    chart.resize();
                });
            });

            // Botón para mostrar/ocultar todos los estudiantes (opcional)
            document.querySelectorAll('.toggle-all-estudiantes').forEach(button => {
                button.addEventListener('click', function() {
                    const card = this.closest('.card');
                    const canvas = card.querySelector('canvas');
                    if (canvas) {
                        const chartId = canvas.id;
                        const chart = charts[chartId];

                        if (chart) {
                            const allHidden = chart.data.datasets.every((dataset, index) => {
                                const meta = chart.getDatasetMeta(index);
                                return meta.hidden === true;
                            });

                            chart.data.datasets.forEach((dataset, index) => {
                                const meta = chart.getDatasetMeta(index);
                                meta.hidden = !allHidden;
                            });

                            chart.update();
                            this.textContent = allHidden ? 'Ocultar Todos' : 'Mostrar Todos';
                        }
                    }
                });
            });
        });
    </script>
@endif
@endsection
