@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card-body bg-light">
        <form id="pdfForm" action="" method="POST">
            @csrf
            <input type="hidden" name="tipo_pdf" id="tipoPdf" value="cuantitativo">

            <div class="row g-3 align-items-end mb-4">
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="anio" class="form-label fw-bold text-primary">
                        <i class="fas fa-calendar-alt me-2"></i>Año Académico
                    </label>
                    <select name="anio" id="anio" class="form-select border-2 border-primary shadow-sm" onchange="cambiarPeriodo(this.value)">
                        <option value="">-- Seleccione Año --</option>
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo['anio'] }}" {{ $anio_param == $periodo['anio'] ? 'selected' : '' }}>
                                {{ $periodo['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

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

                <div class="col-12 col-md-4 text-end">
                    <button type="button" class="btn btn-danger btn-lg w-100 shadow-lg py-2 mt-3 mt-md-0"
                            data-bs-toggle="modal" data-bs-target="#pdfModal">
                        <i class="fas fa-file-pdf me-2"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </form>
        <!-- pdf-->

        @if($matricula_actual && $periodo_actual)
            <div class="table border border-2 border-dark rounded-3 p-4 mb-4 bg-white">
                <div class="text-center mb-4">
                    <div class="h3 fw-bold text-primary border-bottom border-2 border-primary pb-2">
                        LIBRETA DE CALIFICACIONES DEL ESTUDIANTE (sec EBR)
                    </div>
                    <div class="h5 fw-bold text-success mt-2">
                        {{ $periodo_actual['nombre'] }} - {{ $titulo_periodo }}
                    </div>
                </div>

                <div class="row align-items-center">
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

                    <div class="col-sm-9 ps-4">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0 text-center">
                                @foreach($datos_estudiante as $label => $value)
                                    <tr class="border-bottom border-1 border-secondary">
                                        <td width="35%" class="fw-bold text-dark ps-0">{{ $label }}:</td>
                                        <td class="text-dark"><strong>{{ $value }}</strong></td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if($sigla_param != 'anual' && count($materias) > 0)
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Calificaciones Académicas - {{ strtoupper($sigla_param) }}</h5>
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
                        @if($promedio_general_bimestre > 0)
                            <div class="badge bg-success fs-6">Promedio General: {{ number_format($promedio_general_bimestre, 1) }}</div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-primary">
                                <tr class="text-center align-middle">
                                    <th style="width: 18%;">MATERIA</th>
                                    <th style="width: 22%;">COMPETENCIA</th>
                                    <th style="width: 35%;">CRITERIOS DE EVALUACIÓN</th>
                                    <th style="width: 10%;">CRIT.</th>
                                    <th style="width: 15%;">VALOR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $contadorC = 0; $contadorN = 0; @endphp
                                @foreach($materias as $materia)
                                    @php
                                        $rowspan = 0;
                                        // Solo contar competencias regulares (no transversales)
                                        foreach($materia['competencias'] as $competencia) {
                                            if(count($competencia['criterios']) > 0) {
                                                $rowspan += count($competencia['criterios']) + 1;
                                            }
                                        }
                                    @endphp

                                    @foreach($materia['competencias'] as $compIndex => $competencia)
                                        @php
                                            $criteriosCount = count($competencia['criterios']);
                                            // Saltar competencias sin criterios
                                            if($criteriosCount == 0) continue;
                                        @endphp

                                        @foreach($competencia['criterios'] as $criterioIndex => $criterio)
                                            @php $contadorC++; @endphp
                                            <tr>
                                                @if($compIndex === 0 && $criterioIndex === 0)
                                                    <td rowspan="{{ $rowspan }}" class="align-middle bg-light text-center fw-bold text-primary">
                                                        {{ $materia['nombre'] }}
                                                    </td>
                                                @endif

                                                @if($criterioIndex === 0)
                                                    <td rowspan="{{ $criteriosCount + 1 }}" class="align-middle bg-success bg-opacity-10 fw-semibold text-success">
                                                        {{ $competencia['nombre'] }}
                                                    </td>
                                                @endif

                                                <td class="align-middle">{{ $criterio['nombre'] }}</td>
                                                <td class="text-center align-middle fw-bold text-info">C{{ $contadorC }}</td>
                                                <td class="text-center align-middle fw-bold">
                                                    @if($criterio['tiene_nota'])
                                                        <span class="nota-valor" data-valor="{{ $criterio['nota'] }}">
                                                            {{ $criterio['nota'] }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach

                                        @php $contadorN++; @endphp
                                        <tr class="bg-warning bg-opacity-10">
                                            <td class="align-middle fw-bold text-warning">
                                                <i class="fas fa-star me-2"></i>VALORACIÓN DE COMPETENCIA
                                            </td>
                                            <td class="text-center align-middle fw-bold text-success">N{{ $contadorN }}</td>
                                            <td class="text-center align-middle fw-bold">
                                                @if($competencia['promedio'])
                                                    <span class="nota-promedio" data-valor="{{ $competencia['promedio'] }}">
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
                                                        <span class="nota-promedio" data-valor="{{ $competencia['promedio'] }}">
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

            @if(count($competencias_transversales_agrupadas) > 0)
            <div class="mt-4">
                <div class="border border-1 border-dark rounded-1 p-3">
                    <h5 class="mb-3">
                        <i class="fas fa-chalkboard-user me-2"></i>Competencias Transversales
                    </h5>
                    <p class="text-muted small mb-3">
                        Promedio de cada criterio transversal evaluado en todas las materias.
                    </p>

                    <div>
                        <table class="table table-bordered mb-0 mt-3">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th style="width: 70%">CRITERIO TRANSVERSAL</th>
                                    <th style="width: 30%">PROMEDIO GENERAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($competencias_transversales_agrupadas as $item)
                                    <tr>
                                        <td class="fw-bold">
                                            <i class="fas fa-check-circle text-info me-2"></i>
                                            {{ $item['criterio'] }}
                                            @if($item['faltantes'] > 0)
                                                <span class="badge bg-warning text-dark ms-2">
                                                    {{ $item['materias_calificadas'] }}/{{ $item['total_materias'] }} materias
                                                </span>
                                            @else
                                                <span class="badge bg-success ms-2">
                                                    {{ $item['total_materias'] }}/{{ $item['total_materias'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">
                                            @if($item['promedio'] !== null)
                                                <span class="nota-promedio" data-valor="{{ $item['promedio'] }}">
                                                    {{ $item['promedio'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Detalle por materia y bimestre (expandible) -->
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#detalleTransversales" aria-expanded="false">
                            <i class="fas fa-table me-1"></i> Ver detalle por materia y bimestre
                        </button>

                        <div class="collapse mt-3" id="detalleTransversales">
                            <div class="card card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0 table-sm">
                                        <thead class="table-light">
                                            <tr class="text-center">
                                                <th>CRITERIO TRANSVERSAL</th>
                                                @if($sigla_param == 'anual')
                                                    <th>BIMESTRE</th>
                                                @endif
                                                <th>MATERIA</th>
                                                <th>NOTA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($competencias_transversales_agrupadas as $item)
                                                @foreach($item['detalle'] as $detalle)
                                                    <tr>
                                                        <td>{{ $item['criterio'] }}</td>
                                                        @if($sigla_param == 'anual')
                                                            <td class="text-center">
                                                                @if($detalle['sigla_bimestre'])
                                                                    <span class="badge bg-primary">{{ $detalle['sigla_bimestre'] }}</span>
                                                                @else
                                                                    <span class="text-muted">--</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        <td class="fw-bold">{{ $detalle['materia'] }}</td>
                                                        <td class="text-center">
                                                            @if($detalle['nota'] !== null)
                                                                @php
                                                                    $notaRedondeada = $detalle['nota'];
                                                                    $notaOriginal = $detalle['nota'];
                                                                @endphp
                                                                <span class="nota-valor" data-valor="{{ $notaOriginal }}">
                                                                    {{ $notaRedondeada }}
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
                    </div>
                </div>
            </div>
            @endif

            @if(count($todas_las_conductas) > 0)
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
                                        <th style="width: 30%">{{ $titulo_conducta }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todas_las_conductas as $conducta)
                                        <tr>
                                            <td>
                                                {{ $conducta['nombre'] }}
                                                @if($conducta['tiene_tooltip'])
                                                    <i class="bi bi-info-circle text-info ms-2" style="cursor: help;" title="{{ $conducta['estado'] }}"></i>
                                                @endif
                                            </td>
                                            <td class="text-center fw-bold">
                                                <span class="nota-conducta {{ $conducta['clase_color'] }}"
                                                    data-valor="{{ $conducta['nota_original'] }}"
                                                    data-es-guion="{{ $conducta['es_guion'] ? 'true' : 'false' }}">
                                                    {{ $conducta['nota'] }}
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
            <div>

            <div class="mt-4">
                <div class="border border-1 border-dark rounded-1 p-3">
                    <h5 class="mb-3">
                        <i class="fas fa-calendar-check me-2"></i>Asistencias
                        @if($sigla_param != 'anual')
                            <span class="badge bg-info ms-2">{{ strtoupper($sigla_param) }}</span>
                        @endif
                    </h5>

                    @if(count($asistencias) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th>TIPO DE ASISTENCIA</th>
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($asistencias as $asistencia)
                                        <tr>
                                            <td>
                                                <span class="badge" style="background-color: {{ $asistencia['color'] }}; color: white;">
                                                    {{ $asistencia['tipo'] }}
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold">
                                                {{ $asistencia['total'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay registros de asistencia para el período seleccionado.
                        </div>
                    @endif
                </div>
            </div>
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
const routes = {
    cambiarPeriodo: (anio, sigla) => "{{ url('libreta') }}/" + anio + "/" + sigla,
    cambiarBimestre: (anio, sigla) => "{{ url('libreta') }}/" + anio + "/" + sigla
};

function cambiarPeriodo(anio) {
    if (!anio) return;
    window.location.href = routes.cambiarPeriodo(anio, document.getElementById('sigla').value);
}

function cambiarBimestre(sigla) {
    if (!sigla) return;
    const anioActual = document.getElementById('anio').value;
    if (anioActual) window.location.href = routes.cambiarBimestre(anioActual, sigla);
}

function valorCualitativo(valor) {
    const num = parseFloat(valor);
    if (isNaN(num)) return valor;
    if (num >= 3.6) return 'AD';
    if (num >= 2.6) return 'A';
    if (num >= 1.6) return 'B';
    return 'C';
}

function inicializarTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        const instance = bootstrap.Tooltip.getInstance(el);
        if (instance) instance.dispose();
        new bootstrap.Tooltip(el, { trigger: 'hover focus' });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const switchCualitativo = document.getElementById('switchCualitativo');
    const labelSwitch = document.getElementById('labelSwitchCualitativo');

    function actualizarNotas() {
        const esCualitativo = !switchCualitativo.checked;

        document.querySelectorAll('.nota-valor, .nota-promedio, .nota-conducta').forEach(span => {
            const valor = span.getAttribute('data-valor') || span.textContent.trim();
            if (!span.hasAttribute('data-valor')) span.setAttribute('data-valor', valor);

            const esGuion = span.getAttribute('data-es-guion') === 'true';

            if (esGuion) {
                span.textContent = '-';
                span.classList.remove('text-danger', 'fw-bold');
                return;
            }

            if (esCualitativo) {
                const letra = valorCualitativo(valor);
                span.textContent = letra;
                span.classList.toggle('text-danger', letra === 'C' || letra === 'B');
                span.classList.toggle('fw-bold', letra === 'C' || letra === 'B');
            } else {
                const num = parseFloat(valor);
                const notaRedondeada = Math.round(num);
                span.textContent = notaRedondeada;
                span.classList.toggle('text-danger', notaRedondeada <= 2);
                span.classList.toggle('fw-bold', notaRedondeada <= 2);
            }
        });

        labelSwitch.textContent = esCualitativo ? 'Cualitativo' : 'Cuantitativo';
    }

    if (switchCualitativo) {
        switchCualitativo.addEventListener('change', actualizarNotas);
        actualizarNotas();
    }

    inicializarTooltips();
});

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
    const btn = document.getElementById('btnGenerarPdf');
    btn.disabled = false;

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

    bootstrap.Modal.getInstance(document.getElementById('pdfModal')).hide();

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

document.querySelectorAll('.option-card').forEach(card => {
    card.onclick = () => seleccionarTipo(card.dataset.tipo);
});
</script>
@endsection
