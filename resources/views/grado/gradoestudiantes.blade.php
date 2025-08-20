@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-person-rolodex me-2"></i> Estudiantes del Grado
        </h1>
        <a href="{{ route('grado.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i> Volver a Grados
        </a>
    </div>

    <!-- Información del Grado -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">Información del Grado Actual</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Grado:</strong> {{ $grado->grado }}°</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Sección:</strong> {{ $grado->seccion }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Nivel:</strong> {{ $grado->nivel }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario para ascender estudiantes -->
    <form id="ascenderForm" action="{{ route('grado.estudiantesupdategrado', $grado->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Seleccionar Grado de Destino</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nuevo_grado"><strong>Grado Superior:</strong></label>
                            <select class="form-control" id="nuevo_grado" name="nuevo_grado" required>
                                <option value="">Seleccionar grado</option>
                                @php
                                    $gradoActual = (int)$grado->grado;
                                    $siguienteGrado = $gradoActual + 1;
                                @endphp
                                <option value="{{ $siguienteGrado }}">{{ $siguienteGrado }}°</option>
                                <!-- Puedes agregar más opciones si es necesario -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nueva_seccion"><strong>Sección:</strong></label>
                            <select class="form-control" id="nueva_seccion" name="nueva_seccion" required>
                                <option value="">Seleccionar sección</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <!-- Agrega más secciones según necesites -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nuevo_nivel"><strong>Nivel:</strong></label>
                            <select class="form-control" id="nuevo_nivel" name="nuevo_nivel" required>
                                <option value="">Seleccionar nivel</option>
                                <option value="Primaria" {{ $grado->nivel == 'Primaria' ? 'selected' : '' }}>Primaria</option>
                                <option value="Secundaria" {{ $grado->nivel == 'Secundaria' ? 'selected' : '' }}>Secundaria</option>
                                <!-- Agrega más niveles según necesites -->
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Estudiantes -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Lista de Estudiantes - {{ $grado->nombreCompleto }}</h6>
                <span class="badge bg-white text-primary rounded-pill">{{ $estudiantes->count() }} estudiantes</span>
            </div>
            <div class="card-body">
                @if($estudiantes->count() > 0)
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label" for="selectAll">
                            Seleccionar todos
                        </label>
                    </div>
                    <button type="button" id="ascenderBtn" class="btn btn-success" disabled>
                        <i class="bi bi-arrow-up-circle me-2"></i> Ascender estudiantes seleccionados
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">Seleccionar</th>
                                <th width="5%">#</th>
                                <th>DNI</th>
                                <th>Apellidos y Nombres</th>
                                <th>Grado Actual</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantes as $estudiante)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="estudiantes[]" value="{{ $estudiante->id }}" class="estudiante-checkbox">
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $estudiante->user->dni }}</td>
                                <td>
                                    {{ $estudiante->user->apellido_paterno }}
                                    {{ $estudiante->user->apellido_materno }},
                                    {{ $estudiante->user->nombre }}
                                </td>
                                <td>{{ $grado->grado }}{{ $grado->seccion }} - {{ $grado->nivel }}</td>
                                <td>
                                    <span class="badge bg-{{ $estudiante->estado ? 'success' : 'danger' }}">
                                        {{ $estudiante->estado ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @else
                <div class="text-center py-5">
                    <i class="bi bi-people display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No hay estudiantes registrados en este grado</h4>
                    <p class="text-muted">Parece que no hay estudiantes activos asignados a este grado.</p>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirmar Ascenso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea ascender a los <span id="selectedCount">0</span> estudiantes seleccionados al grado <span id="newGradoInfo"></span>?</p>
                <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="confirmAscender" class="btn btn-success">Ascender Estudiantes</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Seleccionar/deseleccionar todos
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.estudiante-checkbox');
        const ascenderBtn = document.getElementById('ascenderBtn');

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateButtonState();
        });

        // Actualizar estado del botón según selección
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateButtonState);
        });

        function updateButtonState() {
            const selectedCount = document.querySelectorAll('.estudiante-checkbox:checked').length;
            ascenderBtn.disabled = selectedCount === 0;
            ascenderBtn.textContent = `Ascender estudiantes seleccionados (${selectedCount})`;
        }

        // Mostrar modal de confirmación
        ascenderBtn.addEventListener('click', function() {
            const selectedCount = document.querySelectorAll('.estudiante-checkbox:checked').length;
            const nuevoGrado = document.getElementById('nuevo_grado').value;
            const nuevaSeccion = document.getElementById('nueva_seccion').value;
            const nuevoNivel = document.getElementById('nuevo_nivel').value;

            if (!nuevoGrado || !nuevaSeccion || !nuevoNivel) {
                alert('Por favor, complete todos los campos del grado destino.');
                return;
            }

            document.getElementById('selectedCount').textContent = selectedCount;
            document.getElementById('newGradoInfo').textContent = `${nuevoGrado}° "${nuevaSeccion}" - ${nuevoNivel}`;

            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        });

        // Confirmar ascenso
        document.getElementById('confirmAscender').addEventListener('click', function() {
            document.getElementById('ascenderForm').submit();
        });
    });
</script>
@endsection
