@extends('layouts.app')
@section('title', 'Detalle del Trámite - ' . $tramite->codigo_tramite)
@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt me-2"></i>
                        Trámite: {{ $tramite->codigo_tramite }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('mis-tramites.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    {{-- Información General --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text text-muted">Código de Trámite</span>
                                    <span class="info-box-number">{{ $tramite->codigo_tramite }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text text-muted">Tipo de Trámite</span>
                                    <span class="info-box-number">{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</span>
                                    <span class="info-box-text">Costo: S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text text-muted">Fecha de Solicitud</span>
                                    <span class="info-box-number">{{ $tramite->fecha_solicitud ? \Carbon\Carbon::parse($tramite->fecha_solicitud)->format('d/m/Y H:i:s') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text text-muted">Fecha de Resolución</span>
                                    <span class="info-box-number">{{ $tramite->fecha_resolucion ? \Carbon\Carbon::parse($tramite->fecha_resolucion)->format('d/m/Y') : 'Pendiente' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Estados Actuales --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Estado del Trámite</h6>
                                </div>
                                <div class="card-body text-center">
                                    @if($estadoActual && $estadoActual->estadoTramite)
                                        <span class="badge" style="background-color: {{ $estadoActual->estadoTramite->color ?? '#6c757d' }}; font-size: 1.2rem; padding: 8px 15px;">
                                            {{ $estadoActual->estadoTramite->nombre }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Sin estado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Estado del Pago</h6>
                                </div>
                                <div class="card-body text-center">
                                    @if($estadoPagoActual && $estadoPagoActual->estadoPago)
                                        <span class="badge" style="background-color: {{ $estadoPagoActual->estadoPago->color ?? '#6c757d' }}; font-size: 1.2rem; padding: 8px 15px;">
                                            {{ $estadoPagoActual->estadoPago->nombre }}
                                        </span>
                                        <div class="mt-2">
                                            <small>Monto registrado: S/ {{ number_format($estadoPagoActual->monto, 2) }}</small>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary">Sin registro</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Información del Solicitante y Estudiante --}}
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">Datos del Solicitante</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td width="35%"><strong>Nombre:</strong></td>
                                            <td>{{ $tramite->user->nombre ?? 'N/A' }} {{ $tramite->user->apellido_paterno ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>DNI:</strong></td>
                                            <td>{{ $tramite->user->dni ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $tramite->user->email ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Teléfono:</strong></td>
                                            <td>{{ $tramite->user->telefono ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">Datos del Estudiante</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td width="35%"><strong>Nombre:</strong></td>
                                            <td>{{ $tramite->estudiante->user->nombre ?? 'N/A' }} {{ $tramite->estudiante->user->apellido_paterno ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>DNI:</strong></td>
                                            <td>{{ $tramite->estudiante->user->dni ?? 'N/A' }}</td>
                                        </tr>
                                        @if($tramite->observaciones)
                                        <tr>
                                            <td><strong>Observaciones:</strong></td>
                                            <td>{{ $tramite->observaciones }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Historial de Estados --}}
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Historial de Estados del Trámite</h6>
                                </div>
                                <div class="card-body p-0">
                                    @forelse($tramite->tramiteRegistros as $registro)
                                        <div class="p-3 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge" style="background-color: {{ $registro->estadoTramite->color ?? '#6c757d' }}">
                                                    {{ $registro->estadoTramite->nombre ?? 'N/A' }}
                                                </span>
                                                <small class="text-muted">{{ $registro->created_at->format('d/m/Y H:i:s') }}</small>
                                            </div>
                                            <div class="mt-2">
                                                <strong>Usuario:</strong> {{ $registro->user->nombre ?? 'Sistema' }}<br>
                                                @if($registro->observacion)
                                                    <strong>Observación:</strong> {{ $registro->observacion }}
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-3 text-center text-muted">
                                            No hay registros de cambios de estado
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Historial de Estados del Pago</h6>
                                </div>
                                <div class="card-body p-0">
                                    @forelse($tramite->tramitePagoRegistros as $registroPago)
                                        <div class="p-3 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge" style="background-color: {{ $registroPago->estadoPago->color ?? '#6c757d' }}">
                                                    {{ $registroPago->estadoPago->nombre ?? 'N/A' }}
                                                </span>
                                                <small class="text-muted">{{ $registroPago->fecha_registro ? \Carbon\Carbon::parse($registroPago->fecha_registro)->format('d/m/Y H:i:s') : 'N/A' }}</small>
                                            </div>
                                            <div class="mt-2">
                                                <strong>Monto:</strong> S/ {{ number_format($registroPago->monto, 2) }}<br>
                                                <strong>Usuario:</strong> {{ $registroPago->user->nombre ?? 'Sistema' }}<br>
                                                @if($registroPago->observacion)
                                                    <strong>Observación:</strong> {{ $registroPago->observacion }}<br>
                                                @endif
                                                @if($registroPago->pagoComprobante)
                                                    <strong>Comprobante:</strong>
                                                    <a href="{{ route('mis-tramites.ver-comprobante', $registroPago->pagoComprobante->id) }}" target="_blank" class="btn btn-sm btn-link">
                                                        <i class="fas fa-file-alt"></i> Ver comprobante
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-3 text-center text-muted">
                                            No hay registros de cambios de estado de pago
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Botón para subir comprobante --}}
                    @php
                        $costoTotal = $tramite->tipoTramite->costo ?? 0;
                        $montoPagado = $tramite->monto_pagado ?? 0;
                        $saldoPendiente = $costoTotal - $montoPagado;
                    @endphp

                    @if($tramite->tipoTramite->requiere_pago && $saldoPendiente > 0)
                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <button class="btn btn-success btn-lg" onclick="subirComprobante({{ $tramite->id }})">
                                <i class="fas fa-upload me-2"></i>Subir Comprobante de Pago
                                (Saldo pendiente: S/ {{ number_format($saldoPendiente, 2) }})
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para subir comprobante --}}
<div class="modal fade" id="modalSubirComprobante" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data" id="formComprobante">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Subir Comprobante de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tramite_id" id="tramite_id_comprobante">

                    <div class="mb-3">
                        <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                        <select name="tipo_pago_id" class="form-select" id="tipo_pago_select" required>
                            <option value="">Seleccione un método de pago...</option>
                            @foreach($tiposPago as $tipo)
                                <option value="{{ $tipo->id }}"
                                        data-categoria="{{ $tipo->categoria }}"
                                        data-entidad="{{ $tipo->entidad_financiera }}"
                                        data-numero-cuenta="{{ $tipo->numero_cuenta }}"
                                        data-cci="{{ $tipo->cci }}"
                                        data-titular="{{ $tipo->titular_cuenta }}"
                                        data-celular="{{ $tipo->numero_celular }}">
                                    {{ $tipo->nombre }}
                                    @if($tipo->entidad_financiera)
                                        - {{ $tipo->entidad_financiera }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Información de la cuenta --}}
                    <div class="mb-3 d-none" id="infoCuenta">
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Datos para el pago:</strong><br>
                            <div id="info_entidad"></div>
                            <div id="info_numero_cuenta"></div>
                            <div id="info_cci"></div>
                            <div id="info_titular"></div>
                            <div id="info_celular"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Número de Operación / Voucher <span class="text-danger">*</span></label>
                        <input type="text" name="numero_operacion" class="form-control" placeholder="Ej: 1234567890" required>
                        <small class="text-muted">Número de transferencia, referencia o código de operación</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Monto a Pagar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" name="monto" class="form-control" id="monto_pago" required>
                        </div>
                        <small class="text-muted" id="info_monto"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comprobante (imagen o PDF) <span class="text-danger">*</span></label>
                        <input type="file" name="comprobante" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="text-muted">Máx. 5MB. Formatos: JPG, PNG, PDF</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones (opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="2" placeholder="Información adicional sobre el pago..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Subir Comprobante</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const costoTotal = {{ $tramite->tipoTramite->costo ?? 0 }};
    const montoPagado = {{ $tramite->monto_pagado ?? 0 }};
    const saldoPendiente = costoTotal - montoPagado;

    function subirComprobante(id) {
        document.getElementById('tramite_id_comprobante').value = id;
        document.getElementById('formComprobante').action = "/mis-tramites/" + id + "/comprobante";

        document.getElementById('monto_pago').value = saldoPendiente.toFixed(2);
        document.getElementById('monto_pago').max = saldoPendiente;
        document.getElementById('info_monto').innerHTML = `Monto total: S/ ${costoTotal.toFixed(2)} | Pagado: S/ ${montoPagado.toFixed(2)} | <strong>Saldo pendiente: S/ ${saldoPendiente.toFixed(2)}</strong>`;

        var modal = new bootstrap.Modal(document.getElementById('modalSubirComprobante'));
        modal.show();
    }

    // Mostrar información de la cuenta al seleccionar método de pago
    document.addEventListener('DOMContentLoaded', function() {
        const selectPago = document.getElementById('tipo_pago_select');
        if (selectPago) {
            selectPago.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const infoCuentaDiv = document.getElementById('infoCuenta');

                if (this.value) {
                    const categoria = selectedOption.dataset.categoria;
                    // Mostrar info solo si no es efectivo
                    if (categoria !== 'efectivo') {
                        document.getElementById('info_entidad').innerHTML = `<strong>Entidad:</strong> ${selectedOption.dataset.entidad || 'N/A'}`;
                        document.getElementById('info_numero_cuenta').innerHTML = `<strong>N° Cuenta:</strong> ${selectedOption.dataset.numeroCuenta || 'N/A'}`;
                        document.getElementById('info_cci').innerHTML = `<strong>CCI:</strong> ${selectedOption.dataset.cci || 'N/A'}`;
                        document.getElementById('info_titular').innerHTML = `<strong>Titular:</strong> ${selectedOption.dataset.titular || 'N/A'}`;
                        document.getElementById('info_celular').innerHTML = `<strong>Celular:</strong> ${selectedOption.dataset.celular || 'N/A'}`;
                        infoCuentaDiv.classList.remove('d-none');
                    } else {
                        infoCuentaDiv.classList.add('d-none');
                    }
                } else {
                    infoCuentaDiv.classList.add('d-none');
                }
            });
        }
    });
</script>
@endsection
