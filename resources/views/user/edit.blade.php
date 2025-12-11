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

                                        // Separar roles existentes
                                        $rolesExistentes = [];
                                        $rolesNuevos = [];

                                        // Usar old('nuevos_roles', []) para los roles nuevos
                                        $nuevosRolesInput = old('nuevos_roles', []);

                                        foreach ($oldRoles as $roleId) {
                                            // Si el rol está en los roles del usuario original, es existente
                                            if (in_array($roleId, $userRoles)) {
                                                $rolesExistentes[] = $roleId;
                                            } else {
                                                $rolesNuevos[] = $roleId;
                                            }
                                        }
                                    @endphp

                                    <!-- Roles Existentes -->
                                    <div id="rolesExistentesContainer">
                                        @foreach($rolesExistentes as $roleId)
                                            @php
                                                $rol = $roles->where('id', $roleId)->first();
                                                $rolNombre = strtolower($rol->nombre ?? '');
                                                $datosRol = $datosEspecificos[$rolNombre] ?? null;
                                            @endphp
                                            <div class="rol-item mb-4 p-3 border rounded-3 bg-light border-primary"
                                                data-role-id="{{ $roleId }}" data-role-name="{{ $rolNombre }}" data-tipo="existente">
                                                <div class="row align-items-center mb-3">
                                                    <div class="col-md-12">
                                                        <label class="form-label fw-bold text-dark d-block">
                                                            Rol Existente
                                                        </label>
                                                        <input type="hidden" name="roles[]" value="{{ $roleId }}">
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-primary me-2">{{ $rol->nombre ?? 'Rol' }}</span>
                                                            <span class="badge bg-success">Existente</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Campos específicos para roles existentes (SIN ÍNDICES) -->
                                                <div class="campos-especificos">
                                                    @switch($rolNombre)
                                                        @case('estudiante')
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Grado <span class="text-danger">*</span></label>
                                                                        <select name="estudiante_grado"
                                                                                class="form-select @error('estudiante_grado') is-invalid @enderror" required>
                                                                            <option value="">Seleccionar grado</option>
                                                                            @foreach($grados as $grado)
                                                                                <option value="{{ $grado->id }}"
                                                                                    {{ old('estudiante_grado', $datosRol->grado_id ?? '') == $grado->id ? 'selected' : '' }}>
                                                                                    {{ $grado->nombreCompleto ?? $grado->grado . '° ' . $grado->seccion . ' - ' . $grado->nivel }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                        @error('estudiante_grado')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Apoderado</label>
                                                                        <select name="estudiante_apoderado"
                                                                                class="form-select select2-apoderado @error('estudiante_apoderado') is-invalid @enderror">
                                                                            <option value="">Buscar apoderado...</option>
                                                                            @if($datosRol && $datosRol->apoderado)
                                                                                <option value="{{ $datosRol->apoderado_id }}" selected>
                                                                                    {{ $datosRol->apoderado->user->nombre ?? '' }}
                                                                                    {{ $datosRol->apoderado->user->apellido_paterno ?? '' }}
                                                                                    (DNI: {{ $datosRol->apoderado->user->dni ?? '' }})
                                                                                </option>
                                                                            @endif
                                                                        </select>
                                                                        @error('estudiante_apoderado')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                                                        <input type="date" name="estudiante_fecha_nacimiento"
                                                                            class="form-control @error('estudiante_fecha_nacimiento') is-invalid @enderror"
                                                                            value="{{ old('estudiante_fecha_nacimiento', $datosRol->fecha_nacimiento ?? '') }}" required>
                                                                        @error('estudiante_fecha_nacimiento')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                                                                        <select name="estudiante_estado"
                                                                                class="form-select @error('estudiante_estado') is-invalid @enderror" required>
                                                                            <option value="1" {{ old('estudiante_estado', $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('estudiante_estado', $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('estudiante_estado')
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
                                                                        <label class="form-label">Estado del Docente <span class="text-danger">*</span></label>
                                                                        <select name="docente_estado"
                                                                                class="form-select @error('docente_estado') is-invalid @enderror" required>
                                                                            <option value="1" {{ old('docente_estado', $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('docente_estado', $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('docente_estado')
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
                                                                        <label class="form-label">Parentesco <span class="text-danger">*</span></label>
                                                                        <input type="text" name="apoderado_parentesco"
                                                                            class="form-control @error('apoderado_parentesco') is-invalid @enderror"
                                                                            value="{{ old('apoderado_parentesco', $datosRol->parentesco ?? '') }}"
                                                                            placeholder="Ej: Padre, Madre, Tutor" required>
                                                                        @error('apoderado_parentesco')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Estado del Apoderado <span class="text-danger">*</span></label>
                                                                        <select name="apoderado_estado"
                                                                                class="form-select @error('apoderado_estado') is-invalid @enderror" required>
                                                                            <option value="1" {{ old('apoderado_estado', $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('apoderado_estado', $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('apoderado_estado')
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
                                                                        <label class="form-label">Turno <span class="text-danger">*</span></label>
                                                                        <select name="auxiliar_turno"
                                                                                class="form-select @error('auxiliar_turno') is-invalid @enderror" required>
                                                                            <option value="">Seleccionar turno</option>
                                                                            <option value="mañana" {{ old('auxiliar_turno', $datosRol->turno ?? '') == 'mañana' ? 'selected' : '' }}>Mañana</option>
                                                                            <option value="tarde" {{ old('auxiliar_turno', $datosRol->turno ?? '') == 'tarde' ? 'selected' : '' }}>Tarde</option>
                                                                            <option value="noche" {{ old('auxiliar_turno', $datosRol->turno ?? '') == 'noche' ? 'selected' : '' }}>Noche</option>
                                                                        </select>
                                                                        @error('auxiliar_turno')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Estado del Auxiliar <span class="text-danger">*</span></label>
                                                                        <select name="auxiliar_estado"
                                                                                class="form-select @error('auxiliar_estado') is-invalid @enderror" required>
                                                                            <option value="1" {{ old('auxiliar_estado', $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('auxiliar_estado', $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('auxiliar_estado')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Funciones</label>
                                                                        <textarea name="auxiliar_funciones"
                                                                                class="form-control @error('auxiliar_funciones') is-invalid @enderror"
                                                                                rows="2">{{ old('auxiliar_funciones', $datosRol->funciones ?? '') }}</textarea>
                                                                        @error('auxiliar_funciones')
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
                                                                        <label class="form-label">Estado del Director <span class="text-danger">*</span></label>
                                                                        <select name="director_estado"
                                                                                class="form-select @error('director_estado') is-invalid @enderror" required>
                                                                            <option value="1" {{ old('director_estado', $datosRol->estado ?? '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('director_estado', $datosRol->estado ?? '') == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('director_estado')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @break

                                                        @default
                                                            <div class="alert alert-info">
                                                                <i class="bi bi-info-circle me-2"></i>
                                                                Este rol no requiere campos adicionales.
                                                            </div>
                                                    @endswitch
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Roles Nuevos (se agregan dinámicamente) -->
                                    <div id="nuevosRolesContainer">
                                        @php
                                            // Para cada rol nuevo que ya fue agregado (en caso de error de validación)
                                            $nuevoRolCounter = 0;
                                        @endphp

                                        @foreach($rolesNuevos as $roleId)
                                            @php
                                                $rol = $roles->where('id', $roleId)->first();
                                                $rolNombre = strtolower($rol->nombre ?? '');
                                                $datosRol = null; // Los nuevos roles no tienen datos previos
                                            @endphp
                                            <div class="rol-item mb-4 p-3 border rounded-3 bg-light border-secondary"
                                                data-role-id="{{ $roleId }}" data-role-name="{{ $rolNombre }}" data-tipo="nuevo">
                                                <div class="row align-items-center mb-3">
                                                    <div class="col-md-10">
                                                        <label class="form-label text-muted d-block">
                                                            Nuevo Rol
                                                        </label>
                                                        <input type="hidden" name="nuevos_roles[]" value="{{ $roleId }}">
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-primary me-2">{{ $rol->nombre ?? 'Rol' }}</span>
                                                            <span class="badge bg-info">Nuevo</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 text-end">
                                                        <button type="button" class="btn btn-sm btn-danger btn-remover-nuevo-rol">
                                                            <i class="bi bi-trash"></i> Quitar
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Campos específicos para roles NUEVOS (con índice único) -->
                                                <div class="campos-especificos">
                                                    @switch($rolNombre)
                                                        @case('estudiante')
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Grado <span class="text-danger">*</span></label>
                                                                        <select name="nuevo_estudiante_grado[{{ $nuevoRolCounter }}]"
                                                                                class="form-select @error('nuevo_estudiante_grado.' . $nuevoRolCounter) is-invalid @enderror" required>
                                                                            <option value="">Seleccionar grado</option>
                                                                            @foreach($grados as $grado)
                                                                                <option value="{{ $grado->id }}" {{ old('nuevo_estudiante_grado.' . $nuevoRolCounter) == $grado->id ? 'selected' : '' }}>
                                                                                    {{ $grado->nombreCompleto ?? $grado->grado . '° ' . $grado->seccion . ' - ' . $grado->nivel }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                        @error('nuevo_estudiante_grado.' . $nuevoRolCounter)
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Apoderado</label>
                                                                        <select name="nuevo_estudiante_apoderado[{{ $nuevoRolCounter }}]"
                                                                                class="form-select select2-apoderado @error('nuevo_estudiante_apoderado.' . $nuevoRolCounter) is-invalid @enderror">
                                                                            <option value="">Buscar apoderado...</option>
                                                                        </select>
                                                                        @error('nuevo_estudiante_apoderado.' . $nuevoRolCounter)
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                                                        <input type="date" name="nuevo_estudiante_fecha_nacimiento[{{ $nuevoRolCounter }}]"
                                                                            class="form-control @error('nuevo_estudiante_fecha_nacimiento.' . $nuevoRolCounter) is-invalid @enderror"
                                                                            value="{{ old('nuevo_estudiante_fecha_nacimiento.' . $nuevoRolCounter) }}" required>
                                                                        @error('nuevo_estudiante_fecha_nacimiento.' . $nuevoRolCounter)
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                                                                        <select name="nuevo_estudiante_estado[{{ $nuevoRolCounter }}]"
                                                                                class="form-select @error('nuevo_estudiante_estado.' . $nuevoRolCounter) is-invalid @enderror" required>
                                                                            <option value="1" {{ old('nuevo_estudiante_estado.' . $nuevoRolCounter, '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('nuevo_estudiante_estado.' . $nuevoRolCounter) == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('nuevo_estudiante_estado.' . $nuevoRolCounter)
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
                                                                        <label class="form-label">Estado del Docente <span class="text-danger">*</span></label>
                                                                        <select name="nuevo_docente_estado[{{ $nuevoRolCounter }}]"
                                                                                class="form-select @error('nuevo_docente_estado.' . $nuevoRolCounter) is-invalid @enderror" required>
                                                                            <option value="1" {{ old('nuevo_docente_estado.' . $nuevoRolCounter, '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('nuevo_docente_estado.' . $nuevoRolCounter) == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('nuevo_docente_estado.' . $nuevoRolCounter)
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
                                                                        <label class="form-label">Parentesco <span class="text-danger">*</span></label>
                                                                        <input type="text" name="nuevo_apoderado_parentesco[{{ $nuevoRolCounter }}]"
                                                                            class="form-control @error('nuevo_apoderado_parentesco.' . $nuevoRolCounter) is-invalid @enderror"
                                                                            value="{{ old('nuevo_apoderado_parentesco.' . $nuevoRolCounter) }}"
                                                                            placeholder="Ej: Padre, Madre, Tutor" required>
                                                                        @error('nuevo_apoderado_parentesco.' . $nuevoRolCounter)
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Estado del Apoderado <span class="text-danger">*</span></label>
                                                                        <select name="nuevo_apoderado_estado[{{ $nuevoRolCounter }}]"
                                                                                class="form-select @error('nuevo_apoderado_estado.' . $nuevoRolCounter) is-invalid @enderror" required>
                                                                            <option value="1" {{ old('nuevo_apoderado_estado.' . $nuevoRolCounter, '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('nuevo_apoderado_estado.' . $nuevoRolCounter) == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('nuevo_apoderado_estado.' . $nuevoRolCounter)
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
                                                                        <label class="form-label">Turno <span class="text-danger">*</span></label>
                                                                        <select name="nuevo_auxiliar_turno[{{ $nuevoRolCounter }}]"
                                                                                class="form-select @error('nuevo_auxiliar_turno.' . $nuevoRolCounter) is-invalid @enderror" required>
                                                                            <option value="">Seleccionar turno</option>
                                                                            <option value="mañana" {{ old('nuevo_auxiliar_turno.' . $nuevoRolCounter) == 'mañana' ? 'selected' : '' }}>Mañana</option>
                                                                            <option value="tarde" {{ old('nuevo_auxiliar_turno.' . $nuevoRolCounter) == 'tarde' ? 'selected' : '' }}>Tarde</option>
                                                                            <option value="noche" {{ old('nuevo_auxiliar_turno.' . $nuevoRolCounter) == 'noche' ? 'selected' : '' }}>Noche</option>
                                                                        </select>
                                                                        @error('nuevo_auxiliar_turno.' . $nuevoRolCounter)
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Estado del Auxiliar <span class="text-danger">*</span></label>
                                                                        <select name="nuevo_auxiliar_estado[{{ $nuevoRolCounter }}]"
                                                                                class="form-select @error('nuevo_auxiliar_estado.' . $nuevoRolCounter) is-invalid @enderror" required>
                                                                            <option value="1" {{ old('nuevo_auxiliar_estado.' . $nuevoRolCounter, '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('nuevo_auxiliar_estado.' . $nuevoRolCounter) == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('nuevo_auxiliar_estado.' . $nuevoRolCounter)
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Funciones</label>
                                                                        <textarea name="nuevo_auxiliar_funciones[{{ $nuevoRolCounter }}]"
                                                                                class="form-control @error('nuevo_auxiliar_funciones.' . $nuevoRolCounter) is-invalid @enderror"
                                                                                rows="2">{{ old('nuevo_auxiliar_funciones.' . $nuevoRolCounter) }}</textarea>
                                                                        @error('nuevo_auxiliar_funciones.' . $nuevoRolCounter)
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
                                                                        <label class="form-label">Estado del Director <span class="text-danger">*</span></label>
                                                                        <select name="nuevo_director_estado[{{ $nuevoRolCounter }}]"
                                                                                class="form-select @error('nuevo_director_estado.' . $nuevoRolCounter) is-invalid @enderror" required>
                                                                            <option value="1" {{ old('nuevo_director_estado.' . $nuevoRolCounter, '1') == '1' ? 'selected' : '' }}>Activo</option>
                                                                            <option value="0" {{ old('nuevo_director_estado.' . $nuevoRolCounter) == '0' ? 'selected' : '' }}>Inactivo</option>
                                                                        </select>
                                                                        @error('nuevo_director_estado.' . $nuevoRolCounter)
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @break

                                                        @default
                                                            <div class="alert alert-info">
                                                                <i class="bi bi-info-circle me-2"></i>
                                                                Este rol no requiere campos adicionales.
                                                            </div>
                                                    @endswitch
                                                </div>
                                            </div>
                                            @php $nuevoRolCounter++; @endphp
                                        @endforeach
                                    </div>

                                    <!-- Agregar nuevo rol -->
                                    @if($rolesDisponibles->count() > 0)
                                        <div class="row mt-4">
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('#selectNuevoRol').select2({
        placeholder: "Seleccione un rol",
        allowClear: true
    });

    // Inicializar Select2 para apoderados existentes
    $('.select2-apoderado').each(function() {
        const selectElement = $(this);
        selectElement.select2({
            placeholder: "Buscar apoderado por nombre, apellido o DNI...",
            allowClear: true,
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
                        results: data.items,
                        pagination: {
                            more: (params.page * 10) < data.total_count
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 2
        });
    });

    // Plantillas para roles NUEVOS (con índice único)
    const roleTemplatesNuevos = {
        'estudiante': (index) => {
            let gradosOptions = '<option value="">Seleccionar grado</option>';
            @foreach($grados as $grado)
                gradosOptions += `<option value="{{ $grado->id }}">{{ $grado->nombreCompleto ?? $grado->grado . '° ' . $grado->seccion . ' - ' . $grado->nivel }}</option>`;
            @endforeach

            return `
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Grado <span class="text-danger">*</span></label>
                            <select name="nuevo_estudiante_grado[${index}]" class="form-select" required>
                                ${gradosOptions}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Apoderado</label>
                            <select name="nuevo_estudiante_apoderado[${index}]"
                                    class="form-select select2-apoderado-nuevo"
                                    data-index="${index}">
                                <option value="">Buscar apoderado...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" name="nuevo_estudiante_fecha_nacimiento[${index}]"
                                class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Estado <span class="text-danger">*</span></label>
                            <select name="nuevo_estudiante_estado[${index}]" class="form-select" required>
                                <option value="1" selected>Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
        },
        'docente': (index) => `
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                        <select name="nuevo_docente_estado[${index}]" class="form-select" required>
                            <option value="1" selected>Activo</option>
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
                        <label class="form-label">Parentesco <span class="text-danger">*</span></label>
                        <input type="text" name="nuevo_apoderado_parentesco[${index}]" class="form-control" placeholder="Ej: Padre, Madre, Tutor" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                        <select name="nuevo_apoderado_estado[${index}]" class="form-select" required>
                            <option value="1" selected>Activo</option>
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
                        <label class="form-label">Turno <span class="text-danger">*</span></label>
                        <select name="nuevo_auxiliar_turno[${index}]" class="form-select" required>
                            <option value="">Seleccionar turno</option>
                            <option value="mañana">Mañana</option>
                            <option value="tarde">Tarde</option>
                            <option value="noche">Noche</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                        <select name="nuevo_auxiliar_estado[${index}]" class="form-select" required>
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Funciones</label>
                        <textarea name="nuevo_auxiliar_funciones[${index}]" class="form-control" rows="2" placeholder="Descripción de funciones"></textarea>
                    </div>
                </div>
            </div>
        `,
        'director': (index) => `
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                        <select name="nuevo_director_estado[${index}]" class="form-select" required>
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
        `,
        // Para otros roles que no tengan campos específicos
        'default': () => `
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Este rol no requiere campos adicionales.
            </div>
        `
    };

    // Contador para nuevos roles
    let nuevoRolCounter = {{ $nuevoRolCounter }};

    // Evento para agregar nuevo rol
    $('#btnAgregarRol').click(function() {
        const selectRol = $('#selectNuevoRol');
        const rolId = selectRol.val();
        const rolOption = selectRol.find('option:selected');
        const rolNombre = rolOption.data('name');

        if (!rolId) {
            Swal.fire({
                icon: 'warning',
                title: 'Por favor seleccione un rol',
                showConfirmButton: false,
                timer: 1500
            });
            return;
        }

        // Verificar si el rol ya está agregado (en roles existentes o nuevos)
        if ($(`input[name="roles[]"][value="${rolId}"]`).length > 0 ||
            $(`input[name="nuevos_roles[]"][value="${rolId}"]`).length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Este rol ya está asignado al usuario',
                showConfirmButton: false,
                timer: 1500
            });
            return;
        }

        // Obtener plantilla para este rol
        const template = roleTemplatesNuevos[rolNombre] ? roleTemplatesNuevos[rolNombre](nuevoRolCounter)
            : '<div class="alert alert-info">No hay campos específicos para este rol.</div>';

        // Crear HTML del nuevo rol
        const nuevoRolHtml = `
            <div class="rol-item mb-4 p-3 border rounded-3 bg-light border-secondary"
                 data-role-id="${rolId}"
                 data-role-name="${rolNombre}"
                 data-tipo="nuevo">
                <div class="row align-items-center mb-3">
                    <div class="col-md-10">
                        <label class="form-label text-muted d-block">
                            Nuevo Rol
                        </label>
                        <input type="hidden" name="nuevos_roles[]" value="${rolId}">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2">${rolOption.text()}</span>
                            <span class="badge bg-info">Nuevo</span>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-danger btn-remover-nuevo-rol">
                            <i class="bi bi-trash"></i> Quitar
                        </button>
                    </div>
                </div>
                <div class="campos-especificos">
                    ${template}
                </div>
            </div>
        `;

        // Agregar al contenedor
        $('#nuevosRolesContainer').append(nuevoRolHtml);

        // Deshabilitar opción en select
        rolOption.prop('disabled', true);
        selectRol.val('').trigger('change');

        // Si no quedan más opciones, ocultar selector
        const opcionesDisponibles = $('#selectNuevoRol option:not(:disabled)').length;
        if (opcionesDisponibles === 1) {
            $('#selectNuevoRol').parent().hide();
            $('#btnAgregarRol').hide();
        }

        // Inicializar Select2 para apoderados (solo para estudiante)
        if (rolNombre === 'estudiante') {
            $(`.select2-apoderado-nuevo[data-index="${nuevoRolCounter}"]`).select2({
                placeholder: "Buscar apoderado por nombre, apellido o DNI...",
                allowClear: true,
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
                            results: data.items,
                            pagination: {
                                more: (params.page * 10) < data.total_count
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2
            });
        }

        // Incrementar contador
        nuevoRolCounter++;
    });

    // Evento para quitar un nuevo rol
    $(document).on('click', '.btn-remover-nuevo-rol', function() {
        const rolDiv = $(this).closest('.rol-item');
        const rolId = rolDiv.data('role-id');

        // Habilitar opción en select
        $('#selectNuevoRol option[value="' + rolId + '"]').prop('disabled', false);

        // Mostrar selector si estaba oculto
        $('#selectNuevoRol').parent().show();
        $('#btnAgregarRol').show();

        // Eliminar el rol
        rolDiv.remove();

        // NOTA: No necesitamos decrementar nuevoRolCounter porque los índices
        // se basan en el momento de creación, no son secuenciales
    });
});
</script>
@endsection
