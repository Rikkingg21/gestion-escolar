@extends('layouts.app')
@section('title', 'Detalle del Trámite - ' . $tramite->codigo_tramite)
@section('content')

<div class="container-fluid">

    <!-- Botón para volver -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('mis-tramites.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i> Volver a mis trámites
            </a>
        </div>
    </div>

    <!-- SECCIÓN 1: Resumen rápido -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-4">
                    <div class="row text-center">
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mb-2 d-inline-flex">
                                    <i class="bi bi-calendar3 text-primary fs-2"></i>
                                </div>
                                <h6 class="text-muted mb-1">Fecha Solicitud</h6>
                                <h5 class="mb-0 fw-bold">{{ $tramite->fecha_solicitud ? \Carbon\Carbon::parse($tramite->fecha_solicitud)->format('d/m/Y') : 'N/A' }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-2 d-inline-flex">
                                    <i class="bi bi-check-circle text-success fs-2"></i>
                                </div>
                                <h6 class="text-muted mb-1">Fecha Resolución</h6>
                                <h5 class="mb-0 fw-bold">{{ $tramite->fecha_resolucion ? \Carbon\Carbon::parse($tramite->fecha_resolucion)->format('d/m/Y') : 'Pendiente' }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-2 d-inline-flex">
                                    <i class="bi bi-cash-stack text-warning fs-2"></i>
                                </div>
                                <h6 class="text-muted mb-1">{{ $tramite->tipoTramite->requiere_pago ? 'Costo Total' : 'Trámite' }}</h6>
                                @if($tramite->tipoTramite->requiere_pago)
                                    <h5 class="mb-0 fw-bold">S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}</h5>
                                @else
                                    <h5 class="mb-0 fw-bold">Sin costo</h5>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($tramite->tipoTramite->requiere_pago)
                        @php
                            $saldoPendiente = ($tramite->tipoTramite->costo ?? 0) - ($tramite->monto_pagado_total ?? 0);
                        @endphp
                        @if($saldoPendiente > 0)
                        <div class="alert alert-warning mb-0 mt-4 text-center py-2">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Saldo pendiente:</strong> S/ {{ number_format($saldoPendiente, 2) }}
                        </div>
                        @elseif($saldoPendiente <= 0 && ($tramite->monto_pagado_total ?? 0) > 0)
                        <div class="alert alert-success mb-0 mt-4 text-center py-2">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Pago completado</strong> - Total pagado: S/ {{ number_format($tramite->monto_pagado_total ?? 0, 2) }}
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: Información + Historiales -->
    <div class="row">
        <!-- Columna Izquierda: Datos -->
        <div class="col-md-4 mb-4">
            <!-- Card Solicitante -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i> Datos del Solicitante</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nombre:</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $tramite->user->nombre ?? 'N/A' }} {{ $tramite->user->apellido_paterno ?? '' }}</dd>

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
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i> Datos del Estudiante</h6>
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
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i> Observación General</h6>
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
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i> Historial del Trámite</h6>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($tramite->tramiteRegistros as $index => $registro)
                        <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 text-center" style="width: 40px;">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 18px;"></i>
                                    @if(!$loop->last)
                                    <div class="mx-auto" style="width: 2px; height: 20px; background: #dee2e6;"></div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $registro->estadoTramite->color ?? '#6c757d' }}; color: white;">
                                            {{ $registro->estadoTramite->nombre ?? 'N/A' }}
                                        </span>
                                        <small class="text-muted">{{ $registro->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted"><i class="bi bi-person-circle me-1"></i> {{ $registro->user->nombre ?? 'Sistema' }}</small>
                                    </div>
                                    @if($registro->observacion)
                                    <div class="mt-2 small text-muted bg-light p-2 rounded">
                                        <i class="bi bi-chat-dots me-1"></i> {{ $registro->observacion }}
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
                    <small class="text-muted"><i class="bi bi-list me-1"></i> Total: {{ $tramite->tramiteRegistros->count() }} registros</small>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Historial de Pagos (solo si requiere pago) -->
        @if($tramite->tipoTramite->requiere_pago)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i> Historial de Pagos</h6>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($tramite->tramitePagoRegistros as $index => $registroPago)
                        <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 text-center" style="width: 40px;">
                                    <i class="bi bi-credit-card text-primary" style="font-size: 18px;"></i>
                                    @if(!$loop->last)
                                    <div class="mx-auto" style="width: 2px; height: 20px; background: #dee2e6;"></div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $registroPago->estadoPago->color ?? '#6c757d' }}; color: white;">
                                            {{ $registroPago->estadoPago->nombre ?? 'N/A' }}
                                        </span>
                                        <small class="text-muted">{{ $registroPago->fecha_registro ? \Carbon\Carbon::parse($registroPago->fecha_registro)->format('d/m/Y H:i') : $registroPago->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <small class="text-muted"><i class="bi bi-person-circle me-1"></i> {{ $registroPago->user->nombre ?? 'Sistema' }}</small>
                                            <span class="ms-2 badge bg-light text-dark border">S/ {{ number_format($registroPago->monto, 2) }}</span>
                                        </div>
                                        @if($registroPago->pagoComprobante)
                                        <a href="{{ route('mis-tramites.ver-comprobante', $registroPago->pagoComprobante->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver comprobante">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endif
                                    </div>
                                    @if($registroPago->observacion)
                                    <div class="mt-1 small text-muted bg-light p-2 rounded">
                                        <i class="bi bi-chat-dots me-1"></i> {{ $registroPago->observacion }}
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
                    <small class="text-muted"><i class="bi bi-list me-1"></i> Total: {{ $tramite->tramitePagoRegistros->count() }} registros</small>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Botón para subir comprobante -->
    @php
        $costoTotal = $tramite->tipoTramite->costo ?? 0;
        $montoPagado = $tramite->monto_pagado_total ?? 0;
        $saldoPendiente = $costoTotal - $montoPagado;
    @endphp

    @if($tramite->tipoTramite->requiere_pago && $saldoPendiente > 0)
    <div class="row mt-3">
        <div class="col-12 text-center">
            <button class="btn btn-success" onclick="subirComprobante({{ $tramite->id }})">
                <i class="bi bi-cloud-upload me-1"></i> Subir Comprobante de Pago
            </button>
        </div>
    </div>
    @endif
</div>

<!-- Modal para subir comprobante -->
<div class="modal fade" id="modalSubirComprobante" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data" id="formComprobante">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title">Subir Comprobante de Pago</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tramite_id" id="tramite_id_comprobante">

                    <div class="mb-3">
                        <label class="form-label small">Método de Pago</label>
                        <select name="tipo_pago_id" class="form-select" id="tipo_pago_select" required>
                            <option value="">Seleccione...</option>
                            @foreach($tiposPago as $tipo)
                                <option value="{{ $tipo->id }}"
                                    data-categoria="{{ $tipo->categoria }}"
                                    data-entidad="{{ $tipo->entidad_financiera }}"
                                    data-numero-cuenta="{{ $tipo->numero_cuenta }}"
                                    data-cci="{{ $tipo->cci }}"
                                    data-titular="{{ $tipo->titular_cuenta }}">
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="infoCuenta">
                        <div class="alert alert-info py-2 small">
                            <div id="info_entidad"></div>
                            <div id="info_numero_cuenta"></div>
                            <div id="info_cci"></div>
                            <div id="info_titular"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Número de Operación</label>
                        <input type="text" name="numero_operacion" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" id="monto_pago" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Comprobante (imagen o PDF)</label>
                        <input type="file" name="comprobante" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm">Subir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const costoTotal = {{ $tramite->tipoTramite->costo ?? 0 }};
    const montoPagado = {{ $tramite->monto_pagado_total ?? 0 }};
    const saldoPendiente = costoTotal - montoPagado;

    function subirComprobante(id) {
        document.getElementById('tramite_id_comprobante').value = id;
        document.getElementById('formComprobante').action = "/mis-tramites/" + id + "/comprobante";
        document.getElementById('monto_pago').value = saldoPendiente.toFixed(2);
        document.getElementById('monto_pago').max = saldoPendiente;
        new bootstrap.Modal(document.getElementById('modalSubirComprobante')).show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const selectPago = document.getElementById('tipo_pago_select');
        if (selectPago) {
            selectPago.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                const infoDiv = document.getElementById('infoCuenta');
                if (this.value && opt.dataset.categoria !== 'efectivo') {
                    document.getElementById('info_entidad').innerHTML = `<strong>Entidad:</strong> ${opt.dataset.entidad || 'N/A'}`;
                    document.getElementById('info_numero_cuenta').innerHTML = `<strong>N° Cuenta:</strong> ${opt.dataset.numeroCuenta || 'N/A'}`;
                    document.getElementById('info_cci').innerHTML = `<strong>CCI:</strong> ${opt.dataset.cci || 'N/A'}`;
                    document.getElementById('info_titular').innerHTML = `<strong>Titular:</strong> ${opt.dataset.titular || 'N/A'}`;
                    infoDiv.classList.remove('d-none');
                } else {
                    infoDiv.classList.add('d-none');
                }
            });
        }
    });
</script>
@endsection
