@extends('layouts.app')
@section('title', 'Revertir Estado de Notas')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Revertir Estado de Notas
                    </h5>
                </div>
                <div class="card-body">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Advertencia:</strong> Está a punto de revertir el estado de las notas. Esta acción requiere autenticación con la sesión principal.
            </div>

            <!-- Información de la sesión principal -->
            @if($sessionMainUser)
            <div class="alert alert-info">
                <i class="bi bi-shield-lock"></i>
                <strong>Sesión Principal Activa:</strong> {{ $sessionMainUser->nombre_usuario }}
            </div>
            @else
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
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

            @if($sessionMainUser)
            <form action="{{ route('nota.revertir', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id, 'periodo_bimestre_id' => $periodo_bimestre_id]) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="password"><strong>Contraseña de la Sesión Principal *</strong></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" required placeholder="Ingrese la contraseña de {{ $sessionMainUser->nombre_usuario }}">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Debe ingresar la contraseña del usuario de la sesión principal para proceder con la reversión.
                    </small>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-arrow-counterclockwise"></i> Confirmar Reversión
                    </button>
                    <a href="{{ route('nota.index', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id, 'periodo_bimestre_id' => $periodo_bimestre_id]) }}"
                       class="btn btn-secondary">
                        <i class="bi bi-x"></i> Cancelar
                    </a>
                </div>
            </form>
            @else
            <div class="alert alert-danger">
                No se puede proceder con la reversión sin una sesión principal activa.
            </div>
            <a href="{{ route('nota.index', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id, 'periodo_bimestre_id' => $periodo_bimestre_id]) }}"
               class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
