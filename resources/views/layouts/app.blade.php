<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestión Escolar')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

</head>
<body class="min-vh-100">

    <div class="d-flex min-vh-100" style="height: 100vh;">

        <!-- Sidebar -->
        <div class="sidebar bg-dark text-white p-3 d-flex flex-column h-100" style="width: 250px;">
            <button class="toggle-btn mb-3" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="text-center mb-4"><!--encabezado-->
                <h4 class="text-success">{{ $colegio->nombre }}</h4>
                <div>

                </div>
                <hr class="bg-light">
                <div class="accordion mb-2" id="accordionSesiones">
                    <!-- Sesión principal -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingPrincipal">
                            <button class="accordion-button collapsed py-2 small" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrincipal" aria-expanded="false" aria-controls="collapsePrincipal">
                                Sesión principal
                            </button>
                        </h2>
                        <div id="collapsePrincipal" class="accordion-collapse collapse" aria-labelledby="headingPrincipal">
                            <div class="accordion-body py-2 small">
                                @if(session('sessionmain'))
                                    <div><strong>Usuario:</strong> {{ session('sessionmain')->nombre_usuario ?? 'No disponible' }}</div>
                                    <div><strong>ID:</strong> <span class="badge bg-primary rounded-pill">{{ session('sessionmain')->id ?? '-' }}</span></div>
                                @else
                                    <div class="text-muted">No hay sesión principal.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- Sesión sub (actual) -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSub">
                            <button class="accordion-button collapsed py-2 small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSub" aria-expanded="false" aria-controls="collapseSub">
                                Sesión sub (actual)
                            </button>
                        </h2>
                        <div id="collapseSub" class="accordion-collapse collapse" aria-labelledby="headingSub">
                            <div class="accordion-body py-2 small">
                                @if(auth()->check())
                                    <div><strong>DNI:</strong> {{ auth()->user()->dni ?? '-' }}</div>
                                    <div>{{ auth()->user()->nombre ?? '-' }}
                                    {{ auth()->user()->apellido_paterno ?? '-' }}
                                    {{ auth()->user()->apellido_materno ?? '-' }}</div>
                                    <div><strong>ID:</strong> <span class="badge bg-primary rounded-pill">{{ auth()->user()->id ?? '-' }}</span></div>
                                    @if(session('current_role'))
                                        <div><strong>Rol:</strong> <span class="badge bg-warning text-dark">{{ session('current_role') }}</span></div>
                                    @endif
                                @else
                                    <div class="text-muted">No hay sesión sub activa.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text small">Bienvenido, {{ auth()->user()->nombre }}</p>

                <span class="badge bg-primary">
                    Rol: {{ ucfirst(session('current_role')) }}
                </span>

            </div>

            <ul class="nav nav-pills flex-column"><!--contenido-->
                @if(session('current_role') === 'admin')
                {{-- Solo para admin --}}
                <li class="nav-item">
                    <a class="nav-link text-white" data-bs-toggle="collapse" href="#collapseAdmin" role="button" aria-expanded="false" aria-controls="collapseAdmin">
                        <i class="bi bi-person-gear me-2"></i> <span>Administración</span>
                    </a>
                    <div class="collapse ps-3" id="collapseAdmin">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="{{ route('colegioconfig.edit') }}" class="nav-link text-white {{ request()->routeIs('colegioconfig.*') ? 'active' : '' }}">
                                    <i class="bi bi-building me-2"></i> <span>Colegio</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="bi bi-people me-2"></i> <span>Usuarios</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="{{ route('colegioconfig.edit') }}" class="nav-link text-white {{ request()->routeIs('colegioconfig.*') ? 'active' : '' }}">
                        <i class="bi bi-building me-2"></i> Colegio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Usuarios
                    </a>
                </li>
                                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Reportes
                    </a>
                </li>

                @endif

                @if(session('current_role') === 'director')
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('user.index') }}" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('grado.index') }}" class="nav-link text-white {{ request()->routeIs('grado.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Grados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('materia.index') }}" class="nav-link text-white {{ request()->routeIs('materia.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Materias
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('maya.index') }}" class="nav-link text-white {{ request()->routeIs('maya.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Mayas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Reportes
                    </a>
                </li>

                @endif

                @if(session('current_role') === 'docente')
                {{-- Ejemplo para docente --}}

                <li class="nav-item">
                    <a href="{{ route('maya.index') }}" class="nav-link text-white {{ request()->routeIs('maya.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Mayas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('docente.dashboard') }}" class="nav-link text-white">
                        <i class="bi bi-journal-text me-2"></i> Mis Cursos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Reportes
                    </a>
                </li>
                @endif

                @if(session('current_role') === 'auxiliar')
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Reportes
                    </a>
                </li>
                @endif
                @if(session('current_role') === 'apoderado')
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Reportes
                    </a>
                </li>
                @endif
                @if(session('current_role') === 'estudiante')
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Reportes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('libreta.index') }}" class="nav-link text-white {{ request()->routeIs('libreta.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Libreta
                    </a>
                </li>
                @endif


            </ul>

            <div class="mt-auto text-center text-white-50 small">
                <div class="dropdown">
                        <button class="btn btn-danger dropdown-toggle w-100" type="button" id="dropdownCerrarSesion" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
                        </button>
                        <ul class="dropdown-menu w-100" aria-labelledby="dropdownCerrarSesion">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right"></i> Cerrar sesión principal
                                    </button>
                                </form>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout_sub') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-arrow-repeat"></i>Cambiar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                &copy; {{ date('Y') }} Gestión Escolar
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 overflow-auto" style="max-height: 100vh; padding: 1rem;">
            @yield('content')
        </div>
    </div>
    <style>
    .sidebar {
        transition: width 0.3s ease;
    }

    .sidebar.collapsed {
        width: 70px !important;
    }

    .sidebar.collapsed .nav-link span,
    .sidebar.collapsed h4,
    .sidebar.collapsed .accordion,
    .sidebar.collapsed p,
    .sidebar.collapsed .badge,
    .sidebar.collapsed .text-muted,
    .sidebar.collapsed .dropdown {
        display: none !important;
    }

    .toggle-btn {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
    }

    .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 5px;
    }
</style>
<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('collapsed');
    }
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/i18n/es.js"></script>
<script src="https://cdn.datatables.net/2.3.1/js/dataTables.js"></script>
@stack('js')
</body>
</html>
