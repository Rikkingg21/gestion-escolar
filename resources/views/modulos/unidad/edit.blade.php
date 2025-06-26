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
<div class="container-fluid">
    <h1>Editar Unidad</h1>

    <form action="{{ route('unidad.update', $unidad->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="anio" value="{{ $anio }}">
        <div class="mb-3">
            <label class="form-label">Bimestre:</label>
            <div class="form-control" readonly>
                {{ $bimestre->nombre }}
            </div>
            <input type="hidden" name="bimestre_id" value="{{ $bimestre->id }}">
        </div>

        <div class="mb-3">
            <label for="unidad" class="form-label">Número de Unidad</label>
            <select name="unidad" id="unidad" class="form-control" required>
                <option value="">Seleccione una unidad</option>
                @for($i = 1; $i <= 8; $i++)
                    <option value="{{ $i }}"
                        {{ $unidad->nombre == $i ? 'selected' : '' }}
                        {{ in_array($i, $ocupadoUnidades) ? 'disabled' : '' }}>
                        Unidad {{ $i }}{{ in_array($i, $ocupadoUnidades) ? ' (Ocupada)' : '' }}
                    </option>
                @endfor
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('maya.index', ['anio' => $anio]) }}" class="btn btn-secondary">Cancelar</a>
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
                option.textContent += ' (Ocupada)';
            }
        });
    });
</script>
@endsection
