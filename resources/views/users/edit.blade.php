@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Editar Usuario: {{ $user->nombreCompleto }}</h1>
    </div>

    <!-- Mensajes de sesión -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Errores de validación -->
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user->id) }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="dni" class="form-label">DNI</label>
                        <input type="text" class="form-control @error('dni') is-invalid @enderror"
                               id="dni" name="dni" value="{{ old('dni', $user->dni) }}" required>
                        @error('dni')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control @error('nombre_usuario') is-invalid @enderror"
                               id="nombre_usuario" name="nombre_usuario"
                               value="{{ old('nombre_usuario', $user->nombre_usuario) }}" required>
                        @error('nombre_usuario')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="nombre" class="form-label">Nombres</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                               id="nombre" name="nombre" value="{{ old('nombre', $user->nombre) }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
                        <input type="text" class="form-control @error('apellido_paterno') is-invalid @enderror"
                               id="apellido_paterno" name="apellido_paterno"
                               value="{{ old('apellido_paterno', $user->apellido_paterno) }}" required>
                        @error('apellido_paterno')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="apellido_materno" class="form-label">Apellido Materno</label>
                        <input type="text" class="form-control @error('apellido_materno') is-invalid @enderror"
                               id="apellido_materno" name="apellido_materno"
                               value="{{ old('apellido_materno', $user->apellido_materno) }}">
                        @error('apellido_materno')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-control @error('estado') is-invalid @enderror"
                                id="estado" name="estado" required>
                            <option value="activo" {{ old('estado', $user->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado', $user->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Sección de Roles -->
                <div class="mb-3">
                    <label class="form-label">Roles</label>
                    @foreach($availableRoles as $role)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="roles[]"
                                   value="{{ $role->id }}"
                                   id="role_{{ $role->id }}"
                                   @if(in_array($role->id, old('roles', $user->roles->pluck('id')->toArray()))) checked @endif
                                   @if($role->nombre == 'admin' && session('current_role') == 'director') disabled @endif>
                            <label class="form-check-label" for="role_{{ $role->id }}">
                                {{ ucfirst($role->nombre) }}
                            </label>
                        </div>
                    @endforeach
                    @error('roles')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Sección de Contraseña (opcional cambiar) -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control"
                               id="password_confirmation" name="password_confirmation">
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Cambios
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
