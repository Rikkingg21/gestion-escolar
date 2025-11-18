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
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .card {
            border: 1px solid #dee2e6;
            box-shadow: none;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #e9ecef;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            padding: 12px 15px;
        }

        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .form-control {
            border: 1px solid #ced4da;
            padding: 8px 12px;
            height: 38px;
        }

        .btn {
            padding: 8px 16px;
            border: 1px solid transparent;
        }

        .table {
            border: 1px solid #dee2e6;
        }

        .table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            font-weight: 600;
            padding: 10px;
        }

        .table td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
        }

        .badge {
            padding: 4px 8px;
            font-size: 12px;
        }

        .alert {
            border: 1px solid transparent;
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

            .print-header h2 {
                margin-bottom: 5px;
                font-size: 18px;
            }

            .print-info {
                display: block !important;
                background: #f8f9fa;
                padding: 12px;
                margin-bottom: 15px;
                border-left: 4px solid #007bff;
                font-size: 11px;
            }

            .print-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 8px;
            }

            .print-info-item {
                margin-bottom: 3px;
            }

            .print-info-label {
                font-weight: 600;
            }

            .table {
                font-size: 10px;
            }

            .table th {
                background: #f8f9fa !important;
                color: #000;
                padding: 6px;
            }

            .table td {
                padding: 5px;
            }

            .badge {
                font-size: 9px;
                padding: 2px 6px;
            }

            .print-footer {
                display: block !important;
                text-align: center;
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #ccc;
                font-size: 9px;
            }

            tr {
                page-break-inside: avoid;
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
        <div style="font-size: 11px;">
            Generado el: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="container-fluid py-3">
        <!-- Botón de regreso -->
        <div class="row mb-3 no-print">
            <div class="col-12">
                <a href="{{ route('asistencia.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Asistencias
                </a>
            </div>
        </div>

        <!-- Filtros (no se imprimen) -->
        <div class="row no-print">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <form id="formReporte" method="GET" action="{{ route('asistencia.reporte') }}">
                            <div class="row mb-3">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label for="estudiante_id" class="form-label">Estudiante (Opcional)</label>
                                    <select name="estudiante_id" id="estudiante_id" class="form-select"
                                        {{ !request('grado_id') ? 'disabled' : '' }}>
                                        <option value="">Todos los estudiantes</option>
                                        @if(request('grado_id'))
                                            @php
                                                $estudiantesFiltro = \App\Models\Estudiante::where('grado_id', request('grado_id'))
                                                    ->where('estado', 1)
                                                    ->with('user')
                                                    ->get();
                                            @endphp
                                            @foreach($estudiantesFiltro as $estudiante)
                                                @php
                                                    $apellidos = trim($estudiante->user->apellido_paterno . ' ' . $estudiante->user->apellido_materno);
                                                    $nombres = $estudiante->user->nombre;
                                                    $nombreCompleto = $apellidos . ', ' . $nombres;
                                                @endphp
                                                <option value="{{ $estudiante->id }}"
                                                    {{ request('estudiante_id') == $estudiante->id ? 'selected' : '' }}>
                                                    {{ $nombreCompleto }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_inicio" class="form-label">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
                                        value="{{ request('fecha_inicio') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_fin" class="form-label">Fecha Fin *</label>
                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
                                        value="{{ request('fecha_fin') }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="bimestre" class="form-label">Bimestre (Opcional)</label>
                                    <select name="bimestre" id="bimestre" class="form-select">
                                        <option value="">Todos los bimestres</option>
                                        <option value="1" {{ request('bimestre') == '1' ? 'selected' : '' }}>Bimestre 1</option>
                                        <option value="2" {{ request('bimestre') == '2' ? 'selected' : '' }}>Bimestre 2</option>
                                        <option value="3" {{ request('bimestre') == '3' ? 'selected' : '' }}>Bimestre 3</option>
                                        <option value="4" {{ request('bimestre') == '4' ? 'selected' : '' }}>Bimestre 4</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipo_asistencia_id" class="form-label">Tipo de Asistencia (Opcional)</label>
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
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i> Generar Reporte
                                    </button>
                                    <a href="{{ route('asistencia.reporte') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-broom me-1"></i> Limpiar Filtros
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resultados -->
        @if(request()->has('grado_id'))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center no-print">
                        <h5 class="mb-0">Resultados del Reporte</h5>
                        <div>
                            <span class="badge bg-secondary me-2">
                                {{ $asistencias->count() }} registros
                            </span>
                            <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Imprimir
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($asistencias->count() > 0)
                            <!-- Información del filtro para impresión -->
                            <div class="print-info">
                                <div class="print-info-grid">
                                    <div class="print-info-item">
                                        <span class="print-info-label">Grado:</span>
                                        {{ $grados->firstWhere('id', request('grado_id'))->nivel ?? 'N/A' }} -
                                        {{ $grados->firstWhere('id', request('grado_id'))->grado ?? '' }}
                                        {{ $grados->firstWhere('id', request('grado_id'))->seccion ?? '' }}
                                    </div>
                                    <div class="print-info-item">
                                        <span class="print-info-label">Fecha Inicio:</span>
                                        {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }}
                                    </div>
                                    <div class="print-info-item">
                                        <span class="print-info-label">Fecha Fin:</span>
                                        {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }}
                                    </div>
                                    <div class="print-info-item">
                                        <span class="print-info-label">Estudiante:</span>
                                        @if(request('estudiante_id'))
                                            @php
                                                $estudianteSeleccionado = \App\Models\Estudiante::with('user')
                                                    ->find(request('estudiante_id'));
                                                if ($estudianteSeleccionado) {
                                                    $apellidos = trim($estudianteSeleccionado->user->apellido_paterno . ' ' . $estudianteSeleccionado->user->apellido_materno);
                                                    $nombres = $estudianteSeleccionado->user->nombre;
                                                    echo $apellidos . ', ' . $nombres;
                                                }
                                            @endphp
                                        @else
                                            Todos los estudiantes
                                        @endif
                                    </div>
                                    @if(request('bimestre'))
                                    <div class="print-info-item">
                                        <span class="print-info-label">Bimestre:</span>
                                        {{ request('bimestre') }}
                                    </div>
                                    @endif
                                    @if(request('tipo_asistencia_id'))
                                    <div class="print-info-item">
                                        <span class="print-info-label">Tipo Asistencia:</span>
                                        {{ $tiposAsistencia->firstWhere('id', request('tipo_asistencia_id'))->nombre ?? 'N/A' }}
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Información del filtro para pantalla -->
                            <div class="row mb-3 no-print">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong>Filtros aplicados:</strong>
                                        Grado: {{ $grados->firstWhere('id', request('grado_id'))->nivel ?? 'N/A' }} -
                                        {{ $grados->firstWhere('id', request('grado_id'))->grado ?? '' }}
                                        {{ $grados->firstWhere('id', request('grado_id'))->seccion ?? '' }} |
                                        Fechas: {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }} -
                                        {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }} |
                                        Registros: {{ $asistencias->count() }}
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="100">Fecha</th>
                                            <th>Estudiante</th>
                                            <th width="120">Grado</th>
                                            <th width="120">Tipo Asistencia</th>
                                            <th width="80">Bimestre</th>
                                            <th width="80">Hora</th>
                                            <th>Descripción</th>
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
                                                <span class="badge bg-secondary">
                                                    {{ $asistencia->tipoasistencia->nombre ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ $asistencia->bimestre }}</td>
                                            <td>{{ $asistencia->hora ?? 'N/A' }}</td>
                                            <td>{{ $asistencia->descripcion ?? 'Sin descripción' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
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
    <script src="https://kit.fontawesome.com/6d4a4c422c.js" crossorigin="anonymous"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#grado_id, #estudiante_id, #bimestre, #tipo_asistencia_id').select2();

            // Cargar estudiantes cuando se seleccione un grado
            $('#grado_id').change(function() {
                var gradoId = $(this).val();
                var estudianteSelect = $('#estudiante_id');

                if (gradoId) {
                    estudianteSelect.prop('disabled', false);

                    // Limpiar opciones actuales
                    estudianteSelect.empty().append('<option value="">Todos los estudiantes</option>');

                    // Mostrar loading
                    estudianteSelect.prop('disabled', true);
                    estudianteSelect.html('<option value="">Cargando estudiantes...</option>');

                    // Cargar estudiantes del grado seleccionado
                    $.ajax({
                        url: '{{ route("asistencia.estudiantes-por-grado") }}',
                        type: 'GET',
                        data: {
                            grado_id: gradoId
                        },
                        success: function(data) {
                            estudianteSelect.empty().append('<option value="">Todos los estudiantes</option>');
                            $.each(data, function(key, estudiante) {
                                estudianteSelect.append(
                                    '<option value="' + estudiante.id + '">' +
                                    estudiante.nombres_completos +
                                    '</option>'
                                );
                            });
                            estudianteSelect.prop('disabled', false);
                        },
                        error: function() {
                            alert('Error al cargar los estudiantes');
                            estudianteSelect.empty().append('<option value="">Todos los estudiantes</option>');
                            estudianteSelect.prop('disabled', false);
                        }
                    });
                } else {
                    estudianteSelect.prop('disabled', true);
                    estudianteSelect.empty().append('<option value="">Todos los estudiantes</option>');
                }
            });

            // Validar fechas
            $('#fecha_inicio, #fecha_fin').change(function() {
                var fechaInicio = $('#fecha_inicio').val();
                var fechaFin = $('#fecha_fin').val();

                if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                    alert('La fecha de inicio no puede ser mayor a la fecha fin');
                    $('#fecha_fin').val('');
                }
            });

            // Si ya hay un grado seleccionado al cargar la página, cargar estudiantes
            @if(request('grado_id'))
                $('#grado_id').trigger('change');
            @endif

            // Atajo de teclado Ctrl + P
            $(document).on('keydown', function(e) {
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    window.print();
                }
            });
        });
    </script>
</body>
</html>
