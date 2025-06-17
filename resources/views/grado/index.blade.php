@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-layers me-2"></i> Gestión de Grados
        </h1>

        <a href="{{ route('grados.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Grado
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th>Grado</th>
                            <th>Sección</th>
                            <th>Nivel</th>
                            <th>Nombre Completo</th>
                            <th width="15%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grados as $grado)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $grado->grado }}°</td>
                            <td>{{ $grado->seccion }}</td>
                            <td>{{ $grado->nivel }}</td>
                            <td>{{ $grado->nombreCompleto }}</td>
                            <td>
                                <div class="d-flex">
                                    <a href="{{ route('grados.edit', $grado->id) }}"
                                       class="btn btn-sm btn-warning mx-1"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('grados.destroy', $grado->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Eliminar"
                                                onclick="return confirm('¿Eliminar este grado?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay grados registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="showing-results text-muted">
                        Mostrando {{ $grados->firstItem() }} a {{ $grados->lastItem() }} de {{ $grados->total() }} grados
                    </div>
                    <div>
                        {{ $grados->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
