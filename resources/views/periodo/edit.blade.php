@extends('layouts.app')
@section('title', 'Editar Periodo')
@section('content')
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-calendar-edit me-2"></i>Editar Periodo
                </h4>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('periodo.update', $periodo->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Campos ocultos para nombre y año -->
                    <input type="hidden" name="nombre" value="{{ $periodo->nombre }}">
                    <input type="hidden" name="anio" value="{{ $periodo->anio }}">

                    <div class="row mb-4">
                        <!-- Información de solo lectura -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted">
                                <i class="fas fa-tag me-1"></i>Nombre del Periodo
                            </label>
                            <div class="border rounded p-3 bg-light">
                                <h5 class="mb-0 text-dark">{{ $periodo->nombre }}</h5>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted">
                                <i class="fas fa-calendar me-1"></i>Año Académico
                            </label>
                            <div class="border rounded p-3 bg-light">
                                <h5 class="mb-0 text-dark">{{ $periodo->anio }}</h5>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Campos editables -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label fw-bold">
                                <i class="fas fa-toggle-on me-1"></i>Estado
                            </label>
                            <select name="estado" id="estado" class="form-select" required>
                                <option value="1" {{ $periodo->estado == 1 ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ $periodo->estado == 0 ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="descripcion" class="form-label fw-bold">
                                <i class="fas fa-file-alt me-1"></i>Descripción
                            </label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3">{{ $periodo->descripcion }}</textarea>
                            <small class="text-muted">Máximo 250 caracteres</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('periodo.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
