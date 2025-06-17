@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Semanarios de la Unidad</h1>
    <a href="{{ route('unidades.index', ['bimestre_id' => $bimestre_id]) }}" class="btn btn-secondary mb-3">Volver</a>
    <a href="{{ route('semanas.create', ['unidad_id' => $unidad_id]) }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus-lg me-2"></i> Nueva Semana
    </a>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Semana</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($semanas as $semana)
                <tr>
                    <td>{{ $semana->id }}</td>
                    <td>Semana {{ $semana->nombre }}</td>
                    <td>
                        <a href="{{ route('clases.index', $semana->id) }}" class="btn btn-info btn-sm" title="Ver"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('semanas.edit', $semana->id) }}" class="btn btn-warning btn-sm" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('semanas.destroy', $semana->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar esta semana?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No hay semanas registradas para esta unidad.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
