@extends('layouts.app')
@section('title', 'Panel del Auxiliar')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
        <h1 class="h4 mb-0 text-body">
            <i class="bi bi-clipboard-check text-primary me-2"></i>Dashboard Auxiliar
        </h1>

        <div class="mt-3 mt-sm-0">
            <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-group" style="width: auto;">
                    <span class="input-group-text bg-white text-muted">
                        <i class="bi bi-calendar4-week"></i>
                    </span>
                    <select name="periodo_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 180px;">
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo->id }}"
                                {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                {{ $periodo->nombre }}
                                @if($periodo->estado == 1) (ACTIVO) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                @php
                    $bimestresDisponibles = \App\Models\Periodobimestre::where('periodo_id', $periodoSeleccionado?->id)
                        ->where('tipo_bimestre', 'A')
                        ->orderBy('bimestre')
                        ->get();
                @endphp

                @if($bimestresDisponibles->count() > 0)
                <div class="input-group" style="width: auto;">
                    <span class="input-group-text bg-white text-muted">
                        <i class="bi bi-layers"></i>
                    </span>
                    <select name="periodobimestre_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 120px;">
                        <option value="">Todos los Bimestres</option>
                        @foreach($bimestresDisponibles as $bim)
                            <option value="{{ $bim->id }}" {{ request('periodobimestre_id') == $bim->id ? 'selected' : '' }}>
                                {{ $bim->sigla }} - {{ $bim->nombre ?? $bim->bimestre . '° Bimestre' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="input-group" style="width: auto;">
                    <span class="input-group-text bg-white text-muted">
                        <i class="bi bi-calendar-month"></i>
                    </span>
                    <select name="mes" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 120px;">
                        <option value="">Mes (Todos)</option>
                        @php
                            $meses = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                        @endphp
                        @foreach($meses as $num => $nombre)
                            <option value="{{ $num }}" {{ request('mes') == $num ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel-fill"></i> Filtrar
                </button>

                @if(request('periodobimestre_id') || request('mes'))
                <a href="{{ request()->url() }}?periodo_id={{ $periodoSeleccionado?->id }}" class="btn btn-secondary btn-sm px-3">
                    <i class="bi bi-eraser-fill"></i> Limpiar
                </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Estudiantes Activos
                            </div>
                            <div class="h5 mb-0 fw-bold text-body">
                                {{ $estadisticasGenerales['totalEstudiantes'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fs-1 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Total Asistencias
                            </div>
                            <div class="h5 mb-0 fw-bold text-body">
                                {{ $estadisticasGenerales['totalAsistencias'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clipboard-data fs-1 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Grados Activos
                            </div>
                            <div class="h5 mb-0 fw-bold text-body">
                                {{ count($datosAsistencias) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-mortarboard fs-1 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Tipos de Asistencia
                            </div>
                            <div class="h5 mb-0 fw-bold text-body">
                                {{ $tiposAsistencia->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-list-ul fs-1 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(empty($datosAsistencias))
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No hay grados activos con estudiantes para mostrar estadísticas de asistencia.
        </div>
    @else
        <div class="accordion" id="gradosAccordion">
            @foreach($datosAsistencias as $index => $gradoData)
            <div class="card shadow mb-3">
                <div class="card-header bg-white" id="heading{{ $index }}">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center text-decoration-none w-100"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse{{ $index }}"
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                aria-controls="collapse{{ $index }}">
                            <div>
                                <i class="bi bi-mortarboard me-2"></i>
                                <strong>{{ $gradoData['grado'] }}</strong>
                                <span class="badge bg-primary ms-2">{{ $gradoData['estadisticas']['totalEstudiantes'] }} estudiantes</span>
                                <span class="badge bg-success ms-1">{{ $gradoData['estadisticas']['totalAsistencias'] }} asistencias</span>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </button>
                    </h2>
                </div>

                <div id="collapse{{ $index }}"
                     class="collapse {{ $loop->first ? 'show' : '' }}"
                     aria-labelledby="heading{{ $index }}"
                     data-bs-parent="#gradosAccordion">
                    <div class="card-body">
                        <!-- Estadísticas del grado -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header py-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="m-0 fw-bold text-dark">
                                                    <i class="bi bi-pie-chart me-1"></i> Estadísticas de Asistencia - {{ $gradoData['grado'] }}
                                                </h6>
                                                <small class="text-muted">
                                                    Período: {{ $periodoSeleccionado->nombre }}
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-people"></i> {{ $gradoData['estadisticas']['totalEstudiantes'] }} estudiantes
                                                </span>
                                                <span class="badge bg-success ms-1">
                                                    <i class="bi bi-clipboard-check"></i> {{ $gradoData['estadisticas']['totalAsistencias'] }} registros
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        @if($gradoData['estadisticas']['totalAsistencias'] == 0)
                                        <div class="alert alert-warning mb-0">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-exclamation-triangle fs-1 me-3 text-warning"></i>
                                                <div>
                                                    <h6 class="alert-heading mb-1">No hay registros de asistencia</h6>
                                                    <p class="mb-0">No se encontraron registros de asistencia para este grado en el período seleccionado.</p>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                        <div class="row">
                                            @foreach($tiposAsistencia as $tipo)
                                            @php
                                                $colorHex = $tipo->color_hex ?? '#6c757d';
                                                $porcentaje = $gradoData['estadisticas']['porcentajesTipo'][$tipo->nombre] ?? 0;
                                                $conteo = array_sum(array_column($gradoData['estudiantes'], 'conteo_tipos.' . $tipo->nombre));
                                            @endphp
                                            <div class="col-xl-2 col-md-4 col-6 mb-3">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                                                style="width: 40px; height: 40px; background-color: {{ $colorHex }}20;">
                                                                @if($tipo->nombre == 'PUNTUALIDAD')
                                                                    <i class="bi bi-check text-success" style="color: {{ $colorHex }}"></i>
                                                                @elseif($tipo->nombre == 'FALTA')
                                                                    <i class="bi bi-x text-danger" style="color: {{ $colorHex }}"></i>
                                                                @elseif($tipo->nombre == 'TARDANZA')
                                                                    <i class="bi bi-clock text-warning" style="color: {{ $colorHex }}"></i>
                                                                @else
                                                                    <i class="bi bi-bar-chart" style="color: {{ $colorHex }}"></i>
                                                                @endif
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="text-xs fw-bold text-uppercase mb-0"
                                                                    style="color: {{ $colorHex }}">
                                                                    {{ $tipo->nombre }}
                                                                </div>
                                                                <div class="h5 mb-0 fw-bold {{ $conteo > 0 ? 'text-body' : 'text-muted' }}">
                                                                    {{ $porcentaje }}%
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mt-2">
                                                            <div class="d-flex justify-content-between small mb-1">
                                                                <span class="text-muted">Registros</span>
                                                                <span class="fw-bold">{{ $conteo }}</span>
                                                            </div>
                                                            @if($conteo > 0)
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar"
                                                                    role="progressbar"
                                                                    style="width: {{ $porcentaje }}%; background-color: {{ $colorHex }}"
                                                                    aria-valuenow="{{ $porcentaje }}"
                                                                    aria-valuemin="0"
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Resumen general -->
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <div class="alert alert-light border">
                                                    <div class="row text-center">
                                                        <div class="col-md-4">
                                                            <div class="text-muted small">Asistencia Total</div>
                                                            @php
                                                                $asistenciaPositiva = $gradoData['estadisticas']['porcentajesTipo']['PUNTUALIDAD'] ?? 0;
                                                            @endphp
                                                            <div class="h4 fw-bold {{ $asistenciaPositiva > 80 ? 'text-success' : ($asistenciaPositiva > 60 ? 'text-warning' : 'text-danger') }}">
                                                                {{ $asistenciaPositiva }}%
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="text-muted small">Inasistencias</div>
                                                            @php
                                                                $inasistencias = $gradoData['estadisticas']['porcentajesTipo']['FALTA'] ?? 0;
                                                            @endphp
                                                            <div class="h4 fw-bold {{ $inasistencias < 10 ? 'text-success' : ($inasistencias < 20 ? 'text-warning' : 'text-danger') }}">
                                                                {{ $inasistencias }}%
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="text-muted small">Promedio Registros/Estudiante</div>
                                                            @php
                                                                $promedioPorEstudiante = $gradoData['estadisticas']['totalEstudiantes'] > 0
                                                                    ? round($gradoData['estadisticas']['totalAsistencias'] / $gradoData['estadisticas']['totalEstudiantes'], 1)
                                                                    : 0;
                                                            @endphp
                                                            <div class="h4 fw-bold text-info">{{ $promedioPorEstudiante }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de barras apiladas -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header py-3 bg-light">
                                        <h6 class="m-0 fw-bold text-primary">
                                            <i class="bi bi-bar-chart me-2"></i> Distribución por Estudiante - {{ $gradoData['grado'] }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        @if($gradoData['estadisticas']['totalAsistencias'] > 0)
                                        <div style="height: 400px;">
                                            <canvas id="asistenciaChart{{ $index }}"></canvas>
                                        </div>
                                        @else
                                        <div class="text-center py-5">
                                            <i class="bi bi-bar-chart fs-1 text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No hay datos suficientes para generar el gráfico</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla detallada -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header py-3 bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 fw-bold text-primary">
                                            <i class="bi bi-table me-2"></i> Detalle por Estudiante
                                        </h6>
                                        <span class="badge bg-info">{{ count($gradoData['estudiantes']) }} estudiantes</span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 500px;">
                                            <table class="table table-sm table-bordered table-striped mb-0">
                                                <thead class="table-dark position-sticky top-0">
                                                    <tr class="text-center">
                                                        <th class="bg-dark text-white">Estudiante</th>
                                                        <th class="bg-dark text-white">Total</th>
                                                        @foreach($tiposAsistencia as $tipo)
                                                        <th class="bg-dark text-white">{{ $tipo->nombre }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($gradoData['estudiantes'] as $estudiante)
                                                    <tr class="text-center">
                                                        <td class="fw-bold text-start">{{ $estudiante['nombre_completo'] }}</td>
                                                        <td class="bg-light fw-bold">{{ $estudiante['total_asistencias'] }}</td>
                                                        @foreach($tiposAsistencia as $tipo)
                                                        <td>
                                                            <span class="fw-bold">{{ $estudiante['porcentajes_tipo'][$tipo->nombre] ?? 0 }}%</span>
                                                            <br>
                                                            <small class="text-muted">({{ $estudiante['conteo_tipos'][$tipo->nombre] ?? 0 }})</small>
                                                        </td>
                                                        @endforeach
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light fw-bold">
                                                    <tr class="text-center">
                                                        <td>Totales</td>
                                                        <td>{{ $gradoData['estadisticas']['totalAsistencias'] }}</td>
                                                        @foreach($tiposAsistencia as $tipo)
                                                        <td>
                                                            {{ round($gradoData['estadisticas']['porcentajesTipo'][$tipo->nombre] ?? 0, 1) }}%
                                                            <br>
                                                            <small class="text-muted">({{ array_sum(array_column($gradoData['estudiantes'], 'conteo_tipos.' . $tipo->nombre)) }})</small>
                                                        </td>
                                                        @endforeach
                                                    </table>
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
            @endforeach
        </div>
    @endif
</div>

@if(!empty($datosAsistencias))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const datosAsistencias = @json($datosAsistencias);
        const tiposAsistencia = @json($tiposAsistencia);

        function lightenColor(color, percent) {
            if (!color || color === '#6c757d') return '#6c757d';
            const num = parseInt(color.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = Math.min(255, (num >> 16) + amt);
            const G = Math.min(255, (num >> 8 & 0x00FF) + amt);
            const B = Math.min(255, (num & 0x0000FF) + amt);
            return "#" + (0x1000000 + (R << 16) + (G << 8) + B).toString(16).slice(1);
        }

        datosAsistencias.forEach((gradoData, index) => {
            const canvas = document.getElementById('asistenciaChart' + index);
            if (!canvas) return;

            if (gradoData.estudiantes.length === 0 || gradoData.estadisticas.totalAsistencias === 0) return;

            const labels = gradoData.estudiantes.map(e => {
                const parts = e.nombre_completo.split(' ');
                return parts.length > 3 ? parts[0] + ' ' + parts[1] : e.nombre_completo;
            });

            const datasets = tiposAsistencia
                .filter(tipo => {
                    const total = gradoData.estudiantes.reduce((sum, est) => sum + (est.conteo_tipos[tipo.nombre] || 0), 0);
                    return total > 0;
                })
                .map(tipo => {
                    const colorBase = tipo.color_hex || '#6c757d';
                    return {
                        label: tipo.nombre,
                        data: gradoData.estudiantes.map(est => est.porcentajes_tipo[tipo.nombre] || 0),
                        backgroundColor: colorBase,
                        borderColor: colorBase,
                        borderWidth: 1,
                        hoverBackgroundColor: lightenColor(colorBase, 20),
                        stack: 'asistencia'
                    };
                });

            if (datasets.length === 0) return;

            new Chart(canvas, {
                type: 'bar',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            mode: 'nearest',
                            intersect: true,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y}%`;
                                }
                            }
                        },
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                    },
                    scales: {
                        x: { stacked: true, ticks: { maxRotation: 45, minRotation: 45 } },
                        y: { stacked: true, min: 0, max: 100, title: { display: true, text: 'Porcentaje (%)' }, ticks: { callback: v => v + '%' } }
                    }
                }
            });
        });
    });
</script>
@endif
@endsection
