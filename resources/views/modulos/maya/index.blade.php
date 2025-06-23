@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-people-fill"></i> Administración de Mayas
        </h1>

        <a href="{{ route('maya.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Maya
        </a>
    </div>
    <div>
        <h2>Año de la maya:</h2>
            <select name="anio" id="anio-select" class="form-select">
                @foreach($anios as $anio)
                    <option value="{{ $anio }}" {{ $anio == $anioSeleccionado ? 'selected' : '' }}>{{ $anio }}</option>
                @endforeach
            </select>
    </div><br>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="materiasTable" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th>id</th>
                            <th>Docente Asignado</th>
                            <th>Materia</th>
                            <th>Grado</th>
                            <th>Año</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mayas as $maya)
                        <tr>
                            <td>{{ $maya->id }}</td>
                            <td>
                                {{ $maya->docente && $maya->docente->user ? $maya->docente->user->nombre . ' ' . $maya->docente->user->apellido_paterno . ' ' . $maya->docente->user->apellido_materno : 'Sin docente' }}
                            </td>
                            <td>
                                {{ $maya->materia->nombre ?? '' }}
                            </td>
                            <td>
                                {{ $maya->grado->grado ?? '' }} - {{ $maya->grado->seccion ?? '' }} - {{ $maya->grado->nivel ?? '' }}
                            </td>
                            <td>{{ $maya->anio }}</td>
                            <td>
                                <div class="d-flex">
                                    <a href=""
                                       class="btn btn-sm btn-info mx-1"
                                       title="Ver Detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('maya.edit', $maya->id) }}"
                                       class="btn btn-sm btn-warning mx-1"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('maya.destroy', $maya->id) }}" method="POST">
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
                        Mostrando {{ $mayas->firstItem() }} a {{ $mayas->lastItem() }} de {{ $mayas->total() }} resultados
                    </div>
                    <div>
                        {{ $mayas->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('anio-select').addEventListener('change', function() {
        window.location.href = '?anio=' + this.value;
    });
</script>
@endsection
