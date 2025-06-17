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
            <form method="POST" action="{{ route('apoderados.update', $apoderado->id) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="parentesco" class="form-label">Parentesco</label>
                    <select class="form-select" id="parentesco" name="parentesco" required>
                        <option value="padre" {{ $apoderado->parentesco == 'padre' ? 'selected' : '' }}>Padre</option>
                        <option value="madre" {{ $apoderado->parentesco == 'madre' ? 'selected' : '' }}>Madre</option>
                        <option value="tutor" {{ $apoderado->parentesco == 'tutor' ? 'selected' : '' }}>Tutor</option>
                        <option value="otro" {{ $apoderado->parentesco == 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="telefono1" class="form-label">Teléfono 1</label>
                    <input type="text" class="form-control @error('telefono1') is-invalid @enderror" id="telefono1" name="telefono1" value="{{ old('telefono1', $apoderado->telefono1) }}" required>
                    @error('telefono1')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="telefono2" class="form-label">Teléfono 2</label>
                    <input type="text" class="form-control @error('telefono2') is-invalid @enderror" id="telefono2" name="telefono2" value="{{ old('telefono2', $apoderado->telefono2) }}">
                    @error('telefono2')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
