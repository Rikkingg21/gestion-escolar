@extends('layouts.app')
@section('title', 'Materias')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-layers me-2"></i> Gestión de Materias</h5>
                    <div class="btn-group">
                        <a href="{{ route('materiacompetencia.index') }}" class="btn btn-light btn-sm text-primary">
                            <i class="bi bi-clipboard2-check me-1"></i> Competencias
                        </a>
                        <button type="button" class="btn btn-light btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#modalCrearMateria">
                            <i class="bi bi-plus-lg me-1"></i> Nueva Materia
                        </button>
                    </div>
                </div>
                <div class="card-body">

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <ul class="nav nav-tabs mb-3" id="materiaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="activos-tab" data-bs-toggle="tab" href="#activos" aria-selected="true" role="tab">Activos</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="inactivos-tab" data-bs-toggle="tab" href="#inactivos" aria-selected="false" role="tab">Inactivos</a>
        </li>
    </ul>

    <div class="tab-content" id="materiaTabsContent">
        {{-- Tab Activos --}}
        <div class="tab-pane fade show active" id="activos" role="tabpanel" aria-labelledby="activos-tab">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    Materias Activas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Nombre</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($materiasActivas as $materia)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $materia->nombre}}</td>
                                    <td>
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-warning mx-1" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarMateria"
                                                data-id="{{ $materia->id }}"
                                                data-nombre="{{ $materia->nombre }}"
                                                data-estado="{{ $materia->estado }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('materia.destroy', $materia->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger mx-1" title="Eliminar" onclick="return confirm('¿Eliminar esta materia?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">No hay materias activos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="showing-results text-muted">
                                Mostrando {{ $materiasActivas->firstItem() ?? 0 }} a {{ $materiasActivas->lastItem() ?? 0 }} de {{ $materiasActivas->total()}} materias
                            </div>
                            <div>
                                {{ $materiasActivas->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Inactivos --}}
        <div class="tab-pane fade" id="inactivos" role="tabpanel" aria-labelledby="inactivos-tab">
            <div class="card shadow mb-4">
                <div class="card-header bg-secondary text-white">
                    Materias Inactivas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Nombre</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($materiasInactivas as $materia)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $materia->nombre}}</td>
                                    <td>
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-warning mx-1" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarMateria"
                                                data-id="{{ $materia->id }}"
                                                data-nombre="{{ $materia->nombre }}"
                                                data-estado="{{ $materia->estado }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('materia.destroy', $materia->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar esta materia?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">No hay materias inactivos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="showing-results text-muted">
                                Mostrando {{ $materiasInactivas->firstItem() ?? 0 }} a {{ $materiasInactivas->lastItem() ?? 0 }} de {{ $materiasInactivas->total()}} materias
                            </div>
                            <div>
                                {{ $materiasInactivas->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal Crear Materia -->
<div class="modal fade" id="modalCrearMateria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCrearMateria" action="{{ route('materia.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-lg me-2"></i> Nueva Materia
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la Materia</label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" placeholder="Ingrese el nombre de la materia" value="{{ old('nombre') }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select @error('estado') is-invalid @enderror" name="estado" id="estado" required>
                            <option value="1" {{ old('estado') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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

<!-- Modal Editar Materia -->
<div class="modal fade" id="modalEditarMateria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditarMateria" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i> Editar Materia
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_edit" class="form-label">Nombre de la Materia</label>
                        <input type="text" name="nombre" id="nombre_edit" class="form-control @error('nombre') is-invalid @enderror" placeholder="Ingrese el nombre de la materia" value="{{ old('nombre') }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="estado_edit" class="form-label">Estado</label>
                        <select class="form-select @error('estado') is-invalid @enderror" name="estado" id="estado_edit" required>
                            <option value="1" {{ old('estado') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var formEditarMateria = document.getElementById('formEditarMateria');
        var modalEditarMateriaEl = document.getElementById('modalEditarMateria');
        var modalCrearMateriaEl = document.getElementById('modalCrearMateria');

        if (modalEditarMateriaEl) {
            modalEditarMateriaEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) {
                    return;
                }

                var id = button.getAttribute('data-id');

                formEditarMateria.action = '{{ route('materia.update', '__ID__') }}'.replace('__ID__', id);
                formEditarMateria.querySelector('input[name="id"]').value = id;
                formEditarMateria.querySelector('input[name="nombre"]').value = button.getAttribute('data-nombre');
                formEditarMateria.querySelector('select[name="estado"]').value = button.getAttribute('data-estado');
            });

            modalEditarMateriaEl.addEventListener('hidden.bs.modal', function () {
                formEditarMateria.reset();
            });
        }

        if (modalCrearMateriaEl && document.getElementById('formCrearMateria')) {
            modalCrearMateriaEl.addEventListener('hidden.bs.modal', function () {
                document.getElementById('formCrearMateria').reset();
            });
        }
    });

    @if (old('id'))
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formEditarMateria');
            var id = '{{ old('id') }}';

            form.action = '{{ route('materia.update', '__ID__') }}'.replace('__ID__', id);
            form.querySelector('input[name="id"]').value = id;
            form.querySelector('input[name="nombre"]').value = '{{ old('nombre') }}';
            form.querySelector('select[name="estado"]').value = '{{ old('estado') }}';

            new bootstrap.Modal(document.getElementById('modalEditarMateria')).show();
        });
    @elseif (old('nombre'))
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('modalCrearMateria')).show();
        });
    @endif
</script>
@endsection
