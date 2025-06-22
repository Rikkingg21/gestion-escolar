<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestión Escolar')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.css" />
    <style>
        body {
            background: #f8fafc;
        }
        .sidebar {
            min-height: 100vh;
            background: #0d6efd;
            color: #fff;
            padding: 2rem 1rem 1rem 1rem;
        }
        .sidebar .dropdown-menu {
            background: #fff;
            color: #212529;
        }
        .sidebar .dropdown-item {
            color: #212529;
        }
        .sidebar .dropdown-item.text-danger {
            color: #dc3545 !important;
        }
        .sidebar .session-info {
            font-size: 0.95rem;
        }
        .sidebar .session-info strong {
            color: #ffc107;
        }
        .sidebar .logo {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 2rem;
            letter-spacing: 1px;
        }
        .main-content {
            padding: 2rem 2rem 2rem 2rem;
        }
        @media (max-width: 767px) {
            .sidebar {
                min-height: auto;
                padding: 1rem;
            }
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Panel lateral izquierdo -->
        <nav class="col-md-3 col-lg-2 sidebar d-flex flex-column">
            <div class="logo mb-4">
                <i class="bi bi-mortarboard-fill"></i> Gestión Escolar
            </div>
            <div class="session-info mb-4">
                <div class="mb-2">
                    <strong>Sesión principal:</strong><br>
                    @if(session('sessionmain'))
                        {{ session('sessionmain')->nombre_usuario ?? 'No disponible' }}<br>
                        <span class="text-white-50">ID: {{ session('sessionmain')->id ?? '-' }}</span>
                    @else
                        <span class="text-white-50">No hay sesión principal.</span>
                    @endif
                </div>
                <div>
                    <strong>Sesión sub (actual):</strong><br>
                    @if(auth()->check())
                        {{ auth()->user()->nombre_usuario ?? 'No disponible' }}<br>
                        <span class="text-white-50">ID: {{ auth()->user()->id ?? '-' }}</span>
                        @if(session('current_role'))
                            <br><span class="badge bg-warning text-dark">Rol: {{ session('current_role') }}</span>
                        @endif
                    @else
                        <span class="text-white-50">No hay sesión sub activa.</span>
                    @endif
                </div>
            </div>
            <!-- Botón desplegable para cerrar sesión -->
            <div class="mb-4">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle w-100" type="button" id="dropdownCerrarSesion" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> Opciones de sesión
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
                                    <i class="bi bi-arrow-repeat"></i> Cerrar sesión sub (cambiar sesión)
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Puedes agregar más opciones de menú aquí -->
            <div class="mt-auto text-center text-white-50 small">
                &copy; {{ date('Y') }} Gestión Escolar
            </div>
        </nav>
        <!-- Contenido principal -->
        <main class="col-md-9 col-lg-10 main-content">
            @yield('content')
        </main>
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
