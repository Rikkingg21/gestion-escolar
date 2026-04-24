@extends('layouts.app')
@section('title', 'Asistencia')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-3">
        <h2 class="h3 mb-0 text-primary fw-bold">
            <i class="bi bi-person-check me-2"></i> Registro de Asistencias
        </h2>
    </div>

    <!-- FILTROS: Período, Bimestre y Fecha -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border rounded-3">
                <div class="card-body p-4">
                    <h6 class="card-title mb-3 text-secondary fw-normal">
                        <i class="bi bi-funnel me-2"></i> Filtros de Asistencia
                    </h6>

                    <form method="GET" action="{{ route('asistencia.index') }}" id="filtroForm">
                        <div class="row g-3">
                            <!-- Selector de Período -->
                            <div class="col-md-4">
                                <label class="form-label small text-secondary mb-1">
                                    <i class="bi bi-calendar3 me-1"></i> Período Académico
                                </label>
                                <select name="periodo_id" id="periodo_id" class="form-select">
                                    <option value="">Todos los períodos</option>
                                    @foreach($periodos as $periodo)
                                        <option value="{{ $periodo->id }}"
                                            {{ $periodoActual && $periodoActual->id == $periodo->id ? 'selected' : '' }}>
                                            {{ $periodo->nombre }} ({{ $periodo->anio }})
                                            @if($periodo->tipo_periodo == 'año escolar')
                                                - Año Escolar
                                            @else
                                                - Recuperación
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Selector de Bimestre -->
                            <div class="col-md-3">
                                <label class="form-label small text-secondary mb-1">
                                    <i class="bi bi-layers me-1"></i> Bimestre
                                </label>
                                <select name="periodobimestre_id" id="periodobimestre_id" class="form-select" {{ !$periodoActual ? 'disabled' : '' }}>
                                    <option value="">Seleccionar bimestre</option>
                                    @if($bimestres && $bimestres->count() > 0)
                                        @foreach($bimestres as $bimestre)
                                            <option value="{{ $bimestre->id }}"
                                                {{ $bimestreActual && $bimestreActual->id == $bimestre->id ? 'selected' : '' }}
                                                data-fecha-inicio="{{ $bimestre->fecha_inicio }}"
                                                data-fecha-fin="{{ $bimestre->fecha_fin }}">
                                                {{ $bimestre->bimestre }}
                                                @if($bimestre->tipo_bimestre == 'A')
                                                    (Académico)
                                                @else
                                                    (Recuperación)
                                                @endif
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Selector de Fecha -->
                            <div class="col-md-3">
                                <label class="form-label small text-secondary mb-1">
                                    <i class="bi bi-calendar-date me-1"></i> Fecha
                                </label>
                                <input type="date"
                                       name="fecha"
                                       id="fechaInput"
                                       class="form-control"
                                       value="{{ $fechaSeleccionada }}"
                                       min="{{ $bimestreActual ? $bimestreActual->fecha_inicio : ($periodoActual ? $periodoActual->fecha_inicio : '2000-01-01') }}"
                                       max="{{ $bimestreActual ? $bimestreActual->fecha_fin : ($periodoActual ? $periodoActual->fecha_fin : now()->format('Y-m-d')) }}">
                            </div>

                            <!-- Botón de filtro -->
                            <div class="col-md-2">
                                <label class="form-label small text-secondary mb-1">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Información adicional del período y bimestre -->
                    @if($periodoActual)
                    <div class="alert alert-info mt-3 mb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>{{ $periodoActual->nombre }}</strong>
                                ({{ \Carbon\Carbon::parse($periodoActual->fecha_inicio)->format('d/m/Y') }} -
                                {{ \Carbon\Carbon::parse($periodoActual->fecha_fin)->format('d/m/Y') }})
                            </div>
                            @if($bimestreActual)
                            <div>
                                <span class="badge bg-primary">
                                    <i class="bi bi-layers me-1"></i> {{ $bimestreActual->bimestre }}
                                </span>
                                <span class="badge {{ $bimestreActual->tipo_bimestre == 'A' ? 'bg-success' : 'bg-warning' }} ms-2">
                                    {{ $bimestreActual->tipo_bimestre == 'A' ? 'Académico' : 'Recuperación' }}
                                </span>
                                <span class="badge bg-secondary ms-2">
                                    {{ \Carbon\Carbon::parse($bimestreActual->fecha_inicio)->format('d/m/Y') }} -
                                    {{ \Carbon\Carbon::parse($bimestreActual->fecha_fin)->format('d/m/Y') }}
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Mensaje de estado de la fecha -->
                    <div id="fechaHelpText" class="small mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción rápida -->
    @if($periodoActual && $bimestreActual)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border rounded-3">
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-success w-100" id="btnAsistenciaAutomatica">
                                <i class="bi bi-check-circle me-2"></i> Marcar todos puntual
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-warning w-100" id="btnTardanzaAutomatica">
                                <i class="bi bi-clock me-2"></i> Marcar todos tardanza
                            </button>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <a class="btn btn-outline-danger w-100" href="{{ route('asistencia.reporte') }}">
                                <i class="bi bi-printer me-2"></i> Reportes
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a class="btn btn-outline-secondary w-100" href="{{ route('asistencia.bloqueo') }}">
                                <i class="bi bi-lock me-2"></i> Bloquear Asistencia
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @php
        $gradosActivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 1);
        $gradosInactivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 0);
    @endphp

    <!-- TABLA DE GRADOS ACTIVOS -->
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-check-circle-fill me-2"></i> Grados Activos ({{ $gradosActivos->count() }})
                </h4>
                @if($periodoActual)
                <span class="badge bg-light text-success fs-6">
                    <i class="bi bi-calendar me-1"></i> {{ $periodoActual->nombre }}
                </span>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light text-uppercase">
                        <tr>
                            <th scope="col" class="py-3 px-4">Grado/Sección</th>
                            <th scope="col" class="py-3 px-4">Nivel</th>
                            <th scope="col" class="py-3 px-4 text-center">Estado</th>
                            <th scope="col" class="py-3 px-4 text-center">Asist. Hoy</th>
                            <th scope="col" class="py-3 px-4 text-center">Matriculados</th>
                            <th scope="col" class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gradosActivos as $grado)
                        @php
                            $porcentaje = $grado->estudiantes_matriculados > 0
                                ? round(($grado->asistencias_hoy / $grado->estudiantes_matriculados) * 100, 1)
                                : 0;
                            $tieneBloqueo = $grado->tiene_registros_bloqueados_hoy ?? false;
                        @endphp
                        <tr class="align-middle">
                            <td class="px-4 fw-bold text-primary">{{ $grado->grado }}° "{{ $grado->seccion }}"</td>
                            <td class="px-4 text-muted">{{ $grado->nivel }}</td>
                            <td class="px-4 text-center">
                                <span class="badge text-bg-success fs-6 py-1 px-2 rounded-pill">Activo</span>
                            </td>
                            <td class="px-4 text-center">
                                <div class="d-flex flex-column align-items-center">
                                    @if($grado->asistencias_hoy > 0)
                                        <span class="badge text-bg-primary fs-6 py-2 px-3 rounded-pill mb-1">
                                            {{ $grado->asistencias_hoy }}
                                        </span>
                                        <small class="text-muted">
                                            {{ $porcentaje }}% de {{ $grado->estudiantes_matriculados }}
                                        </small>
                                    @else
                                        <span class="badge text-bg-warning fs-6 py-2 px-3 rounded-pill">
                                            0
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 text-center">
                                <span class="badge text-bg-info fs-6 py-2 px-3 rounded-pill">
                                    {{ $grado->estudiantes_matriculados }}
                                </span>
                            </td>
                            <td class="px-4 text-center">
                                @if($tieneBloqueo)
                                    <a href="{{ route('asistencia.grado', [
                                        'grado_grado_seccion' => $grado->grado . $grado->seccion,
                                        'grado_nivel' => strtolower($grado->nivel),
                                        'date' => \Carbon\Carbon::parse($fechaSeleccionada)->format('d-m-Y') ]) }}"
                                        class="btn btn-outline-primary rounded-3">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                    <small class="d-block text-muted mt-1">
                                        Registros confirmados
                                    </small>
                                @else
                                    <div class="btn-group-vertical btn-group-sm gap-1" role="group">
                                        <a href="{{ route('asistencia.grado', [
                                            'grado_grado_seccion' => $grado->grado . $grado->seccion,
                                            'grado_nivel' => strtolower($grado->nivel),
                                            'date' => \Carbon\Carbon::parse($fechaSeleccionada)->format('d-m-Y') ]) }}"
                                           class="btn btn-outline-primary rounded-3">
                                            <i class="bi bi-pencil-square me-1"></i> Individual
                                        </a>

                                        <button class="btn btn-outline-success rounded-3 btn-resto-puntualidad"
                                                data-grado-id="{{ $grado->id }}"
                                                data-grado-nombre="{{ $grado->grado }}° {{ $grado->seccion }}"
                                                title="Marcar al resto de estudiantes como puntuales">
                                            <i class="bi bi-check-circle me-1"></i> Resto Puntual
                                        </button>

                                        <button class="btn btn-outline-warning rounded-3 btn-resto-tardanza"
                                                data-grado-id="{{ $grado->id }}"
                                                data-grado-nombre="{{ $grado->grado }}° {{ $grado->seccion }}"
                                                title="Marcar al resto de estudiantes con tardanza">
                                            <i class="bi bi-clock-history me-1"></i> Resto Tardanza
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-info-circle me-2"></i> No hay grados activos disponibles para el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABLA DE GRADOS INACTIVOS -->
    @if($gradosInactivos->count() > 0)
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-secondary text-white">
            <h4 class="mb-0">
                <i class="bi bi-slash-circle-fill me-2"></i> Grados Inactivos ({{ $gradosInactivos->count() }})
            </h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light text-uppercase">
                        <tr>
                            <th scope="col" class="py-3 px-4">Grado/Sección</th>
                            <th scope="col" class="py-3 px-4">Nivel</th>
                            <th scope="col" class="py-3 px-4 text-center">Estado</th>
                            <th scope="col" class="py-3 px-4 text-center">Asistencias Hist.</th>
                            <th scope="col" class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gradosInactivos as $grado)
                        <tr class="align-middle">
                            <td class="px-4 fw-bold text-secondary">{{ $grado->grado }}° "{{ $grado->seccion }}"</td>
                            <td class="px-4 text-muted">{{ $grado->nivel }}</td>
                            <td class="px-4 text-center">
                                <span class="badge text-bg-secondary fs-6 py-1 px-2 rounded-pill">Inactivo</span>
                            </td>
                            <td class="px-4 text-center">
                                <span class="badge text-bg-secondary fs-6 py-2 px-3 rounded-pill">
                                    {{ $grado->total_asistencias ?? 0 }}
                                </span>
                            </td>
                            <td class="px-4 text-center">
                                <button class="btn btn-sm btn-outline-secondary rounded-3" disabled>
                                    <i class="bi bi-slash-circle me-1"></i> No disponible
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodoSelect = document.getElementById('periodo_id');
    const periodobimestreSelect = document.getElementById('periodobimestre_id');
    const fechaInput = document.getElementById('fechaInput');
    const btnAsistenciaAutomatica = document.getElementById('btnAsistenciaAutomatica');
    const btnTardanzaAutomatica = document.getElementById('btnTardanzaAutomatica');
    const fechaHelpText = document.getElementById('fechaHelpText');

    // Función para cargar bimestres por período (AJAX)
    function cargarBimestres(periodoId, periodobimestreSeleccionado = null) {
        if (!periodoId) {
            periodobimestreSelect.innerHTML = '<option value="">Primero seleccione período</option>';
            periodobimestreSelect.disabled = true;
            return;
        }

        fetch(`/asistencia/bimestres-por-periodo/${periodoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.bimestres.length > 0) {
                    let options = '<option value="">Seleccionar bimestre</option>';
                    data.bimestres.forEach(bimestre => {
                        const selected = periodobimestreSeleccionado && periodobimestreSeleccionado == bimestre.id ? 'selected' : '';
                        options += `<option value="${bimestre.id}" ${selected}
                            data-fecha-inicio="${bimestre.fecha_inicio}"
                            data-fecha-fin="${bimestre.fecha_fin}">
                            ${bimestre.bimestre}
                            (${bimestre.fecha_inicio} - ${bimestre.fecha_fin})
                            ${bimestre.tipo_bimestre == 'A' ? '- Académico' : '- Recuperación'}
                        </option>`;
                    });
                    periodobimestreSelect.innerHTML = options;
                    periodobimestreSelect.disabled = false;

                    if (periodobimestreSeleccionado) {
                        actualizarRangosFecha();
                    }
                } else {
                    periodobimestreSelect.innerHTML = '<option value="">No hay bimestres disponibles</option>';
                    periodobimestreSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                periodobimestreSelect.innerHTML = '<option value="">Error al cargar bimestres</option>';
                periodobimestreSelect.disabled = true;
            });
    }

    // Función para actualizar rangos de fecha según bimestre seleccionado
    function actualizarRangosFecha() {
        const selectedOption = periodobimestreSelect.options[periodobimestreSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const fechaInicio = selectedOption.dataset.fechaInicio;
            const fechaFin = selectedOption.dataset.fechaFin;

            if (fechaInicio && fechaFin) {
                fechaInput.min = fechaInicio;
                fechaInput.max = fechaFin;

                const fechaActual = fechaInput.value;
                if (fechaActual < fechaInicio || fechaActual > fechaFin) {
                    fechaInput.value = fechaInicio;
                }
            }
        }
    }

    // Evento: cambio de período
    periodoSelect.addEventListener('change', function() {
        const periodoId = this.value;
        if (periodoId) {
            cargarBimestres(periodoId, null);
            periodobimestreSelect.value = '';
            setTimeout(() => {
                if (fechaInput.value) verificarEstadoFecha();
            }, 500);
        } else {
            periodobimestreSelect.innerHTML = '<option value="">Seleccionar bimestre</option>';
            periodobimestreSelect.disabled = true;
        }
    });

    // Evento: cambio de bimestre
    periodobimestreSelect.addEventListener('change', function() {
        actualizarRangosFecha();
        if (fechaInput.value) {
            verificarEstadoFecha();
        }
    });

    // Evento: cambio de fecha
    fechaInput.addEventListener('change', verificarEstadoFecha);
    fechaInput.addEventListener('input', function() {
        if (this.value.length === 10) verificarEstadoFecha();
    });

    // Función para verificar estado de la fecha
    function verificarEstadoFecha() {
        const periodoId = periodoSelect.value;
        const periodobimestreId = periodobimestreSelect.value;
        const fecha = fechaInput.value;

        if (!periodoId || !periodobimestreId || !fecha) {
            if (fechaHelpText) {
                fechaHelpText.innerHTML = '';
            }
            if (btnAsistenciaAutomatica) btnAsistenciaAutomatica.disabled = false;
            if (btnTardanzaAutomatica) btnTardanzaAutomatica.disabled = false;
            return;
        }

        fetch(`/asistencia/obtener-info-fecha?periodo_id=${periodoId}&periodobimestre_id=${periodobimestreId}&fecha=${fecha}`, {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.fecha_valida) {
                fechaHelpText.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> ${data.message}`;
                fechaHelpText.className = 'small mt-2 text-success';
                if (btnAsistenciaAutomatica) btnAsistenciaAutomatica.disabled = false;
                if (btnTardanzaAutomatica) btnTardanzaAutomatica.disabled = false;
            } else {
                fechaHelpText.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> ${data.message}`;
                fechaHelpText.className = 'small mt-2 text-danger';
                if (btnAsistenciaAutomatica) btnAsistenciaAutomatica.disabled = true;
                if (btnTardanzaAutomatica) btnTardanzaAutomatica.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            fechaHelpText.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Error al verificar la fecha';
            fechaHelpText.className = 'small mt-2 text-danger';
        });
    }

    // Inicializar
    if (periodoSelect && periodoSelect.value) {
        const periodobimestreInicial = '{{ $bimestreActual ? $bimestreActual->id : '' }}';
        cargarBimestres(periodoSelect.value, periodobimestreInicial);
    }

    if (fechaInput && fechaInput.value) {
        setTimeout(verificarEstadoFecha, 500);
    }

    // Contenedor de toasts
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    function mostrarNotificacion(titulo, mensaje, tipo = 'success') {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert">
                <div class="toast-header bg-${tipo} text-white">
                    <strong class="me-auto">${titulo}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${mensaje}</div>
            </div>
        `;
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function() { this.remove(); });
    }

    // Marcar todos los estudiantes
    function marcarTodos(tipo, boton) {
        if (!periodoSelect.value) {
            mostrarNotificacion('Período requerido', 'Debe seleccionar un período', 'warning');
            return;
        }
        if (!periodobimestreSelect.value) {
            mostrarNotificacion('Bimestre requerido', 'Debe seleccionar un bimestre', 'warning');
            return;
        }
        if (!fechaInput.value) {
            mostrarNotificacion('Fecha requerida', 'Debe seleccionar una fecha', 'warning');
            return;
        }

        const tipoTexto = tipo === 'puntual' ? 'puntuales' : 'con tardanza';
        if (!confirm(`¿Estás seguro de marcar a TODOS los estudiantes como ${tipoTexto}?`)) return;

        const originalText = boton.innerHTML;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';
        boton.disabled = true;

        const url = tipo === 'puntual'
            ? '{{ route("asistencia.marcar-todos-puntualidad") }}'
            : '{{ route("asistencia.marcar-todos-tardanza") }}';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                fecha: fechaInput.value,
                periodobimestre_id: periodobimestreSelect.value,
                periodo_id: periodoSelect.value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Operación exitosa', data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            mostrarNotificacion('Error', error.message, 'danger');
            boton.innerHTML = originalText;
            boton.disabled = false;
        });
    }

    // Marcar resto de estudiantes
    function marcarRestoEstudiantes(gradoId, gradoNombre, tipo, boton) {
        if (!periodoSelect.value) {
            mostrarNotificacion('Período requerido', 'Debe seleccionar un período', 'warning');
            return;
        }
        if (!periodobimestreSelect.value) {
            mostrarNotificacion('Bimestre requerido', 'Debe seleccionar un bimestre', 'warning');
            return;
        }
        if (!fechaInput.value) {
            mostrarNotificacion('Fecha requerida', 'Debe seleccionar una fecha', 'warning');
            return;
        }

        const tipoTexto = tipo === 'puntual' ? 'PUNTUALES' : 'con TARDANZA';
        if (!confirm(`¿Marcar al RESTO de estudiantes de ${gradoNombre} como ${tipoTexto}?`)) return;

        const originalText = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>';

        const url = tipo === 'puntual'
            ? '{{ route("asistencia.marcar-resto-puntualidad") }}'
            : '{{ route("asistencia.marcar-resto-tardanza") }}';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                grado_id: gradoId,
                fecha: fechaInput.value,
                periodobimestre_id: periodobimestreSelect.value,
                periodo_id: periodoSelect.value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Éxito', `${data.message} en ${gradoNombre}`, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            mostrarNotificacion('Error', error.message, 'danger');
            boton.disabled = false;
            boton.innerHTML = originalText;
        });
    }

    // Event listeners
    if (btnAsistenciaAutomatica) {
        btnAsistenciaAutomatica.addEventListener('click', function() { marcarTodos('puntual', this); });
    }
    if (btnTardanzaAutomatica) {
        btnTardanzaAutomatica.addEventListener('click', function() { marcarTodos('tardanza', this); });
    }

    document.querySelectorAll('.btn-resto-puntualidad').forEach(btn => {
        btn.addEventListener('click', function() {
            marcarRestoEstudiantes(this.dataset.gradoId, this.dataset.gradoNombre, 'puntual', this);
        });
    });

    document.querySelectorAll('.btn-resto-tardanza').forEach(btn => {
        btn.addEventListener('click', function() {
            marcarRestoEstudiantes(this.dataset.gradoId, this.dataset.gradoNombre, 'tardanza', this);
        });
    });
});
</script>
@endsection
