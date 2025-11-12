@extends('layouts.app')
@section('title', 'Revertir Estado de Notas')
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-undo"></i> Revertir Estado de Notas
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-warning">
            <h6 class="m-0 font-weight-bold text-dark">
                Confirmación de Reversión
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Advertencia:</strong> Está a punto de revertir el estado de las notas. Esta acción requiere autenticación con la sesión principal.
            </div>

            <!-- Información de la sesión principal -->
            @if(session('sessionmain'))
            <div class="alert alert-info">
                <i class="fas fa-user-shield"></i>
                <strong>Sesión Principal Activa:</strong> {{ session('sessionmain')->nombre_usuario }}
            </div>
            @else
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Error:</strong> No hay sesión principal activa.
            </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>Estado Actual:</strong>
                    @php
                        $estados = ['0' => 'Privado', '1' => 'Publicado', '2' => 'Oficial', '3' => 'Extra Oficial'];
                    @endphp
                    <span class="badge bg-secondary">{{ $estados[$estadoActual] ?? 'Desconocido' }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Nuevo Estado:</strong>
                    <span class="badge bg-info">
                        @if($estadoActual == '3') Oficial
                        @elseif($estadoActual == '2') Publicado
                        @elseif($estadoActual == '1') Privado
                        @else No aplica
                        @endif
                    </span>
                </div>
            </div>

            @if(session('sessionmain'))
            <form action="{{ route('nota.revertir', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id, 'bimestre' => $bimestre]) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="password"><strong>Contraseña de la Sesión Principal *</strong></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" required placeholder="Ingrese la contraseña de {{ session('sessionmain')->nombre_usuario }}">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Debe ingresar la contraseña del usuario de la sesión principal para proceder con la reversión.
                    </small>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-undo"></i> Confirmar Reversión
                    </button>
                    <a href="{{ route('nota.index', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id, 'bimestre' => $bimestre]) }}"
                       class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
            @else
            <div class="alert alert-danger">
                No se puede proceder con la reversión sin una sesión principal activa.
            </div>
            <a href="{{ route('nota.index', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id, 'bimestre' => $bimestre]) }}"
               class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
