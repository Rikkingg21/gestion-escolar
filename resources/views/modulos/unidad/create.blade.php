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
<!--mensaje de exito-->
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
<div class="container-fluid">
    <h3>Nueva Unidad para el Bimestre: {{ $bimestre->nombre ?? 'Sin nombre' }}</h3>

    <form action="{{ route('unidad.store') }}" method="POST">
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
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script>
    //si bimestre es 1, se habilitan para seleccnar unidades 1 y 2
    document.addEventListener('DOMContentLoaded', function() {
        const unidadSelect = document.getElementById('unidad');
        const bimestreId = {{ $bimestre->id ?? 0 }};
        const unidadesHabilitadas = {
            1: [1, 2],
            2: [3, 4],
            3: [5, 6],
            4: [7, 8]
        };
        const unidadesOcupadas = @json($ocupadoUnidades);
        const unidadesDisponibles = unidadesHabilitadas[bimestreId] || [];
        // Habilitar solo las unidades correspondientes al bimestre
        Array.from(unidadSelect.options).forEach(option => {
            const unidadValue = parseInt(option.value);
            if (unidadesDisponibles.includes(unidadValue)) {
                option.disabled = false;
                option.textContent = `Unidad ${unidadValue}`;
            } else {
                option.disabled = true;
                option.textContent = `Unidad ${unidadValue} (No disponible)`;
            }
        });
        // Deshabilitar unidades ocupadas
        unidadesOcupadas.forEach(function(unidad) {
            const option = unidadSelect.querySelector(`option[value="${unidad}"]`);
            if (option) {
                option.disabled = true;
                option.textContent += ' (Ocupado)';
            }
        });
    });
</script>
@endsection
