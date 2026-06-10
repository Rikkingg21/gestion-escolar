@extends('layouts.app')
@section('title', 'Mis Trámites')
@section('content')

<div class="container-fluid">

    <!-- Botón para nuevo trámite -->
    <div class="row mb-3">
        <div class="col-12">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearTramite">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Trámite
            </button>
        </div>
    </div>

    <!-- Tabla de trámites -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h3 class="card-title mb-0">
                <i class="bi bi-table me-2 text-primary"></i>
                <span class="fw-semibold">Mis Trámites</span>
            </h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle" id="tramitesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Tipo de Trámite</th>
                            <th class="text-center">Fecha Solicitud</th>
                            <th class="text-center">Estado Trámite</th>
                            <th class="text-center">Estado Pago</th>
                            <th class="text-end">Monto a Pagar</th>
                            <th class="text-end">Monto Pagado</th>
                            <th class="text-center">Fecha Resolución</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tramites as $tramite)
                        @php
                            $ultimoRegistro = $tramite->tramiteRegistros()->latest()->first();
                            $ultimoPago = $tramite->tramitePagoRegistros()->latest('fecha_registro')->first();
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                    <i class="bi bi-upc-scan me-1"></i> {{ $tramite->codigo_tramite }}
                                </span>
                            </td>
                            <td>{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</td>
                            <td class="text-center">
                                <small><i class="bi bi-calendar3 me-1 text-muted"></i> {{ $tramite->fecha_solicitud ? \Carbon\Carbon::parse($tramite->fecha_solicitud)->format('d/m/Y') : 'N/A' }}</small>
                            </td>
                            <td class="text-center">
                                @if($ultimoRegistro && $ultimoRegistro->estadoTramite)
                                    <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $ultimoRegistro->estadoTramite->color ?? '#6c757d' }}; color: white;">
                                        {{ $ultimoRegistro->estadoTramite->nombre }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2 rounded-pill">Sin estado</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($ultimoPago && $ultimoPago->estadoPago)
                                    <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $ultimoPago->estadoPago->color ?? '#6c757d' }}; color: white;">
                                        {{ $ultimoPago->estadoPago->nombre }}
                                    </span>
                                @elseif($tramite->tipoTramite->requiere_pago)
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Pendiente</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2 rounded-pill">No aplica</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-success">
                                S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold {{ ($tramite->monto_pagado_total ?? $tramite->monto_pagado) >= ($tramite->tipoTramite->costo ?? 0) ? 'text-success' : 'text-warning' }}">
                                    S/ {{ number_format($tramite->monto_pagado_total ?? $tramite->monto_pagado, 2) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <small>{{ $tramite->fecha_resolucion ? \Carbon\Carbon::parse($tramite->fecha_resolucion)->format('d/m/Y') : 'Pendiente' }}</small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('mis-tramites.show', $tramite->id) }}" class="btn btn-sm btn-info rounded-pill" title="Ver detalle">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </a>
                                    @if($tramite->tipoTramite->requiere_pago && ($tramite->monto_pagado_total ?? $tramite->monto_pagado) < ($tramite->tipoTramite->costo ?? 0))
                                    <button class="btn btn-sm btn-success rounded-pill" onclick="subirComprobante({{ $tramite->id }})" title="Subir comprobante">
                                        <i class="bi bi-cloud-upload me-1"></i> Pago
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-inbox display-4 d-block text-muted opacity-50 mb-3"></i>
                                <p class="text-muted mb-0">No tienes trámites registrados</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear trámite -->
<div class="modal fade" id="modalCrearTramite" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('mis-tramites.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-file-text me-2"></i> Nuevo Trámite
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Trámite <span class="text-danger">*</span></label>
                        <select name="tipo_tramite_id" class="form-select" required>
                            <option value="">Seleccione...</option>
                            @foreach($tipoTramitesActivos as $tipo)
                            <option value="{{ $tipo->id }}" data-costo="{{ $tipo->costo }}" data-requiere-pago="{{ $tipo->requiere_pago }}">
                                {{ $tipo->nombre }} - S/ {{ number_format($tipo->costo, 2) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Datos del Apoderado:</label>
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <div class="fw-semibold">{{ auth()->user()->nombre ?? '-' }} {{ auth()->user()->apellido_paterno ?? '-' }} {{ auth()->user()->apellido_materno ?? '-' }}</div>
                                <div><i class="bi bi-card-text me-1 text-muted"></i> DNI: <span class="badge bg-secondary">{{ auth()->user()->dni ?? '-' }}</span></div>
                                <div><i class="bi bi-envelope me-1 text-muted"></i> {{ auth()->user()->email ?? '-' }}</div>
                                <div><i class="bi bi-telephone me-1 text-muted"></i> {{ auth()->user()->telefono ?? '-' }}</div>
                                @if($parentesco)
                                    <div><i class="bi bi-people me-1 text-muted"></i> Parentesco: <span class="badge bg-info">{{ $parentesco }}</span></div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estudiante <span class="text-danger">*</span></label>
                        <select name="estudiante_id" class="form-select" required>
                            <option value="">Seleccione un estudiante...</option>
                            @foreach($estudiantes as $estudiante)
                                <option value="{{ $estudiante->id }}">
                                    {{ $estudiante->user->nombre ?? '' }} {{ $estudiante->user->apellido_paterno ?? '' }} {{ $estudiante->user->apellido_materno ?? '' }}
                                    @if($estudiante->user && $estudiante->user->dni)
                                        (DNI: {{ $estudiante->user->dni }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @if($estudiantes->isEmpty())
                            <div class="text-warning mt-1 small">⚠️ No tiene estudiantes registrados. Contacte con administración.</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3" placeholder="Información adicional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Trámite</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function verDetalle(id) {
        window.location.href = "/mis-tramites/" + id;
    }

    function subirComprobante(id) {
        window.location.href = "/mis-tramites/" + id;
    }
</script>

@endsection
