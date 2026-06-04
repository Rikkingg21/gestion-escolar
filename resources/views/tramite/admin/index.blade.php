@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">
                <i class="fas fa-tasks me-2"></i>Panel de Administración de Trámites
            </h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Tipos de Trámites</h5>
                            <h2 class="mb-0">{{ $totalTipos }}</h2>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('tramiteadmin.tipos-tramite.index') }}" class="text-white text-decoration-none">Gestionar <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Trámites -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>Lista de Trámites
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" action="{{ route('tramiteadmin.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Estado de Trámite</label>
                                <select name="estado_tramite_id" class="form-select">
                                    <option value="">Todos</option>
                                    @foreach($estadosTramite as $estado)
                                        <option value="{{ $estado->id }}" {{ request('estado_tramite_id') == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado de Pago</label>
                                <select name="estado_pago_id" class="form-select">
                                    <option value="">Todos</option>
                                    @foreach($estadosPago as $estado)
                                        <option value="{{ $estado->id }}" {{ request('estado_pago_id') == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100 me-2">
                                    <i class="fas fa-search me-1"></i> Filtrar
                                </button>
                                <a href="{{ route('tramiteadmin.index') }}" class="btn btn-secondary w-100">
                                    <i class="fas fa-eraser me-1"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th style="width: 10%;">Código</th>
                                    <th style="width: 15%;">Solicitante</th>
                                    <th style="width: 20%;">Tipo</th>
                                    <th style="width: 10%;">Monto</th>
                                    <th style="width: 10%;">Estado Trámite</th>
                                    <th style="width: 10%;">Estado Pago</th>
                                    <th style="width: 10%;">Fecha</th>
                                    <th style="width: 10%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tramites as $tramite)
                                <tr>
                                    <td class="text-center fw-bold">{{ $tramite->codigo_tramite }}</td>
                                    <td>{{ $tramite->user->name ?? 'N/A' }}</td>
                                    <td>{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</td>
                                    <td class="text-end">S/ {{ number_format($tramite->monto_pagado ?? 0, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge" style="background-color: {{ $tramite->estadoTramite->color ?? '#6c757d' }}">
                                            {{ $tramite->estadoTramite->nombre ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background-color: {{ $tramite->estadoPago->color ?? '#6c757d' }}">
                                            {{ $tramite->estadoPago->nombre ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $tramite->created_at->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('tramiteadmin.tramites.show', $tramite->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No hay trámites registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $tramites->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
