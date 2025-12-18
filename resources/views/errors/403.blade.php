<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Acceso Denegado</div>

                    <div class="card-body">
                        <h4>¡No tienes permiso para acceder a esta página!</h4>
                        <p>Por favor, contacta con el administrador si crees que esto es un error.</p>

                        <div class="mt-4">
                            <a href="{{ url()->previous() }}" class="btn btn-primary">
                                Volver Atrás
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
