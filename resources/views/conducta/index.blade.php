@extends('layouts.app')

@section('content')

<div class="container">
    <h1>Lista de Conductas</h1>

    <!-- Pestañas -->
    <ul class="nav nav-tabs" id="conductaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="activas-tab" data-bs-toggle="tab" data-bs-target="#activas" type="button" role="tab">
                Activas ({{ $conductasActivas->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="inactivas-tab" data-bs-toggle="tab" data-bs-target="#inactivas" type="button" role="tab">
                Inactivas ({{ $conductasInactivas->count() }})
            </button>
        </li>
    </ul>

    <!-- Contenido de las pestañas -->
    <div class="tab-content" id="conductaTabsContent">
        <!-- Pestaña Activas -->
        <div class="tab-pane fade show active" id="activas" role="tabpanel">
            <table class="table table-striped mt-3">
                <thead class="table-success">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conductasActivas as $conducta)
                        <tr>
                            <td>{{ $conducta->id }}</td>
                            <td>{{ $conducta->nombre }}</td>
                            <td>
                                <a href="{{ route('conducta.edit', $conducta->id) }}" class="btn btn-primary btn-sm">Editar</a>
                                <form action="{{ route('conducta.destroy', $conducta->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No hay conductas activas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pestaña Inactivas -->
        <div class="tab-pane fade" id="inactivas" role="tabpanel">
            <table class="table table-striped mt-3">
                <thead class="table-secondary">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conductasInactivas as $conducta)
                        <tr>
                            <td>{{ $conducta->id }}</td>
                            <td>{{ $conducta->nombre }}</td>
                            <td>
                                <a href="{{ route('conducta.edit', $conducta->id) }}" class="btn btn-primary btn-sm">Editar</a>
                                <form action="{{ route('conducta.destroy', $conducta->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No hay conductas inactivas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('conducta.create') }}" class="btn btn-success">Crear Nueva Conducta</a>
    </div>
</div>

@endsection
