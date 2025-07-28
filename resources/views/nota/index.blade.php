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
            <small>Docente: {{ $docente->user->nombre_completo ?? 'No asignado' }}</small>
        </div>

        <div class="card-body">
            <form action="{{ route('nota.store') }}" method="POST" id="formNotas">
                @csrf
                <input type="hidden" name="bimestre_id" value="{{ $bimestre->id }}">

                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaNotas">
                        <thead class="thead-dark">
                            <tr>
                                <th rowspan="2">Estudiante</th>
                                @foreach($competencias as $competencia)
                                    <th colspan="{{ $competencia->criterios->count() }}">
                                        {{ $competencia->nombre }}
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($competencias as $competencia)
                                    @foreach($competencia->criterios as $criterio)
                                        <th title="{{ $criterio->descripcion }}">
                                            {{ $criterio->nombre }}
                                            <input type="hidden" name="criterios[]" value="{{ $criterio->id }}">
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantes as $estudiante)
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
                                                    $nota = $notasExistentes
                                                        ->get($estudiante->id, collect())
                                                        ->get($criterio->id, (object)['nota' => null])
                                                        ->nota;
                                                @endphp
                                                <input type="number"
                                                    name="notas[{{ $estudiante->id }}][{{ $criterio->id }}]"
                                                    value="{{ $nota }}"
                                                    min="1"
                                                    max="4"
                                                    step="0.1"
                                                    class="form-control form-control-sm nota-input">
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Calificaciones
                    </button>
                    <a href="{{ route('maya.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Configuración de toastr (si lo estás usando)
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-bottom-right"
    };

    $(document).on('change', '.auto-save-nota', function() {
        let input = $(this);
        let value = parseFloat(input.val()) || 1; // Valor por defecto 1 si está vacío

        // Validación para 1-4
        if (value < 1) {
            input.val(1);
            value = 1;
        }
        if (value > 4) {
            input.val(4);
            value = 4;
        }

        // Deshabilitar temporalmente el input durante la petición
        input.prop('disabled', true);

        $.ajax({
            url: "{{ route('nota.auto-save') }}",
            method: "POST",
            data: {
                estudiante_id: input.data('estudiante'),
                criterio_id: input.data('criterio'),
                bimestre_id: input.data('bimestre'),
                nota: value,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                input.removeClass('is-invalid')
                     .addClass('is-valid')
                     .prop('disabled', false);

                setTimeout(() => input.removeClass('is-valid'), 2000);
                toastr.success('Nota guardada correctamente');

                // Debug en consola
                console.log('Nota guardada:', response);
            },
            error: function(xhr) {
                input.addClass('is-invalid')
                     .prop('disabled', false);
                toastr.error('Error al guardar la nota');

                // Debug en consola
                console.error('Error:', xhr.responseText);
            }
        });
    });
});
</script>
@endsection
