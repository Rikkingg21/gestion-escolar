@extends('layouts.app')

@section('content')
    <h1>Dashboard Auxiliar</h1>
    <h3>Porcentaje de Tipos de Asistencia por Estudiante</h3>

    @if(empty($datosAsistencias))
        <div class="alert alert-info">
            No hay grados activos con estudiantes.
        </div>
    @else
        @foreach($datosAsistencias as $gradoData)
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4>{{ $gradoData['grado'] }}</h4>
                </div>
                <div class="card-body">
                    <!-- Gráfico de barras apiladas -->
                    <div style="height: 600px;">
                        <canvas id="asistenciaChart{{ $loop->index }}"></canvas>
                    </div>

                    <!-- Tabla detallada -->
                    <div class="mt-4">
                        <h5>Detalle por Estudiante</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Total Asistencias</th>
                                        @foreach($tiposAsistencia as $tipo)
                                            <th>{{ $tipo->nombre }} (%)</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradoData['estudiantes'] as $estudiante)
                                        <tr>
                                            <td>{{ $estudiante['nombre_completo'] }}</td>
                                            <td class="text-center">{{ $estudiante['total_asistencias'] }}</td>
                                            @foreach($tiposAsistencia as $tipo)
                                                <td class="text-center">
                                                    {{ $estudiante['porcentajes_tipo'][$tipo->nombre] }}%
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
        @endforeach
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datosAsistencias = @json($datosAsistencias);
            const tiposAsistencia = @json($tiposAsistencia);

            // Nueva paleta de colores más armoniosa
            const colores = {
                'PUNTUALIDAD': '#2E8B57',      // Verde mar más suave
                'FALTA': '#DC143C',       // Rojo carmesí
                'FALTA JUSTIFICADA': '#FF8C00',      // Naranja oscuro
                'TARDANZA': '#1E90FF',   // Azul dodger
                'TARDANZA JUSTIFICADA': '#9370DB',      // Púrpura medio
            };

            // Funciones para ajustar colores (definirlas primero)
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

            function darkenColor(color, percent) {
                const num = parseInt(color.replace("#", ""), 16);
                const amt = Math.round(2.55 * percent);
                const R = Math.max(0, (num >> 16) - amt);
                const G = Math.max(0, (num >> 8 & 0x00FF) - amt);
                const B = Math.max(0, (num & 0x0000FF) - amt);
                return "#" + (
                    0x1000000 +
                    (R > 0 ? R : 0) * 0x10000 +
                    (G > 0 ? G : 0) * 0x100 +
                    (B > 0 ? B : 0)
                ).toString(16).slice(1);
            }

            datosAsistencias.forEach((gradoData, index) => {
                const ctx = document.getElementById('asistenciaChart' + index);

                if (!ctx) {
                    console.error('Canvas no encontrado para el gráfico: ' + index);
                    return;
                }

                const context = ctx.getContext('2d');
                const labels = gradoData.estudiantes.map(e => e.nombre_completo);

                // Preparar datasets para cada tipo de asistencia
                const datasets = tiposAsistencia.map(tipo => {
                    const colorBase = colores[tipo.nombre] || '#808080';

                    return {
                        label: tipo.nombre,
                        data: gradoData.estudiantes.map(estudiante =>
                            estudiante.porcentajes_tipo[tipo.nombre] || 0
                        ),
                        backgroundColor: colorBase,
                        borderColor: colorBase,
                        borderWidth: 1,
                        hoverBackgroundColor: lightenColor(colorBase, 20),
                        hoverBorderColor: darkenColor(colorBase, 10),
                    };
                });

                new Chart(context, {
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
                                text: 'Porcentaje de Tipos de Asistencia por Estudiante - ' + gradoData.grado,
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                color: '#2C3E50'
                            },
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    color: '#2C3E50',
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    },
                                    usePointStyle: true,
                                    pointStyle: 'rectRounded'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(44, 62, 80, 0.95)',
                                titleColor: '#ECF0F1',
                                bodyColor: '#ECF0F1',
                                borderColor: '#3498DB',
                                borderWidth: 1,
                                cornerRadius: 6,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y;
                                        return `${label}: ${value}%`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Porcentaje (%)',
                                    color: '#2C3E50',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                stacked: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    color: '#2C3E50',
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Estudiantes',
                                    color: '#2C3E50',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: '#2C3E50',
                                    maxRotation: 45,
                                    minRotation: 45,
                                    font: {
                                        size: 11
                                    }
                                },
                                stacked: true,
                                grid: {
                                    display: false
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

    <style>
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .card-header {
            background: linear-gradient(135deg, #3498db, #2980b9) !important;
        }
        .table th {
            background-color: #34495e;
            color: white;
            position: sticky;
            top: 0;
            border: none;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,0.02);
        }
    </style>
@endsection
