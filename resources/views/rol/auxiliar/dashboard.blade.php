@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
        <h1 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check text-primary me-2"></i>Dashboard Auxiliar
        </h1>

        <div class="mt-3 mt-sm-0">
            <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">
                        <i class="bi bi-calendar4-week"></i>
                    </span>
                    <select name="periodo_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo->id }}"
                                {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                {{ $periodo->anio }}
                                {{ $periodo->semestre ? '- Semestre ' . $periodo->semestre : '' }}
                                {{ $periodo->estado == 1 ? ' (ACTIVO)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <select name="bimestre" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 120px;">
                        <option value="anual" {{ request('bimestre') == 'anual' ? 'selected' : '' }}>Anual</option>
                        @for ($i = 1; $i <= 4; $i++)
                            <option value="{{ $i }}" {{ request('bimestre') == $i ? 'selected' : '' }}>
                                {{ $i }}° Bimestre
                            </option>
                        @endfor
                    </select>
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
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-funnel-fill"></i> <span class="d-none d-md-inline">Filtrar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Estudiantes Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticasGenerales['totalEstudiantes'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Total Asistencias
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticasGenerales['totalAsistencias'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                                Grados Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($datosAsistencias) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
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
                                Tipos de Asistencia
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $tiposAsistencia->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(empty($datosAsistencias))
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            No hay grados activos con estudiantes para mostrar estadísticas de asistencia.
        </div>
    @else
        <!-- Acordeón de grados -->
        <div class="accordion" id="gradosAccordion">
            @foreach($datosAsistencias as $index => $gradoData)
            <div class="card shadow mb-3">
                <div class="card-header" id="heading{{ $index }}">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center text-decoration-none"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse{{ $index }}"
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                aria-controls="collapse{{ $index }}">
                            <div>
                                <i class="fas fa-graduation-cap mr-2"></i>
                                <strong>{{ $gradoData['grado'] }}</strong>
                                <span class="badge bg-primary ml-2">{{ $gradoData['estadisticas']['totalEstudiantes'] }} estudiantes</span>
                                <span class="badge bg-success ml-1">{{ $gradoData['estadisticas']['totalAsistencias'] }} asistencias</span>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-chevron-down"></i>
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
                                <div class="card shadow mb-3">
                                    <div class="card-header py-2" style="background-color: #f8f9fa; border-left: 4px solid #4e73df;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="m-0 font-weight-bold text-dark">
                                                    <i class="fas fa-chart-pie me-1"></i> Estadísticas de Asistencia - {{ $gradoData['grado'] }}
                                                </h6>
                                                <small class="text-muted">
                                                    Período: {{ $periodoSeleccionado->anio }}
                                                    @if($periodoSeleccionado->semestre)
                                                        - Semestre {{ $periodoSeleccionado->semestre }}
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-users"></i> {{ $gradoData['estadisticas']['totalEstudiantes'] }} estudiantes
                                                </span>
                                                <span class="badge bg-success ms-1">
                                                    <i class="fas fa-clipboard-check"></i> {{ $gradoData['estadisticas']['totalAsistencias'] }} registros
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        @if($gradoData['estadisticas']['totalAsistencias'] == 0)
                                        <div class="alert alert-warning mb-0">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
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
                                                $colorHex = $coloresTipos[$tipo->nombre]['hex'] ?? '#6c757d';
                                                $porcentaje = $gradoData['estadisticas']['porcentajesTipo'][$tipo->nombre];
                                                $conteo = array_sum(array_column($gradoData['estudiantes'], 'conteo_tipos.' . $tipo->nombre));
                                            @endphp

                                            <div class="col-xl-2 col-md-4 col-6 mb-3">
                                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                                                style="width: 40px; height: 40px; background-color: {{ $colorHex }}20;">
                                                                @if($tipo->nombre == 'PUNTUALIDAD')
                                                                    <i class="fas fa-check text-success" style="color: {{ $colorHex }}"></i>
                                                                @elseif($tipo->nombre == 'FALTA')
                                                                    <i class="fas fa-times text-danger" style="color: {{ $colorHex }}"></i>
                                                                @elseif($tipo->nombre == 'TARDANZA')
                                                                    <i class="fas fa-clock text-warning" style="color: {{ $colorHex }}"></i>
                                                                @else
                                                                    <i class="fas fa-chart-bar" style="color: {{ $colorHex }}"></i>
                                                                @endif
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="text-xs font-weight-bold text-uppercase mb-0"
                                                                    style="color: {{ $colorHex }}">
                                                                    {{ $tipo->nombre }}
                                                                </div>
                                                                <div class="h5 mb-0 font-weight-bold {{ $conteo > 0 ? 'text-gray-800' : 'text-muted' }}">
                                                                    {{ $porcentaje }}%
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mt-2">
                                                            <div class="d-flex justify-content-between small mb-1">
                                                                <span class="text-muted">Registros</span>
                                                                <span class="font-weight-bold">{{ $conteo }}</span>
                                                            </div>
                                                            @if($conteo > 0)
                                                            <div class="progress" style="height: 8px;">
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

                                                        @if($conteo > 0)
                                                        <div class="mt-2 text-center small">
                                                            <span class="text-muted">
                                                                {{ number_format($conteo / $gradoData['estadisticas']['totalAsistencias'] * 100, 1) }}% del total
                                                            </span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Resumen general -->
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <div class="alert alert-light border">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="text-center">
                                                                <div class="text-muted small">Asistencia Total</div>
                                                                @php
                                                                    $asistenciaPositiva = 0;
                                                                    if(isset($gradoData['estadisticas']['porcentajesTipo']['PUNTUALIDAD'])) {
                                                                        $asistenciaPositiva = $gradoData['estadisticas']['porcentajesTipo']['PUNTUALIDAD'];
                                                                    }
                                                                @endphp
                                                                <div class="h4 font-weight-bold {{ $asistenciaPositiva > 80 ? 'text-success' : ($asistenciaPositiva > 60 ? 'text-warning' : 'text-danger') }}">
                                                                    {{ $asistenciaPositiva }}%
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="text-center">
                                                                <div class="text-muted small">Inasistencias</div>
                                                                @php
                                                                    $inasistencias = 0;
                                                                    if(isset($gradoData['estadisticas']['porcentajesTipo']['FALTA'])) {
                                                                        $inasistencias = $gradoData['estadisticas']['porcentajesTipo']['FALTA'];
                                                                    }
                                                                @endphp
                                                                <div class="h4 font-weight-bold {{ $inasistencias < 10 ? 'text-success' : ($inasistencias < 20 ? 'text-warning' : 'text-danger') }}">
                                                                    {{ $inasistencias }}%
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="text-center">
                                                                <div class="text-muted small">Promedio De Registros Estudiante</div>
                                                                @php
                                                                    $promedioPorEstudiante = $gradoData['estadisticas']['totalEstudiantes'] > 0
                                                                        ? round($gradoData['estadisticas']['totalAsistencias'] / $gradoData['estadisticas']['totalEstudiantes'], 1)
                                                                        : 0;
                                                                @endphp
                                                                <div class="h4 font-weight-bold text-info">{{ $promedioPorEstudiante }}</div>
                                                            </div>
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
                                <div class="card">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-chart-bar"></i> Distribución por Estudiante - {{ $gradoData['grado'] }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div style="height: 400px;">
                                            <canvas id="asistenciaChart{{ $index }}"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla detallada -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-table"></i> Detalle por Estudiante
                                        </h6>
                                        <span class="badge bg-info">{{ count($gradoData['estudiantes']) }} estudiantes</span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 400px;">
                                            <table class="table table-sm table-bordered table-striped mb-0">
                                                <thead class="table-dark sticky-top">
                                                    <tr>
                                                        <th class="bg-dark text-white">Estudiante</th>
                                                        <th class="bg-dark text-white text-center">Total</th>
                                                        @foreach($tiposAsistencia as $tipo)
                                                        @php
                                                            $colorHex = $coloresTipos[$tipo->nombre]['hex'] ?? '#6c757d';
                                                        @endphp
                                                        <th class="bg-dark text-white text-center" style="background-color: {{ $colorHex }} !important;">
                                                            {{ $tipo->nombre }}
                                                        </th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($gradoData['estudiantes'] as $estudiante)
                                                    <tr>
                                                        <td class="font-weight-bold">{{ $estudiante['nombre_completo'] }}</td>
                                                        <td class="text-center bg-light font-weight-bold">
                                                            {{ $estudiante['total_asistencias'] }}
                                                        </td>
                                                        @foreach($tiposAsistencia as $tipo)
                                                        @php
                                                            $colorHex = $coloresTipos[$tipo->nombre]['hex'] ?? '#6c757d';
                                                        @endphp
                                                        <td class="text-center" style="background-color: {{ $colorHex }}20;">
                                                            {{ $estudiante['porcentajes_tipo'][$tipo->nombre] }}%
                                                            <br>
                                                            <small class="text-muted">({{ $estudiante['conteo_tipos'][$tipo->nombre] }})</small>
                                                        </td>
                                                        @endforeach
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

        // Paleta de colores para tipos de asistencia
        const colores = {
            'PUNTUALIDAD': '#28a745',
            'FALTA': '#dc3545',
            'FALTA JUSTIFICADA': '#fd7e14',
            'TARDANZA': '#ffc107',
            'TARDANZA JUSTIFICADA': '#17a2b8',
        };

        // Funciones para ajustar colores
        function lightenColor(color, percent) {
            const num = parseInt(color.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = Math.min(255, (num >> 16) + amt);
            const G = Math.min(255, (num >> 8 & 0x00FF) + amt);
            const B = Math.min(255, (num & 0x0000FF) + amt);
            return "#" + (
                0x1000000 +
                (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)
            ).toString(16).slice(1);
        }

        datosAsistencias.forEach((gradoData, index) => {
            const ctx = document.getElementById('asistenciaChart' + index);
            if (!ctx) return;

            const labels = gradoData.estudiantes.map(e => {
                // Acortar nombres largos para mejor visualización
                const names = e.nombre_completo.split(' ');
                return names.length > 3 ? names[0] + ' ' + names[1] + '...' : e.nombre_completo;
            });

            const datasets = tiposAsistencia.map(tipo => {
                const colorBase = colores[tipo.nombre] || '#6c757d';
                return {
                    label: tipo.nombre,
                    data: gradoData.estudiantes.map(estudiante =>
                        estudiante.porcentajes_tipo[tipo.nombre] || 0
                    ),
                    backgroundColor: colorBase,
                    borderColor: colorBase,
                    borderWidth: 1,
                    hoverBackgroundColor: lightenColor(colorBase, 20),
                };
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribución de Asistencias por Estudiante',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Porcentaje (%)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            stacked: true,
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        });
    });
</script>
@endif
@endsection
