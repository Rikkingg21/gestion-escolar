@extends('layouts.app')

@section('content')
<div class="container-fluid mt-3">
    <!-- Formulario de filtros -->
    <div class="card border-0 shadow-lg rounded-3 mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex align-items-center py-3">
            <i class="fas fa-book-open fa-lg me-3"></i>
            <h4 class="mb-0"><strong>Generar Libreta de Calificaciones</strong></h4>
        </div>
        <div class="card-body bg-light">
            <form action="{{ route('libreta.pdf', ['anio' => $anio, 'bimestre' => $bimestre_nombre]) }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <!-- Año -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="anio" class="form-label fw-bold text-primary">
                            <i class="fas fa-calendar-alt me-2"></i>Año Académico
                        </label>
                        <select name="anio" id="anio" class="form-select border-primary shadow-sm">
                            <option value="">-- Seleccione Año --</option>
                            @foreach($anios as $a)
                                <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>
                                    {{ $a }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bimestre -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="bimestre_nombre" class="form-label fw-bold text-success">
                            <i class="fas fa-chart-line me-2"></i>Bimestre
                        </label>
                        <select name="bimestre_nombre" id="bimestre_nombre" class="form-select border-success shadow-sm">
                            <option value="">-- Seleccione Bimestre --</option>
                            <option value="anual" {{ $bimestre_nombre == 'anual' ? 'selected' : '' }}>
                                Año Completo
                            </option>
                            @foreach($bimestres as $bim)
                                <option value="{{ $bim->nombre }}" {{ $bimestre_nombre == $bim->nombre ? 'selected' : '' }}>
                                    {{ $bim->nombre }}
                                </option>
                            @endforeach
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
        </div>
    </div>

    <!-- Informe de libreta -->
    <div id="informe-libreta">
        <div class="card border-0 shadow-lg rounded-3 overflow-hidden mb-4">
            <!-- Encabezado de informe -->
            <div class="card-header bg-gradient-dark text-white text-center py-3">
                <h5 class="mb-1 fw-bold d-none d-md-block">
                    <i class="fas fa-graduation-cap me-2"></i>
                    INFORME DE PROGRESO DE LAS COMPETENCIAS DEL ESTUDIANTE
                </h5>
                <h6 class="mb-0 fw-bold d-md-none">
                    <i class="fas fa-graduation-cap me-1"></i>
                    INFORME DE COMPETENCIAS
                </h6>
                <small class="opacity-75">({{ $estudiante->nivel ?? 'sec' }} EBR)</small>
            </div>

            <div class="card-header bg-gradient-info text-white text-center py-2">
                <h6 class="mb-0 fw-bold">
                    AÑO - {{ $anio ?? date('Y') }} - {{ $bimestre_selected->nombre ?? 'I BIMESTRE' }}
                </h6>
            </div>

            <div class="card-body p-0">
                <!-- Información del estudiante para móvil -->
                <div class="d-block d-lg-none p-3 bg-light border-bottom">
                    <div class="text-center mb-3">
                        <div style="width: 80px; height: 80px; background: white; margin: 0 auto;
                                    display: flex; align-items: center; justify-content: center;
                                    border: 2px solid #2196f3; border-radius: 8px;">
                            <img src="{{ Storage::url($colegio->logo_path) }}" alt="Logo del colegio"
                                 style="max-height: 70px; max-width: 70px;" class="img-thumbnail border-0">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><strong>UGEL:</strong><br>Tacna</div>
                        <div class="col-6"><strong>Nivel:</strong><br>{{ $nivel_selected ?? '-' }}</div>
                        <div class="col-12"><strong>II.EE:</strong><br>{{ $colegio->nombre }}</div>
                        <div class="col-6"><strong>Grado:</strong><br>{{ $grado_selected?->grado ?? '-' }}</div>
                        <div class="col-6"><strong>Sección:</strong><br>{{ $seccion_selected ?? '-' }}</div>
                        <div class="col-12"><strong>Estudiante:</strong><br>
                            {{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}
                        </div>
                    </div>
                </div>

                <!-- Tabla principal - Desktop -->
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr class="bg-light-blue">
                                <td rowspan="6" style="width: 100px; text-align: center; vertical-align: middle; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                                    <div style="width: 80px; height: 100px; background: white; margin: 0 auto;
                                                display: flex; align-items: center; justify-content: center;
                                                border: 2px solid #2196f3; border-radius: 8px;">
                                        <img src="{{ Storage::url($colegio->logo_path) }}" alt="Logo del colegio"
                                             style="max-height: 80px; max-width: 80px;" class="img-thumbnail border-0">
                                    </div>
                                </td>
                                <td style="width: 100px; font-weight: bold; background: #e8f5e8;" class="text-success">UGEL:</td>
                                <td colspan="3" style="background: #f1f8e9;">Tacna</td>
                            </tr>
                            <tr class="bg-light-green">
                                <td style="font-weight: bold; background: #e8f5e8;" class="text-success">Nivel:</td>
                                <td colspan="3" style="background: #f1f8e9;">{{ $nivel_selected ?? '-' }}</td>
                            </tr>
                            <tr class="bg-light-orange">
                                <td style="font-weight: bold; background: #fff3e0;" class="text-warning">II.EE:</td>
                                <td colspan="3" style="background: #fff8e1; font-weight: 600;" class="text-orange">{{ $colegio->nombre }}</td>
                            </tr>
                            <tr class="bg-light-purple">
                                <td style="font-weight: bold; background: #f3e5f5;" class="text-purple">Grado:</td>
                                <td colspan="3" style="background: #f3e5f5;">{{ $grado_selected?->grado ?? '-' }}</td>
                            </tr>
                            <tr class="bg-light-pink">
                                <td style="font-weight: bold; background: #fce4ec;" class="text-pink">Sección:</td>
                                <td colspan="3" style="background: #fce4ec;">{{ $seccion_selected ?? '-' }}</td>
                            </tr>
                            <tr class="bg-light-cyan">
                                <td style="font-weight: bold; background: #e0f2f1;" class="text-teal">Estudiante:</td>
                                <td colspan="3" style="background: #e0f2f1; font-weight: 600;" class="text-dark">
                                    {{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}
                                </td>
                            </tr>
                            <tr class="bg-gradient-secondary text-white">
                                <th style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">Área</th>
                                <th style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">Competencias</th>
                                <th style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">Criterios de evaluación alcanzados</th>
                                <th style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">CRIT.</th>
                                <th style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detalle as $materiaData)
                                @php
                                    $materiaRowspan = $materiaData['total_criterios'];
                                    $materiaColors = [
                                        'linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%)',
                                        'linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%)',
                                        'linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%)',
                                        'linear-gradient(135deg, #fff3e0 0%, #ffecb3 100%)',
                                        'linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%)'
                                    ];
                                    $materiaColor = $materiaColors[$loop->index % count($materiaColors)];
                                @endphp

                                @foreach($materiaData['competencias'] as $competencia)
                                    @php
                                        $compRowspan = $competencia['total_criterios'] + 1;
                                        $competenciaColors = [
                                            'linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%)',
                                            'linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%)'
                                        ];
                                        $competenciaColor = $competenciaColors[$loop->index % count($competenciaColors)];
                                    @endphp

                                    @foreach($competencia['criterios'] as $index => $criterio)
                                        <tr style="background: {{ $loop->parent->first && $loop->first ? $materiaColor : 'white' }};">
                                            @if($loop->parent->first && $loop->first)
                                                <td rowspan="{{ $materiaRowspan }}" style="background: {{ $materiaColor }}; font-weight: bold; vertical-align: middle; border-right: 2px solid #2196f3;">
                                                    <div class="fw-bold text-dark">{{ $materiaData['nombre'] }}</div>
                                                </td>
                                            @endif

                                            @if($loop->first)
                                                <td rowspan="{{ $compRowspan }}" style="background: {{ $competenciaColor }}; vertical-align: middle; border-right: 2px solid #757575;">
                                                    <div class="fw-semibold text-dark">{{ $competencia['nombre'] }}</div>
                                                </td>
                                            @endif

                                            <td class="text-dark">{{ $criterio['nombre'] }}</td>
                                            <td style="font-weight: bold; background: #f8f9fa;" class="text-primary">C{{ $loop->parent->index * 10 + $loop->iteration }}</td>
                                            <td class="{{ $criterio['valor_class'] }}" style="font-weight: bold; text-align: center; border: 2px solid rgba(0,0,0,0.1);">
                                                {{ $criterio['valor'] }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Fila de valoración de competencia --}}
                                    <tr style="background: linear-gradient(135deg, #e8eaf6 0%, #c5cae9 100%);">
                                        <td style="font-weight: bold; background: #5c6bc0; color: white;" class="text-center">VALORACIÓN DE COMPETENCIA</td>
                                        <td style="font-weight: bold; background: #3949ab; color: white;" class="text-center">{{ $competencia['codigo_valoracion'] }}</td>
                                        <td class="{{ $competencia['valor_competencia_class'] }} fw-bold text-center" style="border: 2px solid rgba(0,0,0,0.2); font-size: 1.1em;">
                                            {{ $competencia['valor_competencia'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 bg-light">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                                            <h5>No hay registros de notas públicas para mostrar</h5>
                                            <p class="mb-0">Seleccione un año y bimestre para ver los resultados</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil para competencias -->
                <div class="d-block d-lg-none">
                    @forelse($detalle as $materiaData)
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0 fw-bold">{{ $materiaData['nombre'] }}</h6>
                            </div>
                            <div class="card-body p-0">
                                @foreach($materiaData['competencias'] as $competencia)
                                    <div class="border-bottom p-3">
                                        <h6 class="fw-semibold text-dark mb-2">{{ $competencia['nombre'] }}</h6>

                                        @foreach($competencia['criterios'] as $criterio)
                                            <div class="d-flex justify-content-between align-items-center py-1">
                                                <span class="text-muted small">{{ $criterio['nombre'] }}</span>
                                                <span class="badge {{ $criterio['valor_class'] }} px-2 py-1">
                                                    {{ $criterio['valor'] }}
                                                </span>
                                            </div>
                                        @endforeach

                                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                            <span class="fw-bold text-dark">VALORACIÓN FINAL</span>
                                            <span class="badge {{ $competencia['valor_competencia_class'] }} px-3 py-2">
                                                {{ $competencia['valor_competencia'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 bg-light">
                            <div class="text-muted">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h5>No hay registros de notas públicas para mostrar</h5>
                                <p class="mb-0">Seleccione un año y bimestre para ver los resultados</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Secciones de Conducta y Asistencias -->
        <div class="row g-3">
            @if($conductaNotas->count())
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-lg rounded-3 h-100">
                        <div class="card-header bg-gradient-warning text-dark py-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-star me-2"></i>Notas de Conducta
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr class="bg-amber">
                                            <th style="background: #ffb300; color: white;">Conducta</th>
                                            <th style="background: #ffb300; color: white;">Nota</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($conductaNotas as $nota)
                                            <tr class="{{ $loop->even ? 'bg-light-warning' : 'bg-lighter-warning' }}">
                                                <td class="fw-semibold">{{ $nota->conducta->nombre ?? '-' }}</td>
                                                <td class="fw-bold text-center" style="background: #fff3cd;">
                                                    {{ number_format($nota->promedio, 1) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(!empty($resumenAsistencias))
                <div class="col-12 {{ $conductaNotas->count() ? 'col-lg-6' : '' }}">
                    <div class="card border-0 shadow-lg rounded-3 h-100">
                        <div class="card-header bg-gradient-success text-white py-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-clock me-2"></i>Resumen de Asistencias
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr class="bg-green">
                                            <th style="background: #388e3c; color: white;">Tipo</th>
                                            <th style="background: #388e3c; color: white;">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $asistenciaTypes = [
                                                'Puntualidad' => ['color' => 'bg-light-success'],
                                                'Tardanza' => ['color' => 'bg-light-warning'],
                                                'Tardanza Injustificada' => ['color' => 'bg-light-orange'],
                                                'Falta' => ['color' => 'bg-light-danger'],
                                                'Falta Justificada' => ['color' => 'bg-light-info']
                                            ];
                                        @endphp
                                        @foreach($resumenAsistencias as $tipo => $cantidad)
                                            <tr class="{{ $asistenciaTypes[$tipo]['color'] ?? 'bg-light' }}">
                                                <td class="fw-semibold">{{ $tipo }}</td>
                                                <td class="fw-bold text-center">
                                                    <span class="badge bg-dark rounded-pill px-2 py-1">{{ $cantidad }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .table {
        width: 100%;
        margin-bottom: 0;
        color: #212529;
        border: none;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 8px;
        border: 1px solid #dee2e6;
        font-size: 0.875rem;
    }

    .table thead th {
        vertical-align: middle;
        border-bottom: 2px solid #dee2e6;
    }

    /* Colores para valores */
    .valor-ad {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
        color: white;
        font-weight: bold;
    }
    .valor-a {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important;
        color: white;
        font-weight: bold;
    }
    .valor-b {
        background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important;
        color: #2c3e50;
        font-weight: bold;
    }
    .valor-c {
        background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%) !important;
        color: white;
        font-weight: bold;
    }
    .valor-d {
        background: linear-gradient(135deg, #5a5c69 0%, #373840 100%) !important;
        color: white;
        font-weight: bold;
    }

    /* Gradientes de fondo */
    .bg-gradient-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important; }
    .bg-gradient-dark { background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%) !important; }
    .bg-gradient-info { background: linear-gradient(135deg, #36b9cc 0%, #258391 100%) !important; }
    .bg-gradient-secondary { background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important; }
    .bg-gradient-warning { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important; }
    .bg-gradient-success { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important; }

    /* Colores de fondo suaves */
    .bg-light-blue { background: #e3f2fd !important; }
    .bg-light-green { background: #e8f5e9 !important; }
    .bg-light-orange { background: #fff3e0 !important; }
    .bg-light-purple { background: #f3e5f5 !important; }
    .bg-light-pink { background: #fce4ec !important; }
    .bg-light-cyan { background: #e0f2f1 !important; }
    .bg-amber { background: #ffecb3 !important; }
    .bg-green { background: #c8e6c9 !important; }
    .bg-light-warning { background: #fff3cd !important; }
    .bg-lighter-warning { background: #fffdf6 !important; }
    .bg-light-success { background: #d4edda !important; }
    .bg-light-danger { background: #f8d7da !important; }
    .bg-light-info { background: #d1ecf1 !important; }
    .bg-light-orange { background: #ffe0b2 !important; }

    /* Sombras y bordes */
    .shadow-lg {
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.15) !important;
    }

    .rounded-3 {
        border-radius: 0.75rem !important;
    }

    /* Text colors */
    .text-orange { color: #ff9800 !important; }
    .text-teal { color: #009688 !important; }
    .text-purple { color: #9c27b0 !important; }
    .text-pink { color: #e91e63 !important; }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }

        .card-header h4, .card-header h5, .card-header h6 {
            font-size: 0.9rem;
        }

        .btn-lg {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
    }

    @media (max-width: 768px) {
        .table th, .table td {
            padding: 6px;
            font-size: 0.8rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const anioSelect = document.querySelector('select[name="anio"]');
    const bimestreSelect = document.querySelector('select[name="bimestre_nombre"]');

    function actualizarRuta() {
        const anio = anioSelect.value || '{{ $anio }}';
        const bimestre = bimestreSelect.value || '{{ $bimestre_nombre }}';
        if(anio && bimestre) {
            window.location.href = `/libreta/${anio}/${bimestre}`;
        }
    }

    anioSelect.addEventListener('change', actualizarRuta);
    bimestreSelect.addEventListener('change', actualizarRuta);
});
</script>
@endsection
