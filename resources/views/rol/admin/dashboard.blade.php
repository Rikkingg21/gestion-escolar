@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <!-- Usuarios por rol -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <strong>Usuarios por Rol</strong>
                </div>
                <div class="card-body">
                    <canvas id="rolesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Docentes -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <strong>Docentes</strong>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-4">{{ $docentesCount }}</h1>
                    <p class="text-muted">Total de Docentes</p>
                </div>
            </div>
        </div>

        <!-- Estudiantes -->
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <strong>Estudiantes</strong>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-5">{{ $estudiantesCount }}</h1>
                    <p class="text-muted">Total de Estudiantes</p>
                </div>
            </div>
        </div>

        <!-- Apoderados -->
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <strong>Apoderados</strong>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-5">{{ $apoderadosCount }}</h1>
                    <p class="text-muted">Total de Apoderados</p>
                </div>
            </div>
        </div>

        <!-- Auxiliares -->
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <strong>Auxiliares</strong>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-5">{{ $auxiliaresCount }}</h1>
                    <p class="text-muted">Total de Auxiliares</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const roles = @json($rolesCount->keys());
    const counts = @json($rolesCount->values());

    const ctx = document.getElementById('rolesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: roles,
            datasets: [{
                label: 'Cantidad de usuarios',
                data: counts,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Distribución de usuarios por rol'
                }
            }
        }
    });
</script>
@endsection
