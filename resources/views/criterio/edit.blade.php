@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Editar Criterio de Evaluación</h1>

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

    <form action="{{ route('criterios.update', $criterio->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="tema_id" value="{{ $criterio->tema_id }}">

        <div class="mb-3">
            <label class="form-label">Tema:</label>
            <div class="form-control" readonly>
                {{ $criterio->tema->nombre }} (Orden: {{ $criterio->tema->orden }})
            </div>
            <input type="hidden" name="tema_id" value="{{ $criterio->tema_id }}">
        </div>
        <div class="mb-3">
            <label for="orden" class="form-label">Orden del Criterio</label>
            <input type="number" name="orden" id="orden" class="form-control" value="{{ $criterio->orden }}" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción del Criterio</label>
            <input type="text" name="descripcion" id="descripcion" class="form-control" value="{{ $criterio->descripcion }}" required>
        </div>
        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo del Criterio</label>
            <select name="tipo" id="tipo" class="form-select" required>
                <option value="" disabled>Seleccione un tipo de criterio</option>
                <option value="Examen" {{ $criterio->tipo == 'Examen' ? 'selected' : '' }}>Examen Parcial</option>
                <option value="Tarea" {{ $criterio->tipo == 'Tarea' ? 'selected' : '' }}>Tarea</option>
                <option value="Trabajo en clase" {{ $criterio->tipo == 'Trabajo en clase' ? 'selected' : '' }}>Trabajo en clase</option>
                <option value="Proyecto" {{ $criterio->tipo == 'Proyecto' ? 'selected' : '' }}>Proyecto</option>
                <option value="Participacion" {{ $criterio->tipo == 'Participacion' ? 'selected' : '' }}>Participación</option>
                <option value="Examen de Unidad" {{ $criterio->tipo == 'Examen de Unidad' ? 'selected' : '' }}>Examen de Unidad</option>
                <option value="Examen Bimestral" {{ $criterio->tipo == 'Examen Bimestral' ? 'selected' : '' }}>Examen Bimestral</option>
            </select>
        </div>
        <div class="mb-3">
            <div class="mb-4">
            <label class="form-label">Distribución del Peso (%) en la Unidad</label>
            <div class="progress mb-2" style="height: 30px;">
                <!-- Peso ocupado por otros criterios -->
                <div id="peso-ocupado-bar"
                    class="progress-bar bg-danger"
                    role="progressbar"
                    style="width: {{ $pesoOcupado }}%;"
                    aria-valuenow="{{ $pesoOcupado }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    title="Peso ocupado por otros criterios">
                    {{ $pesoOcupado }}%
                </div>

                <!-- Espacio para el nuevo criterio (dinámico) -->
                <div id="peso-nuevo-bar"
                    class="progress-bar bg-success"
                    role="progressbar"
                    style="width: 0%;"
                    aria-valuenow="0"
                    aria-valuemin="0"
                    aria-valuemax="{{ 100 - $pesoOcupado }}"
                    title="Peso que ocupará este criterio">
                    +0%
                </div>

                <!-- Espacio disponible restante -->
                <div id="peso-disponible-bar"
                    class="progress-bar bg-light text-dark"
                    role="progressbar"
                    style="width: {{ 100 - $pesoOcupado }}%;">
                    {{ 100 - $pesoOcupado }}% libre
                </div>
            </div>
                <!-- Input del Peso con validación -->
            <div class="mb-3">
                <label for="peso" class="form-label">Peso del Criterio (%)</label>
                <input type="number"
                    name="peso"
                    id="peso"
                    class="form-control"
                    min="1"
                    max="{{ 100 - $pesoOcupado }}"
                    value="1"
                    required
                    oninput="actualizarProgressBar(this.value)">
                <small class="text-muted" id="peso-help">
                    Ingrese un valor entre 1 y {{ 100 - $pesoOcupado }}%
                </small>
            </div>
        </div>

        <div class="alert alert-info py-2">
            <small>
                <strong>Límites:</strong>
                Máximo por criterio: <span id="max-permitido">{{ min(20, 100 - $pesoOcupado) }}</span>% |
                Disponible: <span id="disponible-actual">{{ 100 - $pesoOcupado }}</span>%
            </small>
        </div>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('criterios.index', $criterio->tema_id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script>
    function actualizarProgressBar(valor) {
        const pesoOcupado = {{ $pesoOcupado }};
        const nuevoPeso = parseInt(valor) || 0;
        const maxPermitido = 100 - pesoOcupado;

        // Actualizar barras
        document.getElementById('peso-nuevo-bar').style.width = nuevoPeso + '%';
        document.getElementById('peso-nuevo-bar').ariaValuenow = nuevoPeso;
        document.getElementById('peso-nuevo-bar').textContent = '+' + nuevoPeso + '%';

        // Actualizar barra disponible
        const disponible = maxPermitido - nuevoPeso;
        document.getElementById('peso-disponible-bar').style.width = disponible + '%';
        document.getElementById('peso-disponible-bar').textContent = disponible + '% libre';

        // Validación visual
        const pesoHelp = document.getElementById('peso-help');
        if (nuevoPeso > maxPermitido) {
            pesoHelp.classList.add('text-danger');
            pesoHelp.textContent = `¡Superarás el límite! Máximo permitido: ${maxPermitido}%`;
        } else {
            pesoHelp.classList.remove('text-danger');
            pesoHelp.textContent = `Peso válido. Disponible: ${maxPermitido - nuevoPeso}%`;
        }
    }

    // Inicializar al cargar
    document.addEventListener('DOMContentLoaded', function() {
        const inputPeso = document.getElementById('peso');
        inputPeso.setAttribute('max', {{ 100 - $pesoOcupado }});
    });
</script>
@endsection
