@extends('layouts.app')
@section('title', 'Calendario de Asistencia')
@section('content')
<div class="container py-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h2 class="mb-2">
                    <i class="fas fa-calendar-check text-primary"></i>
                    Mi Calendario de Asistencias
                </h2>
                <div class="text-muted">
                    <strong>{{ $estudiante->user->nombre ?? '' }} {{ $estudiante->user->apellido_paterno ?? '' }}</strong>
                    @if($gradoActual)
                        • {{ $gradoActual->grado }}° {{ $gradoActual->seccion }} - {{ $gradoActual->nivel }}
                    @endif
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
                        <div class="col-md-5">
                            <label class="form-label">Período</label>
                            <select name="periodo_id" class="form-select" id="selectPeriodo">
                                <option value="">Seleccionar período</option>
                                @foreach($periodosConAsistencias as $periodo)
                                    <option value="{{ $periodo->id }}"
                                            {{ $periodo_id == $periodo->id ? 'selected' : '' }}>
                                        {{ $periodo->nombre }} ({{ $periodo->anio }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Bimestre</label>
                            <select name="periodobimestre_id" class="form-select" id="selectBimestre" disabled>
                                <option value="todos">Primero seleccione un período</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100" id="btnLimpiar">
                                <i class="fas fa-times me-1"></i> Limpiar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        Resumen
                        @if($periodoSeleccionado)
                            {{ $periodoSeleccionado->nombre }}
                        @endif
                    </h5>
                    <div class="row">
                        @foreach($tiposAsistencia as $tipo)
                            @php
                                $stat = $estadisticas[$tipo->id] ?? ['count' => 0, 'color' => '#6c757d'];
                            @endphp
                            <div class="col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <div style="width: 12px; height: 12px; background-color: {{ $tipo->color_hex ?? '#6c757d' }}; border-radius: 3px; margin-right: 8px;"></div>
                                    <div>
                                        <span class="fw-bold">{{ $stat['count'] }}</span>
                                        <small class="text-muted">{{ $tipo->nombre }}</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <hr class="my-2">
                    <div class="text-center">
                        <span class="badge bg-secondary fs-6">
                            <i class="fas fa-chart-line me-1"></i> Total: {{ $estadisticas['total'] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendario -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2 p-md-3">
                    <div id="calendario"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles -->
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Asistencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalleContenido"></div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.global.min.js'></script>

<style>
    .fc-event {
        cursor: pointer;
        border-radius: 4px;
        font-size: 0.85rem;
        padding: 2px 4px;
        transition: transform 0.2s;
    }

    .fc-event:hover {
        transform: scale(1.02);
    }

    .fc-day-today {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }

    .fc-day-disabled {
        background-color: #f8f9fa !important;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .fc-header-toolbar {
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .fc-toolbar-title {
            font-size: 1.1rem !important;
        }

        .fc-button {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
        }

        .fc-event {
            font-size: 0.7rem !important;
            padding: 2px 2px !important;
            white-space: normal !important;
            word-break: break-word !important;
        }

        .fc-daygrid-day-frame {
            min-height: 55px !important;
        }

        .fc-daygrid-day-number {
            font-size: 0.75rem !important;
            padding: 2px !important;
        }

        .fc-daygrid-day-events {
            min-height: 30px !important;
        }

        .modal-dialog {
            margin: 0.5rem !important;
        }

        .modal-body {
            padding: 1rem !important;
        }

        .table-sm {
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .fc-toolbar-title {
            font-size: 0.9rem !important;
        }

        .fc-button {
            padding: 0.2rem 0.4rem !important;
            font-size: 0.7rem !important;
        }

        .fc-event {
            font-size: 0.65rem !important;
            padding: 1px 1px !important;
        }

        .fc-event .fc-event-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 70px;
            display: inline-block;
        }

        .fc-daygrid-day-number {
            font-size: 0.7rem !important;
        }

        .card-body {
            padding: 1rem !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos precargados desde el backend (pasados directamente)
    const todosLosBimestres = @json($todosLosBimestres);

    function getResponsiveConfig() {
        const isMobile = window.innerWidth < 768;
        const isTablet = window.innerWidth >= 768 && window.innerWidth < 992;

        return {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: isMobile ? 'dayGridMonth' : (isTablet ? 'dayGridMonth,timeGridWeek' : 'dayGridMonth,timeGridWeek,timeGridDay')
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Sem',
                day: 'Día'
            },
            contentHeight: 'auto',
            aspectRatio: isMobile ? 1.2 : 1.35,
            eventMinHeight: isMobile ? 35 : 20,
            eventDisplay: 'block',
            dayMaxEvents: isMobile ? 2 : 3,
            views: {
                dayGridMonth: {
                    titleFormat: { year: 'numeric', month: 'short' },
                    dayHeaderFormat: { weekday: 'short' }
                }
            }
        };
    }

    const periodoActual = @json($periodoSeleccionado);
    const bimestreActual = @json($bimestresDisponibles->firstWhere('id', $periodobimestre_id));

    let fechaMin = null;
    let fechaMax = null;

    if (bimestreActual && bimestreActual.fecha_inicio && bimestreActual.fecha_fin) {
        fechaMin = bimestreActual.fecha_inicio;
        fechaMax = bimestreActual.fecha_fin;
    } else if (periodoActual && periodoActual.fecha_inicio && periodoActual.fecha_fin) {
        fechaMin = periodoActual.fecha_inicio;
        fechaMax = periodoActual.fecha_fin;
    }

    const eventos = @json($eventosCalendario);
    const calendarEl = document.getElementById('calendario');

    const responsiveConfig = getResponsiveConfig();

    const calendar = new FullCalendar.Calendar(calendarEl, {
        ...responsiveConfig,
        locale: 'es',
        events: eventos,
        validRange: (fechaMin && fechaMax) ? {
            start: fechaMin,
            end: fechaMax
        } : null,
        handleWindowResize: true,
        windowResize: function() {
            const newConfig = getResponsiveConfig();
            calendar.setOption('headerToolbar', newConfig.headerToolbar);
            calendar.setOption('aspectRatio', newConfig.aspectRatio);
            calendar.setOption('eventMinHeight', newConfig.eventMinHeight);
            calendar.setOption('dayMaxEvents', newConfig.dayMaxEvents);
        },
        eventClick: function(info) {
            const evento = info.event;
            const extendedProps = evento.extendedProps || {};

            const contenido = `
                <div class="mb-3">
                    <span class="badge" style="background-color: ${info.event.backgroundColor}; padding: 8px 12px; font-size: 14px;">
                        ${info.event.title || 'Asistencia'}
                    </span>
                </div>
                <table class="table table-sm">
                    <tr>
                        <th width="100">Fecha:</th>
                        <td>${evento.startStr.split('T')[0]}</td>
                    </td>
                    <tr><th>Período:</th><td>${extendedProps.periodo || 'N/A'}</td></tr>
                    <tr><th>Grado:</th><td>${extendedProps.grado || 'N/A'}</td></tr>
                    <tr><th>Hora:</th><td>${extendedProps.hora || 'N/A'}</td></tr>
                    <tr><th>Bimestre:</th><td>${extendedProps.bimestre || 'N/A'}</td></tr>
                    <tr><th>Descripción:</th><td>${extendedProps.descripcion || 'Sin descripción'}</td></tr>
                </table>
            `;

            document.getElementById('detalleContenido').innerHTML = contenido;
            new bootstrap.Modal(document.getElementById('detalleModal')).show();
        }
    });

    calendar.render();

    const selectPeriodo = document.getElementById('selectPeriodo');
    const selectBimestre = document.getElementById('selectBimestre');
    const btnLimpiar = document.getElementById('btnLimpiar');

    function cargarBimestres(periodoId, bimestreSeleccionado = null) {
        if (!periodoId) {
            selectBimestre.innerHTML = '<option value="todos">Primero seleccione un período</option>';
            selectBimestre.disabled = true;
            return;
        }

        const bimestres = todosLosBimestres[periodoId] || [];

        selectBimestre.innerHTML = '<option value="todos">Todos los bimestres</option>';
        if (bimestres.length > 0) {
            bimestres.forEach(bimestre => {
                const selected = bimestreSeleccionado && bimestreSeleccionado == bimestre.id ? 'selected' : '';
                selectBimestre.innerHTML += `<option value="${bimestre.id}" ${selected} data-fecha-inicio="${bimestre.fecha_inicio}" data-fecha-fin="${bimestre.fecha_fin}">${bimestre.bimestre}</option>`;
            });
            selectBimestre.disabled = false;
        } else {
            selectBimestre.innerHTML = '<option value="todos">No hay bimestres disponibles</option>';
            selectBimestre.disabled = true;
        }
    }

    function aplicarFiltros() {
        const periodoId = selectPeriodo.value;
        const bimestreId = selectBimestre.value;

        if (!periodoId) {
            alert('Por favor, seleccione un período');
            return;
        }

        let url = `{{ route("asistencia.calendario", ["periodo_id" => ":periodo", "periodobimestre_id" => ":bimestre"]) }}`
            .replace(':periodo', periodoId)
            .replace(':bimestre', bimestreId);

        window.location.href = url;
    }

    selectPeriodo.addEventListener('change', function() {
        const periodoId = this.value;
        if (periodoId) {
            cargarBimestres(periodoId, null);
        } else {
            selectBimestre.innerHTML = '<option value="todos">Primero seleccione un período</option>';
            selectBimestre.disabled = true;
        }
    });

    selectBimestre.addEventListener('change', function() {
        const periodoId = selectPeriodo.value;
        if (periodoId && this.value && !this.disabled) {
            aplicarFiltros();
        }
    });

    btnLimpiar.addEventListener('click', function() {
        const primerPeriodo = selectPeriodo.querySelector('option:not([value=""])');
        if (primerPeriodo) {
            window.location.href = "{{ route('asistencia.calendario', ['periodo_id' => ':periodo', 'periodobimestre_id' => 'todos']) }}"
                .replace(':periodo', primerPeriodo.value);
        } else {
            window.location.href = "{{ route('asistencia.calendario', ['periodo_id' => '', 'periodobimestre_id' => 'todos']) }}";
        }
    });

    const btnAplicar = document.createElement('button');
    btnAplicar.type = 'button';
    btnAplicar.className = 'btn btn-primary w-100 mt-2 mt-md-0';
    btnAplicar.innerHTML = '<i class="fas fa-search me-1"></i> Aplicar Filtros';
    btnAplicar.onclick = aplicarFiltros;

    const filtrosContainer = document.querySelector('#filtroForm .row.g-3');
    if (filtrosContainer && !document.querySelector('#btnAplicarExistente')) {
        btnAplicar.id = 'btnAplicarExistente';
        const colBtn = document.createElement('div');
        colBtn.className = 'col-md-12 d-flex justify-content-end';
        colBtn.appendChild(btnAplicar);
        filtrosContainer.parentNode.appendChild(colBtn);
    }

    @if($periodo_id)
        cargarBimestres('{{ $periodo_id }}', '{{ $periodobimestre_id }}');
    @endif
});
</script>
@endsection
