@extends('layouts.app')
@section('title', 'Pagos de Pensión por Revisar')
@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-hourglass-split me-2"></i> Pagos por Revisar</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('pensiones-admin.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
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

    @php
        $aprobadoId = $estadosPago->first(fn($e) => str_contains(strtolower($e->nombre), 'aprobado'))?->id;
        $rechazadoId = $estadosPago->first(fn($e) => str_contains(strtolower($e->nombre), 'rechazado'))?->id;
    @endphp

    <div class="card shadow-sm">
        <div class="card-header bg-warning text-white py-2">
            <h6 class="mb-0"><i class="bi bi-list-check me-2"></i> Comprobantes en revisión ({{ $registros->count() }})</h6>
        </div>
        <div class="card-body">
            @if($registros->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-check2-circle display-4 d-block mb-3 text-muted opacity-50"></i>
                    <p class="mb-0">No hay pagos pendientes de revisión.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Cuota</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>N° Operación</th>
                                <th>Comprobante</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registros as $registro)
                                <tr>
                                    <td>
                                        <strong>{{ $registro->pension->matricula->estudiante->user->nombre }} {{ $registro->pension->matricula->estudiante->user->apellido_paterno }}</strong>
                                        <div class="text-muted small">{{ $registro->pension->matricula->estudiante->user->dni }}</div>
                                        <div class="text-muted small">{{ $registro->pension->matricula->grado->nombre_completo }}</div>
                                    </td>
                                    <td>
                                        {{ $registro->pension->concepto }}
                                        <div class="text-muted small">Vence: {{ $registro->pension->fecha_vencimiento_formateada }}</div>
                                    </td>
                                    <td>{{ $registro->monto_formateado }}</td>
                                    <td>{{ $registro->pago?->metodoPago?->nombre ?? 'N/A' }}</td>
                                    <td>{{ $registro->pago?->numero_operacion ?? 'N/A' }}</td>
                                    <td>
                                        @if($registro->pago && $registro->pago->comprobante_path)
                                            <a href="{{ route('pensiones-admin.ver-comprobante', $registro->pago->id) }}" target="_blank"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        @else
                                            <span class="badge bg-light text-dark border">Efectivo</span>
                                        @endif
                                    </td>
                                    <td>{{ $registro->fecha_registro_formateada }}</td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            @if($aprobadoId)
                                                <form method="POST" action="{{ route('pensiones-admin.update-estado-pago', $registro->pension_id) }}">
                                                    @csrf
                                                    <input type="hidden" name="pago_registro_id" value="{{ $registro->id }}">
                                                    <input type="hidden" name="estado_pago_id" value="{{ $aprobadoId }}">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-lg"></i> Aprobar
                                                    </button>
                                                </form>
                                            @endif
                                            @if($rechazadoId)
                                                <form method="POST" action="{{ route('pensiones-admin.update-estado-pago', $registro->pension_id) }}">
                                                    @csrf
                                                    <input type="hidden" name="pago_registro_id" value="{{ $registro->id }}">
                                                    <input type="hidden" name="estado_pago_id" value="{{ $rechazadoId }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('¿Rechazar este pago?')">
                                                        <i class="bi bi-x-lg"></i> Rechazar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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