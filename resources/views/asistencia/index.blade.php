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
    <div class="row mb-5">
        <!-- 1. Selector de Años -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-3">
                    <h5 class="card-title mb-3 text-secondary">
                        <i class="bi bi-calendar me-2"></i> Cambiar Año / Período
                    </h5>
                    <form method="GET" action="{{ route('asistencia.index') }}">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-filter"></i>
                            </span>
                            <select name="year" class="form-select border-start-0" onchange="this.form.submit()">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $currentYear == $year ? 'selected' : '' }}>
                                        Año {{ $year }}
                                        @if($year == now()->year) (Actual) @endif
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">
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

        <!-- 2. Sección Marcar Todos con Puntualidad -->
        <div class="col-12">
            <h5 class="mb-3 text-secondary">
                <i class="bi bi-lightning-charge me-2"></i> Asistencia Automática por Fecha
            </h5>
            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <div class="input-group input-group-lg rounded-3 shadow-sm">
                        <span class="input-group-text bg-primary text-white border-primary" id="bimestre-label">
                            <i class="bi bi-calendar-check me-2"></i> Bimestre:
                        </span>
                        <select class="form-select border-primary" name="bimestre" id="bimestre" required aria-labelledby="bimestre-label">
                            <option value="" disabled selected>Seleccione bimestre</option>
                            <option value="1">Bimestre 1</option>
                            <option value="2">Bimestre 2</option>
                            <option value="3">Bimestre 3</option>
                            <option value="4">Bimestre 4</option>
                        </select>
                    </div>
                </div>

                <div class="col-12 col-md-4 mb-3">
                    <div class="input-group input-group-lg rounded-3 shadow-sm">
                        <span class="input-group-text bg-info text-white border-info" id="fecha-label">
                            <i class="bi bi-calendar-date me-2"></i> Fecha:
                        </span>
                        <input type="date"
                            name="fecha"
                            id="fechaInput"
                            class="form-control border-info"
                            aria-labelledby="fecha-label"
                            value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="col-12 col-md-4 mb-3 d-grid">
                    <button type="button" class="btn btn-success btn-lg rounded-3 shadow" id="btnAsistenciaAutomatica">
                        <i class="bi bi-check-lg me-2"></i> Marcar con Puntualidad
                    </button>
                </div>

                <!-- Botón para Reportes -->
                <div class="col-12 mt-2">
                    <a class="btn btn-danger btn-lg rounded-3 shadow w-100" href="{{ route('asistencia.reporte') }}">
                        <i class="bi bi-box-arrow-in-right me-2"></i> IR A IMPRIMIR REPORTES
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        // Separamos los grados activos (estado == 1) e inactivos (estado == 0)
        $gradosActivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 1);
        $gradosInactivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 0);
    @endphp

    <!-- 1. TABLA DE GRADOS ACTIVOS (ESTADO 1) -->
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-header bg-success text-white p-3 border-bottom rounded-top-4">
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
                                <a href="{{ route('asistencia.grado', [
                                    'grado_grado_seccion' => $grado->grado . $grado->seccion,
                                    'grado_nivel' => strtolower($grado->nivel),
                                    'date' => now()->format('d-m-Y')
                                ]) }}" class="btn btn-sm btn-outline-primary rounded-3">
                                    <i class="bi bi-pencil-square me-1"></i> Tomar Asistencia
                                </a>
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
        <div class="card-header bg-secondary text-white p-3 border-bottom rounded-top-4">
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
                                    {{ $grado->asistencias_count ?? 0 }}
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
    const bimestreSelect = document.getElementById('bimestre');
    const fechaInput = document.getElementById('fechaInput');

    btnAsistenciaAutomatica.addEventListener('click', function() {
        if (!bimestreSelect.value) {
            alert('Por favor, seleccione un bimestre');
            return;
        }

        if (!confirm('¿Estás seguro de marcar a todos los estudiantes como puntuales?\n\nEsta acción afectará a todos los grados activos.')) {
            return;
        }

        const originalText = btnAsistenciaAutomatica.innerHTML;
        btnAsistenciaAutomatica.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Procesando...';
        btnAsistenciaAutomatica.disabled = true;

        fetch('{{ route("asistencia.marcar-todos-puntualidad") }}', {
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Se tomaron asistencia para ${data.total_afectados} estudiantes`);
                // Recargar la página para actualizar los contadores
                location.reload();
            } else {
                alert('❌ Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('❌ Error de conexión: ' + error.message);
        })
        .finally(() => {
            btnAsistenciaAutomatica.innerHTML = originalText;
            btnAsistenciaAutomatica.disabled = false;
        });
    });
});
</script>
@endsection
