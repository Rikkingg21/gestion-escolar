@extends('layouts.app')
@section('title', 'Administración de Trámites')
@section('content')

<div class="container-fluid">
    {{-- Todas las estadísticas en UNA SOLA FILA --}}
    <div class="row mb-4">
        <div class="col-6 col-md-3 col-xl mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted small fw-semibold">Total Trámites</span>
                            <h2 class="mb-0 mt-1 fw-bold">{{ $totalTramites }}</h2>
                        </div>
                        <i class="bi bi-files fs-1 text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted small fw-semibold">Pendientes</span>
                            <h2 class="mb-0 mt-1 fw-bold">{{ $totalPendientes }}</h2>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted small fw-semibold">En Proceso</span>
                            <h2 class="mb-0 mt-1 fw-bold">{{ $totalEnProceso }}</h2>
                        </div>
                        <i class="bi bi-arrow-repeat fs-1 text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted small fw-semibold">Completados</span>
                            <h2 class="mb-0 mt-1 fw-bold">{{ $totalCompletados }}</h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted small fw-semibold">Pagos Pendientes</span>
                            <h2 class="mb-0 mt-1 fw-bold">{{ $totalPagosPendientes }}</h2>
                        </div>
                        <i class="bi bi-clock-history fs-1 text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted small fw-semibold">Pagos Aprobados</span>
                            <h2 class="mb-0 mt-1 fw-bold">{{ $totalPagosAprobados }}</h2>
                        </div>
                        <i class="bi bi-check2-circle fs-1 text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted small fw-semibold">Pagos Rechazados</span>
                            <h2 class="mb-0 mt-1 fw-bold">{{ $totalPagosRechazados }}</h2>
                        </div>
                        <i class="bi bi-x-circle fs-1 text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de trámites --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0">
                    <i class="bi bi-table me-2 text-primary"></i>
                    <span class="fw-semibold">Listado de Trámites</span>
                </h3>
                <a href="{{ route('tramiteadmin.tipos-tramite.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-hand-index me-1"></i> Gestionar tipos de trámites
                </a>
            </div>
        </div>
        <div class="card-body">
            {{-- Filtros --}}
            <form method="GET" action="{{ route('tramiteadmin.index') }}" class="mb-4" id="filterForm">
                <div class="card bg-light border-0">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Tipo Trámite</label>
                                <select name="tipo_tramite" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($tiposTramite as $tipo)
                                        <option value="{{ $tipo->id }}" {{ request('tipo_tramite') == $tipo->id ? 'selected' : '' }}>
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Estado Trámite</label>
                                <select name="estado_tramite" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($estadosTramite as $estado)
                                        <option value="{{ $estado->id }}" {{ request('estado_tramite') == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Estado Pago</label>
                                <select name="estado_pago" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($estadosPago as $estado)
                                        <option value="{{ $estado->id }}" {{ request('estado_pago') == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" name="buscar" class="form-control" placeholder="Código, DNI, nombre..." value="{{ request('buscar') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                        <i class="bi bi-funnel me-1"></i> Filtrar
                                    </button>
                                    <a href="{{ route('tramiteadmin.index') }}" class="btn btn-secondary btn-sm" title="Limpiar filtros">
                                        <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Basado en</label>
                                <select name="fecha_tipo" class="form-select form-select-sm">
                                    <option value="solicitud" {{ request('fecha_tipo') == 'solicitud' ? 'selected' : '' }}>Fecha Solicitud</option>
                                    <option value="resolucion" {{ request('fecha_tipo') == 'resolucion' ? 'selected' : '' }}>Fecha Resolución</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Año</label>
                                <select name="anio" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($aniosDisponibles as $anio)
                                        <option value="{{ $anio }}" {{ request('anio') == $anio ? 'selected' : '' }}>
                                            {{ $anio }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Mes</label>
                                <select name="mes" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ request('mes') == $i ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($i)->locale('es')->monthName }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Fecha Desde</label>
                                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-calendar-range me-1"></i> Aplicar fecha
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle" id="tramitesTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px">ID</th>
                            <th>Código</th>
                            <th>Solicitante</th>
                            <th>Estudiante</th>
                            <th>Tipo de Trámite</th>
                            <th class="text-center">Estado Trámite</th>
                            <th class="text-center">Estado Pago</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Pagado</th>
                            <th class="text-center">Fecha Solicitud</th>
                            <th class="text-center" style="width: 60px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tramites as $tramite)
                        @php
                            $ultimoEstado = $tramite->tramiteRegistros->first();
                            $ultimoPago = $tramite->tramitePagoRegistros->first();
                        @endphp
                        <tr>
                            <td class="text-center fw-semibold">{{ $tramite->id }}</td>
                            <td>
                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                    <i class="bi bi-upc-scan me-1"></i> {{ $tramite->codigo_tramite }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">
                                        <i class="bi bi-person-circle me-1 text-muted"></i>
                                        {{ $tramite->user->apellido_paterno ?? '' }} {{ $tramite->user->apellido_materno ?? '' }}
                                    </span>
                                    <small class="text-muted">
                                        {{ $tramite->user->nombre ?? 'N/A' }}
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-card-text me-1"></i> DNI: {{ $tramite->user->dni ?? 'N/A' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">
                                        <i class="bi bi-mortarboard me-1 text-muted"></i>
                                        {{ $tramite->estudiante->user->apellido_paterno ?? 'N/A' }} {{ $tramite->estudiante->user->apellido_materno ?? 'N/A' }}
                                    </span>
                                    <small class="text-muted">
                                        {{ $tramite->estudiante->user->nombre ?? 'N/A' }}
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-card-text me-1"></i> DNI: {{ $tramite->estudiante->user->dni ?? 'N/A' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                    <i class="bi bi-tag me-1 text-primary"></i> {{ $tramite->tipoTramite->nombre ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @if($ultimoEstado && $ultimoEstado->estadoTramite)
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $ultimoEstado->estadoTramite->color ?? '#6c757d' }}; color: white;">
                                            {{ $ultimoEstado->estadoTramite->nombre }}
                                        </span>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> {{ $ultimoEstado->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    @else
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">Sin estado</span>
                                        <small class="text-muted">-</small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @if($ultimoPago && $ultimoPago->estadoPago)
                                        <span class="badge px-3 py-2 rounded-pill" style="background-color: {{ $ultimoPago->estadoPago->color ?? '#6c757d' }}; color: white;">
                                            {{ $ultimoPago->estadoPago->nombre }}
                                        </span>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> {{ $ultimoPago->fecha_registro ? \Carbon\Carbon::parse($ultimoPago->fecha_registro)->format('d/m/Y H:i') : $ultimoPago->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    @else
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">Sin pago</span>
                                        <small class="text-muted">-</small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-semibold text-success">
                                S/ {{ number_format(($tramite->tipoTramite->costo ?? 0) / 100, 2) }}
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold {{ $tramite->monto_pagado_total >= ($tramite->tipoTramite->costo ?? 0) ? 'text-success' : 'text-warning' }}">
                                    S/ {{ number_format($tramite->monto_pagado_total / 100, 2) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <small data-order="{{ $tramite->fecha_solicitud ? $tramite->fecha_solicitud->format('Y-m-d') : '' }}">
                                    <i class="bi bi-calendar3 me-1 text-muted"></i>
                                    {{ $tramite->fecha_solicitud ? $tramite->fecha_solicitud->format('d/m/Y') : 'N/A' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('tramiteadmin.show', $tramite->id) }}" class="btn btn-sm btn-info rounded-pill" title="Ver detalle">
                                    <i class="bi bi-eye me-1"></i> Ver
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tramitesTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            columnDefs: [
                { orderable: false, targets: [10] },
                { className: "text-center", targets: [0, 5, 6, 9, 10] },
                { className: "text-end", targets: [7, 8] }
            ]
        });
    });
</script>
@endsection
