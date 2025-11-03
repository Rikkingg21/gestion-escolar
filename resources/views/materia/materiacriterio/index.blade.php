@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-list-check me-2 text-primary"></i>Listado de Criterios de Evaluación
            </h2>
            @if(isset($materia))
                <h5 class="text-muted mb-0">
                    <i class="bi bi-book me-1 text-secondary"></i>
                    Materia: <span class="text-primary">{{ $materia->nombre }}</span>
                </h5>
            @endif
        </div>
        <a href="{{ route('materiacriterio.create', ['id' => $id]) }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Criterio
        </a>
    </div>

    {{-- FILTROS --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom-0">
            <h5 class="mb-0 fw-semibold text-secondary">
                <i class="bi bi-funnel me-2"></i>Filtros de búsqueda
            </h5>
        </div>
        <div class="card-body pt-0">
            <form method="GET" action="{{ route('materiacriterio.index', $id) }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="anio" class="form-label fw-semibold">Año</label>
                        <select id="anio" name="anio" class="form-select shadow-sm">
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}" {{ $selectedYear == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="grado_id" class="form-label fw-semibold">Grado</label>
                        <select id="grado_id" name="grado_id" class="form-select shadow-sm">
                            <option value="">Todos los grados</option>
                            @foreach($gradosDisponibles as $grado)
                                <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->nombreCompleto }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success shadow-sm flex-fill">
                            <i class="bi bi-funnel-fill me-1"></i> Filtrar
                        </button>
                        @if(request('anio') != date('Y') || request('grado_id'))
                            <a href="{{ route('materiacriterio.index', $id) }}" class="btn btn-outline-secondary flex-fill shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ALERTA DE ÉXITO --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- TABLA --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            @forelse($criteriosAgrupados as $competencia => $criterios)
                <table class="table align-middle mb-4 border">
                    <thead style="background-color: {{ $criterios->first()->rowColor }};">
                        <tr>
                            <th colspan="6" class="text-center fw-bold">
                                <i class="bi bi-star-fill me-2"></i>{{ $competencia }}
                            </th>
                        </tr>
                        <tr class="table-light">
                            <th>Nombre</th>
                            <th>Materia</th>
                            <th>Grado / Sección / Nivel</th>
                            <th>Año</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($criterios as $criterio)
                            <tr style="background-color: {{ $criterio->rowColor }}33;"> {{-- mismo color pero más suave --}}
                                <td>{{ $criterio->nombre }}</td>
                                <td>{{ $criterio->materia->nombre ?? 'N/A' }}</td>
                                <td>{{ $criterio->grado->nombreCompleto ?? 'N/A' }}</td>
                                <td>{{ $criterio->anio }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de criterio">
                                        <a href="{{ route('materiacriterio.edit', $criterio->id) }}"
                                        class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="submit" class="btn btn-outline-danger eliminar-criterio" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este criterio?')">
                                            <form action="{{ route('materiacriterio.destroy', $criterio->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <div>
                                                <i class="bi bi-trash"></i>
                                            </div>
                                        </button>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-info-circle me-2"></i>No hay criterios registrados
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
