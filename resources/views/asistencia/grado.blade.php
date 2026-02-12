@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-2">
                Asistencia: {{ $grado->grado }}° {{ $grado->seccion }} - {{ $grado->nivel }}
            </h3>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="badge {{ $existenRegistros ? 'bg-success' : 'bg-warning' }} me-2">
                    {{ $existenRegistros ? 'Registrada' : 'Pendiente' }}
                </span>

                {{-- BADGE DE BLOQUEO SIMPLE --}}
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
                    {{ \Carbon\Carbon::createFromFormat('d-m-Y', $fechaSeleccionada)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
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

    {{-- ALERTA DE MES BLOQUEADO  --}}
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

    {{-- Panel de controles - TOTALMENTE DESHABILITADO SI EL MES ESTÁ BLOQUEADO --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Bimestre:</label>
                    <select class="form-select" name="bimestre" id="bimestre" required
                        {{ $existenRegistros || $mesBloqueado ? 'disabled' : '' }}>
                        @if($existenRegistros)
                            <option value="{{ $bimestreActual }}" selected>Bimestre {{ $bimestreActual }}</option>
                        @else
                            <option value="" disabled selected>Seleccione bimestre</option>
                            @for($i = 1; $i <= 4; $i++)
                            <option value="{{ $i }}" {{ old('bimestre') == $i ? 'selected' : '' }}>Bimestre {{ $i }}</option>
                            @endfor
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

    {{-- Formulario principal - TOTALMENTE DESHABILITADO SI EL MES ESTÁ BLOQUEADO --}}
    <form id="formAsistencia"
        action="{{ route('asistencia.guardar-multiple', ['grado' => $grado->id, 'fecha' => $fechaFormateada]) }}"
        method="POST">
        @csrf

        <input type="hidden" name="bimestre" id="bimestreHidden" value="{{ $bimestreActual ?? '' }}">
        <input type="hidden" name="periodo_id" value="{{ $periodoActual->id }}">

        {{-- Sección: Estudiantes Matriculados Activos --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header {{ $mesBloqueado ? 'bg-secondary' : 'bg-success' }} text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-check me-1"></i>
                        Estudiantes Matriculados Activos
                        <span class="badge bg-light {{ $mesBloqueado ? 'text-secondary' : 'text-success' }} ms-2">
                            {{ $estudiantesMatriculadosActivos->count() }}
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
                                <th width="220">Tipo Asistencia</th> {{-- Aumentado de 150 a 220 --}}
                                <th width="120">Hora</th>
                                <th width="100">Estado</th>
                                <th width="250" class="text-center">Acciones Rápidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($estudiantesMatriculadosActivos as $estudiante)
                            @php
                                $asistenciaActual = $estudiante->asistencias->first();
                                $tipoAsistenciaId = $asistenciaActual ? $asistenciaActual->tipo_asistencia_id : null;
                                $horaActual = $asistenciaActual ? substr($asistenciaActual->hora, 0, 5) : now()->format('H:i');
                                $estado = $asistenciaActual ? 'Registrado' : 'Pendiente';
                                $badgeClass = $asistenciaActual ? 'bg-success' : 'bg-warning';
                            @endphp
                            <tr id="estudiante-{{ $estudiante->id }}"
                                class="{{ $mesBloqueado ? 'table-secondary' : '' }}"
                                data-estudiante-id="{{ $estudiante->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-semibold {{ $mesBloqueado ? 'text-muted' : '' }}">
                                                {{ $estudiante->user->apellido_paterno ?? '' }}
                                                {{ $estudiante->user->apellido_materno ?? '' }},
                                                {{ $estudiante->user->nombre ?? '' }}
                                            </div>
                                            <small class="{{ $mesBloqueado ? 'text-muted' : 'text-success' }}">
                                                <i class="fas fa-check-circle"></i> Matriculado Activo
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <select name="asistencias[{{ $estudiante->id }}]"
                                            class="form-select form-select-sm tipo-asistencia-select"
                                            data-estudiante-id="{{ $estudiante->id }}"
                                            style="min-width: 200px; {{ $tipoAsistenciaId ? 'border-left: 5px solid ' . ($tiposAsistencia->firstWhere('id', $tipoAsistenciaId)->color ?? '#6B7280') . ';' : '' }}"
                                            {{ $mesBloqueado ? 'disabled' : '' }}>
                                        <option value="">Seleccionar tipo de asistencia</option> {{-- Texto más descriptivo --}}
                                        @foreach($tiposAsistencia as $tipo)
                                        <option value="{{ $tipo->id }}"
                                                data-color="{{ $tipo->color ?? '#6B7280' }}"
                                                data-nombre="{{ $tipo->nombre }}"
                                                style="background-color: {{ $tipo->color }}20; color: {{ $tipo->color }}; font-weight: {{ $tipoAsistenciaId == $tipo->id ? 'bold' : 'normal' }};"
                                                {{ $tipoAsistenciaId == $tipo->id ? 'selected' : '' }}>
                                            ● {{ $tipo->nombre }} {{-- Círculo de color --}}
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
                                            {{ $mesBloqueado ? 'disabled' : '' }}>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm btn-hora-ahora"
                                                data-estudiante-id="{{ $estudiante->id }}"
                                                {{ $mesBloqueado ? 'disabled' : '' }}>
                                            <i class="fas fa-clock"></i>
                                        </button>
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
                                                {{ $mesBloqueado ? 'disabled' : '' }}>
                                            <i class="fas fa-check"></i> Puntual
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-marcar-rapido"
                                                data-estudiante-id="{{ $estudiante->id }}"
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
        @if($estudiantesMatriculadosRetirados->count() > 0)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-slash me-1"></i>
                        Estudiantes Retirados
                        <span class="badge bg-light text-dark ms-2">
                            {{ $estudiantesMatriculadosRetirados->count() }}
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
                                <th width="220">Tipo Asistencia</th> {{-- Aumentado de 150 a 220 --}}
                                <th width="120">Hora</th>
                                <th width="100">Estado</th>
                                <th width="250" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantesMatriculadosRetirados as $estudiante)
                            @php
                                $asistenciaActual = $estudiante->asistencias->first();
                                $tipoAsistenciaId = $asistenciaActual ? $asistenciaActual->tipo_asistencia_id : null;
                                $horaActual = $asistenciaActual ? substr($asistenciaActual->hora, 0, 5) : '--:--';
                                $estado = $asistenciaActual ? 'Registrado (Retirado)' : 'Sin registro';
                                $badgeClass = $asistenciaActual ? 'bg-secondary' : 'bg-light text-dark';
                            @endphp
                            <tr class="table-secondary">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-semibold text-muted">
                                                {{ $estudiante->user->apellido_paterno ?? '' }}
                                                {{ $estudiante->user->apellido_materno ?? '' }},
                                                {{ $estudiante->user->nombre ?? '' }}
                                            </div>
                                            <small class="text-danger">
                                                <i class="fas fa-user-times"></i> Retirado
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" disabled style="min-width: 200px; background-color: #f8f9fa;">
                                        <option value="">Seleccionar tipo de asistencia</option>
                                        @foreach($tiposAsistencia as $tipo)
                                        <option value="{{ $tipo->id }}"
                                                style="background-color: {{ $tipo->color }}20; color: {{ $tipo->color }};"
                                                {{ $tipoAsistenciaId == $tipo->id ? 'selected' : '' }}>
                                            ● {{ $tipo->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="time"
                                        class="form-control form-control-sm"
                                        value="{{ $horaActual }}"
                                        disabled>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $estado }}
                                    </span>
                                </td>
                                <td class="text-center">
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
                                Total registrado: {{ $estudiantesMatriculadosActivos->count() + $estudiantesMatriculadosRetirados->count() }} estudiantes
                            @else
                                Activos: {{ $estudiantesMatriculadosActivos->count() }} |
                                Retirados: {{ $estudiantesMatriculadosRetirados->count() }} |
                                Total: {{ $estudiantesMatriculadosActivos->count() + $estudiantesMatriculadosRetirados->count() }}
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

        // Elementos del DOM - SIEMPRE se inicializan porque el cambio de fecha debe funcionar
        const fechaInput = document.getElementById('fechaInput');
        const btnHoy = document.getElementById('btnHoy');
        const bimestreSelect = document.getElementById('bimestre');
        const bimestreHidden = document.getElementById('bimestreHidden');
        const formAsistencia = document.getElementById('formAsistencia');
        const contadorPuntual = document.getElementById('contadorPuntual');
        const contadorTardanza = document.getElementById('contadorTardanza');
        const contadorTotal = document.getElementById('contadorTotal');

        // FUNCIONES DE CONSULTA/CAMBIO DE FECHA - SIEMPRE ACTIVAS
        // Cambiar fecha - SIEMPRE FUNCIONA (navegación)
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

        // Botón "Hoy" - SIEMPRE FUNCIONA (navegación)
        if (btnHoy) {
            btnHoy.addEventListener('click', function() {
                const hoy = new Date().toISOString().split('T')[0];
                fechaInput.value = hoy;
                fechaInput.dispatchEvent(new Event('change'));
            });
        }

        // FUNCIONES DE EDICIÓN - SOLO SI EL MES ESTÁ LIBRE
        if (!MES_BLOQUEADO) {
            console.log('Mes libre - Modo edición habilitado');

            // Función para actualizar estado de botones rápidos
            function actualizarEstadoBotonesRapidos() {
                document.querySelectorAll('.btn-marcar-rapido:not([disabled])').forEach(btn => {
                    const estudianteId = btn.dataset.estudianteId;
                    const select = document.querySelector(`select[name="asistencias[${estudianteId}]"]`);

                    if (select && select.value) {
                        btn.disabled = true;
                        btn.classList.add('disabled');
                        btn.title = 'Ya tiene registro de asistencia';
                    }
                });
            }

            // Configurar selects con colores
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
                    const fila = document.getElementById(`estudiante-${estudianteId}`);
                    if (!fila) return;

                    // Deshabilitar botón mientras se procesa
                    this.disabled = true;
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    // Verificar bimestre
                    let bimestreValue;
                    if ('{{ $existenRegistros }}' === '1') {
                        bimestreValue = bimestreHidden.value;
                    } else {
                        if (!bimestreSelect.value) {
                            alert('Por favor, seleccione un bimestre primero');
                            this.disabled = false;
                            this.innerHTML = originalText;
                            return;
                        }
                        bimestreValue = bimestreSelect.value;
                    }

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
                                bimestre: bimestreValue,
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            const select = fila.querySelector(`select[name="asistencias[${estudianteId}]"]`);
                            if (select) {
                                select.value = data.asistencia.tipo_asistencia_id;
                                updateSelectColor(select);
                            }

                            const horaInput = fila.querySelector(`input[name="horas[${estudianteId}]"]`);
                            if (horaInput) {
                                horaInput.value = data.asistencia.hora;
                            }

                            updateEstado(estudianteId, 'Registrado');
                            updateContadores();
                            actualizarEstadoBotonesRapidos();

                            fila.classList.add('table-success');
                            setTimeout(() => fila.classList.remove('table-success'), 1500);
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

                    if (bimestreSelect.value) {
                        bimestreHidden.value = bimestreSelect.value;
                    }
                });
            }

            // Actualizar bimestre cuando cambie
            if (bimestreSelect) {
                bimestreSelect.addEventListener('change', function() {
                    bimestreHidden.value = this.value;
                });
            }

            // Inicializar contadores y botones
            updateContadores();
            actualizarEstadoBotonesRapidos();

        } else {
            console.log('Mes bloqueado - Modo solo consulta');
            // SOLO actualizar contadores para visualización, sin permitir edición
            updateContadores();
        }

        // FUNCIONES AUXILIARES - SIEMPRE DISPONIBLES
        function updateSelectColor(select) {
            const selectedOption = select.options[select.selectedIndex];
            const color = selectedOption?.getAttribute('data-color') || '#6B7280';

            // Estilo principal del select
            select.style.borderColor = color;
            select.style.borderLeft = `5px solid ${color}`;
            select.style.color = '#212529'; // Color de texto normal
            select.style.backgroundColor = color + '08'; // Fondo muy sutil

            // Si hay una opción seleccionada, resaltarla visualmente
            if (select.value) {
                select.style.fontWeight = '500';
            } else {
                select.style.borderLeft = '1px solid #ced4da';
                select.style.fontWeight = 'normal';
                select.style.backgroundColor = 'white';
            }
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

        function nowFormatted() {
            const ahora = new Date();
            return ahora.getHours().toString().padStart(2, '0') + ':' +
                ahora.getMinutes().toString().padStart(2, '0');
        }
    });
</script>
<style>
    /* Estilos para selects de tipo asistencia */
    .tipo-asistencia-select {
        min-width: 220px;
        padding: 0.375rem 1.75rem 0.375rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.5;
        background-position: right 0.5rem center;
        background-size: 16px 12px;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .tipo-asistencia-select option {
        padding: 8px 12px;
        font-size: 0.875rem;
    }

    .tipo-asistencia-select option:hover,
    .tipo-asistencia-select option:focus,
    .tipo-asistencia-select option:active,
    .tipo-asistencia-select option:checked {
        background: linear-gradient(0deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.1) 100%);
    }

    /* Mejorar visualización en hover */
    .tipo-asistencia-select:not([disabled]):hover {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }

    /* Estilo para cuando el select está deshabilitado */
    .tipo-asistencia-select[disabled] {
        background-color: #e9ecef;
        opacity: 0.8;
    }

    /* Borde izquierdo coloreado para selects con valor seleccionado */
    .tipo-asistencia-select option[selected] {
        font-weight: bold;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .tipo-asistencia-select {
            min-width: 180px;
        }
    }
</style>
@endsection
