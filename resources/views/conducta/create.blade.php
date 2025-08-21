@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Crear Nueva Conducta</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('conducta.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Conducta *</label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   id="nombre" name="nombre" value="{{ old('nombre') }}"
                                   placeholder="Ingrese el nombre de la conducta" required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado *</label>
                            <select class="form-select @error('estado') is-invalid @enderror"
                                    id="estado" name="estado" required>
                                <option value="">Seleccione el estado</option>
                                <option value="1" {{ old('estado') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('conducta.index') }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Conducta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    .card-header {
        background: linear-gradient(45deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
    }
</style>
@endsection
