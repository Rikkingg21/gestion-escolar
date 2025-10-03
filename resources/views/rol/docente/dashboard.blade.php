@extends('layouts.app')

@section('content')
    <h1>Dashboard Docente</h1>

    @if(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    @if(empty($datosGraficos))
        <div class="alert alert-info">
            No hay datos disponibles para mostrar gráficos.
        </div>
    @else
        @foreach($datosGraficos as $grafico)
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Progreso de Estudiantes - {{ $grafico['grado'] }}</h3>
                </div>
                <div class="card-body">
                    @if(empty($grafico['labelsEstudiantes']))
                        <div class="alert alert-warning">
                            No hay estudiantes activos en este grado.
                        </div>
                    @else
                        <div style="height: 500px;">
                            <canvas id="graficoGrado{{ $loop->index }}"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datosGraficos = @json($datosGraficos);

            datosGraficos.forEach((grafico, index) => {
                // Solo crear gráfico si hay estudiantes
                if (grafico.labelsEstudiantes && grafico.labelsEstudiantes.length > 0) {
                    const ctx = document.getElementById('graficoGrado' + index).getContext('2d');

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: grafico.labelsEstudiantes,
                            datasets: grafico.datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Progreso de Estudiantes - ' + grafico.grado,
                                    font: {
                                        size: 16
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
                                    grid: {
                                        color: 'rgba(0,0,0,0.1)'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Estudiantes'
                                    },
                                    grid: {
                                        display: false
                                    },
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
                }
            });
        });
    </script>
@endsection
