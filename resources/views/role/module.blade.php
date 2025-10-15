@extends('layouts.app')
@section('title', 'Asignar módulos a ' . $role->nombre)
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cubes me-2"></i>Asignar Módulos al Rol: {{ $role->nombre }}
                    </h5>
                    <a href="{{ route('role.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver a Roles
                    </a>
                </div>
                <div class="card-body">
                    <!-- Información del Rol -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong><i class="fas fa-id-card me-1"></i>ID:</strong> {{ $role->id }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong><i class="fas fa-users me-1"></i>Usuarios:</strong> {{ $role->users()->count() }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong><i class="fas fa-cube me-1"></i>Módulos asignados:</strong> {{ $modulesAsignados->count() }}
                                    </div>
                                </div>
                                @if($role->descripcion)
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <strong><i class="fas fa-align-left me-1"></i>Descripción:</strong> {{ $role->descripcion }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Módulos Asignados -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-check-circle me-1"></i>Módulos Asignados
                                        <span class="badge bg-light text-success ms-1">{{ $modulesAsignados->count() }}</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($modulesAsignados->count() > 0)
                                        <div class="list-group">
                                            @foreach($modulesAsignados as $module)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class="{{ $module->icono }} text-success me-3 fs-5"></i>
                                                        <div>
                                                            <h6 class="mb-0">{{ $module->nombre }}</h6>
                                                            <small class="text-muted">
                                                                <code>{{ $module->ruta_base }}</code>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <form action="{{ route('role.remove-module', [$role->id, $module->id]) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                onclick="return confirm('¿Está seguro de remover este módulo?')">
                                                            <i class="bi bi-dash-circle"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No hay módulos asignados a este rol.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Módulos Disponibles -->
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-plus-circle me-1"></i>Módulos Disponibles
                                        <span class="badge bg-light text-primary ms-1">{{ $modulesDisponibles->count() }}</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($modulesDisponibles->count() > 0)
                                        <div class="list-group">
                                            @foreach($modulesDisponibles as $module)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class="{{ $module->icono }} text-primary me-3 fs-5"></i>
                                                        <div>
                                                            <h6 class="mb-0">{{ $module->nombre }}</h6>
                                                            <small class="text-muted">
                                                                <code>{{ $module->ruta_base }}</code>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <form action="{{ route('role.assign-module', $role->id) }}"
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                        <input type="hidden" name="estado" value="1">
                                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                                            <i class="fas fa-plus"></i> Asignar
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Todos los módulos están asignados.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Asignación Rápida -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-bolt me-1"></i>Asignación Rápida
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('role.assign-module', $role->id) }}" method="POST" class="row g-3">
                                        @csrf
                                        <div class="col-md-8">
                                            <label for="module_id" class="form-label">Seleccionar Módulo</label>
                                            <select class="form-select" id="module_id" name="module_id" required>
                                                <option value="">Seleccione un módulo...</option>
                                                @foreach($modulesDisponibles as $module)
                                                    <option value="{{ $module->id }}">
                                                        {{ $module->nombre }} ({{ $module->ruta_base }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-select" id="estado" name="estado" required>
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="fas fa-plus me-1"></i>Asignar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-seleccionar el primer módulo disponible en la asignación rápida
    @if($modulesDisponibles->count() > 0)
        $('#module_id').val('{{ $modulesDisponibles->first()->id }}');
    @endif

    // Confirmación para remover módulos
    $('.btn-remove').on('click', function() {
        return confirm('¿Está seguro de remover este módulo del rol?');
    });
});
</script>

<style>
.list-group-item {
    border-left: 4px solid transparent;
    transition: all 0.2s ease;
}

.list-group-item:hover {
    border-left-color: #0d6efd;
    background-color: #f8f9fa;
}

.card {
    border: none;
    border-radius: 10px;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}
</style>
@endsection
