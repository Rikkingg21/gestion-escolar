<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestión Escolar')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- JS de Select2 (después de jQuery) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/i18n/es.js"></script>

    <!-- DataTables CSS y JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.css" />
    <script src="https://cdn.datatables.net/2.3.1/js/dataTables.js"></script>

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
    <div class="d-flex">
        <!-- Sidebar -->
        @auth
        <div class="sidebar bg-dark text-white p-3" style="width: 250px;">
            <div class="text-center mb-4">
                <h4 class="text-success">nombre colegio</h4>
                <div>

                </div>
                <hr class="bg-light">
                <p class="text small">Bienvenido, {{ auth()->user()->nombre }}</p>
                @if(session('current_role'))
                <span class="badge bg-primary">
                    Rol: {{ ucfirst(session('current_role')) }}
                </span>
                @endif
            </div>

            <ul class="nav nav-pills flex-column">
                {{-- Solo para admin --}}
                @if(auth()->user()->hasRole('admin'))
                <li class="nav-item">
                    <a href="{{ route('colegioconfig.edit') }}" class="nav-link text-white {{ request()->routeIs('colegioconfig.*') ? 'active' : '' }}">
                        <i class="bi bi-building me-2"></i> Colegio
                    </a>
                </li>
                @endif

                {{-- Para admin y director --}}
                @if(auth()->user()->hasAnyRole(['admin', 'director']))
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('grados.index') }}" class="nav-link text-white {{ request()->routeIs('grado.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Grados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('materias.index') }}" class="nav-link text-white {{ request()->routeIs('materia.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Materias
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('mayas.index') }}" class="nav-link text-white {{ request()->routeIs('maya.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Mayas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('estudiantes.index') }}" class="nav-link text-white {{ request()->routeIs('estudiante.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('docente.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Docentes
                    </a>
                </li>

                @endif

                {{-- Ejemplo para docente --}}
                @if(auth()->user()->hasRole('docente'))
                <li class="nav-item">
                    <a href="{{ route('mayas.index') }}" class="nav-link text-white">
                        <i class="bi bi-journal-text me-2"></i> Mi Maya
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('docente.dashboard') }}" class="nav-link text-white">
                        <i class="bi bi-journal-text me-2"></i> Mis Cursos
                    </a>
                </li>
                @endif

                {{-- Cambiar rol si tiene más de uno --}}
                @if(auth()->user()->roles->count() > 1)
                <li class="nav-item">
                    <a href="{{ route('role.selection') }}" class="nav-link text-white">
                        <i class="bi bi-arrow-repeat me-2"></i> Cambiar Rol
                    </a>
                </li>
                @endif
            </ul>

            <div class="mt-auto pt-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light w-100">
                        <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
        @endauth

        <!-- Main Content -->
        <div class="flex-grow-1">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('js')
</body>
</html>
