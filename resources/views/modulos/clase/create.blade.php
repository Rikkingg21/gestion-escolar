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
    <h1>Crear Clase de la Semana</h1>
    <form action="{{ route('clase.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Semana:</label>
            <div class="form-control" readonly>
                Semana {{ $semana->nombre ?? '' }}
            </div>
            <input type="hidden" name="semana_id" value="{{ $semana->id }}">
        </div>

        <div class="mb-3">
            <label for="fecha_clase" class="form-label">Fecha de la Clase</label>
            <input type="date" name="fecha_clase" id="fecha_clase" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción de la Clase</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Crear</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

@endsection
