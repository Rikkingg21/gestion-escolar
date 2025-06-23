@extends('layouts.app')

@section('content')
<!--mensaje de error-->
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<!--mensaje de exito-->
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
<div class="container-fluid">
    <h1>Crear nueva Maya</h1>
    <form action="{{ route('maya.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="materia" class="form-label">Materia</label>
            <select name="materia_id" id="materia" class="form-select" required>
                <option value="">Seleccione una materia</option>
                @foreach($materias as $materia)
                    <option value="{{ $materia->id }}">{{ $materia->nombre ?? $materia->id }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="docente" class="form-label">Docente</label>
            <select name="docente_designado_id" id="docente" class="form-select" required>
                <option value="">Seleccione un docente</option>
                @foreach($docentes as $docente)
                    <option value="{{ $docente->id }}">
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
                    <option value="{{ $grado->id }}">
                        {{ $grado->grado ?? $grado->id }} - {{ $grado->seccion ?? '' }} - {{ $grado->nivel ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="anio" class="form-label">Año</label>
            <input type="number" name="anio" id="anio" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
<script>
    //que en anio se cargue en automatico el anio actual
    document.addEventListener('DOMContentLoaded', function() {
        const anioInput = document.getElementById('anio');
        const currentYear = new Date().getFullYear();
        anioInput.value = currentYear;
    });
</script>
@endsection
