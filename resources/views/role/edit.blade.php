@extends('layouts.app')
@section('title', 'Editar Rol')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Editar Rol: {{ $role->nombre }}
                    </h5>
                    <a href="{{ route('role.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('role.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre del Rol *</label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       id="nombre" name="nombre" value="{{ old('nombre', $role->nombre) }}"
                                       placeholder="Ej: Administrador" required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado *</label>
                                <select class="form-select @error('estado') is-invalid @enderror"
                                        id="estado" name="estado" required>
                                    <option value="">Seleccionar Estado</option>
                                    <option value="1" {{ old('estado', $role->estado) == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado', $role->estado) == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('estado')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                          id="descripcion" name="descripcion" rows="3"
                                          placeholder="Descripción opcional del rol">{{ old('descripcion', $role->descripcion) }}</textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Información adicional del rol -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading mb-2">
                                        <i class="fas fa-info-circle me-1"></i>Información del Rol
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small><strong>ID:</strong> {{ $role->id }}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <small><strong>Usuarios asignados:</strong> {{ $role->users()->count() }}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <small><strong>Módulos asignados:</strong> {{ $role->modules()->count() }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Actualizar Rol
                                </button>
                                <a href="{{ route('role.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>

                                @if($role->users()->count() == 0 && $role->modules()->count() == 0)
                                    <button type="button" class="btn btn-danger float-end"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar">
                                        <i class="fas fa-trash me-1"></i>Eliminar Rol
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
@if($role->users()->count() == 0 && $role->modules()->count() == 0)
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
                <p>¿Está seguro que desea eliminar el rol <strong>{{ $role->nombre }}</strong>?</p>
                <p class="text-danger mb-0">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Esta acción no se puede deshacer.
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('role.destroy', $role->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
