@extends('layouts.app')
@section('title', 'Pensiones - Administración')
@section('content')

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Pensiones - Administración</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('pensiones-admin.registrar-pago') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Registrar Pago
            </a>
            <a href="{{ route('pensiones-admin.configuracion.create', $periodoSeleccionado ? ['periodo_id' => $periodoSeleccionado->id] : []) }}" class="btn btn-primary">
                <i class="bi bi-gear me-1"></i> Nueva Configuración
            </a>
            <a href="{{ route('pensiones-admin.pagos-pendientes') }}" class="btn btn-warning">
                <i class="bi bi-hourglass-split me-1"></i> Pagos por revisar
                @if($pagosEnRevision > 0)
                    <span class="badge bg-danger ms-1">{{ $pagosEnRevision }}</span>
                @endif
            </a>
        </div>
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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Selector de periodo --}}
    <form method="GET" action="{{ route('pensiones-admin.index') }}" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Periodo académico</label>
                <select name="periodo_id" class="form-select" onchange="this.form.submit()">
                    @foreach($periodos as $periodo)
                        <option value="{{ $periodo->id }}" @selected($periodoSeleccionado && $periodo->id == $periodoSeleccionado->id)>
                            {{ $periodo->nombre }} ({{ $periodo->anio }}) {{ $periodo->estado == '1' ? '- Activo' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- Tarjetas de estadísticas --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="bi bi-list-check text-primary fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Total cuotas</h6>
                        <h4 class="mb-0 fw-bold">{{ $totalCuotas }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="bi bi-check-circle-fill text-success fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Pagadas</h6>
                        <h4 class="mb-0 fw-bold">{{ $totalPagadas }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="bi bi-hourglass-split text-warning fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Pendientes</h6>
                        <h4 class="mb-0 fw-bold">{{ $totalPendientes }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Atrasadas</h6>
                        <h4 class="mb-0 fw-bold">{{ $totalAtrasadas }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Recaudado</h6>
                </div>
                <div class="card-body">
                    <h3 class="mb-0 fw-bold text-success">S/ {{ number_format($recaudado / 100, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i> Por cobrar</h6>
                </div>
                <div class="card-body">
                    <h3 class="mb-0 fw-bold text-danger">S/ {{ number_format($porCobrar / 100, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de configuraciones --}}
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="bi bi-gear me-2"></i> Configuraciones de pensión por grado</h6>
        </div>
        <div class="card-body">
            @if($configs->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-4 d-block mb-3 text-muted opacity-50"></i>
                    <p class="mb-0">No hay configuraciones para este periodo.</p>
                    <a href="{{ route('pensiones-admin.configuracion.create', $periodoSeleccionado ? ['periodo_id' => $periodoSeleccionado->id] : []) }}" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-1"></i> Crear configuración
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Grado</th>
                                <th>Periodo</th>
                                <th>N° cuotas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($configs as $config)
                                <tr>
                                    <td>
                                        <strong>{{ $config->grado->nombre_completo }}</strong>
                                    </td>
                                    <td>{{ $config->periodo->nombre }} ({{ $config->periodo->anio }})</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $config->cuotas->count() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('pensiones-admin.cuotas', ['periodo_id' => $config->periodo_id, 'grado_id' => $config->grado_id]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-list-ul"></i> Ver cuotas
                                        </a>
                                        <a href="{{ route('pensiones-admin.configuracion.edit', $config->id) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('pensiones-admin.configuracion.destroy', $config->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar esta configuración? Se eliminarán las cuotas pendientes generadas.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection