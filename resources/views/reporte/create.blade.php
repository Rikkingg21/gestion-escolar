@extends('layouts.app')

@section('content')
@php
    // Prepara los datos para JS
    $grados = \App\Models\Grado::all();
    $estudiantes = \App\Models\Estudiante::with('user', 'apoderado.user')->get();
@endphp
<div class="container">
    <h1 class="h3 mb-4 text-gray-800">Crear Reporte</h1>
    <form action="{{ route('reporte.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="materia_id">Materia (opcional)</label>
            <select name="materia_id" id="materia_id" class="form-control">
                <option value="">Sin materia</option>
                @foreach($materias as $materia)
                    <option value="{{ $materia->id }}">{{ $materia->nombre }}</option>
                @endforeach
            </select>
        </div>
<div class="form-group">
    <label for="grado_id">Grado - Sección - Nivel</label>
    <select name="grado_id" id="grado_id" class="form-control">
        <option value="">Seleccione un grado</option>
        @foreach($grados as $grado)
            <option value="{{ $grado->id }}">{{ $grado->nombre_completo }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="estudiante_id">Estudiante</label>
    <select name="estudiante_id" id="estudiante_id" class="form-control">
        <option value="">Seleccione un estudiante</option>
        {{-- Opciones se llenan por JS --}}
    </select>
</div>

<div class="form-group">
    <label for="destinatario_id">Apoderado Destinatario</label>
    <select name="destinatario_id" id="destinatario_id" class="form-control" required>
        <option value="">Seleccione un apoderado</option>
        {{-- Opciones se llenan por JS --}}
    </select>
</div>
        <div class="form-group">
            <label for="asunto">Asunto</label>
            <input type="text" name="asunto" id="asunto" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="fecha">Fecha de citación</label>
            <input type="date" name="fecha" id="fecha" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="hora">Hora de citación</label>
            <input type="time" name="hora" id="hora" class="form-control" required>
        </div><br>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>

    </form>
</div>
<script>
    // Datos para JS
    const estudiantes = @json($estudiantes);

    document.getElementById('grado_id').addEventListener('change', function() {
        const gradoId = this.value;
        const estudianteSelect = document.getElementById('estudiante_id');
        estudianteSelect.innerHTML = '<option value="">Seleccione un estudiante</option>';

        estudiantes.forEach(est => {
            if (est.grado_id == gradoId) {
                estudianteSelect.innerHTML += `<option value="${est.id}">${est.user.apellido_paterno} ${est.user.apellido_materno} ${est.user.nombre}</option>`;
            }
        });

        // Limpiar apoderado
        document.getElementById('destinatario_id').innerHTML = '<option value="">Seleccione un apoderado</option>';
    });

    document.getElementById('estudiante_id').addEventListener('change', function() {
        const estudianteId = this.value;
        const apoderadoSelect = document.getElementById('destinatario_id');
        apoderadoSelect.innerHTML = '<option value="">Seleccione un apoderado</option>';

        const estudiante = estudiantes.find(est => est.id == estudianteId);
        if (estudiante && estudiante.apoderado) {
            const apoderado = estudiante.apoderado.user;
            apoderadoSelect.innerHTML += `<option value="${apoderado.id}">${apoderado.apellido_paterno} ${apoderado.apellido_materno} ${apoderado.nombre} (${apoderado.dni})</option>`;
        }
    });
</script>
@endsection
