@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Encabezado y Filtros -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-3">
                        <i class="fas fa-hand-holding-heart"></i> Dashboard Apoderado
                        <small class="text-muted">- {{ $infoApoderado['nombre_completo'] }}</small>
                    </h1>
                    <form method="GET" action="{{ request()->url() }}" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Período Escolar</label>
                            <select name="periodo_id" class="form-select" onchange="this.form.submit()">
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->id }}"
                                        {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                        {{ $periodo->anio }}
                                        @if($periodo->estado == 1) (Activo) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Bimestre</label>
                            <select name="bimestre" class="form-select" onchange="this.form.submit()">
                                <option value="anual" {{ $bimestreFiltro == 'anual' ? 'selected' : '' }}>Promedio Anual</option>
                                @php
                                    $bimestresRegulares = \App\Models\Periodobimestre::where('periodo_id', $periodoSeleccionado->id)
                                        ->where('tipo_bimestre', 'A')
                                        ->orderBy('bimestre')
                                        ->get();
                                @endphp
                                @foreach($bimestresRegulares as $bim)
                                    <option value="{{ $bim->sigla }}" {{ $bimestreFiltro == $bim->sigla ? 'selected' : '' }}>
                                        {{ $bim->sigla }} - {{ $bim->nombre ?? $bim->bimestre . '° Bimestre' }}
                                    </option>
                                @endforeach
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

    <!-- Información del apoderado -->
    <div class="card mb-4 shadow">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">
                <i class="fas fa-user-tie me-2"></i>
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
            <i class="fas fa-info-circle me-2"></i>
            No hay estudiantes asignados.
        </div>
    @else
        @foreach($datosEstudiantes as $estudianteIndex => $estudianteData)
            <!-- Tarjeta por cada estudiante -->
            <div class="card mb-5 shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-user-graduate me-2"></i>
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
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $estudianteData['mensaje'] }}
                        </div>
                    @elseif(($estudianteData['total_cursos'] ?? 0) == 0 && ($estudianteData['total_conducta'] ?? 0) == 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay notas registradas para este período.
                        </div>
                    @else
                        <!-- Pestañas para Notas y Conducta -->
                        <ul class="nav nav-tabs mb-4" id="estudianteTabs{{ $estudianteIndex }}" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="notas-tab-{{ $estudianteIndex }}"
                                        data-bs-toggle="tab" data-bs-target="#notas-{{ $estudianteIndex }}"
                                        type="button" role="tab">
                                    <i class="fas fa-graduation-cap me-1"></i> Notas Académicas
                                    @if(($estudianteData['total_cursos'] ?? 0) > 0)
                                        <span class="badge bg-primary ms-1">{{ $estudianteData['total_cursos'] }}</span>
                                    @endif
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="conducta-tab-{{ $estudianteIndex }}"
                                        data-bs-toggle="tab" data-bs-target="#conducta-{{ $estudianteIndex }}"
                                        type="button" role="tab">
                                    <i class="fas fa-hand-peace me-1"></i> Conducta
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
                                    <!-- Resumen estadístico de notas -->
                                    <div class="row mb-4">
                                        @php
                                            $todasNotas = [];
                                            foreach($estudianteData['progreso_cursos'] as $curso) {
                                                $notasValidas = array_filter($curso['promedios'], function($n) { return $n !== null; });
                                                $todasNotas = array_merge($todasNotas, $notasValidas);
                                            }
                                            $promedioGeneral = count($todasNotas) > 0 ? round(array_sum($todasNotas) / count($todasNotas), 1) : null;

                                            $cursosAprobados = 0;
                                            $cursosReprobados = 0;
                                            foreach($estudianteData['progreso_cursos'] as $curso) {
                                                if ($curso['promedio_general'] !== null) {
                                                    if ($curso['promedio_general'] > 2) $cursosAprobados++;
                                                    else $cursosReprobados++;
                                                }
                                            }
                                        @endphp

                                        @if($promedioGeneral)
                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-success h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-success">Promedio General</div>
                                                    <div class="h3 mb-0">{{ $promedioGeneral }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-info h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-info">Cursos con notas</div>
                                                    <div class="h3 mb-0">{{ $estudianteData['total_cursos'] }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-success h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-success">Aprobados</div>
                                                    <div class="h3 mb-0">{{ $cursosAprobados }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-danger h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-danger">Reprobados</div>
                                                    <div class="h3 mb-0">{{ $cursosReprobados }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Gráfico (solo en modo anual) -->
                                    @if($bimestreFiltro == 'anual' && $estudianteData['total_cursos'] > 0)
                                    <div class="mb-4">
                                        <h5 class="mb-3">
                                            <i class="fas fa-chart-line me-2"></i> Progreso Académico
                                        </h5>
                                        <div style="height: 400px;">
                                            <canvas id="progresoChart{{ $estudianteIndex }}"></canvas>
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
                                                        @php
                                                            $siglasBimestres = array_keys($estudianteData['progreso_cursos'][0]['promedios'] ?? []);
                                                        @endphp
                                                        @foreach($siglasBimestres as $sigla)
                                                            <th>{{ $sigla }}</th>
                                                        @endforeach
                                                    @endif
                                                    <th>Promedio</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($estudianteData['progreso_cursos'] as $curso)
                                                <tr>
                                                    <td class="fw-bold">{{ $curso['curso'] }}</td>
                                                    @if($bimestreFiltro == 'anual')
                                                        @foreach($curso['promedios'] as $sigla => $promedio)
                                                        <td class="text-center">
                                                            @if($promedio !== null)
                                                                <span class="badge bg-{{ $promedio > 3 ? 'success' : ($promedio > 2 ? 'warning' : 'danger') }} fs-6">
                                                                    {{ $promedio }}
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary">--</span>
                                                            @endif
                                                        </td>
                                                        @endforeach
                                                    @endif
                                                    <td class="text-center">
                                                        @if($curso['promedio_general'] !== null)
                                                            <span class="badge bg-{{ $curso['promedio_general'] > 3 ? 'success' : ($curso['promedio_general'] > 2 ? 'warning' : 'danger') }} fs-6">
                                                                {{ $curso['promedio_general'] }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">--</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($curso['promedio_general'] !== null)
                                                            @if($curso['promedio_general'] > 2)
                                                                <span class="badge bg-success">Aprobado</span>
                                                            @else
                                                                <span class="badge bg-danger">Reprobado</span>
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
                                        No hay notas académicas registradas para este estudiante.
                                    </div>
                                @endif
                            </div>

                            <!-- Pestaña de Conducta -->
                            <div class="tab-pane fade" id="conducta-{{ $estudianteIndex }}" role="tabpanel">
                                @if(($estudianteData['total_conducta'] ?? 0) > 0)
                                    <!-- Resumen estadístico de conducta -->
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
                                            <div class="card border-left-primary h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-primary">Promedio Conducta</div>
                                                    <div class="h3 mb-0">{{ $promedioConductaGeneral }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-info h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-info">Áreas Evaluadas</div>
                                                    <div class="h3 mb-0">{{ $estudianteData['total_conducta'] }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-success h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-success">Adecuada</div>
                                                    <div class="h3 mb-0">{{ $conductasAdecuadas }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <div class="card border-left-warning h-100">
                                                <div class="card-body">
                                                    <div class="text-xs font-weight-bold text-warning">Inadecuada</div>
                                                    <div class="h3 mb-0">{{ $conductasInadecuadas }}</div>
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
                                                    @if($bimestreFiltro == 'anual')
                                                        @php
                                                            $siglasConducta = array_keys($estudianteData['progreso_conducta'][0]['promedios'] ?? []);
                                                        @endphp
                                                        @foreach($siglasConducta as $sigla)
                                                            <th>{{ $sigla }}</th>
                                                        @endforeach
                                                    @endif
                                                    <th>Promedio</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($estudianteData['progreso_conducta'] as $conducta)
                                                <tr>
                                                    <td class="fw-bold">{{ $conducta['nombre'] }}</td>
                                                    @if($bimestreFiltro == 'anual')
                                                        @foreach($conducta['promedios'] as $sigla => $promedio)
                                                        <td class="text-center">
                                                            @if($promedio !== null)
                                                                <span class="badge bg-{{ $promedio > 3 ? 'success' : ($promedio > 2 ? 'warning' : 'danger') }} fs-6">
                                                                    {{ $promedio }}
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary">--</span>
                                                            @endif
                                                        </td>
                                                        @endforeach
                                                    @endif
                                                    <td class="text-center">
                                                        @if($conducta['promedio_general'] !== null)
                                                            <span class="badge bg-{{ $conducta['promedio_general'] > 3 ? 'success' : ($conducta['promedio_general'] > 2 ? 'warning' : 'danger') }} fs-6">
                                                                {{ $conducta['promedio_general'] }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">--</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($conducta['promedio_general'] !== null)
                                                            @if($conducta['promedio_general'] > 2)
                                                                <span class="badge bg-success">Adecuado</span>
                                                            @else
                                                                <span class="badge bg-danger">Inadecuado</span>
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
    document.addEventListener('DOMContentLoaded', function() {
        const datosEstudiantes = @json($datosEstudiantes);
        const bimestreFiltro = @json($bimestreFiltro);

        if (bimestreFiltro === 'anual') {
            const colores = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8AC926', '#1982C4'];

            datosEstudiantes.forEach((estudiante, index) => {
                if (estudiante.progreso_cursos && estudiante.progreso_cursos.length > 0) {
                    // Obtener las siglas de los bimestres desde el primer curso
                    const siglasBimestres = Object.keys(estudiante.progreso_cursos[0].promedios);

                    const datasets = estudiante.progreso_cursos.map((curso, cursoIndex) => ({
                        label: curso.curso,
                        data: siglasBimestres.map(sigla => curso.promedios[sigla]),
                        borderColor: colores[cursoIndex % colores.length],
                        tension: 0,
                        fill: false
                    }));

                    const config = {
                        type: 'line',
                        data: {
                            labels: siglasBimestres,
                            datasets: datasets.filter(d => d.data.some(v => v !== null))
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

                    const canvasId = document.getElementById(`progresoChart${index}`);
                    if (canvasId) {
                        new Chart(canvasId, config);
                    }
                }
            });
        }
    });
</script>
@endsection
