@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-plus-circle me-2"></i> Nueva Competencia para: {{ $materia->nombre }}
        </h1>

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

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('materiacompetencia.store') }}" method="POST">
                @csrf
                <input type="hidden" name="materia_id" value="{{ $materia->id }}">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre de la Competencia *</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                               id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                  id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar Competencia
                    </button>
                    <a href="{{ route('materiacompetencia.index', $materia->id) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
