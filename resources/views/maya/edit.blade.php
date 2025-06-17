@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Editar Maya</h1>
    <form action="{{ route('mayas.update', $maya->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="materia" class="form-label">Materia</label>
            <select name="materia_id" id="materia" class="form-select" required>
                <option value="">Seleccione una materia</option>
                @foreach($materias as $materia)
                    <option value="{{ $materia->id }}" {{ $maya->materia_id == $materia->id ? 'selected' : '' }}>
                        {{ $materia->nombre ?? $materia->id }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="docente" class="form-label">Docente</label>
            <select name="docente_designado_id" id="docente" class="form-select" required>
                <option value="">Seleccione un docente</option>
                @foreach($docentes as $docente)
                    <option value="{{ $docente->id }}" {{ $maya->docente_designado_id == $docente->id ? 'selected' : '' }}>
                        {{ $docente->user->nombre ?? '' }} {{ $docente->user->apellido_paterno ?? '' }} {{ $docente->user->apellido_materno ?? '' }} - {{ $docente->user->dni ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="grado" class="form-label">Grado</label>
            <select name="grado_id" id="grado" class="form-select" required>
                <option value="">Seleccione un grado</option>
                @foreach($grados as $grado)
                    <option value="{{ $grado->id }}" {{ $maya->grado_id == $grado->id ? 'selected' : '' }}>
                        {{ $grado->grado ?? $grado->id }} - {{ $grado->seccion ?? '' }} - {{ $grado->nivel ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="anio" class="form-label">Año</label>
            <input type="number" name="anio" id="anio" class="form-control" value="{{ $maya->anio }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('mayas.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
