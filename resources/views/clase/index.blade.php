@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Clases de la semana</h1>
    <a href="{{ route('semanas.index', $unidad_id) }}" class="btn btn-secondary mb-3">Volver</a>
    <a href="{{ route('clases.create', ['semana_id' => $semana_id]) }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus-lg me-2"></i> Nueva Clase
    </a>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Descripcion</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clases as $clase)
                <tr>
                    <td>{{ $clase->id }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($clase->fecha_clase)->format('d-m-Y') }}
                    </td>
                    <td>{{ $clase->descripcion }}</td>

                    <td>
                        <a href="{{ route('temas.index', $clase->id) }}" class="btn btn-info btn-sm" title="Ver"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('clases.edit', $clase->id) }}" class="btn btn-warning btn-sm" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('clases.destroy', $clase->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar esta clase?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hay clases registradas para esta semana.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>

</script>
@endsection
