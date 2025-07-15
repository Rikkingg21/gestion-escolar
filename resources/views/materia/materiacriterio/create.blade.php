@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Nuevo Criterio de Evaluación</h4>
                    <p class="mb-0">Materia: {{ $materia->nombre }}</p>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('materiacriterio.store') }}">
                        @csrf

                        <input type="hidden" name="materia_id" value="{{ $materia->id }}">

                        <div class="row mb-3">
                            <label for="materia_competencia_id" class="col-md-4 col-form-label text-md-end">
                                Competencia *
                            </label>
                            <div class="col-md-6">
                                <select id="materia_competencia_id"
                                        name="materia_competencia_id"
                                        class="form-select @error('materia_competencia_id') is-invalid @enderror"
                                        required>
                                    <option value="">Seleccione una competencia</option>
                                    @foreach($competencias as $competencia)
                                        <option value="{{ $competencia->id }}"
                                            {{ old('materia_competencia_id') == $competencia->id ? 'selected' : '' }}>
                                            {{ $competencia->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('materia_competencia_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="grados" class="col-md-4 col-form-label text-md-end">
                                Grados *
                            </label>
                            <div class="col-md-6">
                                <select id="grados"
                                        name="grados[]"
                                        class="form-select @error('grados') is-invalid @enderror"
                                        multiple="multiple"
                                        required>
                                    @foreach($grados as $grado)
                                        <option value="{{ $grado->id }}"
                                            {{ is_array(old('grados')) && in_array($grado->id, old('grados')) ? 'selected' : '' }}>
                                            {{ $grado->nombreCompleto }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('grados')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="text-muted">Mantén presionada la tecla Ctrl (Windows) o Command (Mac) para seleccionar múltiples opciones</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="nombre" class="col-md-4 col-form-label text-md-end">
                                Nombre del Criterio *
                            </label>
                            <div class="col-md-6">
                                <input id="nombre"
                                       type="text"
                                       class="form-control @error('nombre') is-invalid @enderror"
                                       name="nombre"
                                       value="{{ old('nombre') }}"
                                       required
                                       autocomplete="off">
                                @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="anio" class="col-md-4 col-form-label text-md-end">
                                Año *
                            </label>
                            <div class="col-md-6">
                                <select id="anio"
                                        name="anio"
                                        class="form-select @error('anio') is-invalid @enderror"
                                        required>
                                    @foreach($anios as $anio)
                                        <option value="{{ $anio }}"
                                            {{ old('anio', date('Y')) == $anio ? 'selected' : '' }}>
                                            {{ $anio }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('anio')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="descripcion" class="col-md-4 col-form-label text-md-end">
                                Descripción
                            </label>
                            <div class="col-md-6">
                                <textarea id="descripcion"
                                          class="form-control @error('descripcion') is-invalid @enderror"
                                          name="descripcion"
                                          rows="3">{{ old('descripcion') }}</textarea>
                                @error('descripcion')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                                <a href="{{ route('materiacriterio.index', $materia->id) }}"
                                   class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
