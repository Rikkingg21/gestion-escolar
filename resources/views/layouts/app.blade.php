<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestión Escolar')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.css" />
</head>
<body class="min-vh-100">

    <div class="d-flex min-vh-100" style="height: 100vh;">

        <!-- Sidebar -->
        <div class="sidebar bg-dark text-white p-3 d-flex flex-column h-100" style="width: 250px;">
            <div class="text-center mb-4"><!--encabezado-->
                <h4 class="text-success">nombre colegio</h4>
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
                    <a href="{{ route('colegioconfig.edit') }}" class="nav-link text-white {{ request()->routeIs('colegioconfig.*') ? 'active' : '' }}">
                        <i class="bi bi-building me-2"></i> Colegio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Usuarios
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
                    <a href="" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('grado.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Grados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('materia.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Materias
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('maya.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Mayas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('estudiante.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white {{ request()->routeIs('docente.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Docentes
                    </a>
                </li>
                @endif

                @if(session('current_role') === 'docente')
                {{-- Ejemplo para docente --}}

                <li class="nav-item">
                    <a href="" class="nav-link text-white">
                        <i class="bi bi-journal-text me-2"></i> Mi Maya
                    </a>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link text-white">
                        <i class="bi bi-journal-text me-2"></i> Mis Cursos
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
        <div class="flex-grow-1">
            @yield('content')
        </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/i18n/es.js"></script>
<script src="https://cdn.datatables.net/2.3.1/js/dataTables.js"></script>
@stack('js')
</body>
</html>
