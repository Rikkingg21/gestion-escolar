@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-building me-2"></i> Configuración del Colegio
        </h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('colegioconfig.update', $colegio->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre del Colegio *</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                               id="nombre" name="nombre" value="{{ old('nombre', $colegio->nombre) }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="director_actual" class="form-label">Director Actual</label>
                        <input type="text" class="form-control @error('director_actual') is-invalid @enderror"
                               id="director_actual" name="director_actual"
                               value="{{ old('director_actual', $colegio->director_actual) }}">
                        @error('director_actual')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="direccion" class="form-label">Dirección *</label>
                        <textarea class="form-control @error('direccion') is-invalid @enderror"
                                  id="direccion" name="direccion" rows="2" required>{{ old('direccion', $colegio->direccion) }}</textarea>
                        @error('direccion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                               id="telefono" name="telefono"
                               value="{{ old('telefono', $colegio->telefono) }}">
                        @error('telefono')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email"
                               value="{{ old('email', $colegio->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="ruc" class="form-label">RUC</label>
                        <input type="text" class="form-control @error('ruc') is-invalid @enderror"
                               id="ruc" name="ruc" maxlength="11"
                               value="{{ old('ruc', $colegio->ruc) }}">
                        @error('ruc')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="logo" class="form-label">Logo del Colegio</label>
                        <input type="file" class="form-control @error('logo') is-invalid @enderror"
                               id="logo" name="logo" accept="image/*">
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if($colegio->logo_path)
                            <div class="mt-3">
                                <p>Logo actual:</p>
                                <img src="{{ asset($colegio->logo_path) }}" alt="Logo del colegio"
                                     style="max-height: 100px; max-width: 200px;" class="img-thumbnail">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox"
                                           id="eliminar_logo" name="eliminar_logo">
                                    <label class="form-check-label" for="eliminar_logo">
                                        Eliminar logo actual
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i> Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
