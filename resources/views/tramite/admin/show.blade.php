@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-eye me-2"></i>Detalle del Trámite
                </h4>
                <span class="badge bg-light text-dark fs-6">
                    {{ $tramite->codigo_tramite }}
                </span>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <!-- Columna izquierda -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><i class="fas fa-info-circle me-1"></i> Información General</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 35%;">Código</th>
                                    <td>{{ $tramite->codigo_tramite }}</td>
                                </tr>
                                <tr>
                                    <th>Tipo de Trámite</th>
                                    <td>{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Solicitante</th>
                                    <td>{{ $tramite->user->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Relación</th>
                                    <td>{{ $tramite->relacion ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Observaciones</th>
                                    <td>{{ $tramite->observaciones ?? 'Ninguna' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><i class="fas fa-chart-line me-1"></i> Estado del Trámite</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 35%;">Estado Actual</th>
                                    <td>
                                        <span class="badge" style="background-color: {{ $tramite->estadoTramite->color ?? '#6c757d' }}">
                                            {{ $tramite->estadoTramite->nombre ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado de Pago</th>
                                    <td>
                                        <span class="badge" style="background-color: {{ $tramite->estadoPago->color ?? '#6c757d' }}">
                                            {{ $tramite->estadoPago->nombre ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Monto Pagado</th>
                                    <td>S/ {{ number_format($tramite->monto_pagado ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha Solicitud</th>
                                    <td>{{ $tramite->fecha_solicitud->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha Resolución</th>
                                    <td>{{ $tramite->fecha_resolucion ? $tramite->fecha_resolucion->format('d/m/Y') : 'Pendiente' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="mt-3 text-end">
                <a href="{{ route('tramiteadmin.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalActualizarEstado">
                    <i class="fas fa-sync-alt me-1"></i> Actualizar Estado
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Actualizar Estado -->
<div class="modal fade" id="modalActualizarEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tramiteadmin.tramites.update.estado', $tramite->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar Estado del Trámite
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Estado del Trámite</label>
                        <select name="estado_tramite_id" class="form-select" required>
                            @foreach($estadosTramite as $estado)
                                <option value="{{ $estado->id }}" {{ $tramite->estado_tramite_id == $estado->id ? 'selected' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado de Pago</label>
                        <select name="estado_pago_id" class="form-select" required>
                            @foreach($estadosPago as $estado)
                                <option value="{{ $estado->id }}" {{ $tramite->estado_pago_id == $estado->id ? 'selected' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto Pagado (opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" name="monto_pagado" class="form-control" value="{{ $tramite->monto_pagado }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Resolución (opcional)</label>
                        <input type="date" name="fecha_resolucion" class="form-control" value="{{ $tramite->fecha_resolucion ? $tramite->fecha_resolucion->format('Y-m-d') : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones (opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="3">{{ $tramite->observaciones }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
