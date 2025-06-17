@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Bimestres del Curso</h1>
    <a href="{{ route('mayas.index') }}" class="btn btn-secondary mb-3">Volver</a>
    <a href="{{ route('bimestres.create', ['curso_grado_sec_niv_anio_id' => $curso_grado_sec_niv_anio_id]) }}"class="btn btn-primary mb-3">
        <i class="bi bi-plus-lg me-2"></i> Nuevo Bimestre
    </a>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Bimestre</th>
                <th>Curso/Grado/Sec/Niv/Año</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bimestres as $bimestre)
                <tr>
                    <td>{{ $bimestre->id }}</td>
                    <td>
                        @switch($bimestre->nombre)
                            @case(1)
                                Bimestre 1
                                @break
                            @case(2)
                                Bimestre 2
                                @break
                            @case(3)
                                Bimestre 3
                                @break
                            @case(4)
                                Bimestre 4
                                @break
                            @default
                                Desconocido
                        @endswitch
                    </td>
                    <td>
                        {{ $bimestre->cursoGradoSecNivAnio->materia->nombre ?? '' }} |
                        {{ $bimestre->cursoGradoSecNivAnio->grado->grado ?? '' }} -
                        {{ $bimestre->cursoGradoSecNivAnio->grado->seccion ?? '' }} -
                        {{ $bimestre->cursoGradoSecNivAnio->grado->nivel ?? '' }} |
                        {{ $bimestre->cursoGradoSecNivAnio->anio ?? '' }}
                    </td>
                    <td>
                        <!-- Aquí puedes agregar botones de acciones, por ejemplo: -->
                        <a href="{{ route('unidades.index', $bimestre->id) }}" class="btn btn-info btn-sm" title="Ver">Ver</a>
                        <a href="{{ route('bimestres.edit', $bimestre->id) }}" class="btn btn-warning btn-sm" title="Editar">Editar</a>
                        <form action="{{ route('bimestres.destroy', $bimestre->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar este bimestre?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No hay bimestres registrados para este curso.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
