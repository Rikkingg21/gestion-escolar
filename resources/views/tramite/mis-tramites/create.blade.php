@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">
                <i class="fas fa-plus me-2"></i>Nuevo Trámite
            </h4>
        </div>

        <div class="card-body">
            <form action="{{ route('tramite.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Trámite <span class="text-danger">*</span></label>
                        <select name="tipo_tramite_id" class="form-select @error('tipo_tramite_id') is-invalid @enderror" required>
                            <option value="">Seleccione...</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('tipo_tramite_id') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }} @if($tipo->costo > 0) (S/ {{ number_format($tipo->costo, 2) }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_tramite_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Relación</label>
                        <select name="relacion" class="form-select">
                            <option value="">Seleccione...</option>
                            <option value="Padre" {{ old('relacion') == 'Padre' ? 'selected' : '' }}>Padre</option>
                            <option value="Madre" {{ old('relacion') == 'Madre' ? 'selected' : '' }}>Madre</option>
                            <option value="Tutor" {{ old('relacion') == 'Tutor' ? 'selected' : '' }}>Tutor</option>
                            <option value="Estudiante" {{ old('relacion') == 'Estudiante' ? 'selected' : '' }}>Estudiante</option>
                            <option value="Docente" {{ old('relacion') == 'Docente' ? 'selected' : '' }}>Docente</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="4">{{ old('observaciones') }}</textarea>
                    </div>

                    <div class="col-12 text-end">
                        <a href="{{ route('tramite.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Enviar Solicitud
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
