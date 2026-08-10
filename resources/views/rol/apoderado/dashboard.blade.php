@extends('layouts.app')
@section('title', 'Panel del Apoderado')

@section('content')
<div class="container-fluid">
    <!-- Encabezado y Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="h3 mb-0">
                            <i class="bi bi-heart"></i> Dashboard Apoderado
                        </h1>
                        <!-- Switch de visualización -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="btnCuantitativo" onclick="cambiarVisualizacion('cuantitativo')">
                                <i class="bi bi-graph-up me-1"></i> Cuantitativo (Notas)
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnCualitativo" onclick="cambiarVisualizacion('cualitativo')">
                                <i class="bi bi-tag me-1"></i> Cualitativo (AD/A/B/C)
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
                                @foreach($bimestresRegulares ?? [] as $bimestre)
                                    <option value="{{ $bimestre->sigla }}" {{ request('bimestre') == $bimestre->sigla ? 'selected' : '' }}>
                                        {{ $bimestre->sigla }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del apoderado -->
    <div class="card mb-4 shadow">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">
                <i class="bi bi-person-badge me-2"></i>
                Información del Apoderado
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-1"><strong>Nombre:</strong></p>
                    <p class="mb-0">{{ $infoApoderado['nombre_completo'] }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Parentesco:</strong></p>
                    <p class="mb-0">{{ $infoApoderado['parentesco'] }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Estudiantes a cargo:</strong></p>
                    <p class="mb-0">{{ $infoApoderado['total_estudiantes'] }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(empty($datosEstudiantes))
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No hay estudiantes asignados.
        </div>
    @else
        @foreach($datosEstudiantes as $estudianteIndex => $estudianteData)
            <div class="card mb-5 shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-mortarboard me-2"></i>
                            {{ $estudianteData['nombre_completo'] }} - {{ $estudianteData['grado'] }}
                        </h4>
                        @if(($estudianteData['total_cursos'] ?? 0) > 0 || ($estudianteData['total_conducta'] ?? 0) > 0)
                            <span class="badge bg-light text-primary fs-6">
                                {{ $estudianteData['total_cursos'] ?? 0 }} curso(s) / {{ $estudianteData['total_conducta'] ?? 0 }} conducta(s)
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($estudianteData['mensaje']))
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ $estudianteData['mensaje'] }}
                        </div>
                    @elseif(($estudianteData['total_cursos'] ?? 0) == 0 && ($estudianteData['total_conducta'] ?? 0) == 0)
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay notas registradas para este período.
                        </div>
                    @else
                        <ul class="nav nav-tabs mb-4" id="estudianteTabs{{ $estudianteIndex }}" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="notas-tab-{{ $estudianteIndex }}"
                                        data-bs-toggle="tab" data-bs-target="#notas-{{ $estudianteIndex }}"
                                        type="button" role="tab">
                                    <i class="bi bi-mortarboard me-1"></i> Notas Académicas
                                    @if(($estudianteData['total_cursos'] ?? 0) > 0)
                                        <span class="badge bg-primary ms-1">{{ $estudianteData['total_cursos'] }}</span>
                                    @endif
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="conducta-tab-{{ $estudianteIndex }}"
                                        data-bs-toggle="tab" data-bs-target="#conducta-{{ $estudianteIndex }}"
                                        type="button" role="tab">
                                    <i class="bi bi-emoji-smile me-1"></i> Conducta
                                    @if(($estudianteData['total_conducta'] ?? 0) > 0)
                                        <span class="badge bg-success ms-1">{{ $estudianteData['total_conducta'] }}</span>
                                    @endif
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="estudianteContent{{ $estudianteIndex }}">
                            <!-- Pestaña de Notas Académicas -->
                            <div class="tab-pane fade show active" id="notas-{{ $estudianteIndex }}" role="tabpanel">
                                @if(($estudianteData['total_cursos'] ?? 0) > 0)
                                    <!-- Resumen estadístico -->
                                    <div class="row mb-4">
                                        @php
                                            $promedioGeneral = $estudianteData['promedio_general'] ?? null;
                                            $cursosAprobados = $estudianteData['cursos_aprobados'] ?? 0;
                                            $cursosDesaprobados = $estudianteData['cursos_desaprobados'] ?? 0;

                                            $totalCompetencias = 0;
                                            $competenciasAprobadas = 0;
                                            foreach($estudianteData['progreso_cursos'] as $curso) {
                                                $totalCompetencias += $curso['total_competencias'];
                                                $competenciasAprobadas += $curso['competencias_aprobadas'];
                                            }
                                        @endphp

                                        @if($promedioGeneral)
                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-success shadow h-100">
                                                <div class="card-body">
                                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                                        Promedio General
                                                    </div>
                                                    <div class="h3 mb-0 fw-bold text-body">
                                                        {{ number_format($promedioGeneral, 1) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-warning shadow h-100">
                                                <div class="card-body">
                                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                                        Competencias Aprobadas
                                                    </div>
                                                    <div class="h3 mb-0 fw-bold text-body">
                                                        {{ $competenciasAprobadas }} / {{ $totalCompetencias }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Gráfico con filtros por materia -->
                                    @if($bimestreFiltro == 'anual' && !empty($estudianteData['chartData']))
                                    <div class="mb-5" id="graficoContainer{{ $estudianteIndex }}">
                                        <h5 class="mb-3">
                                            <i class="bi bi-graph-up me-2"></i> Progreso de Competencias por Bimestre
                                        </h5>
                                        <div id="filtrosContainer{{ $estudianteIndex }}"></div>
                                        <div class="card">
                                            <div class="card-body">
                                                <div style="height: 450px;">
                                                    <canvas id="competenciasChart{{ $estudianteIndex }}"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Tabla de notas -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr class="text-center">
                                                    <th style="width: 20%">Materia</th>
                                                    <th style="width: 30%">Competencia</th>
                                                    @if($bimestreFiltro == 'anual')
                                                        @foreach($bimestresRegulares ?? [] as $bimestre)
                                                            <th style="width: 10%">{{ $bimestre->sigla }}</th>
                                                        @endforeach
                                                    @endif
                                                    <th style="width: 15%">Promedio Final</th>
                                                    <th style="width: 15%">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($estudianteData['progreso_cursos'] as $curso)
                                                    @foreach($curso['competencias'] as $competenciaIndex => $competencia)
                                                        @php
                                                            $tienePendiente = ($bimestreFiltro == 'anual' && ($competencia['tiene_registro_recuperacion'] ?? false));
                                                            $claseFila = $tienePendiente ? 'table-danger' : '';
                                                        @endphp
                                                        <tr class="competencia-row {{ $claseFila }}">
                                                            @if($loop->first)
                                                            <td class="align-middle text-center fw-bold bg-light" rowspan="{{ count($curso['competencias']) }}">
                                                                {{ $curso['curso'] }}
                                                            </td>
                                                            @endif

                                                            <td class="align-middle">
                                                                <strong>{{ $competencia['nombre'] }}</strong>
                                                                @if($competencia['nota_recuperacion'] ?? false)
                                                                    <span class="badge bg-info ms-1" title="Nota de recuperación aplicada">
                                                                        <i class="bi bi-arrow-repeat"></i> Rec
                                                                    </span>
                                                                @endif
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
                                                                @foreach($bimestresRegulares ?? [] as $bimestre)
                                                                    <td class="text-center">
                                                                        <span class="badge-nota-cuantitativo">
                                                                            @if(isset($promediosBimestres[$bimestre->bimestre]) && $promediosBimestres[$bimestre->bimestre] !== null)
                                                                                <strong class="{{ $promediosBimestres[$bimestre->bimestre] >= 1.5 ? '' : 'text-danger' }}">
                                                                                    {{ number_format($promediosBimestres[$bimestre->bimestre], 1) }}
                                                                                </strong>
                                                                            @else
                                                                                <span class="text-muted">--</span>
                                                                            @endif
                                                                        </span>
                                                                        <span class="badge-nota-cualitativo" style="display: none;">
                                                                            @if(isset($promediosBimestres[$bimestre->bimestre]) && $promediosBimestres[$bimestre->bimestre] !== null)
                                                                                <strong>
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
                                                                            <strong class="{{ !$estaAprobada ? 'text-danger' : '' }}">
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
                                                                            <strong class="{{ !$estaAprobada ? 'text-danger' : '' }}">
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
                                                                @if($bimestreFiltro == 'anual')
                                                                    @if($competencia['requiere_recuperacion'] ?? false)
                                                                        <span class="badge bg-warning text-dark">
                                                                            <i class="bi bi-exclamation-triangle me-1"></i>Recuperación
                                                                        </span>
                                                                    @elseif($estaAprobada)
                                                                        <span class="badge bg-success">
                                                                            <i class="bi bi-check me-1"></i>Aprobado
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-danger">
                                                                            <i class="bi bi-x me-1"></i>Desaprobado
                                                                        </span>
                                                                    @endif
                                                                @else
                                                                    @if($estaAprobada)
                                                                        <span class="badge bg-success">
                                                                            <i class="bi bi-check me-1"></i>Aprobado
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-danger">
                                                                            <i class="bi bi-x me-1"></i>Desaprobado
                                                                        </span>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ $bimestreFiltro == 'anual' ? (4 + count($bimestresRegulares ?? [])) : 4 }}" class="text-center">
                                                            No hay datos disponibles
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Leyenda -->
                                    <div class="mt-4 p-3 bg-light rounded">
                                        <h6 class="mb-2"><i class="bi bi-info-circle me-1"></i> Leyenda:</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <i class="bi bi-arrow-repeat text-info me-1"></i> Rec = Nota mejorada por recuperación
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
                                        <i class="bi bi-info-circle me-2"></i>
                                        No hay notas académicas registradas para este estudiante.
                                    </div>
                                @endif
                            </div>

                            <!-- Pestaña de Conducta -->
                            <div class="tab-pane fade" id="conducta-{{ $estudianteIndex }}" role="tabpanel">
                                @if(($estudianteData['total_conducta'] ?? 0) > 0)
                                    <div class="row mb-4">
                                        @php
                                            $promedioConductaGeneral = 0;
                                            $totalConductas = 0;
                                            $conductasAdecuadas = 0;
                                            $conductasInadecuadas = 0;

                                            foreach($estudianteData['progreso_conducta'] as $conducta) {
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
                                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                                        Promedio Conducta
                                                    </div>
                                                    <div class="h3 mb-0 fw-bold text-body">
                                                        {{ $promedioConductaGeneral }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-info shadow h-100">
                                                <div class="card-body">
                                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                                        Áreas Evaluadas
                                                    </div>
                                                    <div class="h3 mb-0 fw-bold text-body">
                                                        {{ $estudianteData['total_conducta'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-success shadow h-100">
                                                <div class="card-body">
                                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                                        Conducta Adecuada
                                                    </div>
                                                    <div class="h3 mb-0 fw-bold text-body">
                                                        {{ $conductasAdecuadas }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-warning shadow h-100">
                                                <div class="card-body">
                                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                                        Conducta Inadecuada
                                                    </div>
                                                    <div class="h3 mb-0 fw-bold text-body">
                                                        {{ $conductasInadecuadas }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-dark">
                                                <tr class="text-center">
                                                    <th>Conducta</th>
                                                    <th>Promedio</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($estudianteData['progreso_conducta'] as $conducta)
                                                    <tr>
                                                        <td class="fw-bold">
                                                            {{ $conducta['nombre'] }}
                                                        </td>
                                                        <td class="text-center fw-bold conducta-nota" data-nota="{{ $conducta['promedio_general'] }}">
                                                            @if($conducta['promedio_general'] !== null)
                                                                <span class="conducta-nota-cuantitativo">
                                                                    <span class="{{ $conducta['promedio_general'] < 1.5 ? 'text-danger' : 'text-success' }}">
                                                                        {{ number_format($conducta['promedio_general'], 1) }}
                                                                    </span>
                                                                </span>
                                                                <span class="conducta-nota-cualitativo" style="display: none;">
                                                                    <span class="{{ $conducta['promedio_general'] < 1.5 ? 'text-danger' : 'text-success' }}">
                                                                        @if($conducta['promedio_general'] >= 3.5) AD
                                                                        @elseif($conducta['promedio_general'] >= 2.5) A
                                                                        @elseif($conducta['promedio_general'] >= 1.5) B
                                                                        @else C
                                                                        @endif
                                                                    </span>
                                                                </span>
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($conducta['promedio_general'] !== null)
                                                                @if($conducta['promedio_general'] >= 1.5)
                                                                    <span class="badge bg-success">
                                                                        <i class="bi bi-check me-1"></i>
                                                                        Adecuado
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-danger">
                                                                        <i class="bi bi-x me-1"></i>
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
                                        <i class="bi bi-info-circle me-2"></i>
                                        No hay notas de conducta registradas para este estudiante.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variable global para almacenar la visualización actual
let visualizacionActual = localStorage.getItem('visualizacionNotas') || 'cuantitativo';
let chartInstances = {};
let materiasFiltradasMap = {};

// Función para cambiar entre cuantitativo y cualitativo
function cambiarVisualizacion(tipo) {
    visualizacionActual = tipo;
    localStorage.setItem('visualizacionNotas', tipo);

    document.getElementById('btnCuantitativo').classList.remove('active');
    document.getElementById('btnCualitativo').classList.remove('active');

    if (tipo === 'cuantitativo') {
        document.getElementById('btnCuantitativo').classList.add('active');

        document.querySelectorAll('.promedio-final-cuantitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.promedio-final-cualitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.badge-nota-cuantitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.badge-nota-cualitativo').forEach(el => el.style.display = 'none');

        document.querySelectorAll('.conducta-nota-cuantitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.conducta-nota-cualitativo').forEach(el => el.style.display = 'none');

    } else {
        document.getElementById('btnCualitativo').classList.add('active');

        document.querySelectorAll('.promedio-final-cuantitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.promedio-final-cualitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.badge-nota-cuantitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.badge-nota-cualitativo').forEach(el => el.style.display = '');

        document.querySelectorAll('.conducta-nota-cuantitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.conducta-nota-cualitativo').forEach(el => el.style.display = '');
    }
}

// Función para convertir nota a cualitativo
function convertirNotaCualitativo(nota) {
    if (nota === null || nota === undefined) return '--';
    if (nota >= 3.5) return 'AD';
    if (nota >= 2.5) return 'A';
    if (nota >= 1.5) return 'B';
    return 'C';
}

// Función para actualizar el gráfico basado en materias filtradas
function actualizarGrafico(estudianteIndex) {
    const chartInstance = chartInstances[estudianteIndex];
    if (!chartInstance) return;

    const materiasFiltradas = materiasFiltradasMap[estudianteIndex] || new Map();

    chartInstance.data.datasets.forEach(dataset => {
        const materiaVisible = materiasFiltradas.get(dataset.materia);
        dataset.hidden = !materiaVisible;
    });

    chartInstance.update();
}

// Función para toggle de materia
function toggleMateria(estudianteIndex, materia, element) {
    if (!materiasFiltradasMap[estudianteIndex]) {
        materiasFiltradasMap[estudianteIndex] = new Map();
    }

    const nuevaVisibilidad = !materiasFiltradasMap[estudianteIndex].get(materia);
    materiasFiltradasMap[estudianteIndex].set(materia, nuevaVisibilidad);

    if (nuevaVisibilidad) {
        element.classList.remove('btn-outline-secondary');
        element.classList.add('btn-primary');
    } else {
        element.classList.remove('btn-primary');
        element.classList.add('btn-outline-secondary');
    }

    actualizarGrafico(estudianteIndex);
}

// Función para seleccionar todas las materias
function seleccionarTodasMaterias(estudianteIndex, visibles) {
    const botones = document.querySelectorAll(`.materia-filter-${estudianteIndex}`);
    if (!materiasFiltradasMap[estudianteIndex]) {
        materiasFiltradasMap[estudianteIndex] = new Map();
    }

    botones.forEach(btn => {
        const materia = btn.getAttribute('data-materia');
        materiasFiltradasMap[estudianteIndex].set(materia, visibles);

        if (visibles) {
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary');
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        }
    });

    actualizarGrafico(estudianteIndex);
}

document.addEventListener('DOMContentLoaded', function() {
    const bimestreFiltro = @json($bimestreFiltro);
    const datosEstudiantes = @json($datosEstudiantes);
    const bimestresRegulares = @json($bimestresRegulares ?? []);

    // Configurar visualización de conducta
    document.querySelectorAll('.conducta-nota').forEach(el => {
        const nota = parseFloat(el.getAttribute('data-nota'));
        if (!isNaN(nota)) {
            const spanCuantitativo = document.createElement('span');
            spanCuantitativo.className = 'conducta-nota-cuantitativo';
            spanCuantitativo.textContent = nota.toFixed(1);

            const spanCualitativo = document.createElement('span');
            spanCualitativo.className = 'conducta-nota-cualitativo';
            spanCualitativo.style.display = 'none';
            spanCualitativo.textContent = convertirNotaCualitativo(nota);

            el.innerHTML = '';
            el.appendChild(spanCuantitativo);
            el.appendChild(spanCualitativo);
        }
    });

    // Gráficos para cada estudiante (solo en modo anual)
    if (bimestreFiltro === 'anual') {
        const colores = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#8A2BE2', '#5F9EA0', '#D2691E', '#7B68EE',
            '#20B2AA', '#FF69B4', '#87CEEB', '#FFA07A', '#6A5ACD',
            '#48D1CC', '#FFB6C1', '#98FB98', '#F0E68C', '#DDA0DD'
        ];

        datosEstudiantes.forEach((estudiante, index) => {
            if (estudiante.chartData && estudiante.chartData.length > 0) {
                // Agrupar por materia
                const competenciasPorMateria = new Map();
                estudiante.chartData.forEach(item => {
                    const materia = item.materia || 'Sin materia';
                    if (!competenciasPorMateria.has(materia)) {
                        competenciasPorMateria.set(materia, []);
                    }
                    competenciasPorMateria.get(materia).push(item);
                });

                const materias = Array.from(competenciasPorMateria.keys());
                const colorPorMateria = new Map();
                materias.forEach((materia, idx) => {
                    colorPorMateria.set(materia, colores[idx % colores.length]);
                });

                // Crear filtros HTML
                const filtrosHTML = `
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-2">
                            <i class="bi bi-filter me-1"></i> Filtrar por Materia:
                        </label>
                        <div class="d-flex flex-wrap gap-2" id="filtrosMaterias${index}">
                            ${materias.map(materia => `
                                <button type="button"
                                        class="btn btn-primary btn-sm materia-filter-${index}"
                                        data-materia="${materia.replace(/"/g, '&quot;')}"
                                        onclick="toggleMateria(${index}, '${materia.replace(/'/g, "\\'")}', this)"
                                        style="font-size: 0.85rem;">
                                    ${materia}
                                </button>
                            `).join('')}
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-link btn-sm" onclick="seleccionarTodasMaterias(${index}, true)" style="font-size: 0.8rem;">
                                <i class="bi bi-check-square me-1"></i> Seleccionar todas
                            </button>
                            <button type="button" class="btn btn-link btn-sm" onclick="seleccionarTodasMaterias(${index}, false)" style="font-size: 0.8rem;">
                                <i class="bi bi-square me-1"></i> Limpiar todas
                            </button>
                        </div>
                    </div>
                `;

                const filtrosContainer = document.getElementById(`filtrosContainer${index}`);
                if (filtrosContainer) {
                    filtrosContainer.innerHTML = filtrosHTML;
                }

                // Inicializar mapa de materias filtradas
                materiasFiltradasMap[index] = new Map();
                materias.forEach(materia => {
                    materiasFiltradasMap[index].set(materia, true);
                });

                // Preparar datasets
                const labels = bimestresRegulares.map(bim => bim.sigla);
                const datasets = [];

                estudiante.chartData.forEach((competencia, compIndex) => {
                    const materiaColor = colorPorMateria.get(competencia.materia) || colores[compIndex % colores.length];
                    const data = bimestresRegulares.map(bim => competencia.promedios[bim.bimestre] || null);
                    const tieneDatos = data.some(v => v !== null);

                    if (tieneDatos) {
                        datasets.push({
                            label: competencia.nombre,
                            data: data,
                            borderColor: materiaColor,
                            backgroundColor: materiaColor + '15',
                            borderWidth: 2,
                            tension: 0,
                            fill: false,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: materiaColor,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1.5,
                            materia: competencia.materia,
                            hidden: false
                        });
                    }
                });

                const canvas = document.getElementById(`competenciasChart${index}`);
                if (canvas && datasets.length > 0) {
                    chartInstances[index] = new Chart(canvas, {
                        type: 'line',
                        data: { labels, datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        font: { size: 9, family: "'Segoe UI', sans-serif" },
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        padding: 6
                                    }
                                },
                                tooltip: {
                                    mode: 'nearest',
                                    intersect: true,
                                    backgroundColor: 'rgba(0,0,0,0.85)',
                                    titleColor: '#fff',
                                    bodyColor: '#ddd',
                                    borderColor: '#666',
                                    borderWidth: 1,
                                    callbacks: {
                                        title: (items) => items[0].label,
                                        label: (context) => {
                                            const dataset = context.dataset;
                                            const nota = context.parsed.y;
                                            if (nota === null) return null;
                                            let cualitativo = '';
                                            if (nota >= 3.5) cualitativo = 'AD';
                                            else if (nota >= 2.5) cualitativo = 'A';
                                            else if (nota >= 1.5) cualitativo = 'B';
                                            else cualitativo = 'C';
                                            return `${dataset.label}: ${nota.toFixed(1)} (${cualitativo})`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    min: 0,
                                    max: 4.2,
                                    title: { display: true, text: 'Nota', font: { weight: 'bold', size: 12 } },
                                    ticks: { stepSize: 0.5, callback: (value) => value.toFixed(1), font: { size: 11 } },
                                    grid: { color: '#e9ecef', drawBorder: true }
                                },
                                x: {
                                    title: { display: true, text: 'Bimestre', font: { weight: 'bold', size: 12 } },
                                    ticks: { font: { size: 11 } },
                                    grid: { display: false }
                                }
                            },
                            elements: {
                                line: { borderWidth: 2, tension: 0 },
                                point: { radius: 3, hoverRadius: 7, hitRadius: 10 }
                            },
                            hover: { mode: 'nearest', intersect: true, animationDuration: 150 }
                        }
                    });
                }
            }
        });
    }

    cambiarVisualizacion(visualizacionActual);
});

// Exponer funciones globales
window.toggleMateria = toggleMateria;
window.seleccionarTodasMaterias = seleccionarTodasMaterias;
window.actualizarGrafico = actualizarGrafico;
window.cambiarVisualizacion = cambiarVisualizacion;
window.convertirNotaCualitativo = convertirNotaCualitativo;
</script>

@endsection
