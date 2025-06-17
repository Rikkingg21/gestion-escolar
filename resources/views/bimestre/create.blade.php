@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <h1>Crear Bimestre</h1>
    <form action="{{ route('bimestres.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Curso/Grado/Sec/Niv/Año</label>
            @php
                //recolecta el id
                $cursoSeleccionado = $cursos->firstWhere('id', $curso_grado_sec_niv_anio_id);
            @endphp
            <div class="form-control" readonly>
                {{ $cursoSeleccionado->materia->nombre ?? '' }} |
                {{ $cursoSeleccionado->grado->grado ?? '' }} - {{ $cursoSeleccionado->grado->seccion ?? '' }} - {{ $cursoSeleccionado->grado->nivel ?? '' }} |
                {{ $cursoSeleccionado->anio ?? '' }}
            </div>
            <input type="hidden" name="curso_grado_sec_niv_anio_id" value="{{ $curso_grado_sec_niv_anio_id }}">
        </div>
        <!--Selecciona bimestre, son 4 en total-->
        <div class="mb-3">
            <label for="bimestre" class="form-label">Bimestre</label>
            <select name="bimestre" id="bimestre" class="form-select" required>
                <option value="" disabled selected>Seleccione un bimestre</option>
                <option value="1">Bimestre 1</option>
                <option value="2">Bimestre 2</option>
                <option value="3">Bimestre 3</option>
                <option value="4">Bimestre 4</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script>
    //si hay bimestre ocupado, que la opcion este deshabilitada
    document.addEventListener('DOMContentLoaded', function() {
        const bimestreSelect = document.getElementById('bimestre');
        const ocupadoBimestres = @json($ocupadoBimestres);

        // Deshabilitar opciones ocupadas
        ocupadoBimestres.forEach(function(bimestre) {
            const option = bimestreSelect.querySelector(`option[value="${bimestre}"]`);
            if (option) {
                option.disabled = true;
                option.textContent += ' (Ocupado)';
            }
        });
    });
</script>
@endsection
