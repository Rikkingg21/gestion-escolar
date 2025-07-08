@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-pencil-square me-2"></i> Editar Criterio: {{ $criterio->nombre }}
        </h1>
        <a href="{{ route('materiacriterio.index', $criterio->materia_competencia_id) }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('materiacriterio.update', $criterio->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="nombre" class="form-label">Nombre del Criterio *</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                               id="nombre" name="nombre" value="{{ old('nombre', $criterio->nombre) }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                  id="descripcion" name="descripcion" rows="4">{{ old('descripcion', $criterio->descripcion) }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-save me-1"></i> Actualizar Criterio
                        </button>
                        <a href="{{ route('materiacriterio.index', $criterio->materia_competencia_id) }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
