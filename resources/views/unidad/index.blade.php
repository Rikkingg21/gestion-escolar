@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Unidades del Bimestre</h1>
    <a href="{{ route('bimestres.index', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id]) }}" class="btn btn-secondary mb-3">Volver</a>
    <a href="{{ route('unidades.create', ['bimestre_id' => $bimestre_id]) }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus-lg me-2"></i> Nueva Unidad
    </a>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre de la Unidad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unidades as $unidad)
                <tr>
                    <td>{{ $unidad->id }}</td>
                    <td>
                        @switch($unidad->nombre)
                            @case(1)
                                Unidad 1
                                @break
                            @case(2)
                                Unidad 2
                                @break
                            @case(3)
                                Unidad 3
                                @break
                            @case(4)
                                Unidad 4
                                @break
                            @case(5)
                                Unidad 5
                                @break
                            @case(6)
                                Unidad 6
                                @break
                            @case(7)
                                Unidad 7
                                @break
                            @case(8)
                                Unidad 8
                                @break
                            @default
                                Desconocida
                        @endswitch
                    </td>
                    <td>
                        <a href="{{ route('semanas.index', $unidad->id) }}" class="btn btn-info btn-sm" title="Ver"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('unidades.edit', $unidad->id) }}" class="btn btn-warning btn-sm" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('unidades.destroy', $unidad->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar esta unidad?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No hay unidades registradas para este bimestre.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
