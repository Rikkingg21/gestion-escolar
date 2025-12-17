<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de gestión escolar - Iniciar sesión">
    @php
        $colegio = \App\Models\Colegio::configuracion();
    @endphp
    <title>Login - {{ $colegio->nombre ?? 'Sistema Escolar' }}</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100 py-4">
        <div class="col-12 col-md-6 col-lg-4">
            <!-- Encabezado con información del colegio -->
            <div class="text-center mb-4">
                @if($colegio->logo_path)
                    <img src="{{ Storage::url($colegio->logo_path) }}"
                         alt="Logo de {{ $colegio->nombre }}"
                         class="img-fluid mb-3"
                         style="max-height: 80px;">
                @endif
                <h1 class="h4 mb-2">{{ $colegio->nombre ?? 'Sistema Escolar' }}</h1>
                @if($colegio->director_actual)
                    <p class="text-muted small mb-0">Director: {{ $colegio->director_actual }}</p>
                @endif
                <p class="text-muted small">Sistema de Gestión Escolar</p>
            </div>

            <!-- Formulario de login -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h2 class="h5 mb-0">Iniciar Sesión</h2>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-3">
                            <strong>Error:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
                            <input type="text"
                                   class="form-control"
                                   id="nombre_usuario"
                                   name="nombre_usuario"
                                   required
                                   autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Ingresar
                        </button>
                    </form>

                    <!-- Información de contacto -->
                    <div class="mt-4 text-center">
                        <p class="text-muted small mb-2">
                            Si tiene problemas para acceder, contacte al administrador
                        </p>
                        @if($colegio->email || $colegio->telefono)
                            <div class="small">
                                @if($colegio->email)
                                    <div>Email: {{ $colegio->email }}</div>
                                @endif
                                @if($colegio->telefono)
                                    <div>Teléfono: {{ $colegio->telefono }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer text-center text-muted small">
                    &copy; {{ date('Y') }} {{ $colegio->nombre ?? 'Sistema Escolar' }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
