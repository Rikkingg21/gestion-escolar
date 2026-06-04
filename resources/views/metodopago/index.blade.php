@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-credit-card me-2"></i>Métodos de Pago
                </h4>
                <a href="{{ route('metodopago.create') }}" class="btn btn-light">
                    <i class="fas fa-plus me-2"></i> Nuevo Método de Pago
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" action="{{ route('metodopago.index') }}" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-search me-1"></i> Buscar
                        </label>
                        <input type="text" name="search" class="form-control" placeholder="Nombre, entidad o titular..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-tags me-1"></i> Categoría
                        </label>
                        <select name="categoria" class="form-select">
                            <option value="">Todas las categorías</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat }}" {{ request('categoria') == $cat ? 'selected' : '' }}>
                                    {{ ucfirst($cat) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-circle me-1"></i> Estado
                        </label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ request('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 me-2">
                            <i class="fas fa-search me-2"></i> Filtrar
                        </button>
                        <a href="{{ route('metodopago.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-eraser me-2"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>

            <!-- Tabla de métodos de pago -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width: 5%;">ID</th>
                            <th style="width: 12%;">Color</th>
                            <th style="width: 15%;">Nombre</th>
                            <th style="width: 10%;">Categoría</th>
                            <th style="width: 13%;">Entidad Financiera</th>
                            <th style="width: 13%;">N° Cuenta / Celular</th>
                            <th style="width: 10%;">Titular</th>
                            <th style="width: 5%;">Verif.</th>
                            <th style="width: 7%;">Estado</th>
                            <th style="width: 10%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tiposPago as $tipo)
                        <tr>
                            <td class="text-center fw-bold">{{ $tipo->id }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background-color: {{ $tipo->color_hex }}; border: 1px solid #ddd; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></div>
                                </div>
                                <small class="text-muted d-block mt-1">{{ $tipo->color_hex }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-credit-card me-2 text-primary"></i>
                                    {{ $tipo->nombre }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($tipo->categoria == 'transferencia')
                                    <span class="badge bg-primary"><i class="fas fa-university me-1"></i> Transferencia</span>
                                @elseif($tipo->categoria == 'billetera_digital')
                                    <span class="badge bg-info"><i class="fas fa-mobile-alt me-1"></i> Billetera</span>
                                @elseif($tipo->categoria == 'efectivo')
                                    <span class="badge bg-success"><i class="fas fa-money-bill me-1"></i> Efectivo</span>
                                @elseif($tipo->categoria == 'tarjeta')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-credit-card me-1"></i> Tarjeta</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($tipo->categoria) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($tipo->entidad_financiera)
                                    <i class="fas fa-building me-1 text-secondary"></i> {{ $tipo->entidad_financiera }}
                                @else
                                    --
                                @endif
                            </td>
                            <td>
                                @if($tipo->categoria == 'billetera_digital')
                                    <i class="fas fa-mobile-alt me-1 text-primary"></i> {{ $tipo->numero_celular ?? '--' }}
                                @else
                                    <i class="fas fa-hashtag me-1 text-primary"></i> {{ $tipo->numero_cuenta ?? '--' }}
                                @endif
                            </td>
                            <td>
                                @if($tipo->titular_cuenta)
                                    <i class="fas fa-user me-1 text-secondary"></i> {{ $tipo->titular_cuenta }}
                                @else
                                    --
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tipo->requiere_verificacion)
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-check-circle me-1"></i> Sí
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-times-circle me-1"></i> No
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tipo->estado == '1')
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i> Activo
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="fas fa-ban me-1"></i> Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('metodopago.edit', $tipo->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit me-1"></i> Editar
                                    </a>
                                    <button type="button" class="btn btn-sm {{ $tipo->estado == '1' ? 'btn-secondary' : 'btn-success' }}"
                                            onclick="cambiarEstado({{ $tipo->id }}, '{{ $tipo->estado }}')"
                                            title="{{ $tipo->estado == '1' ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas {{ $tipo->estado == '1' ? 'fa-ban' : 'fa-check' }} me-1"></i>
                                        {{ $tipo->estado == '1' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminar({{ $tipo->id }}, '{{ $tipo->nombre }}')" title="Eliminar">
                                        <i class="fas fa-trash me-1"></i> Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="fas fa-credit-card fa-3x mb-3 d-block"></i>
                                    No hay métodos de pago registrados.
                                    <div class="mt-3">
                                        <a href="{{ route('metodopago.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Agregar el primero
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4">
                {{ $tiposPago->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Formulario para cambiar estado -->
<form id="formCambiarEstado" action="" method="POST" style="display: none;">
    @csrf
    @method('PATCH')
</form>

<!-- Formulario para eliminar -->
<form id="formEliminar" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function cambiarEstado(id, estadoActual) {
        const accion = estadoActual == '1' ? 'desactivar' : 'activar';
        const mensaje = `¿Estás seguro de ${accion} el método de pago?`;

        if (confirm(mensaje)) {
            const form = document.getElementById('formCambiarEstado');
            form.action = "{{ url('metodos-de-pago') }}/" + id + "/status";
            form.submit();
        }
    }

    function eliminar(id, nombre) {
        if (confirm(`¿Estás seguro de eliminar el método de pago "${nombre}"? Esta acción no se puede deshacer.`)) {
            const form = document.getElementById('formEliminar');
            form.action = "{{ url('metodos-de-pago') }}/" + id;
            form.submit();
        }
    }
</script>
@endsection
