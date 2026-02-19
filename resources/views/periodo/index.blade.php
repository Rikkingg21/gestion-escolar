@extends('layouts.app')
@section('title', 'Periodos')
@section('content')
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Gestión de Periodos Académicos
                </h4>
                <a href="{{ route('periodo.create') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i> Nuevo Periodo
                </a>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Pestañas -->
                <ul class="nav nav-tabs nav-underline mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" href="#activo" aria-selected="true" role="tab">
                            <i class="fas fa-check-circle me-1"></i> Activos
                            <span class="badge bg-primary ms-1">{{ $periodosActivos->total() }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#inactivo" aria-selected="false" role="tab">
                            <i class="fas fa-times-circle me-1"></i> Inactivos
                            <span class="badge bg-secondary ms-1">{{ $periodosInactivos->total() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- TAB ACTIVOS -->
                    <div class="tab-pane fade show active" id="activo" role="tabpanel">
                        @if($periodosActivos->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="80">ID</th>
                                            <th>Nombre</th>
                                            <th width="100">Año</th>
                                            <th>Descripción</th>
                                            <th width="200" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($periodosActivos as $periodo)
                                            <tr>
                                                <td class="fw-bold">#{{ $periodo->id }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <strong>{{ $periodo->nombre }}</strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $periodo->anio }}</span>
                                                </td>
                                                <td class="text-truncate" style="max-width: 250px;">
                                                    {{ $periodo->descripcion ?: 'Sin descripción' }}
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ url('matricula/' . $periodo->anio) }}"
                                                        class="btn btn-outline-{{ $periodo->estado == 1 ? 'primary' : 'secondary' }}"
                                                        title="Ver Matrículas">
                                                            <i class="bi bi-folder2-open"></i>
                                                            Ver
                                                        </a>

                                                        <a href="{{ route('periodo.edit', $periodo->id) }}"
                                                        class="btn btn-outline-warning" title="Editar">
                                                            <i class="bi bi-pencil-square"></i>
                                                            Editar
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- PAGINACIÓN ACTIVOS -->
                            @if($periodosActivos->hasPages())
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted">
                                        Mostrando <strong>{{ $periodosActivos->firstItem() }}</strong> a
                                        <strong>{{ $periodosActivos->lastItem() }}</strong> de
                                        <strong>{{ $periodosActivos->total() }}</strong> periodos activos
                                    </div>
                                    <nav aria-label="Paginación de periodos activos">
                                        {{ $periodosActivos->onEachSide(1)->links('pagination::bootstrap-5') }}
                                    </nav>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No hay periodos activos</h5>
                                <p class="text-muted">Crea un nuevo periodo académico.</p>
                            </div>
                        @endif
                    </div>

                    <!-- TAB INACTIVOS -->
                    <div class="tab-pane fade" id="inactivo" role="tabpanel">
                        @if($periodosInactivos->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th width="80">ID</th>
                                            <th>Nombre</th>
                                            <th width="100">Año</th>
                                            <th>Descripción</th>
                                            <th width="200" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($periodosInactivos as $periodo)
                                            <tr class="text-muted">
                                                <td>#{{ $periodo->id }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        {{ $periodo->nombre }}
                                                    </div>
                                                </td>
                                                <td>{{ $periodo->anio }}</td>
                                                <td class="text-truncate" style="max-width: 250px;">
                                                    {{ $periodo->descripcion ?: 'Sin descripción' }}
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ url('matricula/' . $periodo->anio) }}"
                                                        class="btn btn-outline-{{ $periodo->estado == 1 ? 'primary' : 'secondary' }}"
                                                        title="Ver Matrículas">
                                                            <i class="bi bi-folder2-open"></i>
                                                            Ver
                                                        </a>
                                                        <a href="{{ route('periodo.edit', $periodo->id) }}"
                                                           class="btn btn-outline-warning" title="Editar">
                                                            <i class="bi bi-pencil-square"></i>
                                                            Editar
                                                        </a>
                                                        <form action="{{ route('periodo.destroy', $periodo->id) }}"
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="btn btn-outline-danger"
                                                                    onclick="return confirm('¿Estás seguro de eliminar el periodo \"{{ $periodo->nombre }}\"?')"
                                                                    title="Eliminar">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- PAGINACIÓN INACTIVOS -->
                            @if($periodosInactivos->hasPages())
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted">
                                        Mostrando <strong>{{ $periodosInactivos->firstItem() }}</strong> a
                                        <strong>{{ $periodosInactivos->lastItem() }}</strong> de
                                        <strong>{{ $periodosInactivos->total() }}</strong> periodos inactivos
                                    </div>
                                    <nav aria-label="Paginación de periodos inactivos">
                                        {{ $periodosInactivos->onEachSide(1)->links('pagination::bootstrap-5') }}
                                    </nav>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <h5>No hay periodos inactivos</h5>
                                <p class="text-muted">Todos los periodos están activos.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Footer informativo -->
            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Total:</strong> {{ $periodosActivos->total() + $periodosInactivos->total() }} periodos
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para mantener la pestaña activa al recargar -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Guardar la pestaña activa en localStorage
            const activeTab = localStorage.getItem('activePeriodoTab');
            if (activeTab) {
                const tab = new bootstrap.Tab(document.querySelector(`[href="${activeTab}"]`));
                tab.show();
            }

            // Detectar cambios de pestaña
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(event) {
                    localStorage.setItem('activePeriodoTab', event.target.getAttribute('href'));
                });
            });
        });
    </script>
@endsection
