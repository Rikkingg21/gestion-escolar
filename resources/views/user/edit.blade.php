@extends('layouts.app')

@section('title', 'Editar Usuario')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Editar Usuario: {{ $user->nombre }} {{ $user->apellido_paterno }}
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

                    <form method="POST" action="{{ route('user.update', $user->id) }}" id="form-editar-usuario">
                        @csrf
                        @method('PUT')

                        <!-- Datos básicos -->
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
                                           value="{{ old('telefono', $user->telefono) }}" maxlength="9">
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
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Deje estos campos en blanco si no desea cambiar la contraseña.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                           id="password" name="password"
                                           placeholder="Dejar en blanco para no cambiar"
                                           minlength="8">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control"
                                           id="password_confirmation" name="password_confirmation"
                                           placeholder="Confirmar nueva contraseña">
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
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
                        </div>

                        <!-- Roles -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-person-badge me-2"></i>Roles y Campos Específicos
                                </h6>

                                <div id="rolesContainer">
                                    @php
                                        $userRoles = $user->roles->pluck('id')->toArray();
                                        $oldRoles = old('roles', $userRoles);
                                        $currentSessionRole = session('sessionmain')->roles->first()->nombre ?? null;
                                        $rolesDisponibles = $roles->whereNotIn('id', $userRoles);

                                        if ($currentSessionRole !== 'admin') {
                                            $rolesDisponibles = $rolesDisponibles->where('id', '!=', 1);
                                        }

                                        $datosEspecificos = [
                                            'estudiante' => $user->estudiante,
                                            'docente' => $user->docente,
                                            'apoderado' => $user->apoderado,
                                            'auxiliar' => $user->auxiliar,
                                            'director' => $user->director
                                        ];
                                    @endphp

                                    @foreach($oldRoles as $index => $roleId)
                                        @php
                                            $rol = $roles->where('id', $roleId)->first();
                                            $rolNombre = strtolower($rol->nombre ?? '');
                                            $datosRol = $datosEspecificos[$rolNombre] ?? null;
                                        @endphp
                                        <div class="rol-item mb-4 p-3 border rounded-3 bg-light border-2 {{ $index === 0 ? 'border-primary' : 'border-secondary' }}"
                                             data-role-id="{{ $roleId }}" data-role-name="{{ $rolNombre }}">
                                            <div class="row align-items-center mb-3">
                                                <div class="col-md-12">
                                                    <label class="form-label {{ $index === 0 ? 'fw-bold text-dark' : 'text-muted' }} d-block">
                                                        {{ $index === 0 ? 'Rol Principal' : 'Rol Adicional ' . ($index + 1) }}
                                                    </label>
                                                    <input type="hidden" name="roles[]" value="{{ $roleId }}">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-primary me-2">{{ $rol->nombre ?? 'Rol' }}</span>
                                                        @if($index === 0)
                                                            <span class="badge bg-success">Principal</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Campos específicos -->
                                            <div class="campos-especificos">
                                                @switch($rolNombre)
                                                    @case('estudiante')
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Grado</label>
                                                                    <select name="estudiante_grado[{{ $index }}]" class="form-select @error('estudiante_grado.' . $index) is-invalid @enderror">
                                                                        <option value="">Seleccionar grado</option>
                                                                        @foreach($grados as $grado)
                                                                            <option value="{{ $grado->id }}" {{ old('estudiante_grado.' . $index, $datosRol->grado_id ?? '') == $grado->id ? 'selected' : '' }}>
                                                                                {{ $grado->nombre }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    @error('estudiante_grado.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Fecha de Nacimiento</label>
                                                                    <input type="date" name="estudiante_fecha_nacimiento[{{ $index }}]"
                                                                           class="form-control @error('estudiante_fecha_nacimiento.' . $index) is-invalid @enderror"
                                                                           value="{{ old('estudiante_fecha_nacimiento.' . $index, $datosRol->fecha_nacimiento ?? '') }}">
                                                                    @error('estudiante_fecha_nacimiento.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @break

                                                    @case('docente')
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Estado del Docente</label>
                                                                    <select name="docente_estado[{{ $index }}]" class="form-select @error('docente_estado.' . $index) is-invalid @enderror">
                                                                        <option value="1" {{ old('docente_estado.' . $index, $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                        <option value="0" {{ old('docente_estado.' . $index, $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                    </select>
                                                                    @error('docente_estado.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @break

                                                    @case('apoderado')
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Parentesco</label>
                                                                    <input type="text" name="apoderado_parentesco[{{ $index }}]"
                                                                           class="form-control @error('apoderado_parentesco.' . $index) is-invalid @enderror"
                                                                           value="{{ old('apoderado_parentesco.' . $index, $datosRol->parentesco ?? '') }}"
                                                                           placeholder="Ej: Padre, Madre, Tutor">
                                                                    @error('apoderado_parentesco.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Estado del Apoderado</label>
                                                                    <select name="apoderado_estado[{{ $index }}]" class="form-select @error('apoderado_estado.' . $index) is-invalid @enderror">
                                                                        <option value="1" {{ old('apoderado_estado.' . $index, $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                        <option value="0" {{ old('apoderado_estado.' . $index, $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                    </select>
                                                                    @error('apoderado_estado.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @break

                                                    @case('auxiliar')
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Turno</label>
                                                                    <select name="auxiliar_turno[{{ $index }}]" class="form-select @error('auxiliar_turno.' . $index) is-invalid @enderror">
                                                                        <option value="mañana" {{ old('auxiliar_turno.' . $index, $datosRol->turno ?? '') == 'mañana' ? 'selected' : '' }}>Mañana</option>
                                                                        <option value="tarde" {{ old('auxiliar_turno.' . $index, $datosRol->turno ?? '') == 'tarde' ? 'selected' : '' }}>Tarde</option>
                                                                        <option value="noche" {{ old('auxiliar_turno.' . $index, $datosRol->turno ?? '') == 'noche' ? 'selected' : '' }}>Noche</option>
                                                                    </select>
                                                                    @error('auxiliar_turno.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Estado del Auxiliar</label>
                                                                    <select name="auxiliar_estado[{{ $index }}]" class="form-select @error('auxiliar_estado.' . $index) is-invalid @enderror">
                                                                        <option value="1" {{ old('auxiliar_estado.' . $index, $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                        <option value="0" {{ old('auxiliar_estado.' . $index, $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                    </select>
                                                                    @error('auxiliar_estado.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Funciones</label>
                                                                    <textarea name="auxiliar_funciones[{{ $index }}]"
                                                                              class="form-control @error('auxiliar_funciones.' . $index) is-invalid @enderror"
                                                                              rows="2">{{ old('auxiliar_funciones.' . $index, $datosRol->funciones ?? '') }}</textarea>
                                                                    @error('auxiliar_funciones.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @break

                                                    @case('director')
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Estado del Director</label>
                                                                    <select name="director_estado[{{ $index }}]" class="form-select @error('director_estado.' . $index) is-invalid @enderror">
                                                                        <option value="1" {{ old('director_estado.' . $index, $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                        <option value="0" {{ old('director_estado.' . $index, $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                    </select>
                                                                    @error('director_estado.' . $index)
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @break
                                                @endswitch
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- Nuevos roles -->
                                    <div id="nuevosRolesContainer"></div>

                                    <!-- Agregar nuevo rol -->
                                    @if($rolesDisponibles->count() > 0)
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label class="form-label">Agregar nuevo rol:</label>
                                                <select class="form-select" id="selectNuevoRol">
                                                    <option value="">Seleccione un rol para agregar</option>
                                                    @foreach($rolesDisponibles as $rol)
                                                        <option value="{{ $rol->id }}" data-name="{{ strtolower($rol->nombre) }}">
                                                            {{ $rol->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="button" id="btnAgregarRol" class="btn btn-primary w-100">
                                                    <i class="bi bi-plus-circle me-1"></i>Agregar Rol
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

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
@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('#selectNuevoRol').select2({
        placeholder: "Seleccione un rol",
        allowClear: true
    });

    // Templates simples
    const roleTemplates = {
        'estudiante': (index) => `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Grado</label>
                        <select name="estudiante_grado[${index}]" class="form-select">
                            <option value="">Seleccionar grado</option>
                            @foreach($grados as $grado)
                            <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Fecha de Nacimiento</label>
                        <input type="date" name="estudiante_fecha_nacimiento[${index}]" class="form-control">
                    </div>
                </div>
            </div>
        `,
        'docente': (index) => `
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="docente_estado[${index}]" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
        `,
        'apoderado': (index) => `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Parentesco</label>
                        <input type="text" name="apoderado_parentesco[${index}]" class="form-control" placeholder="Ej: Padre, Madre, Tutor">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="apoderado_estado[${index}]" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
        `,
        'auxiliar': (index) => `
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Turno</label>
                        <select name="auxiliar_turno[${index}]" class="form-select">
                            <option value="mañana">Mañana</option>
                            <option value="tarde">Tarde</option>
                            <option value="noche">Noche</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="auxiliar_estado[${index}]" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Funciones</label>
                        <textarea name="auxiliar_funciones[${index}]" class="form-control" rows="2" placeholder="Descripción de funciones"></textarea>
                    </div>
                </div>
            </div>
        `,
        'director': (index) => `
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="director_estado[${index}]" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
        `
    };

    // Agregar nuevo rol
    $('#btnAgregarRol').click(function() {
        const selectRol = $('#selectNuevoRol');
        const rolId = selectRol.val();
        const rolOption = selectRol.find('option:selected');
        const rolNombre = rolOption.data('name');

        if (!rolId) {
            alert('Por favor seleccione un rol');
            return;
        }

        if ($(`input[name="roles[]"][value="${rolId}"]`).length > 0) {
            alert('Este rol ya está asignado al usuario');
            return;
        }

        const totalRoles = $('.rol-item').length;
        const template = roleTemplates[rolNombre] ? roleTemplates[rolNombre](totalRoles)
            : '<div class="alert alert-info">No hay campos específicos para este rol.</div>';

        const nuevoRolHtml = `
            <div class="rol-item mb-4 p-3 border rounded-3 bg-light border-secondary"
                 data-role-id="${rolId}"
                 data-role-name="${rolNombre}"
                 data-new="true">
                <div class="row align-items-center mb-3">
                    <div class="col-md-12">
                        <label class="form-label text-muted d-block">
                            Rol Adicional ${totalRoles + 1}
                        </label>
                        <input type="hidden" name="roles[]" value="${rolId}">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2">${rolOption.text()}</span>
                            <span class="badge bg-info">Nuevo</span>
                        </div>
                    </div>
                </div>
                <div class="campos-especificos">
                    ${template}
                </div>
            </div>
        `;

        $('#nuevosRolesContainer').append(nuevoRolHtml);
        rolOption.prop('disabled', true);
        selectRol.val('').trigger('change');
    });
});
</script>
@endsection
