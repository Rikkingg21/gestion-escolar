@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-list-check me-2"></i> Criterios de la Competencia: {{ $competencia->nombre }}
            <small class="text-muted">(Materia: {{ $competencia->materia->nombre }})</small>
        </h1>
        <div>
            <a href="{{ route('materiacompetencia.index', $competencia->materia_id) }}" class="btn btn-secondary btn-sm me-2">
                <i class="bi bi-arrow-left me-1"></i> Volver a Competencias
            </a>
            <a href="{{ route('materiacriterio.create', $competencia->id) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Criterio
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
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-primary">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th width="15%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($criterios as $criterio)
                        <tr>
                            <td>{{ $loop->iteration + ($criterios->currentPage() - 1) * $criterios->perPage() }}</td>
                            <td>{{ $criterio->nombre }}</td>
                            <td>{{ Str::limit($criterio->descripcion, 60) }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('materiacriterio.edit', $criterio->id) }}"
                                       class="btn btn-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('materiacriterio.destroy', $criterio->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger"
                                                title="Eliminar" onclick="return confirm('¿Eliminar este criterio?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No hay criterios registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="showing-results text-muted">
                        Mostrando {{ $criterios->firstItem() ?? 0 }} a {{ $criterios->lastItem() ?? 0 }} de {{ $criterios->total() }} criterios
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="float-end">
                        {{ $criterios->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
