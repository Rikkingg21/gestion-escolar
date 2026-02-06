@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Dashboard Director</h2>

    <!-- Selector de Periodo -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="periodoForm" method="GET" action="{{ route('dashboard.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="periodo_id" class="form-label">Seleccionar Periodo</label>
                        <select name="periodo_id" id="periodo_id" class="form-select">
                            <option value="">-- Seleccione un periodo --</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}"
                                    {{ $periodoSeleccionado && $periodoSeleccionado->id == $periodo->id ? 'selected' : '' }}>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                    @if($periodo->estado == '1')
                                        <span class="badge bg-success">Activo</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        @if($periodoSeleccionado)
                            <a href="{{ route('dashboard.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($periodoSeleccionado)
        <div class="alert alert-info" id="periodoAlert">
            <strong>Periodo seleccionado:</strong> {{ $periodoSeleccionado->nombre }} ({{ $periodoSeleccionado->anio }})
            @if($periodoSeleccionado->descripcion)
                - {{ $periodoSeleccionado->descripcion }}
            @endif
        </div>
    @else
        <div class="alert alert-warning" id="periodoAlert">
            <strong>No hay periodo seleccionado.</strong> Por favor, seleccione un periodo para ver los datos.
        </div>
    @endif

    <!-- Cards de Estadísticas -->
    <div id="cardsContainer">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Grados</h5>
                        <p class="card-text display-6">{{ $estadisticas['total_grados'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Estudiantes Matriculados</h5>
                        <p class="card-text display-6">{{ $estadisticas['total_estudiantes'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Promedio General</h5>
                        <p class="card-text display-6">{{ $estadisticas['promedio_general'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Estado</h5>
                        <p class="card-text">
                            @if($periodoSeleccionado)
                                @if($estadisticas['total_estudiantes'] > 0)
                                    <span class="badge bg-success">Con Datos</span>
                                @else
                                    <span class="badge bg-warning">Sin Matrículas</span>
                                @endif
                            @else
                                <span class="badge bg-secondary">Sin Periodo</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Grados -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Rendimiento por Grado - Periodo:
                @if($periodoSeleccionado)
                    {{ $periodoSeleccionado->nombre }} ({{ $periodoSeleccionado->anio }})
                @else
                    Sin periodo seleccionado
                @endif
            </h4>
        </div>
        <div class="card-body">
            <div id="tablaContainer">
                @if($periodoSeleccionado && $grados->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Grado</th>
                                    <th>Estudiantes Matriculados</th>
                                    <th>Prom. Académico</th>
                                    <th>Prom. Conducta</th>
                                    <th>Prom. General</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grados as $grado)
                                <tr>
                                    <td>
                                        <strong>{{ $grado->nombreCompleto ?? $grado->grado }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $grado->nivel }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info rounded-pill">
                                            {{ $grado->estudiantes_matriculados }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 20px;">
                                                <div class="progress-bar {{ $grado->promedio_notas >= 12 ? 'bg-success' : 'bg-danger' }}"
                                                     role="progressbar"
                                                     style="width: {{ min($grado->promedio_notas * 5, 100) }}%"
                                                     aria-valuenow="{{ $grado->promedio_notas }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="20">
                                                </div>
                                            </div>
                                            <span class="ms-2 fw-bold">{{ $grado->promedio_notas }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 20px;">
                                                <div class="progress-bar {{ $grado->promedio_conducta >= 12 ? 'bg-warning' : 'bg-danger' }}"
                                                     role="progressbar"
                                                     style="width: {{ min($grado->promedio_conducta * 5, 100) }}%"
                                                     aria-valuenow="{{ $grado->promedio_conducta }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="20">
                                                </div>
                                            </div>
                                            <span class="ms-2 fw-bold">{{ $grado->promedio_conducta }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $grado->promedio_general >= 12 ? 'primary' : 'danger' }} rounded-pill p-2">
                                            <strong>{{ $grado->promedio_general }}</strong>
                                        </span>
                                    </td>
                                    <td>
                                        @if($grado->promedio_general >= 12)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Aprobado
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> Bajo
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($periodoSeleccionado)
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h5>No hay datos para este periodo</h5>
                        <p class="text-muted">No se encontraron grados con matrículas en el periodo seleccionado.</p>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                        <h5>Seleccione un periodo</h5>
                        <p class="text-muted">Por favor, seleccione un periodo para ver los datos.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Script para AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodoSelect = document.getElementById('periodo_id');

    if (periodoSelect) {
        periodoSelect.addEventListener('change', function() {
            cambiarPeriodo();
        });
    }
});

function cambiarPeriodo() {
    const periodoId = document.getElementById('periodo_id').value;

    if (!periodoId) {
        // Si no hay periodo seleccionado, limpiar los datos
        const periodoAlert = document.getElementById('periodoAlert');
        const tablaContainer = document.getElementById('tablaContainer');
        const cardsContainer = document.getElementById('cardsContainer');

        if (periodoAlert) {
            periodoAlert.className = 'alert alert-warning';
            periodoAlert.innerHTML = '<strong>No hay periodo seleccionado.</strong> Por favor, seleccione un periodo para ver los datos.';
        }

        if (tablaContainer) {
            tablaContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <h5>Seleccione un periodo</h5>
                    <p class="text-muted">Por favor, seleccione un periodo para ver los datos.</p>
                </div>
            `;
        }

        if (cardsContainer) {
            cardsContainer.innerHTML = `
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Grados</h5>
                                <p class="card-text display-6">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Estudiantes Matriculados</h5>
                                <p class="card-text display-6">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Promedio General</h5>
                                <p class="card-text display-6">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Estado</h5>
                                <p class="card-text">
                                    <span class="badge bg-secondary">Sin Periodo</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return;
    }

    // Mostrar loading
    const tablaContainer = document.getElementById('tablaContainer');
    const cardsContainer = document.getElementById('cardsContainer');
    const periodoAlert = document.getElementById('periodoAlert');

    if (tablaContainer) {
        tablaContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando datos del periodo...</p>
            </div>
        `;
    }

    if (cardsContainer) {
        cardsContainer.innerHTML = `
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Grados</h5>
                            <p class="card-text display-6">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Estudiantes Matriculados</h5>
                            <p class="card-text display-6">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Promedio General</h5>
                            <p class="card-text display-6">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Estado</h5>
                            <p class="card-text">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    if (periodoAlert) {
        periodoAlert.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                Actualizando datos del periodo...
            </div>
        `;
    }

    // Hacer petición AJAX
    fetch(`{{ route('dashboard.index') }}?periodo_id=${periodoId}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la tabla
                if (tablaContainer && data.html) {
                    tablaContainer.innerHTML = data.html;
                }

                // Actualizar las cards
                if (cardsContainer && data.cards_html) {
                    cardsContainer.innerHTML = data.cards_html;
                }

                // Actualizar el alert del periodo
                if (periodoAlert && data.periodo) {
                    periodoAlert.className = 'alert alert-info';
                    periodoAlert.innerHTML = `
                        <strong>Periodo seleccionado:</strong> ${data.periodo.nombre} (${data.periodo.anio})
                        ${data.periodo.descripcion ? ' - ' + data.periodo.descripcion : ''}
                    `;
                } else if (periodoAlert && !data.periodo) {
                    periodoAlert.className = 'alert alert-warning';
                    periodoAlert.innerHTML = '<strong>Periodo no encontrado.</strong>';
                }

                // Actualizar el título del card header
                const cardHeaderTitle = document.querySelector('.card-header h4');
                if (cardHeaderTitle && data.periodo) {
                    cardHeaderTitle.innerHTML = `Rendimiento por Grado - Periodo: ${data.periodo.nombre} (${data.periodo.anio})`;
                } else if (cardHeaderTitle) {
                    cardHeaderTitle.innerHTML = 'Rendimiento por Grado - Periodo: Sin periodo seleccionado';
                }
            } else {
                // Manejar error
                if (tablaContainer) {
                    tablaContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error al cargar los datos.
                        </div>
                    `;
                }
                if (periodoAlert) {
                    periodoAlert.className = 'alert alert-danger';
                    periodoAlert.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error al cargar los datos.';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (tablaContainer) {
                tablaContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error al cargar los datos. Por favor, intente nuevamente.
                    </div>
                `;
            }
            if (periodoAlert) {
                periodoAlert.className = 'alert alert-danger';
                periodoAlert.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error al cargar los datos.';
            }
        });
}

// También puedes permitir que el formulario se envíe normalmente si el usuario prefiere
// y solo usar AJAX como mejora de experiencia de usuario
document.getElementById('periodoForm')?.addEventListener('submit', function(e) {
    // Si quieres que funcione con AJAX, puedes prevenir el envío normal
    // e.preventDefault();
    // cambiarPeriodo();
});
</script>
@endsection
