@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-layers me-2"></i> Gestión de Materias
        </h1>
        <a href="{{ route('materia.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Materia
        </a>
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

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <ul class="nav nav-tabs mb-3" id="materiaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="activos-tab" data-bs-toggle="tab" href="#activos" aria-selected="true" role="tab">Activos</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="inactivos-tab" data-bs-toggle="tab" href="#inactivos" aria-selected="false" role="tab">Inactivos</a>
        </li>
    </ul>
    <div class="tab-content" id="materiaTabsContent">
        {{-- Tab Activos --}}
        <div class="tab-pane fade show active" id="activos" role="tabpanel" aria-labelledby="activos-tab">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    Materias Activas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Nombre</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($materiasActivas as $materia)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $materia->nombre}}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('materiacompetencia.index', $materia->id) }}" class="btn btn-sm btn-primary mx-1" title="Criterios">
                                                <i class="bi bi-clipboard2"></i>
                                            </a>
                                            <a href="{{ route('materia.edit', $materia->id) }}" class="btn btn-sm btn-warning mx-1" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('materia.destroy', $materia->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger mx-1" title="Eliminar" onclick="return confirm('¿Eliminar esta materia?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>

                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">No hay materias activos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="showing-results text-muted">
                                    Mostrando {{ $materiasActivas->firstItem() ?? 0 }} a {{ $materiasActivas->lastItem() ?? 0 }} de {{ $materiasActivas->total()}} materias
                            </div>
                            <div>
                                {{ $materiasActivas->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--ahora para materias inactivas-->
        <div class="tab-pane fade" id="inactivos" role="tabpanel" aria-labelledby="inactivos-tab">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    Materias Inactivas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Nombre</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($materiasInactivas as $materia)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $materia->nombre}}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('materia.edit', $materia->id) }}" class="btn btn-sm btn-warning mx-1" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('materia.destroy', $materia->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar esta materia?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">No hay materias activos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="showing-results text-muted">
                                    Mostrando {{ $materiasInactivas->firstItem() ?? 0 }} a {{ $materiasInactivas->lastItem() ?? 0 }} de {{ $materiasInactivas->total()}} materias
                            </div>
                            <div>
                                {{ $materiasInactivas->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
