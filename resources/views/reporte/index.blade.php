@extends('layouts.app')
@section('title', 'Reportes')
@section('content')
<div class="container-fluid">
    <!-- Header Mejorado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">
                <i class="bi bi-file-text me-2"></i>Reportes Generales
            </h1>
            <p class="text-muted mb-0">Gestión y seguimiento de reportes académicos</p>
        </div>
        @if(auth()->check())
            @if(session('current_role') == 'admin' || session('current_role') == 'director' || session('current_role') == 'auxiliar' || session('current_role') == 'docente')
                <a href="{{ route('reporte.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Crear Reporte
                </a>
            @endif
        @endif
    </div>

    <!-- Información de Sesión -->
    @if(auth()->check())
        <div class="alert alert-light border mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong class="me-2">Rol activo:</strong>
                    <span class="badge bg-warning text-dark fs-6">{{ session('current_role') }}</span>
                </div>
                <small class="text-muted">Usuario: {{ auth()->user()->nombre ?? 'N/A' }}</small>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>No hay sesión sub activa.
        </div>
    @endif

    <!-- Card Principal -->
    <div class="card shadow border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-gray-800">
                <i class="bi bi-list-ul me-2"></i>Lista de Reportes
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Creador</th>
                            <th>Destinatario</th>
                            <th>Materia</th>
                            <th>Asunto</th>
                            <th>Citación</th>
                            <th>Estado</th>
                            <th class="text-center pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportes->sortByDesc('created_at') as $reporte)
                        @php
                            $estado = $reporte->estadoreporte->estado ?? 1;
                            $estados = [
                                1 => ['text' => 'Creado', 'class' => 'bg-secondary'],
                                2 => ['text' => 'Enviado', 'class' => 'bg-primary'],
                                3 => ['text' => 'Visto', 'class' => 'bg-info'],
                                4 => ['text' => 'Aceptado', 'class' => 'bg-success']
                            ];
                            $needsAlert = in_array($estado, [1, 2]);
                        @endphp
                        <tr class="@if($needsAlert) table-warning @endif">
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <span class="fw-medium">{{ $reporte->creador->nombre }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted">{{ $reporte->destinatario->apoderado->user->nombre ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $reporte->materia->nombre ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-medium">{{ $reporte->asunto }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <small class="text-primary fw-medium">
                                        <i class="bi bi-calendar me-1"></i>
                                        {{ \Carbon\Carbon::parse($reporte->fecha)->format('d/m/Y') }}
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>{{ $reporte->hora }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge {{ $estados[$estado]['class'] }} rounded-pill me-2">
                                        {{ $estados[$estado]['text'] }}
                                    </span>
                                    @if($needsAlert)
                                    <i class="bi bi-bell-fill text-warning animate-bell"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="Reporte pendiente - Requiere atención"></i>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('reporte.show', $reporte->id) }}"
                                       class="btn btn-outline-primary"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if($estado == 1 && (auth()->user()->id == $reporte->creador_id || auth()->user()->hasRole('admin')))
                                    <form action="{{ route('reporte.destroy', $reporte->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="Eliminar reporte"
                                                onclick="return confirm('¿Estás seguro de eliminar este reporte?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Estilos Mejorados -->
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}

.table th {
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f8f9fa;
}

.table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.05) !important;
    border-left: 3px solid #ffc107;
}

.badge {
    font-weight: 500;
    font-size: 0.75rem;
}

.btn-group .btn {
    border-radius: 6px;
    margin: 0 2px;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 12px 12px 0 0 !important;
}

/* Animación de campanita mejorada */
.animate-bell {
    animation: ring 2s ease-in-out infinite;
    font-size: 1.1rem;
}

@keyframes ring {
    0%, 100% {
        transform: rotate(0deg) scale(1);
    }
    10% {
        transform: rotate(15deg) scale(1.1);
    }
    20% {
        transform: rotate(-15deg) scale(1.1);
    }
    30% {
        transform: rotate(10deg) scale(1.05);
    }
    40% {
        transform: rotate(-10deg) scale(1.05);
    }
    50% {
        transform: rotate(5deg) scale(1.02);
    }
    60% {
        transform: rotate(-5deg) scale(1.02);
    }
    70%, 90% {
        transform: rotate(0deg) scale(1);
    }
}

/* Mejoras responsive */
@media (max-width: 768px) {
    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        align-items: flex-start !important;
    }

    .d-flex.justify-content-between.align-items-center .btn {
        margin-top: 1rem;
        align-self: flex-end;
    }

    .table-responsive {
        font-size: 0.875rem;
    }

    .btn-group .btn {
        width: 32px;
        height: 32px;
    }
}

/* Efectos hover suaves */
.btn {
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

.table-hover tbody tr:hover {
    transform: scale(1.001);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<!-- Script para tooltips -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
