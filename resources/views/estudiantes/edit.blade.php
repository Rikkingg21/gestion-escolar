@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Editar Estudiante: {{ $estudiante->user->nombreCompleto }}</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('estudiantes.update', $estudiante->id) }}">
                @csrf
                @method('PUT')
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="mb-3"><i class="bi bi-book me-2"></i>Información Académica</h5>
                    </div>
                    <div class="col-md-4">
                        <label for="grado_id" class="form-label">Nivel - Grado - Sección </label>
                        <select class="form-select" id="grado_id" name="grado_id" required>
                            <option value="">Seleccione grado</option>
                            @foreach($grados as $grado)
                                @if(is_null(optional($estudiante->grado)->nivel) || $grado->nivel == optional($estudiante->grado)->nivel)
                                    <option value="{{ $grado->id }}"
                                        {{ $estudiante->grado_id == $grado->id ? 'selected' : '' }}>
                                        {{ $grado->nivel }} - {{ $grado->grado }}° - {{ $grado->seccion }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Sección de Apoderado -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="mb-3"><i class="bi bi-people-fill me-2"></i>Apoderado</h5>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sin_apoderado"
                                name="sin_apoderado" {{ is_null($estudiante->apoderado_id) ? 'checked' : '' }}>
                            <label class="form-check-label" for="sin_apoderado">
                                El estudiante no tiene apoderado
                            </label>
                        </div>

                        <div id="apoderadoContainer" class="{{ is_null($estudiante->apoderado_id) ? 'd-none' : '' }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Buscar Apoderado</label><br>
                                    <select class="form-select select2-apoderado" id="apoderado_id" name="apoderado_id">
                                        <option value=""></option> <!-- Opción vacía para permitir clear -->
                                        @if($estudiante->apoderado)
                                            <option value="{{ $estudiante->apoderado->id }}" selected>
                                                {{ $estudiante->apoderado->user->nombreCompleto }} (DNI: {{ $estudiante->apoderado->user->dni }})
                                            </option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parentesco</label>
                                    <select name="parentesco" id="" class="form-select">
                                        <option value="padre">Padre</option>
                                        <option value="madre">Madre</option>
                                        <option value="tutor">Tutor</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fecha de Nacimiento -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                            value="{{ $estudiante->fecha_nacimiento ? $estudiante->fecha_nacimiento->format('Y-m-d') : '' }}">
                    </div>
                </div>


                <!-- Botones -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <a href="{{ route('estudiantes.index') }}" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar Select2 para apoderados en español
    $('.select2-apoderado').select2({
        language: "es", // Configura el idioma español
        placeholder: "Buscar apoderado por nombre o DNI",
        allowClear: true,
        ajax: {
            url: '{{ route("apoderados.search") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term
                };
            },
            processResults: function(data) {
                const resultados = data.filter(item => item.user !== null).map(item => {
                    return {
                        id: item.id,
                        text: `${item.user.nombre || ''} ${item.user.apellido_paterno || ''} ${item.user.apellido_materno || ''} (DNI: ${item.user.dni || 'Sin DNI'})`.trim(),
                        parentesco: item.parentesco || ''
                    };
                });

                return {
                    results: resultados
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    // Resto de tu código permanece igual...
    $('.select2-apoderado').on('select2:select', function(e) {
        if(e.params.data && e.params.data.parentesco) {
            $('input[name="parentesco"]').val(e.params.data.parentesco);
        }
    });

    $('#sin_apoderado').change(function() {
        if(this.checked) {
            $('#apoderadoContainer').addClass('d-none');
            $('.select2-apoderado').val(null).trigger('change');
            $('input[name="parentesco"]').val('');
        } else {
            $('#apoderadoContainer').removeClass('d-none');
        }
    }).trigger('change');
});
</script>
<style>
    .select2-container .select2-selection--single {
        height: 38px;
        padding: 5px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endsection
