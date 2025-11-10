@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('materia.index') }}" class="text-muted text-decoration-none small fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Volver a Materias
        </a>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 border-bottom pb-3">
        <h1 class="h2 mb-3 mb-md-0 text-primary fw-bold">
            <i class="bi bi-list-check me-3"></i> Gestión de Competencias
        </h1>

        <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
            <a href="{{ route('materiacompetencia.importar') }}" class="btn btn-success shadow-sm text-white rounded-3">
                <i class="bi bi-file-earmark-excel me-2"></i> Importar Excel
            </a>
            <a href="" class="btn btn-primary rounded-3 fw-bold">
                <i class="bi bi-plus-lg me-2"></i> Nueva Competencia
            </a>
            <a href="{{ route('materiacriterio.index') }}" class="btn btn-primary rounded-3 fw-bold">
                <i class="bi bi-plus-lg me-2"></i> Ver Criterios
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- FILTROS --}}
    <div class="card shadow mb-4">
        <div class="card-header bg-white border-bottom-0">
            <h5 class="mb-0 fw-semibold text-secondary">
                <i class="bi bi-funnel me-2"></i>Filtros de búsqueda
            </h5>
        </div>
        <div class="card-body pt-0">
            <form method="GET" action="{{ route('materiacompetencia.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="materia_id" class="form-label fw-semibold">Materia</label>
                        <select id="materia_id" name="materia_id" class="form-select shadow-sm">
                            <option value="">Todas las materias</option>
                            @foreach($materias as $materia)
                                <option value="{{ $materia->id }}" {{ request('materia_id') == $materia->id ? 'selected' : '' }}>
                                    {{ $materia->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="estado" class="form-label fw-semibold">Estado</label>
                        <select id="estado" name="estado" class="form-select shadow-sm">
                            <option value="activas" {{ request('estado', 'activas') == 'activas' ? 'selected' : '' }}>Competencias Activas</option>
                            <option value="inactivas" {{ request('estado') == 'inactivas' ? 'selected' : '' }}>Competencias Inactivas</option>
                            <option value="todas" {{ request('estado') == 'todas' ? 'selected' : '' }}>Todas las Competencias</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success shadow-sm flex-fill">
                            <i class="bi bi-funnel-fill me-1"></i> Filtrar
                        </button>
                        @if(request('materia_id') || request('estado') != 'activas')
                            <a href="{{ route('materiacompetencia.index') }}" class="btn btn-outline-secondary flex-fill shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABS --}}
    <ul class="nav nav-tabs mb-3" id="competenciaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ request('estado', 'activas') == 'activas' ? 'active' : '' }}"
               id="activas-tab"
               href="{{ route('materiacompetencia.index', array_merge(request()->all(), ['estado' => 'activas'])) }}"
               role="tab">
                Competencias Activas
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ request('estado') == 'inactivas' ? 'active' : '' }}"
               id="inactivas-tab"
               href="{{ route('materiacompetencia.index', array_merge(request()->all(), ['estado' => 'inactivas'])) }}"
               role="tab">
                Competencias Inactivas
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ request('estado') == 'todas' ? 'active' : '' }}"
               id="todas-tab"
               href="{{ route('materiacompetencia.index', array_merge(request()->all(), ['estado' => 'todas'])) }}"
               role="tab">
                Todas las Competencias
            </a>
        </li>
    </ul>

    {{-- TABLA --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Materia</th>
                            <th>Estado</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($competencias as $competencia)
                        <tr>
                            <td>{{ $loop->iteration + ($competencias->currentPage() - 1) * $competencias->perPage() }}</td>
                            <td>{{ $competencia->nombre }}</td>
                            <td>{{ $competencia->descripcion ?? 'Sin descripción' }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $competencia->materia->nombre ?? 'N/A' }}</span>
                            </td>
                            <td>
                                @if($competencia->estado)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex">
                                    <a href="{{ route('materiacompetencia.edit', $competencia->id) }}"
                                    class="btn btn-sm btn-warning mx-1" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('materiacompetencia.destroy', $competencia->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger mx-1"
                                                title="Eliminar" onclick="return confirm('¿Eliminar esta competencia?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                @if(request('estado') == 'inactivas')
                                    No hay competencias inactivas
                                @elseif(request('estado') == 'todas')
                                    No hay competencias registradas
                                @else
                                    No hay competencias activas
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="showing-results text-muted">
                        Mostrando {{ $competencias->firstItem() ?? 0 }} a {{ $competencias->lastItem() ?? 0 }} de {{ $competencias->total() }} competencias
                    </div>
                    <div>
                        {{ $competencias->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-tabs .nav-link {
    color: #6c757d;
    font-weight: 500;
}
.nav-tabs .nav-link.active {
    color: #0d6efd;
    font-weight: 600;
    border-bottom: 3px solid #0d6efd;
}
</style>
@endsection
