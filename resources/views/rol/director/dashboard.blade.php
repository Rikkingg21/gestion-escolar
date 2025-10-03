@extends('layouts.app')

@section('content')
    <h1>Dashboard Director</h1>

    <div style="height: 500px;">
        <canvas id="progresoGradosChart"></canvas>
    </div>

    @if(empty($progreso))
        <div class="alert alert-warning">
            No hay datos disponibles para mostrar el gráfico.
        </div>
    @endif

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = @json($labelsBimestres);
        const datosProgreso = @json($progreso);

        // Verificar que los datos existen
        if (!datosProgreso || datosProgreso.length === 0) {
            console.error('No hay datos para mostrar');
            document.getElementById('progresoGradosChart').style.display = 'none';
            return;
        }

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
                        text: 'Progreso Académico por Grado - Año ' + new Date().getFullYear(),
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'nearest', // Cambiado a 'nearest'
                        intersect: false, // Mostrar tooltip cuando el cursor esté cerca de cualquier punto
                        // Filtro personalizado para mostrar solo datasets con datos en ese punto específico
                        filter: function(tooltipItem) {
                            // Solo mostrar tooltip si el valor no es null o 0
                            return tooltipItem.parsed.y !== null && tooltipItem.parsed.y !== 0;
                        },
                        callbacks: {
                            // Personalizar el contenido del tooltip
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toFixed(2);
                                }
                                return label;
                            },
                            // Opcional: filtrar qué items aparecen en el tooltip
                            afterBody: function(tooltipItems) {
                                // Esta función se ejecuta después de mostrar los labels
                                // Puedes usarla para lógica adicional
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
                    mode: 'nearest', // Interactuar con el punto más cercano
                    axis: 'x', // Solo considerar la coordenada X para la interacción
                    intersect: false // No requerir que el cursor intersecte exactamente con el punto
                }
            }
        });
    });
</script>
@endsection
