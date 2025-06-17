@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Crear Nuevo Grado</h1>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <form action="{{ route('grados.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="grado">Grado</label>
                    <input type="text" name="grado" id="grado" class="form-control" placeholder="Ingrese el grado" required>
                </div>
                <div class="form-group">
                    <label for="seccion">Sección</label>
                    <input type="text" name="seccion" id="seccion" class="form-control" placeholder="Ingrese la sección" required>
                </div>
                <div class="form-group">
                    <label for="nivel" class="form-label">Nivel</label>
                    <select name="nivel" id="nivel" class="form-select">
                        <option value="primaria">Primaria</option>
                        <option value="secundaria">Secundaria</option>
                    </select>
                </div><br>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>
</div>
@endsection
