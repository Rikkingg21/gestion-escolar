<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libreta de Notas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .titulo { background: #2c3e50; color: #fff; text-align: center; font-size: 1.2em; padding: 10px; }
        .subtitulo { background: #3498db; color: #fff; text-align: center; padding: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #bbb; padding: 6px; text-align: center; }
        th { background: #f0f0f0; }
        .valor-ad { background: #4e73df; color: #fff; font-weight: bold; }
        .valor-a { background: #1cc88a; color: #fff; font-weight: bold; }
        .valor-b { background: #f6c23e; color: #2c3e50; font-weight: bold; }
        .valor-c { background: #e74a3b; color: #fff; font-weight: bold; }
        .valor-d { background: #5a5c69; color: #fff; font-weight: bold; }
        .text-muted { color: #888; }
        .section-title { background: #eee; font-weight: bold; padding: 6px; }
    </style>
</head>
<body>
    <div class="titulo">
        INFORME DE PROGRESO DE LAS COMPETENCIAS DEL ESTUDIANTE ({{ $estudiante->nivel ?? 'sec' }} EBR)
    </div>
    <div class="subtitulo">
        AÑO - {{ $anio ?? date('Y') }} - {{ $bimestre_selected->nombre ?? 'I BIMESTRE' }}
    </div>
    <table>
        <tr>
            <td rowspan="6" style="width: 100px; background: #e9ecef;">
                <div style="width: 80px; height: 100px; background: #e9ecef; margin: 0 auto; border: 1px dashed #adb5bd; display: flex; align-items: center; justify-content: center;">
                    <span style="color: #6c757d;">Imagen</span>
                </div>
            </td>
            <td style="font-weight: bold;">UGEL:</td>
            <td colspan="3">Tacna</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Nivel:</td>
            <td colspan="3">{{ $nivel_selected ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">II.EE:</td>
            <td colspan="3">{{ $colegio->nombre }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Grado:</td>
            <td colspan="3">{{ $grado_selected?->grado ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Sección:</td>
            <td colspan="3">{{ $seccion_selected ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Estudiante:</td>
            <td colspan="3">{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
        </tr>
    </table>

    <table>
        <tbody>
            <tr>
                <th>Área</th>
                <th>Competencias</th>
                <th>Criterios de evaluación alcanzados</th>
                <th>CRIT.</th>
                <th>Valor</th>
            </tr>
            @forelse($detalle as $materiaData)
                @foreach($materiaData['competencias'] as $competencia)
                    @foreach($competencia['criterios'] as $index => $criterio)
                        <tr>
                            <td style="font-weight: bold; vertical-align: middle;">
                                {{ $materiaData['nombre'] }}
                            </td>
                            <td style="vertical-align: middle;">
                                {{ $competencia['nombre'] }}
                            </td>
                            <td>{{ $criterio['nombre'] }}</td>
                            <td style="font-weight: bold;">C{{ $loop->parent->index * 10 + $loop->iteration }}</td>
                            <td class="{{ $criterio['valor_class'] }}" style="font-weight: bold;">
                                {{ $criterio['valor'] }}
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="font-weight: bold;">{{ $materiaData['nombre'] }}</td>
                        <td style="font-weight: bold;">VALORACIÓN DE COMPETENCIA</td>
                        <td></td> <!-- Celda vacía para "Criterios de evaluación alcanzados" -->
                        <td style="font-weight: bold;">{{ $competencia['codigo_valoracion'] }}</td>
                        <td class="{{ $competencia['valor_competencia_class'] }}" style="font-weight: bold;">
                            {{ $competencia['valor_competencia'] }}
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-muted">No hay registros de notas públicas para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($conductaNotas->count())
        <div class="section-title">Notas de Conducta</div>
        <table>
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
    @endif

    @if(!empty($resumenAsistencias))
        <div class="section-title">Resumen de Asistencias</div>
        <table style="width: 60%; margin: 0 auto;">
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
    @endif

    <div class="section-title">Escala de Valoración</div>
    <table>
        <tr>
            <td class="valor-ad">AD<br><small>Logro destacado</small><br>4.0 - 5.0</td>
            <td class="valor-a">A<br><small>Logro esperado</small><br>3.0 - 3.9</td>
            <td class="valor-b">B<br><small>En proceso</small><br>2.0 - 2.9</td>
            <td class="valor-c">C<br><small>En inicio</small><br>1.0 - 1.9</td>
            <td>-<br><small>Sin registro</small><br>N/A</td>
        </tr>
    </table>
</body>
</html>
