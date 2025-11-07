@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('materia.index') }}" class="text-muted text-decoration-none small fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Volver a Materias
        </a>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 border-bottom pb-3">
        <h1 class="h2 mb-3 mb-md-0 text-primary fw-bold">
            <i class="bi bi-list-check me-3"></i> Competencias de la Materia: <span class="text-secondary">{{ $materia->nombre }}</span>
        </h1>

        <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
            <a href="{{ route('materiacriterio.index', $materia->id) }}"
                class="btn btn-outline-secondary shadow-sm rounded-3">
                <i class="bi bi-card-checklist me-2"></i> Ver Criterios
            </a>
            <a href="{{ route('materiacompetencia.importar') }}" class="btn btn-info shadow-sm text-white rounded-3">
                <i class="bi bi-file-earmark-excel me-2"></i> Importar
            </a>
            <a href="{{ route('materiacompetencia.create', $materia->id) }}" class="btn btn-success shadow-lg rounded-3 fw-bold">
                <i class="bi bi-plus-lg me-2"></i> Nueva Competencia
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

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                @php
                    $activas = $competencias->where('estado', 1);
                    $inactivas = $competencias->where('estado', 0);
                @endphp

                <h4 class="mt-4">Competencias Activas</h4>
                <table class="table table-bordered table-hover">
                    <thead class="table-success">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activas as $competencia)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $competencia->nombre }}</td>
                            <td>{{ $competencia->descripcion }}</td>
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
                            <td colspan="4" class="text-center">No hay competencias activas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <h4 class="mt-4">Competencias Inactivas</h4>
                <table class="table table-bordered table-hover">
                    <thead class="table-secondary">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inactivas as $competencia)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $competencia->nombre }}</td>
                            <td>{{ $competencia->descripcion }}</td>
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
                            <td colspan="4" class="text-center">No hay competencias inactivas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="showing-results text-muted">
                    Mostrando {{ $competencias->firstItem() ?? 0 }} a {{ $competencias->lastItem() ?? 0 }} de {{ $competencias->total() }} competencias
                </div>
                <div>
                    {{ $competencias->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
