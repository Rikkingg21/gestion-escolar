@extends('layouts.app')
@section('title', 'Editar Periodo')
@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Editar Periodo: {{ $periodo->nombre }}
                    </h5>
                </div>

                <div class="card-body">
                    <form action="{{ route('periodo.update', $periodo->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   id="nombre"
                                   name="nombre"
                                   value="{{ old('nombre', $periodo->nombre) }}">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="anio" class="form-label">Año</label>
                                <select class="form-select @error('anio') is-invalid @enderror"
                                        id="anio"
                                        name="anio">
                                    @for($i = date('Y') - 5; $i <= date('Y') + 5; $i++)
                                        <option value="{{ $i }}" {{ old('anio', $periodo->anio) == $i ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                                @error('anio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tipo_periodo" class="form-label">Tipo de Periodo</label>
                                <select class="form-select @error('tipo_periodo') is-invalid @enderror"
                                        id="tipo_periodo"
                                        name="tipo_periodo">
                                    <option value="año escolar" {{ old('tipo_periodo', $periodo->tipo_periodo) == 'año escolar' ? 'selected' : '' }}>
                                        Año Escolar
                                    </option>
                                    <option value="recuperación" {{ old('tipo_periodo', $periodo->tipo_periodo) == 'recuperación' ? 'selected' : '' }}>
                                        Recuperación
                                    </option>
                                </select>
                                @error('tipo_periodo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date"
                                       class="form-control @error('fecha_inicio') is-invalid @enderror"
                                       id="fecha_inicio"
                                       name="fecha_inicio"
                                       value="{{ old('fecha_inicio', $periodo->fecha_inicio ? \Carbon\Carbon::parse($periodo->fecha_inicio)->format('Y-m-d') : '') }}">
                                @error('fecha_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date"
                                       class="form-control @error('fecha_fin') is-invalid @enderror"
                                       id="fecha_fin"
                                       name="fecha_fin"
                                       value="{{ old('fecha_fin', $periodo->fecha_fin ? \Carbon\Carbon::parse($periodo->fecha_fin)->format('Y-m-d') : '') }}">
                                @error('fecha_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                      id="descripcion"
                                      name="descripcion"
                                      rows="3">{{ old('descripcion', $periodo->descripcion) }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select @error('estado') is-invalid @enderror"
                                    id="estado"
                                    name="estado">
                                <option value="1" {{ old('estado', $periodo->estado) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('estado', $periodo->estado) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información adicional:</strong>
                            <ul class="mb-0 mt-2">
                                <li>ID: <strong>{{ $periodo->id }}</strong></li>
                                <li>Creado: <strong>{{ $periodo->created_at ? $periodo->created_at->format('d/m/Y H:i') : 'N/A' }}</strong></li>
                                <li>Matrículas asociadas: <strong>{{ $periodo->matriculas()->count() }}</strong></li>
                                @if($periodo->matriculas()->count() > 0)
                                    <li class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <small>Cambiar año o tipo puede afectar matrículas existentes</small>
                                    </li>
                                @endif
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('periodo.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Actualizar Periodo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Validación de fechas en tiempo real
    document.getElementById('fecha_inicio').addEventListener('change', function() {
        const fechaFin = document.getElementById('fecha_fin');
        if (fechaFin.value && this.value > fechaFin.value) {
            alert('La fecha de inicio no puede ser mayor a la fecha de fin');
            this.value = '';
        }
    });

    document.getElementById('fecha_fin').addEventListener('change', function() {
        const fechaInicio = document.getElementById('fecha_inicio');
        if (fechaInicio.value && this.value < fechaInicio.value) {
            alert('La fecha de fin no puede ser menor a la fecha de inicio');
            this.value = '';
        }
    });

    // Advertencia al cambiar año o tipo si tiene matrículas
    @if($periodo->matriculas()->count() > 0)
        const anioSelect = document.getElementById('anio');
        const tipoSelect = document.getElementById('tipo_periodo');

        [anioSelect, tipoSelect].forEach(select => {
            if (select) {
                select.addEventListener('change', function() {
                    if (this.value !== '{{ old('anio', $periodo->anio) }}' &&
                        this.id === 'anio') {
                        if (!confirm('ADVERTENCIA: Cambiar el año afectará las matrículas existentes. ¿Deseas continuar?')) {
                            this.value = '{{ $periodo->anio }}';
                        }
                    }

                    if (this.value !== '{{ old('tipo_periodo', $periodo->tipo_periodo) }}' &&
                        this.id === 'tipo_periodo') {
                        if (!confirm('ADVERTENCIA: Cambiar el tipo de periodo puede causar inconsistencias. ¿Deseas continuar?')) {
                            this.value = '{{ $periodo->tipo_periodo }}';
                        }
                    }
                });
            }
        });
    @endif
</script>
@endsection
