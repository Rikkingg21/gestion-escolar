@extends('layouts.app')
@section('title', 'Crear Periodos')
@section('content')
    <div class="container">
        <h1>Crear Nuevo Periodo</h1>
        <form action="{{ route('periodo.store') }}" method="POST">
            @csrf
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
            <div class="mb-3">
                <label for="anio" class="form-label">Año</label>
                <input type="number" class="form-control" id="anio" name="anio" required>
            </div>
            <div>
                <select name="estado" id="estado" class="form-select">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Periodo</button>
        </form>
    </div>
@endsection
