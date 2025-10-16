<ul class="nav nav-pills flex-column"><!--contenido-->
                @if(session('current_role') === 'admin')
                {{-- Solo para admin --}}
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('maya.index') }}" class="nav-link text-white {{ request()->routeIs('maya.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-check me-2"></i> <span>Mayas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('colegioconfig.edit') }}" class="nav-link text-white {{ request()->routeIs('colegioconfig.*') ? 'active' : '' }}">
                        <i class="bi bi-building me-2"></i> <span>Colegio</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('user.index') }}" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> <span>Usuarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone me-2"></i> <span>Reportes</span>
                    </a>
                </li>

                @endif

                @if(session('current_role') === 'director')
                <li class="nav-item">
                    <a href="{{ route('director.dashboard') }}" class="nav-link text-white {{ request()->routeIs('director.*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('user.index') }}" class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> <span>Usuarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('grado.index') }}" class="nav-link text-white {{ request()->routeIs('grado.*') ? 'active' : '' }}">
                        <i class="bi bi-person-rolodex me-2"></i> <span>Grados</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('materia.index') }}" class="nav-link text-white {{ request()->routeIs('materia.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i> <span>Materias</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('conducta.index') }}" class="nav-link text-white {{ request()->routeIs('conducta.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> <span>Conducta</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('maya.index') }}" class="nav-link text-white {{ request()->routeIs('maya.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-check me-2"></i> <span>Mayas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone me-2"></i> <span>Reportes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('asistencia.index') }}" class="nav-link text-white {{ request()->routeIs('asistencia.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-check me-2"></i> <span>Asistencia</span>
                    </a>
                </li>
                @endif

                @if(session('current_role') === 'docente')

                <li class="nav-item">
                    <a href="{{ route('docente.dashboard') }}" class="nav-link text-white {{ request()->routeIs('docente.*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('maya.index') }}" class="nav-link text-white {{ request()->routeIs('maya.*') ? 'active' : '' }}">
                         <i class="bi bi-clipboard2-check me-2"></i> <span>Mayas</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> <span>Reportes</span>
                    </a>
                </li>
                @endif

                @if(session('current_role') === 'auxiliar')
                <li class="nav-item">
                    <a href="{{ route('auxiliar.dashboard') }}" class="nav-link text-white {{ request()->routeIs('auxiliar.*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone me-2"></i> <span>Reportes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('asistencia.index') }}" class="nav-link text-white {{ request()->routeIs('asistencia.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-check me-2"></i> <span>Asistencia</span>
                    </a>
                </li>
                @endif
                @if(session('current_role') === 'apoderado')
                <li class="nav-item">
                    <a href="{{ route('apoderado.dashboard') }}" class="nav-link text-white {{ request()->routeIs('apoderado.*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reporte.index') }}" class="nav-link text-white {{ request()->routeIs('reporte.*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone me-2"></i> <span>Reportes</span>
                    </a>
                </li>
                @endif
                @if(session('current_role') === 'estudiante')
                <li class="nav-item">
                    <a href="{{ route('estudiante.dashboard') }}" class="nav-link text-white {{ request()->routeIs('estudiante.*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('libreta.index', ['anio' => date('Y'), 'bimestre' => 1]) }}"
                    class="nav-link text-white {{ request()->routeIs('libreta.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-bookmark me-2"></i> <span>Libreta</span>
                    </a>
                </li>
                @endif


            </ul>
