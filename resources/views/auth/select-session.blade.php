<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <h2 class="mb-4 text-center">Selecciona una sesión</h2>
    <!--Me muestre el id usuario, y rol de sessionmain; id usuario, rol y session_sub -->
    <div class="session-info mb-4">
        <div class="mb-2">
            <strong>Sesión principal:</strong><br>
            @if(session('sessionmain'))
                {{ session('sessionmain')->nombre_usuario ?? 'No disponible' }}<br>
                <span class="">ID: {{ session('sessionmain')->id ?? '-' }}</span>

            @else
                <span class="">No hay sesión principal.</span>
            @endif
        </div>
        <div>
            <strong>Sesión sub (actual):</strong><br>
            @if(auth()->check())
                {{ auth()->user()->nombre_usuario ?? 'No disponible' }}<br>
                <span class="">ID: {{ auth()->user()->id ?? '-' }}</span>
                @if(session('current_role'))
                    <br><span class="badge bg-warning text-dark">Rol: {{ session('current_role') }}</span>
                @endif
            @else
                <span class="">No hay sesión sub activa.</span>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle bg-white">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Roles disponibles</th>
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $i => $usuario)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $usuario->nombre_usuario }}</td>
                        <td>{{ $usuario->nombre }} {{ $usuario->apellido_paterno }} {{ $usuario->apellido_materno }}</td>
                        <td>
                            @foreach($usuario->roles as $rol)
                                <form method="POST" action="{{ route('session.select') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $usuario->id }}">
                                    <input type="hidden" name="role" value="{{ $rol->nombre }}">
                                    <button type="submit" class="btn btn-outline-primary btn-sm mb-1">
                                        {{ ucfirst($rol->nombre) }}
                                    </button>
                                </form>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="text-center mt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-danger">Cerrar Sesión</button>
        </form>
    </div>
</div>
</body>
</html>
