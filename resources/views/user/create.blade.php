@extends('layouts.app')

@section('title', 'Crear Usuario')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-plus me-2"></i>Registrar Nuevo Usuario
                    </h5>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>Por favor corrige los siguientes errores:
                            </h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.store') }}">
                        @csrf

                        <!-- Datos básicos del usuario -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-person-badge me-2"></i>Datos Personales
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dni" class="form-label fw-bold">DNI <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('dni') is-invalid @enderror"
                                           id="dni" name="dni" value="{{ old('dni') }}"
                                           required maxlength="8" placeholder="Ingrese 8 dígitos">
                                    @error('dni')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">El DNI será usado como nombre de usuario</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre_usuario" class="form-label fw-bold">Nombre de Usuario <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nombre_usuario') is-invalid @enderror"
                                           id="nombre_usuario" name="nombre_usuario" value="{{ old('nombre_usuario') }}"
                                           required>
                                    @error('nombre_usuario')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label fw-bold">Nombres <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                           id="nombre" name="nombre" value="{{ old('nombre') }}"
                                           required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="apellido_paterno" class="form-label fw-bold">Apellido Paterno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('apellido_paterno') is-invalid @enderror"
                                           id="apellido_paterno" name="apellido_paterno"
                                           value="{{ old('apellido_paterno') }}" required>
                                    @error('apellido_paterno')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="apellido_materno" class="form-label">Apellido Materno</label>
                                    <input type="text" class="form-control @error('apellido_materno') is-invalid @enderror"
                                           id="apellido_materno" name="apellido_materno"
                                           value="{{ old('apellido_materno') }}">
                                    @error('apellido_materno')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email') }}"
                                           required placeholder="ejemplo@correo.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                                           id="telefono" name="telefono" value="{{ old('telefono') }}"
                                           maxlength="9" placeholder="9 dígitos">
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Credenciales -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-shield-lock me-2"></i>Credenciales de Acceso
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-bold">Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                           id="password" name="password" required minlength="8">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">Mínimo 8 caracteres</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label fw-bold">Confirmar Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control"
                                           id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>
                        </div>

                        <!-- Rol y campos específicos -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-person-gear me-2"></i>Rol y Configuración
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="rol" class="form-label fw-bold">Rol del Usuario <span class="text-danger">*</span></label>
                                    <select class="form-select @error('rol') is-invalid @enderror"
                                            id="rol" name="rol" required>
                                        <option value="">Seleccione un rol</option>
                                        @php
                                            $currentSessionRole = session('sessionmain') && session('sessionmain')->roles->isNotEmpty()
                                                ? session('sessionmain')->roles->first()->nombre
                                                : null;
                                        @endphp

                                        @foreach($roles as $role)
                                            @if ($currentSessionRole === 'admin' || $role->id !== 1)
                                                <option value="{{ $role->id }}"
                                                        {{ old('rol') == $role->id ? 'selected' : '' }}
                                                        data-role-name="{{ strtolower($role->nombre) }}">
                                                    {{ $role->nombre }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('rol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Campos específicos por rol -->

                        <!-- Estudiante -->
                        <div id="campos-estudiante" class="campos-rol mb-4" style="display: none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 text-primary">
                                    <h6 class="mb-0">
                                        <i class="bi bi-mortarboard me-2"></i>Datos del Estudiante
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                                                       id="fecha_nacimiento" name="fecha_nacimiento"
                                                       value="{{ old('fecha_nacimiento') }}">
                                                @error('fecha_nacimiento')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="grado_id" class="form-label fw-bold">Grado <span class="text-danger">*</span></label>
                                                <select class="form-select @error('grado_id') is-invalid @enderror"
                                                        id="grado_id" name="grado_id">
                                                    <option value="">Seleccione un grado</option>
                                                    @foreach($grados as $grado)
                                                        <option value="{{ $grado->id }}"
                                                                {{ old('grado_id') == $grado->id ? 'selected' : '' }}>
                                                            {{ $grado->nombre_completo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('grado_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Apoderado -->
                                    <div class="mb-3">
                                        <h6 class="mb-3">
                                            <i class="bi bi-people-fill me-2"></i>Apoderado
                                        </h6>

                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox"
                                                   id="sin_apoderado" name="sin_apoderado"
                                                   {{ old('sin_apoderado') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sin_apoderado">
                                                El estudiante no tiene apoderado
                                            </label>
                                        </div>

                                        <div id="apoderadoContainer" class="{{ old('sin_apoderado') ? 'd-none' : '' }}">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Buscar Apoderado</label>
                                                        <select class="form-select select2-apoderado @error('apoderado_id') is-invalid @enderror"
                                                                id="apoderado_id" name="apoderado_id">
                                                            <option value=""></option>
                                                            @if(old('apoderado_id'))
                                                                @php
                                                                    $apoderado = \App\Models\Apoderado::with('user')->find(old('apoderado_id'));
                                                                @endphp
                                                                @if($apoderado)
                                                                    <option value="{{ $apoderado->id }}" selected>
                                                                        {{ $apoderado->user->nombre }} {{ $apoderado->user->apellido_paterno }} (DNI: {{ $apoderado->user->dni }})
                                                                    </option>
                                                                @endif
                                                            @endif
                                                        </select>
                                                        @error('apoderado_id')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Parentesco</label>
                                                        <select name="parentesco"
                                                                class="form-select @error('parentesco') is-invalid @enderror">
                                                            <option value="">Seleccione parentesco</option>
                                                            <option value="padre" {{ old('parentesco') == 'padre' ? 'selected' : '' }}>Padre</option>
                                                            <option value="madre" {{ old('parentesco') == 'madre' ? 'selected' : '' }}>Madre</option>
                                                            <option value="tutor" {{ old('parentesco') == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                                            <option value="otro" {{ old('parentesco') == 'otro' ? 'selected' : '' }}>Otro</option>
                                                        </select>
                                                        @error('parentesco')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Apoderado -->
                        <div id="campos-apoderado" class="campos-rol mb-4" style="display: none;">
                            <div class="card border-info">
                                <div class="card-header bg-info bg-opacity-10 text-info">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-bounding-box me-2"></i>Datos del Apoderado
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Parentesco <span class="text-danger">*</span></label>
                                            <select name="parentesco"
                                                    class="form-select @error('parentesco') is-invalid @enderror">
                                                <option value="">Seleccione parentesco</option>
                                                <option value="padre" {{ old('parentesco') == 'padre' ? 'selected' : '' }}>Padre</option>
                                                <option value="madre" {{ old('parentesco') == 'madre' ? 'selected' : '' }}>Madre</option>
                                                <option value="tutor" {{ old('parentesco') == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                                <option value="otro" {{ old('parentesco') == 'otro' ? 'selected' : '' }}>Otro</option>
                                            </select>
                                            @error('parentesco')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Auxiliar -->
                        <div id="campos-auxiliar" class="campos-rol mb-4" style="display: none;">
                            <div class="card border-warning">
                                <div class="card-header bg-warning bg-opacity-10 text-warning">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-workspace me-2"></i>Datos del Auxiliar
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="turno" class="form-label">Turno</label>
                                                <select class="form-select @error('turno') is-invalid @enderror"
                                                        id="turno" name="turno">
                                                    <option value="">Seleccione turno</option>
                                                    <option value="mañana" {{ old('turno') == 'mañana' ? 'selected' : '' }}>Mañana</option>
                                                    <option value="tarde" {{ old('turno') == 'tarde' ? 'selected' : '' }}>Tarde</option>
                                                    <option value="completo" {{ old('turno') == 'completo' ? 'selected' : '' }}>Completo</option>
                                                </select>
                                                @error('turno')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="funciones" class="form-label">Funciones</label>
                                                <input type="text" class="form-control @error('funciones') is-invalid @enderror"
                                                       id="funciones" name="funciones"
                                                       value="{{ old('funciones') }}"
                                                       placeholder="Ej: Limpieza, Vigilancia">
                                                @error('funciones')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('user.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i>Registrar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #dee2e6;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-container--default .select2-selection--single {
        border-radius: 0.375rem;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .form-text {
        font-size: 0.875rem;
        color: #6c757d;
    }
    .border-bottom {
        border-color: #dee2e6 !important;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Autollenar nombre de usuario y email con DNI
    $('#dni').on('input', function() {
        const dniValue = $(this).val().trim();
        if (dniValue) {
            $('#nombre_usuario').val(dniValue);
            $('#email').val(dniValue + '@gmail.com');
        } else {
            $('#nombre_usuario').val('');
            $('#email').val('');
        }
    });

    // Mostrar campos según rol
    $('#rol').change(function() {
        const rolId = $(this).val();

        // Ocultar todos los campos específicos
        $('.campos-rol').hide();

        // Remover atributo required de campos específicos
        $('.campos-rol').find('select, input').removeAttr('required');

        // Mostrar campos según el rol
        if (rolId == 6) { // Estudiante
            $('#campos-estudiante').show();
            $('#grado_id').attr('required', true);
        } else if (rolId == 5) { // Apoderado
            $('#campos-apoderado').show();
            $('#campos-apoderado select[name="parentesco"]').attr('required', true);
        } else if (rolId == 4) { // Auxiliar
            $('#campos-auxiliar').show();
        }

        // Forzar actualización de validación
        $(this).trigger('blur');
    });

    // Apoderado checkbox
    $('#sin_apoderado').change(function() {
        if ($(this).is(':checked')) {
            $('#apoderadoContainer').addClass('d-none');
            $('#apoderado_id').val('').trigger('change');
            $('#apoderadoContainer select[name="parentesco"]').val('');
        } else {
            $('#apoderadoContainer').removeClass('d-none');
        }
    });

    // Select2 para apoderados
    $('.select2-apoderado').select2({
        placeholder: "Buscar apoderado...",
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("apoderados.search") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items || [],
                    pagination: {
                        more: (params.page * 10) < (data.total_count || 0)
                    }
                };
            },
            cache: true
        },
        templateResult: function(apoderado) {
            if (apoderado.loading) return apoderado.text;

            var $container = $(
                '<div class="d-flex justify-content-between align-items-center">' +
                    '<span>' + (apoderado.nombre_completo || apoderado.text) + '</span>' +
                    '<small class="text-muted ms-2">DNI: ' + (apoderado.dni || '') + '</small>' +
                '</div>'
            );
            return $container;
        },
        templateSelection: function(apoderado) {
            if (!apoderado.id) return apoderado.text || 'Buscar apoderado...';
            return (apoderado.nombre_completo || apoderado.text) +
                   (apoderado.dni ? ' (DNI: ' + apoderado.dni + ')' : '');
        }
    });

    // Si hay error de validación, mostrar los campos correspondientes
    @if(old('rol'))
        $('#rol').trigger('change');
    @endif

    @if(old('sin_apoderado'))
        $('#sin_apoderado').trigger('change');
    @endif
});
</script>
@endsection
