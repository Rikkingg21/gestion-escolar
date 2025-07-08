@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-list-check me-2"></i> Competencias de la Materia: {{ $materia->nombre }}
        </h1>
        <a href="{{ route('materiacompetencia.create', $materia->id) }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nueva Competencia
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
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($competencias as $competencia)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $competencia->nombre }}</td>
                            <td>{{ $competencia->descripcion }}</td>
                            <td>
                                <div class="d-flex">
                                    <a href="{{ route('materiacriterio.index', $competencia->id) }}"
                                       class="btn btn-sm btn-info mx-1" title="Ver Criterios">
                                        <i class="bi bi-list-check"></i>
                                    </a>
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
                            <td colspan="4" class="text-center">No hay competencias registradas</td>
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
