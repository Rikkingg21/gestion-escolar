@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Dashboard Director</h2>

    <!-- Selector de Periodo -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="periodoForm" method="GET" action="{{ route('dashboard.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="periodo_id" class="form-label">Seleccionar Periodo</label>
                        <select name="periodo_id" id="periodo_id" class="form-select">
                            <option value="">-- Seleccione un periodo --</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}"
                                    {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                    @if($periodo->estado == '1')
                                        <span class="badge bg-success ms-2">Activo</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        @if($periodoSeleccionado)
                            <a href="{{ route('dashboard.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($periodoSeleccionado)
        <div class="alert alert-info" id="periodoAlert">
            <strong>Periodo seleccionado:</strong> {{ $periodoSeleccionado->nombre }} ({{ $periodoSeleccionado->anio }})
            @if($periodoSeleccionado->descripcion)
                - {{ $periodoSeleccionado->descripcion }}
            @endif
        </div>
    @else
        <div class="alert alert-warning" id="periodoAlert">
            <strong>No hay periodo seleccionado.</strong> Por favor, seleccione un periodo para ver los datos.
        </div>
    @endif

    <!-- Cards de Estadísticas -->
    <div id="cardsContainer">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Grados</h5>
                        <p class="card-text display-6">{{ $estadisticas['total_grados'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Estudiantes Matriculados</h5>
                        <p class="card-text display-6">{{ $estadisticas['total_estudiantes'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Promedio General</h5>
                        <p class="card-text display-6">{{ $estadisticas['promedio_general'] }}</p>
                        <small class="text-muted">
                            Escala: 1.0 - 4.0
                            @if($estadisticas['promedio_general'] >= 3.0)
                                <span class="badge bg-success">Excelente</span>
                            @elseif($estadisticas['promedio_general'] >= 2.5)
                                <span class="badge bg-primary">Bueno</span>
                            @elseif($estadisticas['promedio_general'] >= 2.0)
                                <span class="badge bg-warning">Regular</span>
                            @else
                                <span class="badge bg-danger">Bajo</span>
                            @endif
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Estado</h5>
                        <p class="card-text">
                            @if($periodoSeleccionado)
                                @if($estadisticas['total_estudiantes'] > 0)
                                    <span class="badge bg-success">Con Datos</span>
                                @else
                                    <span class="badge bg-warning">Sin Matrículas</span>
                                @endif
                            @else
                                <span class="badge bg-secondary">Sin Periodo</span>
                            @endif
                        </p>
                        @if($periodoSeleccionado && $estadisticas['total_estudiantes'] > 0)
                            <small class="text-muted">
                                {{ $estadisticas['total_grados'] }} grados con {{ $estadisticas['total_estudiantes'] }} estudiantes
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Grados -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Rendimiento por Grado - Periodo:
                @if($periodoSeleccionado)
                    {{ $periodoSeleccionado->nombre }} ({{ $periodoSeleccionado->anio }})
                @else
                    Sin periodo seleccionado
                @endif
            </h4>
            @if($periodoSeleccionado && $grados->count() > 0)
                <div class="text-end">
                    <small class="text-muted me-3">
                        <i class="fas fa-info-circle"></i> Escala de notas: 1.0 - 4.0
                    </small>
                </div>
            @endif
        </div>
        <div class="card-body">
            <div id="tablaContainer">
                @if($periodoSeleccionado && $grados->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Grado</th>
                                    <th>Estudiantes</th>
                                    <th>Prom. Académico</th>
                                    <th>Prom. Conducta</th>
                                    <th>Prom. General</th>
                                    <th>Estado</th>
                                    <th>Nivel</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grados as $grado)
                                <tr>
                                    <td>
                                        <strong>{{ $grado->nombreCompleto ?? $grado->grado }}</strong>
                                        <br>
                                        <small class="text-muted">Código: {{ $grado->codigo ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info rounded-pill p-2">
                                            <i class="fas fa-users"></i> {{ $grado->estudiantes_matriculados }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 20px;">
                                                @php
                                                    $porcentajeNotas = min(($grado->promedio_notas / 4) * 100, 100);
                                                    $colorNotas = $grado->promedio_notas >= 3.0 ? 'bg-success' :
                                                                ($grado->promedio_notas >= 2.5 ? 'bg-primary' :
                                                                ($grado->promedio_notas >= 2.0 ? 'bg-warning' : 'bg-danger'));
                                                @endphp
                                                <div class="progress-bar {{ $colorNotas }}"
                                                     role="progressbar"
                                                     style="width: {{ $porcentajeNotas }}%"
                                                     aria-valuenow="{{ $grado->promedio_notas }}"
                                                     aria-valuemin="1"
                                                     aria-valuemax="4">
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <span class="fw-bold">{{ number_format($grado->promedio_notas, 2) }}</span>
                                                <br>
                                                <small class="text-muted">
                                                    @if($grado->promedio_notas >= 3.0)
                                                        <i class="fas fa-star text-warning"></i> Excelente
                                                    @elseif($grado->promedio_notas >= 2.5)
                                                        <i class="fas fa-check-circle text-primary"></i> Bueno
                                                    @elseif($grado->promedio_notas >= 2.0)
                                                        <i class="fas fa-exclamation-circle text-warning"></i> Regular
                                                    @else
                                                        <i class="fas fa-times-circle text-danger"></i> Bajo
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 20px;">
                                                @php
                                                    $porcentajeConducta = min(($grado->promedio_conducta / 4) * 100, 100);
                                                    $colorConducta = $grado->promedio_conducta >= 3.0 ? 'bg-success' :
                                                                   ($grado->promedio_conducta >= 2.5 ? 'bg-warning' :
                                                                   ($grado->promedio_conducta >= 2.0 ? 'bg-info' : 'bg-danger'));
                                                @endphp
                                                <div class="progress-bar {{ $colorConducta }}"
                                                     role="progressbar"
                                                     style="width: {{ $porcentajeConducta }}%"
                                                     aria-valuenow="{{ $grado->promedio_conducta }}"
                                                     aria-valuemin="1"
                                                     aria-valuemax="4">
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <span class="fw-bold">{{ number_format($grado->promedio_conducta, 2) }}</span>
                                                <br>
                                                <small class="text-muted">
                                                    @if($grado->promedio_conducta >= 3.0)
                                                        <i class="fas fa-smile text-success"></i> Excelente
                                                    @elseif($grado->promedio_conducta >= 2.5)
                                                        <i class="fas fa-meh text-warning"></i> Bueno
                                                    @elseif($grado->promedio_conducta >= 2.0)
                                                        <i class="fas fa-frown text-warning"></i> Regular
                                                    @else
                                                        <i class="fas fa-angry text-danger"></i> Bajo
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $colorGeneral = $grado->promedio_general >= 3.0 ? 'bg-success' :
                                                          ($grado->promedio_general >= 2.5 ? 'bg-primary' :
                                                          ($grado->promedio_general >= 2.0 ? 'bg-warning' : 'bg-danger'));
                                            $iconoGeneral = $grado->promedio_general >= 3.0 ? 'fa-trophy' :
                                                          ($grado->promedio_general >= 2.5 ? 'fa-medal' :
                                                          ($grado->promedio_general >= 2.0 ? 'fa-certificate' : 'fa-exclamation-triangle'));
                                        @endphp
                                        <div class="text-center">
                                            <span class="badge {{ $colorGeneral }} rounded-pill p-3">
                                                <i class="fas {{ $iconoGeneral }} me-1"></i>
                                                <strong>{{ number_format($grado->promedio_general, 2) }}</strong>
                                            </span>
                                            <br>
                                            <small class="text-muted mt-1 d-block">
                                                @if($grado->promedio_general >= 3.0)
                                                    <span class="text-success">Destacado</span>
                                                @elseif($grado->promedio_general >= 2.5)
                                                    <span class="text-primary">Satisfactorio</span>
                                                @elseif($grado->promedio_general >= 2.0)
                                                    <span class="text-warning">Aceptable</span>
                                                @else
                                                    <span class="text-danger">Necesita mejorar</span>
                                                @endif
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($grado->promedio_general >= 3.0)
                                            <span class="badge bg-success p-2">
                                                <i class="fas fa-trophy"></i> Excelente
                                            </span>
                                        @elseif($grado->promedio_general >= 2.5)
                                            <span class="badge bg-primary p-2">
                                                <i class="fas fa-check-circle"></i> Bueno
                                            </span>
                                        @elseif($grado->promedio_general >= 2.0)
                                            <span class="badge bg-warning p-2">
                                                <i class="fas fa-exclamation-circle"></i> Regular
                                            </span>
                                        @else
                                            <span class="badge bg-danger p-2">
                                                <i class="fas fa-times-circle"></i> Bajo
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge
                                            @if($grado->nivel == 'Primaria') bg-info
                                            @elseif($grado->nivel == 'Secundaria') bg-secondary
                                            @elseif($grado->nivel == 'Inicial') bg-pink
                                            @else bg-light text-dark @endif">
                                            {{ $grado->nivel }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td><strong>Promedios Totales</strong></td>
                                    <td>
                                        <strong>{{ $estadisticas['total_estudiantes'] }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($grados->avg('promedio_notas'), 2) }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Promedio académico
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($grados->avg('promedio_conducta'), 2) }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Promedio conducta
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($estadisticas['promedio_general'], 2) }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Promedio general
                                        </small>
                                    </td>
                                    <td colspan="2">
                                        @php
                                            $promedioFinal = $estadisticas['promedio_general'];
                                            $estadoFinal = $promedioFinal >= 3.0 ? 'Excelente' :
                                                         ($promedioFinal >= 2.5 ? 'Bueno' :
                                                         ($promedioFinal >= 2.0 ? 'Regular' : 'Bajo'));
                                            $colorFinal = $promedioFinal >= 3.0 ? 'success' :
                                                        ($promedioFinal >= 2.5 ? 'primary' :
                                                        ($promedioFinal >= 2.0 ? 'warning' : 'danger'));
                                        @endphp
                                        <span class="badge bg-{{ $colorFinal }} p-2">
                                            <i class="fas fa-chart-line"></i> {{ $estadoFinal }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Gráfico de resumen -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Distribución de Niveles</h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $niveles = $grados->groupBy('nivel');
                                    @endphp
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach($niveles as $nivel => $gradosNivel)
                                            <div class="text-center">
                                                <div class="display-6">{{ $gradosNivel->count() }}</div>
                                                <small class="text-muted">{{ $nivel }}</small>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Rendimiento General</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center">
                                        <div class="display-4 text-{{ $colorFinal }}">
                                            {{ number_format($estadisticas['promedio_general'], 2) }}
                                        </div>
                                        <small class="text-muted">Promedio general en escala 1.0 - 4.0</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($periodoSeleccionado)
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h5>No hay datos para este periodo</h5>
                        <p class="text-muted">No se encontraron grados con matrículas en el periodo seleccionado.</p>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                        <h5>Seleccione un periodo</h5>
                        <p class="text-muted">Por favor, seleccione un periodo para ver los datos.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Actualizar select de periodo automáticamente
    $('#periodo_id').change(function() {
        if ($(this).val()) {
            $('#periodoForm').submit();
        }
    });

    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Efectos visuales en las tablas
    $('table tbody tr').hover(
        function() {
            $(this).addClass('shadow-sm');
        },
        function() {
            $(this).removeClass('shadow-sm');
        }
    );
});
</script>
@endsection
