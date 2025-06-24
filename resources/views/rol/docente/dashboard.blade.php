@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Panel Docente</h1>
    <div>
        <h2>Año de la maya:</h2>
        <select name="anio" id="anio-select" class="form-select mb-3">
            @foreach($anios as $anio)
                <option value="{{ $anio }}" {{ $anio == $anioSeleccionado ? 'selected' : '' }}>{{ $anio }}</option>
            @endforeach
        </select>
    </div>
    @if($mayas->count())
        <div class="accordion" id="mayasAccordion">
            @foreach($mayas as $maya)
            <div class="accordion-item mb-3">
                <h2 class="accordion-header" id="headingMaya{{ $maya->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaya{{ $maya->id }}" aria-expanded="false" aria-controls="collapseMaya{{ $maya->id }}">
                        <strong>{{ $maya->materia->nombre ?? '' }}</strong> - {{ $maya->grado->grado ?? '' }} {{ $maya->grado->seccion ?? '' }} ({{ $maya->anio }})
                    </button>
                </h2>
                <div id="collapseMaya{{ $maya->id }}" class="accordion-collapse collapse" aria-labelledby="headingMaya{{ $maya->id }}" data-bs-parent="#mayasAccordion">
                    <div class="accordion-body">
                        <!-- Aquí puedes mostrar información relevante para el docente -->
                        <p><strong>Docente:</strong> {{ $maya->docente->nombre ?? 'No asignado' }}</p>
                        <!-- Puedes agregar más detalles o submódulos aquí -->
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">No hay mayas asignadas para este año.</div>
    @endif
</div>

<script>
    document.getElementById('anio-select').addEventListener('change', function() {
        window.location.href = '?anio=' + this.value;
    });
</script>
@endsection
