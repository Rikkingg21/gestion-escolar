@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Editar Materia</h1>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <form action="{{ route('materia.update', $materia->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <div class="mb-3">
                        <label for="nombre">Nombre de la Materia</label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre', $materia->nombre) }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select @error('estado') is-invalid @enderror" name="estado" id="estado">
                            <option value="1" {{ old('estado', $materia->estado) == 1 ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('estado', $materia->estado) == 0 ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('materia.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
