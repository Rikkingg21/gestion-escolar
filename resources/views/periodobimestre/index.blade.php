@extends('layouts.app')
@section('title', 'Bimestres - ' . $periodo->nombre)
@section('content')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-calendar-week me-2"></i>
                    Bimestres del Periodo: {{ $periodo->nombre }}
                </h4>
                <small class="text-white-50">
                    {{ ucfirst($periodo->tipo_periodo) }} |
                    Año: {{ $periodo->anio }} |
                    {{ \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }} -
                    {{ \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }}
                </small>
            </div>
            <div>
                <a href="{{ route('periodo.index') }}" class="btn btn-light btn-sm me-2">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </a>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createBimestreModal">
                    <i class="bi bi-plus-lg"></i> Nuevo Bimestre
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Rango permitido:</strong>
                {{ \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }} -
                {{ \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }}
            </div>

            @if($bimestres->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th width="50">ID</th>
                                <th>Bimestre</th>
                                <th>Tipo</th>
                                <th width="200">Fecha Inicio</th>
                                <th width="200">Fecha Fin</th>
                                <th>Duración</th>
                                <th width="120" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bimestres as $bimestre)
                                <tr>
                                    <td class="fw-bold">#{{ $bimestre->id }}</td>
                                    <td>
                                        <strong>{{ $bimestre->bimestre }}</strong>
                                    </td>
                                    <td>
                                        @if($bimestre->tipo_bimestre == 'A')
                                            <span class="badge bg-primary">
                                                <i class="fas fa-graduation-cap me-1"></i>Académico
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>Recuperación
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-alt text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($bimestre->fecha_inicio)->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-check text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($bimestre->fecha_fin)->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        @php
                                            $dias = \Carbon\Carbon::parse($bimestre->fecha_inicio)
                                                ->diffInDays(\Carbon\Carbon::parse($bimestre->fecha_fin)) + 1;
                                        @endphp
                                        <span class="badge bg-info">{{ $dias }} días</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button"
                                                    class="btn btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal{{ $bimestre->id }}"
                                                    title="Editar">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal{{ $bimestre->id }}"
                                                    title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- MODAL EDITAR -->
                                <div class="modal fade" id="editModal{{ $bimestre->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('periodobimestre.update', [$periodo->nombre, $bimestre->id]) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-edit me-2"></i>Editar Bimestre
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nombre del Bimestre</label>
                                                        <input type="text" class="form-control" name="bimestre"
                                                               value="{{ $bimestre->bimestre }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Tipo de Bimestre</label>
                                                        <select class="form-select" name="tipo_bimestre" required>
                                                            <option value="A" {{ $bimestre->tipo_bimestre == 'A' ? 'selected' : '' }}>
                                                                Académico (A)
                                                            </option>
                                                            <option value="R" {{ $bimestre->tipo_bimestre == 'R' ? 'selected' : '' }}>
                                                                Recuperación (R)
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Fecha Inicio</label>
                                                            <input type="date" class="form-control" name="fecha_inicio"
                                                                   value="{{ \Carbon\Carbon::parse($bimestre->fecha_inicio)->format('Y-m-d') }}"
                                                                   min="{{ $periodo->fecha_inicio }}"
                                                                   max="{{ $periodo->fecha_fin }}" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Fecha Fin</label>
                                                            <input type="date" class="form-control" name="fecha_fin"
                                                                   value="{{ \Carbon\Carbon::parse($bimestre->fecha_fin)->format('Y-m-d') }}"
                                                                   min="{{ $periodo->fecha_inicio }}"
                                                                   max="{{ $periodo->fecha_fin }}" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-warning">Actualizar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- MODAL ELIMINAR -->
                                <div class="modal fade" id="deleteModal{{ $bimestre->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('periodobimestre.destroy', [$periodo->nombre, $bimestre->id]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>¿Estás seguro de eliminar el bimestre <strong>{{ $bimestre->bimestre }}</strong>?</p>
                                                    <p class="text-danger mb-0"><small>Esta acción no se puede deshacer.</small></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Mostrando {{ $bimestres->firstItem() }} a {{ $bimestres->lastItem() }}
                        de {{ $bimestres->total() }} bimestres
                    </div>
                    {{ $bimestres->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5>No hay bimestres configurados</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBimestreModal">
                        <i class="fas fa-plus me-1"></i>Crear Primer Bimestre
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- MODAL CREAR -->
<div class="modal fade" id="createBimestreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('periodobimestre.store', $periodo->nombre) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Bimestre
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Bimestre *</label>
                        <input type="text" class="form-control" name="bimestre"
                               placeholder="Ej: Bimestre I, Primer Bimestre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Bimestre *</label>
                        <select class="form-select" name="tipo_bimestre" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="A">Académico (A)</option>
                            <option value="R">Recuperación (R)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Inicio *</label>
                            <input type="date" class="form-control" name="fecha_inicio"
                                   min="{{ $periodo->fecha_inicio }}"
                                   max="{{ $periodo->fecha_fin }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Fin *</label>
                            <input type="date" class="form-control" name="fecha_fin"
                                   min="{{ $periodo->fecha_inicio }}"
                                   max="{{ $periodo->fecha_fin }}" required>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>
                            Rango permitido: {{ \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }} -
                            {{ \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Validación de fechas en creación
    document.querySelector('#createBimestreModal form')?.addEventListener('submit', function(e) {
        const inicio = this.querySelector('input[name="fecha_inicio"]').value;
        const fin = this.querySelector('input[name="fecha_fin"]').value;
        if (inicio && fin && inicio > fin) {
            e.preventDefault();
            alert('La fecha de inicio no puede ser mayor a la fecha de fin');
        }
    });

    // Validación de fechas en edición
    document.querySelectorAll('[id^="editModal"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const inicio = this.querySelector('input[name="fecha_inicio"]').value;
            const fin = this.querySelector('input[name="fecha_fin"]').value;
            if (inicio && fin && inicio > fin) {
                e.preventDefault();
                alert('La fecha de inicio no puede ser mayor a la fecha de fin');
            }
        });
    });
</script>
@endsection
