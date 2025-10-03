@extends('layouts.app')

@section('content')
    <h1>Dashboard Director</h1>

    <div>
        <canvas id="progresoGradosChart"></canvas>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = @json($labelsBimestres);
        const datosProgreso = @json($progreso);

        // Verificar que los datos existen
        if (!datosProgreso || datosProgreso.length === 0) {
            console.error('No hay datos para mostrar');
            return;
        }

        const datasets = datosProgreso.map(grado => {
            // Generar color hexadecimal válido
            const color = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');

            return {
                label: grado.grado || 'Grado sin nombre',
                data: grado.promedios || [],
                fill: false,
                borderColor: color,
                tension: 0.1,
                backgroundColor: color + '20'
            };
        });

        // Crear el gráfico
        new Chart(document.getElementById('progresoGradosChart'), {
            type: 'line',
            data: {
                labels: labels || [],
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Progreso de los Grados en el Año Actual'
                    },
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 1,
                        max: 4,
                        title: {
                            display: true,
                            text: 'Nota'
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
</script>
@endsection
