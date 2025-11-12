@extends('layouts.app')
@section('title', 'Notas')
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-graduation-cap"></i> Registro de Calificaciones
        </h1>

        <!-- Indicador de estado actual -->
        @php
            $estados = [
                '0' => ['Privado', 'secondary'],
                '1' => ['Publicado', 'info'],
                '2' => ['Oficial', 'success'],
                '3' => ['Extra Oficial', 'warning']
            ];

            // Determinar si el usuario actual puede editar
            $puedeEditar = auth()->user()->hasRole('admin') ||
                           auth()->user()->hasRole('director') ||
                           (auth()->user()->hasRole('docente') && in_array($estadoActual, ['0', '1']));
        @endphp
        <span class="badge bg-{{ $estados[$estadoActual][1] }} badge-lg">
            Estado: {{ $estados[$estadoActual][0] }}
        </span>
    </div>

    <!-- Botones de publicación/reversión -->
    <div class="mt-3">
        @php
            $user = auth()->user();
            $esDocenteDelCurso = $user->hasRole('docente') && $docente && $docente->id == ($user->docente->id ?? 0);

            // Lógica para botón de publicación
            $puedePublicar = false;
            $textoBotonPublicar = '';

            if ($user->hasRole('admin') && in_array($estadoActual, ['0', '1', '2'])) {
                $puedePublicar = true;
                if ($estadoActual == '0') $textoBotonPublicar = 'Publicar Notas';
                elseif ($estadoActual == '1') $textoBotonPublicar = 'Marcar como Oficial';
                elseif ($estadoActual == '2') $textoBotonPublicar = 'Marcar como Extra Oficial';
            } elseif ($user->hasRole('director') && in_array($estadoActual, ['0', '1'])) {
                $puedePublicar = true;
                if ($estadoActual == '0') $textoBotonPublicar = 'Publicar Notas';
                elseif ($estadoActual == '1') $textoBotonPublicar = 'Marcar como Oficial';
            } elseif ($esDocenteDelCurso && $estadoActual == '0') {
                $puedePublicar = true;
                $textoBotonPublicar = 'Publicar Notas';
            }
        @endphp

        @if($puedePublicar)
        <form action="{{ route('nota.publicar', ['curso_grado_sec_niv_anio_id' => $curso_id, 'bimestre' => $bimestre]) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning" onclick="return confirm('¿Está seguro de cambiar el estado de las notas?')">
                <i class="fas fa-share-square"></i> {{ $textoBotonPublicar }}
            </button>
        </form>
        @endif

        @if(($user->hasRole('admin') || $user->hasRole('director')) && in_array($estadoActual, ['1', '2', '3']))
        <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#revertirModal">
            <i class="fas fa-undo"></i> Revertir Estado
        </button>
        @endif
    </div><br>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                {{ $materia->nombre }} - {{ $grado->nombreCompleto }} - Bimestre {{ $bimestre }}
            </h6>
            <small>
                Docente:
                @if($docente && $docente->user)
                    {{ $docente->user->apellido_paterno.' '.
                      $docente->user->apellido_materno.', '.
                      $docente->user->nombre }}
                @else
                    No asignado
                @endif
                | Año: {{ $curso->anio }}
            </small>
        </div>

        <div class="card-body">
            <!-- Mensajes de advertencia según el estado -->
            @if(!$puedeEditar)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Las notas están en estado <strong>{{ $estados[$estadoActual][0] }}</strong>.
                @if(auth()->user()->hasRole('docente'))
                    Solo puede editar en estados Privado y Publicado.
                @else
                    No tiene permisos para editar en este estado.
                @endif
            </div>
            @endif

            <!-- Pestañas -->
            <ul class="nav nav-tabs" id="notasTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="materia-tab" data-bs-toggle="tab" data-bs-target="#materia" type="button" role="tab">
                        <i class="fas fa-book"></i> Notas de la Materia
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="comportamiento-tab" data-bs-toggle="tab" data-bs-target="#comportamiento" type="button" role="tab">
                        <i class="fas fa-users"></i> Notas de Comportamiento
                    </button>
                </li>
            </ul>

            <!-- Contenido de las pestañas -->
            <div class="tab-content" id="notasTabsContent">
                <!-- Pestaña Notas de la Materia -->
                <div class="tab-pane fade show active" id="materia" role="tabpanel">
                    <form action="{{ route('nota.store') }}" method="POST" id="formNotas">
                        @csrf
                        <input type="hidden" name="curso_id" value="{{ $curso_id }}">
                        <input type="hidden" name="bimestre" value="{{ $bimestre }}">

                        <!-- Tabla para Estudiantes Activos -->
                        @if($estudiantesActivos->count() > 0)
                        <h5 class="mb-3 mt-3 text-success">
                            <i class="fas fa-user-check"></i> Estudiantes Activos
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaNotasActivos">
                                <thead class="table-dark">
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
                                                <th title="{{ $criterio->descripcion ?? 'Sin descripción' }}">
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
                                                            $notaData = $notasExistentes[$key] ?? null;
                                                            $nota = $notaData['nota'] ?? null;
                                                        @endphp
                                                        <input type="number"
                                                            name="notas[{{ $estudiante->id }}][{{ $criterio->id }}]"
                                                            value="{{ $nota }}"
                                                            min="1"
                                                            max="4"
                                                            step="1"
                                                            class="form-control form-control-sm nota-input"
                                                            {{ !$puedeEditar ? 'readonly' : '' }}
                                                            style="{{ !$puedeEditar ? 'background-color: #f8f9fa;' : '' }}"
                                                            oninput="this.value = this.value.replace(/[^0-4]/g, '').replace(/(\..*)\./g, '$1');">
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
                                <thead class="table-light">
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
                                                <th title="{{ $criterio->descripcion ?? 'Sin descripción' }}">
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
                                                <i class="fas fa-lock text-muted me-1"></i>
                                                {{ $estudiante->user->apellido_paterno }}
                                                {{ $estudiante->user->apellido_materno }},
                                                {{ $estudiante->user->nombre }}
                                                <span class="badge bg-warning ms-2">Inactivo</span>
                                            </td>
                                            @foreach($competencias as $competencia)
                                                @foreach($competencia->criterios as $criterio)
                                                    <td>
                                                        @php
                                                            $key = $estudiante->id.'-'.$criterio->id;
                                                            $notaData = $notasExistentes[$key] ?? null;
                                                            $nota = $notaData['nota'] ?? null;
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
                            <button type="submit" class="btn btn-primary" {{ !$puedeEditar ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Guardar Calificaciones
                            </button>
                            <a href="{{ route('maya.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Pestaña Notas de Comportamiento -->
                <div class="tab-pane fade" id="comportamiento" role="tabpanel">
                    <form action="{{ route('nota.storeConductaNotas') }}" method="POST" id="formConductaNotas">
                        @csrf
                        <input type="hidden" name="curso_id" value="{{ $curso_id }}">
                        <input type="hidden" name="bimestre" value="{{ $bimestre }}">

                        <!-- Tabla para Estudiantes Activos - Conducta -->
                        @if($estudiantesActivos->count() > 0 && $conductas->count() > 0)
                        <h5 class="mb-3 mt-3 text-success">
                            <i class="fas fa-user-check"></i> Estudiantes Activos - Conducta
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaConductaActivos">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Estudiante</th>
                                        @foreach($conductas as $conducta)
                                        <th title="{{ $conducta->nombre }}">
                                            {{ $conducta->nombre }}
                                            <input type="hidden" name="conductas[]" value="{{ $conducta->id }}">
                                        </th>
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
                                            @foreach($conductas as $conducta)
                                                <td>
                                                    @php
                                                        $key = $estudiante->id.'-'.$conducta->id;
                                                        $notaData = $conductaNotas[$key] ?? null;
                                                        $notaConducta = $notaData['nota'] ?? null;
                                                    @endphp
                                                    <input type="number"
                                                        name="notas_conducta[{{ $estudiante->id }}][{{ $conducta->id }}]"
                                                        value="{{ $notaConducta }}"
                                                        min="1"
                                                        max="4"
                                                        step="1"
                                                        class="form-control form-control-sm nota-input"
                                                        {{ !$puedeEditar ? 'readonly' : '' }}
                                                        style="{{ !$puedeEditar ? 'background-color: #f8f9fa;' : '' }}"
                                                        oninput="this.value = this.value.replace(/[^0-4]/g, '').replace(/(\..*)\./g, '$1');">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        <!-- Tabla para Estudiantes Inactivos - Conducta (solo lectura) -->
                        @if($estudiantesInactivos->count() > 0 && $conductas->count() > 0)
                        <h5 class="mb-3 mt-4 text-secondary">
                            <i class="fas fa-user-times"></i> Estudiantes Inactivos - Conducta (Solo Lectura)
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaConductaInactivos">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estudiante</th>
                                        @foreach($conductas as $conducta)
                                        <th title="{{ $conducta->nombre }}">
                                            {{ $conducta->nombre }}
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estudiantesInactivos as $estudiante)
                                        <tr class="table-secondary">
                                            <td>
                                                <i class="fas fa-lock text-muted me-1"></i>
                                                {{ $estudiante->user->apellido_paterno }}
                                                {{ $estudiante->user->apellido_materno }},
                                                {{ $estudiante->user->nombre }}
                                                <span class="badge bg-warning ms-2">Inactivo</span>
                                            </td>
                                            @foreach($conductas as $conducta)
                                                <td>
                                                    @php
                                                        $key = $estudiante->id.'-'.$conducta->id;
                                                        $notaData = $conductaNotas[$key] ?? null;
                                                        $notaConducta = $notaData['nota'] ?? null;
                                                    @endphp
                                                    <input type="number"
                                                        value="{{ $notaConducta }}"
                                                        min="1"
                                                        max="4"
                                                        step="1"
                                                        class="form-control form-control-sm"
                                                        readonly
                                                        style="background-color: #f8f9fa;">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        @if($conductas->count() > 0)
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary" {{ !$puedeEditar ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Guardar Notas de Conducta
                            </button>
                        </div>
                        @else
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            No hay conductas configuradas. Contacte al administrador.
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="revertirModal" tabindex="-1" aria-labelledby="revertirModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="revertirModalLabel">
                    <i class="fas fa-undo"></i> Revertir Estado de Notas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Advertencia:</strong> Esta acción requiere autenticación con la sesión.
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Estado Actual:</strong>
                        <span class="badge bg-secondary">{{ $estados[$estadoActual][0] }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Nuevo Estado:</strong>
                        <span class="badge bg-info">
                            @if($estadoActual == '3') Oficial
                            @elseif($estadoActual == '2') Publicado
                            @elseif($estadoActual == '1') Privado
                            @else No aplica
                            @endif
                        </span>
                    </div>
                </div>

                @if(session('sessionmain'))
                <form id="revertirForm" action="{{ route('nota.revertir', ['curso_grado_sec_niv_anio_id' => $curso_id, 'bimestre' => $bimestre]) }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="password" class="form-label"><strong>Contraseña *</strong></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" required
                               placeholder="Ingrese la contraseña de {{ session('sessionmain')->nombre_usuario }}">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Contraseña de la sesión principal para autorizar la reversión.
                        </small>
                    </div>
                </form>
                @else
                <div class="alert alert-danger">
                    No se puede proceder sin una sesión principal activa.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                @if(session('sessionmain'))
                <button type="submit" form="revertirForm" class="btn btn-danger">
                    <i class="fas fa-undo"></i> Confirmar Reversión
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación de inputs de notas - solo formato, no obligatoriedad
    document.querySelectorAll('.nota-input').forEach(input => {
        input.addEventListener('input', function() {
            let value = parseInt(this.value);

            if (isNaN(value)) {
                this.value = '';
                return;
            }

            if (value < 1) {
                this.value = 1;
            } else if (value > 4) {
                this.value = 4;
            } else {
                this.value = Math.floor(value); // Asegura número entero
            }
        });

        // Validación cuando pierde el foco
        input.addEventListener('blur', function() {
            let value = parseInt(this.value);
            if (!isNaN(value) && (value < 1 || value > 4)) {
                this.style.borderColor = 'red';
            } else {
                this.style.borderColor = '';
            }
        });
    });

    // Activar las pestañas de Bootstrap
    const triggerTabList = document.querySelectorAll('#notasTabs button');
    triggerTabList.forEach(triggerEl => {
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            const tab = new bootstrap.Tab(this);
            tab.show();
        });
    });

    // Validación suave para formularios - solo verifica formato, no obligatoriedad
    document.getElementById('formNotas')?.addEventListener('submit', function(e) {
        if (!validarFormatoNotas()) {
            e.preventDefault();
            alert('Algunas notas tienen valores fuera del rango permitido (1-4). Por favor, corríjalas antes de guardar.');
        } else {
            // Opcional: Mostrar mensaje de confirmación
            if(confirm('¿Está seguro de guardar las calificaciones?')) {
                return true;
            } else {
                e.preventDefault();
            }
        }
    });

    document.getElementById('formConductaNotas')?.addEventListener('submit', function(e) {
        if (!validarFormatoNotasConducta()) {
            e.preventDefault();
            alert('Algunas notas de conducta tienen valores fuera del rango permitido (1-4). Por favor, corríjalas antes de guardar.');
        } else {
            // Opcional: Mostrar mensaje de confirmación
            if(confirm('¿Está seguro de guardar las notas de conducta?')) {
                return true;
            } else {
                e.preventDefault();
            }
        }
    });

    // Solo valida que las notas que SÍ tienen valor estén en el rango correcto
    function validarFormatoNotas() {
        let valid = true;
        document.querySelectorAll('#formNotas .nota-input:not([readonly])').forEach(input => {
            if (input.value !== '') {
                let value = parseInt(input.value);
                if (isNaN(value) || value < 1 || value > 4) {
                    valid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '';
                }
            }
        });
        return valid;
    }

    function validarFormatoNotasConducta() {
        let valid = true;
        document.querySelectorAll('#formConductaNotas .nota-input:not([readonly])').forEach(input => {
            if (input.value !== '') {
                let value = parseInt(input.value);
                if (isNaN(value) || value < 1 || value > 4) {
                    valid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '';
                }
            }
        });
        return valid;
    }
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const revertirModal = document.getElementById('revertirModal');

    if (revertirModal) {
        // Limpiar el formulario cuando se cierra el modal
        revertirModal.addEventListener('hidden.bs.modal', function () {
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.value = '';
                passwordInput.classList.remove('is-invalid');
            }
        });

        // Validación del formulario de reversión
        const revertirForm = document.getElementById('revertirForm');
        if (revertirForm) {
            revertirForm.addEventListener('submit', function(e) {
                const passwordInput = document.getElementById('password');
                if (!passwordInput.value.trim()) {
                    e.preventDefault();
                    passwordInput.classList.add('is-invalid');
                    passwordInput.focus();
                }
            });
        }

        // Remover validación cuando el usuario escribe
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        }
    }
});
</script>
@endsection
