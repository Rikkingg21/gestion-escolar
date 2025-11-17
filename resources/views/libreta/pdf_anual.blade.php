<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libreta de Notas - Año Completo</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 10px;
            line-height: 1.3;
        }
        .titulo {
            background: #2c3e50;
            color: #fff;
            text-align: center;
            font-size: 1.2em;
            padding: 8px;
            margin-bottom: 10px;
        }
        .subtitulo {
            background: #3498db;
            color: #fff;
            text-align: center;
            padding: 6px;
            margin: 8px 0;
            font-size: 1em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 5px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .valor-ad { background: #4e73df; color: #fff; font-weight: bold; text-align: center; }
        .valor-a { background: #1cc88a; color: #fff; font-weight: bold; text-align: center; }
        .valor-b { background: #f6c23e; color: #2c3e50; font-weight: bold; text-align: center; }
        .valor-c { background: #e74a3b; color: #fff; font-weight: bold; text-align: center; }
        .valor-d { background: #5a5c69; color: #fff; font-weight: bold; text-align: center; }
        .text-muted { color: #888; text-align: center; }
        .competencia-text {
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.2;
        }
        .materia-header {
            background: #e9ecef;
            font-weight: bold;
            font-size: 1.1em;
        }
        .section-separator {
            margin: 15px 0;
            border-top: 2px solid #ccc;
        }
        .summary-table {
            background: #f8f9fa;
        }
        .logo-container {
            width: 80px;
            height: 80px;
            background: white;
            margin: 0 auto;
            border: 1px solid #adb5bd;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-img {
            max-height: 75px;
            max-width: 75px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="titulo">
        INFORME DE PROGRESO ANUAL - {{ $anio }}
    </div>

    <!-- Datos del estudiante -->
    <table>
        <tr>
            <td rowspan="5" style="width: 100px; background: #e9ecef; vertical-align: middle; text-align: center;">
                <div class="logo-container">
                    <img src="{{ public_path('storage/' . $colegio->logo_path) }}"
                        alt="Logo del colegio"
                        class="logo-img">
                </div>
            </td>
            <td style="font-weight: bold; width: 15%;">UGEL:</td>
            <td style="width: 35%;">Tacna</td>
            <td style="font-weight: bold; width: 15%;">Nivel:</td>
            <td style="width: 35%;">{{ $nivel_selected ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">II.EE:</td>
            <td colspan="3">{{ $colegio->nombre }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Grado:</td>
            <td>{{ $grado_selected?->grado ?? '-' }}</td>
            <td style="font-weight: bold;">Sección:</td>
            <td>{{ $seccion_selected ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Estudiante:</td>
            <td colspan="3">{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Periodo:</td>
            <td colspan="3">Año Completo</td>
        </tr>
    </table>

    <!-- Resumen Anual de Competencias -->
    <div class="subtitulo">PROMEDIO ANUAL DE COMPETENCIAS</div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Materia</th>
                <th style="width: 55%;">Competencias</th>
                <th style="width: 20%;">Promedio Anual</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Calcular promedios anuales consolidados
                $materiasAnuales = [];
                $totalBimestres = count($allBimestresData);
                $nombresBimestres = array_keys($allBimestresData);

                foreach($allBimestresData as $bimestreNombre => $bimestreData) {
                    foreach($bimestreData['detalle'] as $materiaData) {
                        $materiaNombre = $materiaData['nombre'];
                        if (!isset($materiasAnuales[$materiaNombre])) {
                            $materiasAnuales[$materiaNombre] = [
                                'nombre' => $materiaNombre,
                                'competencias' => []
                            ];
                        }

                        foreach($materiaData['competencias'] as $competencia) {
                            $competenciaNombre = $competencia['nombre'];
                            if (!isset($materiasAnuales[$materiaNombre]['competencias'][$competenciaNombre])) {
                                $materiasAnuales[$materiaNombre]['competencias'][$competenciaNombre] = [
                                    'nombre' => $competenciaNombre,
                                    'promedios_por_bimestre' => array_fill_keys($nombresBimestres, 0) // Inicializar todos los bimestres en 0
                                ];
                            }

                            // Asignar el promedio del bimestre actual
                            $materiasAnuales[$materiaNombre]['competencias'][$competenciaNombre]['promedios_por_bimestre'][$bimestreNombre] = $competencia['promedio_competencia'];
                        }
                    }
                }
            @endphp

            @foreach($materiasAnuales as $materiaNombre => $materiaData)
                <tr class="materia-header">
                    <td colspan="3" style="font-weight: bold; background: #d1ecf1;">
                        {{ $materiaNombre }}
                    </td>
                </tr>

                @foreach($materiaData['competencias'] as $competenciaNombre => $competenciaData)
                    @php
                        // Calcular promedio considerando TODOS los bimestres
                        $sumaTotal = 0;
                        $bimestresConNota = 0;

                        foreach($competenciaData['promedios_por_bimestre'] as $bimestreNombre => $promedio) {
                            if ($promedio > 0) {
                                $sumaTotal += $promedio;
                                $bimestresConNota++;
                            }
                        }

                        // El promedio se calcula sobre el total de bimestres del año
                        $promedioAnual = $totalBimestres > 0 ? round($sumaTotal / $totalBimestres, 2) : 0;

                        // Calcular valor basado en el promedio
                        $valorAnual = 'N/E';
                        if ($promedioAnual >= 4) $valorAnual = 'AD';
                        elseif ($promedioAnual >= 3) $valorAnual = 'A';
                        elseif ($promedioAnual >= 2) $valorAnual = 'B';
                        elseif ($promedioAnual >= 1) $valorAnual = 'C';

                        $valorClass = $valorAnual != 'N/E' ? 'valor-' . strtolower($valorAnual) : 'text-muted';
                    @endphp

                    <tr>
                        <td style="font-weight: bold;"></td>
                        <td class="competencia-text">
                            {{ $competenciaNombre }}
                            <br><small style="color: #666;">Evaluada en {{ $bimestresConNota }}/{{ $totalBimestres }} bimestres</small>
                        </td>
                        <td class="{{ $valorClass }}" style="font-weight: bold; text-align: center; vertical-align: middle;">
                            {{ $valorAnual }}
                        </td>
                    </tr>
                @endforeach

                <!-- Línea separadora entre materias -->
                @if(!$loop->last)
                    <tr>
                        <td colspan="3" style="padding: 2px; background: #dee2e6;"></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- Separador de sección -->
    <div class="section-separator"></div>

    <!-- Resumen Anual de Asistencias -->
    <div class="subtitulo">RESUMEN ANUAL DE ASISTENCIAS</div>

    @php
        // Calcular totales anuales de asistencias
        $asistenciasAnuales = [
            'Puntualidad' => 0,
            'Tardanza' => 0,
            'Tardanza Injustificada' => 0,
            'Falta' => 0,
            'Falta Justificada' => 0
        ];

        foreach($allBimestresData as $bimestreData) {
            if (isset($bimestreData['resumenAsistencias'])) {
                foreach($bimestreData['resumenAsistencias'] as $tipo => $cantidad) {
                    $asistenciasAnuales[$tipo] += $cantidad;
                }
            }
        }

        $totalAsistencias = array_sum($asistenciasAnuales);
    @endphp

    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 20%; text-align: center;">Tipo</th>
                <th style="width: 20%; text-align: center;">Cantidad</th>
                <th style="width: 20%; text-align: center;">Porcentaje</th>
                <th style="width: 40%; text-align: center;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center; font-weight: bold;">Puntualidad</td>
                <td style="text-align: center;">{{ $asistenciasAnuales['Puntualidad'] }}</td>
                <td style="text-align: center;">
                    {{ $totalAsistencias > 0 ? number_format(($asistenciasAnuales['Puntualidad'] / $totalAsistencias) * 100, 1) : 0 }}%
                </td>
                <td style="text-align: center;">Asistencias en horario</td>
            </tr>
            <tr>
                <td style="text-align: center; font-weight: bold;">Tardanza</td>
                <td style="text-align: center;">{{ $asistenciasAnuales['Tardanza'] }}</td>
                <td style="text-align: center;">
                    {{ $totalAsistencias > 0 ? number_format(($asistenciasAnuales['Tardanza'] / $totalAsistencias) * 100, 1) : 0 }}%
                </td>
                <td style="text-align: center;">Llegadas después del horario</td>
            </tr>
            <tr>
                <td style="text-align: center; font-weight: bold;">Tard. Injustificada</td>
                <td style="text-align: center;">{{ $asistenciasAnuales['Tardanza Injustificada'] }}</td>
                <td style="text-align: center;">
                    {{ $totalAsistencias > 0 ? number_format(($asistenciasAnuales['Tardanza Injustificada'] / $totalAsistencias) * 100, 1) : 0 }}%
                </td>
                <td style="text-align: center;">Sin justificación</td>
            </tr>
            <tr>
                <td style="text-align: center; font-weight: bold;">Faltas</td>
                <td style="text-align: center;">{{ $asistenciasAnuales['Falta'] }}</td>
                <td style="text-align: center;">
                    {{ $totalAsistencias > 0 ? number_format(($asistenciasAnuales['Falta'] / $totalAsistencias) * 100, 1) : 0 }}%
                </td>
                <td style="text-align: center;">Inasistencias totales</td>
            </tr>
            <tr>
                <td style="text-align: center; font-weight: bold;">Faltas Justificadas</td>
                <td style="text-align: center;">{{ $asistenciasAnuales['Falta Justificada'] }}</td>
                <td style="text-align: center;">
                    {{ $totalAsistencias > 0 ? number_format(($asistenciasAnuales['Falta Justificada'] / $totalAsistencias) * 100, 1) : 0 }}%
                </td>
                <td style="text-align: center;">Con documentación</td>
            </tr>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td style="text-align: center;">TOTAL</td>
                <td style="text-align: center;">{{ $totalAsistencias }}</td>
                <td style="text-align: center;">100%</td>
                <td style="text-align: center;">Registro anual completo</td>
            </tr>
        </tbody>
    </table>

    <!-- Resumen de Conducta Anual -->
    <div class="subtitulo">PROMEDIO ANUAL DE CONDUCTA</div>

    @php
        // Calcular promedios anuales de conducta
        $conductaAnual = [];
        foreach($allBimestresData as $bimestreData) {
            if (isset($bimestreData['conductaNotas'])) {
                foreach($bimestreData['conductaNotas'] as $notaConducta) {
                    $conductaId = $notaConducta->conducta_id;
                    $conductaNombre = $notaConducta->conducta->nombre ?? 'Conducta';

                    if (!isset($conductaAnual[$conductaId])) {
                        $conductaAnual[$conductaId] = [
                            'nombre' => $conductaNombre,
                            'notas' => []
                        ];
                    }

                    $conductaAnual[$conductaId]['notas'][] = $notaConducta->promedio;
                }
            }
        }
    @endphp

    @if(count($conductaAnual) > 0)
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 70%; text-align: center;">Aspecto de Conducta</th>
                <th style="width: 30%; text-align: center;">Promedio Anual</th>
            </tr>
        </thead>
        <tbody>
            @foreach($conductaAnual as $conductaId => $conductaData)
            @php
                $promedioConducta = count($conductaData['notas']) > 0
                    ? round(array_sum($conductaData['notas']) / count($conductaData['notas']), 1)
                    : 0;
            @endphp
            <tr>
                <td style="text-align: left; padding-left: 15px;">{{ $conductaData['nombre'] }}</td>
                <td style="text-align: center; font-weight: bold;">{{ number_format($promedioConducta, 1) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <table class="summary-table">
        <tr>
            <td style="text-align: center; padding: 10px;">No hay registros de conducta para el año {{ $anio }}</td>
        </tr>
    </table>
    @endif

    <!-- Escala de Valoración -->
    <div class="subtitulo">ESCALA DE VALORACIÓN</div>
    <table>
        <tr>
            <td class="valor-ad" style="width: 20%; text-align: center; padding: 6px;">
                <strong>AD</strong><br>
                <small>Logro destacado</small><br>
                <small>4.0 - 5.0</small>
            </td>
            <td class="valor-a" style="width: 20%; text-align: center; padding: 6px;">
                <strong>A</strong><br>
                <small>Logro esperado</small><br>
                <small>3.0 - 3.9</small>
            </td>
            <td class="valor-b" style="width: 20%; text-align: center; padding: 6px;">
                <strong>B</strong><br>
                <small>En proceso</small><br>
                <small>2.0 - 2.9</small>
            </td>
            <td class="valor-c" style="width: 20%; text-align: center; padding: 6px;">
                <strong>C</strong><br>
                <small>En inicio</small><br>
                <small>1.0 - 1.9</small>
            </td>
            <td style="background: #5a5c69; color: #fff; text-align: center; width: 20%; padding: 6px;">
                <strong>N/E</strong><br>
                <small>No evaluado</small><br>
                <small>-</small>
            </td>
        </tr>
    </table>
</body>
</html>
