@extends('layouts.app')
@section('title', 'Estudiantes - ' . $grado->nombre_completo)
@section('content')
    <div class="container py-4">
        <!-- Header con información del grado y período -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5 class="mb-1">{{ $grado->nombre_completo }}</h5>
                    <p class="mb-0">Período: <strong>{{ $nombre }}</strong></p>
                </div>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Tabla de Estudiantes Matriculados -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-check me-2"></i>
                    Estudiantes Matriculados
                    <span class="badge bg-light text-dark ms-2">
                        {{ $matriculas->where('estado', '1')->count() }} activo(s)
                    </span>
                    <span class="badge bg-warning text-dark ms-1">
                        {{ $matriculas->where('estado', '0')->count() }} retirado(s)
                    </span>
                </h5>
            </div>
            <div class="card-body">
                @if($matriculas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Apellidos y Nombres</th>
                                    <th>DNI</th>
                                    <th>Email</th>
                                    <th>Estado Matrícula</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matriculas as $index => $matricula)
                                    @php
                                        $estudiante = $matricula->estudiante;
                                        $user = $estudiante->user ?? null;
                                    @endphp
                                    @if($user)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ $user->apellido_paterno }}
                                                {{ $user->apellido_materno }},
                                                {{ $user->nombre }}
                                            </td>
                                            <td>{{ $user->dni ?? 'N/A' }}</td>
                                            <td>{{ $user->email ?? 'N/A' }}</td>
                                            <td>
                                                @if($matricula->estado == '1')
                                                    <span class="badge bg-success">Matriculado</span>
                                                @else
                                                    <span class="badge bg-warning">Retirado</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('user.edit', ['user' => $user->id]) }}"
                                                class="btn btn-sm btn-warning" title="Editar usuario">
                                                    <i class="bi bi-person-gear"></i>
                                                </a>
                                                @if($matricula->estado == '1')
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger btn-retirar"
                                                            data-matricula-id="{{ $matricula->id }}"
                                                            data-estudiante-nombre="{{ $user->apellido_paterno }} {{ $user->apellido_materno }}, {{ $user->nombre }}"
                                                            title="Retirar">
                                                        <i class="bi bi-box-arrow-right"></i>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            class="btn btn-sm btn-success btn-reactivar"
                                                            data-matricula-id="{{ $matricula->id }}"
                                                            data-estudiante-nombre="{{ $user->apellido_paterno }} {{ $user->apellido_materno }}, {{ $user->nombre }}"
                                                            title="Reactivar">
                                                        <i class="bi bi-plus-square"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        No hay estudiantes matriculados en este período.
                    </div>
                @endif
            </div>
        </div>

        <!-- Tabla de Estudiantes No Matriculados pero Activos -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Estudiantes Disponibles para Matricular
                    <span class="badge bg-light text-dark ms-2">
                        {{ $estudiantesNoMatriculados->count() }} estudiante(s)
                    </span>
                </h5>
            </div>
            <div class="card-body">
                @if($estudiantesNoMatriculados->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Apellidos y Nombres</th>
                                    <th>DNI</th>
                                    <th>Email</th>
                                    <th>Estado Estudiante</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($estudiantesNoMatriculados as $index => $estudiante)
                                    @php
                                        $user = $estudiante->user ?? null;
                                    @endphp
                                    @if($user)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ $user->apellido_paterno }}
                                                {{ $user->apellido_materno }},
                                                {{ $user->nombre }}
                                            </td>
                                            <td>{{ $user->dni ?? 'N/A' }}</td>
                                            <td>{{ $user->email ?? 'N/A' }}</td>
                                            <td>
                                                @if($estudiante->estado == '1')
                                                    <span class="badge bg-success">Activo</span>
                                                @else
                                                    <span class="badge bg-danger">Inactivo</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('user.edit', ['user' => $user->id]) }}"
                                                    class="btn btn-sm btn-warning" title="Editar usuario">
                                                    <i class="bi bi-person-gear"></i>
                                                </a>
                                                @if($estudiante->estado == '1')
                                                    <button type="button"
                                                            class="btn btn-sm btn-success btn-matricular"
                                                            data-estudiante-id="{{ $estudiante->id }}"
                                                            data-estudiante-nombre="{{ $user->apellido_paterno }} {{ $user->apellido_materno }}, {{ $user->nombre }}"
                                                            title="Matricular">
                                                        <i class="bi bi-folder-plus"></i> Matricular
                                                    </button>
                                                @else
                                                    <span class="badge bg-secondary">No disponible</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        No hay estudiantes disponibles para matrícula en este grado.
                    </div>
                @endif
            </div>
        </div>

        <!-- Botón para volver -->
        <div class="mt-4">
            <a href="{{ route('matricula.index', ['nombre' => $nombre]) }}"
               class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Volver a Grados
            </a>
        </div>
    </div>

    <!-- Modal para Matricular -->
    <div class="modal fade" id="modalMatricular" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Matrícula</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Desea matricular al estudiante <strong id="nombreEstudiante"></strong>?</p>
                    <form id="formMatricular" method="POST">
                        @csrf
                        <input type="hidden" name="estudiante_id" id="estudianteId">
                        <input type="hidden" name="periodo_id" value="{{ $periodo->id }}">
                        <input type="hidden" name="grado_id" value="{{ $grado->id }}">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarMatricula">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Retirar/Reactivar -->
    <div class="modal fade" id="modalCambiarEstado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Cambiar Estado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMensaje"></p>
                    <form id="formCambiarEstado" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="estado" id="nuevoEstado">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarCambio">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal para matricular
            const modalMatricular = new bootstrap.Modal(document.getElementById('modalMatricular'));
            const formMatricular = document.getElementById('formMatricular');
            const nombreEstudiante = document.getElementById('nombreEstudiante');
            const estudianteId = document.getElementById('estudianteId');
            const btnConfirmarMatricula = document.getElementById('btnConfirmarMatricula');

            // Modal para cambiar estado (retirar/reactivar)
            const modalCambiarEstado = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            const formCambiarEstado = document.getElementById('formCambiarEstado');
            const modalTitulo = document.getElementById('modalTitulo');
            const modalMensaje = document.getElementById('modalMensaje');
            const nuevoEstado = document.getElementById('nuevoEstado');
            const btnConfirmarCambio = document.getElementById('btnConfirmarCambio');

            // Event listeners para botones de matricular
            document.querySelectorAll('.btn-matricular').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-estudiante-id');
                    const nombre = this.getAttribute('data-estudiante-nombre');

                    estudianteId.value = id;
                    nombreEstudiante.textContent = nombre;
                    formMatricular.action = "{{ route('matricula.store') }}";

                    modalMatricular.show();
                });
            });

            // Event listeners para botones de retirar
            document.querySelectorAll('.btn-retirar').forEach(button => {
                button.addEventListener('click', function() {
                    const matriculaId = this.getAttribute('data-matricula-id');
                    const nombre = this.getAttribute('data-estudiante-nombre');

                    modalTitulo.textContent = 'Retirar Estudiante';
                    modalMensaje.textContent = `¿Desea retirar al estudiante ${nombre}?`;
                    nuevoEstado.value = '0'; // Estado retirado

                    // Usar ruta con nombre
                    formCambiarEstado.action = "{{ route('matricula.estado', ['matricula' => ':id']) }}".replace(':id', matriculaId);

                    modalCambiarEstado.show();
                });
            });

            // Event listeners para botones de reactivar
            document.querySelectorAll('.btn-reactivar').forEach(button => {
                button.addEventListener('click', function() {
                    const matriculaId = this.getAttribute('data-matricula-id');
                    const nombre = this.getAttribute('data-estudiante-nombre');

                    modalTitulo.textContent = 'Reactivar Estudiante';
                    modalMensaje.textContent = `¿Desea reactivar al estudiante ${nombre}?`;
                    nuevoEstado.value = '1'; // Estado activo/matriculado

                    // Usar ruta con nombre
                    formCambiarEstado.action = "{{ route('matricula.estado', ['matricula' => ':id']) }}".replace(':id', matriculaId);

                    modalCambiarEstado.show();
                });
            });

            // Confirmar matrícula
            btnConfirmarMatricula.addEventListener('click', function() {
                formMatricular.submit();
            });

            // Confirmar cambio de estado
            btnConfirmarCambio.addEventListener('click', function() {
                formCambiarEstado.submit();
            });
        });
    </script>
@endsection
