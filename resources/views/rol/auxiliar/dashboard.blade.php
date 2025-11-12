@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check"></i> Dashboard Auxiliar
        </h1>
        <div class="text-muted">
            Control de Asistencias - Año {{ date('Y') }}
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
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-light border">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Distribución de Asistencias del Grado:</strong>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fas fa-chart-pie"></i>
                                            Porcentajes generales
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @foreach($tiposAsistencia as $tipo)
                            @php
                                $colorHex = $coloresTipos[$tipo->nombre]['hex'] ?? '#6c757d';
                            @endphp
                            <div class="col-xl-2 col-md-4 col-6 mb-3">
                                <div class="card border-left-{{ $coloresTipos[$tipo->nombre]['class'] }} shadow h-100">
                                    <div class="card-body py-3">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: {{ $colorHex }}">
                                            {{ $tipo->nombre }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $gradoData['estadisticas']['porcentajesTipo'][$tipo->nombre] }}%
                                        </div>
                                        <small class="text-muted">
                                            {{ array_sum(array_column($gradoData['estudiantes'], 'conteo_tipos.' . $tipo->nombre)) }} registros
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
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

<style>
.accordion .card {
    border: 1px solid #e3e6f0;
}

.accordion .card-header h2 button {
    font-size: 1rem;
    padding: 1rem 1.25rem;
    color: #495057 !important;
    font-weight: 500;
}

.accordion .card-header h2 button:hover {
    color: #007bff !important;
    text-decoration: none;
}

.table th.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.badge {
    font-size: 0.75rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e3e6f0;
}
</style>
@endsection
