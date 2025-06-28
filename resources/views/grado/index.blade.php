@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-layers me-2"></i> Gestión de Grados
        </h1>
        <a href="{{ route('grado.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Grado
        </a>
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
                                            <a href="{{ route('grado.edit', $grado->id) }}" class="btn btn-sm btn-warning mx-1" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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
                                            <a href="{{ route('grado.edit', $grado->id) }}" class="btn btn-sm btn-warning mx-1" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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
@endsection
