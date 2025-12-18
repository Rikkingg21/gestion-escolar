<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sesión Expirada - {{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow">
                    <div class="card-header bg-danger text-white">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-exclamation fs-4 me-2"></i>
                            <h5 class="mb-0">Sesión Expirada</h5>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-clock-history text-danger display-1"></i>
                        </div>

                        <h4 class="text-center mb-3">¡Tu sesión ha expirado!</h4>

                        <div class="alert alert-warning mb-4">
                            <div class="d-flex">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <div>
                                    <p class="mb-1">Por seguridad, tu sesión se ha cerrado automáticamente.</p>
                                    <p class="mb-0">No te preocupes, tus datos están seguros.</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                            </a>

                            <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-house-door me-2"></i> Volver al Inicio
                            </a>
                        </div>

                        <div class="text-center text-muted small mt-4">
                            <p class="mb-1">
                                <i class="bi bi-info-circle me-1"></i>
                                {{ config('app.name', 'Laravel') }} • {{ date('Y') }}
                            </p>
                            <p class="mb-0">
                                Si el problema persiste, contacta al administrador.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información adicional para desarrollo -->
                @if(app()->environment('local'))
                <div class="card border-info mt-3">
                    <div class="card-header bg-info text-white py-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-bug me-2"></i>
                            <small>Información para desarrollo</small>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Error:</strong> 419 - Page Expired</li>
                            <li><strong>Sesión:</strong> {{ config('session.lifetime') }} minutos</li>
                            <li><strong>Controlador:</strong> CSRF Token Mismatch</li>
                            <li><strong>URL:</strong> {{ request()->fullUrl() }}</li>
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Redirección automática opcional después de 60 segundos
            let countdown = 60;
            const loginUrl = "{{ route('login') }}";

        });
    </script>
</body>
</html>
