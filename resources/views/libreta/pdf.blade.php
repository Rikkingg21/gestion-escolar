<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Libreta - {{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th, .table td {
            border: 1px solid #dee2e6;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }
        .table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }
        .valor-ad { background-color: #4e73df; color: white; font-weight: bold; }
        .valor-a { background-color: #1cc88a; color: white; font-weight: bold; }
        .valor-b { background-color: #f6c23e; color: #2c3e50; font-weight: bold; }
        .valor-c { background-color: #e74a3b; color: white; font-weight: bold; }

        /* Estilos específicos para PDF */
        .page-break { page-break-after: always; }
        .text-center { text-align: center; }
        .mb-3 { margin-bottom: 15px; }
        .mt-4 { margin-top: 20px; }
        .card { border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; margin-bottom: 15px; }
        .card-header { background-color: #f8f9fa; padding: 8px; font-weight: bold; border-bottom: 1px solid #dee2e6; }
        .card-body { padding: 10px; }

        /* Mejorar legibilidad en PDF */
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
    </style>
</head>
<body>
    <!-- Contenido principal de la libreta -->
    <table class="table">
        <!-- Encabezado de informe -->
        <thead>
            <tr>
                <th colspan="5" style="text-align: center; font-size: 1.2em; padding: 10px;">
                    INFORME DE PROGRESO DE LAS COMPETENCIAS DEL ESTUDIANTE ({{ $estudiante->nivel ?? 'sec' }} EBR)
                </th>
            </tr>
            <tr>
                <th colspan="5" style="text-align: center; padding: 8px;">
                    AÑO - {{ $anio ?? date('Y') }} - {{ $bimestre_selected->nombre ?? 'I BIMESTRE' }}
                </th>
            </tr>
            <tr>
                <td rowspan="6" style="width: 100px; text-align: center;">
                    <div style="width: 80px; height: 100px; background-color: #e9ecef; margin: 0 auto;
                                display: flex; align-items: center; justify-content: center; border: 1px dashed #adb5bd;">
                        <span style="color: #6c757d;">Imagen</span>
                    </div>
                </td>
                <td style="width: 100px; font-weight: bold;">UGEL:</td>
                <td colspan="3">Tacna</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Nivel:</td>
                <td colspan="3">Secundaria</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">II.EE:</td>
                <td colspan="3">{{ $colegio->nombre }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Grado:</td>
                <td colspan="3">{{ $grado_selected->nombre ?? '1' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Sección:</td>
                <td colspan="3">{{ $estudiante->seccion ?? 'A' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Estudiante:</td>
                <td colspan="3">{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
            </tr>
            <tr>
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
                        $compRowspan = $competencia['total_criterios'] + 1;
                        $competenciaColors = ['#f5f5f5', '#eeeeee'];
                        $competenciaColor = $competenciaColors[$loop->index % count($competenciaColors)];
                    @endphp

                    @foreach($competencia['criterios'] as $index => $criterio)
                        <tr>
                            @if($loop->parent->first && $loop->first)
                                <td rowspan="{{ $materiaRowspan }}" style="background-color: {{ $materiaColor }}; font-weight: bold;">
                                    {{ $materiaData['nombre'] }}
                                </td>
                            @endif

                            @if($loop->first)
                                <td rowspan="{{ $compRowspan }}" style="background-color: {{ $competenciaColor }};">
                                    {{ $competencia['nombre'] }}
                                </td>
                            @endif

                            <td>{{ $criterio['nombre'] }}</td>
                            <td style="font-weight: bold;">C{{ $loop->parent->index * 10 + $loop->iteration }}</td>
                            <td class="{{ $criterio['valor_class'] }}" style="font-weight: bold;">
                                {{ $criterio['valor'] }}
                            </td>
                        </tr>
                    @endforeach

                    <tr style="background-color: {{ $competenciaColor }};">
                        <td style="font-weight: bold;">VALORACIÓN DE COMPETENCIA</td>
                        <td style="font-weight: bold;">{{ $competencia['codigo_valoracion'] }}</td>
                        <td class="{{ $competencia['valor_competencia_class'] }}" style="font-weight: bold;">
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

    <!-- Sección de conducta -->
    @if($conductaNotas->count())
    <div class="card mt-4">
        <div class="card-header" style="background-color: #ffc107; color: #000;">
            <strong>Notas de Conducta</strong>
        </div>
        <div class="card-body">
            <table class="table">
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
                            <td>{{ $nota->nota }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Sección de asistencias -->
    @if(!empty($resumenAsistencias))
    <div class="card mt-4">
        <div class="card-header" style="background-color: #28a745; color: white;">
            <strong>Resumen de Asistencias</strong>
        </div>
        <div class="card-body">
            <table class="table" style="width: 50%; margin: 0 auto;">
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

    <!-- Escala de valoración -->
    <div class="card mt-4">
        <div class="card-header" style="background-color: #17a2b8; color: white;">
            <strong>Escala de Valoración</strong>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <td style="background-color: #4e73df; color: white; font-weight: bold;">AD (4.0 - 5.0) - Logro destacado</td>
                    <td style="background-color: #1cc88a; color: white; font-weight: bold;">A (3.0 - 3.9) - Logro esperado</td>
                    <td style="background-color: #f6c23e; color: #2c3e50; font-weight: bold;">B (2.0 - 2.9) - En proceso</td>
                    <td style="background-color: #e74a3b; color: white; font-weight: bold;">C (1.0 - 1.9) - En inicio</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
