@extends('layouts.app')
@section('title', 'Detalle del Trámite - ' . $tramite->codigo_tramite)
@section('content')

<div class="container-fluid">
    <!-- ... sección de botón volver y mensajes (igual) ... -->

    <!-- SECCIÓN 1: Resumen rápido (simplificado) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-4">
                    <div class="row text-center">
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 mb-2">
                                    <i class="bi bi-calendar3 text-primary fs-2"></i>
                                </div>
                                <h6 class="text-muted mb-1">Fecha Solicitud</h6>
                                <h5 class="mb-0 fw-bold">{{ $fechaSolicitud }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-2">
                                    <i class="bi bi-check-circle text-success fs-2"></i>
                                </div>
                                <h6 class="text-muted mb-1">Fecha Resolución</h6>
                                <h5 class="mb-0 fw-bold">{{ $fechaResolucion }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-2">
                                    <i class="bi bi-cash-stack text-warning fs-2"></i>
                                </div>
                                <h6 class="text-muted mb-1">{{ $requierePago ? 'Costo Total' : 'Trámite' }}</h6>
                                @if($requierePago)
                                    <h5 class="mb-0 fw-bold">S/ {{ number_format($costoTotal, 2) }}</h5>
                                @else
                                    <h5 class="mb-0 fw-bold">Sin costo</h5>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($requierePago && $saldoPendiente > 0)
                    <div class="alert alert-warning mb-0 mt-4 text-center py-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Saldo pendiente:</strong> S/ {{ number_format($saldoPendiente, 2) }}
                    </div>
                    @elseif($requierePago && $montoPagado > 0 && $saldoPendiente <= 0)
                    <div class="alert alert-success mb-0 mt-4 text-center py-2">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Pago completado</strong> - Total pagado: S/ {{ number_format($montoPagado, 2) }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: Información + Historiales -->
    <div class="row">
        <!-- Columna Izquierda: Datos del Solicitante y Estudiante -->
        <div class="col-md-4 mb-4">
            <!-- Card Solicitante -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i> Datos del Solicitante</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nombre:</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $solicitante['nombre_completo'] }}</dd>

                        <dt class="col-sm-4 text-muted">DNI:</dt>
                        <dd class="col-sm-8">{{ $solicitante['dni'] }}</dd>

                        <dt class="col-sm-4 text-muted">Email:</dt>
                        <dd class="col-sm-8">{{ $solicitante['email'] }}</dd>

                        <dt class="col-sm-4 text-muted">Teléfono:</dt>
                        <dd class="col-sm-8">{{ $solicitante['telefono'] }}</dd>
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
                        <dd class="col-sm-8 fw-semibold">{{ $estudianteData['nombre_completo'] }}</dd>

                        <dt class="col-sm-4 text-muted">DNI:</dt>
                        <dd class="col-sm-8">{{ $estudianteData['dni'] }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Card Observación General -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i> Información del Trámite</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1 text-muted">
                        <strong>Tipo de trámite:</strong> {{ $tipoTramiteNombre }}
                    </p>
                    <p class="mb-0 text-muted">
                        <strong>Observaciones:</strong><br>
                        {{ $observacionGeneral }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Columna Central: Historial del Trámite -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i> Historial del Trámite</h6>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($historialTramitesEnriquecidos as $index => $item)
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
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $item['color_estado'] }}; color: white;">
                                            {{ $item['nombre_estado'] }}
                                        </span>
                                        <small class="text-muted">{{ $item['fecha_formateada'] }}</small>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted"><i class="bi bi-person-circle me-1"></i> {{ $item['nombre_usuario'] }}</small>
                                    </div>
                                    @if($item['registro']->observacion)
                                    <div class="mt-2 small text-muted bg-light p-2 rounded">
                                        <i class="bi bi-chat-dots me-1"></i> {{ $item['registro']->observacion }}
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
                    <small class="text-muted"><i class="bi bi-list me-1"></i> Total: {{ $totalRegistrosTramite }} registros</small>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Historial de Pagos -->
        @if($requierePago)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i> Historial de Pagos</h6>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($pagosEnriquecidos as $index => $pago)
                        <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 text-center" style="width: 40px;">
                                    @if($pago['es_efectivo'])
                                        <i class="bi bi-cash-stack text-success" style="font-size: 18px;"></i>
                                    @else
                                        <i class="bi bi-credit-card text-primary" style="font-size: 18px;"></i>
                                    @endif
                                    @if(!$loop->last)
                                    <div class="mx-auto" style="width: 2px; height: 20px; background: #dee2e6;"></div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $pago['registro']->estadoPago->color ?? '#6c757d' }}; color: white;">
                                            {{ $pago['registro']->estadoPago->nombre ?? 'N/A' }}
                                        </span>
                                        <small class="text-muted">{{ $pago['fecha_formateada'] }}</small>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <small class="text-muted"><i class="bi bi-person-circle me-1"></i> {{ $pago['registro']->user->nombre ?? 'Sistema' }}</small>
                                            <span class="ms-2 badge bg-light text-dark border">{{ $pago['monto_formateado'] }}</span>
                                        </div>
                                        @if($pago['registro']->pagoComprobante && $pago['registro']->pagoComprobante->comprobante_path)
                                        <a href="{{ route('mis-tramites.ver-comprobante', $pago['registro']->pagoComprobante->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endif
                                    </div>

                                    @if($pago['registro']->pagoComprobante)
                                    <div class="mt-2 p-2 bg-light rounded small">
                                        <div class="d-flex flex-wrap gap-3">
                                            <div>
                                                <i class="bi bi-upc-scan me-1"></i>
                                                <span class="text-muted">N° Operación:</span>
                                                <strong>{{ $pago['registro']->pagoComprobante->numero_operacion ?? 'N/A' }}</strong>
                                            </div>
                                            <div>
                                                <i class="bi bi-cash-stack me-1"></i>
                                                <span class="text-muted">Método:</span>
                                                <strong>{{ $pago['metodo_pago']->nombre ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        @if($pago['metodo_pago'] && $pago['metodo_pago']->entidad_financiera)
                                        <div class="mt-1">
                                            <span class="text-muted">Entidad:</span>
                                            <strong>{{ $pago['metodo_pago']->entidad_financiera }}</strong>
                                        </div>
                                        @endif
                                    </div>
                                    @endif

                                    @if($pago['registro']->observacion)
                                    <div class="mt-2 small text-muted bg-light p-2 rounded">
                                        <i class="bi bi-chat-dots me-1"></i> {{ $pago['registro']->observacion }}
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
                    <small class="text-muted"><i class="bi bi-list me-1"></i> Total: {{ $totalRegistrosPago }} registros</small>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Botón para subir comprobante -->
    @if($mostrarBotonSubirComprobante)
    <div class="row mt-3">
        <div class="col-12 text-center">
            <button class="btn btn-success" onclick="subirComprobante({{ $tramite->id }})">
                <i class="bi bi-cloud-upload me-1"></i> Subir Comprobante de Pago
            </button>
        </div>
    </div>
    @endif
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

        // Resetear campos
        const select = document.getElementById('tipo_pago_select');
        select.value = '';

        new bootstrap.Modal(document.getElementById('modalSubirComprobante')).show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const selectPago = document.getElementById('tipo_pago_select');
        const infoCuentaDiv = document.getElementById('infoCuenta');
        const camposNoEfectivo = document.getElementById('camposNoEfectivo');
        const mensajeEfectivo = document.getElementById('mensajeEfectivo');
        const numeroOperacion = document.getElementById('numero_operacion');
        const comprobanteFile = document.getElementById('comprobante_file');

        if (selectPago) {
            selectPago.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                const esEfectivo = opt.dataset.esEfectivo == '1';

                if (this.value && !esEfectivo) {
                    // Mostrar información de la cuenta
                    document.getElementById('info_entidad').innerHTML = `<strong>Entidad:</strong> ${opt.dataset.entidad || 'N/A'}`;
                    document.getElementById('info_numero_cuenta').innerHTML = `<strong>N° Cuenta:</strong> ${opt.dataset.numeroCuenta || 'N/A'}`;
                    document.getElementById('info_cci').innerHTML = `<strong>CCI:</strong> ${opt.dataset.cci || 'N/A'}`;
                    document.getElementById('info_titular').innerHTML = `<strong>Titular:</strong> ${opt.dataset.titular || 'N/A'}`;
                    infoCuentaDiv.classList.remove('d-none');

                    // Mostrar campos normales, ocultar mensaje efectivo
                    camposNoEfectivo.classList.remove('d-none');
                    mensajeEfectivo.classList.add('d-none');

                    // Hacer campos requeridos
                    numeroOperacion.required = true;
                    comprobanteFile.required = true;

                } else if (this.value && esEfectivo) {
                    // Ocultar información de cuenta
                    infoCuentaDiv.classList.add('d-none');

                    // Ocultar campos normales, mostrar mensaje efectivo
                    camposNoEfectivo.classList.add('d-none');
                    mensajeEfectivo.classList.remove('d-none');

                    // Quitar required de campos
                    numeroOperacion.required = false;
                    comprobanteFile.required = false;

                } else {
                    infoCuentaDiv.classList.add('d-none');
                    camposNoEfectivo.classList.remove('d-none');
                    mensajeEfectivo.classList.add('d-none');
                    numeroOperacion.required = true;
                    comprobanteFile.required = true;
                }
            });
        }
    });
</script>
@endsection
