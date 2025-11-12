@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt"></i> Dashboard Director
        </h1>
        <div class="text-muted">
            Año Académico: {{ $estadisticas['anio'] }}
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
                                {{ $estadisticas['totalEstudiantes'] }}
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
                                Docentes Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['totalDocentes'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
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
                                Cursos Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['totalCursos'] }}
                            </div>
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
                                Notas Oficializadas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($estadisticas['notasPorBimestre'])->avg('porcentaje') }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico principal -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-chart-line"></i> Progreso Académico por Grado
            </h6>
        </div>
        <div class="card-body">
            @if(empty($progreso) || collect($progreso)->flatMap->promedios->filter()->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No hay datos suficientes de notas oficializadas para mostrar el gráfico.
                    <br><small>Los datos se mostrarán cuando las notas estén en estado "Oficial" o "Extra Oficial".</small>
                </div>
            @else
                <div style="height: 500px;">
                    <canvas id="progresoGradosChart"></canvas>
                </div>
            @endif
        </div>
    </div>

    <!-- Tabla de progreso por bimestre -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table"></i> Progreso Detallado por Bimestre
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Grado</th>
                            @foreach($labelsBimestres as $bimestre)
                            <th class="text-center">{{ $bimestre }}</th>
                            @endforeach
                            <th class="text-center">Promedio Anual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($progreso as $gradoData)
                        @php
                            $promediosValidos = array_filter($gradoData['promedios'], function($valor) {
                                return $valor !== null;
                            });
                            $promedioAnual = count($promediosValidos) > 0
                                ? round(array_sum($promediosValidos) / count($promediosValidos), 2)
                                : null;
                        @endphp
                        <tr>
                            <td><strong>{{ $gradoData['grado'] }}</strong></td>
                            @foreach($gradoData['promedios'] as $promedio)
                            <td class="text-center">
                                @if($promedio !== null)
                                    <span class="badge bg-{{ $promedio >= 3 ? 'success' : ($promedio >= 2 ? 'warning' : 'danger') }}">
                                        {{ $promedio }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            @endforeach
                            <td class="text-center">
                                @if($promedioAnual !== null)
                                    <span class="badge bg-{{ $promedioAnual >= 3 ? 'success' : ($promedioAnual >= 2 ? 'warning' : 'danger') }}">
                                        {{ $promedioAnual }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Estadísticas de notas por bimestre -->
    <div class="row">
        @foreach($estadisticas['notasPorBimestre'] as $bimestre => $datos)
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-{{ $datos['porcentaje'] >= 80 ? 'success' : ($datos['porcentaje'] >= 50 ? 'warning' : 'danger') }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #{{ $datos['porcentaje'] >= 80 ? '1cc88a' : ($datos['porcentaje'] >= 50 ? 'f6c23e' : 'e74a3b') }}">
                                Bimestre {{ $bimestre }} - Oficializadas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $datos['porcentaje'] }}%
                            </div>
                            <div class="text-xs text-muted">
                                {{ $datos['publicadas'] }} / {{ $datos['total'] }} notas
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-{{ $datos['porcentaje'] >= 80 ? 'check-circle' : ($datos['porcentaje'] >= 50 ? 'exclamation-circle' : 'times-circle') }} fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@if(!empty($progreso) && !collect($progreso)->flatMap->promedios->filter()->isEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = @json($labelsBimestres);
        const datosProgreso = @json($progreso);

        // Colores predefinidos para mejor consistencia
        const colores = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
            '#9966FF', '#FF9F40', '#8AC926', '#1982C4',
            '#6A4C93', '#F15BB5'
        ];

        const datasets = datosProgreso.map((grado, index) => {
            const color = colores[index % colores.length];

            return {
                label: grado.grado || 'Grado sin nombre',
                data: grado.promedios.map(promedio => promedio !== null ? promedio : null),
                fill: false,
                borderColor: color,
                backgroundColor: color + '80',
                tension: 0.3,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7,
                spanGaps: true, // Permitir líneas discontinuas para datos faltantes
                // Solo mostrar en legend si tiene datos
                hidden: grado.promedios.every(p => p === null || p === 0)
            };
        });

        // Crear el gráfico
        const ctx = document.getElementById('progresoGradosChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels || [],
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Progreso Académico por Grado - Año {{ $estadisticas["anio"] }} (Notas Oficializadas)',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'nearest',
                        intersect: false,
                        filter: function(tooltipItem) {
                            return tooltipItem.parsed.y !== null && tooltipItem.parsed.y !== 0;
                        },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toFixed(2);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        suggestedMin: 0,
                        suggestedMax: 4,
                        title: {
                            display: true,
                            text: 'Promedio de Notas'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Bimestres'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
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
</script>
@endif
@endsection
