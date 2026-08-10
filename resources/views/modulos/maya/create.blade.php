@extends('layouts.app')
@section('title', 'Crear Planificación')

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
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-plus me-2"></i>Crear nueva Maya
                    </h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="window.history.back()">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </button>
                </div>
                <div class="card-body">
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
                        {{ $docente->user->apellido_paterno ?? '' }} {{ $docente->user->apellido_materno ?? '' }} {{ $docente->user->nombre ?? '' }}- {{ $docente->user->dni ?? '' }}
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

        <div class="mb-3">
            <label for="periodo" class="form-label">Periodo</label>
            <select name="periodo_id" id="periodo" class="form-select" required>
                <option value="">Seleccione un periodo</option>
                @foreach($periodos as $periodo)
                    <option value="{{ $periodo->id }}">{{ $periodo->nombre ?? $periodo->id }}</option>
                @endforeach
            </select>
        </div>

        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
            <i class="bi bi-x me-1"></i>Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Guardar
        </button>
    </form>
                </div>
            </div>
        </div>
    </div>
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
