@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Editar Clase de la Semana</h1>
    <form action="{{ route('clases.update', $clase->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Semana:</label>
            <div class="form-control" readonly>
                Semana {{ $clase->semana->nombre ?? '' }}
            </div>
            <input type="hidden" name="semana_id" value="{{ $clase->semana->id }}">
        </div>
        <div class="mb-3">
            <label for="fecha_clase" class="form-label">Fecha de la Clase</label>
            <input type="date" name="fecha_clase" id="fecha_clase" class="form-control" value="{{ $clase->fecha_clase }}" required>
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción de la Clase</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required>{{ $clase->descripcion }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('clases.index', $clase->semana->id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
