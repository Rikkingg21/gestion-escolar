@extends('layouts.app')
@section('title', 'Cuota de Pensión')
@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Cuota de Pensión</h4>
        <a href="{{ route('pensiones-admin.cuotas') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a cuotas
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $aprobadoId = $estadosPago->first(fn($e) => str_contains(strtolower($e->nombre), 'aprobado'))?->id;
        $rechazadoId = $estadosPago->first(fn($e) => str_contains(strtolower($e->nombre), 'rechazado'))?->id;
        $estado = $pension->estado_efectivo;
    @endphp

    {{-- Resumen --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-person-video3 me-2"></i> Estudiante</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-muted small">Nombre completo</div>
                            <strong>{{ $pension->matricula->estudiante->user->nombre_completo }}</strong>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">DNI</div>
                            <strong>{{ $pension->matricula->estudiante->user->dni }}</strong>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Grado</div>
                            <strong>{{ $pension->matricula->grado->nombre_completo }}</strong>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-muted small">Periodo</div>
                            <strong>{{ $pension->matricula->periodo->nombre }} ({{ $pension->matricula->periodo->anio }})</strong>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Estado</div>
                            <span class="badge text-bg-{{ $pension->estado_efectivo_color }}">
                                {{ $pension->estado_efectivo_label }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Vencimiento</div>
                            <strong>{{ $pension->fecha_vencimiento_formateada }}</strong>
                            @if($pension->atrasada)
                                <span class="badge text-bg-danger ms-1">Atrasada</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Montos</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ $pension->concepto }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Monto:</span>
                        <strong>{{ $pension->monto_formateado }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Pagado:</span>
                        <strong class="text-success">{{ $pension->monto_pagado_formateado }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="text-muted">Saldo:</span>
                        <strong class="{{ $pension->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $pension->saldo_pendiente_formateado }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Historial de pagos --}}
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-white py-2">
            <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i> Historial de pagos</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Método</th>
                            <th>N° Operación</th>
                            <th>Registrado por</th>
                            <th>Observación</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pension->pagoRegistros as $registro)
                            @php
                                $nombreEstado = strtolower($registro->estadoPago->nombre ?? '');
                                $esPendiente = str_contains($nombreEstado, 'revisión') || str_contains($nombreEstado, 'pendiente');
                                $tienePago = $registro->pago !== null;
                            @endphp
                            <tr>
                                <td>{{ $registro->fecha_registro_formateada }}</td>
                                <td>{{ $registro->monto_formateado }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $registro->estadoPago->color ?? '#6c757d' }}; color: white;">
                                        {{ $registro->estadoPago->nombre ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $registro->pago?->metodoPago?->nombre ?? 'N/A' }}</td>
                                <td>
                                    {{ $registro->pago?->numero_operacion ?? 'N/A' }}
                                    @if($registro->pago && $registro->pago->comprobante_path)
                                        <a href="{{ route('pensiones-admin.ver-comprobante', $registro->pago->id) }}" target="_blank"
                                           class="btn btn-sm btn-outline-secondary ms-1">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $registro->user->nombre ?? 'Sistema' }}</td>
                                <td class="small">{{ $registro->observacion ?? '' }}</td>
                                <td class="text-end">
                                    @if($esPendiente && $tienePago)
                                        <div class="d-flex gap-1 justify-content-end">
                                            @if($aprobadoId)
                                                <form method="POST" action="{{ route('pensiones-admin.update-estado-pago', $pension->id) }}">
                                                    @csrf
                                                    <input type="hidden" name="pago_registro_id" value="{{ $registro->id }}">
                                                    <input type="hidden" name="estado_pago_id" value="{{ $aprobadoId }}">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-lg"></i> Aprobar
                                                    </button>
                                                </form>
                                            @endif
                                            @if($rechazadoId)
                                                <form method="POST" action="{{ route('pensiones-admin.update-estado-pago', $pension->id) }}">
                                                    @csrf
                                                    <input type="hidden" name="pago_registro_id" value="{{ $registro->id }}">
                                                    <input type="hidden" name="estado_pago_id" value="{{ $rechazadoId }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('¿Rechazar este pago?')">
                                                        <i class="bi bi-x-lg"></i> Rechazar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox display-4 d-block mb-3 text-muted opacity-50"></i>
                                    No hay registros de pago para esta cuota.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(! $pension->esPagada())
        <div class="text-center mt-4">
            <a href="{{ route('pensiones-admin.registrar-pago') }}" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle me-1"></i> Registrar pago para esta cuota
            </a>
        </div>
    @endif
</div>

@endsection