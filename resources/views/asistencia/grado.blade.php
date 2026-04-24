@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-2">
                Asistencia: {{ $grado_nombre }} - {{ $grado_nivel }}
            </h3>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="badge {{ $existenRegistros ? 'bg-success' : 'bg-warning' }} me-2">
                    {{ $existenRegistros ? 'Registrada' : 'Pendiente' }}
                </span>

                @if($mesBloqueado)
                    <span class="badge bg-danger me-2">
                        <i class="fas fa-lock me-1"></i>
                        Mes Bloqueado ({{ $cantidadBloqueados }} registros bloqueados)
                    </span>
                @else
                    <span class="badge bg-success me-2">
                        <i class="fas fa-unlock me-1"></i>
                        Mes Libre
                    </span>
                @endif

                <small class="text-muted">
                    <i class="far fa-calendar-alt me-1"></i>
                    {{ $fechaLegible }}
                </small>

                @if(isset($periodoActual))
                <small class="text-muted ms-2">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Período: {{ $periodoActual->nombre }}
                </small>
                @endif
            </div>
        </div>
    </div>

    @if($mesBloqueado)
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-lock fa-2x me-3"></i>
                <div>
                    <strong>Mes bloqueado:</strong> Este mes ya tiene {{ $cantidadBloqueados }} asistencia(s) bloqueada(s).
                    No se pueden realizar modificaciones ni agregar nuevos registros.
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

    {{-- Panel de controles --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Bimestre:</label>
                    <select class="form-select" name="periodobimestre_id" id="periodobimestre_id" required
                        {{ $existenRegistros || $mesBloqueado ? 'disabled' : '' }}>
                        @if($existenRegistros && $bimestreActual)
                            <option value="{{ $bimestreActual->id }}" selected>
                                {{ $bimestreActual->bimestre }}
                                ({{ \Carbon\Carbon::parse($bimestreActual->fecha_inicio)->format('d/m/Y') }} -
                                {{ \Carbon\Carbon::parse($bimestreActual->fecha_fin)->format('d/m/Y') }})
                                @if($bimestreActual->tipo_bimestre == 'A') - Académico @else - Recuperación @endif
                            </option>
                        @else
                            <option value="" disabled selected>Seleccione bimestre</option>
                            @foreach($bimestresPeriodo as $bimestre)
                            <option value="{{ $bimestre['id'] }}"
                                {{ $bimestreSeleccionadoId == $bimestre['id'] ? 'selected' : '' }}
                                data-fecha-inicio="{{ $bimestre['fecha_inicio'] }}"
                                data-fecha-fin="{{ $bimestre['fecha_fin'] }}">
                                {{ $bimestre['nombre'] }}
                                ({{ $bimestre['fecha_inicio_formateada'] }} -
                                {{ $bimestre['fecha_fin_formateada'] }})
                                - {{ $bimestre['tipo'] }}
                            </option>
                            @endforeach
                        @endif
                    </select>
                    @if($mesBloqueado)
                        <small class="text-danger d-block mt-1">
                            <i class="fas fa-info-circle"></i> Mes bloqueado - No se puede modificar
                        </small>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha:</label>
                    <div class="input-group">
                        <input type="date"
                            name="fecha"
                            id="fechaInput"
                            class="form-control"
                            value="{{ $fechaFormateada }}"
                            min="{{ $bimestreActual ? $bimestreActual->fecha_inicio : ($periodoActual ? $periodoActual->fecha_inicio : '2000-01-01') }}"
                            max="{{ $bimestreActual ? $bimestreActual->fecha_fin : ($periodoActual ? $periodoActual->fecha_fin : now()->format('Y-m-d')) }}">
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

    {{-- Formulario principal --}}
    <form id="formAsistencia"
        action="{{ route('asistencia.guardar-multiple', ['grado' => $grado->id, 'fecha' => $fechaFormateada]) }}"
        method="POST">
        @csrf

        <input type="hidden" name="periodobimestre_id" id="periodobimestreHidden" value="{{ $bimestreActual ? $bimestreActual->id : '' }}">
        <input type="hidden" name="periodo_id" value="{{ $periodoActual->id }}">

        {{-- Sección: Estudiantes Matriculados Activos --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header {{ $mesBloqueado ? 'bg-secondary' : 'bg-success' }} text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-check me-1"></i>
                        Estudiantes Matriculados Activos
                        <span class="badge bg-light {{ $mesBloqueado ? 'text-secondary' : 'text-success' }} ms-2">
                            {{ count($estudiantesActivos) }}
                        </span>
                    </h5>
                    @if($mesBloqueado)
                        <span class="badge bg-danger">
                            <i class="fas fa-lock me-1"></i> Solo lectura - Mes bloqueado
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Estudiante</th>
                                <th width="300">Tipo Asistencia</th>
                                <th width="130">Hora</th>
                                <th width="100">Estado</th>
                                <th width="250" class="text-center">Acciones Rápidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($estudiantesActivos as $index => $estudiante)
                            <tr id="estudiante-{{ $estudiante['id'] }}"
                                class="{{ $mesBloqueado ? 'table-secondary' : '' }}"
                                data-estudiante-id="{{ $estudiante['id'] }}">
                                <td class="align-middle">{{ $index + 1 }}</td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-semibold {{ $mesBloqueado ? 'text-muted' : '' }}">
                                                {{ $estudiante['nombre_completo'] }}
                                            </div>
                                            <small class="{{ $mesBloqueado ? 'text-muted' : 'text-success' }}">
                                                <i class="fas fa-check-circle"></i> Matriculado Activo
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <select name="asistencias[{{ $estudiante['id'] }}]"
                                            class="form-select form-select-sm tipo-asistencia-select"
                                            data-estudiante-id="{{ $estudiante['id'] }}"
                                            style="min-width: 230px; {{ $estudiante['tipo_asistencia_id'] ? 'border-left: 4px solid ' . $estudiante['tipo_asistencia_color'] . '; background-color: ' . $estudiante['tipo_asistencia_color'] . '10;' : '' }}"
                                            {{ $mesBloqueado ? 'disabled' : '' }}>
                                        <option value="">Seleccionar tipo de asistencia</option>
                                        @foreach($tiposAsistencia as $tipo)
                                        <option value="{{ $tipo['id'] }}"
                                                data-color="{{ $tipo['color_hex'] }}"
                                                data-nombre="{{ $tipo['nombre'] }}"
                                                style="background-color: {{ $tipo['color_hex'] }}20; color: {{ $tipo['color_hex'] }};"
                                                {{ $estudiante['tipo_asistencia_id'] == $tipo['id'] ? 'selected' : '' }}>
                                            ● {{ $tipo['nombre'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="align-middle">
                                    <div class="input-group input-group-sm">
                                        <input type="time"
                                            name="horas[{{ $estudiante['id'] }}]"
                                            class="form-control hora-input"
                                            value="{{ $estudiante['hora'] }}"
                                            data-estudiante-id="{{ $estudiante['id'] }}"
                                            {{ $mesBloqueado ? 'disabled' : '' }}>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm btn-hora-ahora"
                                                data-estudiante-id="{{ $estudiante['id'] }}"
                                                {{ $mesBloqueado ? 'disabled' : '' }}
                                                title="Establecer hora actual">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span id="estado-{{ $estudiante['id'] }}" class="badge {{ $estudiante['estado_clase'] }}">
                                        {{ $estudiante['estado'] }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button"
                                                class="btn btn-outline-success btn-marcar-rapido"
                                                data-estudiante-id="{{ $estudiante['id'] }}"
                                                data-tipo="5"
                                                title="Marcar como Puntual"
                                                {{ $mesBloqueado ? 'disabled' : '' }}>
                                            <i class="fas fa-check"></i> Puntual
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-marcar-rapido"
                                                data-estudiante-id="{{ $estudiante['id'] }}"
                                                data-tipo="1"
                                                title="Marcar como Tardanza"
                                                {{ $mesBloqueado ? 'disabled' : '' }}>
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
                                        <p>No hay estudiantes matriculados activos</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sección: Estudiantes Matriculados Retirados --}}
        @if(count($estudiantesRetirados) > 0)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-slash me-1"></i>
                        Estudiantes Retirados
                        <span class="badge bg-light text-dark ms-2">
                            {{ count($estudiantesRetirados) }}
                        </span>
                    </h5>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Estudiante</th>
                                <th width="250">Tipo Asistencia</th>
                                <th width="120">Hora</th>
                                <th width="100">Estado</th>
                                <th width="250" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesRetirados as $index => $estudiante)
                            <tr class="table-secondary">
                                <td class="align-middle">{{ $index + 1 }}</td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-semibold text-muted">
                                                {{ $estudiante['nombre_completo'] }}
                                            </div>
                                            <small class="text-danger">
                                                <i class="fas fa-user-times"></i> Retirado
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <select class="form-select form-select-sm" disabled style="min-width: 230px;">
                                        <option value="">Seleccionar tipo de asistencia</option>
                                        @foreach($tiposAsistencia as $tipo)
                                        <option value="{{ $tipo['id'] }}" {{ $estudiante['tipo_asistencia_id'] == $tipo['id'] ? 'selected' : '' }}>
                                            {{ $tipo['nombre'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="align-middle">
                                    <input type="time" class="form-control form-control-sm" value="{{ $estudiante['hora'] }}" disabled>
                                </td>
                                <td class="align-middle">
                                    <span class="badge {{ $estudiante['estado_clase'] }}">
                                        {{ $estudiante['estado'] }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary" disabled>
                                            <i class="fas fa-check"></i> Puntual
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" disabled>
                                            <i class="fas fa-clock"></i> Tardanza
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-footer bg-light">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            @if($existenRegistros)
                                Total registrado: {{ count($estudiantesActivos) + count($estudiantesRetirados) }} estudiantes
                            @else
                                Activos: {{ count($estudiantesActivos) }} |
                                Retirados: {{ count($estudiantesRetirados) }} |
                                Total: {{ count($estudiantesActivos) + count($estudiantesRetirados) }}
                            @endif
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="submit"
                                class="btn btn-primary"
                                {{ $mesBloqueado ? 'disabled' : '' }}>
                            <i class="fas fa-save"></i>
                            {{ $existenRegistros ? 'Actualizar Asistencia' : 'Guardar Asistencia' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const MES_BLOQUEADO = {{ $mesBloqueado ? 'true' : 'false' }};

    const fechaInput = document.getElementById('fechaInput');
    const btnHoy = document.getElementById('btnHoy');
    const periodobimestreSelect = document.getElementById('periodobimestre_id');
    const periodobimestreHidden = document.getElementById('periodobimestreHidden');
    const formAsistencia = document.getElementById('formAsistencia');
    const contadorPuntual = document.getElementById('contadorPuntual');
    const contadorTardanza = document.getElementById('contadorTardanza');
    const contadorTotal = document.getElementById('contadorTotal');

    function actualizarRangosFecha() {
        const selectedOption = periodobimestreSelect.options[periodobimestreSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const fechaInicio = selectedOption.dataset.fechaInicio;
            const fechaFin = selectedOption.dataset.fechaFin;
            if (fechaInicio && fechaFin && fechaInput) {
                fechaInput.min = fechaInicio;
                fechaInput.max = fechaFin;
            }
        }
    }

    // Cambiar fecha - navegación SIN parámetros en URL
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            const fechaEnFormatoYMD = this.value;
            if (fechaEnFormatoYMD) {
                const partes = fechaEnFormatoYMD.split('-');
                const fechaEnFormatoDMY = partes[2] + '-' + partes[1] + '-' + partes[0];
                const gradoSeccion = "{{ $grado->grado }}{{ $grado->seccion }}";
                const gradoNivel = "{{ strtolower($grado->nivel) }}";

                // Construir URL sin parámetros adicionales
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
            if (fechaInput) {
                fechaInput.value = hoy;
                fechaInput.dispatchEvent(new Event('change'));
            }
        });
    }

    // Cuando cambia el bimestre, actualizar rangos de fecha
    if (periodobimestreSelect && !MES_BLOQUEADO) {
        periodobimestreSelect.addEventListener('change', function() {
            actualizarRangosFecha();
            periodobimestreHidden.value = this.value;
        });
        actualizarRangosFecha();
    }

    // FUNCIONES DE EDICIÓN - SOLO SI EL MES ESTÁ LIBRE
    if (!MES_BLOQUEADO) {
        function updateSelectColor(select) {
            const selectedOption = select.options[select.selectedIndex];
            const color = selectedOption?.getAttribute('data-color') || '#6B7280';

            select.style.borderColor = color;
            select.style.borderLeft = `4px solid ${color}`;
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
            let puntual = 0, tardanza = 0, total = 0;
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

        function actualizarEstadoBotonesRapidos() {
            document.querySelectorAll('.btn-marcar-rapido:not([disabled])').forEach(btn => {
                const estudianteId = btn.dataset.estudianteId;
                const select = document.querySelector(`select[name="asistencias[${estudianteId}]"]`);
                if (select && select.value) {
                    btn.disabled = true;
                    btn.classList.add('disabled');
                }
            });
        }

        function nowFormatted() {
            const ahora = new Date();
            return ahora.getHours().toString().padStart(2, '0') + ':' + ahora.getMinutes().toString().padStart(2, '0');
        }

        // Configurar selects
        document.querySelectorAll('.tipo-asistencia-select:not([disabled])').forEach(select => {
            updateSelectColor(select);
            select.addEventListener('change', function() {
                updateSelectColor(this);
                updateEstado(this.dataset.estudianteId, 'Registrado');
                updateContadores();
                actualizarEstadoBotonesRapidos();
            });
        });

        // Botones de marcado rápido
        document.querySelectorAll('.btn-marcar-rapido:not([disabled])').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                if (this.disabled) return;

                const estudianteId = this.dataset.estudianteId;
                const tipoId = this.dataset.tipo;

                if (!periodobimestreSelect.value && !periodobimestreHidden.value) {
                    alert('Por favor, seleccione un bimestre primero');
                    return;
                }

                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                const periodobimestreValue = periodobimestreSelect.value || periodobimestreHidden.value;

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
                            fecha: '{{ $fechaSeleccionada }}',
                            hora: nowFormatted(),
                            grado_id: '{{ $grado->id }}',
                            periodo_id: '{{ $periodoActual->id }}',
                            periodobimestre_id: periodobimestreValue,
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        const select = document.querySelector(`select[name="asistencias[${estudianteId}]"]`);
                        if (select) {
                            select.value = data.asistencia.tipo_asistencia_id;
                            updateSelectColor(select);
                        }
                        const horaInput = document.querySelector(`input[name="horas[${estudianteId}]"]`);
                        if (horaInput) horaInput.value = data.asistencia.hora;
                        updateEstado(estudianteId, 'Registrado');
                        updateContadores();
                        actualizarEstadoBotonesRapidos();

                        const fila = document.getElementById(`estudiante-${estudianteId}`);
                        if (fila) {
                            fila.classList.add('table-success');
                            setTimeout(() => fila.classList.remove('table-success'), 1500);
                        }
                    } else {
                        alert(data.message || 'Error al registrar la asistencia');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ocurrió un error al conectar con el servidor');
                } finally {
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            });
        });

        // Botones de hora actual
        document.querySelectorAll('.btn-hora-ahora:not([disabled])').forEach(btn => {
            btn.addEventListener('click', function() {
                const estudianteId = this.dataset.estudianteId;
                const horaInput = document.querySelector(`input[name="horas[${estudianteId}]"]:not([disabled])`);
                if (horaInput) {
                    const ahora = new Date();
                    horaInput.value = ahora.getHours().toString().padStart(2, '0') + ':' + ahora.getMinutes().toString().padStart(2, '0');
                }
            });
        });

        // Validar formulario
        if (formAsistencia) {
            formAsistencia.addEventListener('submit', function(e) {
                if (!periodobimestreSelect.disabled && !periodobimestreSelect.value && !periodobimestreHidden.value) {
                    e.preventDefault();
                    alert('Por favor, seleccione un bimestre');
                    periodobimestreSelect.focus();
                    return;
                }
                if (periodobimestreSelect.value) periodobimestreHidden.value = periodobimestreSelect.value;
            });
        }

        // Inicializar
        updateContadores();
        actualizarEstadoBotonesRapidos();
    } else {
        // Solo actualizar contadores para visualización
        function updateContadoresReadOnly() {
            let puntual = 0, tardanza = 0, total = 0;
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
        updateContadoresReadOnly();
    }
});
</script>

<style>
    .tipo-asistencia-select {
        min-width: 230px;
        padding: 0.375rem 1.75rem 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .tipo-asistencia-select:not([disabled]):hover {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }

    .tipo-asistencia-select[disabled] {
        background-color: #e9ecef;
        opacity: 0.8;
    }

    @media (max-width: 768px) {
        .tipo-asistencia-select {
            min-width: 180px;
        }
    }
</style>
@endsection
