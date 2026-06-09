@extends('layouts.app')
@section('title', 'Mis Trámites')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Mis Trámites</h3>
            <div class="card-tools">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearTramite">
                    <i class="fas fa-plus me-2"></i>Nuevo Trámite
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Tipo de Trámite</th>
                            <th>Fecha Solicitud</th>
                            <th>Estado del Trámite</th>
                            <th>Estado del Pago</th>
                            <th>Monto a Pagar</th>
                            <th>Monto Pagado</th>
                            <th>Fecha Resolución</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tramites as $tramite)
                        <tr>
                            <td>{{ $tramite->codigo_tramite }}</td>
                            <td>{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</td>
                            <td>{{ $tramite->fecha_solicitud ? \Carbon\Carbon::parse($tramite->fecha_solicitud)->format('d/m/Y') : 'N/A' }}</td>

                            {{-- Estado del Trámite --}}
                            <td>
                                @php
                                    $ultimoRegistro = $tramite->tramiteRegistros()->latest()->first();
                                @endphp
                                @if($ultimoRegistro && $ultimoRegistro->estadoTramite)
                                    <span class="badge" style="background-color: {{ $ultimoRegistro->estadoTramite->color ?? '#6c757d' }}">
                                        {{ $ultimoRegistro->estadoTramite->nombre }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Sin estado</span>
                                @endif
                            </td>

                            {{-- Estado del Pago --}}
                            <td>
                                @php
                                    $ultimoPago = $tramite->tramitePagoRegistros()->latest('fecha_registro')->first();
                                @endphp
                                @if($ultimoPago && $ultimoPago->estadoPago)
                                    <span class="badge" style="background-color: {{ $ultimoPago->estadoPago->color ?? '#6c757d' }}">
                                        {{ $ultimoPago->estadoPago->nombre }}
                                    </span>
                                @elseif($tramite->tipoTramite->requiere_pago)
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @else
                                    <span class="badge bg-secondary">No aplica</span>
                                @endif
                            </td>

                            <td>S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}</td>
                            <td>S/ {{ number_format($tramite->monto_pagado, 2) }}</td>
                            <td>{{ $tramite->fecha_resolucion ? \Carbon\Carbon::parse($tramite->fecha_resolucion)->format('d/m/Y') : 'Pendiente' }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="verDetalle({{ $tramite->id }})" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($tramite->tipoTramite->requiere_pago && $tramite->monto_pagado < ($tramite->tipoTramite->costo ?? 0))
                                <button class="btn btn-sm btn-success" onclick="subirComprobante({{ $tramite->id }})" title="Subir comprobante de pago">
                                    <i class="fas fa-upload"></i> Pago
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                No tienes trámites registrados
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear trámite --}}
<div class="modal fade" id="modalCrearTramite" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('mis-tramites.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Nuevo Trámite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Trámite <span class="text-danger">*</span></label>
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
                        <label class="form-label">Datos del Apoderado:</label>
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <div class="fw-bold">{{ auth()->user()->nombre ?? '-' }} {{ auth()->user()->apellido_paterno ?? '-' }} {{ auth()->user()->apellido_materno ?? '-' }}</div>
                                <div><strong>DNI:</strong> <span class="badge bg-secondary">{{ auth()->user()->dni ?? '-' }}</span></div>
                                <div><strong>Email:</strong> {{ auth()->user()->email ?? '-' }}</div>
                                <div><strong>Teléfono:</strong> {{ auth()->user()->telefono ?? '-' }}</div>
                                @if($parentesco)
                                    <div><strong>Parentesco:</strong> <span class="badge bg-info">{{ $parentesco }}</span></div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estudiante <span class="text-danger">*</span></label>
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
                            <div class="text-warning mt-1">
                                <small>No tiene estudiantes registrados. Contacte con administración.</small>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"></textarea>
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
        // Redirigir a la vista de detalle o abrir modal
        window.location.href = "/mis-tramites/" + id;
    }

    function subirComprobante(id) {
        document.getElementById('tramite_id_comprobante').value = id;
        document.getElementById('formComprobante').action = "/mis-tramites/" + id + "/comprobante";
        var modal = new bootstrap.Modal(document.getElementById('modalSubirComprobante'));
        modal.show();
    }
</script>
@endsection
