@extends('layouts.app')

@section('title', 'Editar Usuario')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Editar Usuario
                    </h5>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

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

                    <form method="POST" action="{{ route('user.update', $user->id) }}">
                        @csrf
                        @method('PUT')

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
                                           id="dni" name="dni" value="{{ old('dni', $user->dni) }}"
                                           required maxlength="8">
                                    @error('dni')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre_usuario" class="form-label fw-bold">Nombre de Usuario <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nombre_usuario') is-invalid @enderror"
                                           id="nombre_usuario" name="nombre_usuario"
                                           value="{{ old('nombre_usuario', $user->nombre_usuario) }}" required>
                                    @error('nombre_usuario')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label fw-bold">Nombres <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                           id="nombre" name="nombre"
                                           value="{{ old('nombre', $user->nombre) }}" required>
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
                                           value="{{ old('apellido_paterno', $user->apellido_paterno) }}" required>
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
                                           value="{{ old('apellido_materno', $user->apellido_materno) }}">
                                    @error('apellido_materno')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                                           id="telefono" name="telefono"
                                           value="{{ old('telefono', $user->telefono) }}"
                                           maxlength="9">
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
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                           id="password" name="password"
                                           placeholder="Dejar en blanco para no cambiar">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">Mínimo 8 caracteres. Déjelo vacío si no desea cambiarla.</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control"
                                           id="password_confirmation" name="password_confirmation">
                                </div>
                            </div>
                        </div>

                        <!-- Rol y Estado -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estado" class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select @error('estado') is-invalid @enderror"
                                            id="estado" name="estado" required>
                                        <option value="1" {{ old('estado', $user->estado) == '1' ? 'selected' : '' }}>Activo</option>
                                        <option value="2" {{ old('estado', $user->estado) == '2' ? 'selected' : '' }}>Lector</option>
                                        <option value="0" {{ old('estado', $user->estado) == '0' ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label fw-bold">Rol <span class="text-danger">*</span></label>
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
                                                    {{ old('rol', $user->roles->first()->id ?? '') == $role->id ? 'selected' : '' }}
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

                        <!-- Campos dinámicos según rol actual -->
                        <!-- Se muestran según el rol actual del usuario -->

                        <!-- Estudiante -->
                        @if($user->estudiante)
                        <div id="campos-estudiante" class="campos-rol mb-4">
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
                                                <label for="estado_estudiante" class="form-label">Estado del Estudiante</label>
                                                <select class="form-select @error('estado_estudiante') is-invalid @enderror"
                                                        id="estado_estudiante" name="estado_estudiante">
                                                    <option value="1" {{ old('estado_estudiante', $user->estudiante->estado ?? '') == '1' ? 'selected' : '' }}>Activo</option>
                                                    <option value="0" {{ old('estado_estudiante', $user->estudiante->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                </select>
                                                @error('estado_estudiante')
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
                                                            {{ old('grado_id', $user->estudiante->grado_id ?? '') == $grado->id ? 'selected' : '' }}>
                                                            {{ $grado->nombre_completo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('grado_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                                                       id="fecha_nacimiento" name="fecha_nacimiento"
                                                       value="{{ old('fecha_nacimiento', $user->estudiante->fecha_nacimiento ?? '') }}">
                                                @error('fecha_nacimiento')
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
                                                   {{ old('sin_apoderado', !$user->estudiante->apoderado_id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sin_apoderado">
                                                El estudiante no tiene apoderado
                                            </label>
                                        </div>

                                        <div id="apoderadoContainer" class="{{ old('sin_apoderado', !$user->estudiante->apoderado_id) ? 'd-none' : '' }}">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Buscar Apoderado</label>
                                                        <select class="form-select select2-apoderado @error('apoderado_id') is-invalid @enderror"
                                                                id="apoderado_id" name="apoderado_id">
                                                            <option value=""></option>
                                                            @if(old('apoderado_id', $user->estudiante->apoderado_id))
                                                                @php
                                                                    $apoderado = \App\Models\Apoderado::with('user')
                                                                        ->find(old('apoderado_id', $user->estudiante->apoderado_id));
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
                                                            <option value="padre" {{ old('parentesco', $user->estudiante->parentesco ?? '') == 'padre' ? 'selected' : '' }}>Padre</option>
                                                            <option value="madre" {{ old('parentesco', $user->estudiante->parentesco ?? '') == 'madre' ? 'selected' : '' }}>Madre</option>
                                                            <option value="tutor" {{ old('parentesco', $user->estudiante->parentesco ?? '') == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                                            <option value="otro" {{ old('parentesco', $user->estudiante->parentesco ?? '') == 'otro' ? 'selected' : '' }}>Otro</option>
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
                        @endif

                        <!-- Apoderado -->
                        @if($user->apoderado)
                        <div id="campos-apoderado" class="campos-rol mb-4">
                            <div class="card border-info">
                                <div class="card-header bg-info bg-opacity-10 text-info">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-bounding-box me-2"></i>Datos del Apoderado
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="estado_apoderado" class="form-label">Estado del Apoderado</label>
                                                <select class="form-select @error('estado_apoderado') is-invalid @enderror"
                                                        id="estado_apoderado" name="estado_apoderado">
                                                    <option value="1" {{ old('estado_apoderado', $user->apoderado->estado ?? '') == '1' ? 'selected' : '' }}>Activo</option>
                                                    <option value="0" {{ old('estado_apoderado', $user->apoderado->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                </select>
                                                @error('estado_apoderado')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Parentesco <span class="text-danger">*</span></label>
                                                <select name="parentesco"
                                                        class="form-select @error('parentesco') is-invalid @enderror">
                                                    <option value="">Seleccione parentesco</option>
                                                    <option value="padre" {{ old('parentesco', $user->apoderado->parentesco ?? '') == 'padre' ? 'selected' : '' }}>Padre</option>
                                                    <option value="madre" {{ old('parentesco', $user->apoderado->parentesco ?? '') == 'madre' ? 'selected' : '' }}>Madre</option>
                                                    <option value="tutor" {{ old('parentesco', $user->apoderado->parentesco ?? '') == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                                    <option value="otro" {{ old('parentesco', $user->apoderado->parentesco ?? '') == 'otro' ? 'selected' : '' }}>Otro</option>
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
                        @endif

                        <!-- Auxiliar -->
                        @if($user->auxiliar)
                        <div id="campos-auxiliar" class="campos-rol mb-4">
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
                                                <label for="estado_auxiliar" class="form-label">Estado del Auxiliar</label>
                                                <select class="form-select @error('estado_auxiliar') is-invalid @enderror"
                                                        id="estado_auxiliar" name="estado_auxiliar">
                                                    <option value="1" {{ old('estado_auxiliar', $user->auxiliar->estado ?? '') == '1' ? 'selected' : '' }}>Activo</option>
                                                    <option value="0" {{ old('estado_auxiliar', $user->auxiliar->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                </select>
                                                @error('estado_auxiliar')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="turno" class="form-label">Turno</label>
                                                <select class="form-select @error('turno') is-invalid @enderror"
                                                        id="turno" name="turno">
                                                    <option value="">Seleccione turno</option>
                                                    <option value="mañana" {{ old('turno', $user->auxiliar->turno ?? '') == 'mañana' ? 'selected' : '' }}>Mañana</option>
                                                    <option value="tarde" {{ old('turno', $user->auxiliar->turno ?? '') == 'tarde' ? 'selected' : '' }}>Tarde</option>
                                                    <option value="completo" {{ old('turno', $user->auxiliar->turno ?? '') == 'completo' ? 'selected' : '' }}>Completo</option>
                                                </select>
                                                @error('turno')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label for="funciones" class="form-label">Funciones</label>
                                                <input type="text" class="form-control @error('funciones') is-invalid @enderror"
                                                       id="funciones" name="funciones"
                                                       value="{{ old('funciones', $user->auxiliar->funciones ?? '') }}">
                                                @error('funciones')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Docente -->
                        @if($user->docente)
                        <div id="campos-docente" class="campos-rol mb-4">
                            <div class="card border-success">
                                <div class="card-header bg-success bg-opacity-10 text-success">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-video3 me-2"></i>Datos del Docente
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="estado_docente" class="form-label">Estado del Docente</label>
                                                <select class="form-select @error('estado_docente') is-invalid @enderror"
                                                        id="estado_docente" name="estado_docente">
                                                    <option value="1" {{ old('estado_docente', $user->docente->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
                                                    <option value="0" {{ old('estado_docente', $user->docente->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
                                                </select>
                                                @error('estado_docente')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Botones -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('user.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-check-circle me-1"></i>Actualizar Usuario
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
    .form-text {
        font-size: 0.875rem;
        color: #6c757d;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Mostrar campos según rol seleccionado
    $('#rol').change(function() {
        const rolId = $(this).val();

        // Ocultar todos los campos específicos
        $('.campos-rol').hide();

        // Mostrar campos según el rol seleccionado
        if (rolId == 6) { // Estudiante
            $('#campos-estudiante').show();
            $('#grado_id').attr('required', true);
        } else if (rolId == 5) { // Apoderado
            $('#campos-apoderado').show();
            $('#campos-apoderado select[name="parentesco"]').attr('required', true);
        } else if (rolId == 4) { // Auxiliar
            $('#campos-auxiliar').show();
        } else if (rolId == 3) { // Docente
            $('#campos-docente').show();
        }
    });

    // Inicializar Select2 para apoderados
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

            return $(
                '<div class="d-flex justify-content-between align-items-center">' +
                    '<span>' + (apoderado.nombre_completo || apoderado.text) + '</span>' +
                    '<small class="text-muted ms-2">DNI: ' + (apoderado.dni || '') + '</small>' +
                '</div>'
            );
        },
        templateSelection: function(apoderado) {
            if (!apoderado.id) return apoderado.text || 'Buscar apoderado...';
            return (apoderado.nombre_completo || apoderado.text) +
                   (apoderado.dni ? ' (DNI: ' + apoderado.dni + ')' : '');
        }
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

    // Si hay error de validación, mostrar los campos según el rol seleccionado
    @if(old('rol'))
        $('#rol').trigger('change');
    @endif

    // Si no hay rol en old() pero el usuario tiene un rol, disparar el cambio
    @if(!old('rol') && $user->roles->first())
        setTimeout(function() {
            $('#rol').trigger('change');
        }, 100);
    @endif

    @if(old('sin_apoderado') || (!$user->estudiante || !$user->estudiante->apoderado_id))
        $('#sin_apoderado').trigger('change');
    @endif
});
</script>
@endsection
