<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Libreta de Calificaciones</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            padding: 20px;
        }

        /* Encabezado principal */
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 5px;
        }
        .header-subtitle {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 15px;
        }

        /* Tabla de información del estudiante */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #000;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .info-label {
            background-color: #f2f2f2;
            font-weight: bold;
            width: 18%;
        }

        /* Logo */
        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }

        /* Títulos de sección */
        .section-title {
            background-color: #6c757d;
            color: white;
            font-weight: bold;
            padding: 6px;
            text-align: center;
            border: 1px solid #000;
            border-bottom: none;
            font-size: 10pt;
        }

        /* Tablas de contenido */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #000;
        }
        .data-table th {
            background-color: #e9ecef;
            border: 1px solid #000;
            padding: 5px;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }
        .data-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        /* Notas */
        .nota-c { color: #dc3545; font-weight: bold; }
        .nota-b { color: #000; }
        .nota-a { color: #000; }
        .nota-ad { color: #28a745; font-weight: bold; }

        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

        /* Escala */
        .escala {
            margin-top: 15px;
            font-size: 7pt;
            text-align: center;
            border: 1px solid #000;
            padding: 6px;
        }
    </style>
</head>
<body>

<!-- CABECERA PRINCIPAL -->
<div class="main-header" style="border: 1px solid #000; margin-bottom: 15px;">
    <div class="title-bar" style="border-bottom: 1px solid #000; background-color: #6c757d; padding: 10px;">
        <div class="title-main" style="text-align: center; font-weight: bold; font-size: 14pt; color: #fff;">LIBRETA DE CALIFICACIONES DEL ESTUDIANTE (sec EBR)</div>
        <div class="title-sub" style="text-align: center; font-size: 10pt; color: #fff;">{{ $periodo_actual['nombre'] }} - {{ $titulo_periodo }}</div>
    </div>

    <!-- Info estudiante con tabla -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- Logo -->
            <td style="width: 25%; border-right: 1px solid #000; padding: 12px; text-align: center; vertical-align: middle;">
                @if($logo_base64)
                    <img src="{{ $logo_base64 }}" alt="Logo" style="max-height: 175px; width: auto;">
                @else
                    <strong>LOGO</strong>
                @endif
            </td>

            <!-- Datos del estudiante -->
            <td style="width: 75%; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($datos_estudiante as $label => $value)
                    <tr>
                        <td style="width: 30%; border: 1px solid #000; padding: 6px 10px; background-color: #f8f9fa; font-weight: bold;">
                            {{ $label }}:
                        </td>
                        <td style="width: 70%; border: 1px solid #000; padding: 6px 10px;">
                            <strong>{{ $value }}</strong>
                        </td>
                    </tr>
                    @endforeach
                </table>
             </td>
        </tr>
    </table>
</div>

@if($esPeriodoRecuperacion)
    <!-- RECUPERACIÓN -->
    <div class="section-title">RECUPERACIÓN DE COMPETENCIAS</div>
    <table class="data-table">
        <thead>
            <tr><th style="width: 25%;">ÁREA</th><th style="width: 45%;">COMPETENCIA</th><th style="width: 10%;">NOTA ORIG.</th><th style="width: 10%;">NOTA REC.</th><th style="width: 10%;">ESTADO</th></tr>
        </thead>
        <tbody>
            @foreach($recuperaciones as $rec)
            <tr>
                <td class="fw-bold">{{ $rec->materia->nombre ?? 'Sin materia' }}</td>
                <td>{{ $rec->materiaCompetencia->nombre ?? 'Competencia' }}</td>
                <td class="text-center">{{ $rec->nivel_logro_inicial ?? '--' }}</td>
                <td class="text-center"><strong>{{ $rec->nivel_logro_final ?? '--' }}</strong></td>
                <td class="text-center">
                    @php $aprobado = in_array($rec->nivel_logro_final, ['B', 'A', 'AD']); @endphp
                    <strong style="color: {{ $aprobado ? '#28a745' : '#dc3545' }};">{{ $aprobado ? 'APROBADO' : 'DESAPROBADO' }}</strong>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@else
    @if($sigla_param == 'anual')
        <!-- PROMEDIOS ANUALES -->
        <div class="section-title">PROMEDIOS ANUALES POR COMPETENCIA</div>
        <table class="data-table">
            <thead>
                <tr><th style="width: 30%;">ÁREA</th><th style="width: 55%;">COMPETENCIA</th><th style="width: 15%;">PROMEDIO</th></tr>
            </thead>
            <tbody>
                @foreach($materias as $materia)
                    @foreach($materia['competencias'] as $competencia)
                    <tr>
                        <td class="fw-bold">{{ $materia['nombre'] }}</td>
                        <td>{{ $competencia['nombre'] }}</td>
                        <td class="text-center">
                            @php $promedio = $competencia['promedio_cualitativo'] ?? 'C'; @endphp
                            <strong class="nota-{{ strtolower($promedio) }}">{{ $promedio }}</strong>
                        </td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    @if($sigla_param != 'anual')
        <!-- CALIFICACIONES BIMESTRALES -->
        <div class="section-title">CALIFICACIONES - {{ strtoupper($sigla_param) }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">ÁREA</th>
                    <th style="width: 25%;">COMPETENCIA</th>
                    <th style="width: 45%;">CRITERIOS DE EVALUACIÓN</th>
                    <th style="width: 5%;">CRIT.</th>
                    <th style="width: 10%;">VALOR</th>
                </tr>
            </thead>
            <tbody>
                @php $contadorC = 0; $contadorN = 0; @endphp
                @foreach($materias as $materia)
                    @foreach($materia['competencias'] as $competencia)
                        @if(count($competencia['criterios']) > 0)
                            @foreach($competencia['criterios'] as $criterio)
                                @php $contadorC++; @endphp
                                <tr>
                                    <td class="fw-bold">{{ $materia['nombre'] }}</td>
                                    <td>{{ $competencia['nombre'] }}</td>
                                    <td>{{ $criterio['nombre'] }}</td>
                                    <td class="text-center">C{{ $contadorC }}</td>
                                    <td class="text-center">
                                        @if($criterio['tiene_nota'])
                                            @php
                                                $n = $criterio['nota'];
                                                $cualitativo = ($n >= 3.5 ? 'AD' : ($n >= 2.5 ? 'A' : ($n >= 1.5 ? 'B' : 'C')));
                                            @endphp
                                            <strong class="nota-{{ strtolower($cualitativo) }}">{{ $cualitativo }}</strong>
                                        @else -- @endif
                                    </td>
                                </tr>
                            @endforeach
                            @php $contadorN++; @endphp
                            <tr style="background-color: #f8f9fa;">
                                <td class="fw-bold">{{ $materia['nombre'] }}</td>
                                <td>{{ $competencia['nombre'] }}</td>
                                <td><strong>VALORACIÓN DE COMPETENCIA</strong></td>
                                <td class="text-center">N{{ $contadorN }}</td>
                                <td class="text-center">
                                    @if($competencia['promedio'])
                                        @php $cualitativo = $competencia['promedio_cualitativo'] ?? 'C'; @endphp
                                        <strong class="nota-{{ strtolower($cualitativo) }}">{{ $cualitativo }}</strong>
                                    @else -- @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- CONDUCTA -->
    @if(count($todas_las_conductas) > 0)
    <div class="section-title">CALIFICACIONES DE CONDUCTA</div>
    <table class="data-table">
        <thead><tr><th style="width: 75%;">CONDUCTA</th><th style="width: 25%;">CALIFICACIÓN</th></tr></thead>
        <tbody>
            @foreach($todas_las_conductas as $conducta)
            <tr>
                <td>{{ $conducta['nombre'] }}</td>
                <td class="text-center">
                    @if(!$conducta['es_guion'])
                        @php
                            $nota = $conducta['nota_original'];
                            $cualitativo = ($nota >= 3.5 ? 'AD' : ($nota >= 2.5 ? 'A' : ($nota >= 1.5 ? 'B' : 'C')));
                        @endphp
                        <strong class="nota-{{ strtolower($cualitativo) }}">{{ $cualitativo }}</strong>
                    @else - @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
@endif

<!-- ASISTENCIAS -->
<div class="section-title">ASISTENCIAS @if($sigla_param != 'anual') ({{ strtoupper($sigla_param) }}) @endif</div>
@if(count($asistencias) > 0)
<table class="data-table">
    <thead><tr><th style="width: 75%;">TIPO DE ASISTENCIA</th><th style="width: 25%;">TOTAL</th></tr></thead>
    <tbody>
        @foreach($asistencias as $asistencia)
        <tr>
            <td>{{ $asistencia['tipo'] }}</td>
            <td class="text-center fw-bold">{{ $asistencia['total'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div style="border: 1px solid #000; padding: 10px; text-align: center;">No hay registros de asistencia para el período seleccionado.</div>
@endif

<!-- ESCALA DE CALIFICACIÓN -->
<div class="escala">
    <strong>ESCALA DE CALIFICACIÓN</strong><br>
    <strong class="nota-ad">AD</strong> (4 - Destacado) |
    <strong class="nota-a">A</strong> (3 - Logro) |
    <strong class="nota-b">B</strong> (2 - Proceso) |
    <strong class="nota-c">C</strong> (1 - Inicio)
</div>

</body>
</html>
