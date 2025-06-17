@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Criterios de Evaluación</h1>

    <div class="mb-3">
        <a href="{{ route('temas.index', $clase_id) }}" class="btn btn-secondary mb-3">Volver</a>
        <a href="{{ route('criterios.create', ['tema_id' => $tema_id]) }}" class="btn btn-primary mb-3">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Criterio
        </a>
    </div>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Orden</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Peso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($criterios as $criterio)
                <tr>
                    <td>{{ $criterio->id }}</td>
                    <td>{{ $criterio->orden }}</td>
                    <td>{{ $criterio->descripcion }}</td>
                    <td>{{ $criterio->tipo }}</td>
                    <td>{{ $criterio->peso }}</td>
                    <td>
                        <a href="{{ route('criterios.edit', $criterio->id) }}" class="btn btn-warning btn-sm" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('criterios.destroy', $criterio->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar este criterio?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay criterios registrados para este tema.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
