@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Lista de Estudiantes</h1>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>DNI</th>
                <th>Grado/Sección/Nivel</th>
                <th>Apoderado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantes as $estudiante)
                <tr>
                    <td>{{ $estudiante->id }}</td>
                    <td>{{ $estudiante->user->nombre }} {{ $estudiante->user->apellido_paterno }}</td>
                    <td>{{ $estudiante->user->email }}</td>
                    <td>{{ $estudiante->user->dni }}</td>
                    <td>
                        @if($estudiante->grado)
                            {{ ucfirst($estudiante->grado->nivel) }} - {{ $estudiante->grado->grado }}° "{{ $estudiante->grado->seccion }}"
                        @else
                            Sin grado asignado
                        @endif
                    </td>
                    <td>
                        @if($estudiante->apoderado)
                            {{ $estudiante->apoderado->user->nombreCompleto ?? $estudiante->apoderado->user->nombre }}
                            ({{ $estudiante->apoderado->parentesco ?? 'Sin parentesco' }})
                        @else
                            Sin apoderado
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('estudiantes.edit', $estudiante->id) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('estudiantes.destroy', $estudiante->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este estudiante?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
