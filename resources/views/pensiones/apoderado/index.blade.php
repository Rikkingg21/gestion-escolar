@extends('layouts.app')
@section('title', 'Mis Pensiones')
@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Mis Pensiones</h4>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i> Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('pensiones.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Periodo</label>
                        <select name="periodo_id" class="form-select">
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}" @selected((string) $periodoId === (string) $periodo->id)>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" @selected(request('estado') == 'pendiente')>Pendiente</option>
                            <option value="atrasado" @selected(request('estado') == 'atrasado')>Atrasado</option>
                            <option value="pagado" @selected(request('estado') == 'pagado')>Pagado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                        <a href="{{ route('pensiones.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($pensiones->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox display-4 d-block mb-3 text-muted opacity-50"></i>
                <p class="mb-0">No tienes pensiones registradas para este periodo.</p>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i> Cuotas de pensión</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Grado</th>
                                <th>Concepto</th>
                                <th>Vencimiento</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pensiones as $pension)
                                <tr class="{{ $pension->atrasada ? 'table-danger' : '' }}">
                                    <td>
                                        <strong>{{ $pension->matricula->estudiante->user->nombre_completo }}</strong>
                                    </td>
                                    <td>{{ $pension->matricula->grado->nombre_completo }}</td>
                                    <td>
                                        {{ $pension->concepto }}
                                        @if($pension->mes)
                                            <span class="badge bg-light text-dark border ms-1">{{ $pension->mes }}/{{ $pension->anio }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $pension->fecha_vencimiento_formateada }}</td>
                                    <td>{{ $pension->monto_formateado }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $pension->estado_efectivo_color }}">
                                            {{ $pension->estado_efectivo_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('pensiones.show', $pension->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Ver / Pagar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

@endsection