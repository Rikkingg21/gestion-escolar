<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte de Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .table th {
            background-color: #f8f9fa;
        }

        /* Estilos para impresión */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                font-size: 12px;
            }
            .container-fluid {
                padding: 0 !important;
            }
            .card {
                border: none !important;
                margin: 0 !important;
            }
            .card-body {
                padding: 0 !important;
            }
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #000;
            }
            .print-info {
                display: block !important;
                background: #f8f9fa;
                padding: 10px;
                margin-bottom: 15px;
                font-size: 11px;
            }
            .table {
                font-size: 10px;
            }
            .table th, .table td {
                padding: 4px;
            }
            .print-footer {
                display: block !important;
                text-align: center;
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #ccc;
                font-size: 9px;
            }
            .select2-container {
                display: none !important;
            }
        }

        .print-header, .print-info, .print-footer {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Encabezado para impresión -->
    <div class="print-header">
        <h2>REPORTE DE ASISTENCIA</h2>
        <div>Generado el: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="container-fluid py-3">
        <!-- Botón de regreso -->
        <div class="row mb-3 no-print">
            <div class="col-12">
                <a href="{{ route('asistencia.index') }}" class="btn btn-secondary btn-sm">
                    ← Volver a Asistencias
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row no-print">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <form id="formReporte" method="GET" action="{{ route('asistencia.reporte') }}">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="periodo_id" class="form-label">Período *</label>
                                    <select name="periodo_id" id="periodo_id" class="form-select" required>
                                        <option value="">Seleccionar Período</option>
                                        @foreach($periodos as $periodo)
                                            <option value="{{ $periodo->id }}"
                                                {{ request('periodo_id') == $periodo->id ? 'selected' : '' }}>
                                                {{ $periodo->nombre }} ({{ $periodo->anio }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="grado_id" class="form-label">Grado *</label>
                                    <select name="grado_id" id="grado_id" class="form-select" required>
                                        <option value="">Seleccionar Grado</option>
                                        @foreach($grados as $grado)
                                            <option value="{{ $grado->id }}"
                                                {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                                {{ $grado->nivel }} - {{ $grado->grado }} {{ $grado->seccion }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="estudiante_id" class="form-label">Estudiante (Opcional)</label>
                                    <select name="estudiante_id" id="estudiante_id" class="form-select" style="width: 100%;" disabled>
                                        <option value="">Todos los estudiantes</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="periodobimestre_id" class="form-label">Bimestre (Opcional)</label>
                                    <select name="periodobimestre_id" id="periodobimestre_id" class="form-select">
                                        <option value="">Todos los bimestres</option>
                                        @foreach($bimestres as $bimestre)
                                            <option value="{{ $bimestre->id }}"
                                                data-fecha-inicio="{{ $bimestre->fecha_inicio }}"
                                                data-fecha-fin="{{ $bimestre->fecha_fin }}"
                                                {{ request('periodobimestre_id') == $bimestre->id ? 'selected' : '' }}>
                                                {{ $bimestre->bimestre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
                                        value="{{ request('fecha_inicio') }}" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="fecha_fin" class="form-label">Fecha Fin *</label>
                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
                                        value="{{ request('fecha_fin') }}" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="tipo_asistencia_id" class="form-label">Tipo Asistencia (Opcional)</label>
                                    <select name="tipo_asistencia_id" id="tipo_asistencia_id" class="form-select">
                                        <option value="">Todos los tipos</option>
                                        @foreach($tiposAsistencia as $tipo)
                                            <option value="{{ $tipo->id }}"
                                                {{ request('tipo_asistencia_id') == $tipo->id ? 'selected' : '' }}>
                                                {{ $tipo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Generar Reporte</button>
                                    <a href="{{ route('asistencia.reporte') }}" class="btn btn-outline-secondary">Limpiar Filtros</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resultados -->
        @if(request()->has('grado_id') && request()->has('periodo_id'))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center no-print">
                        <h5 class="mb-0">Resultados del Reporte</h5>
                        <div>
                            <span class="badge bg-secondary">{{ $asistencias->count() }} registros</span>
                            <button class="btn btn-outline-primary btn-sm ms-2" onclick="window.print()">Imprimir</button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($asistencias->count() > 0)
                            <!-- Información del filtro para impresión -->
                            <div class="print-info">
                                <strong>Filtros aplicados:</strong><br>
                                @php
                                    $periodoSeleccionado = $periodos->firstWhere('id', request('periodo_id'));
                                    $gradoSeleccionado = $grados->firstWhere('id', request('grado_id'));
                                @endphp
                                Período: {{ $periodoSeleccionado->nombre ?? 'N/A' }} ({{ $periodoSeleccionado->anio ?? 'N/A' }}) |
                                Grado: {{ $gradoSeleccionado->nivel ?? 'N/A' }} - {{ $gradoSeleccionado->grado ?? '' }}{{ $gradoSeleccionado->seccion ?? '' }} |
                                Fechas: {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }} |
                                Registros: {{ $asistencias->count() }}
                            </div>

                            <!-- Información del filtro para pantalla -->
                            <div class="alert alert-info no-print">
                                <strong>Filtros aplicados:</strong>
                                Período: {{ $periodoSeleccionado->nombre ?? 'N/A' }} |
                                Grado: {{ $gradoSeleccionado->nivel ?? 'N/A' }} - {{ $gradoSeleccionado->grado ?? '' }}{{ $gradoSeleccionado->seccion ?? '' }} |
                                Fechas: {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }}
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha</th>
                                            <th>Estudiante</th>
                                            <th>Grado</th>
                                            <th>Tipo Asistencia</th>
                                            <th>Bimestre</th>
                                            <th>Hora</th>
                                            <th>Período</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($asistencias as $index => $asistencia)
                                        @php
                                            $apellidos = trim($asistencia->estudiante->user->apellido_paterno . ' ' . $asistencia->estudiante->user->apellido_materno);
                                            $nombres = $asistencia->estudiante->user->nombre;
                                            $nombreCompleto = $apellidos . ', ' . $nombres;
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}</td>
                                            <td>{{ $nombreCompleto }}</td>
                                            <td>{{ $asistencia->grado->nivel ?? 'N/A' }} - {{ $asistencia->grado->grado ?? '' }}{{ $asistencia->grado->seccion ?? '' }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $asistencia->tipoasistencia->nombre ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @if($asistencia->periodobimestre)
                                                    {{ $asistencia->periodobimestre->bimestre }}
                                                @else
                                                    N/A
                                                @endif
                                             </td>
                                            <td>{{ $asistencia->hora ?? 'N/A' }}</td>
                                            <td>{{ $asistencia->periodo->nombre ?? 'N/A' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning text-center">
                                <strong>No se encontraron registros</strong>
                                <p class="mb-0 mt-1">No hay registros de asistencia que coincidan con los filtros seleccionados.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Pie de página para impresión -->
    <div class="print-footer">
        Reporte generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} | Sistema de Gestión Escolar
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2 solo para estudiantes
            $('#estudiante_id').select2({
                placeholder: "Buscar estudiante...",
                allowClear: true,
                width: '100%'
            });

            // Cargar estudiantes cuando se seleccionen grado y período
            function cargarEstudiantes() {
                var gradoId = $('#grado_id').val();
                var periodoId = $('#periodo_id').val();
                var estudianteSelect = $('#estudiante_id');

                if (gradoId && periodoId) {
                    estudianteSelect.prop('disabled', false);
                    estudianteSelect.empty().append('<option value="">Cargando...</option>');
                    estudianteSelect.trigger('change');

                    $.ajax({
                        url: '{{ route("asistencia.estudiantes-por-grado") }}',
                        type: 'GET',
                        data: { grado_id: gradoId, periodo_id: periodoId },
                        success: function(data) {
                            estudianteSelect.empty().append('<option value="">Todos los estudiantes</option>');
                            $.each(data, function(key, estudiante) {
                                estudianteSelect.append('<option value="' + estudiante.id + '">' + estudiante.nombres_completos + '</option>');
                            });
                            estudianteSelect.trigger('change');
                        },
                        error: function() {
                            estudianteSelect.empty().append('<option value="">Error al cargar estudiantes</option>');
                            estudianteSelect.trigger('change');
                        }
                    });
                } else {
                    estudianteSelect.prop('disabled', true);
                    estudianteSelect.empty().append('<option value="">Seleccione grado y período primero</option>');
                    estudianteSelect.trigger('change');
                }
            }

            // Cargar bimestres y establecer fechas
            function cargarBimestresYEstandarizarFechas() {
                var periodoId = $('#periodo_id').val();
                var bimestreSelect = $('#periodobimestre_id');

                if (periodoId) {
                    $.ajax({
                        url: '/asistencia/bimestres-por-periodo/' + periodoId,
                        type: 'GET',
                        success: function(data) {
                            bimestreSelect.html('<option value="">Todos los bimestres</option>');
                            if (data.success && data.bimestres.length > 0) {
                                $.each(data.bimestres, function(key, bimestre) {
                                    bimestreSelect.append(
                                        '<option value="' + bimestre.id + '" data-fecha-inicio="' + bimestre.fecha_inicio + '" data-fecha-fin="' + bimestre.fecha_fin + '">' +
                                        bimestre.bimestre + '</option>'
                                    );
                                });
                                bimestreSelect.prop('disabled', false);
                            } else {
                                bimestreSelect.append('<option value="">No hay bimestres disponibles</option>');
                            }
                        }
                    });
                }
            }

            // Al seleccionar un bimestre, actualizar fechas
            $('#periodobimestre_id').change(function() {
                var selectedOption = $(this).find('option:selected');
                var fechaInicio = selectedOption.data('fecha-inicio');
                var fechaFin = selectedOption.data('fecha-fin');

                if (fechaInicio && fechaFin) {
                    $('#fecha_inicio').val(fechaInicio);
                    $('#fecha_fin').val(fechaFin);
                }
            });

            // Eventos
            $('#periodo_id').change(function() {
                cargarBimestresYEstandarizarFechas();
                cargarEstudiantes();
            });

            $('#grado_id').change(cargarEstudiantes);

            // Validar fechas
            $('#fecha_inicio, #fecha_fin').change(function() {
                var fechaInicio = $('#fecha_inicio').val();
                var fechaFin = $('#fecha_fin').val();
                if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                    alert('La fecha de inicio no puede ser mayor a la fecha fin');
                    $('#fecha_fin').val('');
                }
            });

            // Cargar datos iniciales
            @if(request('periodo_id'))
                cargarBimestresYEstandarizarFechas();
                @if(request('grado_id') && request('periodo_id'))
                    cargarEstudiantes();
                    // Restaurar valor seleccionado de estudiante si existe
                    @if(request('estudiante_id'))
                        setTimeout(function() {
                            $('#estudiante_id').val('{{ request('estudiante_id') }}').trigger('change');
                        }, 500);
                    @endif
                @endif
            @endif
        });
    </script>
</body>
</html>
