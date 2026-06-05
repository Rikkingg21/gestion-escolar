@extends('layouts.app')
@section('title', 'Mis trámites')
@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">
                <i class="fas fa-eye me-2"></i>Detalle del Trámite
            </h4>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width: 35%;">Código</th><td colspan="3">{{ $tramite->codigo_tramite }}</td></tr>
                        <tr><th>Tipo de Trámite</th><td colspan="3">{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</td></tr>
                        <tr><th>Fecha Solicitud</th><td colspan="3">{{ $tramite->fecha_solicitud->format('d/m/Y') }}</td></tr>
                        <tr><th>Relación</th><td colspan="3">{{ $tramite->relacion ?? 'N/A' }}</td></tr>
                        <tr><th>Observaciones</th><td colspan="3">{{ $tramite->observaciones ?? 'Ninguna' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th style="width: 35%;">Estado del Trámite</th>
                            <td colspan="3">
                                <span class="badge" style="background-color: {{ $tramite->estadoTramite->color ?? '#6c757d' }}">
                                    {{ $tramite->estadoTramite->nombre ?? 'N/A' }}
                                </span>
                             </td>
                        </tr>
                        <tr><th>Estado del Pago</th>
                            <td colspan="3">
                                <span class="badge" style="background-color: {{ $tramite->estadoPago->color ?? '#6c757d' }}">
                                    {{ $tramite->estadoPago->nombre ?? 'N/A' }}
                                </span>
                             </td>
                        </tr>
                        <tr><th>Monto a Pagar</th>
                            <td colspan="3">
                                <strong class="text-primary">
                                    S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}
                                </strong>
                             </td>
                        </tr>
                        <tr><th>Monto Pagado</th>
                            <td colspan="3">S/ {{ number_format($tramite->monto_pagado ?? 0, 2) }}</td>
                        </tr>
                        <tr><th>Fecha Resolución</th>
                            <td colspan="3">{{ $tramite->fecha_resolucion ? $tramite->fecha_resolucion->format('d/m/Y') : 'Pendiente' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Botón de Pago -->
            @if($tramite->estado_pago_id == 1 && $tramite->tipoTramite->costo > 0)
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Pendiente de Pago:</strong> Debe pagar S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }} para continuar con su trámite.
                            </div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPago">
                                <i class="fas fa-credit-card me-1"></i> Realizar Pago
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Mensaje de pago completado -->
            @if($tramite->estado_pago_id == 2)
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Pago Completado:</strong> Su pago ha sido registrado correctamente. Su trámite está en proceso.
                    </div>
                </div>
            </div>
            @endif

            <div class="mt-4 text-end">
                <a href="{{ route('tramite.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <a href="{{ route('tramite.seguimiento', $tramite->id) }}" class="btn btn-primary">
                    <i class="fas fa-chart-line me-1"></i> Ver Seguimiento
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pago -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tramite.pago', $tramite->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-credit-card me-1"></i> Realizar Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Trámite</label>
                        <input type="text" class="form-control" value="{{ $tramite->codigo_tramite }} - {{ $tramite->tipoTramite->nombre }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto a Pagar</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="text" class="form-control" value="{{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="efectivo">Efectivo (Ventanilla)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Número de Operación / Voucher</label>
                        <input type="text" name="numero_operacion" class="form-control" placeholder="Ingrese el número de operación o código del voucher" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
