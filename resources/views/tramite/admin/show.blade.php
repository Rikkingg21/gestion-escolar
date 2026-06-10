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

    <!-- SECCIÓN 1: Resumen rápido (usando variables del controlador) -->
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

                    @if($requierePago)
                        @if($saldoPendiente > 0)
                        <div class="alert alert-warning mb-0 mt-4 text-center py-2">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Saldo pendiente:</strong> S/ {{ number_format($saldoPendiente, 2) }}
                        </div>
                        @elseif($saldoPendiente <= 0 && $montoPagado > 0)
                        <div class="alert alert-success mb-0 mt-4 text-center py-2">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Pago completado</strong> - Total pagado: S/ {{ number_format($montoPagado, 2) }}
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

            <!-- Card Observación -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i> Observación General</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted">
                        <i class="bi bi-file-text me-1"></i> {{ $observacionGeneral }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Columna Central: Historial del Trámite -->
        <div class="{{ $requierePago ? 'col-md-4' : 'col-md-8' }} mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i> Historial del Trámite</h6>
                        <button type="button" class="btn btn-sm btn-light rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCambiarEstadoTramite">
                            <i class="bi bi-pencil me-1"></i> Cambiar Estado
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($historialTramites as $index => $item)
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
                                    @if($item['observacion'])
                                    <div class="mt-2 small text-muted bg-light p-2 rounded">
                                        <i class="bi bi-chat-dots me-1"></i> {{ $item['observacion'] }}
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

        <!-- Columna Derecha: Historial de Pagos (solo si requiere pago) -->
        @if($requierePago)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i> Historial de Pagos</h6>
                        <button type="button" class="btn btn-sm btn-light rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCambiarEstadoPago">
                            <i class="bi bi-pencil me-1"></i> Cambiar Estado
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @forelse($pagosEnriquecidos as $index => $pago)
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
                                        <span class="badge px-3 py-2 rounded-pill d-flex align-items-center gap-1" style="background-color: {{ $pago['color_estado'] }}; color: white;">
                                            <i class="{{ $pago['icono_clase'] }}"></i>
                                            {{ $pago['nombre_estado'] }}
                                        </span>
                                        <small class="text-muted">{{ $pago['fecha_formateada'] }}</small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <small class="text-muted"><i class="bi bi-person-circle me-1"></i> {{ $pago['nombre_usuario'] }}</small>
                                            <span class="ms-2 badge bg-light text-dark border">{{ $pago['monto_formateado'] }}</span>
                                        </div>
                                        @if($pago['tiene_comprobante'])
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalVerComprobante"
                                                data-comprobante-id="{{ $pago['comprobante']['id'] }}"
                                                data-comprobante-url="{{ route('tramiteadmin.ver-comprobante', $pago['comprobante']['id']) }}"
                                                data-comprobante-nombre="Comprobante_{{ $tramite->codigo_tramite }}_{{ $pago['id'] }}"
                                                title="Ver comprobante">
                                            <i class="bi bi-eye me-1"></i> Ver
                                        </button>
                                        @endif
                                    </div>

                                    <!-- Mostrar observación del registro de pago (Tramitepagoregistro) -->
                                    @if($pago['observacion'])
                                    <div class="mt-1 small text-muted bg-light p-2 rounded">
                                        <i class="bi bi-chat-dots me-1"></i> <strong>Registro:</strong> {{ $pago['observacion'] }}
                                    </div>
                                    @endif

                                    <!-- Mostrar datos del comprobante (Pagocomprobante) -->
                                    @if($pago['tiene_comprobante'])
                                    <div class="mt-2 small border-start border-3 border-primary ps-2">
                                        <div class="d-flex flex-wrap gap-3">
                                            <div>
                                                <i class="bi bi-upc-scan text-muted me-1"></i>
                                                <span class="text-muted">N° Operación:</span>
                                                <strong>{{ $pago['comprobante']['numero_operacion'] }}</strong>
                                            </div>
                                            <div>
                                                <i class="bi bi-cash-stack text-muted me-1"></i>
                                                <span class="text-muted">Método:</span>
                                                <strong>{{ $pago['comprobante']['metodo_pago_nombre'] }}</strong>
                                            </div>
                                        </div>
                                        @if($pago['comprobante']['metodo_pago_entidad'])
                                        <div class="mt-1">
                                            <i class="bi bi-building text-muted me-1"></i>
                                            <span class="text-muted">Entidad:</span>
                                            <strong>{{ $pago['comprobante']['metodo_pago_entidad'] }}</strong>
                                        </div>
                                        @endif
                                        @if($pago['comprobante']['observaciones'])
                                        <div class="mt-1">
                                            <i class="bi bi-chat-quote text-muted me-1"></i>
                                            <span class="text-muted">Observaciones del comprobante:</span>
                                            <strong>{{ $pago['comprobante']['observaciones'] }}</strong>
                                        </div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Botones rápidos de acción -->
                                    @if($pago['mostrar_botones_accion'])
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-success rounded-pill"
                                                onclick="abrirModalConComprobante({{ $tramite->id }}, {{ $pago['id'] }}, 'aprobado')">
                                            <i class="bi bi-check-lg me-1"></i> Aprobar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger rounded-pill"
                                                onclick="abrirModalConComprobante({{ $tramite->id }}, {{ $pago['id'] }}, 'rechazado')">
                                            <i class="bi bi-x-lg me-1"></i> Rechazar
                                        </button>
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
                        <div>
                            <span class="badge fs-6 px-3 py-2" style="background-color: {{ $estadoActualTramite->estadoTramite->color ?? '#6c757d' }}">
                                <i class="bi bi-tag me-1"></i> {{ $estadoActualTramite->estadoTramite->nombre ?? 'Sin estado' }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo Estado <span class="text-danger">*</span></label>
                        <select name="estado_tramite_id" class="form-select" required>
                            <option value="">Seleccione un estado...</option>
                            @foreach($estadosTramite as $estado)
                                <option value="{{ $estado->id }}" {{ ($estadoActualTramite && $estadoActualTramite->estado_tramite_id == $estado->id) ? 'disabled' : '' }}>
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

<!-- MODAL: Cambiar Estado del Pago -->
@if($requierePago)
<div class="modal fade" id="modalCambiarEstadoPago" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">
                    <i class="bi bi-credit-card me-2"></i> Cambiar Estado de Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('tramiteadmin.update-estado-pago', $tramite->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seleccione el comprobante</label>
                        <select name="pago_registro_id" class="form-select" id="selectPagoRegistro" required>
                            <option value="">Seleccione un comprobante...</option>
                            @foreach($opcionesComprobantes as $opcion)
                                <option value="{{ $opcion['id'] }}"
                                        data-monto="{{ $opcion['monto'] }}"
                                        data-operacion="{{ $opcion['numero_operacion'] }}"
                                        data-estado="{{ $opcion['estado_nombre'] }}">
                                    {{ $opcion['icono'] }} {{ $opcion['fecha'] }}
                                    - {{ $opcion['monto_formateado'] }}
                                    - Estado: {{ $opcion['estado_nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="infoComprobanteSeleccionado" class="alert alert-info mb-3 d-none">
                        <div class="small">
                            <div><strong><i class="bi bi-cash-stack me-1"></i> Monto:</strong> <span id="info_monto"></span></div>
                            <div><strong><i class="bi bi-upc-scan me-1"></i> N° Operación:</strong> <span id="info_operacion"></span></div>
                            <div><strong><i class="bi bi-tag me-1"></i> Estado actual:</strong> <span id="info_estado_actual"></span></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo Estado <span class="text-danger">*</span></label>
                        <select name="estado_pago_id" class="form-select" id="selectNuevoEstado" required>
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
        const modalComprobante = document.getElementById('modalVerComprobante');
        if (modalComprobante) {
            modalComprobante.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                currentComprobanteUrl = button.getAttribute('data-comprobante-url');
                currentComprobanteNombre = button.getAttribute('data-comprobante-nombre');
                document.getElementById('btnAbrirNuevaPestana').href = currentComprobanteUrl;
                document.getElementById('btnDescargar').href = currentComprobanteUrl;
                document.getElementById('btnDescargar').setAttribute('download', currentComprobanteNombre);
                cargarComprobante(currentComprobanteUrl);
            });

            modalComprobante.addEventListener('hidden.bs.modal', function() {
                document.getElementById('comprobante-contenedor').innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando comprobante...</p>
                `;
            });
        }

        // Mostrar información del comprobante seleccionado
        const selectComprobante = document.getElementById('selectPagoRegistro');
        if (selectComprobante) {
            selectComprobante.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const infoDiv = document.getElementById('infoComprobanteSeleccionado');
                if (this.value) {
                    document.getElementById('info_monto').innerHTML = `S/ ${selectedOption.dataset.monto || '0'}`;
                    document.getElementById('info_operacion').innerHTML = selectedOption.dataset.operacion || 'N/A';
                    document.getElementById('info_estado_actual').innerHTML = selectedOption.dataset.estado || 'N/A';
                    infoDiv.classList.remove('d-none');
                } else {
                    infoDiv.classList.add('d-none');
                }
            });
        }
    });

    function cargarComprobante(url) {
        const contenedor = document.getElementById('comprobante-contenedor');
        contenedor.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando comprobante...</p>
        `;
        fetch(url, { method: 'HEAD' })
            .then(response => {
                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.includes('pdf')) {
                    contenedor.innerHTML = `<iframe src="${url}" style="width: 100%; height: 70vh; border: none;" class="rounded"></iframe>`;
                } else if (contentType && contentType.includes('image')) {
                    contenedor.innerHTML = `<img src="${url}" alt="Comprobante" class="img-fluid rounded shadow-sm" style="max-height: 70vh;">`;
                } else {
                    contenedor.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i> No se puede previsualizar este tipo de archivo.
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
                        <i class="bi bi-exclamation-circle me-2"></i> Error al cargar el comprobante.
                    </div>
                    <a href="${url}" class="btn btn-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Abrir directamente
                    </a>
                `;
            });
    }

    function abrirModalConComprobante(tramiteId, pagoRegistroId, accion) {
        const select = document.getElementById('selectPagoRegistro');
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value == pagoRegistroId) {
                select.selectedIndex = i;
                const event = new Event('change');
                select.dispatchEvent(event);
                break;
            }
        }
        const estadoSelect = document.getElementById('selectNuevoEstado');
        for (let i = 0; i < estadoSelect.options.length; i++) {
            const texto = estadoSelect.options[i].text.toLowerCase();
            if (accion === 'aprobado' && texto.includes('aprobado')) {
                estadoSelect.selectedIndex = i;
                break;
            }
            if (accion === 'rechazado' && texto.includes('rechazado')) {
                estadoSelect.selectedIndex = i;
                break;
            }
        }
        const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstadoPago'));
        modal.show();
    }
</script>
@endsection
