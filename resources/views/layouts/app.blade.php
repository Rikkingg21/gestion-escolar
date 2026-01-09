<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestión Escolar')</title>
    <link rel="icon" href="{{ asset('storage/logo/logo-actual.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS (después de jQuery) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
    <style>
    /* Estilos adicionales para mejorar la visibilidad de las pestañas */
    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 500;
        border: 1px solid transparent;
        border-bottom: none;
    }

    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
        color: #0056b3;
    }

    .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        font-weight: 600;
    }

    .tab-content {
        background-color: #fff;
        border-radius: 0 0 0.375rem 0.375rem;
    }
    </style>
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --transition-speed: 0.3s;
        }

        body {
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            transition: width var(--transition-speed) ease;
            background-color: #212529;
            color: white;
            height: 100dvh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .accordion,
        .sidebar.collapsed .dropdown {
            display: none !important;
        }

        .sidebar.collapsed .toggle-btn {
            margin: 0 auto;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            margin-bottom: 3px;
            transition: all 0.2s;
            position: relative;
            padding: 10px 15px;
        }

        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            color: white;
            background-color: #0d6efd;
            font-weight: 500;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: #ffffff;
            border-radius: 0 3px 3px 0;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
            min-height: 100vh;
            padding: 1rem;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        .accordion-button {
            background-color: transparent;
            color: white;
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .accordion-button:not(.collapsed) {
            background-color: rgb(255, 252, 169);
            color: white;
            box-shadow: none;
        }

        .accordion-button::after {
            filter: invert(1);
        }

        .accordion-body {
            padding: 10px 15px;
            background-color: rgba(255, 252, 226, 0.2);
            font-size: 0.8rem;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .badge-role {
            font-size: 0.7rem;
        }

        /* Scrollbar personalizado para el sidebar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Estilo para el dropdown de cerrar sesión */
        .dropdown-menu {
            background-color: #343a40;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dropdown-item {
            color: rgba(255, 255, 255, 0.8);
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .mobile-overlay.active {
                display: block;
            }
        }

        /* =========================== */
        /* ESTILOS PARA IMPRESIÓN */
        /* =========================== */
        @media print {
            /* Ocultar elementos que no deben imprimirse */
            .sidebar,
            .mobile-overlay,
            .btn-primary.d-md-none,
            .toggle-btn,
            .nav-link, /* Ocultar enlaces de navegación */
            .dropdown, /* Ocultar dropdowns */
            .btn, /* Ocultar botones */
            .badge /* Ocultar badges */ {
                display: none !important;
            }

            /* Ajustar el contenido principal para ocupar todo el ancho */
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
            }

            /* Eliminar espacios y márgenes innecesarios en impresión */
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            /* Asegurar que el contenido sea legible en blanco y negro */
            * {
                color: black !important;
                background: transparent !important;
            }

            /* Mejorar la legibilidad del texto */
            .main-content * {
                font-size: 12pt !important;
                line-height: 1.4 !important;
            }

            /* Evitar que se corten elementos entre páginas */
            h1, h2, h3, h4, h5, h6 {
                page-break-after: avoid;
            }

            table, img, .card {
                page-break-inside: avoid;
            }

            /* Asegurar que los enlaces se muestren con su URL - EXCLUYENDO BOTONES Y NAVEGACIÓN */
            .main-content a[href]:not(.btn):not(.nav-link):not(.dropdown-item):after {
                content: " (" attr(href) ")";
                font-size: 10pt;
                color: #666 !important;
            }

            /* Ocultar elementos decorativos */
            .bg-primary, .bg-success, .bg-warning, .bg-danger,
            .text-primary, .text-success, .text-warning, .text-danger {
                color: black !important;
                background: transparent !important;
            }

            /* Ocultar completamente botones y elementos de navegación */
            .btn, .nav-link, .dropdown, .badge {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay para móvil -->
    <div class="mobile-overlay" onclick="toggleMobileSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar bg-dark text-white p-3 d-flex flex-column">
        <div class="d-flex flex-column mb-3">
            <!-- Botón en la parte superior -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="toggle-btn d-none d-md-block" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="toggle-btn d-md-none" onclick="toggleMobileSidebar()">
                    <i class="bi bi-x-lg"></i>
                </button>
                <!-- Espacio vacío para mantener la alineación -->
                <div class="d-none d-md-block" style="width: 40px;"></div>
            </div>

            <!-- Nombre del colegio centrado debajo -->
            <div class="text-center">
                <h4 class="text-success sidebar-text mb-0">{{ $colegio->nombre }}</h4>
            </div>
        </div>

        <hr class="bg-light">

        <!-- Información de sesiones -->
        <div class="accordion mb-3" id="accordionSesiones">
            <!-- Sesión principal -->
            <div class="accordion-item border-0 bg-transparent">
                <h2 class="accordion-header" id="headingPrincipal">
                    <button class="accordion-button collapsed py-2 small bg-transparent text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrincipal" aria-expanded="false" aria-controls="collapsePrincipal">
                        <i class="bi bi-person me-2"></i>
                        <span class="sidebar-text">Sesión principal</span>
                    </button>
                </h2>
                <div id="collapsePrincipal" class="accordion-collapse collapse" aria-labelledby="headingPrincipal" data-bs-parent="#accordionSesiones">
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
            <div class="accordion-item border-0 bg-transparent">
                <h2 class="accordion-header" id="headingSub">
                    <button class="accordion-button collapsed py-2 small bg-transparent text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSub" aria-expanded="false" aria-controls="collapseSub">
                        <i class="bi bi-person-check me-2"></i>
                        <span class="sidebar-text">Sesión actual</span>
                    </button>
                </h2>
                <div id="collapseSub" class="accordion-collapse collapse" aria-labelledby="headingSub" data-bs-parent="#accordionSesiones">
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
                            <div class="text-muted">No hay sesión activa.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3 text-center">
            <p class="mb-1 small sidebar-text">Bienvenido,
            <span class="badge bg-primary badge-role">
                {{ auth()->user()->nombre }}
            </span></p>
        </div>

        <!-- Módulos de navegación -->
        <ul class="nav nav-pills flex-column mb-3" id="sidebar-nav">
            @php
                $filteredModules = $sidebarModules->filter(function($module) {
                    return $module->nombre;
                });

                // Obtener la ruta actual para marcar el módulo activo
                $currentRoute = request()->route()->getName();
            @endphp

            @if($filteredModules->count() > 0)
                @foreach($filteredModules as $module)
                    @php
                        // Determinar si este módulo está activo
                        $isActive = false;
                        if (isset($module->custom_route)) {
                            // Comparar la ruta actual con la ruta del módulo
                            $isActive = request()->fullUrlIs($module->custom_route) ||
                                        (strpos(request()->url(), $module->custom_route) !== false);
                        }
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center {{ $isActive ? 'active' : '' }}"
                           href="{{ $module->custom_route }}"
                           data-bs-toggle="tooltip"
                           data-bs-placement="right"
                           title="{{ $module->nombre }}"
                           data-module-id="{{ $module->id }}">
                            <i class="{{ $module->custom_icon }} me-2"></i>
                            <span class="sidebar-text">{{ $module->nombre }}</span>
                            @if($module->has_special_route)
                                <small class="ms-1 opacity-75 sidebar-text">
                                    <i class="bi bi-star-fill"></i>
                                </small>
                            @endif
                        </a>
                    </li>
                @endforeach
            @else
                <li class="nav-item">
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No tienes módulos asignados
                    </div>
                </li>
            @endif
        </ul>

        <!-- Footer del sidebar -->
        <div class="sidebar-footer text-center text-white-50 small">
            <div class="dropdown mb-3">
                <button class="btn btn-danger dropdown-toggle w-100" type="button" id="dropdownCerrarSesion" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-box-arrow-left me-2"></i>
                    <span class="sidebar-text">Cerrar Sesión</span>
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
                                <i class="bi bi-arrow-repeat"></i> Cambiar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            <div class="sidebar-text">&copy; {{ date('Y') }} Gestión Escolar</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <!-- Botón para abrir sidebar en móvil -->
        <button class="btn btn-primary d-md-none mb-3" onclick="toggleMobileSidebar()">
            <i class="bi bi-list"></i> Menú
        </button>

        @yield('content')
    </div>

    <!-- SOLUCIÓN: Solo cargar Bootstrap una vez -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.1/js/dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Estado del sidebar
        let sidebarCollapsed = false;

        // Función para alternar el sidebar en desktop
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.getElementById('main-content');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            sidebarCollapsed = !sidebarCollapsed;

            // Guardar preferencia en localStorage
            localStorage.setItem('sidebarCollapsed', sidebarCollapsed);

            // Reinicializar tooltips después de cambiar el estado del sidebar
            setTimeout(initTooltips, 300);
        }

        // Función para alternar el sidebar en móvil
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');

            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');

            // Reinicializar tooltips después de cambiar el estado del sidebar
            setTimeout(initTooltips, 300);
        }

        // Marcar módulo activo
        function setActiveModule() {
            const currentUrl = window.location.href;
            const navLinks = document.querySelectorAll('#sidebar-nav .nav-link');

            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                // Comparar URLs para determinar si es el enlace activo
                if (href && (currentUrl.includes(href) || href === currentUrl)) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }

        // Inicializar tooltips de Bootstrap
        function initTooltips() {
            // Destruir tooltips existentes
            const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            existingTooltips.forEach(el => {
                const instance = bootstrap.Tooltip.getInstance(el);
                if (instance) {
                    instance.dispose();
                }
            });

            // Crear nuevos tooltips solo para elementos visibles
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                // Solo crear tooltip si el sidebar está colapsado o estamos en móvil
                const sidebar = document.querySelector('.sidebar');
                if (sidebar.classList.contains('collapsed') || window.innerWidth < 768) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                }
                return null;
            }).filter(Boolean);
        }

        // Cargar estado del sidebar desde localStorage
        function loadSidebarState() {
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                const sidebar = document.querySelector('.sidebar');
                const mainContent = document.getElementById('main-content');

                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                sidebarCollapsed = true;
            }
        }

        // Cerrar automáticamente acordeones cuando se abre otro
        function setupAccordionBehavior() {
            const accordionButtons = document.querySelectorAll('.accordion-button');

            accordionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-bs-target');

                    // Si este acordeón se está abriendo, cerrar los demás
                    if (!this.classList.contains('collapsed')) {
                        accordionButtons.forEach(otherButton => {
                            if (otherButton !== this && !otherButton.classList.contains('collapsed')) {
                                const otherTarget = document.querySelector(otherButton.getAttribute('data-bs-target'));
                                if (otherTarget) {
                                    const bsCollapse = bootstrap.Collapse.getInstance(otherTarget);
                                    if (bsCollapse) {
                                        bsCollapse.hide();
                                    }
                                }
                            }
                        });
                    }
                });
            });
        }

        // Inicializar cuando el documento esté listo
        document.addEventListener('DOMContentLoaded', function() {
            setActiveModule();
            initTooltips();
            loadSidebarState();
            setupAccordionBehavior();

            // Actualizar módulo activo cuando se hace clic en un enlace
            document.querySelectorAll('#sidebar-nav .nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    // Actualizar la clase activa
                    document.querySelectorAll('#sidebar-nav .nav-link').forEach(l => {
                        l.classList.remove('active');
                    });
                    this.classList.add('active');

                    // En móvil, cerrar el sidebar después de hacer clic
                    if (window.innerWidth < 768) {
                        toggleMobileSidebar();
                    }
                });
            });

            // Cerrar acordeones cuando se colapse el sidebar
            const sidebar = document.querySelector('.sidebar');
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        if (sidebar.classList.contains('collapsed')) {
                            // Cerrar todos los acordeones cuando el sidebar se colapsa
                            const collapses = document.querySelectorAll('.accordion-collapse.show');
                            collapses.forEach(collapse => {
                                const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                                if (bsCollapse) {
                                    bsCollapse.hide();
                                }
                            });
                        }
                    }
                });
            });

            observer.observe(sidebar, { attributes: true });
        });

        // Reinicializar tooltips cuando cambia el tamaño de la ventana
        window.addEventListener('resize', function() {
            initTooltips();
        });
    </script>
</body>
</html>
