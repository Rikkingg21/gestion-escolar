@extends('layouts.app')
@section('title', 'Tipos de Trámite')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Tipos de Trámites
                </h4>
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalCreate">
                    <i class="bi bi-plus me-1"></i> Nuevo Tipo
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width: 5%;">ID</th>
                            <th style="width: 15%;">Código</th>
                            <th style="width: 25%;">Nombre</th>
                            <th style="width: 10%;">Costo</th>
                            <th style="width: 10%;">Requiere Pago</th>
                            <th style="width: 10%;">Estado</th>
                            <th style="width: 15%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tipos as $tipo)
                        <tr>
                            <td class="text-center">{{ $tipo->id }}</td>
                            <td>{{ $tipo->codigo ?? '--' }}</td>
                            <td>{{ $tipo->nombre }}</td>
                            <td class="text-end">S/ {{ number_format($tipo->costo ?? 0, 2) }}</td>
                            <td class="text-center">
                                @if($tipo->requiere_pago)
                                    <span class="badge bg-warning text-dark">Sí</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tipo->estado == '1')
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $tipo->id }}">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="eliminar({{ $tipo->id }}, '{{ $tipo->nombre }}')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Editar -->
                        <div class="modal fade" id="modalEdit{{ $tipo->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('tramiteadmin.tipos-tramite.update', $tipo->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header bg-warning">
                                            <h5 class="modal-title">Editar Tipo de Trámite</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Nombre</label>
                                                <input type="text" name="nombre" class="form-control" value="{{ $tipo->nombre }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Código</label>
                                                <input type="text" name="codigo" class="form-control" value="{{ $tipo->codigo }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Descripción</label>
                                                <textarea name="descripcion" class="form-control" rows="3">{{ $tipo->descripcion }}</textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Costo</label>
                                                    <input type="number" step="0.01" name="costo" class="form-control" value="{{ $tipo->costo ?? 0 }}">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Tiempo estimado (días)</label>
                                                    <input type="number" name="tiempo_estimado_dias" class="form-control" value="{{ $tipo->tiempo_estimado_dias }}">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">¿Requiere pago?</label>
                                                    <select name="requiere_pago" class="form-select">
                                                        <option value="0" {{ $tipo->requiere_pago == 0 ? 'selected' : '' }}>No</option>
                                                        <option value="1" {{ $tipo->requiere_pago == 1 ? 'selected' : '' }}>Sí</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">¿Requiere documentos?</label>
                                                    <select name="requiere_documentos" class="form-select">
                                                        <option value="0" {{ $tipo->requiere_documentos == 0 ? 'selected' : '' }}>No</option>
                                                        <option value="1" {{ $tipo->requiere_documentos == 1 ? 'selected' : '' }}>Sí</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Estado</label>
                                                <select name="estado" class="form-select">
                                                    <option value="1" {{ $tipo->estado == '1' ? 'selected' : '' }}>Activo</option>
                                                    <option value="0" {{ $tipo->estado == '0' ? 'selected' : '' }}>Inactivo</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Guardar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $tipos->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tramiteadmin.tipos-tramite.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Nuevo Tipo de Trámite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" name="codigo" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Costo</label>
                            <input type="number" step="0.01" name="costo" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tiempo estimado (días)</label>
                            <input type="number" name="tiempo_estimado_dias" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">¿Requiere pago?</label>
                            <select name="requiere_pago" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">¿Requiere documentos?</label>
                            <select name="requiere_documentos" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
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
    function eliminar(id, nombre) {
        if (confirm(`¿Estás seguro de eliminar el tipo de trámite "${nombre}"?`)) {
            const form = document.createElement('form');
            form.action = "{{ url('tramite-admin/tipos-tramite') }}/" + id;
            form.method = 'POST';
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endsection
