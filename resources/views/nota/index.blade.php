@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-graduation-cap"></i> Registro de Calificaciones
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                {{ $materia->nombre }} - {{ $grado->nombreCompleto }} - {{ $bimestre->nombre }} Bimestre
            </h6>
            <small>
                Docente:
                @if($docente && $docente->user)
                    {{ $docente->user->nombre_completo ??
                    $docente->user->apellido_paterno.' '.
                    $docente->user->apellido_materno.', '.
                    $docente->user->nombre }}
                @else
                    No asignado
                @endif
            </small>
        </div>

        <div class="card-body">
            <form action="{{ route('nota.store') }}" method="POST" id="formNotas">
                @csrf
                <input type="hidden" name="bimestre_id" value="{{ $bimestre->id }}">

                <!-- Tabla para Estudiantes Activos -->
                @if($estudiantesActivos->count() > 0)
                <h5 class="mb-3 text-success">
                    <i class="fas fa-user-check"></i> Estudiantes Activos
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaNotasActivos">
                        <thead class="thead-dark">
                            <tr>
                                <th rowspan="2">Estudiante</th>
                                @foreach($competencias as $competencia)
                                    @if($competencia->criterios && $competencia->criterios->count() > 0)
                                        <th colspan="{{ $competencia->criterios->count() }}">
                                            {{ $competencia->nombre }}
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($competencias as $competencia)
                                    @foreach($competencia->criterios ?? [] as $criterio)
                                        <th title="{{ $criterio->descripcion }}">
                                            {{ $criterio->nombre }}
                                            <input type="hidden" name="criterios[]" value="{{ $criterio->id }}">
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesActivos as $estudiante)
                                <tr>
                                    <td>
                                        {{ $estudiante->user->apellido_paterno }}
                                        {{ $estudiante->user->apellido_materno }},
                                        {{ $estudiante->user->nombre }}
                                    </td>
                                    @foreach($competencias as $competencia)
                                        @foreach($competencia->criterios as $criterio)
                                            <td>
                                                @php
                                                    $key = $estudiante->id.'-'.$criterio->id;
                                                    $nota = $notasExistentes[$key] ?? null;
                                                @endphp
                                                <input type="number"
                                                    name="notas[{{ $estudiante->id }}][{{ $criterio->id }}]"
                                                    value="{{ $nota }}"
                                                    min="1"
                                                    max="4"
                                                    step="1"
                                                    class="form-control form-control-sm nota-input">
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Tabla para Estudiantes Inactivos (solo lectura) -->
                @if($estudiantesInactivos->count() > 0)
                <h5 class="mb-3 mt-4 text-secondary">
                    <i class="fas fa-user-times"></i> Estudiantes Inactivos (Solo Lectura)
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaNotasInactivos">
                        <thead class="thead-light">
                            <tr>
                                <th rowspan="2">Estudiante</th>
                                @foreach($competencias as $competencia)
                                    @if($competencia->criterios && $competencia->criterios->count() > 0)
                                        <th colspan="{{ $competencia->criterios->count() }}">
                                            {{ $competencia->nombre }}
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($competencias as $competencia)
                                    @foreach($competencia->criterios ?? [] as $criterio)
                                        <th title="{{ $criterio->descripcion }}">
                                            {{ $criterio->nombre }}
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesInactivos as $estudiante)
                                <tr class="table-secondary">
                                    <td>
                                        <i class="fas fa-lock text-muted mr-1"></i>
                                        {{ $estudiante->user->apellido_paterno }}
                                        {{ $estudiante->user->apellido_materno }},
                                        {{ $estudiante->user->nombre }}
                                        <span class="badge badge-warning ml-2">Inactivo</span>
                                    </td>
                                    @foreach($competencias as $competencia)
                                        @foreach($competencia->criterios as $criterio)
                                            <td>
                                                @php
                                                    $key = $estudiante->id.'-'.$criterio->id;
                                                    $nota = $notasExistentes[$key] ?? null;
                                                @endphp
                                                <input type="number"
                                                    value="{{ $nota }}"
                                                    min="1"
                                                    max="4"
                                                    step="1"
                                                    class="form-control form-control-sm"
                                                    readonly
                                                    style="background-color: #f8f9fa;">
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Calificaciones
                    </button>
                    <a href="{{ route('maya.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>

            <form action="{{ route('nota.publicar', $bimestre->id) }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-success"
                    onclick="return confirm('¿Seguro que deseas publicar todas las notas?')">
                    <i class="fas fa-bullhorn"></i> Publicar Notas
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Configuración de toastr (si lo estás usando)
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-bottom-right"
    };

    $(document).on('input', '.nota-input', function() {
        let value = parseInt($(this).val());

        if (isNaN(value)) {
            $(this).val('');
            return;
        }

        if (value < 1) {
            $(this).val(1);
        } else if (value > 4) {
            $(this).val(4);
        } else {
            $(this).val(Math.floor(value)); // Asegura número entero
        }
    });
});
</script>
@endsection
