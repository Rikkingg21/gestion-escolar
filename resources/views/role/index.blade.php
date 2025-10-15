@extends('layouts.app')
@section('title', 'Roles')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center p-3">
                    <h5 class="mb-0 fs-5">
                        <i class="bi bi-person-badge me-2"></i>Gestión de Roles
                    </h5>

                    <div class="btn-group" role="group" aria-label="Acciones de roles">
                        <a href="{{ route('role.create') }}" class="btn btn-light btn-sm text-primary">
                            <i class="bi bi-plus-lg me-1"></i>Nuevo Rol
                        </a>

                        <a href="{{ route('module.index') }}" class="btn btn-light btn-sm text-primary">
                            <i class="bi bi-grid me-1"></i>Ver Módulos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-4" id="rolesTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" data-bs-toggle="tab" href="#activo" aria-selected="true" role="tab">
                                Activos
                                <span class="badge bg-success ms-1">{{ $rolesActivos->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-bs-toggle="tab" href="#inactivo" aria-selected="false" role="tab" tabindex="-1">
                                Inactivos
                                <span class="badge bg-danger ms-1">{{ $rolesInactivos->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="myTabContent">
                        <!-- Tab Activos -->
                        <div class="tab-pane fade active show" id="activo" role="tabpanel">
                            @if($rolesActivos->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th>Usuarios</th>
                                                <th>Módulos</th>
                                                <th width="120">Estado</th>
                                                <th width="150">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rolesActivos as $role)
                                                <tr>
                                                    <td>{{ $role->id }}</td>
                                                    <td>
                                                        <strong>{{ $role->nombre }}</strong>
                                                    </td>
                                                    <td>
                                                        @if($role->descripcion)
                                                            <small class="text-muted">{{ $role->descripcion }}</small>
                                                        @else
                                                            <span class="text-muted fst-italic">Sin descripción</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            {{ $role->users()->count() }} usuarios
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            {{ $role->modules()->count() }} módulos
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Activo</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de rol">
                                                            <a href="{{ route('role.edit', $role->id) }}" class="btn btn-outline-primary"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>

                                                            <a href="{{ route('role.module', $role->id) }}" class="btn btn-outline-warning"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Ver Modulos Asignados">
                                                                <i class="bi bi-gear-wide-connected"></i>
                                                            </a>

                                                            <button class="btn btn-outline-danger eliminar-rol"
                                                                data-id="{{ $role->id }}"
                                                                data-nombre="{{ $role->nombre }}"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar">
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
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle me-2"></i>No hay roles activos registrados.
                                </div>
                            @endif
                        </div>

                        <!-- Tab Inactivos -->
                        <div class="tab-pane fade" id="inactivo" role="tabpanel">
                            @if($rolesInactivos->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th>Usuarios</th>
                                                <th>Módulos</th>
                                                <th width="120">Estado</th>
                                                <th width="150">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rolesInactivos as $role)
                                                <tr>
                                                    <td>{{ $role->id }}</td>
                                                    <td>
                                                        <strong>{{ $role->nombre }}</strong>
                                                    </td>
                                                    <td>
                                                        @if($role->descripcion)
                                                            <small class="text-muted">{{ $role->descripcion }}</small>
                                                        @else
                                                            <span class="text-muted fst-italic">Sin descripción</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            {{ $role->users()->count() }} usuarios
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            {{ $role->modules()->count() }} módulos
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger">Inactivo</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de rol">
                                                            <a href="{{ route('role.edit', $role->id) }}" class="btn btn-outline-primary"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>

                                                            <a href="{{ route('role.module', $role->id) }}" class="btn btn-outline-warning"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Ver Modulos Asignados">
                                                                <i class="bi bi-gear-wide-connected"></i>
                                                            </a>

                                                            <button class="btn btn-outline-danger eliminar-rol"
                                                                data-id="{{ $role->id }}"
                                                                data-nombre="{{ $role->nombre }}"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar">
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
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle me-2"></i>No hay roles inactivos.
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
                <p>¿Está seguro que desea eliminar el rol <strong id="nombreRolEliminar"></strong>?</p>
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
<script>
$(document).ready(function() {
    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Eliminar rol
    $('.eliminar-rol').on('click', function() {
        const roleId = $(this).data('id');
        const roleNombre = $(this).data('nombre');

        $('#nombreRolEliminar').text(roleNombre);
        $('#formEliminar').attr('action', "{{ url('role') }}/" + roleId);
        $('#modalEliminar').modal('show');
    });
});
</script>
@endsection
