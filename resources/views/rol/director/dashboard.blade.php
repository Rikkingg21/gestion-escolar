@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h4 mb-0 text-gray-800">
                <i class="fas fa-chalkboard-user text-primary me-2"></i>Dashboard Director
            </h1>
            <p class="text-muted small mb-0 mt-1">
                <i class="fas fa-calendar-alt me-1"></i> {{ now()->format('d/m/Y H:i') }} |
                <i class="fas fa-chart-bar me-1"></i> Análisis Institucional
            </p>
        </div>
        <div class="mt-3 mt-sm-0">
            <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-group" style="width: auto;">
                    <span class="input-group-text bg-white text-muted">
                        <i class="bi bi-calendar4-week"></i>
                    </span>
                    <select name="periodo_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                        <option value="">-- Seleccione un periodo --</option>
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo->id }}"
                                {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                {{ $periodo->nombre }} ({{ $periodo->anio }})
                                @if($periodo->estado == '1') <span class="text-success">✓ Activo</span> @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($bimestresDisponibles->count() > 0)
                <div class="input-group" style="width: auto;">
                    <span class="input-group-text bg-white text-muted">
                        <i class="bi bi-layers"></i>
                    </span>
                    <select name="periodobimestre_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 120px;">
                        <option value="">Todos los Bimestres</option>
                        @foreach($bimestresDisponibles as $bim)
                            <option value="{{ $bim->id }}" {{ $bimestreSeleccionado && $bimestreSeleccionado->id == $bim->id ? 'selected' : '' }}>
                                {{ $bim->sigla }} - {{ $bim->nombre ?? $bim->bimestre . '° Bimestre' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($periodoSeleccionado)
                <a href="{{ route('dashboard.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-eraser-fill"></i> Limpiar
                </a>
                @endif
            </form>
        </div>
    </div>

    @if($periodoSeleccionado)
        <!-- Alert de periodo activo -->
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Periodo seleccionado:</strong> {{ $periodoSeleccionado->nombre }} ({{ $periodoSeleccionado->anio }})
            @if($bimestreSeleccionado)
                <strong> | Bimestre:</strong> {{ $bimestreSeleccionado->sigla }} - {{ $bimestreSeleccionado->nombre ?? $bimestreSeleccionado->bimestre . '° Bimestre' }}
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- KPIs Principales -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    <i class="fas fa-graduation-cap me-1"></i> Grados Activos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $estadisticas['total_grados'] }}
                                </div>
                                <small class="text-muted">Con estudiantes matriculados</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    <i class="fas fa-users me-1"></i> Estudiantes Matriculados
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($estadisticas['total_estudiantes']) }}
                                </div>
                                <small class="text-muted">En {{ $estadisticas['total_grados'] }} grados</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    <i class="fas fa-book-open me-1"></i> Total Materias
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($estadisticas['total_materias']) }}
                                </div>
                                <small class="text-muted">Promedio por grado: {{ $estadisticas['total_grados'] > 0 ? round($estadisticas['total_materias'] / $estadisticas['total_grados'], 1) : 0 }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    <i class="fas fa-chart-line me-1"></i> Promedio General
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($estadisticas['promedio_general'], 2) }}
                                </div>
                                <small class="text-muted">
                                    Escala 1.0 - 4.0
                                    @if($estadisticas['promedio_general'] >= 3.0)
                                        <span class="badge bg-success">Excelente</span>
                                    @elseif($estadisticas['promedio_general'] >= 2.5)
                                        <span class="badge bg-primary">Bueno</span>
                                    @elseif($estadisticas['promedio_general'] >= 2.0)
                                        <span class="badge bg-warning">Regular</span>
                                    @else
                                        <span class="badge bg-danger">Bajo</span>
                                    @endif
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs de Asistencia -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    <i class="fas fa-calendar-check me-1"></i> Total Asistencias
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($estadisticas['total_registros_asistencia'] ?? 0) }}
                                </div>
                                <small class="text-muted">Total de registros</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    <i class="fas fa-chart-pie me-1"></i> Desglose
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span class="text-success">{{ number_format($estadisticas['total_puntualidad'] ?? 0) }}</span> /
                                    <span class="text-danger">{{ number_format($estadisticas['total_falta'] ?? 0) }}</span> /
                                    <span class="text-warning">{{ number_format($estadisticas['total_tardanza'] ?? 0) }}</span>
                                </div>
                                <small class="text-muted">Puntualidad / Faltas / Tardanzas</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    <i class="fas fa-percent me-1"></i> Porcentaje Puntualidad
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($estadisticas['porcentaje_puntualidad'], 2) }}%
                                </div>
                                <small class="text-muted">
                                    Faltas: {{ number_format($estadisticas['porcentaje_falta'], 2) }}% |
                                    Tardanzas: {{ number_format($estadisticas['porcentaje_tardanza'], 2) }}%
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-percent fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Rendimiento y Asistencia (2 columnas) -->
        <div class="row mb-4">
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar me-2"></i> Rendimiento por Grado
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($grados->count() > 0)
                            <div style="height: 350px;">
                                <canvas id="rendimientoGradosChart"></canvas>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No hay datos para mostrar</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar me-2"></i> Asistencia por Grado
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($grados->count() > 0)
                            <div style="height: 350px;">
                                <canvas id="asistenciaGradosChart"></canvas>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No hay datos para mostrar</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Distribución (2 columnas) -->
        <div class="row mb-4">
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i> Distribución por Categoría Académica
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($estadisticas['total_grados'] > 0)
                            <div style="height: 350px;">
                                <canvas id="categoriasChart"></canvas>
                            </div>
                            <div class="mt-3 text-center">
                                <div class="d-flex justify-content-center gap-3 flex-wrap">
                                    <div><span class="badge bg-success">●</span> Excelente (≥3.5): {{ $estadisticas['excelentes'] }}</div>
                                    <div><span class="badge bg-primary">●</span> Bueno (2.5-3.4): {{ $estadisticas['buenos'] }}</div>
                                    <div><span class="badge bg-warning">●</span> Regular (2.0-2.4): {{ $estadisticas['regulares'] }}</div>
                                    <div><span class="badge bg-danger">●</span> Bajo (<2.0): {{ $estadisticas['bajos'] }}</div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No hay datos para mostrar</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i> Distribución por Nivel
                        </h6>
                    </div>
                    <div class="card-body">
                        @if(isset($estadisticas['por_nivel']) && count($estadisticas['por_nivel']) > 0)
                            <div style="height: 350px;">
                                <canvas id="nivelesChart"></canvas>
                            </div>
                            <div class="mt-3 text-center">
                                <div class="d-flex justify-content-center gap-3 flex-wrap">
                                    @foreach($estadisticas['por_nivel'] as $nivel => $data)
                                        <div>
                                            <span class="badge bg-secondary">{{ $nivel }}</span>
                                            <span class="fw-bold">{{ $data['promedio'] }}</span>
                                            <small class="text-muted">({{ $data['total'] }} grados)</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No hay datos para mostrar</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparativa Académico vs Conducta -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar me-2"></i> Comparativa: Académico vs Conducta por Grado
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($grados->count() > 0)
                            <div style="height: 400px;">
                                <canvas id="comparativaChart"></canvas>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No hay datos para mostrar</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Grados -->
        <div class="card shadow">
            <div class="card-header py-3 bg-light d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table me-2"></i> Detalle de Rendimiento por Grado
                </h6>
                <div>
                    <span class="badge bg-primary">{{ $grados->count() }} grados</span>
                    <span class="badge bg-success">{{ $estadisticas['total_estudiantes'] }} estudiantes</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr class="text-center">
                                <th>Grado</th>
                                <th>Nivel</th>
                                <th>Estudiantes</th>
                                <th>Materias</th>
                                <th>Prom. Académico</th>
                                <th>Prom. Conducta</th>
                                <th>Asistencia (P/F/T)</th>
                                <th>Prom. General</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grados as $grado)
                            @php
                                $promNotas = $grado->promedio_notas ?? 0;
                                $promConducta = $grado->promedio_conducta ?? 0;
                                $promGeneral = $grado->promedio_general ?? 0;
                                $totalAsistencias = $grado->total_asistencias_raw ?? 0;
                                $puntualidad = $grado->total_puntualidad_raw ?? 0;
                                $falta = $grado->total_falta_raw ?? 0;
                                $tardanza = $grado->total_tardanza_raw ?? 0;
                                $porcentajeAsistencia = $totalAsistencias > 0 ? round(($puntualidad / $totalAsistencias) * 100, 2) : 0;
                                $colorNotas = $promNotas >= 3.0 ? 'success' : ($promNotas >= 2.5 ? 'primary' : ($promNotas >= 2.0 ? 'warning' : 'danger'));
                                $colorGeneral = $promGeneral >= 3.0 ? 'success' : ($promGeneral >= 2.5 ? 'primary' : ($promGeneral >= 2.0 ? 'warning' : 'danger'));
                                $colorAsistencia = $porcentajeAsistencia >= 90 ? 'success' : ($porcentajeAsistencia >= 75 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="fw-bold">
                                    {{ $grado->nombreCompleto ?? $grado->grado . '° ' . ($grado->seccion ?? '') }}
                                 </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $grado->nivel }}</span>
                                 </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $grado->estudiantes_matriculados ?? 0 }}</span>
                                 </td>
                                <td class="text-center">
                                    <span class="badge bg-dark">{{ $grado->total_materias ?? 0 }}</span>
                                 </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="progress me-2" style="width: 60px; height: 6px;">
                                            <div class="progress-bar bg-{{ $colorNotas }}"
                                                 style="width: {{ ($promNotas / 4) * 100 }}%"></div>
                                        </div>
                                        <span class="fw-bold text-{{ $colorNotas }}">{{ number_format($promNotas, 2) }}</span>
                                    </div>
                                 </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="progress me-2" style="width: 60px; height: 6px;">
                                            <div class="progress-bar bg-info"
                                                 style="width: {{ ($promConducta / 4) * 100 }}%"></div>
                                        </div>
                                        <span class="fw-bold">{{ number_format($promConducta, 2) }}</span>
                                    </div>
                                 </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="progress me-2" style="width: 60px; height: 6px;">
                                            <div class="progress-bar bg-{{ $colorAsistencia }}"
                                                 style="width: {{ $porcentajeAsistencia }}%"></div>
                                        </div>
                                        <span class="fw-bold text-{{ $colorAsistencia }}">{{ number_format($totalAsistencias) }}</span>
                                    </div>
                                    <br>
                                    <small class="text-muted">
                                        <span class="text-success">P:{{ number_format($puntualidad) }}</span> |
                                        <span class="text-danger">F:{{ number_format($falta) }}</span> |
                                        <span class="text-warning">T:{{ number_format($tardanza) }}</span>
                                    </small>
                                 </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $colorGeneral }} p-2">
                                        {{ number_format($promGeneral, 2) }}
                                    </span>
                                 </td>
                                <td class="text-center">
                                    @if($promGeneral >= 3.0)
                                        <span class="badge bg-success"><i class="fas fa-trophy me-1"></i>Excelente</span>
                                    @elseif($promGeneral >= 2.5)
                                        <span class="badge bg-primary"><i class="fas fa-medal me-1"></i>Bueno</span>
                                    @elseif($promGeneral >= 2.0)
                                        <span class="badge bg-warning"><i class="fas fa-certificate me-1"></i>Regular</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Crítico</span>
                                    @endif
                                 </td>
                             </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">Totales / Promedios:</td>
                                <td class="text-center">{{ number_format($estadisticas['total_estudiantes']) }}</td>
                                <td class="text-center">{{ number_format($estadisticas['total_materias']) }}</td>
                                <td class="text-center">
                                    {{ number_format($estadisticas['promedio_academico'], 2) }}
                                 </td>
                                <td class="text-center">{{ number_format($estadisticas['promedio_conducta'], 2) }}</td>
                                <td class="text-center">
                                    <span class="fw-bold">{{ number_format($estadisticas['total_registros_asistencia']) }}</span>
                                    <br>
                                    <small class="text-muted">
                                        <span class="text-success">P:{{ number_format($estadisticas['total_puntualidad']) }}</span> |
                                        <span class="text-danger">F:{{ number_format($estadisticas['total_falta']) }}</span> |
                                        <span class="text-warning">T:{{ number_format($estadisticas['total_tardanza']) }}</span>
                                    </small>
                                 </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $estadisticas['promedio_general'] >= 3.0 ? 'success' : ($estadisticas['promedio_general'] >= 2.5 ? 'primary' : ($estadisticas['promedio_general'] >= 2.0 ? 'warning' : 'danger')) }} p-2">
                                        {{ number_format($estadisticas['promedio_general'], 2) }}
                                    </span>
                                 </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @else
        <!-- Mensaje cuando no hay periodo seleccionado -->
        <div class="text-center py-5">
            <div class="card shadow-sm mx-auto" style="max-width: 500px;">
                <div class="card-body p-5">
                    <i class="fas fa-calendar-alt fa-4x text-muted mb-3"></i>
                    <h5>Seleccione un periodo académico</h5>
                    <p class="text-muted">Por favor, seleccione un periodo para visualizar las estadísticas institucionales.</p>
                    <div class="input-group mt-3" style="max-width: 300px; margin: 0 auto;">
                        <select name="periodo_id" class="form-select" onchange="window.location.href=this.value ? '{{ request()->url() }}?periodo_id=' + this.value : '{{ request()->url() }}'">
                            <option value="">-- Seleccione --</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }} ({{ $periodo->anio }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if($periodoSeleccionado && $grados->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const grados = @json($grados->values());
        const estadisticas = @json($estadisticas);

        // Gráfico de rendimiento por grado (barras)
        const rendimientoCtx = document.getElementById('rendimientoGradosChart');
        if (rendimientoCtx) {
            const labels = grados.map(g => g.nombreCompleto || (g.grado + '° ' + (g.seccion || '')));
            const promedios = grados.map(g => g.promedio_general || 0);
            const colores = promedios.map(p => p >= 3.0 ? '#28a745' : (p >= 2.5 ? '#4e73df' : (p >= 2.0 ? '#f6c23e' : '#e74a3b')));

            new Chart(rendimientoCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Promedio General',
                        data: promedios,
                        backgroundColor: colores,
                        borderRadius: 4,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: { callbacks: { label: ctx => `Promedio: ${ctx.parsed.y.toFixed(2)}` } },
                        legend: { display: false }
                    },
                    scales: {
                        y: { min: 0, max: 4, title: { display: true, text: 'Notas (1-4)' }, ticks: { stepSize: 0.5 } },
                        x: { ticks: { autoSkip: false, rotation: 15 } }
                    }
                }
            });
        }

        // Gráfico de asistencia por grado (barras - total de asistencias)
        const asistenciaCtx = document.getElementById('asistenciaGradosChart');
        if (asistenciaCtx) {
            const labels = grados.map(g => g.nombreCompleto || (g.grado + '° ' + (g.seccion || '')));
            const puntualidad = grados.map(g => g.total_puntualidad_raw || 0);
            const faltas = grados.map(g => g.total_falta_raw || 0);
            const tardanzas = grados.map(g => g.total_tardanza_raw || 0);

            new Chart(asistenciaCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Puntualidad', data: puntualidad, backgroundColor: '#28a745', borderRadius: 4 },
                        { label: 'Faltas', data: faltas, backgroundColor: '#dc3545', borderRadius: 4 },
                        { label: 'Tardanzas', data: tardanzas, backgroundColor: '#ffc107', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} registros` } } },
                    scales: { y: { title: { display: true, text: 'Número de Registros' } }, x: { ticks: { autoSkip: false, rotation: 15 } } }
                }
            });
        }

        // Gráfico de categorías académicas (pastel)
        const categoriasCtx = document.getElementById('categoriasChart');
        if (categoriasCtx) {
            new Chart(categoriasCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excelente (≥3.5)', 'Bueno (2.5-3.4)', 'Regular (2.0-2.4)', 'Bajo (<2.0)'],
                    datasets: [{
                        data: [estadisticas.excelentes, estadisticas.buenos, estadisticas.regulares, estadisticas.bajos],
                        backgroundColor: ['#28a745', '#4e73df', '#f6c23e', '#e74a3b'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed} grados (${((ctx.parsed / estadisticas.total_grados) * 100).toFixed(1)}%)` } }
                    }
                }
            });
        }

        // Gráfico de distribución por nivel
        const nivelesCtx = document.getElementById('nivelesChart');
        if (nivelesCtx && estadisticas.por_nivel) {
            const niveles = Object.keys(estadisticas.por_nivel);
            const promediosNiveles = niveles.map(n => estadisticas.por_nivel[n].promedio);
            const coloresNiveles = promediosNiveles.map(p => p >= 3.0 ? '#28a745' : (p >= 2.5 ? '#4e73df' : (p >= 2.0 ? '#f6c23e' : '#e74a3b')));

            new Chart(nivelesCtx, {
                type: 'bar',
                data: {
                    labels: niveles,
                    datasets: [{
                        label: 'Promedio General por Nivel',
                        data: promediosNiveles,
                        backgroundColor: coloresNiveles,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { tooltip: { callbacks: { label: ctx => `Promedio: ${ctx.parsed.y.toFixed(2)}` } } },
                    scales: { y: { min: 0, max: 4, title: { display: true, text: 'Notas (1-4)' }, ticks: { stepSize: 0.5 } } }
                }
            });
        }

        // Gráfico comparativo académico vs conducta
        const comparativaCtx = document.getElementById('comparativaChart');
        if (comparativaCtx) {
            const labels = grados.map(g => g.nombreCompleto || (g.grado + '° ' + (g.seccion || '')));
            const academicos = grados.map(g => g.promedio_notas || 0);
            const conductas = grados.map(g => g.promedio_conducta || 0);

            new Chart(comparativaCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Promedio Académico', data: academicos, backgroundColor: '#4e73df', borderRadius: 4 },
                        { label: 'Promedio Conducta', data: conductas, backgroundColor: '#f6c23e', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)}` } }, legend: { position: 'top' } },
                    scales: {
                        y: { min: 0, max: 4, title: { display: true, text: 'Notas (1-4)' }, ticks: { stepSize: 0.5 } },
                        x: { ticks: { autoSkip: false, rotation: 15 } }
                    }
                }
            });
        }
    });
</script>
@endif
@endsection
