@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-2">
                Asistencia: {{ $grado->grado }}° {{ $grado->seccion }} - {{ $grado->nivel }}
            </h3>
            <div class="d-flex align-items-center">
                <span class="badge {{ $existenRegistros ? 'bg-success' : 'bg-warning' }} me-2">
                    {{ $existenRegistros ? 'Registrada' : 'Pendiente' }}
                </span>
                <small class="text-muted">
                    <i class="far fa-calendar-alt me-1"></i>
                    {{ \Carbon\Carbon::createFromFormat('d-m-Y', $fechaSeleccionada)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </small>
            </div>
        </div>
    </div>

    <div>
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <!-- Panel de controles -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Bimestre:</label>
                    <select class="form-select" name="bimestre" id="bimestre" required {{ $existenRegistros ? 'disabled' : '' }}>
                        @if($existenRegistros)
                            <option value="{{ $bimestreActual }}" selected>Bimestre {{ $bimestreActual }}</option>
                        @else
                            <option value="" disabled selected>Seleccione bimestre</option>
                            @for($i = 1; $i <= 4; $i++)
                            <option value="{{ $i }}" {{ old('bimestre') == $i ? 'selected' : '' }}>Bimestre {{ $i }}</option>
                            @endfor
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha:</label>
                    <div class="input-group">
                        <input type="date"
                            name="fecha"
                            id="fechaInput"
                            class="form-control"
                            value="{{ \Carbon\Carbon::createFromFormat('d-m-Y', $fechaSeleccionada)->format('Y-m-d') }}"
                            min="{{ now()->subYears(1)->format('Y-m-d') }}"
                            max="{{ now()->addYear()->format('Y-m-d') }}">
                        <button class="btn btn-outline-secondary" type="button" id="btnHoy">
                            <i class="fas fa-calendar-day"></i> Hoy
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Resumen:</label>
                    <div class="d-flex justify-content-around">
                        <div class="text-center">
                            <span class="badge bg-success" id="contadorPuntual">0</span>
                            <div class="small text-muted">Puntual</div>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-danger" id="contadorTardanza">0</span>
                            <div class="small text-muted">Tardanza</div>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-secondary" id="contadorTotal">0</span>
                            <div class="small text-muted">Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario principal -->
    <form id="formAsistencia"
      action="{{ route('asistencia.guardar-multiple', ['grado' => $grado->id, 'fecha' => $fechaFormateada]) }}"
      method="POST">
        @csrf

        <input type="hidden" name="bimestre" id="bimestreHidden" value="{{ $bimestreActual ?? '' }}">

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Estudiantes</h5>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        {{ $existenRegistros ? 'Actualizar Asistencia' : 'Guardar Asistencia' }}
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Estudiante</th>
                                <th width="150">Tipo Asistencia</th>
                                <th width="120">Hora</th>
                                <th width="100">Estado</th>
                                <th width="250" class="text-center">Acciones Rápidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($estudiantes as $estudiante)
                            @php
                                $asistenciaActual = $estudiante->asistencias->first();
                                $tipoAsistenciaId = $asistenciaActual ? $asistenciaActual->tipo_asistencia_id : null;
                                $horaActual = $asistenciaActual ? substr($asistenciaActual->hora, 0, 5) : now()->format('H:i');
                                $estado = $asistenciaActual ? 'Registrado' : 'Pendiente';
                                $badgeClass = $asistenciaActual ? 'bg-success' : 'bg-warning';
                            @endphp
                            <tr id="estudiante-{{ $estudiante->id }}"
                                class="{{ $estudiante->estado == 0 ? 'table-secondary' : '' }}"
                                data-estudiante-id="{{ $estudiante->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $estudiante->user->apellido_paterno ?? '' }}
                                                {{ $estudiante->user->apellido_materno ?? '' }},
                                                {{ $estudiante->user->nombre ?? '' }}
                                            </div>
                                            @if($estudiante->estado == 0)
                                            <small class="text-danger">
                                                <i class="fas fa-user-slash"></i> Inactivo
                                            </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <select name="asistencias[{{ $estudiante->id }}]"
                                            class="form-select form-select-sm tipo-asistencia-select"
                                            data-estudiante-id="{{ $estudiante->id }}"
                                            {{ $estudiante->estado == 0 ? 'disabled' : '' }}>
                                        <option value="">Seleccionar</option>
                                        @foreach($tiposAsistencia as $tipo)
                                        <option value="{{ $tipo->id }}"
                                                data-color="{{ $tipo->color ?? '#6B7280' }}"
                                                {{ $tipoAsistenciaId == $tipo->id ? 'selected' : '' }}>
                                            {{ $tipo->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="time"
                                               name="horas[{{ $estudiante->id }}]"
                                               class="form-control hora-input"
                                               value="{{ $horaActual }}"
                                               data-estudiante-id="{{ $estudiante->id }}"
                                               {{ $estudiante->estado == 0 ? 'disabled' : '' }}>
                                        @if($estudiante->estado == 1)
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm btn-hora-ahora"
                                                data-estudiante-id="{{ $estudiante->id }}">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span id="estado-{{ $estudiante->id }}" class="badge {{ $badgeClass }}">
                                        {{ $estado }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button"
                                                class="btn btn-outline-success btn-marcar-rapido"
                                                data-estudiante-id="{{ $estudiante->id }}"
                                                data-tipo="5"
                                                title="Marcar como Puntual"
                                                {{ $estudiante->estado == 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-check"></i> Puntual
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-marcar-rapido"
                                                data-estudiante-id="{{ $estudiante->id }}"
                                                data-tipo="1"
                                                title="Marcar como Tardanza"
                                                {{ $estudiante->estado == 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-clock"></i> Tardanza
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-user-graduate fa-2x mb-2"></i>
                                        <p>No hay estudiantes {{ $existenRegistros ? '' : 'activos' }} en este grado</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            @if($existenRegistros)
                                Total de registros: {{ $estudiantes->count() }}
                            @else
                                Estudiantes activos: {{ $estudiantes->where('estado', 1)->count() }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const fechaInput = document.getElementById('fechaInput');
    const bimestreSelect = document.getElementById('bimestre');
    const bimestreHidden = document.getElementById('bimestreHidden');
    const formAsistencia = document.getElementById('formAsistencia');
    const btnHoy = document.getElementById('btnHoy');
    const btnMarcarTodos = document.getElementById('btnMarcarTodos');
    const btnEliminarTodo = document.getElementById('btnEliminarTodo');
    const formEliminarTodo = document.getElementById('formEliminarTodo');
    const contadorPuntual = document.getElementById('contadorPuntual');
    const contadorTardanza = document.getElementById('contadorTardanza');
    const contadorTotal = document.getElementById('contadorTotal');

    // Configurar selects con colores
    document.querySelectorAll('.tipo-asistencia-select').forEach(select => {
        updateSelectColor(select);
        select.addEventListener('change', function() {
            updateSelectColor(this);
            updateEstado(this.dataset.estudianteId, 'Registrado');
            updateContadores();
        });
    });

    // Cambiar fecha
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            const fechaEnFormatoYMD = this.value;
            if (fechaEnFormatoYMD) {
                const partes = fechaEnFormatoYMD.split('-');
                const fechaEnFormatoDMY = partes[2] + '-' + partes[1] + '-' + partes[0];
                const gradoSeccion = "{{ $grado->grado }}{{ $grado->seccion }}";
                const gradoNivel = "{{ strtolower($grado->nivel) }}";

                const nuevaUrl = "{{ route('asistencia.grado', ['grado_grado_seccion' => ':gradoSeccion', 'grado_nivel' => ':gradoNivel', 'date' => ':date']) }}"
                    .replace(':gradoSeccion', gradoSeccion)
                    .replace(':gradoNivel', gradoNivel)
                    .replace(':date', fechaEnFormatoDMY);

                window.location.href = nuevaUrl;
            }
        });
    }

    // Botón "Hoy"
    if (btnHoy) {
        btnHoy.addEventListener('click', function() {
            const hoy = new Date().toISOString().split('T')[0];
            fechaInput.value = hoy;
            fechaInput.dispatchEvent(new Event('change'));
        });
    }

    // Botones de marcado rápido
    document.querySelectorAll('.btn-marcar-rapido').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            if (this.disabled) return;

            const estudianteId = this.dataset.estudianteId;
            const tipoId = this.dataset.tipo; // 5 = puntual, 1 = tardanza, etc.
            const fila = document.getElementById(`estudiante-${estudianteId}`);
            if (!fila) return;

            // Deshabilitar botón mientras se procesa
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const response = await fetch('{{ route("asistencia.marcar-individual", ":estudiante") }}'
                    .replace(':estudiante', estudianteId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        tipo_asistencia_id: tipoId,
                        fecha: '{{ $fechaSeleccionada }}',           // d-m-Y
                        hora: nowFormatted(),                         // función auxiliar abajo
                        grado_id: '{{ $grado->id }}',
                        bimestre: bimestreHidden.value || '{{ $bimestreActual ?? '' }}',
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Actualizar select
                    const select = fila.querySelector(`select[name="asistencias[${estudianteId}]"]`);
                    if (select) {
                        select.value = data.asistencia.tipo_asistencia_id;
                        updateSelectColor(select);
                    }

                    // Actualizar hora (si quieres mostrar la hora real registrada)
                    const horaInput = fila.querySelector(`input[name="horas[${estudianteId}]"]`);
                    if (horaInput) {
                        horaInput.value = data.asistencia.hora;
                    }

                    // Actualizar estado
                    updateEstado(estudianteId, data.asistencia.estado);

                    // Opcional: cambiar borde de la fila o mostrar check
                    fila.classList.add('table-success');
                    setTimeout(() => fila.classList.remove('table-success'), 1500);

                    updateContadores();
                } else {
                    alert(data.message || 'Error al registrar la asistencia');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al conectar con el servidor');
            } finally {
                // Restaurar botón
                this.disabled = false;
                this.innerHTML = this.dataset.tipo === '5'
                    ? '<i class="fas fa-check"></i> Puntual'
                    : '<i class="fas fa-clock"></i> Tardanza';
            }
        });
    });

    // Botones de hora actual
    document.querySelectorAll('.btn-hora-ahora').forEach(btn => {
        btn.addEventListener('click', function() {
            const estudianteId = this.dataset.estudianteId;
            const horaInput = document.querySelector(`input[name="horas[${estudianteId}]"]`);
            if (horaInput) {
                const ahora = new Date();
                const horaFormateada = ahora.getHours().toString().padStart(2, '0') + ':' +
                                     ahora.getMinutes().toString().padStart(2, '0');
                horaInput.value = horaFormateada;
            }
        });
    });

    // Validar formulario
    if (formAsistencia) {
        formAsistencia.addEventListener('submit', function(e) {
            if (!bimestreSelect.disabled && !bimestreSelect.value) {
                e.preventDefault();
                alert('Por favor, seleccione un bimestre');
                bimestreSelect.focus();
                return;
            }

            let todosMarcados = true;
            document.querySelectorAll('.tipo-asistencia-select:not([disabled])').forEach(select => {
                if (!select.value) {
                    todosMarcados = false;
                    select.classList.add('is-invalid');
                } else {
                    select.classList.remove('is-invalid');
                }
            });

            // Actualizar bimestre hidden
            if (bimestreSelect.value) {
                bimestreHidden.value = bimestreSelect.value;
            }
        });
    }

    // Funciones auxiliares
    function updateSelectColor(select) {
        const selectedOption = select.options[select.selectedIndex];
        const color = selectedOption.getAttribute('data-color') || '#6B7280';
        select.style.borderColor = color;
        select.style.color = color;
        select.style.backgroundColor = color + '10';
    }

    function updateEstado(estudianteId, estado) {
        const badge = document.getElementById(`estado-${estudianteId}`);
        if (badge) {
            badge.textContent = estado;
            badge.className = 'badge bg-success';
        }
    }

    function updateContadores() {
        let puntual = 0;
        let tardanza = 0;
        let total = 0;

        document.querySelectorAll('.tipo-asistencia-select').forEach(select => {
            if (select.value) {
                total++;
                if (select.value === '5') puntual++;
                if (select.value === '1') tardanza++;
            }
        });

        if (contadorPuntual) contadorPuntual.textContent = puntual;
        if (contadorTardanza) contadorTardanza.textContent = tardanza;
        if (contadorTotal) contadorTotal.textContent = total;
    }

    // Inicializar contadores
    updateContadores();

    // Actualizar bimestre cuando cambie
    if (bimestreSelect) {
        bimestreSelect.addEventListener('change', function() {
            bimestreHidden.value = this.value;
        });
    }

    // Función auxiliar para obtener hora actual en formato HH:MM
    function nowFormatted() {
        const ahora = new Date();
        return ahora.getHours().toString().padStart(2, '0') + ':' +
               ahora.getMinutes().toString().padStart(2, '0');
    }
});
</script>

<style>
.tipo-asistencia-select {
    transition: all 0.2s ease;
    font-weight: 500;
}

.tipo-asistencia-select:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.avatar-sm {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hora-input {
    max-width: 100px;
}

.table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>
@endsection
