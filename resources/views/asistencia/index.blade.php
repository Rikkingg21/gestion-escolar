@extends('layouts.app')
@section('title', 'Asistencia')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-3">
        <h2 class="h3 mb-0 text-primary fw-bold">
            <i class="bi bi-person-check me-2"></i> Registro de Asistencias
        </h2>
    </div>

    <!-- Información del Período -->
    @if(isset($periodoActual))
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-calendar-check fs-4 me-3"></i>
            <div>
                <h5 class="mb-1">Período Activo: <strong>{{ $periodoActual->nombre }}</strong></h5>
                <div class="d-flex flex-wrap gap-3">
                    <span class="badge bg-dark">
                        <i class="bi bi-calendar3 me-1"></i> Año: {{ $periodoActual->anio }}
                    </span>
                    @if($periodoActual->descripcion)
                    <span class="text-muted">
                        <i class="bi bi-info-circle me-1"></i> {{ $periodoActual->descripcion }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No hay períodos activos configurados. Por favor, contacte al administrador.
    </div>
    @endif

    <!-- Filtro de Año y Sección de Asistencia Rápida -->
    <div class="row mb-4">
        <!-- Selector de Años -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border rounded-3">
                <div class="card-body p-3">
                    <h6 class="card-title mb-3 text-secondary fw-normal">
                        <i class="bi bi-calendar me-2"></i> Cambiar Año / Período
                    </h6>
                    <form method="GET" action="{{ route('asistencia.index') }}" id="yearForm">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-filter"></i>
                            </span>
                            <select name="year" class="form-select" onchange="this.form.submit()">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $currentYear == $year ? 'selected' : '' }}>
                                        Año {{ $year }} @if($year == now()->year)(Actual)@endif
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Selecciona un año para ver los grados y asistencias de ese período.
                        </small>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sección de Control de Asistencia -->
        <div class="col-12">
            <div class="card shadow-sm border rounded-3">
                <div class="card-body p-3">
                    <h6 class="card-title mb-3 text-secondary fw-normal">
                        <i class="bi bi-clock-history me-2"></i> Control de Asistencia por Fecha
                    </h6>

                    <!-- Fila de controles principales -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Bimestre</label>
                            <select class="form-select" name="bimestre" id="bimestre" required>
                                <option value="" disabled selected>Seleccionar bimestre</option>
                                <option value="1">Bimestre 1</option>
                                <option value="2">Bimestre 2</option>
                                <option value="3">Bimestre 3</option>
                                <option value="4">Bimestre 4</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-secondary mb-1">Fecha</label>
                            <input type="date"
                                name="fecha"
                                id="fechaInput"
                                class="form-control"
                                value="{{ $fechaPorDefecto }}"
                                min="{{ $periodoActual ? $periodoActual->anio . '-01-01' : '2000-01-01' }}"
                                max="{{ $periodoActual ? $periodoActual->anio . '-12-31' : now()->format('Y-m-d') }}">
                            <small class="text-muted">
                                Año {{ $periodoActual ? $periodoActual->anio : 'actual' }}
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-secondary mb-1">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success flex-fill" id="btnAsistenciaAutomatica">
                                    <i class="bi bi-check-circle me-2"></i> Marcar todos puntual
                                </button>
                                <button type="button" class="btn btn-warning flex-fill" id="btnTardanzaAutomatica">
                                    <i class="bi bi-clock me-2"></i> Marcar todos tardanza
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje de estado de la fecha -->
                    <div id="fechaHelpText" class="small text-muted mt-2"></div>

                    <!-- Botones de Reportes -->
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

    @php
        // Separamos los grados activos (estado == 1) e inactivos (estado == 0)
        $gradosActivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 1);
        $gradosInactivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 0);

        // Verificar si la fecha de hoy tiene registros bloqueados en algún grado
        $fechaHoyBloqueada = $gradosActivos->contains(function($grado) {
            return $grado->tiene_registros_bloqueados_hoy ?? false;
        });
    @endphp

    <!-- 1. TABLA DE GRADOS ACTIVOS (ESTADO 1) -->
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-check-circle-fill me-2"></i> Grados Activos ({{ $gradosActivos->count() }})
                </h4>
                @if(isset($periodoActual))
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
                            // Calcular porcentaje de asistencia de hoy
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
                                    <button class="btn btn-sm btn-secondary rounded-3" disabled>
                                        <i class="bi bi-lock me-1"></i> Bloqueado
                                    </button>
                                    <small class="d-block text-muted mt-1">
                                        Registros confirmados
                                    </small>
                                @else
                                    <div class="btn-group-vertical btn-group-sm gap-1" role="group">
                                        <a href="{{ route('asistencia.grado', [
                                            'grado_grado_seccion' => $grado->grado . $grado->seccion,
                                            'grado_nivel' => strtolower($grado->nivel),
                                            'date' => now()->format('d-m-Y')
                                        ]) }}" class="btn btn-outline-primary rounded-3">
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

    <!-- 2. TABLA DE GRADOS INACTIVOS (ESTADO 0) - Solo si existen -->
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
    const btnAsistenciaAutomatica = document.getElementById('btnAsistenciaAutomatica');
    const btnTardanzaAutomatica = document.getElementById('btnTardanzaAutomatica');
    const bimestreSelect = document.getElementById('bimestre');
    const fechaInput = document.getElementById('fechaInput');
    const periodoAnio = {{ $periodoActual ? $periodoActual->anio : 'null' }};
    const currentYear = {{ $currentYear }};

    // Elemento para mostrar mensajes de estado
    const fechaHelpText = document.getElementById('fechaHelpText');

    // Crear contenedor de toasts si no existe
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    // Función para mostrar notificaciones toast
    function mostrarNotificacion(titulo, mensaje, tipo = 'success') {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-${tipo} text-white">
                    <strong class="me-auto">${titulo}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${mensaje}
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Función para verificar si una fecha está dentro del año del período
    function fechaEnPeriodo(fecha) {
        if (!fecha || !periodoAnio) return true;
        const añoFecha = new Date(fecha).getFullYear();
        return añoFecha === periodoAnio;
    }

    // Función para actualizar el estado de los botones y mostrar mensajes
    function actualizarEstadoBotones(data) {
        // Ambos botones SIEMPRE están habilitados, solo cambian los mensajes
        [btnAsistenciaAutomatica, btnTardanzaAutomatica].forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('btn-secondary');
        });

        btnAsistenciaAutomatica.classList.add('btn-success');
        btnTardanzaAutomatica.classList.add('btn-warning');
        btnAsistenciaAutomatica.title = 'Marcar todos como puntuales';
        btnTardanzaAutomatica.title = 'Marcar todos con tardanza';

        if (data.existe_registro_pendiente) {
            fechaHelpText.innerHTML = `Atención: Ya existen ${data.total_pendientes || ''} registro(s) pendiente(s) para esta fecha. Se marcarán SOLO los estudiantes restantes.`;
            fechaHelpText.className = 'small mt-2 text-warning';
        } else if (data.bimestre) {
            fechaHelpText.innerHTML = `Fecha disponible. Bimestre ${data.bimestre} detectado.`;
            fechaHelpText.className = 'small mt-2 text-success';
        } else {
            fechaHelpText.innerHTML = `Fecha disponible. No hay registros previos.`;
            fechaHelpText.className = 'small mt-2 text-info';
        }
    }

    // Función para verificar el estado de la fecha
    function verificarEstadoFecha() {
        const fecha = fechaInput.value;
        if (!fecha || !periodoAnio) return;

        // Mostrar indicador de carga
        fechaHelpText.innerHTML = 'Verificando fecha...';
        fechaHelpText.className = 'small mt-2 text-muted';

        fetch(`{{ route("asistencia.obtener-bimestre-y-estado-por-fecha") }}?fecha=${fecha}`, {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Auto-seleccionar bimestre si existe
                if (data.bimestre) {
                    bimestreSelect.value = data.bimestre;
                } else {
                    bimestreSelect.value = '';
                }

                // Actualizar estado de los botones
                actualizarEstadoBotones(data);

                // Actualizar títulos de botones de resto
                actualizarTitulosBotones();
            } else {
                // Si hay error, resetear todo
                bimestreSelect.value = '';
                [btnAsistenciaAutomatica, btnTardanzaAutomatica].forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('btn-secondary');
                });
                btnAsistenciaAutomatica.classList.add('btn-success');
                btnTardanzaAutomatica.classList.add('btn-warning');
                btnAsistenciaAutomatica.title = 'Marcar todos como puntuales';
                btnTardanzaAutomatica.title = 'Marcar todos con tardanza';

                fechaHelpText.innerHTML = data.message;
                fechaHelpText.className = 'small mt-2 text-danger';
            }
        })
        .catch(error => {
            console.error('Error al verificar fecha:', error);
            fechaHelpText.innerHTML = 'Error al verificar la fecha';
            fechaHelpText.className = 'small mt-2 text-danger';
        });
    }

    // Validar que la fecha seleccionada esté dentro del año del período
    fechaInput.addEventListener('change', function() {
        const fecha = this.value;

        if (!fechaEnPeriodo(fecha)) {
            mostrarNotificacion(
                'Fecha no válida',
                `La fecha debe estar dentro del año ${periodoAnio}.`,
                'warning'
            );

            // Restaurar la fecha anterior o establecer una por defecto
            if (periodoAnio === new Date().getFullYear()) {
                this.value = new Date().toISOString().split('T')[0];
            } else {
                this.value = `${periodoAnio}-01-01`;
            }
        }

        // Verificar estado de la nueva fecha
        verificarEstadoFecha();
    });

    // También verificar cuando se escribe manualmente
    fechaInput.addEventListener('input', function() {
        if (this.value.length === 10) { // Cuando la fecha está completa (YYYY-MM-DD)
            verificarEstadoFecha();
        }
    });

    // Verificar estado al cargar la página
    if (fechaInput.value) {
        verificarEstadoFecha();
    }

    // Función genérica para marcar todos los estudiantes (puntual o tardanza)
    function marcarTodos(tipo, boton) {
        // Validar que la fecha esté dentro del año del período
        if (!fechaEnPeriodo(fechaInput.value)) {
            mostrarNotificacion(
                'Fecha no válida',
                `La fecha debe estar dentro del año ${periodoAnio}.`,
                'warning'
            );
            return;
        }

        if (!bimestreSelect.value) {
            mostrarNotificacion(
                'Bimestre requerido',
                'Por favor, seleccione un bimestre',
                'warning'
            );
            return;
        }

        const tipoTexto = tipo === 'puntual' ? 'puntuales' : 'con tardanza';
        if (!confirm(`¿Estás seguro de marcar a TODOS los estudiantes como ${tipoTexto}?\n\n` +
                     '• Se procesarán TODOS los grados activos\n' +
                     '• Solo se marcarán estudiantes SIN asistencia\n' +
                     '• Los registros existentes NO se sobrescribirán')) {
            return;
        }

        const originalText = boton.innerHTML;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Procesando...';
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
                bimestre: bimestreSelect.value
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Error en la solicitud');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mostrar notificación
                mostrarNotificacion(
                    'Operación exitosa',
                    data.message,
                    'success'
                );

                // Actualizar el texto de ayuda
                fechaHelpText.innerHTML = `${data.total_afectados} estudiantes marcados en ${data.total_grados_procesados} grados`;
                fechaHelpText.className = 'small mt-2 text-success';

                // Recargar después de 2 segundos
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            mostrarNotificacion(
                'Error',
                error.message || 'Error al procesar la solicitud',
                'danger'
            );
            console.error('Error:', error);

            // Restaurar el estado del botón
            boton.innerHTML = originalText;
            boton.disabled = false;

            // Verificar nuevamente el estado
            verificarEstadoFecha();
        });
    }

    // Event listeners para botones de marcado masivo
    btnAsistenciaAutomatica.addEventListener('click', function() {
        marcarTodos('puntual', this);
    });

    btnTardanzaAutomatica.addEventListener('click', function() {
        marcarTodos('tardanza', this);
    });

    // Función para marcar resto de estudiantes (por grado)
    function marcarRestoEstudiantes(gradoId, gradoNombre, tipo, boton) {
        const fecha = fechaInput.value;
        const bimestre = bimestreSelect.value;
        const todosTienen = boton.dataset.todosTienen === 'true';

        if (!fecha) {
            mostrarNotificacion('Error', 'Debe seleccionar una fecha primero', 'warning');
            return;
        }

        if (!bimestre) {
            mostrarNotificacion('Error', 'Debe seleccionar un bimestre primero', 'warning');
            return;
        }

        // Verificar si la fecha está en el período
        if (!fechaEnPeriodo(fecha)) {
            mostrarNotificacion(
                'Fecha no válida',
                `La fecha debe estar dentro del año ${periodoAnio}.`,
                'warning'
            );
            return;
        }

        // Verificar si ya todos tienen asistencia
        if (todosTienen) {
            mostrarNotificacion(
                'Grado completo',
                `Todos los estudiantes de ${gradoNombre} ya tienen asistencia registrada para hoy.`,
                'info'
            );
            return;
        }

        const tipoTexto = tipo === 'puntual' ? 'PUNTUALES' : 'con TARDANZA';
        const mensajeConfirmacion = `¿Estás seguro de marcar al RESTO de estudiantes de ${gradoNombre} como ${tipoTexto}?\n\n` +
            '• Solo se marcarán estudiantes SIN asistencia\n' +
            '• Los registros existentes NO se sobrescribirán';

        if (!confirm(mensajeConfirmacion)) {
            return;
        }

        // Deshabilitar botón
        const originalText = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Procesando...';

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
                fecha: fecha,
                bimestre: bimestre
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Error en la solicitud');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarNotificacion(
                    'Éxito',
                    `${data.message} en ${gradoNombre}`,
                    'success'
                );

                // Recargar después de 2 segundos
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            mostrarNotificacion(
                'Error',
                error.message || 'Error al procesar la solicitud',
                'danger'
            );
            console.error('Error:', error);

            // Restaurar botón
            boton.disabled = false;
            boton.innerHTML = originalText;
        });
    }

    // Función para actualizar títulos y estados de botones de resto
    function actualizarTitulosBotones() {
        const fecha = fechaInput.value;
        const bimestre = bimestreSelect.value;
        const fechaValida = fecha && fechaEnPeriodo(fecha);

        document.querySelectorAll('.btn-resto-puntualidad, .btn-resto-tardanza').forEach(btn => {
            const todosTienen = btn.dataset.todosTienen === 'true';

            if (!fechaValida || !bimestre) {
                btn.disabled = true;
                btn.title = 'Seleccione fecha y bimestre válidos primero';
            } else if (todosTienen) {
                btn.disabled = true;
                btn.title = 'Todos los estudiantes ya tienen asistencia';
            } else {
                btn.disabled = false;
                btn.title = btn.classList.contains('btn-resto-puntualidad')
                    ? 'Marcar al resto como puntuales'
                    : 'Marcar al resto con tardanza';
            }
        });
    }

    // Agregar event listeners a los botones de resto de estudiantes
    document.querySelectorAll('.btn-resto-puntualidad').forEach(btn => {
        btn.addEventListener('click', function() {
            const gradoId = this.dataset.gradoId;
            const gradoNombre = this.dataset.gradoNombre;
            marcarRestoEstudiantes(gradoId, gradoNombre, 'puntual', this);
        });
    });

    document.querySelectorAll('.btn-resto-tardanza').forEach(btn => {
        btn.addEventListener('click', function() {
            const gradoId = this.dataset.gradoId;
            const gradoNombre = this.dataset.gradoNombre;
            marcarRestoEstudiantes(gradoId, gradoNombre, 'tardanza', this);
        });
    });

    // Escuchar cambios en fecha y bimestre
    fechaInput.addEventListener('change', actualizarTitulosBotones);
    bimestreSelect.addEventListener('change', actualizarTitulosBotones);

    // Inicializar títulos
    actualizarTitulosBotones();
});
</script>
@endsection
