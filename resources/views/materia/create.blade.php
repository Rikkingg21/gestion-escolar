@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Crear Nueva Materia</h1>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <form action="{{ route('materia.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <div class="mb-3">
                        <label for="nombre">Nombre de la Materia</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ingrese el nombre de la materia" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" name="estado" id="">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('materia.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
