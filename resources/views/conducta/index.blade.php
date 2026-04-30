@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <h1>Gestión de Conductas</h1>

    <div class="row">
        <!-- SECCIÓN 1: LISTA DE CONDUCTAS -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Conductas Registradas</h4>
                    <a href="{{ route('conducta.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Crear Conducta
                    </a>
                </div>
                <div class="card-body">
                    <!-- Pestañas de Conductas -->
                    <ul class="nav nav-tabs" id="conductaTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="activas-tab" data-bs-toggle="tab" data-bs-target="#activas" type="button" role="tab">
                                Activas ({{ $conductasActivas->count() }})
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="inactivas-tab" data-bs-toggle="tab" data-bs-target="#inactivas" type="button" role="tab">
                                Inactivas ({{ $conductasInactivas->count() }})
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Conductas Activas -->
                        <div class="tab-pane fade show active" id="activas" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-success">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th width="100">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($conductasActivas as $conducta)
                                        <tr>
                                            <td>{{ $conducta->id }}</td>
                                            <td>{{ $conducta->nombre }}</td>
                                            <td>
                                                <a href="{{ route('conducta.edit', $conducta->id) }}" class="btn btn-primary btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('conducta.destroy', $conducta->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar esta conducta?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center">No hay conductas activas</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Conductas Inactivas -->
                        <div class="tab-pane fade" id="inactivas" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($conductasInactivas as $conducta)
                                        <tr>
                                            <td>{{ $conducta->id }}</td>
                                            <td>{{ $conducta->nombre }}</td>
                                            <td>
                                                <a href="{{ route('conducta.edit', $conducta->id) }}" class="btn btn-primary btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('conducta.destroy', $conducta->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center">No hay conductas inactivas</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: PERIODOS CON BIMESTRES Y CONDUCTAS -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Conductas por Periodo y Bimestre</h4>
                    <div>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalMigrar">
                            <i class="fas fa-copy"></i> Migrar Conductas
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Periodos Activos (Acordeón) -->
                    @if($periodosActivos->isNotEmpty())
                        <h5 class="text-success">Periodos Activos</h5>
                        <div class="accordion mb-4" id="accordionPeriodosActivos">
                            @foreach($periodosActivos as $index => $periodo)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading{{ $periodo->id }}">
                                        <button class="accordion-button {{ $index != 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $periodo->id }}">
                                            <strong class="text-black">{{ $periodo->nombre }} ({{ $periodo->anio }})</strong>
                                            @if($periodo->descripcion)
                                                <small class="text-muted ms-2">- {{ $periodo->descripcion }}</small>
                                            @endif
                                            <span class="badge bg-success ms-2">Activo</span>
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $periodo->id }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" data-bs-parent="#accordionPeriodosActivos">
                                        <div class="accordion-body">
                                            @foreach($periodo->periodobimestres as $bimestre)
                                                <div class="card mb-2">
                                                    <div class="card-header bg-light">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <strong>
                                                                {{ $bimestre->bimestre }}° Bimestre
                                                                ({{ $bimestre->sigla }})
                                                            </strong>
                                                            <button type="button"
                                                                    class="btn btn-primary btn-sm btn-asignar-conductas"
                                                                    data-bimestre-id="{{ $bimestre->id }}"
                                                                    data-bimestre-nombre="Bimestre {{ $bimestre->bimestre }} - {{ $periodo->nombre }}">
                                                                <i class="fas fa-tasks"></i> Asignar Conductas
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        @if($bimestre->conductas->count() > 0)
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered">
                                                                    <thead class="table-info">
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Nombre de Conducta</th>
                                                                            <th width="80">Acción</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($bimestre->conductas as $conducta)
                                                                        <tr>
                                                                            <td width="50">{{ $conducta->id }}</td>
                                                                            <td>{{ $conducta->nombre }}</td>
                                                                            <td class="text-center">
                                                                                <button type="button"
                                                                                        class="btn btn-danger btn-sm btn-eliminar-conducta"
                                                                                        data-bimestre-id="{{ $bimestre->id }}"
                                                                                        data-conducta-id="{{ $conducta->id }}"
                                                                                        data-conducta-nombre="{{ $conducta->nombre }}"
                                                                                        title="Eliminar conducta del bimestre">
                                                                                    <i class="bi bi-x-circle"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @else
                                                            <div class="alert alert-warning mb-0">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                                No hay conductas asignadas a este bimestre
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Periodos Inactivos (Lista con links) -->
                    @if($periodosInactivos->isNotEmpty())
                        <h5 class="text-secondary mt-3">Periodos Inactivos</h5>
                        <div class="list-group">
                            @foreach($periodosInactivos as $periodo)
                                <a href="{{ route('conducta.periodo-inactivo', $periodo->id) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $periodo->nombre }} ({{ $periodo->anio }})</strong>
                                            @if($periodo->descripcion)
                                                <small class="text-muted">- {{ $periodo->descripcion }}</small>
                                            @endif
                                        </div>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($periodosActivos->isEmpty() && $periodosInactivos->isEmpty())
                        <div class="alert alert-info">No hay periodos de tipo 'año escolar' registrados</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA ASIGNAR CONDUCTAS A BIMESTRE -->
<div class="modal fade" id="modalAsignarConductas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Asignar Conductas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAsignarConductas" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Bimestre:</strong> <span id="bimestreNombre"></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-bold">Seleccionar Conductas:</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAllConductas">
                                        </th>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                    </tr>
                                </thead>
                                <tbody id="listaConductasCheckbox">
                                    @foreach($conductasActivas as $conducta)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   name="conducta_ids[]"
                                                   value="{{ $conducta->id }}"
                                                   class="conducta-checkbox">
                                        </td>
                                        <td>{{ $conducta->id }}</td>
                                        <td>{{ $conducta->nombre }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <input type="hidden" name="periodo_bimestre_id" id="periodoBimestreId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Asignación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PARA MIGRAR CONDUCTAS -->
<div class="modal fade" id="modalMigrar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Migrar Conductas de Periodo Anterior</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('conducta.migrar') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Esta acción copiará todas las conductas asignadas del periodo origen al periodo destino<br>
                        <strong>Nota:</strong> La migración se realizará usando las siglas (B1, B2, B3, B4)
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Periodo Origen:</label>
                        <select name="periodo_origen_id" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            @foreach($periodosActivos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }} ({{ $periodo->anio }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Periodo Destino:</label>
                        <select name="periodo_destino_id" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            @foreach($periodosActivos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }} ({{ $periodo->anio }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Migrar Conductas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Select all functionality
    $('#selectAllConductas').change(function() {
        $('.conducta-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Open modal to assign conductas
    $('.btn-asignar-conductas').click(function() {
        const bimestreId = $(this).data('bimestre-id');
        const bimestreNombre = $(this).data('bimestre-nombre');

        console.log('Abriendo modal para bimestre:', bimestreId); // Para depuración

        $('#periodoBimestreId').val(bimestreId);
        $('#bimestreNombre').text(bimestreNombre);

        // Reset checkboxes primero
        $('.conducta-checkbox').prop('checked', false);
        $('#selectAllConductas').prop('checked', false);

        // Load existing assignments
        $.ajax({
            url: `/conducta/conductas-por-bimestre/${bimestreId}`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('Conductas asignadas recibidas:', data);

                if (data.success && data.conductas_asignadas) {
                    // Marcar las conductas que ya están asignadas
                    data.conductas_asignadas.forEach(function(conductaId) {
                        $(`input.conducta-checkbox[value="${conductaId}"]`).prop('checked', true);
                        console.log('Marcando conducta:', conductaId);
                    });

                    // Actualizar el select all si todas están marcadas
                    const totalCheckboxes = $('.conducta-checkbox').length;
                    const checkedCheckboxes = $('.conducta-checkbox:checked').length;
                    $('#selectAllConductas').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
                } else {
                    console.warn('No se recibieron conductas asignadas:', data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar conductas asignadas:', error);
                console.error('Respuesta del servidor:', xhr.responseText);
                alert('Error al cargar las conductas asignadas. Revisa la consola para más detalles.');
            }
        });

        $('#modalAsignarConductas').modal('show');
    });

    // Submit asignacion form
    $('#formAsignarConductas').submit(function(e) {
        e.preventDefault();

        // Mostrar indicador de carga
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('Guardando...');

        $.ajax({
            url: '{{ route("conducta.asignar") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                console.log('Respuesta al guardar:', response);
                if(response.success) {
                    // Cerrar modal y recargar
                    $('#modalAsignarConductas').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function(xhr) {
                console.error('Error en la petición:', xhr);
                let errorMsg = 'Error al guardar: ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMsg += xhr.statusText;
                } else {
                    errorMsg += 'Error desconocido';
                }
                alert(errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Eliminar conducta de un bimestre
    $(document).on('click', '.btn-eliminar-conducta', function() {
        const bimestreId = $(this).data('bimestre-id');
        const conductaId = $(this).data('conducta-id');
        const conductaNombre = $(this).data('conducta-nombre');

        if(confirm(`¿Desasignar la conducta "${conductaNombre}" de este bimestre?`)) {
            // Mostrar indicador en el botón
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');

            $.ajax({
                url: '{{ route("conducta.eliminar-bimestre") }}',
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    periodo_bimestre_id: bimestreId,
                    conducta_id: conductaId
                },
                success: function(response) {
                    console.log('Respuesta al eliminar:', response);
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Error desconocido'));
                        btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    console.error('Error al eliminar:', xhr);
                    alert('Error al desasignar la conducta: ' + (xhr.responseJSON?.message || xhr.statusText));
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        }
    });

    // Depuración: Verificar que los botones de eliminar existen
    console.log('Botones de eliminar encontrados:', $('.btn-eliminar-conducta').length);
});
</script>
@endsection
