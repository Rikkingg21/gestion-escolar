@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-people-fill"></i> Administración de Usuarios
        </h1>

        @can('create', App\Models\User::class)
        <a href="{{ route('users.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Usuario
        </a>
        @endcan
    </div>

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#home" aria-selected="true" role="tab">Usuarios</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#profile" aria-selected="false" tabindex="-1" role="tab">Directores</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact" aria-selected="false" tabindex="-1" role="tab">Auxiliares</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact2" aria-selected="false" tabindex="-1" role="tab">Docentes</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact3" aria-selected="false" tabindex="-1" role="tab">Estudiantes</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact4" aria-selected="false" tabindex="-1" role="tab">Apoderados</a>
        </li>

    </ul>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane fade show active" id="home" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <h5>Usuarios</h5>
                        <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                            <thead class="table-dark">
                                <tr>
                                    <th>DNI</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Roles</th> <!-- Cambiado de "Rol" a "Roles" -->
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->dni }}</td>
                                    <td>{{ $user->nombre_usuario }}</td>
                                    <td>{{ $user->nombreCompleto }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                        <span class="badge bg-{{ $role->color }} mb-1">
                                            {{ ucfirst($role->nombre) }}
                                        </span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $user->estado == 'activo' ? 'success' : 'danger' }}">
                                            {{ ucfirst($user->estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            @can('update', $user)
                                            <a href="{{ route('users.edit', $user->id) }}"
                                            class="btn btn-sm btn-warning mx-1"
                                            title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @endcan

                                            @can('delete', $user)
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        title="Eliminar"
                                                        onclick="return confirm('¿Confirmar eliminación?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="showing-results text-muted">
                                Mostrando {{ $users->firstItem() }} a {{ $users->lastItem() }} de {{ $users->total() }} resultados
                            </div>
                            <div>
                                {{ $users->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="profile" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h5>Directores</h5>
                    @include('users._partials.users_table', ['users' => $directores])
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="contact" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h5>Auxiliares</h5>
                    @include('users._partials.users_table', ['users' => $auxiliares])
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="contact2" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h5>Docentes</h5>
                    @include('users._partials.users_table', ['users' => $docentes])
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="contact3" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h5>Estudiantes</h5>
                    @include('users._partials.users_table', ['users' => $estudiantes])
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="contact4" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h5>Apoderados</h5>
                    @include('users._partials.users_table', ['users' => $apoderados])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
