@extends('layouts.app')

@section('title', 'Panel de Administración')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Admin</h1>
    </div>

    <div class="row">
        <!-- Card 1 - Estudiantes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Estudiantes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ App\Models\User::whereHas('roles', fn($q) => $q->where('nombre', 'estudiante'))->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2 - Docentes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Docentes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ App\Models\User::whereHas('roles', fn($q) => $q->where('nombre', 'docente'))->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-video3 fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3 - Administradores -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Administradores</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ App\Models\User::whereHas('roles', fn($q) => $q->where('nombre', 'admin'))->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-shield-lock fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Últimos registros</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Rol</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($counts['latestUsers'] as $user)
                                <tr>
                                    <td>{{ $user->nombre }} {{ $user->apellido_paterno }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                        <span class="badge bg-{{ $role->color }}">{{ ucfirst($role->nombre) }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach

                                @if($counts['latestUsers']->isEmpty())
                                <tr>
                                    <td colspan="3" class="text-center">No hay usuarios para mostrar</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
