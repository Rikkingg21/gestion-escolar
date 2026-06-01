@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Encabezdo y Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="h3 mb-0">
                            <i class="fas fa-user-graduate"></i> Dashboard Estudiante
                        </h1>
                        <!-- Switch de visualización -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="btnCuantitativo" onclick="cambiarVisualizacion('cuantitativo')">
                                <i class="fas fa-chart-line me-1"></i> Cuantitativo (Notas)
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnCualitativo" onclick="cambiarVisualizacion('cualitativo')">
                                <i class="fas fa-tag me-1"></i> Cualitativo (AD/A/B/C)
                            </button>
                        </div>
                    </div>

                    <form method="GET" action="{{ request()->url() }}" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Período Escolar</label>
                            <select name="periodo_id" class="form-select" onchange="this.form.submit()">
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->id }}"
                                        {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                        {{ $periodo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bimestre</label>
                            <select name="bimestre" class="form-select" onchange="this.form.submit()">
                                <option value="anual" {{ request('bimestre', 'anual') == 'anual' ? 'selected' : '' }}>Promedio Anual</option>
                                @foreach($bimestresDisponibles as $bimestre)
                                    <option value="{{ $bimestre->sigla }}" {{ request('bimestre') == $bimestre->sigla ? 'selected' : '' }}>
                                        {{ $bimestre->sigla }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ NUEVO: Mensaje contextual para período de recuperación -->
    @if(isset($mensajeRecuperacion))
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                {{ $mensajeRecuperacion }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

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
                                    $promedioGeneral = $infoEstudiante['promedio_general'] ?? null;
                                    $cursosAprobados = $infoEstudiante['cursos_aprobados'] ?? 0;
                                    $cursosDesaprobados = $infoEstudiante['cursos_desaprobados'] ?? 0;

                                    // Calcular competencias totales
                                    $totalCompetencias = 0;
                                    $competenciasAprobadas = 0;
                                    $competenciasEnRecuperacion = 0;
                                    foreach($infoEstudiante['progreso_cursos'] as $curso) {
                                        $totalCompetencias += $curso['total_competencias'];
                                        $competenciasAprobadas += $curso['competencias_aprobadas'];
                                        $competenciasEnRecuperacion += $curso['competencias_recuperacion'];
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
                                                {{ number_format($promedioGeneral, 1) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-warning shadow h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Competencias Aprobadas
                                            </div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800">
                                                {{ $competenciasAprobadas }} / {{ $totalCompetencias }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de progreso por bimestre (solo modo anual y NO período de recuperación) -->
                            @if($bimestreFiltro == 'anual' && !empty($chartData))
                            <div class="mb-5">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-line me-2"></i> Progreso de Competencias por Bimestre
                                </h5>
                                <div class="card">
                                    <div class="card-body">
                                        <div style="height: 450px;">
                                            <canvas id="competenciasChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Tabla de notas por Materia y Competencia -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="notasTable">
                                    <thead class="table-dark">
                                        <tr class="text-center">
                                            <th style="width: 20%">Materia</th>
                                            <th style="width: 30%">Competencia</th>
                                            @if($bimestreFiltro == 'anual')
                                                @foreach($bimestresDisponibles as $bimestre)
                                                    <th style="width: 10%">{{ $bimestre->sigla }}</th>
                                                @endforeach
                                            @endif
                                            <th style="width: 15%">Promedio Final</th>
                                            <th style="width: 15%">Estado</th>
                                        </td>
                                    </thead>
                                    <tbody>
                                        @forelse($infoEstudiante['progreso_cursos'] as $curso)
                                            @foreach($curso['competencias'] as $competenciaIndex => $competencia)
                                                @php
                                                    $tienePendiente = $competencia['tiene_registro_recuperacion'] ?? false;
                                                    $claseFila = $tienePendiente ? 'table-danger' : '';
                                                @endphp
                                                <tr class="competencia-row {{ $claseFila }}" data-visualizacion="cuantitativo">
                                                    @if($loop->first)
                                                    <td class="align-middle text-center fw-bold bg-light" rowspan="{{ count($curso['competencias']) }}">
                                                        {{ $curso['curso'] }}
                                                    </td>
                                                    @endif

                                                    <td class="align-middle">
                                                        <strong>{{ $competencia['nombre'] }}</strong>
                                                        @if($competencia['nota_recuperacion'] ?? false)
                                                            <span class="badge bg-info ms-1" title="Nota de recuperación aplicada">
                                                                <i class="fas fa-sync-alt"></i> Rec
                                                            </span>
                                                        @endif
                                                        <!-- Mostrar nota original si tiene recuperación -->
                                                        @if(($competencia['nota_recuperacion'] ?? false) && isset($competencia['promedio_original']))
                                                            <br>
                                                            <small class="text-muted">
                                                                Original: {{ number_format($competencia['promedio_original'], 1) }}
                                                            </small>
                                                        @endif
                                                    </td>

                                                    @if($bimestreFiltro == 'anual')
                                                        @php
                                                            $promediosBimestres = $competencia['promedios_bimestres'] ?? [];
                                                        @endphp
                                                        @foreach($bimestresDisponibles as $bimestre)
                                                            <td class="text-center">
                                                                <span class="badge-nota-cuantitativo">
                                                                    @if(isset($promediosBimestres[$bimestre->bimestre]) && $promediosBimestres[$bimestre->bimestre] !== null)
                                                                        <strong class="
                                                                            @if($promediosBimestres[$bimestre->bimestre] >= 1.5)
                                                                            @else text-danger
                                                                            @endif">
                                                                            {{ number_format($promediosBimestres[$bimestre->bimestre], 1) }}
                                                                        </strong>
                                                                    @else
                                                                        <span class="text-muted">--</span>
                                                                    @endif
                                                                </span>
                                                                <span class="badge-nota-cualitativo" style="display: none;">
                                                                    @if(isset($promediosBimestres[$bimestre->bimestre]) && $promediosBimestres[$bimestre->bimestre] !== null)
                                                                        <strong class="
                                                                            @if($promediosBimestres[$bimestre->bimestre] >= 3.5)
                                                                            @elseif($promediosBimestres[$bimestre->bimestre] >= 2.5)
                                                                            @elseif($promediosBimestres[$bimestre->bimestre] >= 1.5)
                                                                            @else text-danger
                                                                            @endif">
                                                                            @if($promediosBimestres[$bimestre->bimestre] >= 3.5) AD
                                                                            @elseif($promediosBimestres[$bimestre->bimestre] >= 2.5) A
                                                                            @elseif($promediosBimestres[$bimestre->bimestre] >= 1.5) B
                                                                            @else C
                                                                            @endif
                                                                        </strong>
                                                                    @else
                                                                        <span class="text-muted">--</span>
                                                                    @endif
                                                                </span>
                                                            </td>
                                                        @endforeach
                                                    @endif

                                                    @php
                                                        $promedioFinal = $competencia['promedio_final'] ?? $competencia['promedio_original'];
                                                        $notaOriginal = $competencia['promedio_original'];
                                                        $tieneRecuperacion = $competencia['nota_recuperacion'] ?? false;
                                                        $estaAprobada = $competencia['esta_aprobada'] ?? false;
                                                    @endphp

                                                    <td class="text-center">
                                                        <span class="promedio-final-cuantitativo">
                                                            @if($promedioFinal !== null)
                                                                <div>
                                                                    <strong class="@if(!$estaAprobada) text-danger @endif">
                                                                        {{ number_format($promedioFinal, 1) }}
                                                                    </strong>
                                                                </div>
                                                                @if($tieneRecuperacion && $promedioFinal != $notaOriginal)
                                                                    <small class="text-muted">
                                                                        (orig: {{ number_format($notaOriginal, 1) }})
                                                                    </small>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </span>
                                                        <span class="promedio-final-cualitativo" style="display: none;">
                                                            @if($promedioFinal !== null)
                                                                <div>
                                                                    <strong class="@if(!$estaAprobada) text-danger @endif">
                                                                        {{ $competencia['promedio_final_cualitativo'] ?? $competencia['promedio_original_cualitativo'] }}
                                                                    </strong>
                                                                </div>
                                                                @if($tieneRecuperacion && $promedioFinal != $notaOriginal)
                                                                    <small class="text-muted">
                                                                        (orig: {{ number_format($notaOriginal, 1) }})
                                                                    </small>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        @if($competencia['requiere_recuperacion'] ?? false)
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Recuperación
                                                            </span>
                                                        @elseif($estaAprobada)
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Aprobado
                                                            </span>
                                                        @else
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i>Desaprobado
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="{{ $bimestreFiltro == 'anual' ? (4 + count($bimestresDisponibles)) : 4 }}" class="text-center">
                                                    No hay datos disponibles
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Leyenda de colores -->
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6 class="mb-2"><i class="fas fa-info-circle me-1"></i> Leyenda:</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <i class="fas fa-sync-alt text-info me-1"></i> Rec = Nota mejorada por recuperación
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-danger fw-bold me-1">Nota roja</span> = Nota desaprobatoria (&lt; 1.5)
                                    </div>
                                    <div class="col-md-4">
                                        <span class="bg-danger text-white px-2 me-1">Fila roja</span> = Competencia con recuperación pendiente
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <small class="text-muted">
                                            Umbral de aprobación: Nota ≥ 1.5 (equivalente a B)
                                        </small>
                                    </div>
                                </div>
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
                                            if ($conducta['promedio_general'] >= 1.5) {
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
                                                <td class="fw-bold">
                                                    {{ $conducta['nombre'] }}
                                                </td>

                                                <td class="text-center fw-bold">
                                                    @if($conducta['promedio_general'] !== null)
                                                        <span class="{{ $conducta['promedio_general'] < 1.5 ? 'text-danger' : 'text-success' }}">
                                                            {{ number_format($conducta['promedio_general'], 1) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    @if($conducta['promedio_general'] !== null)
                                                        @if($conducta['promedio_general'] >= 1.5)
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>
                                                                Adecuado
                                                            </span>
                                                        @else
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i>
                                                                Inadecuado
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary">
                                                            Sin datos
                                                        </span>
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
// Variable global para almacenar la visualización actual
let visualizacionActual = localStorage.getItem('visualizacionNotas') || 'cuantitativo';

// Función para cambiar entre cuantitativo y cualitativo
function cambiarVisualizacion(tipo) {
    visualizacionActual = tipo;
    localStorage.setItem('visualizacionNotas', tipo);

    // Actualizar botones
    document.getElementById('btnCuantitativo').classList.remove('active');
    document.getElementById('btnCualitativo').classList.remove('active');

    if (tipo === 'cuantitativo') {
        document.getElementById('btnCuantitativo').classList.add('active');
        // Mostrar cuantitativo, ocultar cualitativo
        document.querySelectorAll('.promedio-final-cuantitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.promedio-final-cualitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.badge-nota-cuantitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.badge-nota-cualitativo').forEach(el => el.style.display = 'none');
    } else {
        document.getElementById('btnCualitativo').classList.add('active');
        // Mostrar cualitativo, ocultar cuantitativo
        document.querySelectorAll('.promedio-final-cuantitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.promedio-final-cualitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.badge-nota-cuantitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.badge-nota-cualitativo').forEach(el => el.style.display = '');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const bimestreFiltro = @json($bimestreFiltro);
    const chartData = @json($chartData ?? []);

    if (bimestreFiltro === 'anual' && chartData.length > 0) {
        const colores = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
        ];

        const datasets = [];
        const competenciasMostrar = chartData.slice(0, 8);

        competenciasMostrar.forEach((competencia, index) => {
            const tieneDatos = Object.values(competencia.promedios).some(v => v !== null && v !== undefined);

            if (tieneDatos) {
                datasets.push({
                    label: competencia.nombre.length > 40 ? competencia.nombre.substring(0, 37) + '...' : competencia.nombre,
                    data: [
                        competencia.promedios[1] || null,
                        competencia.promedios[2] || null,
                        competencia.promedios[3] || null,
                        competencia.promedios[4] || null
                    ],
                    borderColor: colores[index % colores.length],
                    backgroundColor: colores[index % colores.length] + '15',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: colores[index % colores.length],
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                });
            }
        });

        if (datasets.length > 0) {
            const config = {
                type: 'line',
                data: {
                    labels: @json($bimestresDisponibles->pluck('nombre')),
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 11 },
                                usePointStyle: true,
                                boxWidth: 10
                            }
                        },
                        title: {
                            display: true,
                            text: 'Evolución del Rendimiento por Competencia',
                            font: { size: 14, weight: 'bold' },
                            padding: { bottom: 20 }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null && context.parsed.y !== undefined) {
                                        const nota = context.parsed.y;
                                        label += nota.toFixed(1);
                                        if (nota >= 3.5) label += ' (AD)';
                                        else if (nota >= 2.5) label += ' (A)';
                                        else if (nota >= 1.5) label += ' (B)';
                                        else label += ' (C)';
                                    } else {
                                        label += 'Sin datos';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 4.2,
                            title: {
                                display: true,
                                text: 'Notas',
                                font: { weight: 'bold' }
                            },
                            ticks: {
                                stepSize: 0.5,
                                callback: function(value) {
                                    return value.toFixed(1);
                                }
                            },
                            grid: {
                                color: '#e3e6f0',
                                drawBorder: true
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Bimestres',
                                font: { weight: 'bold' }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            };

            const canvas = document.getElementById('competenciasChart');
            if (canvas) {
                const existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    existingChart.destroy();
                }
                new Chart(canvas, config);
            }
        }
    }

    // Aplicar la visualización guardada
    cambiarVisualizacion(visualizacionActual);
});
</script>
@endsection
