<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Página No Encontrada - {{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle fs-4 me-2"></i>
                            <h5 class="mb-0">Página No Encontrada</h5>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-emoji-dizzy text-warning display-1"></i>
                            <h1 class="display-1 fw-bold text-muted mt-3">404</h1>
                        </div>

                        <h4 class="text-center mb-4">¡Ups! Página no encontrada</h4>

                        <div class="alert alert-info mb-4">
                            <div class="d-flex">
                                <i class="bi bi-info-circle me-2"></i>
                                <div>
                                    <p class="mb-1">La página que estás buscando no existe o ha sido movida.</p>
                                    <p class="mb-0">Verifica la URL o utiliza los enlaces a continuación.</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            <a href="javascript:history.go(-1)" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i> Volver Atrás
                            </a>

                            <a href="{{ url('/') }}" class="btn btn-success">
                                <i class="bi bi-house-door me-2"></i> Ir al Inicio
                            </a>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <a href="javascript:history.go(-1)" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i> Retroceder
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button onclick="location.reload()" class="btn btn-outline-info w-100">
                                    <i class="bi bi-arrow-clockwise me-2"></i> Recargar Página
                                </button>
                            </div>
                        </div>

                        <div class="text-center text-muted small">
                            <p class="mb-1">
                                <i class="bi bi-shield-check me-1"></i>
                                {{ config('app.name', 'Laravel') }} • {{ date('Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar búsqueda
            const searchButton = document.getElementById('searchButton');
            const searchInput = document.getElementById('searchInput');

            searchButton.addEventListener('click', function() {
                performSearch();
            });

            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });

            function performSearch() {
                const query = searchInput.value.trim();
                if (query) {
                    // Puedes cambiar esta URL por tu endpoint de búsqueda
                    window.location.href = "{{ url('/') }}?s=" + encodeURIComponent(query);
                }
            }

            // Mostrar la URL truncada si es muy larga
            const urlElement = document.querySelector('code');
            if (urlElement) {
                const url = urlElement.textContent;
                if (url.length > 80) {
                    urlElement.textContent = url.substring(0, 80) + '...';
                    urlElement.title = url;
                }
            }
        });
    </script>
</body>
</html>
