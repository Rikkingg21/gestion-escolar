@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card-body bg-light">
        <!-- Filtros -->
        <form id="pdfForm" action="" method="POST">
            @csrf
            <input type="hidden" name="tipo_pdf" id="tipoPdf" value="cuantitativo">

            <div class="row g-3 align-items-end mb-4">
                <!-- Periodo -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="anio" class="form-label fw-bold text-primary">
                        <i class="fas fa-calendar-alt me-2"></i>Año Académico
                    </label>
                    <select name="anio" id="anio" class="form-select border-2 border-primary shadow-sm" onchange="cambiarPeriodo(this.value)">
                        <option value="">-- Seleccione Año --</option>
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo['anio'] }}"
                                {{ $anio_param == $periodo['anio'] ? 'selected' : '' }}>
                                {{ $periodo['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Bimestre -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="sigla" class="form-label fw-bold text-success">
                        <i class="fas fa-chart-line me-2"></i>Bimestre
                    </label>
                    <select name="sigla" id="sigla" class="form-select border-2 border-success shadow-sm" onchange="cambiarBimestre(this.value)">
                        @foreach($bimestres_disponibles as $bimestre)
                            <option value="{{ $bimestre['sigla'] }}" {{ $sigla_param == $bimestre['sigla'] ? 'selected' : '' }}>
                                {{ $bimestre['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Botón PDF -->
                <div class="col-12 col-md-4 text-end">
                    <button type="button" class="btn btn-danger btn-lg w-100 shadow-lg py-2 mt-3 mt-md-0"
                            data-bs-toggle="modal" data-bs-target="#pdfModal">
                        <i class="fas fa-file-pdf me-2"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </form>

        <!-- Modal para seleccionar tipo de PDF -->
        <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="pdfModalLabel">
                            <i class="fas fa-file-pdf me-2"></i>Seleccionar Formato del PDF
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-4">¿En qué formato desea generar el reporte de calificaciones?</p>

                        <div class="row g-3">
                            <!-- Opción Cuantitativo -->
                            <div class="col-md-6">
                                <div class="card h-100 border-2 border-primary option-card"
                                    data-tipo="cuantitativo" onclick="seleccionarTipo('cuantitativo')">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-calculator fa-3x text-primary"></i>
                                        </div>
                                        <h5 class="card-title fw-bold text-primary">Cuantitativo</h5>
                                        <p class="card-text text-muted small">Calificaciones en números (1-4)</p>
                                        <div class="mt-2"><span class="badge bg-primary">Ejemplo: 3.5</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Opción Cualitativo -->
                            <div class="col-md-6">
                                <div class="card h-100 border-2 border-success option-card"
                                    data-tipo="cualitativo" onclick="seleccionarTipo('cualitativo')">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-chart-bar fa-3x text-success"></i>
                                        </div>
                                        <h5 class="card-title fw-bold text-success">Cualitativo</h5>
                                        <p class="card-text text-muted small">Calificaciones en letras (A-D)</p>
                                        <div class="mt-2"><span class="badge bg-success">Ejemplo: A</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>
                                <strong>Cuantitativo:</strong> Escala numérica del 1 al 4<br>
                                <strong>Cualitativo:</strong> C = 1, B = 2, A = 3, AD = 4
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="btnGenerarPdf" onclick="generarPdf()" disabled>
                            <i class="fas fa-download me-2"></i>Generar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Datos del estudiante -->
        @if($matricula_actual && $periodo_actual)
        <div class="table border border-2 border-dark rounded-3 p-4 mb-4 bg-white">
            <div class="text-center mb-4">
                <div class="h3 fw-bold text-primary border-bottom border-2 border-primary pb-2">
                    LIBRETA DE CALIFICACIONES DEL ESTUDIANTE (sec EBR)
                </div>
                <div class="h5 fw-bold text-success mt-2">
                    {{ $periodo_actual['nombre'] }} -
                    @if($sigla_param == 'anual')
                        EVALUACIÓN ANUAL
                    @else
                        {{ strtoupper($sigla_param) }}
                    @endif
                </div>
            </div>

            <div class="row align-items-center">
                <!-- Logo -->
                <div class="col-sm-3 text-center border-end border-2 border-dark pe-3">
                    @if($colegio->logo_path)
                    <img src="{{ Storage::url($colegio->logo_path) }}" alt="Logo" style="height: 300px" class="img-thumbnail border-0">
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
                            <tr class="border-bottom border-1 border-secondary">
                                <td width="35%" class="border-1 fw-bold text-dark ps-0">UGEL:</td>
                                <td class="border-1 text-dark"><strong>{{ $colegio->ugel ?? 'Tacna' }}</strong></td>
                            </tr>
                            <tr class="border-bottom border-1 border-secondary">
                                <td class="border-1 fw-bold text-dark ps-0">II.EE:</td>
                                <td class="border-1 text-dark"><strong>{{ $colegio->nombre ?? 'NO REGISTRADO' }}</strong></td>
                            </tr>
                            <tr class="border-bottom border-1 border-secondary">
                                <td class="border-1 fw-bold text-dark ps-0">NIVEL:</td>
                                <td class="border-1 text-dark"><strong>{{ $matricula_actual->grado->nivel ?? 'No disponible' }}</strong></td>
                            </tr>
                            <tr class="border-bottom border-1 border-secondary">
                                <td class="border-1 fw-bold text-dark ps-0">GRADO:</td>
                                <td class="border-1 text-dark"><strong>{{ $matricula_actual->grado->grado ?? 'No disponible' }}°</strong></td>
                            </tr>
                            <tr class="border-bottom border-1 border-secondary">
                                <td class="border-1 fw-bold text-dark ps-0">SECCIÓN:</td>
                                <td class="border-1 text-dark"><strong>"{{ $matricula_actual->grado->seccion ?? 'No disponible' }}"</strong></td>
                            </tr>
                            <tr class="border-bottom border-1 border-secondary">
                                <td class="border-1 fw-bold text-dark ps-0">ESTUDIANTE:</td>
                                <td class="border-1 text-dark">
                                    <strong class="text-primary">
                                        {{ $estudiante->user->apellido_paterno }}
                                        {{ $estudiante->user->apellido_materno }},
                                        {{ $estudiante->user->nombre }}
                                    </strong>
                                </td>
                            </tr>
                            <tr class="border-bottom border-1 border-secondary">
                                <td class="border-1 fw-bold text-dark ps-0">DNI:</td>
                                <td class="border-1 text-dark"><strong>{{ $estudiante->user->dni }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODO BIMESTRE (detallado con CRIT y N) -->
        @if($sigla_param != 'anual' && count($materias) > 0)
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    Calificaciones Académicas - {{ strtoupper($sigla_param) }}
                </h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="switchCualitativo" checked>
                    <label class="form-check-label fw-bold" for="switchCualitativo">
                        <span id="labelSwitchCualitativo">Cuantitativo</span>
                    </label>
                </div>
            </div>

            <div class="card mb-4 border shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Calificaciones Regulares</h5>
                    @php
                        $promedioGeneral = 0;
                        $totalMaterias = 0;
                        foreach($materias as $materia) {
                            if($materia['promedio']) {
                                $promedioGeneral += $materia['promedio'];
                                $totalMaterias++;
                            }
                        }
                        $promedioGeneral = $totalMaterias > 0 ? round($promedioGeneral / $totalMaterias, 1) : 0;
                    @endphp
                    @if($promedioGeneral > 0)
                    <div class="badge bg-success fs-6">Promedio General: {{ number_format($promedioGeneral, 1) }}</div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-primary">
                            <tr class="text-center align-middle">
                                <th style="width: 18%;" class="fw-bold">MATERIA</th>
                                <th style="width: 22%;" class="fw-bold">COMPETENCIA</th>
                                <th style="width: 35%;" class="fw-bold">CRITERIOS DE EVALUACIÓN</th>
                                <th style="width: 10%;" class="fw-bold">CRIT.</th>
                                <th style="width: 15%;" class="fw-bold">VALOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $contadorC = 0; // Contador para C1, C2, C3...
                                $contadorN = 0; // Contador para N1, N2, N3...
                            @endphp

                            @foreach($materias as $materia)
                                @php
                                    $materiaRowspan = 0;
                                    // Calcular rowspan: suma de todos los criterios + número de competencias
                                    foreach($materia['competencias'] as $competencia) {
                                        $materiaRowspan += count($competencia['criterios']) + 1; // +1 por la fila de valoración
                                    }
                                @endphp

                                @foreach($materia['competencias'] as $compIndex => $competencia)
                                    @php $criteriosCount = count($competencia['criterios']); @endphp

                                    @foreach($competencia['criterios'] as $criterioIndex => $criterio)
                                        @php $contadorC++; @endphp
                                        <tr>
                                            <!-- Columna Materia (solo en el primer criterio de la primera competencia) -->
                                            @if($compIndex === 0 && $criterioIndex === 0)
                                            <td rowspan="{{ $materiaRowspan }}" class="align-middle bg-light text-center fw-bold text-primary">
                                                {{ $materia['nombre'] }}
                                            </td>
                                            @endif

                                            <!-- Columna Competencia (solo en el primer criterio de cada competencia) -->
                                            @if($criterioIndex === 0)
                                            <td rowspan="{{ $criteriosCount + 1 }}" class="align-middle bg-success bg-opacity-10 fw-semibold text-success">
                                                {{ $competencia['nombre'] }}
                                            </td>
                                            @endif

                                            <!-- Columna Criterio -->
                                            <td class="align-middle">{{ $criterio['nombre'] }}</td>

                                            <!-- Columna CRIT -->
                                            <td class="text-center align-middle fw-bold text-info">C{{ $contadorC }}</td>

                                            <!-- Columna VALOR -->
                                            <td class="text-center align-middle fw-bold">
                                                @if($criterio['nota'])
                                                    <span class="px-3 py-1 nota-valor" data-original="{{ $criterio['nota'] }}">
                                                        {{ $criterio['nota'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    <!-- Fila de VALORACIÓN DE COMPETENCIA -->
                                    @php $contadorN++; @endphp
                                    <tr class="bg-warning bg-opacity-10">
                                        <td class="align-middle fw-bold text-warning">
                                            <i class="fas fa-star me-2"></i>VALORACIÓN DE COMPETENCIA
                                        </td>
                                        <td class="text-center align-middle fw-bold text-success">N{{ $contadorN }}</td>
                                        <td class="text-center align-middle fw-bold">
                                            @if($competencia['promedio'])
                                                <span class="px-3 py-1 nota-promedio" data-original="{{ $competencia['promedio'] }}">
                                                    {{ number_format($competencia['promedio'], 1) }}
                                                </span>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="5" class="text-center py-2">
                                    <small class="text-muted">
                                        <strong>Nota:</strong> Escala de calificación: 1-4 (C=1, B=2, A=3, AD=4)
                                    </small>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif
        <!-- MODO ANUAL (compacto) -->
        @if($sigla_param == 'anual' && count($materias) > 0)
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Calificaciones Académicas - Promedio Anual</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="switchCualitativo" checked>
                    <label class="form-check-label fw-bold" for="switchCualitativo">
                        <span id="labelSwitchCualitativo">Cuantitativo</span>
                    </label>
                </div>
            </div>

            <div class="card mb-4 border shadow-sm">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Promedios por Competencia</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-primary">
                            <tr class="text-center">
                                <th style="width: 25%;">MATERIA</th>
                                <th style="width: 55%;">COMPETENCIA</th>
                                <th style="width: 20%;">PROMEDIO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materias as $materia)
                                @php $compCount = count($materia['competencias']); @endphp
                                @foreach($materia['competencias'] as $compIndex => $competencia)
                                    <tr>
                                        @if($compIndex === 0)
                                        <td rowspan="{{ $compCount }}" class="align-middle fw-bold text-primary bg-light">
                                            {{ $materia['nombre'] }}
                                        </td>
                                        @endif
                                        <td>{{ $competencia['nombre'] }}</td>
                                        <td class="text-center fw-bold">
                                            @if($competencia['promedio'])
                                                <span class="nota-promedio" data-original="{{ $competencia['promedio'] }}">
                                                    {{ number_format($competencia['promedio'], 1) }}
                                                </span>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- COMPETENCIAS TRANSVERSALES -->
        @php
            $competenciasTransversales = [];
            foreach($materias as $materia) {
                foreach($materia['competencias_transversales'] as $competencia) {
                    $competenciasTransversales[] = [
                        'materia' => $materia['nombre'],
                        'competencia' => $competencia['nombre'],
                        'promedio' => $competencia['promedio']
                    ];
                }
            }
        @endphp

        @if(count($competenciasTransversales) > 0)
        <div class="mt-4">
            <div class="border border-1 border-dark rounded-1 p-3">
                <h5 class="mb-3">
                    <i class="fas fa-chalkboard-user me-2"></i>Competencias Transversales
                </h5>
                <p class="text-muted small mb-3">
                    Las competencias transversales se evalúan de forma independiente y no se incluyen en el promedio regular.
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">MATERIA</th>
                                <th class="text-center">COMPETENCIA TRANSVERSAL</th>
                                <th class="text-center">PROMEDIO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($competenciasTransversales as $item)
                            <tr>
                                <td class="fw-bold">{{ $item['materia'] }}</td>
                                <td>{{ $item['competencia'] }}</td>
                                <td class="text-center fw-bold">
                                    @if($item['promedio'])
                                        <span class="nota-promedio" data-original="{{ $item['promedio'] }}">
                                            {{ number_format($item['promedio'], 1) }}
                                        </span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        @if(isset($promedioTransversales) && $promedioTransversales)
                        <tfoot class="table-info">
                            <tr>
                                <td colspan="2" class="text-end fw-bold">Promedio General de Competencias Transversales:</td>
                                <td class="text-center fw-bold">
                                    <span class="nota-promedio" data-original="{{ $promedioTransversales }}">
                                        {{ number_format($promedioTransversales, 1) }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Calificaciones de Conducta -->
        @if(isset($todas_las_conductas) && count($todas_las_conductas) > 0)
        <div class="mt-4">
            <div class="border border-1 border-dark rounded-1 p-3">
                <h5 class="mb-3">
                    <i class="fas fa-user-check me-2"></i>Calificaciones de Conducta
                </h5>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th style="width: 70%">CONDUCTA</th>
                                <th style="width: 30%">
                                    @if($sigla_param == 'anual')
                                        PROMEDIO ANUAL
                                    @else
                                        CALIFICACIÓN {{ strtoupper($sigla_param) }}
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todas_las_conductas as $conducta)
                            <tr>
                                <td>{{ $conducta['nombre'] }}</td>
                                <td class="text-center fw-bold">
                                    <span class="badge bg-secondary fs-6 p-2">
                                        {{ number_format($conducta['nota'], 1) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @else
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

<script>
function cambiarPeriodo(anio) {
    if (!anio) return;
    const siglaActual = document.getElementById('sigla').value;
    window.location.href = "{{ url('libreta') }}/" + anio + "/" + siglaActual;
}

function cambiarBimestre(sigla) {
    if (!sigla) return;
    const anioActual = document.getElementById('anio').value;
    if (anioActual) {
        window.location.href = "{{ url('libreta') }}/" + anioActual + "/" + sigla;
    }
}

// Convertir número a letra para modo cualitativo
function valorCualitativo(valor) {
    const num = parseFloat(valor);
    if (isNaN(num)) return valor;
    if (num >= 3.6) return 'AD';
    if (num >= 2.6) return 'A';
    if (num >= 1.6) return 'B';
    return 'C';
}

// Cambiar entre cuantitativo y cualitativo
document.addEventListener('DOMContentLoaded', function() {
    const switchCualitativo = document.getElementById('switchCualitativo');
    const labelSwitch = document.getElementById('labelSwitchCualitativo');

    function actualizarNotas() {
        const esCualitativo = !switchCualitativo.checked;

        document.querySelectorAll('.nota-valor').forEach(function(span) {
            const original = span.getAttribute('data-original');
            if (!original) {
                span.setAttribute('data-original', span.textContent.trim());
            }
            const valor = span.getAttribute('data-original');
            span.textContent = esCualitativo ? valorCualitativo(valor) : valor;
        });

        document.querySelectorAll('.nota-promedio').forEach(function(span) {
            const original = span.getAttribute('data-original');
            if (!original) {
                span.setAttribute('data-original', span.textContent.trim());
            }
            const valor = span.getAttribute('data-original');
            span.textContent = esCualitativo ? valorCualitativo(valor) : valor;
        });

        labelSwitch.textContent = esCualitativo ? 'Cualitativo' : 'Cuantitativo';
    }

    if (switchCualitativo) {
        switchCualitativo.addEventListener('change', actualizarNotas);
        actualizarNotas();
    }
});

// Modal PDF
let tipoSeleccionado = null;

function seleccionarTipo(tipo) {
    document.querySelectorAll('.option-card').forEach(card => {
        card.classList.remove('selected');
        card.style.backgroundColor = '#fff';
    });

    const card = document.querySelector(`.option-card[data-tipo="${tipo}"]`);
    card.classList.add('selected');
    card.style.backgroundColor = '#f0f8ff';

    tipoSeleccionado = tipo;
    document.getElementById('tipoPdf').value = tipo;
    document.getElementById('btnGenerarPdf').disabled = false;

    const btn = document.getElementById('btnGenerarPdf');
    if (tipo === 'cualitativo') {
        btn.className = 'btn btn-success';
        btn.innerHTML = '<i class="fas fa-download me-2"></i>Generar PDF Cualitativo';
    } else {
        btn.className = 'btn btn-primary';
        btn.innerHTML = '<i class="fas fa-download me-2"></i>Generar PDF Cuantitativo';
    }
}

function generarPdf() {
    if (!tipoSeleccionado) {
        alert('Por favor seleccione un formato para el PDF');
        return;
    }

    const btn = document.getElementById('btnGenerarPdf');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando...';
    btn.disabled = true;

    const modal = bootstrap.Modal.getInstance(document.getElementById('pdfModal'));
    modal.hide();

    setTimeout(() => {
        document.getElementById('pdfForm').submit();
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-download me-2"></i>Generar PDF';
            btn.disabled = false;
            tipoSeleccionado = null;
            document.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected');
                card.style.backgroundColor = '#fff';
            });
        }, 3000);
    }, 500);
}
</script>
@endsection
