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

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

<div class="container-fluid">
    <h1>Crear Criterios de evaluar</h1>

    <form action="{{ route('criterio.store') }}" method="POST">
        @csrf
        <input type="hidden" name="tema_id" value="{{ $tema->id }}">
        <div class="mb-3">
            <label class="form-label">Tema:</label>
            <div class="form-control" readonly>
                {{ $tema->nombre }} (Orden: {{ $tema->orden }})
            </div>
            <input type="hidden" name="tema_id" value="{{ $tema->id }}">
            <input type="hidden" name="clase_id" value="{{ $tema->clase_id }}">
            <input type="hidden" name="semana_id" value="{{ $tema->clase->semana_id }}">
        </div>
        <div class="mb-3">
            <label for="orden" class="form-label">Orden del Criterio</label>
            <input type="number" name="orden" id="orden" class="form-control" value="{{ $ultimoOrden + 1 }}" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción del Criterio</label>
            <input type="text" name="descripcion" id="descripcion" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo del Criterio</label>
            <select name="tipo" id="tipo" class="form-select" required>
                <option value="" disabled selected>Seleccione un tipo de criterio</option>
                <option value="Examen">Examen Parcial</option>
                <option value="Tarea">Tarea</option>
                <option value="Trabajo en clase">Trabajo en clase</option>
                <option value="Proyecto">Proyecto</option>
                <option value="Participacion">Participación</option>
                <option value="Examen de Unidad">Examen de Unidad</option>
                <option value="Examen Bimestral">Examen Bimestral</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Crear</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
