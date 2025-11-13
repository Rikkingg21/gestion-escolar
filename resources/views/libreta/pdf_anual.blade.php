<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libreta de Notas - Año Completo</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .titulo { background: #2c3e50; color: #fff; text-align: center; font-size: 1.1em; padding: 8px; }
        .subtitulo { background: #3498db; color: #fff; text-align: center; padding: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #bbb; padding: 4px; text-align: center; }
        th { background: #f0f0f0; }
        .bimestre-section { margin-bottom: 15px; page-break-inside: avoid; }
        .bimestre-title { background: #e74c3c; color: #fff; padding: 5px; font-weight: bold; }
        .valor-ad { background: #4e73df; color: #fff; }
        .valor-a { background: #1cc88a; color: #fff; }
        .valor-b { background: #f6c23e; color: #2c3e50; }
        .valor-c { background: #e74a3b; color: #fff; }
        .summary-table { background: #f8f9fa; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="titulo">
        INFORME DE PROGRESO ANUAL - {{ $anio }}
    </div>

    <!-- Datos del estudiante -->
    <table>
        <tr>
            <td style="font-weight: bold;">Estudiante:</td>
            <td colspan="3">{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Grado:</td>
            <td>{{ $estudiante->grado->grado ?? '-' }}</td>
            <td style="font-weight: bold;">Sección:</td>
            <td>{{ $estudiante->seccion ?? '-' }}</td>
        </tr>
    </table>

    <!-- Resumen por Bimestres -->
    <div class="bimestre-title">RESUMEN ANUAL - TODOS LOS BIMESTRES</div>

    @foreach($allBimestresData as $bimestreNombre => $bimestreData)
        <div class="bimestre-section">
            <div class="subtitulo">{{ strtoupper($bimestreNombre) }}</div>

            <!-- Tabla de competencias -->
            <table>
                <thead>
                    <tr>
                        <th>Área</th>
                        <th>Competencia</th>
                        <th>Valoración</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bimestreData['detalle'] as $materiaData)
                        @foreach($materiaData['competencias'] as $competencia)
                            <tr>
                                <td style="font-weight: bold;">{{ $materiaData['nombre'] }}</td>
                                <td>{{ Str::limit($competencia['nombre'], 50) }}</td>
                                <td class="{{ $competencia['valor_competencia_class'] }}" style="font-weight: bold;">
                                    {{ $competencia['valor_competencia'] }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>

            <!-- Conducta y Asistencias -->
            <table style="width: 100%;">
                <tr>
                    @if(isset($bimestreData['conductaNotas']) && $bimestreData['conductaNotas']->count())
                    <td style="width: 50%; vertical-align: top;">
                        <table style="width: 100%;">
                            <thead>
                                <tr><th colspan="2">Conducta - {{ $bimestreNombre }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach($bimestreData['conductaNotas'] as $nota)
                                <tr>
                                    <td>{{ Str::limit($nota->conducta->nombre ?? '-', 30) }}</td>
                                    <td>{{ number_format($nota->promedio, 1) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                    @endif

                    @if(isset($bimestreData['resumenAsistencias']))
                    <td style="width: 50%; vertical-align: top;">
                        <table style="width: 100%;">
                            <thead>
                                <tr><th colspan="2">Asistencias - {{ $bimestreNombre }}</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Puntualidad</td><td>{{ $bimestreData['resumenAsistencias']['Puntualidad'] }}</td></tr>
                                <tr><td>Tardanza</td><td>{{ $bimestreData['resumenAsistencias']['Tardanza'] }}</td></tr>
                                <tr><td>Faltas</td><td>{{ $bimestreData['resumenAsistencias']['Falta'] }}</td></tr>
                            </tbody>
                        </table>
                    </td>
                    @endif
                </tr>
            </table>
        </div>

        @if(!$loop->last)
            <div style="border-bottom: 2px dashed #ccc; margin: 10px 0;"></div>
        @endif
    @endforeach

    <!-- Escala de Valoración -->
    <div style="margin-top: 20px;">
        <table>
            <tr>
                <td class="valor-ad">AD (4.0-5.0)<br>Logro destacado</td>
                <td class="valor-a">A (3.0-3.9)<br>Logro esperado</td>
                <td class="valor-b">B (2.0-2.9)<br>En proceso</td>
                <td class="valor-c">C (1.0-1.9)<br>En inicio</td>
                <td style="background: #5a5c69; color: #fff;">-<br>Sin registro</td>
            </tr>
        </table>
    </div>
</body>
</html>
