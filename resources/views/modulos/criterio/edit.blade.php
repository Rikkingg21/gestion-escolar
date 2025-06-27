@extends('layouts.app')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="container-fluid">
    <h1>Editar Criterio de Evaluación</h1>

    <form action="{{ route('criterio.update', $criterio->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="tema_id" value="{{ $tema->id }}">
        <input type="hidden" name="clase_id" value="{{ $tema->clase_id }}">
        <input type="hidden" name="semana_id" value="{{ $tema->clase->semana_id }}">

        <div class="mb-3">
            <label class="form-label">Tema:</label>
            <div class="form-control" readonly>
                {{ $tema->nombre }} (Orden: {{ $tema->orden }})
            </div>
            <div class="mb-3">
            <label for="orden" class="form-label">Orden del Criterio</label>
            <input type="number" name="orden" id="orden" class="form-control" value="{{ $criterio->orden }}" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción del Criterio</label>
            <input type="text" name="descripcion" id="descripcion" class="form-control" value="{{ $criterio->descripcion }}" required>
        </div>
        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo del Criterio</label>
            <select name="tipo" id="tipo" class="form-select" required>
                <option value="" disabled>Seleccione un tipo de criterio</option>
                <option value="Examen" {{ $criterio->tipo == 'Examen' ? 'selected' : '' }}>Examen Parcial</option>
                <option value="Tarea" {{ $criterio->tipo == 'Tarea' ? 'selected' : '' }}>Tarea</option>
                <option value="Trabajo en clase" {{ $criterio->tipo == 'Trabajo en clase' ? 'selected' : '' }}>Trabajo en clase</option>
                <option value="Proyecto" {{ $criterio->tipo == 'Proyecto' ? 'selected' : '' }}>Proyecto</option>
                <option value="Participacion" {{ $criterio->tipo == 'Participacion' ? 'selected' : '' }}>Participación</option>
                <option value="Examen de Unidad" {{ $criterio->tipo == 'Examen de Unidad' ? 'selected' : '' }}>Examen de Unidad</option>
                <option value="Examen Bimestral" {{ $criterio->tipo == 'Examen Bimestral' ? 'selected' : '' }}>Examen Bimestral</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('maya.index', ['anio' => $anio]) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
