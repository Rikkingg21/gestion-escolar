@extends('layouts.app')

@section('content')

<div class="container">
    <h3>Nueva Unidad para el Bimestre: {{ $bimestre->nombre ?? 'Sin nombre' }}</h3>

    <form action="{{ route('unidades.store') }}" method="POST">
        @csrf
        <label class="form-label">Bimestre:</label>
        <div class="form-control" readonly>
            {{ $bimestre->nombre ?? '' }}
        </div>
        <input type="hidden" name="bimestre_id" value="{{ $bimestre->id }}">
        <!--Selecciona unidades, son 8 en total-->

        <div class="mb-3">
            <label for="unidad" class="form-label">Unidad</label>
            <select name="unidad" id="unidad" class="form-control" required>
                <option value="">Seleccione una unidad</option>
                <option value="1">Unidad 1</option>
                <option value="2">Unidad 2</option>
                <option value="3">Unidad 3</option>
                <option value="4">Unidad 4</option>
                <option value="5">Unidad 5</option>
                <option value="6">Unidad 6</option>
                <option value="7">Unidad 7</option>
                <option value="8">Unidad 8</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('unidades.index', $bimestre->id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script>
    //si hay unidades ocupadas, que la opcion este deshabilitada
    document.addEventListener('DOMContentLoaded', function() {
        const unidadSelect = document.getElementById('unidad');
        const ocupadoUnidades = @json($ocupadoUnidades);
        // Deshabilitar opciones ocupadas
        ocupadoUnidades.forEach(function(unidad) {
            const option = unidadSelect.querySelector(`option[value="${unidad}"]`);
            if (option) {
                option.disabled = true;
                option.textContent += ' (Ocupado)';
            }
        });
    });
</script>
@endsection
