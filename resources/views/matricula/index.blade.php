@extends('layouts.app')
@section('title', 'Matrículas')
@section('content')
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <label for="periodo_id" class="form-label">Seleccionar Período:</label>
                <select name="periodo_id" id="periodo_id" class="form-select">
                    <option value="">-- Seleccione un período --</option>
                    @foreach($nombresPeriodos as $id => $nombrePeriodo)
                        <option value="{{ $id }}"
                                {{ $nombrePeriodo == $nombre ? 'selected' : '' }}>
                            {{ $nombrePeriodo }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted mt-1 d-block">
                    Período actual: <strong>{{ $nombre }}</strong>
                </small>
            </div>
        </div>

        <!-- Mostrar información del período -->
        <div class="alert alert-info mb-4">
            <h5>Período: {{ $nombre }}</h5>
            <p class="mb-0">
                @if($hayMatriculas)
                    Mostrando todos los grados activos, separados por matrículas registradas
                @else
                    Mostrando todos los grados activos (no hay matrículas registradas)
                @endif
            </p>
        </div>

        <!-- Tabla de Grados con Matrículas Registradas -->
        @if($gradosConMatriculas->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-check me-2"></i>
                        Grados con Matrículas Registradas
                        <span class="badge bg-light text-dark ms-2">
                            {{ $gradosConMatriculas->count() }} grado(s)
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Grado</th>
                                    <th>Nivel</th>
                                    <th>Sección</th>
                                    <th>Matrículas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gradosConMatriculas as $index => $grado)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $grado->grado }}°</strong>
                                        </td>
                                        <td>{{ $grado->nivel }}</td>
                                        <td>{{ $grado->seccion }}</td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ $grado->matriculas_count }} matrícula(s)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Activo</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('matricula.grado', ['nombre' => $nombre, 'grado_id' => $grado->id]) }}"
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i> Ver Matrículas
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tabla de Grados sin Matrículas -->
        @if($gradosSinMatriculas->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Grados Disponibles para Matrícula
                        <span class="badge bg-light text-dark ms-2">
                            {{ $gradosSinMatriculas->count() }} grado(s)
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Grado</th>
                                    <th>Nivel</th>
                                    <th>Sección</th>
                                    <th>Matrículas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gradosSinMatriculas as $index => $grado)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $grado->grado }}°</strong>
                                        </td>
                                        <td>{{ $grado->nivel }}</td>
                                        <td>{{ $grado->seccion }}</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                Sin matrículas
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Activo</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('matricula.grado', ['nombre' => $nombre, 'grado_id' => $grado->id]) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-plus me-1"></i> Iniciar Matrículas
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Mensaje si no hay grados -->
        @if($gradosConMatriculas->count() == 0 && $gradosSinMatriculas->count() == 0)
            <div class="alert alert-warning">
                No hay grados activos disponibles para este período.
            </div>
        @endif
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectPeriodo = document.getElementById('periodo_id');

            selectPeriodo.addEventListener('change', function() {
                if (this.value) {
                    const periodoNombre = this.options[this.selectedIndex].text;
                    window.location.href = `/matricula/${encodeURIComponent(periodoNombre)}`;
                }
            });
        });
    </script>
@endsection
