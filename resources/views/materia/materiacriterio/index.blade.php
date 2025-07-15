@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h2>Listado de Criterios de Evaluación</h2>
            @if(isset($materia))
                <h4>Materia: {{ $materia->nombre }}</h4>
            @endif
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('materiacriterio.create', ['id' => $id]) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Criterio
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('materiacriterio.index', $id) }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="anio" class="form-label">Año</label>
                        <select id="anio" name="anio" class="form-select">
                            @foreach($anios as $anio)
                                <option value="{{ $anio }}" {{ $selectedYear == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="grado_id" class="form-label">Grado</label>
                        <select id="grado_id" name="grado_id" class="form-select">
                            <option value="">Todos los grados</option>
                            @foreach($gradosDisponibles as $grado)
                                <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->nombreCompleto }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        @if(request('anio') != date('Y') || request('grado_id'))
                            <a href="{{ route('materiacriterio.index', $id) }}" class="btn btn-outline-danger">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Materia</th>
                    <th>Grado/Sección/Nivel</th>
                    <th>Competencia</th>
                    <th>Año</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materiaCriterios as $criterio)
                    <tr>
                        <td>{{ $criterio->nombre }}</td>
                        <td>{{ $criterio->materia->nombre ?? 'N/A' }}</td>
                        <td>{{ $criterio->grado->nombreCompleto ?? 'N/A' }}</td>
                        <td>{{ $criterio->materiaCompetencia->nombre ?? 'N/A' }}</td>
                        <td>{{ $criterio->anio }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('materiacriterio.edit', $criterio->id) }}"
                                   class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('materiacriterio.destroy', $criterio->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            title="Eliminar" onclick="return confirm('¿Estás seguro?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay criterios registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
