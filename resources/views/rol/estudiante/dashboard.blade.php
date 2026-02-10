@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Encabezado y Filtros -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card-body">
                <h1 class="h3 mb-3">
                    <i class="fas fa-user-graduate"></i> Dashboard Estudiante
                </h1>
                <form method="GET" action="{{ request()->url() }}" class="row g-3">
                    <div class="col-md-5">
                        <select name="periodo_id" class="form-select" onchange="this.form.submit()">
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}"
                                    {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                    {{ $periodo->anio }}
                                    @if($periodo->semestre)
                                        - Semestre {{ $periodo->semestre }}
                                    @endif
                                    @if($periodo->estado == 1)
                                        <span class="text-success">(Activo)</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <select name="bimestre" class="form-select" onchange="this.form.submit()">
                            <option value="anual" {{ request('bimestre', 'anual') == 'anual' ? 'selected' : '' }}>Todos los Bimestres</option>
                            @for ($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ request('bimestre') == $i ? 'selected' : '' }}>
                                    {{ $i }}° Bimestre
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Información del estudiante -->
    <div class="card mb-4 shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user-graduate me-2"></i>
                    {{ $infoEstudiante['nombre_completo'] }} - {{ $infoEstudiante['grado'] }}
                </h4>
                @if($infoEstudiante['total_cursos'] > 0 || $infoEstudiante['total_conducta'] > 0)
                    <span class="badge bg-light text-primary fs-6">
                        {{ $infoEstudiante['total_cursos'] }} curso(s) /
                        {{ $infoEstudiante['total_conducta'] }} conducta(s)
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(isset($infoEstudiante['mensaje']))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ $infoEstudiante['mensaje'] }}
                </div>
            @elseif($infoEstudiante['total_cursos'] == 0 && $infoEstudiante['total_conducta'] == 0)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay notas registradas para este período.
                </div>
            @else
                <!-- Pestañas para Notas y Conducta -->
                <ul class="nav nav-tabs mb-4" id="estudianteTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="notas-tab"
                                data-bs-toggle="tab" data-bs-target="#notas"
                                type="button" role="tab">
                            <i class="fas fa-graduation-cap me-1"></i> Notas Académicas
                            @if($infoEstudiante['total_cursos'] > 0)
                                <span class="badge bg-primary ms-1">{{ $infoEstudiante['total_cursos'] }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="conducta-tab"
                                data-bs-toggle="tab" data-bs-target="#conducta"
                                type="button" role="tab">
                            <i class="fas fa-users me-1"></i> Conducta
                            @if($infoEstudiante['total_conducta'] > 0)
                                <span class="badge bg-success ms-1">{{ $infoEstudiante['total_conducta'] }}</span>
                            @endif
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="estudianteContent">
                    <!-- Pestaña de Notas Académicas -->
                    <div class="tab-pane fade show active" id="notas" role="tabpanel" aria-labelledby="notas-tab">
                        @if($infoEstudiante['total_cursos'] > 0)
                            <!-- Resumen estadístico de notas -->
                            <div class="row mb-4">
                                @php
                                    $todasNotas = [];
                                    foreach($infoEstudiante['progreso_cursos'] as $curso) {
                                        $notasValidas = array_filter($curso['promedios'], function($n) { return $n !== null; });
                                        $todasNotas = array_merge($todasNotas, $notasValidas);
                                    }
                                    $promedioGeneral = count($todasNotas) > 0 ?
                                        round(array_sum($todasNotas) / count($todasNotas), 2) : null;

                                    $cursosAprobados = 0;
                                    $cursosReprobados = 0;
                                    foreach($infoEstudiante['progreso_cursos'] as $curso) {
                                        if ($curso['promedio_general'] !== null) {
                                            if ($curso['promedio_general'] >= 2.5) {
                                                $cursosAprobados++;
                                            } else {
                                                $cursosReprobados++;
                                            }
                                        }
                                    }
                                @endphp

                                @if($promedioGeneral)
                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-success shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Promedio General
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $promedioGeneral }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-info shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Cursos con notas
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $infoEstudiante['total_cursos'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-success shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Cursos Aprobados
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $cursosAprobados }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-danger shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Cursos Reprobados
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $cursosReprobados }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de notas -->
                            <div class="mb-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-line me-2"></i> Progreso Académico
                                </h5>
                                <div style="height: 400px;">
                                    <canvas id="progresoChart"></canvas>
                                </div>
                            </div>

                            <!-- Tabla de notas -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Curso / Materia</th>
                                            <th class="text-center">Bimestre 1</th>
                                            <th class="text-center">Bimestre 2</th>
                                            <th class="text-center">Bimestre 3</th>
                                            <th class="text-center">Bimestre 4</th>
                                            <th class="text-center">Promedio</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($infoEstudiante['progreso_cursos'] as $curso)
                                        <tr>
                                            <td class="fw-bold">{{ $curso['curso'] }}</td>
                                            @foreach($curso['promedios'] as $bimestre => $promedio)
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
                                                @if($curso['promedio_general'] !== null)
                                                    <span class="badge
                                                        @if($curso['promedio_general'] >= 3.5) bg-success
                                                        @elseif($curso['promedio_general'] >= 2.5) bg-warning
                                                        @else bg-danger
                                                        @endif">
                                                        {{ $curso['promedio_general'] }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($curso['promedio_general'] !== null)
                                                    @if($curso['promedio_general'] >= 2.5)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Aprobado
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Reprobado
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">Sin datos</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay notas académicas registradas para este período.
                            </div>
                        @endif
                    </div>

                    <!-- Pestaña de Conducta -->
                    <div class="tab-pane fade" id="conducta" role="tabpanel" aria-labelledby="conducta-tab">
                        @if($infoEstudiante['total_conducta'] > 0)
                            <!-- Resumen estadístico de conducta -->
                            <div class="row mb-4">
                                @php
                                    $todasConductas = [];
                                    foreach($infoEstudiante['progreso_conducta'] as $conducta) {
                                        $conductasValidas = array_filter($conducta['promedios'], function($c) { return $c !== null; });
                                        $todasConductas = array_merge($todasConductas, $conductasValidas);
                                    }
                                    $promedioConductaGeneral = count($todasConductas) > 0 ?
                                        round(array_sum($todasConductas) / count($todasConductas), 2) : null;

                                    $conductasAdecuadas = 0;
                                    $conductasInadecuadas = 0;
                                    foreach($infoEstudiante['progreso_conducta'] as $conducta) {
                                        if ($conducta['promedio_general'] !== null) {
                                            if ($conducta['promedio_general'] >= 2.5) {
                                                $conductasAdecuadas++;
                                            } else {
                                                $conductasInadecuadas++;
                                            }
                                        }
                                    }
                                @endphp

                                @if($promedioConductaGeneral)
                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-primary shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Promedio Conducta
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $promedioConductaGeneral }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-info shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Áreas Evaluadas
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $infoEstudiante['total_conducta'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-success shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Conducta Adecuada
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $conductasAdecuadas }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-warning shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Conducta Inadecuada
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $conductasInadecuadas }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de conducta -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Competencia / Área</th>
                                            <th class="text-center">Bimestre 1</th>
                                            <th class="text-center">Bimestre 2</th>
                                            <th class="text-center">Bimestre 3</th>
                                            <th class="text-center">Bimestre 4</th>
                                            <th class="text-center">Promedio</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($infoEstudiante['progreso_conducta'] as $conducta)
                                        <tr>
                                            <td class="fw-bold">{{ $conducta['curso'] }}</td>
                                            @foreach($conducta['promedios'] as $bimestre => $promedio)
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
                                                @if($conducta['promedio_general'] !== null)
                                                    <span class="badge
                                                        @if($conducta['promedio_general'] >= 3.5) bg-success
                                                        @elseif($conducta['promedio_general'] >= 2.5) bg-warning
                                                        @else bg-danger
                                                        @endif">
                                                        {{ $conducta['promedio_general'] }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($conducta['promedio_general'] !== null)
                                                    @if($conducta['promedio_general'] >= 2.5)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Adecuada
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Inadecuada
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">Sin datos</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay notas de conducta registradas para este período.
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const infoEstudiante = @json($infoEstudiante);
        const labelsBimestres = @json($labelsBimestres);

        // Paleta de colores para los cursos
        const colores = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
            '#9966FF', '#FF9F40', '#8AC926', '#1982C4',
            '#6A4C93', '#F15BB5', '#00BBF9', '#00F5D4'
        ];

        // Gráfico de notas académicas
        if (infoEstudiante.progreso_cursos && infoEstudiante.progreso_cursos.length > 0) {
            const ctxNotas = document.getElementById('progresoChart');
            if (ctxNotas) {
                const datasetsNotas = infoEstudiante.progreso_cursos.map((curso, cursoIndex) => {
                    const color = colores[cursoIndex % colores.length];

                    return {
                        label: curso.curso,
                        data: [1,2,3,4].map(bimestre => {
                            const promedio = curso.promedios[bimestre];
                            return promedio !== null ? promedio : null;
                        }),
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

                new Chart(ctxNotas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labelsBimestres,
                        datasets: datasetsNotas
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Progreso Académico - ' + infoEstudiante.nombre_completo,
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
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
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
            }
        }

        // Gráfico de conducta (se crea cuando se hace clic en la pestaña)
        const conductaTab = document.getElementById('conducta-tab');
        if (conductaTab) {
            conductaTab.addEventListener('click', function() {
                if (infoEstudiante.progreso_conducta && infoEstudiante.progreso_conducta.length > 0) {
                    setTimeout(() => {
                        const conductaContent = document.getElementById('conducta');
                        if (conductaContent && !document.getElementById('conductaChart')) {
                            // Crear contenedor para el gráfico
                            const rowContainer = conductaContent.querySelector('.row.mb-4');
                            if (rowContainer) {
                                const chartContainer = document.createElement('div');
                                chartContainer.className = 'mb-4';
                                chartContainer.innerHTML = `
                                    <h5 class="mb-3">
                                        <i class="fas fa-chart-line me-2"></i> Progreso de Conducta
                                    </h5>
                                    <div style="height: 400px;">
                                        <canvas id="conductaChart"></canvas>
                                    </div>
                                `;
                                rowContainer.after(chartContainer);

                                // Crear el gráfico de conducta
                                const ctxConducta = document.getElementById('conductaChart');
                                if (ctxConducta) {
                                    const datasetsConducta = infoEstudiante.progreso_conducta.map((conducta, conductaIndex) => {
                                        const color = colores[conductaIndex % colores.length];

                                        return {
                                            label: conducta.curso,
                                            data: [1,2,3,4].map(bimestre => {
                                                const promedio = conducta.promedios[bimestre];
                                                return promedio !== null ? promedio : null;
                                            }),
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

                                    new Chart(ctxConducta.getContext('2d'), {
                                        type: 'line',
                                        data: {
                                            labels: labelsBimestres,
                                            datasets: datasetsConducta
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                title: {
                                                    display: true,
                                                    text: 'Progreso de Conducta - ' + infoEstudiante.nombre_completo,
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
                                                    intersect: false,
                                                    callbacks: {
                                                        label: function(context) {
                                                            return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
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
                                }
                            }
                        }
                    }, 100);
                }
            });
        }
    });
</script>
@endsection
