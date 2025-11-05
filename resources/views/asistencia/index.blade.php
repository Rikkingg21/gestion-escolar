@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Registro de Asistencias</h3>
    </div>

    <!-- Selector de años -->
    <form method="GET" action="{{ route('asistencia.index') }}" class="mb-4">
        <div class="input-group" style="max-width: 300px;">
            <select name="year" class="form-select" onchange="this.form.submit()">
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
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </div>
    </form>
    <!--Seccion Marcar Todos con Puntualidad-->
    <div class="row mb-5 p-3 p-md-0">
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
            <div class="input-group input-group-lg rounded-3 shadow-sm">
                <span class="input-group-text bg-info text-white border-info">
                    <i class="bi bi-calendar-date me-2"></i> Fecha:
                </span>
                <input type="date"
                    name="fecha"
                    id="fechaInput"
                    class="form-control border-info">
            </div>
        </div>

        <div class="col-12 col-md-4 mb-3 d-grid">
            <!-- Botón de acción principal, usa d-grid para ocupar todo el ancho en móvil -->
            <button type="button" class="btn btn-success btn-lg rounded-3 shadow" id="btnAsistenciaAutomatica">
                <i class="bi bi-lightning-charge me-2"></i> Marcar Todos con Puntualidad
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Grado/Sección</th>
                            <th>Nivel</th>
                            <th>Asistencias Registradas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grados as $grado)
                        <tr>
                            <td>{{ $grado->grado }}° "{{ $grado->seccion }}"</td>
                            <td>{{ $grado->nivel }}</td>
                            <td>
                                @if(isset($grado->asistencias_count))
                                    <span class="badge bg-primary">{{ $grado->asistencias_count }}</span>
                                @else
                                    <span class="badge bg-warning">0</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('asistencia.grado', [
                                    'grado_grado_seccion' => $grado->grado . $grado->seccion, // Ej: "1a"
                                    'grado_nivel' => strtolower($grado->nivel), // Ej: "secundaria"
                                    'date' => now()->format('d-m-Y') // Fecha actual
                                ]) }}" class="btn btn-primary">
                                    Tomar Asistencia
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No hay grados disponibles</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
