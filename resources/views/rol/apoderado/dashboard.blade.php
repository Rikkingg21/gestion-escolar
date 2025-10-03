@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <h1>Dashboard Apoderado</h1>

        <!-- Información del apoderado -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-tie me-2"></i>
                    Información del Apoderado
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Nombre:</strong> {{ $infoApoderado['nombre_completo'] }}
                    </div>
                    <div class="col-md-4">
                        <strong>Parentesco:</strong> {{ $infoApoderado['parentesco'] }}
                    </div>
                    <div class="col-md-4">
                        <strong>Estudiantes a cargo:</strong> {{ $infoApoderado['total_estudiantes'] }}
                    </div>
                </div>
            </div>
        </div>

        @if(empty($datosEstudiantes))
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No hay estudiantes asignados o no tienen notas registradas.
            </div>
        @else
            @foreach($datosEstudiantes as $estudianteData)
                <!-- Tarjeta por cada estudiante -->
                <div class="card mb-5">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-graduate me-2"></i>
                            {{ $estudianteData['nombre_completo'] }} - {{ $estudianteData['grado'] }}
                        </h4>
                    </div>
                    <div class="card-body">
                        @if(empty($estudianteData['progreso_cursos']))
                            <div class="alert alert-warning">
                                No hay notas registradas para este estudiante en el año actual.
                            </div>
                        @else
                            <!-- Gráfico del estudiante -->
                            <div class="mb-4">
                                <h5>Progreso Académico</h5>
                                <div style="height: 400px;">
                                    <canvas id="progresoChart{{ $loop->index }}"></canvas>
                                </div>
                            </div>

                            <!-- Tabla de detalles del estudiante -->
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
                                        @foreach($estudianteData['progreso_cursos'] as $curso)
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
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datosEstudiantes = @json($datosEstudiantes);
            const labelsBimestres = @json($labelsBimestres);

            if (datosEstudiantes.length === 0) {
                return;
            }

            // Paleta de colores para los cursos
            const colores = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#8AC926', '#1982C4',
                '#6A4C93', '#F15BB5', '#00BBF9', '#00F5D4'
            ];

            // Crear gráfico para cada estudiante
            datosEstudiantes.forEach((estudiante, estudianteIndex) => {
                if (estudiante.progreso_cursos.length === 0) {
                    return;
                }

                const ctx = document.getElementById('progresoChart' + estudianteIndex);
                if (!ctx) return;

                const datasets = estudiante.progreso_cursos.map((curso, cursoIndex) => {
                    const color = colores[cursoIndex % colores.length];

                    return {
                        label: curso.curso,
                        data: curso.promedios.map(promedio => promedio !== null ? promedio : null),
                        borderColor: color,
                        backgroundColor: color + '40',
                        tension: 0.3,
                        fill: false,
                        pointBackgroundColor: color,
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        spanGaps: true
                    };
                });

                new Chart(ctx.getContext('2d'), {
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
                                text: 'Progreso de ' + estudiante.nombre_completo,
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
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
                                    stepSize: 0.5
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Bimestres'
                                }
                            }
                        }
                    }
                });
            });
        });
    </script>

    <style>
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .table th {
            background-color: #34495e;
            color: white;
        }
        .badge {
            font-size: 0.85em;
            padding: 0.5em 0.75em;
        }
    </style>
@endsection
