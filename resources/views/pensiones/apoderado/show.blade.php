@extends('layouts.app')
@section('title', 'Detalle de Pensión')
@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Detalle de Pensión</h4>
        <a href="{{ route('pensiones.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a Mis Pensiones
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Revise los siguientes errores:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Resumen --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="row text-center">
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mb-2 mx-auto" style="width: fit-content;">
                        <i class="bi bi-person text-primary fs-2"></i>
                    </div>
                    <h6 class="text-muted mb-1">Estudiante</h6>
                    <h5 class="mb-0 fw-bold">{{ $pension->matricula->estudiante->user->nombre_completo }}</h5>
                    <small class="text-muted">{{ $pension->matricula->grado->nombre_completo }}</small>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-2 mx-auto" style="width: fit-content;">
                        <i class="bi bi-calendar-event text-success fs-2"></i>
                    </div>
                    <h6 class="text-muted mb-1">Vencimiento</h6>
                    <h5 class="mb-0 fw-bold">{{ $pension->fecha_vencimiento_formateada }}</h5>
                    @if($pension->atrasada)
                        <span class="badge text-bg-danger">Atrasada</span>
                    @endif
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-2 mx-auto" style="width: fit-content;">
                        <i class="bi bi-cash-stack text-warning fs-2"></i>
                    </div>
                    <h6 class="text-muted mb-1">Monto</h6>
                    <h5 class="mb-0 fw-bold">{{ $pension->monto_formateado }}</h5>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="bg-info bg-opacity-10 rounded-circle p-3 mb-2 mx-auto" style="width: fit-content;">
                        <i class="bi bi-flag text-info fs-2"></i>
                    </div>
                    <h6 class="text-muted mb-1">Estado</h6>
                    <span class="badge text-bg-{{ $pension->estado_efectivo_color }} fs-6">
                        {{ $pension->estado_efectivo_label }}
                    </span>
                </div>
            </div>

            @if($pension->esPagada())
                <div class="alert alert-success mb-0 mt-4 text-center py-2">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Cuota pagada</strong> - Total pagado: {{ $pension->monto_pagado_formateado }}
                </div>
            @else
                <div class="alert alert-warning mb-0 mt-4 text-center py-2">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Saldo pendiente:</strong> {{ $pension->saldo_pendiente_formateado }}
                </div>
            @endif
        </div>
    </div>

    {{-- Historial de pagos --}}
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i> Historial de pagos</h6>
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
                            <th>Comprobante</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pension->pagoRegistros as $registro)
                            <tr>
                                <td>{{ $registro->fecha_registro_formateada }}</td>
                                <td>{{ $registro->monto_formateado }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $registro->estadoPago->color ?? '#6c757d' }}; color: white;">
                                        {{ $registro->estadoPago->nombre ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $registro->pago?->metodoPago?->nombre ?? 'N/A' }}</td>
                                <td>{{ $registro->pago?->numero_operacion ?? 'N/A' }}</td>
                                <td>
                                    @if($registro->pago && $registro->pago->comprobante_path)
                                        <a href="{{ route('pensiones.ver-comprobante', $registro->pago->id) }}" target="_blank"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                    @else
                                        <span class="badge bg-light text-dark border">Sin archivo</span>
                                    @endif
                                </td>
                                <td class="small">{{ $registro->observacion ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
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
            <button class="btn btn-success btn-lg" onclick="subirComprobante()">
                <i class="bi bi-cloud-upload me-1"></i> Pagar pensión
            </button>
            @if($pasarelaHabilitada)
                <button class="btn btn-primary btn-lg" onclick="abrirPagoTarjeta()">
                    <i class="bi bi-credit-card me-1"></i> Pagar con tarjeta
                </button>
            @endif
        </div>
    @endif
</div>

@if($pasarelaHabilitada)
    <form id="formTarjeta" method="POST" action="{{ route('pensiones.tarjeta', $pension->id) }}" class="d-none">
        @csrf
        <input type="hidden" name="token" id="token_tarjeta">
    </form>
@endif

{{-- Modal para subir comprobante --}}
<div class="modal fade" id="modalSubirComprobante" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('pensiones.comprobante', $pension->id) }}" method="POST"
                  enctype="multipart/form-data" id="formComprobante">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i> Pagar pensión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Cuota:</span>
                            <strong>{{ $pension->concepto }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Monto a pagar:</span>
                            <strong>{{ $pension->monto_formateado }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Vencimiento:</span>
                            <span>{{ $pension->fecha_vencimiento_formateada }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                        <select name="tipo_pago_id" class="form-select" id="tipo_pago_select" required>
                            <option value="">Seleccione un método de pago...</option>
                            @foreach($tiposPago as $tipo)
                                <option value="{{ $tipo->id }}"
                                        data-es-efectivo="{{ $tipo->es_efectivo }}"
                                        data-entidad="{{ $tipo->entidad_financiera }}"
                                        data-numero-cuenta="{{ $tipo->numero_cuenta }}"
                                        data-cci="{{ $tipo->cci }}"
                                        data-titular="{{ $tipo->titular_cuenta }}">
                                    {{ $tipo->nombre }}
                                    @if($tipo->entidad_financiera)
                                        - {{ $tipo->entidad_financiera }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="infoCuenta">
                        <div class="alert alert-info">
                            <strong><i class="bi bi-info-circle me-1"></i> Datos para el pago:</strong><br>
                            <div id="info_entidad"></div>
                            <div id="info_numero_cuenta"></div>
                            <div id="info_cci"></div>
                            <div id="info_titular"></div>
                        </div>
                    </div>

                    <div id="camposNoEfectivo">
                        <div class="mb-3">
                            <label class="form-label">Número de Operación / Voucher <span class="text-danger">*</span></label>
                            <input type="text" name="numero_operacion" id="numero_operacion" class="form-control"
                                   placeholder="Ej: 1234567890" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comprobante (imagen o PDF) <span class="text-danger">*</span></label>
                            <input type="file" name="comprobante" id="comprobante_file" class="form-control"
                                   accept=".jpg,.jpeg,.png,.pdf" required>
                            <small class="text-muted">Máx. 5MB. Formatos: JPG, PNG, PDF.</small>
                        </div>
                    </div>

                    <div class="alert alert-warning d-none" id="mensajeEfectivo">
                        <i class="bi bi-cash-stack me-1"></i>
                        Pagarás en efectivo. El administrador verificará el pago en la institución.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones (opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="2"
                                  placeholder="Información adicional sobre el pago..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function subirComprobante() {
        const select = document.getElementById('tipo_pago_select');
        select.value = '';
        new bootstrap.Modal(document.getElementById('modalSubirComprobante')).show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selectPago = document.getElementById('tipo_pago_select');
        const infoCuentaDiv = document.getElementById('infoCuenta');
        const camposNoEfectivo = document.getElementById('camposNoEfectivo');
        const mensajeEfectivo = document.getElementById('mensajeEfectivo');
        const numeroOperacion = document.getElementById('numero_operacion');
        const comprobanteFile = document.getElementById('comprobante_file');

        if (selectPago) {
            selectPago.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                const esEfectivo = opt.dataset.esEfectivo == '1';

                if (this.value && !esEfectivo) {
                    document.getElementById('info_entidad').innerHTML = `<strong>Entidad:</strong> ${opt.dataset.entidad || 'N/A'}`;
                    document.getElementById('info_numero_cuenta').innerHTML = `<strong>N° Cuenta:</strong> ${opt.dataset.numeroCuenta || 'N/A'}`;
                    document.getElementById('info_cci').innerHTML = `<strong>CCI:</strong> ${opt.dataset.cci || 'N/A'}`;
                    document.getElementById('info_titular').innerHTML = `<strong>Titular:</strong> ${opt.dataset.titular || 'N/A'}`;
                    infoCuentaDiv.classList.remove('d-none');
                    camposNoEfectivo.classList.remove('d-none');
                    mensajeEfectivo.classList.add('d-none');
                    numeroOperacion.required = true;
                    comprobanteFile.required = true;
                } else if (this.value && esEfectivo) {
                    infoCuentaDiv.classList.add('d-none');
                    camposNoEfectivo.classList.add('d-none');
                    mensajeEfectivo.classList.remove('d-none');
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

@if($pasarelaHabilitada)
    <script src="https://checkout.culqi.com/js/v4"></script>
    <script>
        Culqi.publicKey = '{{ $culqiPublicKey }}';

        Culqi.settings({
            title: '{{ $pension->concepto }}',
            currency: 'PEN',
            description: '{{ $pension->matricula->estudiante->user->nombre_completo }} - {{ $pension->concepto }}',
            amount: {{ $pension->monto }}
        });

        window.culqi = function () {
            if (Culqi.token) {
                document.getElementById('token_tarjeta').value = Culqi.token.id;
                Culqi.close();
                document.getElementById('formTarjeta').submit();
            } else if (Culqi.order) {
                Culqi.close();
            } else {
                alert(Culqi.error?.user_message ?? 'No se pudo completar el pago con tarjeta.');
                Culqi.close();
            }
        };

        function abrirPagoTarjeta() {
            if (typeof Culqi === 'undefined') {
                alert('No se pudo cargar Culqi.js. Revisa tu conexión o la configuración de la pasarela.');
                return;
            }
            Culqi.open();
        }
    </script>
@endif

@endsection