@extends('layouts.app')
@section('title', 'Administración de Trámites')
@section('content')

<div class="container-fluid">
    {{-- Tarjetas de estadísticas --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalTramites }}</h3>
                    <p>Total Trámites</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalPendientes }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalEnProceso }}</h3>
                    <p>En Proceso</p>
                </div>
                <div class="icon">
                    <i class="fas fa-spinner"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalCompletados }}</h3>
                    <p>Completados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Segunda fila de estadísticas - Pagos --}}
    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $totalPagosPendientes }}</h3>
                    <p>Pagos Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalPagosAprobados }}</h3>
                    <p>Pagos Aprobados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $totalPagosRechazados }}</h3>
                    <p>Pagos Rechazados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de trámites --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list me-2"></i>
                Listado de Trámites
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            {{-- Filtros --}}
            <form method="GET" action="{{ route('tramiteadmin.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar por código, DNI, nombre..." value="{{ request('buscar') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="tipo_tramite" class="form-select">
                            <option value="">Todos los tipos</option>
                            @foreach($tiposTramite as $tipo)
                                <option value="{{ $tipo->id }}" {{ request('tipo_tramite') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="estado_tramite" class="form-select">
                            <option value="">Todos los estados</option>
                            @foreach($estadosTramite as $estado)
                                <option value="{{ $estado->id }}" {{ request('estado_tramite') == $estado->id ? 'selected' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="estado_pago" class="form-select">
                            <option value="">Todos los pagos</option>
                            @foreach($estadosPago as $estado)
                                <option value="{{ $estado->id }}" {{ request('estado_pago') == $estado->id ? 'selected' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="{{ route('tramiteadmin.index') }}" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Solicitante</th>
                            <th>Estudiante</th>
                            <th>Tipo de Trámite</th>
                            <th>Estado Trámite</th>
                            <th>Estado Pago</th>
                            <th>Monto</th>
                            <th>Pagado</th>
                            <th>Fecha Solicitud</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tramites as $tramite)
                        <tr>
                            <td>{{ $tramite->id }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $tramite->codigo_tramite }}</span>
                            </td>
                            <td>
                                {{ $tramite->user->nombre ?? 'N/A' }} {{ $tramite->user->apellido_paterno ?? '' }}<br>
                                <small class="text-muted">DNI: {{ $tramite->user->dni ?? 'N/A' }}</small>
                            </td>
                            <td>
                                {{ $tramite->estudiante->user->nombre ?? 'N/A' }}<br>
                                <small class="text-muted">DNI: {{ $tramite->estudiante->user->dni ?? 'N/A' }}</small>
                            </td>
                            <td>{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</td>
                            <td>
                                @php
                                    $ultimoEstado = $tramite->tramiteRegistros->first();
                                @endphp
                                @if($ultimoEstado && $ultimoEstado->estadoTramite)
                                    <span class="badge" style="background-color: {{ $ultimoEstado->estadoTramite->color ?? '#6c757d' }}">
                                        {{ $ultimoEstado->estadoTramite->nombre }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Sin estado</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $ultimoPago = $tramite->tramitePagoRegistros->first();
                                @endphp
                                @if($ultimoPago && $ultimoPago->estadoPago)
                                    <span class="badge" style="background-color: {{ $ultimoPago->estadoPago->color ?? '#6c757d' }}">
                                        {{ $ultimoPago->estadoPago->nombre }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Sin pago</span>
                                @endif
                            </td>
                            <td>S/ {{ number_format($tramite->tipoTramite->costo ?? 0, 2) }}</td>
                            <td>S/ {{ number_format($tramite->monto_pagado, 2) }}</td>
                            <td>{{ $tramite->fecha_solicitud ? $tramite->fecha_solicitud->format('d/m/Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('tramiteadmin.show', $tramite->id) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">
                                <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                No hay trámites registrados
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $tramites->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
