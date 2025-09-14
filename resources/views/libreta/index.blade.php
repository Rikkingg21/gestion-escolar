@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card border-1 rounded-3">
        <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-book-open me-2"></i> <strong>Generar Libreta</strong>
        </div>
        <div class="card-body">
            <form action="{{ route('libreta.pdf', ['anio' => $anio, 'bimestre' => $bimestre_nombre]) }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <!-- Año -->
                    <div class="col-md-4">
                        <label for="anio" class="form-label fw-semibold">Año</label>
                        <select name="anio" id="anio" class="form-select">
                            <option value="">-- Seleccione Año --</option>
                            @foreach($anios as $a)
                                <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>
                                    {{ $a }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bimestre -->
                    <div class="col-md-4">
                        <label for="bimestre_nombre" class="form-label fw-semibold">Bimestre</label>
                        <select name="bimestre_nombre" id="bimestre_nombre" class="form-select">
                            <option value="">-- Seleccione Bimestre --</option>
                            @foreach($bimestres as $bim)
                                <option value="{{ $bim->nombre }}" {{ $bimestre_selected && $bimestre_selected->nombre == $bim->nombre ? 'selected' : '' }}>
                                    {{ $bim->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botón PDF -->
                    <div class="col-md-4 text-end">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-file-pdf me-2"></i> Descargar PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div><br>
    <table class="table table-bordered mb-4" style="border-collapse: collapse;">

        <!-- Encabezado de informe -->
        <thead>
            <tr style="background-color: #2c3e50; color: white;">
                <th colspan="5" style="text-align: center; font-size: 1.2em; padding: 15px;">
                    INFORME DE PROGRESO DE LAS COMPETENCIAS DEL ESTUDIANTE ({{ $estudiante->nivel ?? 'sec' }} EBR)
                </th>
            </tr>
            <tr style="background-color: #3498db; color: white;">
                <th colspan="5" style="text-align: center; padding: 12px;">
                    AÑO - {{ $anio ?? date('Y') }} - {{ $bimestre_selected->nombre ?? 'I BIMESTRE' }}
                </th>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td rowspan="6" style="width: 120px; text-align: center; vertical-align: middle;">
                    <div style="width: 100px; height: 120px; background-color: #e9ecef; margin: 0 auto;
                                display: flex; align-items: center; justify-content: center; border: 1px dashed #adb5bd;">
                        <span style="color: #6c757d;">Imagen</span>
                    </div>
                </td>
                <td style="width: 120px; font-weight: bold;">UGEL:</td>
                <td colspan="3">Tacna</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold;">Nivel:</td>
                <td colspan="3">{{ $nivel_selected ?? '-' }}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold;">II.EE:</td>
                <td colspan="3">{{ $colegio->nombre }}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold;">Grado:</td>
                <td colspan="3">{{ $grado_selected?->grado ?? '-' }}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold;">Sección:</td>
                <td colspan="3">{{ $seccion_selected ?? '-' }}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold;">Estudiante:</td>
                <td colspan="3">{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
            </tr>
            <tr style="background-color: #34495e; color: white;">
                <th style="width: 15%;">Área</th>
                <th style="width: 25%;">Competencias</th>
                <th style="width: 35%;">Criterios de evaluación alcanzados</th>
                <th style="width: 10%;">CRIT.</th>
                <th style="width: 15%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detalle as $materiaData)
                @php
                    $materiaRowspan = $materiaData['total_criterios'];
                    $materiaColors = ['#e3f2fd', '#bbdefb', '#90caf9'];
                    $materiaColor = $materiaColors[$loop->index % count($materiaColors)];
                @endphp

                @foreach($materiaData['competencias'] as $competencia)
                    @php
                        $compRowspan = $competencia['total_criterios'] + 1; // +1 para la fila de valoración
                        $competenciaColors = ['#f5f5f5', '#eeeeee'];
                        $competenciaColor = $competenciaColors[$loop->index % count($competenciaColors)];
                    @endphp

                    @foreach($competencia['criterios'] as $index => $criterio)
                        <tr style="background-color: {{ $loop->parent->first && $loop->first ? $materiaColor : 'white' }};">
                            @if($loop->parent->first && $loop->first)
                                <td rowspan="{{ $materiaRowspan }}" style="background-color: {{ $materiaColor }}; font-weight: bold; vertical-align: middle;">
                                    {{ $materiaData['nombre'] }}
                                </td>
                            @endif

                            @if($loop->first)
                                <td rowspan="{{ $compRowspan }}" style="background-color: {{ $competenciaColor }}; vertical-align: middle;">
                                    {{ $competencia['nombre'] }}
                                </td>
                            @endif

                            <td>{{ $criterio['nombre'] }}</td>
                            <td style="font-weight: bold;">C{{ $loop->parent->index * 10 + $loop->iteration }}</td>
                            <td class="{{ $criterio['valor_class'] }}" style="font-weight: bold; text-align: center;">
                                {{ $criterio['valor'] }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- Fila de valoración de competencia --}}
                    <tr style="background-color: {{ $competenciaColor }};">
                        <td style="font-weight: bold;">VALORACIÓN DE COMPETENCIA</td>
                        <td style="font-weight: bold;">{{ $competencia['codigo_valoracion'] }}</td>
                        <td class="{{ $competencia['valor_competencia_class'] }}" style="font-weight: bold; text-align: center;">
                            {{ $competencia['valor_competencia'] }}
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center">No hay registros de notas públicas para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div>
        @if($conductaNotas->count())
            <div class="card mt-4">
                <div class="card-header bg-warning text-dark">
                    <strong>Notas de Conducta</strong>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Conducta</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($conductaNotas as $nota)
                                <tr>
                                    <td>{{ $nota->conducta->nombre ?? '-' }}</td>
                                    <td>{{ number_format($nota->promedio, 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        @if(!empty($resumenAsistencias))
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <strong>Resumen de Asistencias</strong>
                </div>
                <div class="card-body">
                    <table class="table table-bordered w-50 mx-auto">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Puntualidad</td>
                                <td>{{ $resumenAsistencias['Puntualidad'] }}</td>
                            </tr>
                            <tr>
                                <td>Tardanza</td>
                                <td>{{ $resumenAsistencias['Tardanza'] }}</td>
                            </tr>
                            <tr>
                                <td>Tardanza Injustificada</td>
                                <td>{{ $resumenAsistencias['Tardanza Injustificada'] }}</td>
                            </tr>
                            <tr>
                                <td>Falta</td>
                                <td>{{ $resumenAsistencias['Falta'] }}</td>
                            </tr>
                            <tr>
                                <td>Falta Justificada</td>
                                <td>{{ $resumenAsistencias['Falta Justificada'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i> Escala de Valoración
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 mb-3">
                            <div class="p-3 valor-ad rounded">
                                <h5 class="mb-1">AD</h5>
                                <small>Logro destacado</small>
                                <div class="mt-2">4.0 - 5.0</div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="p-3 valor-a rounded">
                                <h5 class="mb-1">A</h5>
                                <small>Logro esperado</small>
                                <div class="mt-2">3.0 - 3.9</div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="p-3 valor-b rounded">
                                <h5 class="mb-1">B</h5>
                                <small>En proceso</small>
                                <div class="mt-2">2.0 - 2.9</div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="p-3 valor-c rounded">
                                <h5 class="mb-1">C</h5>
                                <small>En inicio</small>
                                <div class="mt-2">1.0 - 1.9</div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h5 class="mb-1">-</h5>
                                <small>Sin registro</small>
                                <div class="mt-2">N/A</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border: 1px solid #dee2e6;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 12px;
        border: 1px solid #dee2e6;
    }

    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
    }

    .valor-ad {
        background-color: #4e73df;
        color: white;
        font-weight: bold;
    }

    .valor-a {
        background-color: #1cc88a;
        color: white;
        font-weight: bold;
    }

    .valor-b {
        background-color: #f6c23e;
        color: #2c3e50;
        font-weight: bold;
    }

    .valor-c {
        background-color: #e74a3b;
        color: white;
        font-weight: bold;
    }

    .valor-d {
        background-color: #5a5c69;
        color: white;
        font-weight: bold;
    }

    /* Efecto hover para filas */
    tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    /* Estilo para filas vacías */
    .text-center {
        text-align: center !important;
        padding: 20px;
        font-style: italic;
        color: #6c757d;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const anioSelect = document.querySelector('select[name="anio"]');
    const bimestreSelect = document.querySelector('select[name="bimestre_nombre"]'); // corregido

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
