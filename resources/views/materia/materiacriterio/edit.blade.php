@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <i class="bi bi-pencil-square me-2"></i> Editar Criterio de Evaluación
                            </h4>
                            <p class="mb-0 mt-1 small">
                                Editando criterio específico #{{ $criterio->id }}
                            </p>
                        </div>
                        <a href="{{ route('materiacriterio.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('materiacriterio.update', $criterio->id) }}">
                        @csrf
                        @method('PUT')

                        {{-- Información de solo lectura (Materia y Competencia) --}}
                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label text-md-end fw-semibold">
                                Materia
                            </label>
                            <div class="col-md-7">
                                <div class="form-control-plaintext fw-semibold">
                                    {{ $criterio->materia->nombre }}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label text-md-end fw-semibold">
                                Competencia
                            </label>
                            <div class="col-md-7">
                                <div class="form-control-plaintext">
                                    {{ $criterio->materiaCompetencia->nombre }}
                                </div>
                            </div>
                        </div>

                        {{-- Editar solo lo necesario --}}
                        <div class="row mb-3">
                            <label for="nombre" class="col-md-3 col-form-label text-md-end fw-semibold">
                                Nombre del Criterio *
                            </label>
                            <div class="col-md-7">
                                <textarea id="nombre"
                                          name="nombre"
                                          class="form-control @error('nombre') is-invalid @enderror"
                                          rows="4"
                                          required>{{ old('nombre', $criterio->nombre) }}</textarea>
                                @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="grado_id" class="col-md-3 col-form-label text-md-end fw-semibold">
                                Grado *
                            </label>
                            <div class="col-md-7">
                                <select id="grado_id"
                                        name="grado_id"
                                        class="form-select @error('grado_id') is-invalid @enderror"
                                        required>
                                    @php
                                        $gradosPorNivel = $grados->groupBy('nivel');
                                    @endphp
                                    @foreach($gradosPorNivel as $nivel => $gradosNivel)
                                        <optgroup label="{{ $nivel }}">
                                            @foreach($gradosNivel as $grado)
                                                <option value="{{ $grado->id }}"
                                                    {{ old('grado_id', $criterio->grado_id) == $grado->id ? 'selected' : '' }}>
                                                    {{ $grado->grado }}° "{{ $grado->seccion }}" - {{ $grado->nivel }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('grado_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="periodo_id" class="col-md-3 col-form-label text-md-end fw-semibold">
                                Período Escolar *
                            </label>
                            <div class="col-md-7">
                                <select id="periodo_id"
                                        name="periodo_id"
                                        class="form-select @error('periodo_id') is-invalid @enderror"
                                        required>
                                    <option value="">Seleccione un período</option>
                                    @foreach($periodos as $periodo)
                                        <option value="{{ $periodo->id }}"
                                            {{ $criterio->periodoBimestre->periodo_id == $periodo->id ? 'selected' : '' }}>
                                            {{ $periodo->nombre }} ({{ $periodo->anio }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('periodo_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="periodo_bimestre_id" class="col-md-3 col-form-label text-md-end fw-semibold">
                                Bimestre *
                            </label>
                            <div class="col-md-7">
                                <select id="periodo_bimestre_id"
                                        name="periodo_bimestre_id"
                                        class="form-select @error('periodo_bimestre_id') is-invalid @enderror"
                                        required>
                                    <option value="">Seleccione un bimestre</option>
                                    @foreach($bimestresDelPeriodo as $bimestre)
                                        <option value="{{ $bimestre->id }}"
                                            {{ old('periodo_bimestre_id', $criterio->periodo_bimestre_id) == $bimestre->id ? 'selected' : '' }}>
                                            {{ $bimestre->sigla }} - Bimestre {{ $bimestre->bimestre }}
                                            <small class="text-muted">
                                                ({{ \Carbon\Carbon::parse($bimestre->fecha_inicio)->format('d/m') }} -
                                                {{ \Carbon\Carbon::parse($bimestre->fecha_fin)->format('d/m') }})
                                            </small>
                                        </option>
                                    @endforeach
                                </select>
                                @error('periodo_bimestre_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="descripcion" class="col-md-3 col-form-label text-md-end fw-semibold">
                                Descripción
                            </label>
                            <div class="col-md-7">
                                <textarea id="descripcion"
                                          name="descripcion"
                                          class="form-control @error('descripcion') is-invalid @enderror"
                                          rows="4"
                                          placeholder="Descripción detallada del criterio (opcional)...">{{ old('descripcion', $criterio->descripcion) }}</textarea>
                                @error('descripcion')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-7 offset-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Actualizar Criterio
                                </button>
                                <a href="{{ route('materiacriterio.index') }}"
                                   class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Información adicional --}}
            <div class="card mt-3 border-info">
                <div class="card-body">
                    <div class="d-flex">
                        <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                        <div>
                            <h6 class="mb-1">Información del criterio actual</h6>
                            <p class="mb-0 small text-muted">
                                <strong>ID:</strong> {{ $criterio->id }} |
                                <strong>Período actual:</strong> {{ $criterio->periodoBimestre->periodo->nombre }} |
                                <strong>Bimestre actual:</strong> {{ $criterio->periodoBimestre->sigla }} |
                                <strong>Grado actual:</strong> {{ $criterio->grado->nombreCompleto ?? 'N/A' }} |
                                <strong>Materia:</strong> {{ $criterio->materia->nombre }} |
                                <strong>Competencia:</strong> {{ $criterio->materiaCompetencia->nombre }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodoSelect = document.getElementById('periodo_id');
    const bimestreSelect = document.getElementById('periodo_bimestre_id');

    // Cargar bimestres cuando cambia el período
    periodoSelect.addEventListener('change', function() {
        const periodoId = this.value;

        if (periodoId) {
            // Deshabilitar y mostrar loading
            bimestreSelect.disabled = true;
            bimestreSelect.innerHTML = '<option value="">Cargando bimestres...</option>';

            // Cargar bimestres vía AJAX
            fetch(`/materiacriterio/bimestres/${periodoId}`)
                .then(response => response.json())
                .then(data => {
                    bimestreSelect.innerHTML = '<option value="">Seleccione un bimestre</option>';
                    if (data.length === 0) {
                        bimestreSelect.innerHTML += '<option value="" disabled>No hay bimestres disponibles</option>';
                    } else {
                        data.forEach(bimestre => {
                            const option = document.createElement('option');
                            option.value = bimestre.id;
                            option.textContent = `${bimestre.sigla} - Bimestre ${bimestre.bimestre} (${formatDate(bimestre.fecha_inicio)} - ${formatDate(bimestre.fecha_fin)})`;
                            bimestreSelect.appendChild(option);
                        });
                    }
                    bimestreSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    bimestreSelect.innerHTML = '<option value="">Error al cargar bimestres</option>';
                    bimestreSelect.disabled = false;
                });
        } else {
            bimestreSelect.disabled = true;
            bimestreSelect.innerHTML = '<option value="">Primero seleccione un período</option>';
        }
    });

    // Función para formatear fecha
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
    }
});
</script>
@endsection
