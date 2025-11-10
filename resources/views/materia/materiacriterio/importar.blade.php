@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-file-earmark-excel me-2"></i> Importar Criterios desde Excel
        </h1>
        <a href="{{ route('materiacriterio.index', $materia->id ?? 0) }}" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left me-2"></i> Volver a Criterios
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Mostrar duplicados y errores específicos -->
    @if (session('duplicados') && count(session('duplicados')) > 0)
        <div class="alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Criterios Duplicados:</h6>
            <ul class="mb-0">
                @foreach (session('duplicados') as $duplicado)
                    <li>{{ $duplicado }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('errores') && count(session('errores')) > 0)
        <div class="alert alert-danger">
            <h6><i class="bi bi-x-circle me-2"></i>Errores de Importación:</h6>
            <ul class="mb-0">
                @foreach (session('errores') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="bi bi-upload me-2"></i> Cargar Archivo Excel
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('importar.criterio') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        <div class="form-group">
                            <label for="archivo_excel">Archivo Excel *</label>
                            <input type="file" name="archivo_excel" id="archivo_excel" class="form-control-file" accept=".xlsx,.xls" required>
                            <small class="form-text text-muted">Formatos permitidos: .xlsx, .xls (Tamaño máximo: 2MB)</small>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Formato requerido:</h6>
                            <ul class="mb-0">
                                <li><strong>Columna A:</strong> Materia (debe existir en el sistema)</li>
                                <li><strong>Columna B:</strong> Competencia (debe existir en el sistema)</li>
                                <li><strong>Columna C:</strong> Nombre del criterio (obligatorio)</li>
                                <li><strong>Columna D:</strong> Descripción (opcional)</li>
                                <li><strong>Columna E:</strong> Grado (debe existir en el sistema)</li>
                                <li><strong>Columna F:</strong> Sección (debe existir en el sistema)</li>
                                <li><strong>Columna G:</strong> Nivel (debe existir en el sistema)</li>
                                <li><strong>Columna H:</strong> Año (obligatorio)</li>
                                <li><strong>Columna I:</strong> Bimestre (obligatorio)</li>
                            </ul>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success" id="btn-importar">
                                <i class="bi bi-upload me-2"></i> Importar Criterios
                            </button>
                            <a href="{{ route('materiacriterio.index', $materia->id ?? 0) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="bi bi-download me-2"></i> Descargar Plantilla
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-file-earmark-excel display-4 text-info mb-3"></i>
                        <h5>Plantilla de Criterios</h5>
                        <p class="text-muted">Descarga la plantilla Excel con el formato correcto para importar criterios.</p>

                        <a href="{{ asset('templates/plantilla_materia_criterio.xlsx') }}" class="btn btn-info btn-block" download>
                            <i class="bi bi-download me-2"></i> Descargar Plantilla
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3 bg-warning">
                    <h6 class="m-0 font-weight-bold">
                        <i class="bi bi-info-circle me-2"></i> Instrucciones
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Descarga la plantilla primero
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Completa los datos según el formato
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            La materia debe existir en el sistema
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            La competencia debe existir en la materia
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            El grado debe existir en el sistema
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Sube el archivo completado
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Validación básica del formulario
    $('#importForm').on('submit', function() {
        var fileInput = $('#archivo_excel');

        if (!fileInput.val()) {
            alert('Por favor selecciona un archivo Excel.');
            return false;
        }

        // Verificar extensión del archivo
        var fileName = fileInput.val();
        var extension = fileName.split('.').pop().toLowerCase();

        if (extension !== 'xlsx' && extension !== 'xls') {
            alert('Por favor selecciona un archivo Excel válido (.xlsx o .xls).');
            return false;
        }

        return confirm('¿Estás seguro de que deseas importar los criterios?');
    });
});
</script>
@endsection
