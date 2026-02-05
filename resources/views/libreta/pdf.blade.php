<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libreta de Calificaciones - PDF</title>
    <style>
        /* Estilos generales */
        @page {
            margin: 20px;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            width: 100%;
            max-width: 100%;
        }

        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 14px;
            color: #27ae60;
            font-weight: bold;
        }

        /* Banner de formato */
        .formato-banner {
            text-align: center;
            margin-bottom: 10px;
            padding: 5px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }

        .formato-cuantitativo {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .formato-cualitativo {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        /* Datos del estudiante */
        .student-info {
            margin-bottom: 25px;
            border: 2px solid #000;
            border-radius: 5px;
            padding: 15px;
            background-color: #fff;
        }

        .student-row {
            display: flex;
            margin-bottom: 5px;
        }

        .student-label {
            font-weight: bold;
            width: 40%;
            color: #2c3e50;
        }

        .student-value {
            width: 60%;
        }

        /* Tablas */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .table td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .table-primary th {
            background-color: #2c3e50;
        }

        .table-info th {
            background-color: #17a2b8;
        }

        .table-success th {
            background-color: #28a745;
        }

        /* Colores para notas */
        .text-success {
            color: #28a745 !important;
        }

        .text-warning {
            color: #ffc107 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .text-info {
            color: #17a2b8 !important;
        }

        /* Fondo para filas */
        .bg-light {
            background-color: #f8f9fa !important;
        }

        .bg-warning {
            background-color: #fff3cd !important;
        }

        .bg-success {
            background-color: #d4edda !important;
        }

        /* Alineaciones */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .align-middle {
            vertical-align: middle !important;
        }

        /* Spacing */
        .mt-2 { margin-top: 10px; }
        .mt-3 { margin-top: 15px; }
        .mt-4 { margin-top: 20px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .p-2 { padding: 10px; }
        .p-3 { padding: 15px; }

        /* Logo */
        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo {
            max-height: 80px;
            max-width: 200px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }

        /* Break para evitar que las tablas se corten mal */
        .keep-together {
            page-break-inside: avoid;
        }

        .page-break {
            page-break-before: always;
        }

        /* Estilos específicos para materias */
        .materia-nombre {
            font-weight: bold;
            color: #2c3e50;
            background-color: #e9ecef;
            padding: 5px;
            border-radius: 3px;
        }

        .competencia-nombre {
            font-weight: bold;
            color: #155724;
            background-color: #d4edda;
            padding: 4px;
            border-radius: 3px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        /* Notas */
        .nota-valor {
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            min-width: 25px;
            text-align: center;
        }

        /* Leyenda de escala */
        .leyenda-escala {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }

        /* Alertas */
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Banner de formato -->
        @if(isset($tipo_pdf) && $tipo_pdf == 'cualitativo')
        <div class="formato-banner formato-cualitativo">
            FORMATO CUALITATIVO | Escala: C (1), B (2), A (3), AD (4)
        </div>
        @else
        <div class="formato-banner formato-cuantitativo">
            FORMATO CUANTITATIVO | Escala numérica: 1 - 4
        </div>
        @endif

        <!-- Encabezado -->
        <div class="header">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 5px;">
                    LIBRETA DE CALIFICACIONES DEL ESTUDIANTE (sec EBR)
                </div>
                <div style="font-size: 14px; color: #27ae60; font-weight: bold;">
                    {{ $periodo_actual->anio }} -
                    @if($bimestre_param == 'anual')
                        EVALUACIÓN ANUAL
                    @else
                        {{ $bimestre_param }}° BIMESTRE
                    @endif
                </div>
            </div>
        </div>

        <!-- Información del estudiante -->
        <div style="border: 2px solid #000; border-radius: 8px; padding: 15px; margin-bottom: 20px; background-color: #fff;">
            <table style="width: 100%;">
                <tr>
                    <!-- Logo (columna izquierda) -->
                    <td style="width: 25%; vertical-align: top; text-align: center; border-right: 2px solid #000; padding-right: 15px;">
                        <img src="https://www.iesantateresita.com/storage/logo/logo-actual.png" alt="Logo" style="max-height: 300px; max-width: 150px;">
                    </td>

                    <!-- Datos (columna derecha) -->
                    <td style="width: 75%; vertical-align: top; padding-left: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <!-- UGEL -->
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="width: 35%; padding: 5px 0; font-weight: bold; color: #2c3e50;">
                                    UGEL:
                                </td>
                                <td style="padding: 5px 0;">
                                    <strong>{{ $colegio->ugel ?? 'Tacna' }}</strong>
                                </td>
                            </tr>
                            <!-- II.EE -->
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 5px 0; font-weight: bold; color: #2c3e50;">
                                    II.EE:
                                </td>
                                <td style="padding: 5px 0;">
                                    <strong>{{ $colegio->nombre ?? 'NO REGISTRADO' }}</strong>
                                </td>
                            </tr>
                            <!-- NIVEL -->
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 5px 0; font-weight: bold; color: #2c3e50;">
                                    NIVEL:
                                </td>
                                <td style="padding: 5px 0;">
                                    <strong>{{ $matricula_actual->grado->nivel ?? 'No disponible' }}</strong>
                                </td>
                            </tr>
                            <!-- GRADO -->
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 5px 0; font-weight: bold; color: #2c3e50;">
                                    GRADO:
                                </td>
                                <td style="padding: 5px 0;">
                                    <strong>{{ $matricula_actual->grado->grado ?? 'No disponible' }}°</strong>
                                </td>
                            </tr>
                            <!-- SECCIÓN -->
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 5px 0; font-weight: bold; color: #2c3e50;">
                                    SECCIÓN:
                                </td>
                                <td style="padding: 5px 0;">
                                    <strong>"{{ $matricula_actual->grado->seccion ?? 'No disponible' }}"</strong>
                                </td>
                            </tr>
                            <!-- ESTUDIANTE -->
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 5px 0; font-weight: bold; color: #2c3e50;">
                                    ESTUDIANTE:
                                </td>
                                <td style="padding: 5px 0;">
                                    <strong style="color: #3498db;">
                                        {{ $estudiante->user->apellido_paterno }}
                                        {{ $estudiante->user->apellido_materno }},
                                        {{ $estudiante->user->nombre }}
                                    </strong>
                                </td>
                            </tr>
                            <!-- DNI -->
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold; color: #2c3e50;">
                                    DNI:
                                </td>
                                <td style="padding: 5px 0;">
                                    <strong>{{ $estudiante->user->dni }}</strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Calificaciones Regulares -->
        @if(count($materias_procesadas) > 0)
        <div class="keep-together">
            <h4 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 5px; margin-bottom: 15px;">
                CALIFICACIONES REGULARES
            </h4>

            @if($promedio_general_materias > 0)
            <div style="text-align: right; margin-bottom: 10px;">
                <strong>Promedio General: </strong>
                <span class="badge badge-success" style="font-size: 12px;">
                    {{ $promedio_general_materias }}
                </span>
            </div>
            @endif

            <table class="table">
                <thead class="table-primary">
                    <tr>
                        <th style="width: 20%;">Materia</th>
                        <th style="width: 20%;">Competencia</th>
                        <th style="width: 25%;">Criterio</th>
                        <th style="width: 5%;">Bim</th>
                        <th style="width: 5%;">CRIT</th>
                        <th style="width: 15%;">Calificación</th>
                        <th style="width: 10%;">Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    @php $criterioCounter = 0; $competenciaCounter = 0; @endphp
                    @foreach($materias_procesadas as $materiaIndex => $materia)
                        @php $competenciaInMateriaCounter = 0; @endphp
                        @foreach($materia['competencias'] as $competenciaIndex => $competencia)
                            @php
                                $competenciaCounter++;
                                $competenciaInMateriaCounter++;
                            @endphp

                            <!-- Mostrar criterios de la competencia -->
                            @foreach($competencia['criterios'] as $criterioIndex => $criterio)
                                @php $criterioCounter++; @endphp
                                <tr>
                                    <!-- Columna Materia -->
                                    @if($competenciaIndex === 0 && $criterioIndex === 0)
                                    <td rowspan="{{ $materia['rowspan'] }}" class="align-middle bg-light text-center">
                                        <div class="materia-nombre">
                                            {{ $materia['nombre'] }}
                                        </div>
                                    </td>
                                    @endif

                                    <!-- Columna Competencia -->
                                    @if($criterioIndex === 0)
                                    <td rowspan="{{ $competencia['criterios_count'] + 1 }}"
                                        class="align-middle bg-success">
                                        <div class="competencia-nombre">
                                            {{ $competencia['nombre'] }}
                                        </div>
                                    </td>
                                    @endif

                                    <!-- Columna Criterio -->
                                    <td class="align-middle">
                                        {{ $criterio['criterio_nombre'] }}
                                    </td>

                                    <!-- Columna Bimestre -->
                                    <td class="text-center align-middle">
                                        @if($criterio['nota'] && $criterio['nota']['bimestre'])
                                        <span class="text-info">
                                            B{{ $criterio['nota']['bimestre'] }}
                                        </span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <!-- Columna CRIT -->
                                    <td class="text-center align-middle">
                                        <strong class="text-info">C{{ $criterioCounter }}</strong>
                                    </td>

                                    <!-- Columna Calificación -->
                                    <td class="text-center align-middle">
                                        @if($criterio['nota'])
                                            @php
                                                $nota = $criterio['nota']['valor'];
                                                $textClass = ($nota == 'C' || $nota == '1') ? 'text-danger' : '';
                                            @endphp
                                            <span class="nota-valor {{ $textClass }}">
                                                {{ $nota }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">N/A</span>
                                        @endif
                                    </td>

                                    <!-- Columna Promedio Materia -->
                                    @if($competenciaIndex === 0 && $criterioIndex === 0)
                                    <td rowspan="{{ $materia['rowspan'] }}" class="text-center align-middle">
                                        @if($materia['promedio'] > 0)
                                            @php $materiaTextClass = ($materia['promedio'] == 'C' || $materia['promedio'] == '1') ? 'text-danger' : ''; @endphp
                                            <div>
                                                <span class="nota-valor {{ $materiaTextClass }}">
                                                    {{ $materia['promedio'] }}
                                                </span>
                                                <div style="font-size: 9px; color: #666;">
                                                    {{ $materia['total_competencias'] }} comp.
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                            @endforeach

                            <!-- Fila de Valoración de Competencia -->
                            <tr class="bg-warning">
                                <!-- Columna Criterio -->
                                <td class="align-middle">
                                    <strong>Valoración Competencia</strong>
                                </td>

                                <!-- Columna Bimestre -->
                                <td class="text-center align-middle">
                                    @if($competencia['ultimo_criterio'] && $competencia['ultimo_criterio']['nota'] && $competencia['ultimo_criterio']['nota']['bimestre'])
                                    <span class="text-info">
                                        B{{ $competencia['ultimo_criterio']['nota']['bimestre'] }}
                                    </span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Columna CRIT -->
                                <td class="text-center align-middle">
                                    <strong class="text-success">N{{ $competenciaCounter }}</strong>
                                </td>

                                <!-- Columna Calificación -->
                                <td class="text-center align-middle">
                                    @if($competencia['promedio'] > 0)
                                    @php $compTextClass = ($competencia['promedio'] == 'C' || $competencia['promedio'] == '1') ? 'text-danger' : ''; @endphp
                                    <span class="nota-valor {{ $compTextClass }}">
                                        {{ $competencia['promedio'] }}
                                    </span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="padding: 10px; background-color: #f8f9fa; font-size: 10px;">
                            <div style="display: flex; justify-content: space-between;">
                                <div>
                                    <strong>
                                        @if($tipo_pdf == 'cualitativo')
                                            Escala cualitativa: C (1), B (2), A (3), AD (4)
                                        @else
                                            Escala de calificación: 1-4
                                        @endif
                                    </strong>
                                </div>
                                <div>
                                    <strong>Total CRIT (C):</strong> {{ $numero_criterio_global }} |
                                    <strong>Total Valoraciones (N):</strong> {{ $numero_competencia_global }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <!-- Competencias Transversales -->
        @if(count($competencias_transversales) > 0)
        <div class="keep-together mt-4">
            <h4 style="color: #17a2b8; border-bottom: 2px solid #17a2b8; padding-bottom: 5px; margin-bottom: 15px;">
                COMPETENCIAS TRANSVERSALES
            </h4>

            <table class="table">
                <thead class="table-info">
                    <tr>
                        <th style="width: 60%">CRITERIO</th>
                        <th style="width: 20%" class="text-center">BIMESTRE</th>
                        <th style="width: 20%" class="text-center">NOTA PROMEDIO</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($criterios_transversales as $criterioNombre => $data)
                    @php
                        $promedioCriterio = $promedios_por_criterio[$criterioNombre] ?? 0;
                        $bimestresUnicos = array_unique($data['bimestres']);
                        $bimestreTexto = count($bimestresUnicos) > 0 ?
                            implode(', ', $bimestresUnicos) : '-';
                    @endphp
                    <tr>
                        <td>{{ $criterioNombre }}</td>
                        <td class="text-center">
                            @if($bimestreTexto != '-')
                                {{ $bimestreTexto }}
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($promedioCriterio > 0)
                                @php $transversalClass = ($promedioCriterio == 'C' || $promedioCriterio == '1') ? 'text-danger' : ''; @endphp
                                <span class="nota-valor {{ $transversalClass }}">
                                    {{ $promedioCriterio }}
                                </span>
                                <div style="font-size: 9px; color: #666;">
                                    ({{ count($data['notas']) }} eval.)
                                </div>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    <!-- Fila de Valoración General -->
                    <tr style="background-color: #d1ecf1;">
                        <td colspan="2" class="text-right">
                            <strong>Valoración General de Competencias Transversales</strong>
                        </td>
                        <td class="text-center">
                            @php $generalTransClass = ($promedio_general_transversales == 'C' || $promedio_general_transversales == '1') ? 'text-danger' : ''; @endphp
                            <strong class="nota-valor {{ $generalTransClass }}">{{ $promedio_general_transversales }}</strong>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="alert alert-info mt-3">
                <strong>Nota:</strong> Las competencias transversales se evalúan de forma independiente y
                <strong>no se incluyen</strong> en el cálculo del promedio regular de las materias.
                Su promedio general se calcula únicamente entre las competencias transversales.
            </div>
        </div>
        @endif

        <!-- Calificaciones de Conducta -->
        @if(!empty($conductas_agrupadas))
        <div class="keep-together mt-4">
            <h4 style="color: #17a2b8; border-bottom: 2px solid #17a2b8; padding-bottom: 5px; margin-bottom: 15px;">
                CALIFICACIONES DE CONDUCTA
            </h4>

            <!-- Tabla resumen de conductas (promedios) -->
            <div style="margin-bottom: 20px;">
                <table class="table" style="margin-bottom: 0;">
                    <thead class="table-info">
                        <tr>
                            <th style="width: 50%">Conducta</th>
                            <th style="width: 30%" class="text-center">Bimestre</th>
                            <th style="width: 20%" class="text-center">Calificación Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conductas_agrupadas as $conductaId => $datosConducta)
                        <tr>
                            <td>{{ $datosConducta['nombre'] }}</td>
                            <td class="text-center">
                                @if($total_bimestres_conducta > 0)
                                    @for($i = 1; $i <= $total_bimestres_conducta; $i++)
                                        @if(isset($datosConducta['bimestres'][$i]))
                                            B{{ $i }}
                                        @endif
                                    @endfor
                                @else
                                    <span class="text-muted"> - </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php $condClass = ($datosConducta['promedio'] == 'C' || $datosConducta['promedio'] == '1') ? 'text-danger' : ''; @endphp
                                <span class="nota-valor {{ $condClass }}">{{ $datosConducta['promedio'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Asistencias -->
        @if($asistencias->count() > 0)
        <div class="keep-together mt-4">
            <h4 style="color: #856404; border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px; font-family: 'Times New Roman', serif;">
                REGISTRO DE ASISTENCIAS
            </h4>

            @if(count($resumen_asistencias['tipos']) > 0)
            <div style="margin-bottom: 20px; border: 2px solid #333; border-radius: 0; background-color: #fff;">
                <div style="background-color: #ffc107; color: #333; padding: 8px; font-weight: bold; border-bottom: 2px solid #333;">
                    RESUMEN DE ASISTENCIAS
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($resumen_asistencias['tipos'] as $index => $tipo)
                    <tr>
                        <td style="width: 70%; border-bottom: 1px solid #ccc; padding: 10px;
                                @if($index % 2 == 0) background-color: #f9f9f9; @endif">
                            {{ $tipo['tipo_nombre'] }}
                        </td>
                        <td style="width: 30%; border-bottom: 1px solid #ccc; padding: 10px; text-align: center; font-weight: bold;
                                @if($index % 2 == 0) background-color: #f9f9f9; @endif">
                            {{ $tipo['cantidad'] }}
                        </td>
                    </tr>
                    @endforeach
                    <!-- Línea separadora -->
                    <tr>
                        <td colspan="2" style="border-top: 2px solid #333; padding: 0;"></td>
                    </tr>
                    <!-- Total -->
                    <tr style="background-color: #fff3cd;">
                        <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 14px;">
                            TOTAL:
                        </td>
                        <td style="padding: 12px; text-align: center; font-weight: bold; font-size: 16px; color: #d35400;">
                            {{ $resumen_asistencias['total'] }}
                        </td>
                    </tr>
                </table>
            </div>
            @endif
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <strong>Generado el:</strong> {{ $fecha_generacion }}
                </div>
                <div>
                    <strong>Formato:</strong>
                    @if($tipo_pdf == 'cualitativo')
                        <span style="color: #155724; font-weight: bold;">CUALITATIVO</span>
                    @else
                        <span style="color: #0c5460; font-weight: bold;">CUANTITATIVO</span>
                    @endif
                </div>
            </div>
            <div style="text-align: center; margin-top: 5px;">
                Sistema de Gestión Académica - {{ $colegio->nombre ?? 'Colegio' }}
            </div>
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 9;
            $font = $fontMetrics->getFont("Arial");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 30;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
