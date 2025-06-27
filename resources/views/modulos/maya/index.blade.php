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
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-people-fill"></i> Administración de Mayas
        </h1>
        <!-- Si rol es admin o director que se muestre nueva maya -->
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
            <a href="{{ route('maya.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Nueva Maya
            </a>
        @endif
    </div>
    <div>
        <h2>Año de la maya:</h2>
        <select name="anio" id="anio-select" class="form-select">
            @foreach($anios as $anio)
                <option value="{{ $anio }}" {{ $anio == $anioSeleccionado ? 'selected' : '' }}>{{ $anio }}</option>
            @endforeach
        </select>
    </div><br>
    <div id="formulario-dinamico">

    <div class="accordion" id="mayasAccordion">
        @foreach($mayas as $maya)
        <div class="accordion-item mb-3">
            <h2 class="accordion-header" id="headingMaya{{ $maya->id }}"><!-- Módulo: Maya -->
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaya{{ $maya->id }}" aria-expanded="false" aria-controls="collapseMaya{{ $maya->id }}">
                    <div class="mb-2">
                    <strong>{{ $maya->materia->nombre ?? '' }}</strong> - {{ $maya->grado->grado ?? '' }} {{ $maya->grado->seccion ?? '' }} ({{ $maya->anio }})
                    <a href="" class="btn btn-primary shadow-sm">Calificar</a>
                    </div>
                </button>
            </h2>
            <div id="collapseMaya{{ $maya->id }}" class="accordion-collapse collapse" aria-labelledby="headingMaya{{ $maya->id }}" data-bs-parent="#mayasAccordion">
                <div class="accordion-body">
                    <div class="mb-2">
                        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
                            <a href="{{ route('maya.edit', $maya->id) }}" class="btn btn-warning btn-sm">Editar Maya</a>
                            <form action="{{ route('maya.destroy', $maya->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta maya?')">Eliminar Maya</button>
                            </form>
                        @endif
                        <a href="{{ route('bimestre.create', ['curso_grado_sec_niv_anio_id' => $maya->id]) }}" class="btn btn-primary btn-sm crear-bimestre-btn" data-bimestres="{{ $maya->bimestres->count() }}">Crear Bimestre</a>
                    </div>
                    <!-- Submódulo: Bimestres -->
                    <div class="accordion" id="bimestresAccordion{{ $maya->id }}">
                        <div class="accordion-item">
                            @foreach ($maya->bimestres as $bimestre)
                                <h2 class="accordion-header" id="headingBimestre{{ $bimestre->id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBimestre{{ $bimestre->id }}" aria-expanded="false" aria-controls="collapseBimestre{{ $bimestre->id }}">
                                        {{ $bimestre->nombre }} Bimestre
                                    </button>
                                </h2>
                                <div id="collapseBimestre{{ $bimestre->id }}" class="accordion-collapse collapse" aria-labelledby="headingBimestre{{ $bimestre->id }}" data-bs-parent="#bimestresAccordion{{ $maya->id }}">
                                    <div class="accordion-body">
                                        <div class="mb-2">
                                            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))

                                                <a href="{{ route('bimestre.edit', $bimestre->id) }}" class="btn btn-warning btn-sm">Editar Bimestre</a>
                                                <form action="{{ route('bimestre.destroy', $bimestre->id) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este bimestre?')">Eliminar Bimestre</button>
                                                </form>

                                            @endif
                                            <a href="{{ route('unidad.create', ['bimestre_id' => $bimestre->id]) }}" class="btn btn-primary btn-sm crear-unidad-btn" data-unidad="{{ $maya->bimestres->count() }}">Crear Unidad</a>
                                        </div>


                                        <!-- Unidades -->
                                        <div class="accordion" id="unidadesAccordion{{ $bimestre->id }}">
                                            <div class="accordion-item">
                                                @foreach ($bimestre->unidades as $unidad)
                                                    <h2 class="accordion-header" id="headingUnidad{{ $unidad->id }}">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUnidad{{ $unidad->id }}" aria-expanded="false" aria-controls="collapseUnidad{{ $unidad->id }}">
                                                            {{ $unidad->nombre }} Unidad
                                                        </button>
                                                    </h2>
                                                    <div id="collapseUnidad{{ $unidad->id }}" class="accordion-collapse collapse" aria-labelledby="headingUnidad{{ $unidad->id }}" data-bs-parent="#unidadesAccordion{{ $bimestre->id }}">
                                                        <div class="accordion-body">
                                                            <div class="mb-2">
                                                                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))

                                                                    <a href="{{ route('unidad.edit', $unidad->id) }}" class="btn btn-warning btn-sm">Editar Unidad</a>
                                                                    <form action="{{ route('unidad.destroy', $unidad->id) }}" method="POST" class="d-inline">
                                                                        @csrf @method('DELETE')
                                                                        <input type="hidden" name="anio" value="{{ $anioSeleccionado }}">
                                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta unidad?')">Eliminar Unidad</button>
                                                                    </form>

                                                                @endif
                                                                <a href="{{ route('semana.create', ['unidad_id' => $unidad->id]) }}" class="btn btn-primary btn-sm crear-semana-btn" data-semanas="{{ $unidad->semanas->count() }}">Crear Semana</a>
                                                            </div>
                                                            <!-- Semanas -->
                                                            <div class="accordion" id="semanasAccordion{{ $unidad->id }}">
                                                                <div class="accordion-item">
                                                                    @foreach ($unidad->semanas as $semana)
                                                                        <h2 class="accordion-header" id="headingSemana{{ $semana->id }}">
                                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSemana{{ $semana->id }}" aria-expanded="false" aria-controls="collapseSemana{{ $semana->id }}">
                                                                                {{ $semana->nombre }} Semana
                                                                            </button>
                                                                        </h2>
                                                                        <div id="collapseSemana{{ $semana->id }}" class="accordion-collapse collapse" aria-labelledby="headingSemana{{ $semana->id }}" data-bs-parent="#semanasAccordion{{ $unidad->id }}">
                                                                            <div class="accordion-body">
                                                                                <div class="mb-2">
                                                                                    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))

                                                                                        <a href="{{ route('semana.edit', $semana->id) }}" class="btn btn-warning btn-sm">Editar Semana</a>
                                                                                        <form action="{{ route('semana.destroy', $semana->id) }}" method="POST" class="d-inline">
                                                                                            @csrf @method('DELETE')
                                                                                            <input type="hidden" name="anio" value="{{ $anioSeleccionado }}">
                                                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta semana?')">Eliminar Semana</button>
                                                                                        </form>

                                                                                    @endif
                                                                                    <a href="{{ route('clase.create', ['semana_id' => $semana->id]) }}" class="btn btn-primary btn-sm crear-clase-btn" data-clases="{{ $semana->clases->count() }}">Crear Clase</a>
                                                                                </div>
                                                                                <!-- Clases -->
                                                                                <div class="accordion" id="clasesAccordion{{ $semana->id }}">
                                                                                    <div class="accordion-item">
                                                                                        @foreach ($semana->clases as $clase)
                                                                                            <h2 class="accordion-header" id="headingClase{{ $clase->id }}">
                                                                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClase{{ $clase->id }}" aria-expanded="false" aria-controls="collapseClase{{ $clase->id }}">
                                                                                                    Clase: {{ $clase->descripcion }} ({{ \Carbon\Carbon::parse($clase->fecha_clase)->format('d-m-Y') }})
                                                                                                </button>
                                                                                            </h2>
                                                                                            <div id="collapseClase{{ $clase->id }}" class="accordion-collapse collapse" aria-labelledby="headingClase{{ $clase->id }}" data-bs-parent="#clasesAccordion{{ $semana->id }}">
                                                                                                <div class="accordion-body">
                                                                                                    <div class="mb-2">
                                                                                                        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))

                                                                                                            <a href="{{ route('clase.edit', $clase->id) }}" class="btn btn-warning btn-sm">Editar Clase</a>
                                                                                                            <form action="{{ route('clase.destroy', $clase->id) }}" method="POST" class="d-inline">
                                                                                                                @csrf @method('DELETE')
                                                                                                                <input type="hidden" name="anio" value="{{ $anioSeleccionado }}">
                                                                                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta clase?')">Eliminar Clase</button>
                                                                                                            </form>

                                                                                                        @endif
                                                                                                        <a href="{{ route('tema.create', ['clase_id' => $clase->id]) }}" class="btn btn-primary btn-sm crear-tema-btn" data-temas="{{ $clase->temas->count() }}">Crear Tema</a>
                                                                                                    </div>
                                                                                                    <!-- Temas -->
                                                                                                    <div class="accordion" id="temasAccordion{{ $clase->id }}">
                                                                                                        <div class="accordion-item">
                                                                                                            @foreach ($clase->temas as $tema)
                                                                                                                <h2 class="accordion-header" id="headingTema{{ $tema->id }}">
                                                                                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTema{{ $tema->id }}" aria-expanded="false" aria-controls="collapseTema{{ $tema->id }}">
                                                                                                                        Tema: {{ $tema->nombre }}
                                                                                                                    </button>
                                                                                                                </h2>
                                                                                                                <div id="collapseTema{{ $tema->id }}" class="accordion-collapse collapse" aria-labelledby="headingTema{{ $tema->id }}" data-bs-parent="#temasAccordion{{ $clase->id }}">
                                                                                                                    <div class="accordion-body">
                                                                                                                        <!-- Criterios -->
                                                                                                                        <ul>
                                                                                                                            @foreach ($tema->criterios as $criterio)
                                                                                                                                <li>{{ $criterio->descripcion }} ({{ $criterio->tipo }})</li>
                                                                                                                            @endforeach
                                                                                                                        </ul>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            @endforeach
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

    </div>
</div>



<script>
    document.getElementById('anio-select').addEventListener('change', function() {
        window.location.href = '?anio=' + this.value;
    });

    // Deshabilitar botón "Crear bimestre" si hay 4 o más bimestres
    document.querySelectorAll('.crear-bimestre-btn').forEach(function(btn) {
        if (parseInt(btn.dataset.bimestres) >= 4) {
            btn.classList.add('disabled');
            btn.setAttribute('aria-disabled', 'true');
            btn.setAttribute('tabindex', '-1');
            btn.onclick = function(e) { e.preventDefault(); };
        }
    });
    // En cada bimestre debe tener 2 unidades como maximo, si llega a esa cantidad entonces deshabilitar el boton de crear unidad
    /*fata*/


    // Guardar el último acordeón abierto en localStorage
    document.querySelectorAll('.accordion-button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            localStorage.setItem('maya_last_open', this.dataset.bsTarget || this.getAttribute('data-bs-target'));
        });
    });

    // Al cargar la página, abrir el último acordeón seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        var lastOpen = localStorage.getItem('maya_last_open');
        if (lastOpen) {
            var collapse = document.querySelector(lastOpen);
            if (collapse) {
                var bsCollapse = new bootstrap.Collapse(collapse, {toggle: true});
            }
        }
    });
</script>
@endsection
