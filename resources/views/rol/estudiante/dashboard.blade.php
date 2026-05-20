@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Encabezado y Filtros -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-3">
                        <i class="fas fa-user-graduate"></i> Dashboard Estudiante
                    </h1>
                    <form method="GET" action="{{ request()->url() }}" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Período Escolar</label>
                            <select name="periodo_id" class="form-select" onchange="this.form.submit()">
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->id }}"
                                        {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                        {{ $periodo->anio }}
                                        @if($periodo->estado == 1)
                                            <span class="text-success">(Activo)</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Bimestre</label>
                            <select name="bimestre" class="form-select" onchange="this.form.submit()">
                                <option value="anual" {{ request('bimestre', 'anual') == 'anual' ? 'selected' : '' }}>Promedio Anual</option>
                                <option value="B1" {{ request('bimestre') == 'B1' ? 'selected' : '' }}>1° Bimestre</option>
                                <option value="B2" {{ request('bimestre') == 'B2' ? 'selected' : '' }}>2° Bimestre</option>
                                <option value="B3" {{ request('bimestre') == 'B3' ? 'selected' : '' }}>3° Bimestre</option>
                                <option value="B4" {{ request('bimestre') == 'B4' ? 'selected' : '' }}>4° Bimestre</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
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
                        {{ $infoEstudiante['total_cursos'] }} curso(s) / {{ $infoEstudiante['total_conducta'] }} conducta(s)
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
                            <i class="fas fa-hand-peace me-1"></i> Conducta
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
                                        round(array_sum($todasNotas) / count($todasNotas), 1) : null;

                                    $cursosAprobados = 0;
                                    $cursosReprobados = 0;
                                    foreach($infoEstudiante['progreso_cursos'] as $curso) {
                                        if ($curso['promedio_general'] !== null) {
                                            if ($curso['promedio_general'] > 2) {
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

                            <!-- Gráfico de notas (solo en modo anual) -->
                            @if($bimestreFiltro == 'anual')
                            <div class="mb-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-line me-2"></i> Progreso Académico por Bimestre
                                </h5>
                                <div style="height: 400px;">
                                    <canvas id="progresoChart"></canvas>
                                </div>
                            </div>
                            @endif

                            <!-- Tabla de notas -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr class="text-center">
                                            <th>Curso / Materia</th>
                                            @if($bimestreFiltro == 'anual')
                                                <th>Bimestre 1</th>
                                                <th>Bimestre 2</th>
                                                <th>Bimestre 3</th>
                                                <th>Bimestre 4</th>
                                            @endif
                                            <th>Promedio</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($infoEstudiante['progreso_cursos'] as $curso)
                                        <tr>
                                            <td class="fw-bold">{{ $curso['curso'] }}</td>
                                            @if($bimestreFiltro == 'anual')
                                                @foreach($curso['promedios'] as $bimestre => $promedio)
                                                <td class="text-center">
                                                    @if($promedio !== null)
                                                        <span class="badge
                                                            @if($promedio > 3) bg-success
                                                            @elseif($promedio > 2) bg-warning
                                                            @else bg-danger
                                                            @endif fs-6">
                                                            {{ $promedio }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">--</span>
                                                    @endif
                                                </td>
                                                @endforeach
                                            @endif
                                            <td class="text-center fw-bold">
                                                @if($curso['promedio_general'] !== null)
                                                    <span class="badge
                                                        @if($curso['promedio_general'] > 3) bg-success
                                                        @elseif($curso['promedio_general'] > 2) bg-warning
                                                        @else bg-danger
                                                        @endif fs-6">
                                                        {{ $curso['promedio_general'] }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($curso['promedio_general'] !== null)
                                                    @if($curso['promedio_general'] > 2)
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
                                    $promedioConductaGeneral = 0;
                                    $totalConductas = 0;
                                    $conductasAdecuadas = 0;
                                    $conductasInadecuadas = 0;

                                    foreach($infoEstudiante['progreso_conducta'] as $conducta) {
                                        if ($conducta['promedio_general'] !== null) {
                                            $promedioConductaGeneral += $conducta['promedio_general'];
                                            $totalConductas++;
                                            if ($conducta['promedio_general'] > 2) {
                                                $conductasAdecuadas++;
                                            } else {
                                                $conductasInadecuadas++;
                                            }
                                        }
                                    }
                                    $promedioConductaGeneral = $totalConductas > 0 ? round($promedioConductaGeneral / $totalConductas, 1) : null;
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
                                        <tr class="text-center">
                                            <th>Competencia / Área</th>
                                            <th>Promedio</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($infoEstudiante['progreso_conducta'] as $conducta)
                                        <tr>
                                            <td class="fw-bold">{{ $conducta['nombre'] }}</td>
                                            <td class="text-center fw-bold">
                                                @if($conducta['promedio_general'] !== null)
                                                    <span class="badge
                                                        @if($conducta['promedio_general'] > 3) bg-success
                                                        @elseif($conducta['promedio_general'] > 2) bg-warning
                                                        @else bg-danger
                                                        @endif fs-6">
                                                        {{ $conducta['promedio_general'] }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($conducta['promedio_general'] !== null)
                                                    @if($conducta['promedio_general'] > 2)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Adecuado
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Inadecuado
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
        const bimestreFiltro = @json($bimestreFiltro);

        if (bimestreFiltro === 'anual' && infoEstudiante.progreso_cursos && infoEstudiante.progreso_cursos.length > 0) {
            const colores = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];

            const datasets = infoEstudiante.progreso_cursos.map((curso, index) => ({
                label: curso.curso,
                data: [curso.promedios[1], curso.promedios[2], curso.promedios[3], curso.promedios[4]],
                borderColor: colores[index % colores.length],
                tension: 0,
                fill: false
            }));

            const config = {
                type: 'line',
                data: {
                    labels: ['B1', 'B2', 'B3', 'B4'],
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Progreso Académico' }
                    },
                    scales: {
                        y: { min: 1, max: 4, title: { display: true, text: 'Notas' } }
                    }
                }
            };

            new Chart(document.getElementById('progresoChart'), config);
        }
    });
</script>

<style>
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
    .border-left-danger { border-left: 4px solid #e74a3b !important; }
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .badge { padding: 0.5rem 0.75rem; }
    .table-responsive { overflow-x: auto; }
</style>
@endsection
