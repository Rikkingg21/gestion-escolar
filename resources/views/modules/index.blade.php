@extends('layouts.app')

@section('title', 'Gestión de Módulos')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cubes me-2"></i>Gestión de Módulos
                    </h5>
                    <a href="{{ route('modules.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Nuevo Módulo
                    </a>
                </div>
                <div class="card-body">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-4" id="modulesTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="activos-tab" data-bs-toggle="tab"
                                    data-bs-target="#activos" type="button" role="tab">
                                <i class="fas fa-check-circle me-1"></i>Activos
                                <span class="badge bg-success ms-1">{{ $modulosActivos->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inactivos-tab" data-bs-toggle="tab"
                                    data-bs-target="#inactivos" type="button" role="tab">
                                <i class="fas fa-times-circle me-1"></i>Inactivos
                                <span class="badge bg-danger ms-1">{{ $modulosInactivos->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="modulesTabContent">
                        <!-- Tab Activos -->
                        <div class="tab-pane fade show active" id="activos" role="tabpanel">
                            @if($modulosActivos->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Nombre</th>
                                                <th>Icono</th>
                                                <th>Ruta Base</th>
                                                <th width="120">Estado</th>
                                                <th width="200">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($modulosActivos as $module)
                                                <tr>
                                                    <td>{{ $module->id }}</td>
                                                    <td>
                                                        <strong>{{ $module->nombre }}</strong>
                                                    </td>
                                                    <td>
                                                        <i class="{{ $module->icono }} me-1"></i>
                                                        <small class="text-muted">{{ $module->icono }}</small>
                                                    </td>
                                                    <td>
                                                        <code>{{ $module->ruta_base }}</code>
                                                    </td>
                                                    <td>
                                                        {!! $module->estado_badge !!}
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="#" class="btn btn-outline-primary"
                                                               data-bs-toggle="tooltip" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button class="btn btn-outline-warning cambiar-estado"
                                                                    data-id="{{ $module->id }}"
                                                                    data-bs-toggle="tooltip" title="Desactivar">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger eliminar-modulo"
                                                                    data-id="{{ $module->id }}"
                                                                    data-nombre="{{ $module->nombre }}"
                                                                    data-bs-toggle="tooltip" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle me-2"></i>No hay módulos activos registrados.
                                </div>
                            @endif
                        </div>

                        <!-- Tab Inactivos -->
                        <div class="tab-pane fade" id="inactivos" role="tabpanel">
                            @if($modulosInactivos->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Nombre</th>
                                                <th>Icono</th>
                                                <th>Ruta Base</th>
                                                <th width="120">Estado</th>
                                                <th width="200">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($modulosInactivos as $module)
                                                <tr>
                                                    <td>{{ $module->id }}</td>
                                                    <td>
                                                        <strong>{{ $module->nombre }}</strong>
                                                    </td>
                                                    <td>
                                                        <i class="{{ $module->icono }} me-1"></i>
                                                        <small class="text-muted">{{ $module->icono }}</small>
                                                    </td>
                                                    <td>
                                                        <code>{{ $module->ruta_base }}</code>
                                                    </td>
                                                    <td>
                                                        {!! $module->estado_badge !!}
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="#" class="btn btn-outline-primary"
                                                               data-bs-toggle="tooltip" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button class="btn btn-outline-success cambiar-estado"
                                                                    data-id="{{ $module->id }}"
                                                                    data-bs-toggle="tooltip" title="Activar">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger eliminar-modulo"
                                                                    data-id="{{ $module->id }}"
                                                                    data-nombre="{{ $module->nombre }}"
                                                                    data-bs-toggle="tooltip" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle me-2"></i>No hay módulos inactivos.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el módulo <strong id="nombreModuloEliminar"></strong>?</p>
                <p class="text-danger mb-0">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Esta acción no se puede deshacer.
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Cambiar estado
    $('.cambiar-estado').on('click', function() {
        const moduleId = $(this).data('id');
        $.ajax({
            url: "{{ url('modules') }}/" + moduleId + "/cambiar-estado",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                location.reload();
            },
            error: function(xhr) {
                alert('Error al cambiar el estado');
            }
        });
    });

    // Eliminar módulo
    $('.eliminar-modulo').on('click', function() {
        const moduleId = $(this).data('id');
        const moduleNombre = $(this).data('nombre');

        $('#nombreModuloEliminar').text(moduleNombre);
        $('#formEliminar').attr('action', "{{ url('modules') }}/" + moduleId);
        $('#modalEliminar').modal('show');
    });
});
</script>
@endsection
