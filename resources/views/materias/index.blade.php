@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-people-fill"></i> Administración de Materias
        </h1>

        <a href="{{ route('materias.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nueva Materia
        </a>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="materiasTable" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materias as $materia)
                        <tr>
                            <td>{{ $materia->nombre }}</td>
                            <td>
                                <div class="d-flex">
                                    <a href="{{ route('materias.edit', $materia->id) }}"
                                       class="btn btn-sm btn-warning mx-1"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('materias.destroy', $materia->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Eliminar"
                                                onclick="return confirm('¿Eliminar esta materia?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="showing-results text-muted">
                        Mostrando {{ $materias->firstItem() }} a {{ $materias->lastItem() }} de {{ $materias->total() }} resultados
                    </div>
                    <div>
                        {{ $materias->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
