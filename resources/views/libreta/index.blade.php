@extends('layouts.app')
@section('title', 'Libreta de Notas')

@section('content')
<div class="container-fluid p-0">
    <div class="card-body bg-light p-3">
        <form id="pdfForm" action="{{ route('libreta.pdf') }}" method="POST" target="_blank">
            @csrf
            <input type="hidden" name="tipo_pdf" id="tipoPdf" value="cuantitativo">
            <input type="hidden" name="periodo_id" id="periodoIdHidden" value="{{ $periodo_id_param ?? '' }}">
            <input type="hidden" name="bimestre" id="bimestreHidden" value="{{ $sigla_param ?? 'anual' }}">

            <div class="row g-3 align-items-end mb-4">
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="periodo_id" class="form-label fw-bold text-primary">
                        <i class="bi bi-calendar3 me-2"></i>Período Escolar
                    </label>
                    <select name="periodo_id" id="periodo_id" class="form-select border-2 border-primary shadow-sm" onchange="cambiarPeriodo(this.value)">
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo['id'] }}" {{ ($periodo_actual['id'] ?? '') == $periodo['id'] ? 'selected' : '' }}>
                                {{ $periodo['nombre'] }} ({{ $periodo['anio'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-4">
                    <label for="bimestre" class="form-label fw-bold text-success">
                        <i class="bi bi-graph-up me-2"></i>Bimestre
                    </label>
                    <select name="bimestre" id="bimestre" class="form-select border-2 border-success shadow-sm" onchange="cambiarBimestre(this.value)">
                        @foreach($bimestres_disponibles as $bimestre)
                            <option value="{{ $bimestre['sigla'] }}" {{ $sigla_param == $bimestre['sigla'] ? 'selected' : '' }}>
                                {{ $bimestre['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4 text-end">
                    <button type="submit" class="btn btn-danger btn-lg w-100 shadow-lg py-2 mt-3 mt-md-0" id="btnDescargarPDF">
                        <i class="bi bi-download me-2"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </form>

        <!-- Switch de visualización -->
        <div class="d-flex justify-content-end mb-3">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary active" id="btnCuantitativo" onclick="cambiarVisualizacion('cuantitativo')">
                    <i class="bi bi-graph-up me-1"></i> Cuantitativo (Notas)
                </button>
                <button type="button" class="btn btn-outline-primary" id="btnCualitativo" onclick="cambiarVisualizacion('cualitativo')">
                    <i class="bi bi-tag me-1"></i> Cualitativo (AD/A/B/C)
                </button>
            </div>
        </div>

        @if($matricula_actual && $periodo_actual)
            <!-- Versión tipo boleta escolar tradicional - SIN MÁRGENES ENTRE BLOQUES -->
            <div class="border border-dark">
                <!-- Cabecera -->
                <div class="border-bottom border-dark p-2 bg-secondary text-white text-center">
                    <div class="h5 fw-bold mb-0">LIBRETA DE CALIFICACIONES DEL ESTUDIANTE (sec EBR)</div>
                    <div class="small">{{ $periodo_actual['nombre'] }} - {{ $titulo_periodo }}</div>
                </div>

                <!-- Cuerpo con tabla de datos -->
                <div class="row g-0">
                    <div class="col-sm-3 border-end border-dark p-3 d-flex align-items-center justify-content-center" style="min-height: 180px;">
                        @if($colegio->logo_path)
                            <img src="{{ Storage::url($colegio->logo_path) }}" alt="Logo" class="img-fluid" style="max-height: 250px; width: auto; object-fit: contain;">
                        @else
                            <div class="text-center">
                                <i class="bi bi-buildings fs-1 text-muted"></i>
                                <div class="small text-muted mt-1">LOGO</div>
                            </div>
                        @endif
                    </div>

                    <div class="col-sm-9 p-0">
                        <table class="table table-bordered mb-0 h-100" style="font-size: 0.9rem;">
                            @foreach($datos_estudiante as $label => $value)
                                <tr>
                                    <td class="fw-bold bg-light" style="width: 30%; padding: 8px 12px; vertical-align: middle;">{{ $label }}:</td>
                                    <td style="width: 70%; padding: 8px 12px; vertical-align: middle;"><strong>{{ $value }}</strong></td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>

            @if(isset($esPeriodoRecuperacion) && $esPeriodoRecuperacion)
                <!-- VISTA PARA RECUPERACIÓN - SIN MÁRGENES -->
                @if(isset($recuperaciones) && count($recuperaciones) > 0)
                    <div class="border border-dark border-top-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th style="width: 25%;">MATERIA</th>
                                        <th style="width: 40%;">COMPETENCIA</th>
                                        <th style="width: 10%;">NOTA ORIG.</th>
                                        <th style="width: 10%;">NOTA REC.</th>
                                        <th style="width: 15%;">ESTADO FINAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $materiasRecuperacion = [];
                                        foreach($recuperaciones as $rec) {
                                            if (!isset($materiasRecuperacion[$rec->materia_id])) {
                                                $materiasRecuperacion[$rec->materia_id] = [
                                                    'nombre' => $rec->materia->nombre ?? 'Sin materia',
                                                    'competencias' => []
                                                ];
                                            }
                                            $materiasRecuperacion[$rec->materia_id]['competencias'][] = $rec;
                                        }
                                    @endphp

                                    @foreach($materiasRecuperacion as $materiaId => $materiaData)
                                        @foreach($materiaData['competencias'] as $index => $rec)
                                            <tr>
                                                @if($index === 0)
                                                    <td rowspan="{{ count($materiaData['competencias']) }}" class="align-middle fw-bold bg-light">
                                                        {{ $materiaData['nombre'] }}
                                                    </td>
                                                @endif
                                                <td class="align-middle">{{ $rec->materiaCompetencia->nombre ?? 'Competencia' }}</td>
                                                <td class="text-center">
                                                    @php
                                                        $notaOriginal = $rec->nivel_logro_inicial;
                                                        $notaOriginalNumerica = match($notaOriginal) {
                                                            'AD' => 4, 'A' => 3, 'B' => 2, 'C' => 1, default => null
                                                        };
                                                    @endphp
                                                    {{ $notaOriginal ?? '--' }}
                                                    @if($notaOriginalNumerica)
                                                        <br><small>({{ $notaOriginalNumerica }})</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $notaFinal = $rec->nivel_logro_final;
                                                        $notaFinalNumerica = match($notaFinal) {
                                                            'AD' => 4, 'A' => 3, 'B' => 2, 'C' => 1, default => null
                                                        };
                                                        $mejoro = $notaFinalNumerica && $notaOriginalNumerica && $notaFinalNumerica > $notaOriginalNumerica;
                                                    @endphp
                                                    <strong class="{{ $mejoro ? 'text-success' : '' }}">{{ $notaFinal ?? '--' }}</strong>
                                                    @if($notaFinalNumerica)
                                                        <br><small>({{ $notaFinalNumerica }})</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @php $aprobado = $notaFinalNumerica && $notaFinalNumerica >= 2; @endphp
                                                    <strong class="{{ $aprobado ? 'text-success' : 'text-danger' }}">
                                                        {{ $aprobado ? 'APROBADO' : 'DESAPROBADO' }}
                                                    </strong>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="5" class="text-center py-2">
                                            <small>Escala: C=1 (Desaprobado) | B=2 (Aprobado) | A=3 (Sobresaliente) | AD=4 (Destacado)</small>
                                            <br>
                                            <small><i class="bi bi-arrow-repeat"></i> Las notas de recuperación reemplazan a las notas originales</small>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning rounded-0 border border-dark border-top-0">No hay registros de recuperación para este período.</div>
                @endif

            @else
                <!-- ==================== VISTA NORMAL - SIN MÁRGENES ==================== -->
                @if($sin_criterios ?? true)
                    <div class="alert alert-warning rounded-0 border border-dark border-top-0">
                        No hay criterios de evaluación registrados para el período seleccionado.
                    </div>
                @else
                    <!-- Calificaciones Regulares (Modo Bimestral) -->
                    @if($sigla_param != 'anual')
                        <div class="border border-dark border-top-0">
                            <div class="border-bottom border-dark p-1 bg-light text-center">
                                <strong>CALIFICACIONES REGULARES - {{ strtoupper($sigla_param) }}</strong>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
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
                                                foreach($materia['competencias'] as $competencia) {
                                                    if(count($competencia['criterios']) > 0) $rowspan += count($competencia['criterios']) + 1;
                                                }
                                            @endphp

                                            @foreach($materia['competencias'] as $compIndex => $competencia)
                                                @php $criteriosCount = count($competencia['criterios']); @endphp
                                                @if($criteriosCount == 0) @continue @endif

                                                @foreach($competencia['criterios'] as $criterioIndex => $criterio)
                                                    @php $contadorC++; @endphp
                                                    <tr>
                                                        @if($compIndex === 0 && $criterioIndex === 0)
                                                            <td rowspan="{{ $rowspan }}" class="align-middle bg-light fw-bold">{{ $materia['nombre'] }}</td>
                                                        @endif
                                                        @if($criterioIndex === 0)
                                                            <td rowspan="{{ $criteriosCount + 1 }}" class="align-middle bg-light">
                                                                {{ $competencia['nombre'] }}
                                                                @if($competencia['tiene_recuperacion'] ?? false)
                                                                    <span class="badge bg-info ms-1">Rec</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        <td class="align-middle">
                                                            {{ $criterio['nombre'] }}
                                                            @if(($criterio['tiene_recuperacion'] ?? false) && isset($criterio['nota_original']))
                                                                <br><small class="text-muted">(orig: {{ number_format($criterio['nota_original'], 1) }})</small>
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle fw-bold">C{{ $contadorC }}</td>
                                                        <td class="text-center align-middle fw-bold">
                                                            @if($criterio['tiene_nota'])
                                                                <span class="nota-valor cuantitativo">{{ number_format($criterio['nota'], 1) }}</span>
                                                                <span class="nota-valor cualitativo" style="display:none;">
                                                                    @php $n = $criterio['nota']; echo ($n >= 3.5 ? 'AD' : ($n >= 2.5 ? 'A' : ($n >= 1.5 ? 'B' : 'C'))); @endphp
                                                                </span>
                                                            @else -- @endif
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                @php $contadorN++; @endphp
                                                <tr class="bg-warning bg-opacity-25">
                                                    <td class="align-middle fw-bold">
                                                        <i class="bi bi-star me-2"></i>VALORACIÓN DE COMPETENCIA
                                                    </td>
                                                    <td class="text-center align-middle fw-bold">N{{ $contadorN }}</td>
                                                    <td class="text-center align-middle fw-bold">
                                                        @if($competencia['promedio'])
                                                            <span class="nota-promedio cuantitativo" data-valor="{{ $competencia['promedio'] }}">
                                                                {{ number_format($competencia['promedio'], 1) }}
                                                            </span>
                                                            <span class="nota-promedio cualitativo" style="display: none;" data-valor="{{ $competencia['promedio'] }}">
                                                                {{ $competencia['promedio_cualitativo'] ?? 'C' }}
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
                                            <td colspan="5" class="text-center py-1">
                                                <small>Escala: 1-4 (C=1, B=2, A=3, AD=4)</small>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Promedios Anuales -->
                    @if($sigla_param == 'anual')
                        <div class="border border-dark border-top-0">
                            <div class="border-bottom border-dark p-1 bg-light text-center">
                                <strong>PROMEDIOS ANUALES POR COMPETENCIA</strong>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th style="width: 25%;">MATERIA</th>
                                            <th style="width: 55%;">COMPETENCIA</th>
                                            <th style="width: 20%;">PROMEDIO</th>
                                        <tr>
                                    </thead>
                                    <tbody>
                                        @foreach($materias as $materia)
                                            @php $compCount = count($materia['competencias']); @endphp
                                            @foreach($materia['competencias'] as $compIndex => $competencia)
                                                <tr>
                                                    @if($compIndex === 0)
                                                        <td rowspan="{{ $compCount }}" class="align-middle fw-bold bg-light">{{ $materia['nombre'] }}</td>
                                                    @endif
                                                    <td class="fw-semibold">
                                                        {{ $competencia['nombre'] }}
                                                        @if($competencia['tiene_recuperacion'] ?? false)
                                                            <span class="badge bg-info ms-1">Rec</span>
                                                        @endif
                                                        @if(($competencia['tiene_recuperacion'] ?? false) && isset($competencia['promedio_original']))
                                                            <br><small class="text-muted">(orig: {{ number_format($competencia['promedio_original'], 1) }})</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-center fw-bold">
                                                        @if($competencia['promedio'])
                                                            <span class="nota-promedio cuantitativo">{{ number_format($competencia['promedio'], 1) }}</span>
                                                            <span class="nota-promedio cualitativo" style="display:none;">{{ $competencia['promedio_cualitativo'] ?? 'C' }}</span>
                                                        @else -- @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(isset($todas_las_conductas) && count($todas_las_conductas) > 0)
                        <div class="border border-dark border-top-0 fw-bold">
                            <div class="border-bottom border-dark p-1 bg-light text-center">
                                <strong>CALIFICACIONES DE CONDUCTA - {{ $titulo_conducta }}</strong>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th style="width: 70%;">CONDUCTA</th>
                                            <th style="width: 30%;">CALIFICACIÓN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($todas_las_conductas as $conducta)
                                            <tr>
                                                <td>
                                                    {{ $conducta['nombre'] }}
                                                    @if($conducta['tiene_tooltip'])
                                                        <i class="bi bi-info-circle text-info ms-1" style="cursor:help;" title="{{ $conducta['estado'] }}"></i>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="nota-conducta cuantitativo {{ $conducta['clase_color'] }}">{{ $conducta['nota'] }}</span>
                                                    <span class="nota-conducta cualitativo" style="display:none;">
                                                        @if(!$conducta['es_guion'])
                                                            @php
                                                                $n = $conducta['nota_original'];
                                                                $letra = ($n >= 3.5) ? 'AD' : (($n >= 2.5) ? 'A' : (($n >= 1.5) ? 'B' : 'C'));
                                                            @endphp
                                                            {{ $letra }}
                                                        @else
                                                            -
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr> @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endif
            @endif

            <!-- Asistencias - SIN MÁRGENES -->
            <div class="border border-dark border-top-0">
                <div class="border-bottom border-dark p-1 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><i class="bi bi-calendar-check me-2"></i>ASISTENCIAS</strong>
                        @if($sigla_param != 'anual')
                            <span class="badge bg-info">{{ strtoupper($sigla_param) }}</span>
                        @endif
                        <a href="{{ route('asistencia.calendario', ['periodo_id' => $periodo_actual['id'], 'periodobimestre_sigla' => $sigla_param]) }}" class="btn btn-sm btn-outline-primary">Ver Detalles</a>
                    </div>
                </div>

                @if(isset($asistencias) && count($asistencias) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>TIPO DE ASISTENCIA</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asistencias as $asistencia)
                                    <tr>
                                        <td class="text-center">{{ $asistencia['tipo'] }}</td>
                                        <td class="text-center fw-bold">{{ $asistencia['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-2 text-center">No hay registros de asistencia para el período seleccionado.</div>
                @endif
            </div>
        @else
            <div class="alert alert-warning border border-dark">No se encontró matrícula para el período seleccionado.</div>
        @endif
    </div>
</div>

<script>
// Función para actualizar el tipo de PDF antes de enviar el formulario
document.getElementById('pdfForm').addEventListener('submit', function(e) {
    var tipoSeleccionado = localStorage.getItem('tipoPdfSeleccionado') || 'cuantitativo';
    document.getElementById('tipoPdf').value = tipoSeleccionado;
    document.getElementById('periodoIdHidden').value = document.getElementById('periodo_id').value;
    document.getElementById('bimestreHidden').value = document.getElementById('bimestre').value;
});

// Función para cambiar entre cuantitativo y cualitativo
function cambiarVisualizacion(tipo) {
    localStorage.setItem('tipoPdfSeleccionado', tipo);

    document.getElementById('btnCuantitativo').classList.remove('active');
    document.getElementById('btnCualitativo').classList.remove('active');

    if (tipo === 'cuantitativo') {
        document.getElementById('btnCuantitativo').classList.add('active');
        document.querySelectorAll('.cuantitativo').forEach(el => el.style.display = '');
        document.querySelectorAll('.cualitativo').forEach(el => el.style.display = 'none');
        aplicarColorNotasNumericas();
    } else {
        document.getElementById('btnCualitativo').classList.add('active');
        document.querySelectorAll('.cuantitativo').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.cualitativo').forEach(el => el.style.display = '');
        aplicarColorNotasCualitativas();
    }
}

function aplicarColorNotasNumericas() {
    document.querySelectorAll('.nota-valor.cuantitativo, .nota-promedio.cuantitativo, .nota-conducta.cuantitativo').forEach(span => {
        const valor = span.textContent.trim();
        const nota = parseFloat(valor);
        if (!isNaN(nota)) {
            if (nota <= 1) {
                span.style.color = '#dc3545';
                span.style.fontWeight = 'bold';
            } else {
                span.style.color = '';
                span.style.fontWeight = '';
            }
        }
    });
}

function aplicarColorNotasCualitativas() {
    document.querySelectorAll('.nota-valor.cualitativo, .nota-promedio.cualitativo, .nota-conducta.cualitativo').forEach(span => {
        const valor = span.textContent.trim();
        if (valor === 'C') {
            span.style.color = '#dc3545';
            span.style.fontWeight = 'bold';
        } else {
            span.style.color = '';
            span.style.fontWeight = '';
        }
    });
}

function redondearNota(nota) {
    if (nota === null || nota === undefined || nota === '--') return nota;
    const num = parseFloat(nota);
    if (isNaN(num)) return nota;
    return Math.round(num);
}

function inicializarTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        const instance = bootstrap.Tooltip.getInstance(el);
        if (instance) instance.dispose();
        new bootstrap.Tooltip(el, { trigger: 'hover focus' });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    inicializarTooltips();

    document.querySelectorAll('.nota-valor.cuantitativo, .nota-promedio.cuantitativo, .nota-conducta.cuantitativo').forEach(span => {
        const valor = span.textContent.trim();
        if (!isNaN(parseFloat(valor))) {
            span.textContent = redondearNota(valor);
        }
    });

    aplicarColorNotasNumericas();
    aplicarColorNotasCualitativas();

    // Restaurar tipo de visualización guardado
    var tipoGuardado = localStorage.getItem('tipoPdfSeleccionado') || 'cuantitativo';
    cambiarVisualizacion(tipoGuardado);
});

function cambiarPeriodo(periodoId) {
    if (!periodoId) return;
    const bimestreActual = document.getElementById('bimestre').value;
    window.location.href = "{{ route('libreta.index') }}?periodo_id=" + periodoId + "&bimestre=" + bimestreActual;
}

function cambiarBimestre(bimestre) {
    if (!bimestre) return;
    const periodoIdActual = document.getElementById('periodo_id').value;
    if (periodoIdActual) {
        window.location.href = "{{ route('libreta.index') }}?periodo_id=" + periodoIdActual + "&bimestre=" + bimestre;
    }
}
</script>
@endsection
