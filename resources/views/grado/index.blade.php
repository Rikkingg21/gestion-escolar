@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-layers me-2"></i> Gestión de Grados
        </h1>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearGrado">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Grado
        </button>
    </div>
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

    <ul class="nav nav-tabs mb-3" id="gradoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="activos-tab" data-bs-toggle="tab" href="#activos" aria-selected="true" role="tab">Activos</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="inactivos-tab" data-bs-toggle="tab" href="#inactivos" aria-selected="false" role="tab">Inactivos</a>
        </li>
    </ul>
    <div class="tab-content" id="gradoTabsContent">
        {{-- Tab Activos --}}
        <div class="tab-pane fade show active" id="activos" role="tabpanel" aria-labelledby="activos-tab">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    Grados Activos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Grado</th>
                                    <th>Sección</th>
                                    <th>Nivel</th>
                                    <th>Nombre Completo</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gradosActivos as $grado)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $grado->grado }}°</td>
                                    <td>{{ $grado->seccion }}</td>
                                    <td>{{ $grado->nivel }}</td>
                                    <td>{{ $grado->nombreCompleto }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('grado.estudiantes', $grado->id) }}" class="btn btn-sm btn-primary" title="Relación de Estudiantes">
                                                <i class="bi bi-person-rolodex"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-warning mx-1" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarGrado"
                                                data-id="{{ $grado->id }}"
                                                data-grado="{{ $grado->grado }}"
                                                data-seccion="{{ $grado->seccion }}"
                                                data-nivel="{{ strtolower($grado->nivel) }}"
                                                data-estado="{{ $grado->estado }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('grado.destroy', $grado->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este grado?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No hay grados activos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="showing-results text-muted">
                                Mostrando {{ $gradosActivos->firstItem() ?? 0 }} a {{ $gradosActivos->lastItem() ?? 0 }} de {{ $gradosActivos->total() }} grados
                            </div>
                            <div>
                                {{ $gradosActivos->links('pagination::bootstrap-4') }}
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
                    Grados Inactivos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Grado</th>
                                    <th>Sección</th>
                                    <th>Nivel</th>
                                    <th>Nombre Completo</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gradosInactivos as $grado)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $grado->grado }}°</td>
                                    <td>{{ $grado->seccion }}</td>
                                    <td>{{ $grado->nivel }}</td>
                                    <td>{{ $grado->nombreCompleto }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-warning mx-1" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarGrado"
                                                data-id="{{ $grado->id }}"
                                                data-grado="{{ $grado->grado }}"
                                                data-seccion="{{ $grado->seccion }}"
                                                data-nivel="{{ strtolower($grado->nivel) }}"
                                                data-estado="{{ $grado->estado }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('grado.destroy', $grado->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este grado?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No hay grados inactivos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="showing-results text-muted">
                                Mostrando {{ $gradosInactivos->firstItem() ?? 0 }} a {{ $gradosInactivos->lastItem() ?? 0 }} de {{ $gradosInactivos->total() }} grados
                            </div>
                            <div>
                                {{ $gradosInactivos->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Grado -->
<div class="modal fade" id="modalCrearGrado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCrearGrado" action="{{ route('grado.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-lg me-2"></i> Nuevo Grado
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="grado" class="form-label">Grado</label>
                        <input type="number" min="1" max="12" class="form-control @error('grado') is-invalid @enderror" id="grado" name="grado" value="{{ old('grado') }}" required>
                        @error('grado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="seccion" class="form-label">Sección</label>
                        <input type="text" maxlength="1" class="form-control @error('seccion') is-invalid @enderror" id="seccion" name="seccion" value="{{ old('seccion') }}" required>
                        @error('seccion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="nivel" class="form-label">Nivel</label>
                        <select name="nivel" id="nivel" class="form-select @error('nivel') is-invalid @enderror" required>
                            <option value="primaria" {{ old('nivel') == 'primaria' ? 'selected' : '' }}>Primaria</option>
                            <option value="secundaria" {{ old('nivel') == 'secundaria' ? 'selected' : '' }}>Secundaria</option>
                        </select>
                        @error('nivel')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
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

<!-- Modal Editar Grado -->
<div class="modal fade" id="modalEditarGrado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditarGrado" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i> Editar Grado
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="grado_edit" class="form-label">Grado</label>
                        <input type="number" min="1" max="12" class="form-control @error('grado') is-invalid @enderror" id="grado_edit" name="grado" value="{{ old('grado') }}" required>
                        @error('grado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="seccion_edit" class="form-label">Sección</label>
                        <input type="text" maxlength="1" class="form-control @error('seccion') is-invalid @enderror" id="seccion_edit" name="seccion" value="{{ old('seccion') }}" required>
                        @error('seccion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="nivel_edit" class="form-label">Nivel</label>
                        <select name="nivel" id="nivel_edit" class="form-select @error('nivel') is-invalid @enderror" required>
                            <option value="primaria" {{ old('nivel') == 'primaria' ? 'selected' : '' }}>Primaria</option>
                            <option value="secundaria" {{ old('nivel') == 'secundaria' ? 'selected' : '' }}>Secundaria</option>
                        </select>
                        @error('nivel')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="estado_edit" class="form-label">Estado</label>
                        <select class="form-select @error('estado') is-invalid @enderror" id="estado_edit" name="estado" required>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var formEditarGrado = document.getElementById('formEditarGrado');
        var modalEditarGradoEl = document.getElementById('modalEditarGrado');
        var modalCrearGradoEl = document.getElementById('modalCrearGrado');

        if (modalEditarGradoEl) {
            modalEditarGradoEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) {
                    return;
                }

                var id = button.getAttribute('data-id');

                formEditarGrado.action = '{{ route('grado.update', '__ID__') }}'.replace('__ID__', id);
                formEditarGrado.querySelector('input[name="id"]').value = id;
                formEditarGrado.querySelector('input[name="grado"]').value = button.getAttribute('data-grado');
                formEditarGrado.querySelector('input[name="seccion"]').value = button.getAttribute('data-seccion');
                formEditarGrado.querySelector('select[name="nivel"]').value = button.getAttribute('data-nivel');
                formEditarGrado.querySelector('select[name="estado"]').value = button.getAttribute('data-estado');
            });

            modalEditarGradoEl.addEventListener('hidden.bs.modal', function () {
                formEditarGrado.reset();
            });
        }

        if (modalCrearGradoEl && document.getElementById('formCrearGrado')) {
            modalCrearGradoEl.addEventListener('hidden.bs.modal', function () {
                document.getElementById('formCrearGrado').reset();
            });
        }
    });

    @if (old('id'))
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formEditarGrado');
            var id = '{{ old('id') }}';

            form.action = '{{ route('grado.update', '__ID__') }}'.replace('__ID__', id);
            form.querySelector('input[name="id"]').value = id;
            form.querySelector('input[name="grado"]').value = '{{ old('grado') }}';
            form.querySelector('input[name="seccion"]').value = '{{ old('seccion') }}';
            form.querySelector('select[name="nivel"]').value = '{{ old('nivel') }}';
            form.querySelector('select[name="estado"]').value = '{{ old('estado') }}';

            new bootstrap.Modal(document.getElementById('modalEditarGrado')).show();
        });
    @elseif (old('grado'))
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('modalCrearGrado')).show();
        });
    @endif
</script>
@endsection
