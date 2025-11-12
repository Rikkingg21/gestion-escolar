@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt"></i> Dashboard Docente
        </h1>
        <div class="text-muted">
            Bienvenido/a, {{ $docente->user->nombre ?? 'Docente' }}
        </div>
    </div>

    @if(session('info'))
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> {{ session('info') }}
        </div>
    @endif

    <!-- Estadísticas rápidas del docente -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Cursos Asignados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticasGenerales['totalCursos'] }}
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Estudiantes Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ array_sum(array_column($datosGraficos, 'totalEstudiantes')) }}
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Progreso General
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        {{ $estadisticasGenerales['progresoGeneral'] }}%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar"
                                             style="width: {{ $estadisticasGenerales['progresoGeneral'] }}%"
                                             aria-valuenow="{{ $estadisticasGenerales['progresoGeneral'] }}"
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Notas Registradas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticasGenerales['notasRegistradas'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(empty($datosGraficos))
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            No hay datos disponibles para mostrar gráficos.
            @if($estadisticasGenerales['totalCursos'] > 0)
                <br><small>Comience a registrar calificaciones para ver el progreso de sus estudiantes.</small>
            @endif
        </div>
    @else
        <!-- Acordeón de grados -->
        <div class="accordion" id="gradosAccordion">
            @foreach($datosGraficos as $index => $grafico)
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
                                <strong>{{ $grafico['grado'] }}</strong>
                                <span class="badge bg-primary ml-2">{{ $grafico['totalEstudiantes'] }} estudiantes</span>
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
                        <!-- Información del grado -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="alert alert-light border">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Cursos:</strong>
                                            <span class="text-primary">{{ implode(', ', $grafico['cursos']) }}</span>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fas fa-info-circle"></i>
                                            Haga clic en un estudiante para ver su progreso detallado
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas del grado -->
                        <div class="row mb-4">
                            @foreach([1,2,3,4] as $bimestre)
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card border-left-{{ $estadisticasDocente[$grafico['grado']]['bimestres'][$bimestre]['completado'] >= 80 ? 'success' : ($estadisticasDocente[$grafico['grado']]['bimestres'][$bimestre]['completado'] >= 50 ? 'warning' : 'danger') }} shadow h-100">
                                    <div class="card-body py-3">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #{{ $estadisticasDocente[$grafico['grado']]['bimestres'][$bimestre]['completado'] >= 80 ? '1cc88a' : ($estadisticasDocente[$grafico['grado']]['bimestres'][$bimestre]['completado'] >= 50 ? 'f6c23e' : 'e74a3b') }}">
                                            Bimestre {{ $bimestre }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $estadisticasDocente[$grafico['grado']]['bimestres'][$bimestre]['completado'] }}%
                                        </div>
                                        <small class="text-muted">
                                            {{ $estadisticasDocente[$grafico['grado']]['bimestres'][$bimestre]['notasRegistradas'] }}/{{ $estadisticasDocente[$grafico['grado']]['bimestres'][$bimestre]['totalEsperado'] }} notas
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        @if(empty($grafico['estudiantes']))
                            <div class="alert alert-warning">
                                No hay datos de calificaciones para los estudiantes de este grado.
                            </div>
                        @else
                            <!-- Acordeón de estudiantes dentro del grado -->
                            <div class="accordion" id="estudiantesAccordion{{ $index }}">
                                <div class="row">
                                    @foreach($grafico['estudiantes'] as $estIndex => $estudianteData)
                                    <div class="col-xl-6 col-lg-6 mb-3">
                                        <div class="card">
                                            <div class="card-header py-2" id="estHeading{{ $index }}_{{ $estIndex }}">
                                                <h3 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center text-decoration-none p-0"
                                                            type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#estCollapse{{ $index }}_{{ $estIndex }}"
                                                            aria-expanded="false"
                                                            aria-controls="estCollapse{{ $index }}_{{ $estIndex }}">
                                                        <div class="d-flex align-items-center">
                                                            <div class="color-indicator mr-2" style="width: 12px; height: 12px; background-color: {{ $estudianteData['color'] }}; border-radius: 50%;"></div>
                                                            <span class="font-weight-bold text-dark">{{ $estudianteData['estudiante'] }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            @php
                                                                $datosFiltrados = array_filter($estudianteData['datos']);
                                                                $promedio = !empty($datosFiltrados) ? round(array_sum($datosFiltrados) / count($datosFiltrados), 2) : 'N/A';
                                                            @endphp
                                                            <span class="badge mr-2" style="background-color: {{ $estudianteData['color'] }}; color: white;">
                                                                {{ $promedio }}
                                                            </span>
                                                            <i class="fas fa-chevron-down text-muted"></i>
                                                        </div>
                                                    </button>
                                                </h3>
                                            </div>

                                            <div id="estCollapse{{ $index }}_{{ $estIndex }}"
                                                 class="collapse"
                                                 aria-labelledby="estHeading{{ $index }}_{{ $estIndex }}"
                                                 data-bs-parent="#estudiantesAccordion{{ $index }}">
                                                <div class="card-body p-3">
                                                    <div style="height: 250px;">
                                                        <canvas id="graficoEstudiante{{ $index }}_{{ $estIndex }}"></canvas>
                                                    </div>
                                                    <!-- Detalles adicionales -->
                                                    <div class="mt-3">
                                                        <div class="row text-center">
                                                            @foreach($estudianteData['datos'] as $bimestre => $nota)
                                                            <div class="col-3">
                                                                <small class="text-muted d-block">Bim {{ $bimestre + 1 }}</small>
                                                                <span class="badge bg-{{ $nota >= 3 ? 'success' : ($nota >= 2 ? 'warning' : 'danger') }}">
                                                                    {{ $nota ?? '-' }}
                                                                </span>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

@if(!empty($datosGraficos))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const datosGraficos = @json($datosGraficos);

        datosGraficos.forEach((grado, gradoIndex) => {
            if (grado.estudiantes && grado.estudiantes.length > 0) {
                grado.estudiantes.forEach((estudiante, estudianteIndex) => {
                    const ctx = document.getElementById(`graficoEstudiante${gradoIndex}_${estudianteIndex}`).getContext('2d');

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: estudiante.labels,
                            datasets: [{
                                label: 'Promedio',
                                data: estudiante.datos,
                                borderColor: estudiante.color,
                                backgroundColor: estudiante.color + '20',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: estudiante.color,
                                pointBorderColor: '#fff',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                spanGaps: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: function(context) {
                                            let label = 'Nota: ';
                                            if (context.parsed.y !== null) {
                                                label += context.parsed.y.toFixed(2);
                                            } else {
                                                label += 'Sin datos';
                                            }
                                            return label;
                                        },
                                        title: function(tooltipItems) {
                                            return 'Bimestre ' + (tooltipItems[0].dataIndex + 1);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    min: 1,
                                    max: 4,
                                    ticks: {
                                        stepSize: 1,
                                        callback: function(value) {
                                            return value;
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0,0,0,0.1)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Nota'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    title: {
                                        display: true,
                                        text: 'Bimestres'
                                    }
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            },
                            elements: {
                                line: {
                                    borderWidth: 2
                                }
                            }
                        }
                    });
                });
            }
        });

        // Auto-expandir el primer estudiante de cada grado
        datosGraficos.forEach((grado, gradoIndex) => {
            if (grado.estudiantes && grado.estudiantes.length > 0) {
                // Expandir el primer estudiante automáticamente
                const firstStudentCollapse = new bootstrap.Collapse(document.getElementById(`estCollapse${gradoIndex}_0`), {
                    toggle: false
                });
                firstStudentCollapse.show();
            }
        });
    });
</script>
@endif

<style>
.color-indicator {
    flex-shrink: 0;
}

.btn-link {
    color: #495057 !important;
    font-weight: 500;
}

.btn-link:hover {
    color: #007bff !important;
    text-decoration: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e3e6f0;
}

.accordion .card {
    border: 1px solid #e3e6f0;
}

.accordion .card-header h2 button {
    font-size: 1rem;
    padding: 1rem 1.25rem;
}

.accordion .card-header h3 button {
    font-size: 0.9rem;
    padding: 0.75rem 1rem;
}

.badge {
    font-size: 0.75rem;
}
</style>
@endsection
