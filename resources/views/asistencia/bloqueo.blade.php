@extends('layouts.app')

@section('title', 'Bloqueo de Asistencias')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-primary fw-bold">
            <i class="fas fa-lock me-2"></i> Bloqueo de Asistencias
        </h2>
        <a href="{{ route('asistencia.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('bloqueo.view') }}" id="filtroForm">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="periodo_id" class="form-label">Período *</label>
                        <select name="periodo_id" id="periodo_id" class="form-select" required>
                            <option value="">Seleccionar Período</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}"
                                    {{ $periodoSeleccionado == $periodo->id ? 'selected' : '' }}>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="mes" class="form-label">Mes *</label>
                        <select name="mes" id="mes" class="form-select" required>
                            <option value="">Seleccionar Mes</option>
                            @foreach($meses as $num => $nombre)
                                <option value="{{ $num }}" {{ $mesSeleccionado == $num ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="grado_id" class="form-label">Grado (Opcional)</label>
                        <select name="grado_id" id="grado_id" class="form-select">
                            <option value="">Todos los grados</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}" {{ $gradoSeleccionado == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->nivel }} - {{ $grado->grado }}° {{ $grado->seccion }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                        <a href="{{ route('bloqueo.view') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-broom me-1"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($periodoSeleccionado && $mesSeleccionado)
        <!-- Resumen de Estados -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ $contadoresEstados['libres'] }}</h3>
                        <small>Asistencias Libres</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ $contadoresEstados['bloqueados'] }}</h3>
                        <small>Bloqueo Temporal</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ $contadoresEstados['bloqueados_def'] }}</h3>
                        <small>Bloqueo Definitivo</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ $contadoresEstados['total'] }}</h3>
                        <small>Total</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Acciones Masivas</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#bloquearModal">
                            <i class="fas fa-lock me-1"></i> Bloquear Temporal
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#bloquearDefinitivoModal">
                            <i class="fas fa-lock me-1"></i> Bloquear Definitivo
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#liberarModal">
                            <i class="fas fa-unlock-alt me-1"></i> Liberar Temporal
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#liberarDefinitivoModal">
                            <i class="fas fa-unlock me-1"></i> Liberar Definitivo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Asistencias -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Listado de Asistencias</h5>
                <span class="badge bg-secondary">{{ $asistencias->count() }} registros</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Grado</th>
                                <th>Tipo Asistencia</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($asistencias as $index => $asistencia)
                            @php
                                $estado = is_string($asistencia->estado) ? (int)$asistencia->estado : $asistencia->estado;
                                $badgeClass = match($estado) {
                                    0 => 'bg-success',
                                    1 => 'bg-warning text-dark',
                                    2 => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                $estadoTexto = match($estado) {
                                    0 => 'Libre',
                                    1 => 'Bloqueo Temporal',
                                    2 => 'Bloqueo Definitivo',
                                    default => 'Desconocido'
                                };
                                $apellidos = trim($asistencia->estudiante->user->apellido_paterno . ' ' . $asistencia->estudiante->user->apellido_materno);
                                $nombreCompleto = $apellidos . ', ' . $asistencia->estudiante->user->nombre;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $nombreCompleto }}</td>
                                <td>{{ $asistencia->grado->nivel ?? 'N/A' }} - {{ $asistencia->grado->grado ?? '' }}° {{ $asistencia->grado->seccion ?? '' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $asistencia->tipoasistencia->nombre ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $asistencia->hora ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">{{ $estadoTexto }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-2x text-muted mb-2 d-block"></i>
                                    No se encontraron registros para los filtros seleccionados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal Bloquear Temporal -->
<div class="modal fade" id="bloquearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('asistencia.bloquear-masivo') }}" method="POST">
                @csrf
                <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Confirmar Bloqueo Temporal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas <strong>BLOQUEAR TEMPORALMENTE</strong> las asistencias?</p>
                    <div class="alert alert-warning">
                        <strong>Resumen:</strong><br>
                        Período: {{ $periodos->firstWhere('id', $periodoSeleccionado)->nombre ?? 'N/A' }}<br>
                        Mes: {{ $meses[$mesSeleccionado] ?? 'N/A' }}<br>
                        Grado: {{ $gradoSeleccionado ? ($grados->firstWhere('id', $gradoSeleccionado)->nivel ?? 'N/A') . ' - ' . ($grados->firstWhere('id', $gradoSeleccionado)->grado ?? '') . '° ' . ($grados->firstWhere('id', $gradoSeleccionado)->seccion ?? '') : 'Todos los grados' }}<br>
                        Registros a bloquear: <strong>{{ $contadoresEstados['libres'] }}</strong> asistencias libres
                    </div>
                    <p class="text-muted small mb-0">Esta acción cambiará las asistencias de estado <strong>Libre (0)</strong> a <strong>Bloqueo Temporal (1)</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Confirmar Bloqueo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bloquear Definitivo -->
<div class="modal fade" id="bloquearDefinitivoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('asistencia.bloquear-definitivo-masivo') }}" method="POST">
                @csrf
                <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Bloqueo Definitivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas <strong>BLOQUEAR DEFINITIVAMENTE</strong> las asistencias?</p>
                    <div class="alert alert-danger">
                        <strong>Resumen:</strong><br>
                        Período: {{ $periodos->firstWhere('id', $periodoSeleccionado)->nombre ?? 'N/A' }}<br>
                        Mes: {{ $meses[$mesSeleccionado] ?? 'N/A' }}<br>
                        Grado: {{ $gradoSeleccionado ? ($grados->firstWhere('id', $gradoSeleccionado)->nivel ?? 'N/A') . ' - ' . ($grados->firstWhere('id', $gradoSeleccionado)->grado ?? '') . '° ' . ($grados->firstWhere('id', $gradoSeleccionado)->seccion ?? '') : 'Todos los grados' }}<br>
                        Registros a bloquear: <strong>{{ $contadoresEstados['bloqueados'] }}</strong> asistencias en bloqueo temporal
                    </div>
                    <p class="text-muted small mb-0">Esta acción cambiará las asistencias de estado <strong>Bloqueo Temporal (1)</strong> a <strong>Bloqueo Definitivo (2)</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Bloqueo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Liberar Temporal -->
<div class="modal fade" id="liberarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('asistencia.liberar-masivo') }}" method="POST">
                @csrf
                <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Confirmar Liberación Temporal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas <strong>LIBERAR</strong> las asistencias?</p>
                    <div class="alert alert-info">
                        <strong>Resumen:</strong><br>
                        Período: {{ $periodos->firstWhere('id', $periodoSeleccionado)->nombre ?? 'N/A' }}<br>
                        Mes: {{ $meses[$mesSeleccionado] ?? 'N/A' }}<br>
                        Grado: {{ $gradoSeleccionado ? ($grados->firstWhere('id', $gradoSeleccionado)->nivel ?? 'N/A') . ' - ' . ($grados->firstWhere('id', $gradoSeleccionado)->grado ?? '') . '° ' . ($grados->firstWhere('id', $gradoSeleccionado)->seccion ?? '') : 'Todos los grados' }}<br>
                        Registros a liberar: <strong>{{ $contadoresEstados['bloqueados'] }}</strong> asistencias en bloqueo temporal
                    </div>
                    <p class="text-muted small mb-0">Esta acción cambiará las asistencias de estado <strong>Bloqueo Temporal (1)</strong> a <strong>Libre (0)</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Confirmar Liberación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Liberar Definitivo (requiere autorización) -->
<div class="modal fade" id="liberarDefinitivoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('asistencia.liberar-definitivo-masivo') }}" method="POST">
                @csrf
                <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">Autorización - Liberar Bloqueo Definitivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas <strong>LIBERAR</strong> asistencias que están en <strong>BLOQUEO DEFINITIVO</strong>?</p>
                    <div class="alert alert-secondary">
                        <strong>Resumen:</strong><br>
                        Período: {{ $periodos->firstWhere('id', $periodoSeleccionado)->nombre ?? 'N/A' }}<br>
                        Mes: {{ $meses[$mesSeleccionado] ?? 'N/A' }}<br>
                        Grado: {{ $gradoSeleccionado ? ($grados->firstWhere('id', $gradoSeleccionado)->nivel ?? 'N/A') . ' - ' . ($grados->firstWhere('id', $gradoSeleccionado)->grado ?? '') . '° ' . ($grados->firstWhere('id', $gradoSeleccionado)->seccion ?? '') : 'Todos los grados' }}<br>
                        Registros a liberar: <strong>{{ $contadoresEstados['bloqueados_def'] }}</strong> asistencias en bloqueo definitivo
                    </div>

                    <div class="mb-3">
                        <label for="usuario_autorizador_id" class="form-label">Usuario Autorizador (Admin/Director) *</label>
                        <select name="usuario_autorizador_id" id="usuario_autorizador_id" class="form-select" required>
                            <option value="">Seleccionar usuario</option>
                            @foreach($usuariosAutorizados as $usuario)
                                <option value="{{ $usuario->id }}">
                                    {{ $usuario->nombre }} {{ $usuario->apellido_paterno }} ({{ $usuario->roles->pluck('nombre')->implode(', ') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Contraseña del Usuario Autorizador *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    </div>

                    <p class="text-danger small mb-0">⚠️ Esta acción requiere autorización de un administrador o director.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">Confirmar Liberación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar que se seleccionen período y mes antes de enviar
    const filtroForm = document.getElementById('filtroForm');
    if (filtroForm) {
        filtroForm.addEventListener('submit', function(e) {
            const periodoId = document.getElementById('periodo_id').value;
            const mes = document.getElementById('mes').value;

            if (!periodoId) {
                e.preventDefault();
                alert('Por favor, seleccione un período');
                return false;
            }
            if (!mes) {
                e.preventDefault();
                alert('Por favor, seleccione un mes');
                return false;
            }
        });
    }

    // Mostrar notificaciones con Toast (opcional)
    @if(session('success'))
        const toast = document.createElement('div');
        toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <i class="fas fa-check-circle me-2"></i> ${session('success')}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    @endif

    @if(session('error'))
        const toastError = document.createElement('div');
        toastError.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3';
        toastError.style.zIndex = '9999';
        toastError.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i> ${session('error')}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toastError);
        setTimeout(() => toastError.remove(), 5000);
    @endif

    @if(session('info'))
        const toastInfo = document.createElement('div');
        toastInfo.className = 'alert alert-info alert-dismissible fade show position-fixed top-0 end-0 m-3';
        toastInfo.style.zIndex = '9999';
        toastInfo.innerHTML = `
            <i class="fas fa-info-circle me-2"></i> ${session('info')}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toastInfo);
        setTimeout(() => toastInfo.remove(), 5000);
    @endif
});
</script>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            font-size: 10px;
        }
        .table {
            font-size: 9px;
        }
    }
</style>
@endsection
