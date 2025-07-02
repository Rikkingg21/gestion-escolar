<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-row-hidden {
            display: none;
        }

        /* Nuevo estilo para el contenedor de la tabla con scroll */
        .table-scroll-container {
            max-height: 50vh; /* 50% de la altura del viewport */
            overflow-y: auto; /* Habilita el scroll vertical cuando el contenido excede la altura máxima */
            border: 1px solid #dee2e6; /* Borde para el contenedor del scroll, opcional */
            border-radius: .375rem; /* Bordes redondeados, opcional */
        }

        /* Asegurarse de que la cabecera de la tabla no se desplaza con el cuerpo */
        .table-scroll-container thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa; /* Color de fondo para la cabecera pegajosa */
            z-index: 10; /* Asegura que esté sobre el contenido que se desplaza */
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Selecciona una sesión</h2>
    <div class="session-info mb-4">
        <div class="mb-3 d-flex flex-column flex-md-row align-items-md-end gap-3">
            <div class="flex-grow-1">
                <strong>Sesión principal:</strong><br>
                @if(session('sessionmain'))
                    {{ session('sessionmain')->nombre_usuario ?? 'No disponible' }}<br>
                    <span class="">ID: {{ session('sessionmain')->id ?? '-' }}</span>
                    @if(session('sessionmain')->roles->isNotEmpty())
                        <br><span class="badge bg-info text-dark">Roles principales: {{ session('sessionmain')->roles->pluck('nombre')->join(', ') }}</span>
                    @endif
                @else
                    <span class="">No hay sesión principal.</span>
                @endif
            </div>
            <div class="flex-grow-1">
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
    </div>

    @php
        $mainUser = session('sessionmain');
    @endphp

    @if($mainUser && ($mainUser->hasRole('admin') || $mainUser->hasRole('director')))
        <div class="mb-3 d-flex flex-column flex-md-row align-items-md-end gap-3">
            <div class="flex-grow-1">
                <label for="estadoFilter" class="form-label">Filtrar por estado:</label>
                <select name="estado" id="estadoFilter" class="form-select">
                    <option value="1">Activo</option>
                    <option value="2">Lector</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="flex-grow-1">
                <label for="searchFilter" class="form-label">Buscar usuario:</label>
                <input type="text" id="searchFilter" class="form-control" placeholder="Buscar por usuario o nombre completo">
            </div>
        </div>
    @endif

    <div class="table-responsive table-scroll-container">
        <table class="table table-bordered table-hover align-middle bg-white mb-0">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Estado</th>
                    <th>Roles disponibles</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                @foreach($usuarios as $i => $usuario)
                    <tr data-estado="{{ $usuario->estado }}" data-search="{{ Str::lower($usuario->nombre_usuario . ' ' . $usuario->nombre . ' ' . $usuario->apellido_paterno . ' ' . $usuario->apellido_materno) }}">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $usuario->nombre_usuario }}</td>
                        <td>{{ $usuario->nombre }} {{ $usuario->apellido_paterno }} {{ $usuario->apellido_materno }}</td>
                        <td>
                            @if($usuario->estado == 1)
                                <span class="badge bg-success">Activo</span>
                            @elseif($usuario->estado == 2)
                                <span class="badge bg-info">Lector</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            @foreach($usuario->roles as $rol)
                                <form method="POST" action="{{ route('session.select') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $usuario->id }}">
                                    <input type="hidden" name="role" value="{{ $rol->nombre }}">
                                    <button type="submit" class="btn btn-outline-primary btn-sm mb-1"
                                            @if($usuario->estado == 0) disabled @endif>
                                        {{ ucfirst($rol->nombre) }}
                                        @if($usuario->estado == 0) (Inactivo) @endif
                                    </button>
                                </form>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                @if($usuarios->isEmpty())
                    <tr>
                        <td colspan="5" class="text-center">No hay usuarios disponibles para seleccionar sesión.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="text-center mt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-danger">Cerrar Sesión Principal</button>
        </form>
        @if(auth()->check() && session('sessionmain') && auth()->user()->id != session('sessionmain')->id)
        <form method="POST" action="{{ route('logout_sub') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-secondary">Cerrar Sub-Sesión</button>
        </form>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const estadoFilter = document.getElementById('estadoFilter');
        const searchFilter = document.getElementById('searchFilter'); // Nuevo: Input de búsqueda
        const usersTableBody = document.getElementById('usersTableBody');
        const userRows = usersTableBody.querySelectorAll('tr[data-estado]');

        function applyFilters() {
            const selectedEstado = estadoFilter.value;
            const searchTerm = searchFilter.value.toLowerCase().trim(); // Nuevo: Término de búsqueda

            userRows.forEach(row => {
                const rowEstado = row.dataset.estado;
                const rowSearchData = row.dataset.search; // Nuevo: Datos para búsqueda

                const matchesEstado = (selectedEstado === 'all' || rowEstado === selectedEstado);
                const matchesSearch = (rowSearchData.includes(searchTerm) || searchTerm === ''); // Nueva condición de búsqueda

                if (matchesEstado && matchesSearch) {
                    row.classList.remove('table-row-hidden');
                } else {
                    row.classList.add('table-row-hidden');
                }
            });
        }

        // Event listeners para ambos filtros
        estadoFilter.addEventListener('change', applyFilters);
        searchFilter.addEventListener('keyup', applyFilters); // Se dispara al escribir

        // Aplicar los filtros iniciales al cargar la página
        applyFilters();
    });
</script>
</body>
</html>
