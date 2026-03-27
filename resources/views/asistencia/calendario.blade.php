@extends('layouts.app')
@section('title', 'Calendario de Asistencia')
@section('content')
<div class="container py-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-calendar-check text-primary"></i>
                    Mi Calendario de Asistencias
                </h2>
                <div class="text-muted">
                    {{ $estudiante->user->nombre_completo ?? 'Estudiante' }}
                    • {{ $estudiante->grado->grado ?? '' }}° {{ $estudiante->grado->seccion ?? '' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Filtros de Asistencia</h5>
                    <form method="GET" class="row g-3" id="filtroForm">
                        <div class="col-md-4">
                            <label class="form-label">Año</label>
                            <select name="anio" class="form-select" id="selectAnio">
                                @foreach($aniosDisponibles as $anioOption)
                                    <option value="{{ $anioOption }}"
                                            {{ $anio == $anioOption ? 'selected' : '' }}>
                                        {{ $anioOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bimestre</label>
                            <select name="bimestre" class="form-select" id="selectBimestre">
                                @foreach($bimestresDisponibles as $bim)
                                    @if($bim == 'todos')
                                        <option value="todos"
                                                {{ ($bimestre == null || $bimestre == 'todos') ? 'selected' : '' }}>
                                            Todos los bimestres
                                        </option>
                                    @else
                                        <option value="{{ $bim }}"
                                                {{ $bimestre == $bim ? 'selected' : '' }}>
                                            Bimestre {{ $bim }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Resumen {{ $anio }}</h5>
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="text-success">
                                <h4>{{ $estadisticas['puntual'] ?? 0 }}</h4>
                                <small class="text-muted">Puntual</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-danger">
                                <h4>{{ $estadisticas['tardanza'] ?? 0 }}</h4>
                                <small class="text-muted">Tardanza</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-warning">
                                <h4>{{ $estadisticas['falta'] ?? 0 }}</h4>
                                <small class="text-muted">Falta</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary">
                                <h4>{{ $estadisticas['total'] ?? 0 }}</h4>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </div>
                    @if($bimestre && $bimestre != 'todos')
                        <div class="text-center mt-2">
                            <span class="badge bg-info">Bimestre {{ $bimestre }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Calendario -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="calendario"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles -->
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Asistencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalleContenido">
                    <!-- Contenido dinámico -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventos = @json($eventosCalendario);

    const calendarEl = document.getElementById('calendario');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        events: eventos,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        eventClick: function(info) {
            const evento = info.event;
            const extendedProps = evento.extendedProps || {};

            const contenido = `
                <div class="mb-3">
                    <span class="badge" style="background-color: ${info.event.backgroundColor}">
                        ${info.event.title || 'Asistencia'}
                    </span>
                </div>
                <p><strong>Fecha:</strong> ${evento.startStr.split('T')[0]}</p>
                ${extendedProps.detalle ? `<p><strong>Grado:</strong> ${extendedProps.detalle.replace('Grado: ', '')}</p>` : ''}
                ${extendedProps.hora ? `<p><strong>Hora:</strong> ${extendedProps.hora}</p>` : ''}
                ${extendedProps.bimestre ? `<p><strong>Bimestre:</strong> ${extendedProps.bimestre}</p>` : ''}
                ${extendedProps.descripcion ? `<p><strong>Descripción:</strong> ${extendedProps.descripcion}</p>` : ''}
            `;

            document.getElementById('detalleContenido').innerHTML = contenido;
            new bootstrap.Modal(document.getElementById('detalleModal')).show();
        }
    });

    calendar.render();

    // Manejar cambios en los filtros
    const selectAnio = document.getElementById('selectAnio');
    const selectBimestre = document.getElementById('selectBimestre');

    function actualizarFiltros() {
        const anio = selectAnio.value;
        const bimestre = selectBimestre.value;

        // Construir la URL con los parámetros
        let url = "{{ route('asistencia.calendario', [':anio', ':bimestre']) }}"
            .replace(':anio', anio)
            .replace(':bimestre', bimestre);

        window.location.href = url;
    }

    selectAnio.addEventListener('change', actualizarFiltros);
    selectBimestre.addEventListener('change', actualizarFiltros);

    // Botón para limpiar filtros (volver a año actual + todos)
    const btnLimpiar = document.createElement('button');
    btnLimpiar.className = 'btn btn-outline-secondary btn-sm mt-3';
    btnLimpiar.innerHTML = '<i class="fas fa-times"></i> Limpiar filtros';
    btnLimpiar.onclick = function() {
        const anioActual = new Date().getFullYear();
        window.location.href = "{{ route('asistencia.calendario', [':anio', 'todos']) }}"
            .replace(':anio', anioActual);
    };

    // Insertar botón después del formulario
    const filtrosContainer = document.querySelector('.card-body .row.g-3');
    if (filtrosContainer) {
        filtrosContainer.parentNode.appendChild(btnLimpiar);
    }

    const urlCompleta = window.location.href;
    const urlPath = window.location.pathname;


    // Si la URL es exactamente /historial-asistencia
    if (urlPath === '/historial-asistencia' || urlPath === '/historial-asistencia/') {
        // Obtener año actual
        const anioActual = new Date().getFullYear();

        // Redirigir a /historial-asistencia/añoactual/todos
        const nuevaUrl = `/historial-asistencia/${anioActual}/todos`;
        console.log('Redirigiendo a:', nuevaUrl);

        window.location.href = nuevaUrl;
        return; // Salir para evitar ejecutar el resto del código
    }

});
</script>

<style>
/* Estilo para los eventos del calendario */
.fc-event {
    cursor: pointer;
    border-radius: 4px;
    font-size: 0.85rem;
    padding: 2px 4px;
}

/* Estilo para hoy */
.fc-day-today {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

/* Responsive */
@media (max-width: 768px) {
    .fc-header-toolbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .fc-toolbar-chunk {
        margin-bottom: 10px;
    }
}
</style>
@endsection
