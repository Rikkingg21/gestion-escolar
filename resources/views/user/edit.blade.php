@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Editar Usuario') }}</div>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="card-body">
                    <form method="POST" action="{{ route('user.update', $user->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Datos básicos del usuario -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dni" class="form-label">DNI</label>
                                    <input type="text" class="form-control @error('dni') is-invalid @enderror"
                                           id="dni" name="dni" value="{{ old('dni', $user->dni) }}" required>
                                    @error('dni')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control @error('nombre_usuario') is-invalid @enderror"
                                       id="nombre_usuario" name="nombre_usuario" value="{{ old('nombre_usuario', $user->nombre_usuario) }}" required>
                                @error('nombre_usuario')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">Nombres</label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $user->nombre) }}" required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
                                <input type="text" class="form-control @error('apellido_paterno') is-invalid @enderror" id="apellido_paterno" name="apellido_paterno" value="{{ old('apellido_paterno', $user->apellido_paterno) }}" required>
                                @error('apellido_paterno')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="apellido_materno" class="form-label">Apellido Materno</label>
                                <input type="text" class="form-control @error('apellido_materno') is-invalid @enderror" id="apellido_materno" name="apellido_materno" value="{{ old('apellido_materno', $user->apellido_materno) }}">
                                @error('apellido_materno')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono', $user->telefono) }}">
                                @error('telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password" placeholder="Dejar en blanco para no cambiar">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                                <option value="1" {{ old('estado', $user->estado) == 1 ? 'selected' : '' }}>Activo</option>
                                <option value="2" {{ old('estado', $user->estado) == 2 ? 'selected' : '' }}>Lector</option>
                                <option value="0" {{ old('estado', $user->estado) == 0 ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="foto_path" class="form-label">Foto de Perfil</label>
                                <input type="file" class="form-control @error('foto_path') is-invalid @enderror" id="foto_path" name="foto_path">
                                @error('foto_path')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select @error('rol') is-invalid @enderror" id="rol" name="rol" required>
                                    <option value="">Seleccione un rol</option>
                                    @php
                                        $currentSessionRole = session('sessionmain') && session('sessionmain')->roles->isNotEmpty() ? session('sessionmain')->roles->first()->nombre : null;
                                    @endphp

                                    @foreach($roles as $role)
                                        @if ($currentSessionRole === 'admin' || $role->id !== 1)
                                            <option value="{{ $role->id }}"
                                                {{ old('rol', $user->roles->first()->id ?? '') == $role->id ? 'selected' : '' }}>
                                                {{ $role->nombre }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('rol')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div><br>


                        <!-- Campos específicos según el rol -->
                        @if($user->hasRole('estudiante'))
                        <div id="campos-estudiante" class="campos-rol">
                            <!-- Campos de estudiante con valores actuales -->
                             <div class="col-md-6">
                                <label for="estado_estudiante" class="form-label">Estado del Estudiante</label>
                                <select class="form-select @error('estado_estudiante') is-invalid @enderror" id="estado_estudiante" name="estado_estudiante">
                                    <option value="1" {{ old('estado_estudiante', $user->estudiante->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado_estudiante', $user->estudiante->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('estado_estudiante')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <input type="hidden" name="estudiante_id" value="{{ $user->estudiante->id ?? '' }}">
                            <div class="row">
                                <h5 class="mt-4 mb-3">Datos del Estudiante</h5>
                                <div class="col-md-6">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                                           id="fecha_nacimiento" name="fecha_nacimiento"
                                           value="{{ old('fecha_nacimiento', $user->estudiante->fecha_nacimiento ?? '') }}">
                                    @error('fecha_nacimiento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="grado_id" class="form-label">Grado</label>
                                    <select class="form-select @error('grado_id') is-invalid @enderror" id="grado_id" name="grado_id">
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
                            <div class="mb-3">
                                <h5 class="mb-3"><i class="bi bi-people-fill me-2"></i>Apoderado</h5>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="sin_apoderado" name="sin_apoderado" {{ old('sin_apoderado') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="sin_apoderado">
                                        El estudiante no tiene apoderado
                                    </label>
                                </div>

                                <div id="apoderadoContainer" class="{{ old('sin_apoderado') ? 'd-none' : '' }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Buscar Apoderado</label>
                                            <select class="form-select select2-apoderado @error('apoderado_id') is-invalid @enderror" id="apoderado_id" name="apoderado_id">
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
                                        <div class="col-md-6">
                                            <label class="form-label">Parentesco</label>
                                            <select name="parentesco" class="form-select @error('parentesco') is-invalid @enderror">
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
                        @endif

                        @if($user->hasRole('apoderado'))
                        <div id="campos-apoderado" class="campos-rol" style="display: none;">
                            <h5 class="mt-4 mb-3">Datos del Apoderado</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="estado_apoderado" class="form-label">Estado del Apoderado</label>
                                    <select class="form-select @error('estado_apoderado') is-invalid @enderror" id="estado_apoderado" name="estado_apoderado">
                                        <option value="1" {{ old('estado_apoderado', $user->apoderado->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('estado_apoderado', $user->apoderado->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('estado_apoderado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="parentesco" class="form-label">Parentesco</label>
                                    <select name="parentesco" class="form-select @error('parentesco') is-invalid @enderror">
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
                        </div><br>
                        @endif

                        @if($user->hasRole('auxiliar'))
                        <div id="campos-auxiliar" class="campos-rol">
                            <div class="row">
                                <h5 class="mt-4 mb-3">Datos del Auxiliar</h5>
                                <div class="col-md-6">
                                    <label for="estado_auxiliar" class="form-label">Estado del Auxiliar</label>
                                    <select class="form-select @error('estado_auxiliar') is-invalid @enderror" id="estado_auxiliar" name="estado_auxiliar">
                                        <option value="1" {{ old('estado_auxiliar', $user->auxiliar->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('estado_auxiliar', $user->auxiliar->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('estado_auxiliar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="turno" class="form-label">Turno (Opcional)</label>
                                    <input type="text" class="form-control @error('turno') is-invalid @enderror"
                                        id="turno" name="turno"
                                        value="{{ old('turno', $user->auxiliar->turno ?? '') }}">
                                    @error('turno')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-12 mt-3">
                                    <label for="funciones" class="form-label">Funciones (Opcional)</label>
                                    <textarea class="form-control @error('funciones') is-invalid @enderror"
                                            id="funciones" name="funciones">{{ old('funciones', $user->auxiliar->funciones ?? '') }}</textarea>
                                    @error('funciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($user->hasRole('docente'))
                        <div id="campos-docente" class="campos-rol">
                            <div class="col-md-12 mt-3">
                                <div class="col-md-6">
                                    <label for="estado_docente" class="form-label">Estado del docente</label>
                                    <select class="form-select @error('estado_docente') is-invalid @enderror" id="estado_docente" name="estado_docente">
                                        <option value="1" {{ old('estado_docente', $user->docente->estado ?? '') == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('estado_docente', $user->docente->estado ?? '') == 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('estado_docente')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div><br>
                        @endif
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Actualizar Usuario') }}
                            </button>
                            <a href="{{ route('user.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Mostrar campos específicos según el rol seleccionado
    document.getElementById('rol').addEventListener('change', function() {
        // Ocultar todos los campos específicos y hacerlos no requeridos
        document.querySelectorAll('.campos-rol').forEach(function(section) {
            section.style.display = 'none';
            section.querySelectorAll('[required]').forEach(function(input) {
                input.required = false;
            });
        });

        // Mostrar los campos correspondientes al rol seleccionado
        const rolId = this.value;
        if (rolId) {
            let sectionToShow = null;
            if (rolId == 6) { // Estudiante
                sectionToShow = document.getElementById('campos-estudiante');
            } else if (rolId == 3) { // Docente
                sectionToShow = document.getElementById('campos-docente');
            } else if (rolId == 5) { // Apoderado
                sectionToShow = document.getElementById('campos-apoderado');
            } else if (rolId == 4) { // Auxiliar
                sectionToShow = document.getElementById('campos-auxiliar');
            }

            if (sectionToShow) {
                sectionToShow.style.display = 'block';
                // Hacer requeridos los campos solo cuando se muestran
                sectionToShow.querySelectorAll('[data-required]').forEach(function(input) {
                    input.required = true;
                });
            }
        }
    });

    // Disparar el evento change al cargar la página si ya hay un rol seleccionado
    window.addEventListener('DOMContentLoaded', function() {
        const rolSelect = document.getElementById('rol');
        if (rolSelect.value) {
            rolSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
<script>
    // Mostrar/ocultar campos de apoderado según checkbox
    document.getElementById('sin_apoderado').addEventListener('change', function() {
        const apoderadoContainer = document.getElementById('apoderadoContainer');
        if (this.checked) {
            apoderadoContainer.classList.add('d-none');
            document.getElementById('apoderado_id').value = '';
        } else {
            apoderadoContainer.classList.remove('d-none');
        }
    });
    $(document).ready(function() {
        $('.select2-apoderado').select2({
            placeholder: "Buscar apoderado...",
            minimumInputLength: 3,
            ajax: {
                url: '{{ route("apoderados.search") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page * 10) < data.total_count
                        }
                    };
                },
                cache: true
            },
            templateResult: formatApoderado,
            templateSelection: formatApoderadoSelection
        });
    });

    function formatApoderado(apoderado) {
        if (apoderado.loading) return apoderado.text;

        var $container = $(
            '<div class="d-flex justify-content-between">' +
                '<span>' + apoderado.nombre_completo + '</span>' +
                '<small class="text-muted">DNI: ' + apoderado.dni + '</small>' +
            '</div>'
        );

        return $container;
    }

    function formatApoderadoSelection(apoderado) {
        if (!apoderado.id) return apoderado.text;
        return apoderado.nombre_completo + ' (DNI: ' + apoderado.dni + ')';
    }
</script>
<script>
    // Script para autollenar nombre de usuario y correo electrónico al ingresar el DNI
    document.getElementById('dni').addEventListener('input', function() {
        const dniValue = this.value.trim();
        if (dniValue) {
            document.getElementById('nombre_usuario').value = dniValue;
            document.getElementById('email').value = dniValue + '@gmail.com';
        } else {
            document.getElementById('nombre_usuario').value = '';
            document.getElementById('email').value = '';
        }
    });
</script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endsection
