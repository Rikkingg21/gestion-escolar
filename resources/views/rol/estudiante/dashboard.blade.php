@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <h1>Dashboard Estudiante</h1>

        <!-- Información del estudiante -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-graduate me-2"></i>
                    Información del Estudiante
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Nombre:</strong> {{ $infoEstudiante['nombre_completo'] }}
                    </div>
                    <div class="col-md-4">
                        <strong>Grado:</strong> {{ $infoEstudiante['grado'] }}
                    </div>
                    <div class="col-md-4">
                        <strong>Cursos con notas:</strong> {{ $infoEstudiante['total_cursos'] }}
                    </div>
                </div>
            </div>
        </div>

        @if(empty($progresoFinal))
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No hay notas registradas para el año actual.
            </div>
        @else
            <!-- Gráfico de progreso -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Progreso Académico por Curso
                    </h4>
                </div>
                <div class="card-body">
                    <div style="height: 500px;">
                        <canvas id="progresoCursosChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabla de detalles -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        Detalle de Notas por Bimestre
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Curso</th>
                                    <th class="text-center">Bimestre 1</th>
                                    <th class="text-center">Bimestre 2</th>
                                    <th class="text-center">Bimestre 3</th>
                                    <th class="text-center">Bimestre 4</th>
                                    <th class="text-center">Promedio General</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($progresoFinal as $curso)
                                    @php
                                        $notasValidas = array_filter($curso['promedios'], function($nota) {
                                            return $nota !== null;
                                        });
                                        $promedioGeneral = count($notasValidas) > 0 ?
                                            round(array_sum($notasValidas) / count($notasValidas), 2) : null;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $curso['curso'] }}</td>
                                        @foreach($curso['promedios'] as $promedio)
                                            <td class="text-center">
                                                @if($promedio !== null)
                                                    <span class="badge
                                                        @if($promedio >= 3.5) bg-success
                                                        @elseif($promedio >= 2.5) bg-warning
                                                        @else bg-danger
                                                        @endif">
                                                        {{ $promedio }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold">
                                            @if($promedioGeneral !== null)
                                                <span class="badge
                                                    @if($promedioGeneral >= 3.5) bg-success
                                                    @elseif($promedioGeneral >= 2.5) bg-warning
                                                    @else bg-danger
                                                    @endif">
                                                    {{ $promedioGeneral }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const progresoFinal = @json($progresoFinal);
            const labelsBimestres = @json($labelsBimestres);

            if (progresoFinal.length === 0) {
                return;
            }

            const ctx = document.getElementById('progresoCursosChart').getContext('2d');

            // Paleta de colores para los cursos
            const colores = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#8AC926', '#1982C4',
                '#6A4C93', '#F15BB5', '#00BBF9', '#00F5D4'
            ];

            const datasets = progresoFinal.map((curso, index) => {
                const color = colores[index % colores.length];

                return {
                    label: curso.curso,
                    data: curso.promedios.map(promedio => promedio !== null ? promedio : null),
                    borderColor: color,
                    backgroundColor: color + '40',
                    tension: 0.3,
                    fill: false,
                    pointBackgroundColor: color,
                    pointBorderColor: '#fff',
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    spanGaps: true // Conectar puntos incluso cuando hay valores null
                };
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labelsBimestres,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolución de Notas por Curso - Año ' + new Date().getFullYear(),
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#2C3E50',
                                font: {
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    return value !== null ? `${label}: ${value}` : `${label}: Sin nota`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 1,
                            max: 4,
                            title: {
                                display: true,
                                text: 'Notas (1-4)',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            },
                            ticks: {
                                stepSize: 0.5
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Bimestres',
                                font: {
                                    weight: 'bold'
                                }
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

    <style>
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        .table th {
            background-color: #34495e;
            color: white;
            border: none;
        }
        .badge {
            font-size: 0.85em;
            padding: 0.5em 0.75em;
            border-radius: 6px;
        }
        .table-responsive {
            border-radius: 6px;
            overflow: hidden;
        }
    </style>
@endsection
