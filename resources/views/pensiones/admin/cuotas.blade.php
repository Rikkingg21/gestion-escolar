@extends('layouts.app')
@section('title', 'Cuotas de Pensión')
@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-list-check me-2"></i> Cuotas de Pensión</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('pensiones-admin.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i> Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('pensiones-admin.cuotas') }}">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Periodo</label>
                        <select name="periodo_id" class="form-select">
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}" @selected((string) $periodoId === (string) $periodo->id)>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Grado</label>
                        <select name="grado_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}" @selected(request('grado_id') == $grado->id)>
                                    {{ $grado->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-select">
                            <option value="">Todos</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(request('mes') == $m)>{{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" @selected(request('estado') == 'pendiente')>Pendiente</option>
                            <option value="atrasado" @selected(request('estado') == 'atrasado')>Atrasado</option>
                            <option value="pagado" @selected(request('estado') == 'pagado')>Pagado</option>
                            <option value="anulado" @selected(request('estado') == 'anulado')>Anulado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Buscar (DNI / nombre)</label>
                        <input type="text" name="buscar" class="form-control" value="{{ request('buscar') }}"
                               placeholder="Ej: 12345678 o Juan">
                    </div>
                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                        <a href="{{ route('pensiones-admin.cuotas') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-success text-white py-2">
            <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i> Cuotas ({{ $pensiones->total() }})</h6>
        </div>
        <div class="card-body">
            @if($pensiones->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-4 d-block mb-3 text-muted opacity-50"></i>
                    <p class="mb-0">No hay cuotas que coincidan con los filtros.</p>
                </div>
            @else
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
                                <tr>
                                    <td>
                                        <strong>{{ $pension->matricula->estudiante->user->nombre }} {{ $pension->matricula->estudiante->user->apellido_paterno }}</strong>
                                        <div class="text-muted small">{{ $pension->matricula->estudiante->user->dni }}</div>
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
                                        <a href="{{ route('pensiones-admin.pensiones.show', $pension->id) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $pensiones->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection