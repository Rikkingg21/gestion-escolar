@extends('layouts.app')

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
                        <tr><th>Monto Pagado</th><td colspan="3">S/ {{ number_format($tramite->monto_pagado ?? 0, 2) }}</td></tr>
                        <tr><th>Fecha Resolución</th><td colspan="3">{{ $tramite->fecha_resolucion ? $tramite->fecha_resolucion->format('d/m/Y') : 'Pendiente' }}</td></tr>
                    </table>
                </div>
            </div>

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
@endsection
