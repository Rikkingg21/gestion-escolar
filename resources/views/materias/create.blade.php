@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Crear Nueva Materia</h1>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <form action="{{ route('materias.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="nombre">Nombre de la Materia</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ingrese el nombre de la materia" required>
                </div><br>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>
</div>
@endsection
