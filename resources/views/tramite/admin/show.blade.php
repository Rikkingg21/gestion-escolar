@extends('layouts.app')
@section('title', 'Administrar Trámite - ' . $tramite->codigo_tramite)
@section('content')

<div class="container-fluid">

    <!-- Botón para volver -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('tramiteadmin.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i> Volver al listado de trámites
            </a>
        </div>
    </div>

    <!-- SECCIÓN 1: Resumen rápido - Tarjetas de estado (FULL WIDTH) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-4">
                    <div class="row text-center">
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mb-2 d-inline-flex">
                                    <i class="bi bi-calendar3 fa-2x text-primary"></i>
                                </div>
                                <h6 class="text-muted mb-1">Fecha Solicitud</h6>
                                <h5 class="mb-0 fw-bold">{{ $tramite->fecha_solicitud ? \Carbon\Carbon::parse($tramite->fecha_solicitud)->format('d/m/Y') : 'N/A' }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-2 d-inline-flex">
                                    <i class="bi bi-check-circle fa-2x text-success"></i>
                                </div>
                                <h6 class="text-muted mb-1">Fecha Resolución</h6>
                                <h5 class="mb-0 fw-bold">{{ $tramite->fecha_resolucion ? \Carbon\Carbon::parse($tramite->fecha_resolucion)->format('d/m/Y') : 'Pendiente' }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-2 d-inline-flex">
                                    <i class="bi bi-cash-stack fa-2x text-warning"></i>
                                </div>
                                <h6 class="text-muted mb-1">{{ $requierePago ? 'Costo Total' : 'Trámite' }}</h6>
                                @if($requierePago)
                                    <h5 class="mb-0 fw-bold">S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}</h5>
                                @else
                                    <h5 class="mb-0 fw-bold">Sin costo</h5>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($requierePago)
                        @php $saldoPendiente = ($tramite->tipoTramite->costo ?? 0) - $tramite->monto_pagado; @endphp
                        @if($saldoPendiente > 0)
                        <div class="alert alert-warning mb-0 mt-4 text-center">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Saldo pendiente:</strong> S/ {{ number_format($saldoPendiente, 2) }}
                        </div>
                        @elseif($saldoPendiente <= 0 && $tramite->monto_pagado > 0)
                        <div class="alert alert-success mb-0 mt-4 text-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Pago completado</strong> - Total pagado: S/ {{ number_format($tramite->monto_pagado, 2) }}
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: Información + Historiales (layout dinámico según requiere_pago) -->
    <div class="row">
        <!-- Columna Izquierda: Datos del Solicitante + Estudiante + Observación -->
        <div class="col-md-4 mb-4">
            <!-- Card Solicitante -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2"></i> Datos del Solicitante
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nombre:</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $tramite->user->nombre ?? 'N/A' }} {{ $tramite->user->apellido_paterno ?? '' }} {{ $tramite->user->apellido_materno ?? '' }}</dd>

                        <dt class="col-sm-4 text-muted">DNI:</dt>
                        <dd class="col-sm-8"><i class="bi bi-card-text me-1 text-muted"></i> {{ $tramite->user->dni ?? 'N/A' }}</dd>

                        <dt class="col-sm-4 text-muted">Email:</dt>
                        <dd class="col-sm-8"><i class="bi bi-envelope me-1 text-muted"></i> {{ $tramite->user->email ?? 'N/A' }}</dd>

                        <dt class="col-sm-4 text-muted">Teléfono:</dt>
                        <dd class="col-sm-8"><i class="bi bi-telephone me-1 text-muted"></i> {{ $tramite->user->telefono ?? 'N/A' }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Card Estudiante -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-mortarboard me-2"></i> Datos del Estudiante
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nombre:</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $tramite->estudiante->user->nombre ?? 'N/A' }} {{ $tramite->estudiante->user->apellido_paterno ?? '' }}</dd>

                        <dt class="col-sm-4 text-muted">DNI:</dt>
                        <dd class="col-sm-8"><i class="bi bi-card-text me-1 text-muted"></i> {{ $tramite->estudiante->user->dni ?? 'N/A' }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Card Observación -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-chat-dots me-2"></i> Observación General
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted">
                        Tipo tramite: {{ $tramite->tipoTramite->nombre ?? 'Sin nombre' }}
                    </p>
                    <p class="mb-0 text-muted">
                        <i class="bi bi-file-text me-1"></i> {{ $tramite->observaciones ?? 'Sin observaciones registradas' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Columna Central: Historial del Trámite -->
        <div class="{{ $requierePago ? 'col-md-4' : 'col-md-8' }} mb-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-warning text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-arrow-repeat me-2"></i>
                            <span class="fw-semibold">Historial del Trámite</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCambiarEstadoTramite">
                            <i class="bi bi-pencil me-1"></i> Cambiar Estado
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($tramite->tramiteRegistros as $index => $registro)
                        <div class="timeline-item p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 text-center" style="width: 50px;">
                                    <div class="rounded-circle bg-light p-1 d-inline-block">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 18px;"></i>
                                    </div>
                                    @if(!$loop->last)
                                    <div class="timeline-line mx-auto" style="width: 2px; height: 30px; background: #e0e0e0;"></div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $registro->estadoTramite->color ?? '#6c757d' }}; color: white;">
                                            <i class="bi bi-tag me-1"></i> {{ $registro->estadoTramite->nombre ?? 'N/A' }}
                                        </span>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> {{ $registro->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <small class="text-muted">
                                            <i class="bi bi-person-circle me-1"></i> {{ $registro->user->nombre ?? 'Sistema' }}
                                        </small>
                                        @if($registro->observacion)
                                        <small class="text-info">
                                            <i class="bi bi-chat-dots me-1"></i> {{ \Illuminate\Support\Str::limit($registro->observacion, 50) }}
                                        </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <p class="mb-0">No hay registros de cambios</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer bg-light py-2 text-center">
                    <small class="text-muted">
                        <i class="bi bi-list me-1"></i> Total: {{ $tramite->tramiteRegistros->count() }} registros
                    </small>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Historial de Pagos (SOLO SI requiere_pago = 1) -->
        @if($requierePago)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-credit-card me-2"></i>
                            <span class="fw-semibold">Historial de Pagos</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCambiarEstadoPago">
                            <i class="bi bi-pencil me-1"></i> Cambiar Estado
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($tramite->tramitePagoRegistros as $index => $registroPago)
                        <div class="payment-item p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $registroPago->estadoPago->color ?? '#6c757d' }}; color: white;">
                                    <i class="bi bi-circle me-1" style="font-size: 8px;"></i> {{ $registroPago->estadoPago->nombre ?? 'N/A' }}
                                </span>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i> {{ $registroPago->fecha_registro ? \Carbon\Carbon::parse($registroPago->fecha_registro)->format('d/m/Y H:i') : 'N/A' }}
                                </small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-3">
                                    <div>
                                        <i class="bi bi-cash-stack text-success me-1"></i>
                                        <span class="fw-bold">S/ {{ number_format($registroPago->monto, 2) }}</span>
                                    </div>
                                    <div>
                                        <i class="bi bi-person-circle text-muted me-1"></i>
                                        <small>{{ $registroPago->user->nombre ?? 'Sistema' }}</small>
                                    </div>
                                </div>
                                @if($registroPago->pagoComprobante)
                                <a href="{{ route('tramiteadmin.ver-comprobante', $registroPago->pagoComprobante->id) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill" title="Ver comprobante">
                                    <i class="bi bi-file-text me-1"></i> Ver
                                </a>
                                @else
                                <span class="text-muted small">Sin comprobante</span>
                                @endif
                            </div>

                            @if($registroPago->observacion)
                            <div class="mt-2 small text-muted bg-light p-2 rounded">
                                <i class="bi bi-quote me-1 opacity-50"></i> {{ $registroPago->observacion }}
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <p class="mb-0">No hay registros de pagos</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer bg-light py-2 text-center">
                    <small class="text-muted">
                        <i class="bi bi-list me-1"></i> Total: {{ $tramite->tramitePagoRegistros->count() }} registros
                    </small>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- MODAL: Cambiar Estado del Trámite -->
<div class="modal fade" id="modalCambiarEstadoTramite" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-arrow-repeat me-2"></i> Cambiar Estado del Trámite
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('tramiteadmin.update-estado-tramite', $tramite->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado Actual</label>
                        @php $estadoActual = $tramite->tramiteRegistros->first(); @endphp
                        <div>
                            <span class="badge fs-6 px-3 py-2" style="background-color: {{ $estadoActual->estadoTramite->color ?? '#6c757d' }}">
                                <i class="bi bi-tag me-1"></i> {{ $estadoActual->estadoTramite->nombre ?? 'Sin estado' }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo Estado <span class="text-danger">*</span></label>
                        <select name="estado_tramite_id" class="form-select" required>
                            <option value="">Seleccione un estado...</option>
                            @foreach($estadosTramite as $estado)
                                <option value="{{ $estado->id }}" {{ ($estadoActual && $estadoActual->estado_tramite_id == $estado->id) ? 'disabled' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Observación</label>
                        <textarea name="observacion" class="form-control" rows="3" placeholder="Ej: Documentación revisada, pendiente de firma..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i> Actualizar Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Cambiar Estado del Pago (SOLO SI requiere_pago = 1) -->
@if($requierePago)
<div class="modal fade" id="modalCambiarEstadoPago" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">
                    <i class="bi bi-credit-card me-2"></i> Cambiar Estado del Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('tramiteadmin.update-estado-pago', $tramite->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado Actual</label>
                        @php
                            $estadoPagoActual = $tramite->tramitePagoRegistros->first();
                            $ultimoComprobante = $tramite->tramitePagoRegistros->first()?->pagoComprobante;
                        @endphp
                        <div>
                            <span class="badge fs-6 px-3 py-2" style="background-color: {{ $estadoPagoActual->estadoPago->color ?? '#6c757d' }}">
                                <i class="bi bi-circle me-1"></i> {{ $estadoPagoActual->estadoPago->nombre ?? 'Sin registro' }}
                            </span>
                        </div>
                    </div>

                    @if($ultimoComprobante)
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-file-text me-2"></i>
                        <strong>Último comprobante:</strong>
                        <a href="{{ route('tramiteadmin.ver-comprobante', $ultimoComprobante->id) }}" target="_blank" class="alert-link">
                            Ver comprobante <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                        <hr class="my-2">
                        <div class="small">
                            <div><strong><i class="bi bi-cash-stack me-1"></i> Monto:</strong> S/ {{ number_format($estadoPagoActual->monto ?? 0, 2) }}</div>
                            <div><strong><i class="bi bi-upc-scan me-1"></i> N° Operación:</strong> {{ $ultimoComprobante->numero_operacion ?? 'N/A' }}</div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo Estado <span class="text-danger">*</span></label>
                        <select name="estado_pago_id" class="form-select" required>
                            <option value="">Seleccione un estado...</option>
                            @foreach($estadosPago as $estado)
                                <option value="{{ $estado->id }}">
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Observación</label>
                        <textarea name="observacion" class="form-control" rows="3" placeholder="Ej: Comprobante verificado, pago aprobado..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-save me-1"></i> Actualizar Estado de Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
