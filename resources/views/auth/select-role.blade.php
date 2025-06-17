<!DOCTYPE html>
<html lang="eS">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Rol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .badge-admin { background-color: #dc3545; }
        .badge-director { background-color: #fd7e14; }
        .badge-docente { background-color: #28a745; }
        .badge-auxiliar { background-color: #ffc107; color: #000; }
        .badge-estudiante { background-color: #17a2b8; }
        .badge-apoderado { background-color: #6c757d; }
    </style>
    @stack('css')
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="bi bi-person-rolodex me-2"></i> Seleccionar Rol</h4>
                    </div>

                    <div class="card-body p-4 text-center">
                        <p class="lead">Hola, {{ auth()->user()->nombre }}</p>
                        <p>Selecciona el rol con el que deseas trabajar:</p>

                        <form method="POST" action="{{ route('role.select') }}">
                            @csrf
                            <div class="d-grid gap-3">
                                @foreach(auth()->user()->roles as $role)
                                <button type="submit" name="selected_role" value="{{ $role->id }}"
                                        class="btn btn-outline-primary btn-lg">
                                    <i class="bi bi-person-badge me-2"></i>
                                    {{ ucfirst($role->nombre) }}
                                </button>
                                @endforeach
                            </div>
                        </form>
                        <div class="mt-auto pt-3">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



