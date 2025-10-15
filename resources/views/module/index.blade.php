@extends('layouts.app')
@section('title', 'Módulos del Sistema')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cubes me-2"></i>Gestión de Módulos del Sistema
                    </h5>
                    <a href="{{ route('module.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Nuevo Módulo
                    </a>
                </div>
                <div class="card-body">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-4" id="modulesTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" data-bs-toggle="tab" href="#activo" aria-selected="true" role="tab">
                                <i class="fas fa-check-circle me-1"></i>Activos
                                <span class="badge bg-success ms-1">{{ $modulesActivos->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-bs-toggle="tab" href="#inactivo" aria-selected="false" role="tab" tabindex="-1">
                                <i class="fas fa-times-circle me-1"></i>Inactivos
                                <span class="badge bg-danger ms-1">{{ $modulesInactivos->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="myTabContent">
                        <!-- Tab Activos -->
                        <div class="tab-pane fade active show" id="activo" role="tabpanel">
                            @if($modulesActivos->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="60">ID</th>
                                                <th>Módulo</th>
                                                <th>Icono</th>
                                                <th>Ruta Base</th>
                                                <th>Roles Asignados</th>
                                                <th width="100">Estado</th>
                                                <th width="150">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($modulesActivos as $module)
                                                <tr>
                                                    <td>{{ $module->id }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="{{ $module->icono }} text-primary me-3 fs-5"></i>
                                                            <div>
                                                                <h6 class="mb-0">{{ $module->nombre }}</h6>
                                                                @if($module->descripcion)
                                                                    <small class="text-muted">{{ $module->descripcion }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code class="text-sm">{{ $module->icono }}</code>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            <i class="fas fa-link me-1 text-muted"></i>
                                                            {{ $module->ruta_base }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-users me-1"></i>
                                                            {{ $module->roles()->count() }} roles
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Activo
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('module.edit', $module->id) }}"
                                                                class="btn btn-outline-primary"
                                                                data-bs-toggle="tooltip" title="Editar módulo">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <button class="btn btn-outline-danger eliminar-modulo"
                                                                    data-id="{{ $module->id }}"
                                                                    data-nombre="{{ $module->nombre }}"
                                                                    data-roles="{{ $module->roles()->count() }}"
                                                                    data-bs-toggle="tooltip" title="Eliminar módulo">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info text-center py-4">
                                    <i class="fas fa-cubes fa-3x text-info mb-3"></i>
                                    <h5>No hay módulos activos</h5>
                                    <p class="mb-0">Comienza creando el primer módulo del sistema.</p>
                                    <a href="{{ route('module.create') }}" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus me-1"></i>Crear Primer Módulo
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Tab Inactivos -->
                        <div class="tab-pane fade" id="inactivo" role="tabpanel">
                            @if($modulesInactivos->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="60">ID</th>
                                                <th>Módulo</th>
                                                <th>Icono</th>
                                                <th>Ruta Base</th>
                                                <th>Roles Asignados</th>
                                                <th width="100">Estado</th>
                                                <th width="150">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($modulesInactivos as $module)
                                                <tr>
                                                    <td>{{ $module->id }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="{{ $module->icono }} text-muted me-3 fs-5"></i>
                                                            <div>
                                                                <h6 class="mb-0 text-muted">{{ $module->nombre }}</h6>
                                                                @if($module->descripcion)
                                                                    <small class="text-muted">{{ $module->descripcion }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code class="text-sm text-muted">{{ $module->icono }}</code>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-muted border">
                                                            <i class="fas fa-link me-1"></i>
                                                            {{ $module->ruta_base }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-users me-1"></i>
                                                            {{ $module->roles()->count() }} roles
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Inactivo
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('module.edit', $module->id) }}"
                                                               class="btn btn-outline-primary"
                                                               data-bs-toggle="tooltip" title="Editar módulo">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <button class="btn btn-outline-danger eliminar-modulo"
                                                                    data-id="{{ $module->id }}"
                                                                    data-nombre="{{ $module->nombre }}"
                                                                    data-roles="{{ $module->roles()->count() }}"
                                                                    data-bs-toggle="tooltip" title="Eliminar módulo">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-warning text-center py-4">
                                    <i class="fas fa-ban fa-3x text-warning mb-3"></i>
                                    <h5>No hay módulos inactivos</h5>
                                    <p class="mb-0">Todos los módulos se encuentran activos en el sistema.</p>
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
                <div class="alert alert-warning mt-3">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-circle me-1"></i>Información del módulo:
                    </h6>
                    <ul class="mb-0">
                        <li>Roles asignados: <strong id="rolesModulo"></strong></li>
                    </ul>
                </div>
                <p class="text-danger mb-0">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Solo se puede eliminar módulos sin roles asignados.
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
<script>
$(document).ready(function() {
    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Cambiar estado del módulo
    $('.cambiar-estado').on('click', function() {
        const moduleId = $(this).data('id');
        if (confirm('¿Está seguro de cambiar el estado de este módulo?')) {
            $.ajax({
                url: "{{ url('module') }}/" + moduleId + "/cambiar-estado",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Error al cambiar el estado del módulo');
                }
            });
        }
    });

    // Eliminar módulo
    $('.eliminar-modulo').on('click', function() {
        const moduleId = $(this).data('id');
        const moduleNombre = $(this).data('nombre');
        const rolesAsignados = $(this).data('roles');

        $('#nombreModuloEliminar').text(moduleNombre);
        $('#rolesModulo').text(rolesAsignados);
        $('#formEliminar').attr('action', "{{ url('module') }}/" + moduleId);
        $('#modalEliminar').modal('show');
    });
});
</script>
@endsection
