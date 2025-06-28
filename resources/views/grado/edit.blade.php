@extends('layouts.app')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="container">
    <h3 class="mb-4">Editar Grado</h3>
    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('grado.update', $grado->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="grado" class="form-label">Grado</label>
                    <input type="number" min="1" max="12" class="form-control @error('grado') is-invalid @enderror" id="grado" name="grado" value="{{ old('grado', $grado->grado) }}" required>
                    @error('grado')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="seccion" class="form-label">Sección</label>
                    <input type="text" maxlength="1" class="form-control @error('seccion') is-invalid @enderror" id="seccion" name="seccion" value="{{ old('seccion', $grado->seccion) }}" required>
                    @error('seccion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="nivel" class="form-label">Nivel</label>
                    <select name="nivel" id="nivel" class="form-select @error('nivel') is-invalid @enderror" required>
                        <option value="primaria" {{ old('nivel', strtolower($grado->nivel)) == 'primaria' ? 'selected' : '' }}>Primaria</option>
                        <option value="secundaria" {{ old('nivel', strtolower($grado->nivel)) == 'secundaria' ? 'selected' : '' }}>Secundaria</option>
                    </select>
                    @error('nivel')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                        <option value="1" {{ old('estado', $grado->estado) == 1 ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('estado', $grado->estado) == 0 ? 'selected' : '' }}>Inactivo</option>
                    </select>
                    @error('estado')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('grado.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
