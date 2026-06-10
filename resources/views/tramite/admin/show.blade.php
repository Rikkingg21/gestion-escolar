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
                                <!-- Columna del icono fija -->
                                <div class="flex-shrink-0" style="width: 40px;">
                                    <div class="text-center">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 20px;"></i>
                                    </div>
                                    @if(!$loop->last)
                                    <div class="timeline-line mx-auto" style="width: 2px; height: 30px; background: #dee2e6;"></div>
                                    @endif
                                </div>

                                <!-- Contenido principal -->
                                <div class="flex-grow-1 ps-2">
                                    <!-- Cabecera: Estado y Fecha -->
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $registro->estadoTramite->color ?? '#6c757d' }}; color: white;">
                                            <i class="bi bi-tag me-1"></i> {{ $registro->estadoTramite->nombre ?? 'N/A' }}
                                        </span>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> {{ $registro->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>

                                    <!-- Usuario -->
                                    <div class="mb-1">
                                        <small class="text-muted">
                                            <i class="bi bi-person-circle me-1"></i> {{ $registro->user->nombre ?? 'Sistema' }}
                                        </small>
                                    </div>

                                    <!-- Observación (si existe) -->
                                    @if($registro->observacion)
                                    <div class="mt-2">
                                        <div class="alert alert-light mb-0 py-2 px-3 rounded-3" style="background-color: #f8f9fa; border-left: 4px solid #0dcaf0;">
                                            <i class="bi bi-chat-dots text-info me-1"> Observaciones: </i>
                                            <span class="small text-dark" style="white-space: pre-line; word-break: break-word;">
                                                {{ $registro->observacion }}
                                            </span>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox display-4 mb-3 d-block text-muted opacity-50"></i>
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
                            <div class="d-flex">
                                <!-- Columna del icono fija -->
                                <div class="flex-shrink-0" style="width: 40px;">
                                    <div class="text-center">
                                        <i class="bi bi-credit-card text-primary" style="font-size: 20px;"></i>
                                    </div>
                                    @if(!$loop->last)
                                    <div class="timeline-line mx-auto" style="width: 2px; height: 30px; background: #dee2e6;"></div>
                                    @endif
                                </div>

                                <!-- Contenido principal -->
                                <div class="flex-grow-1 ps-2">
                                    <!-- Cabecera: Estado y Fecha -->
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $registroPago->estadoPago->color ?? '#6c757d' }}; color: white;">
                                            <i class="bi bi-circle me-1" style="font-size: 8px;"></i> {{ $registroPago->estadoPago->nombre ?? 'N/A' }}
                                        </span>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> {{ $registroPago->fecha_registro ? \Carbon\Carbon::parse($registroPago->fecha_registro)->format('d/m/Y H:i') : 'N/A' }}
                                        </small>
                                    </div>

                                    <!-- Monto y Usuario -->
                                    <div class="d-flex justify-content-between align-items-center mb-2">
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
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalVerComprobante"
                                                data-comprobante-id="{{ $registroPago->pagoComprobante->id }}"
                                                data-comprobante-url="{{ route('tramiteadmin.ver-comprobante', $registroPago->pagoComprobante->id) }}"
                                                data-comprobante-nombre="Comprobante_{{ $tramite->codigo_tramite }}_{{ $registroPago->id }}"
                                                title="Ver comprobante">
                                            <i class="bi bi-eye me-1"></i> Ver
                                        </button>
                                        @else
                                        <span class="text-muted small">Sin comprobante</span>
                                        @endif
                                    </div>

                                    <!-- Observación (si existe) -->
                                    @if($registroPago->observacion)
                                    <div class="mt-2">
                                        <div class="alert alert-light mb-0 py-2 px-3 rounded-3" style="background-color: #f8f9fa; border-left: 4px solid #0dcaf0;">
                                            <i class="bi bi-chat-dots text-info me-1"> Observaciones: </i>
                                            <span class="small text-dark" style="white-space: pre-line; word-break: break-word;">
                                                {{ $registroPago->observacion }}
                                            </span>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox display-4 mb-3 d-block text-muted opacity-50"></i>
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

<!-- MODAL: Ver Comprobante -->
<div class="modal fade" id="modalVerComprobante" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-file-text me-2"></i> Ver Comprobante de Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="comprobante-contenedor" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando comprobante...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cerrar
                </button>
                <a href="#" id="btnAbrirNuevaPestana" target="_blank" class="btn btn-info text-white">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Abrir en nueva pestaña
                </a>
                <a href="#" id="btnDescargar" class="btn btn-success">
                    <i class="bi bi-download me-1"></i> Descargar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables para almacenar datos del comprobante actual
    let currentComprobanteUrl = '';
    let currentComprobanteNombre = '';

    // Manejar apertura del modal de comprobante
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalVerComprobante');

        modal.addEventListener('show.bs.modal', function(event) {
            // Botón que abrió el modal
            const button = event.relatedTarget;

            // Obtener datos del comprobante
            currentComprobanteUrl = button.getAttribute('data-comprobante-url');
            currentComprobanteNombre = button.getAttribute('data-comprobante-nombre');

            // Actualizar enlaces del footer
            document.getElementById('btnAbrirNuevaPestana').href = currentComprobanteUrl;
            document.getElementById('btnDescargar').href = currentComprobanteUrl;
            document.getElementById('btnDescargar').setAttribute('download', currentComprobanteNombre);

            // Cargar el comprobante en el modal
            cargarComprobante(currentComprobanteUrl);
        });

        // Limpiar al cerrar
        modal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('comprobante-contenedor').innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando comprobante...</p>
            `;
        });
    });

    function cargarComprobante(url) {
        const contenedor = document.getElementById('comprobante-contenedor');

        // Mostrar loading
        contenedor.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando comprobante...</p>
        `;

        // Determinar el tipo de archivo por la URL o hacer una petición HEAD
        fetch(url, { method: 'HEAD' })
            .then(response => {
                const contentType = response.headers.get('Content-Type');

                if (contentType && contentType.includes('pdf')) {
                    // Mostrar PDF
                    contenedor.innerHTML = `
                        <iframe src="${url}" style="width: 100%; height: 70vh; border: none;" class="rounded"></iframe>
                    `;
                } else if (contentType && contentType.includes('image')) {
                    // Mostrar imagen
                    contenedor.innerHTML = `
                        <img src="${url}" alt="Comprobante" class="img-fluid rounded shadow-sm" style="max-height: 70vh;">
                    `;
                } else {
                    // Mostrar mensaje y enlace de descarga
                    contenedor.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No se puede previsualizar este tipo de archivo.
                        </div>
                        <a href="${url}" class="btn btn-primary" download="${currentComprobanteNombre}">
                            <i class="bi bi-download me-1"></i> Descargar archivo
                        </a>
                    `;
                }
            })
            .catch(error => {
                contenedor.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        Error al cargar el comprobante. Por favor, intente de nuevo.
                    </div>
                    <a href="${url}" class="btn btn-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Abrir directamente
                    </a>
                `;
                console.error('Error:', error);
            });
    }
</script>
@endsection
