@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Crear Criterios de evaluar</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('criterios.store') }}" method="POST">
        @csrf
        <input type="hidden" name="tema_id" value="{{ $tema->id }}">
        <div class="mb-3">
            <label class="form-label">Tema:</label>
            <div class="form-control" readonly>
                {{ $tema->nombre }} (Orden: {{ $tema->orden }})
            </div>
            <input type="hidden" name="tema_id" value="{{ $tema->id }}">
        </div>
        <div class="mb-3">
            <label for="orden" class="form-label">Orden del Criterio</label>
            <input type="number" name="orden" id="orden" class="form-control" value="{{ $ultimoOrden + 1 }}" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción del Criterio</label>
            <input type="text" name="descripcion" id="descripcion" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo del Criterio</label>
            <select name="tipo" id="tipo" class="form-select" required>
                <option value="" disabled selected>Seleccione un tipo de criterio</option>
                <option value="Examen">Examen Parcial</option>
                <option value="Tarea">Tarea</option>
                <option value="Trabajo en clase">Trabajo en clase</option>
                <option value="Proyecto">Proyecto</option>
                <option value="Participacion">Participación</option>
                <option value="Examen de Unidad">Examen de Unidad</option>
                <option value="Examen Bimestral">Examen Bimestral</option>
            </select>
        </div>

        <!--Generar progress según las unidades que hay-->
        <div class="mb-3">
            <label class="form-label">Distribución del Peso (%) en base a la Unidad</label>
            <div class="progress" style="height: 30px;">
                <div id="peso-ocupado-bar" class="progress-bar bg-success" role="progressbar"
                    style="width: {{ $pesoOcupado }}%;" aria-valuenow="{{ $pesoOcupado }}" aria-valuemin="0" aria-valuemax="100">
                    {{ $pesoOcupado }}%
                </div>
                <div id="peso-nuevo-bar" class="progress-bar bg-info" role="progressbar"
                    style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    +0%
                </div>
            </div>
            <small id="peso-help" class="form-text text-muted">
                El peso total de los criterios no debe exceder el 100%.
            </small>
        </div>

        <div class="mb-3">
            <label for="peso" class="form-label">Peso del Criterio</label>
            <!--Del 1 al 100%-->
            <input type="number" name="peso" id="peso" class="form-control" min="1" max="100" value="1" required>
        </div>


        <button type="submit" class="btn btn-primary">Crear</button>
        <a href="{{ route('criterios.index', $tema->id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script>
    //que a la barra se agregue el peso del criterio que se esta creando de forma dinamica
    document.addEventListener('DOMContentLoaded', function() {
        const pesoInput = document.getElementById('peso');
        const pesoOcupadoBar = document.getElementById('peso-ocupado-bar');
        const pesoNuevoBar = document.getElementById('peso-nuevo-bar');
        const pesoHelp = document.getElementById('peso-help');
        const pesoOcupado = {{ $pesoOcupado }};
        const pesoMaximo = 100;
        const pesoRestante = pesoMaximo - pesoOcupado;
        pesoInput.max = pesoRestante;
        pesoInput.value = Math.min(pesoInput.value, pesoRestante);
        pesoNuevoBar.style.width = (pesoInput.value / pesoMaximo * 100) + '%';
        pesoNuevoBar.setAttribute('aria-valuenow', pesoInput.value);
        pesoNuevoBar.textContent = '+' + pesoInput.value + '%';
        pesoHelp.textContent = `El peso total de los criterios no debe exceder el 100%. Peso restante: ${pesoRestante}%`;
        pesoInput.addEventListener('input', function() {
            const pesoNuevo = parseInt(pesoInput.value);
            if (pesoNuevo < 1 || pesoNuevo > pesoRestante) {
                pesoInput.setCustomValidity(`El peso debe estar entre 1 y ${pesoRestante}.`);
            } else {
                pesoInput.setCustomValidity('');
            }
            pesoNuevoBar.style.width = (pesoNuevo / pesoMaximo * 100) + '%';
            pesoNuevoBar.setAttribute('aria-valuenow', pesoNuevo);
            pesoNuevoBar.textContent = '+' + pesoNuevo + '%';
        });
    });
</script>
@endsection
