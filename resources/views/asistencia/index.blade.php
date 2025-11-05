@extends('layouts.app')
@section('title', 'Asistencia')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-3">
        <h2 class="h3 mb-0 text-primary fw-bold">
            <i class="bi bi-person-check me-2"></i> Registro de Asistencias
        </h2>
    </div>

    <!-- Filtro de Año y Sección de Asistencia Rápida -->
    <div class="row mb-5">

        <div class="row mb-5">

        <!-- 1. Selector de Años (Full Width: col-12) -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-3">
                    <h5 class="card-title mb-3 text-secondary">
                        <i class="bi bi-calendar me-2"></i> Filtro Anual
                    </h5>
                    <!-- El input-group ahora ocupa todo el ancho disponible dentro del card -->
                    <form method="GET" action="{{ route('asistencia.index') }}">
                        <div class="input-group">
                            <select name="year" class="form-select border-secondary" onchange="this.form.submit()">
                                <option value="{{ now()->year }}" {{ $currentYear == now()->year ? 'selected' : '' }}>
                                    Año Actual ({{ now()->year }})
                                </option>
                                @foreach($availableYears as $year)
                                    @if($year != now()->year)
                                    <option value="{{ $year }}" {{ $currentYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel-fill"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Sección Marcar Todos con Puntualidad (Full Width: col-12) -->
        <div class="col-12">
            <h5 class="mb-3 text-secondary">
                <i class="bi bi-lightning-charge me-2"></i> Asistencia Automática por Fecha
            </h5>
            <!-- Los tres inputs internos se reparten en una fila de 12 columnas -->
            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <!-- Input para seleccionar Bimestre -->
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
                    <!-- Input para seleccionar Fecha -->
                    <div class="input-group input-group-lg rounded-3 shadow-sm">
                        <span class="input-group-text bg-info text-white border-info" id="fecha-label">
                            <i class="bi bi-calendar-date me-2"></i> Fecha:
                        </span>
                        <input type="date"
                            name="fecha"
                            id="fechaInput"
                            class="form-control border-info"
                            aria-labelledby="fecha-label">
                    </div>
                </div>

                <div class="col-12 col-md-4 mb-3 d-grid">
                    <!-- Botón de acción principal con icono de visto bueno -->
                    <button type="button" class="btn btn-success btn-lg rounded-3 shadow" id="btnAsistenciaAutomatica">
                        <i class="bi bi-check-lg me-2"></i> Marcar con Puntualidad
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin de Filtro de Año y Sección de Asistencia Rápida -->

    @php
        // Separamos los grados activos (estado == 1) e inactivos (estado == 0)
        $gradosActivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 1);
        $gradosInactivos = collect($grados)->filter(fn($g) => ($g->estado ?? 0) == 0);
    @endphp


    <!-- 1. TABLA DE GRADOS ACTIVOS (ESTADO 1) -->
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-header bg-success text-white p-3 border-bottom rounded-top-4">
            <h4 class="mb-0">
                <i class="bi bi-check-circle-fill me-2"></i> Grados Activos ({{ $gradosActivos->count() }})
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
                            <th scope="col" class="py-3 px-4 text-center">Asistencias Reg.</th>
                            <th scope="col" class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gradosActivos as $grado)
                        <tr class="align-middle">
                            <td class="px-4 fw-bold text-primary">{{ $grado->grado }}° "{{ $grado->seccion }}"</td>
                            <td class="px-4 text-muted">{{ $grado->nivel }}</td>
                            <td class="px-4 text-center">
                                <span class="badge text-bg-success fs-6 py-1 px-2 rounded-pill">Activo</span>
                            </td>
                            <td class="px-4 text-center">
                                @if(isset($grado->asistencias_count) && $grado->asistencias_count > 0)
                                    <span class="badge text-bg-primary fs-6 py-2 px-3 rounded-pill">
                                        {{ $grado->asistencias_count }}
                                    </span>
                                @else
                                    <span class="badge text-bg-warning fs-6 py-2 px-3 rounded-pill">
                                        0
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <a href="{{ route('asistencia.grado', [
                                    'grado_grado_seccion' => $grado->grado . $grado->seccion, // Ej: "1a"
                                    'grado_nivel' => strtolower($grado->nivel), // Ej: "secundaria"
                                    'date' => now()->format('d-m-Y') // Fecha actual
                                ]) }}" class="btn btn-sm btn-outline-primary rounded-3">
                                    <i class="bi bi-pencil-square me-1"></i> Tomar
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-info-circle me-2"></i> No hay grados activos disponibles para registrar asistencia.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- 2. TABLA DE GRADOS INACTIVOS (ESTADO 0) -->
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
                            <th scope="col" class="py-3 px-4 text-center">Asistencias Reg.</th>
                            <th scope="col" class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gradosInactivos as $grado)
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
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-info-circle me-2"></i> No hay grados inactivos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnAsistenciaAutomatica = document.getElementById('btnAsistenciaAutomatica');
    const bimestreSelect = document.getElementById('bimestre');
    const fechaInput = document.getElementById('fechaInput');

    // Establecer fecha actual automáticamente
    fechaInput.value = new Date().toISOString().split('T')[0];

    btnAsistenciaAutomatica.addEventListener('click', function() {
        if (!bimestreSelect.value) {
            alert('Por favor, seleccione un bimestre');
            return;
        }

        const originalText = btnAsistenciaAutomatica.innerHTML;
        btnAsistenciaAutomatica.innerHTML = 'Guardando...';
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
                alert(`Se tomaron asistencia para ${data.total_afectados} estudiantes`);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión');
        })
        .finally(() => {
            btnAsistenciaAutomatica.innerHTML = originalText;
            btnAsistenciaAutomatica.disabled = false;
        });
    });
});
</script>
@endsection
