@extends('layouts.app')

@section('content')
<div class="container-fluid mt-3">
    <div class="card border-2 border-primary rounded-3 mb-4">
        <div class="card-header bg-gradient-primary d-flex align-items-center py-3 border-bottom border-2 border-white">
            <h4 class="mb-0 text-black"><strong>Libreta de Calificaciones</strong></h4>
        </div>
        <div class="card-body bg-light">
            <form action="{{ route('libreta.pdf', ['anio' => $anio_param, 'bimestre' => $bimestre_param]) }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end mb-4">
                    <!-- Periodo -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="anio" class="form-label fw-bold text-primary">
                            <i class="fas fa-calendar-alt me-2"></i>Año Académico
                        </label>
                        <select name="anio" id="anio" class="form-select border-2 border-primary shadow-sm" onchange="cambiarPeriodo(this.value)">
                            <option value="">-- Seleccione Año --</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->anio }}"
                                    {{ $anio_param == $periodo->anio ? 'selected' : '' }}>
                                    {{ $periodo->anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bimestre -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="bimestre" class="form-label fw-bold text-success">
                            <i class="fas fa-chart-line me-2"></i>Bimestre
                        </label>
                        <select name="bimestre" id="bimestre" class="form-select border-2 border-success shadow-sm" onchange="cambiarBimestre(this.value)">
                            <option value="anual">Anual</option>
                            @for($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ $bimestre_param == $i ? 'selected' : '' }}>
                                    Bimestre {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <!-- Botón PDF -->
                    <div class="col-12 col-md-4 text-end">
                        <button type="submit" class="btn btn-danger btn-lg w-100 shadow-lg py-2 mt-3 mt-md-0">
                            <i class="fas fa-file-pdf me-2"></i> Descargar PDF
                        </button>
                    </div>
                </div>
            </form>

            <!-- SECCIÓN: Datos del estudiante en el periodo -->
            @if($matricula_actual && $periodo_actual)
            <div class="container border border-2 border-dark rounded-3 p-4 mb-4 bg-white">
                <!-- Encabezado de la libreta -->
                <div class="text-center mb-4">
                    <div class="h3 fw-bold text-primary border-bottom border-2 border-primary pb-2">
                        LIBRETA DE CALIFICACIONES DEL ESTUDIANTE (sec EBR)
                    </div>
                    <div class="h5 fw-bold text-success mt-2">
                        {{ $periodo_actual->anio }} -
                        @if($bimestre_param == 'anual')
                            EVALUACIÓN ANUAL
                        @else
                            {{ $bimestre_param }}° BIMESTRE
                        @endif
                    </div>
                </div>

                <!-- Contenido de la libreta -->
                <div class="row align-items-center">
                    <!-- Logo -->
                    <div class="col-sm-3 text-center border-end border-2 border-dark pe-3">
                        @if($colegio->logo_path)
                        <img src="{{ Storage::url($colegio->logo_path) }}" alt="Logo del colegio"
                                style="" class="img-thumbnail border-0">
                        @else
                        <div class="border border-2 border-secondary rounded-3 p-4 mb-3 bg-light">
                            <i class="fas fa-school fa-3x text-muted"></i>
                            <div class="mt-2 text-muted small">LOGO</div>
                        </div>
                        @endif
                    </div>

                    <!-- Datos -->
                    <div class="col-sm-9 ps-4">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0 text-center">
                                <!-- UGEL -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td width="35%" class="border-1 fw-bold text-dark ps-0">
                                        UGEL:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $colegio->ugel ?? 'Tacna' }}</strong>
                                    </td>
                                </tr>
                                <!-- II.EE -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        II.EE:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $colegio->nombre ?? 'NO REGISTRADO' }}</strong>
                                    </td>
                                </tr>
                                <!-- NIVEL -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        NIVEL:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $matricula_actual->grado->nivel ?? 'No disponible' }}</strong>
                                    </td>
                                </tr>
                                <!-- GRADO -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        GRADO:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $matricula_actual->grado->grado ?? 'No disponible' }}°</strong>
                                    </td>
                                </tr>
                                <!-- SECCIÓN -->
                                <tr class="border-bottom border-1 border-secondary">
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        SECCIÓN:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>"{{ $matricula_actual->grado->seccion ?? 'No disponible' }}"</strong>
                                    </td>
                                </tr>
                                <!-- ESTUDIANTE -->
                                <tr>
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        ESTUDIANTE:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong class="text-primary">
                                            {{ $estudiante->user->apellido_paterno }}
                                            {{ $estudiante->user->apellido_materno }},
                                            {{ $estudiante->user->nombre }}
                                        </strong>
                                    </td>
                                </tr>
                                <!-- DNI ESTUDIANTE -->
                                <tr>
                                    <td class="border-1 fw-bold text-dark ps-0">
                                        DNI:
                                    </td>
                                    <td class="border-1 text-dark">
                                        <strong>{{ $estudiante->user->dni }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sección de Calificaciones de Materias -->
            @if($materias_con_jerarquia->count() > 0)
            <div class="mt-4">
                <div class="border border-2 border-success rounded-3 p-3">
                    <h5 class="fw-bold text-success mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Calificaciones Académicas
                    </h5>

                    @foreach($materias_con_jerarquia as $materia)
                    <div class="mb-4 border border-1 border-primary rounded-2 p-3 bg-light">
                        <!-- Materia -->
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-1 border-secondary">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-book text-primary me-2"></i>
                                <h6 class="fw-bold text-primary mb-0">{{ $materia['materia_nombre'] }}</h6>
                            </div>
                        </div>

                        <!-- Competencias y Criterios con Notas -->
                        @foreach($materia['competencias'] as $competencia)
                        <div class="ms-2 mb-3">
                            <!-- Competencia -->
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-bullseye text-success me-2"></i>
                                <span class="fw-semibold text-success">{{ $competencia['competencia_nombre'] }}</span>
                            </div>

                            <!-- Criterios con Notas -->
                            @if($competencia['criterios']->count() > 0)
                            <div class="ms-4">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        @foreach($competencia['criterios'] as $criterio)
                                        <tr class="border-bottom border-1 border-light">
                                            <td width="70%" class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-circle text-info me-2" style="font-size: 0.5rem;"></i>
                                                    <span class="text-dark">{{ $criterio['criterio_nombre'] }}</span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                @if($criterio['nota'])
                                                <span class="badge
                                                    @if($criterio['nota']['valor'] >= 13) bg-success
                                                    @elseif($criterio['nota']['valor'] >= 10) bg-warning
                                                    @else bg-danger
                                                    @endif fs-6">
                                                    {{ $criterio['nota']['valor'] }}
                                                </span>
                                                @if($criterio['nota']['bimestre'])
                                                <small class="text-muted ms-2">Bim {{ $criterio['nota']['bimestre'] }}</small>
                                                @endif
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Sección de Calificaciones de Conducta -->
            @if($notas_conducta->count() > 0)
            <div class="mt-4">
                <div class="border border-2 border-info rounded-3 p-3">
                    <h5 class="fw-bold text-info mb-3">
                        <i class="fas fa-user-check me-2"></i>Calificaciones de Conducta
                    </h5>

                    <div class="row">
                        @foreach($notas_conducta as $notaConducta)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="border border-1 border-secondary rounded-2 p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-dark mb-0">{{ $notaConducta['conducta_nombre'] }}</h6>
                                    <span class="badge
                                        @if($notaConducta['valor'] >= 13) bg-success
                                        @elseif($notaConducta['valor'] >= 10) bg-warning
                                        @else bg-danger
                                        @endif fs-6">
                                        {{ $notaConducta['valor'] }}
                                    </span>
                                </div>
                                @if($notaConducta['bimestre'])
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i> Bimestre {{ $notaConducta['bimestre'] }}
                                </small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            <!-- Sección de Asistencias -->
            @if($asistencias->count() > 0)
            <div class="mt-4">
                <div class="border border-2 border-warning rounded-3 p-3">
                    <h5 class="fw-bold text-warning mb-3">
                        <i class="fas fa-calendar-check me-2"></i>Registro de Asistencias
                    </h5>

                    <!-- Resumen de Asistencias -->
                    @if(count($resumen_asistencias['tipos']) > 0)
                    <div class="mb-4 border border-1 border-secondary rounded-2 p-3 bg-light">
                        <h6 class="fw-bold text-dark mb-3">Resumen General</h6>
                        <table class="table table-border text-center mb-4">
                            <thead>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                            </thead>
                            <tbody>
                                @foreach($resumen_asistencias['tipos'] as $tipo)
                                <tr>
                                    <td>{{ $tipo['tipo_nombre'] }}</td>
                                    <td>{{ $tipo['cantidad'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <!-- Lista Detallada de Asistencias -->
                    <div class="accordion mb-3" id="accordionAsistenciasDetalladas">
                        <div class="accordion-item border border-1 border-secondary rounded-2">
                            <h2 class="accordion-header" id="headingAsistenciasDetalladas">
                                <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAsistenciasDetalladas" aria-expanded="false" aria-controls="collapseAsistenciasDetalladas">
                                    Registros Detallados
                                </button>
                            </h2>
                            <div id="collapseAsistenciasDetalladas" class="accordion-collapse collapse" aria-labelledby="headingAsistenciasDetalladas" data-bs-parent="#accordionAsistenciasDetalladas">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive p-3">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="border-1">Fecha</th>
                                                    <th class="border-1">Hora</th>
                                                    <th class="border-1">Tipo</th>
                                                    <th class="border-1">Descripción</th>
                                                    <th class="border-1">Bimestre</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($asistencias as $asistencia)
                                                <tr class="border-bottom border-1 border-light">
                                                    <td class="border-1">
                                                        <i class="fas fa-calendar-day text-primary me-1"></i>
                                                        {{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}
                                                    </td>
                                                    <td class="border-1">
                                                        @if($asistencia->hora)
                                                        <i class="fas fa-clock text-info me-1"></i>
                                                        {{ \Carbon\Carbon::parse($asistencia->hora)->format('h:i A') }}
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="border-1">
                                                        <span class="badge
                                                            @if($asistencia->tipoasistencia)
                                                                @if(str_contains(strtolower($asistencia->tipoasistencia->nombre), 'tardanza')) bg-warning
                                                                @elseif(str_contains(strtolower($asistencia->tipoasistencia->nombre), 'falta')) bg-danger
                                                                @else bg-success
                                                                @endif
                                                            @else bg-secondary
                                                            @endif">
                                                            {{ $asistencia->tipoasistencia->nombre ?? 'Sin tipo' }}
                                                        </span>
                                                    </td>
                                                    <td class="border-1">
                                                        @if($asistencia->descripcion)
                                                        <small>{{ $asistencia->descripcion }}</small>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="border-1">
                                                        <span class="badge bg-info">Bim {{ $asistencia->bimestre }}</span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                         <a href="{{ route('asistencia.calendario', ['anio' => $anio_param, 'bimestre' => $bimestre_param]) }}">Ver más detalles aquí</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($matricula_actual)
            <!-- Mensaje si no hay asistencias pero sí matrícula -->
            <div class="mt-4">
                <div class="alert alert-warning border-2 border-warning">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-times fa-2x me-3 text-warning"></i>
                        <div>
                            <h5 class="mb-1 text-warning">No hay registros de asistencia</h5>
                            <p class="mb-0">No se encontraron registros de asistencia para el periodo seleccionado.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @else
            <!-- Mensaje si no hay matrícula -->
            <div class="alert alert-warning border border-2 border-warning rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                    <div>
                        <h5 class="mb-1 text-warning">No se encontró matrícula</h5>
                        <p class="mb-0">El estudiante no tiene matrícula registrada para el año {{ $anio_param }}.</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function cambiarPeriodo(anio) {
    if (!anio) return;
    const baseUrl = "{{ route('libreta.index', ['anio' => 'ANIO_PLACEHOLDER', 'bimestre' => '1']) }}";
    const url = baseUrl.replace('ANIO_PLACEHOLDER', anio);
    window.location.href = url;
}

function cambiarBimestre(bimestre) {
    if (!bimestre) return;
    const baseUrl = "{{ route('libreta.index', ['anio' => $anio_param, 'bimestre' => 'BIMESTRE_PLACEHOLDER']) }}";
    const url = baseUrl.replace('BIMESTRE_PLACEHOLDER', bimestre);
    window.location.href = url;
}
</script>
@endsection
