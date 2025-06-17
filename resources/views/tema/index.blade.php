@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Temas de clases</h1>
    <div class="mb-3">
        <a href="{{ route('clases.index', $semana_id,) }}" class="btn btn-secondary mb-3">Volver</a>
        <a href="{{ route('temas.create', ['clase_id' => $clase_id]) }}" class="btn btn-primary mb-3">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Tema
        </a>

    </div>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Orden</th>
                <th>Nombre del Tema</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($temas as $tema)
                <tr>
                    <td>{{ $tema->id }}</td>
                    <td>{{ $tema->orden}}</td>
                    <td>{{ $tema->nombre }}</td>
                    <td>{{ $tema->descripcion }}</td>
                    <td>
                        <a href="{{ route('criterios.index', $tema->id) }}" class="btn btn-info btn-sm" title="Ver Detalles">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('temas.edit', $tema->id) }}" class="btn btn-warning btn-sm" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('temas.destroy', $tema->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar este tema?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hay temas registrados para esta clase.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
@endsection
