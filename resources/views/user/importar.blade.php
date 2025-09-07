@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-file-earmark-excel me-2"></i> Importar Usuarios desde Excel
                    </h1>
                    <a href="{{ route('user.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="row">
                            <!-- Importar Apoderados -->
                            <div class="col-md-6 mb-4 mb-md-0">
                                <div class="import-section h-100">
                                    <h5 class="mb-3">Importar Apoderados</h5>
                                    <p>Formato requerido para apoderados:</p>
                                    <ul>
                                        <li><strong>A1:</strong> DNI</li>
                                        <li><strong>B1:</strong> Apellido Paterno</li>
                                        <li><strong>C1:</strong> Apellido Materno</li>
                                        <li><strong>D1:</strong> Nombres</li>
                                        <li><strong>E1:</strong> Teléfono</li>
                                        <li><strong>F1:</strong> Parentesco (padre, madre, tutor)</li>
                                    </ul>
                                    <a href="{{ asset('templates/plantilla_apoderados.xlsx') }}" class="template-download" download>
                                        <i class="bi bi-download me-1"></i> Descargar plantilla
                                    </a>
                                    <form id="importApoderadosForm" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="apoderadosFile" class="form-label">Seleccionar archivo Excel</label>
                                            <input class="form-control" type="file" id="apoderadosFile" name="file" accept=".xlsx,.xls" required>
                                        </div>
                                        <button type="button" class="btn btn-success" id="importApoderadosBtn">
                                            <i class="bi bi-upload me-1"></i> Importar Apoderados
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Importar Estudiantes -->
                            <div class="col-md-6">
                                <div class="import-section h-100">
                                    <h5 class="mb-3">Importar Estudiantes</h5>
                                    <p>Formato requerido para estudiantes:</p>
                                    <ul>
                                        <li><strong>A1:</strong> DNI del estudiante</li>
                                        <li><strong>B1:</strong> Apellido Paterno</li>
                                        <li><strong>C1:</strong> Apellido Materno</li>
                                        <li><strong>D1:</strong> Nombres</li>
                                        <li><strong>E1:</strong> Fecha de nacimiento (dd/mm/yyyy)</li>
                                        <li><strong>F1:</strong> DNI del apoderado</li>
                                        <li><strong>G1:</strong> Grado (Solo numeros 1 al 6)</li>
                                        <li><strong>H1:</strong> Sección (A, B, C...)</li>
                                        <li><strong>I1:</strong> Nivel (Primaria o Secundaria)</li>
                                    </ul>
                                    <a href="{{ asset('templates/plantilla_estudiantes.xlsx') }}" class="template-download" download>
                                        <i class="bi bi-download me-1"></i> Descargar plantilla
                                    </a>
                                    <form id="importEstudiantesForm" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="estudiantesFile" class="form-label">Seleccionar archivo Excel</label>
                                            <input class="form-control" type="file" id="estudiantesFile" name="file" accept=".xlsx,.xls" required>
                                        </div>
                                        <button type="button" class="btn btn-success" id="importEstudiantesBtn">
                                            <i class="bi bi-upload me-1"></i> Importar Estudiantes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resultados de importación -->
                <div class="card shadow mt-4 d-none" id="resultCard">
                    <div class="card-header">
                        <h5 class="m-0 font-weight-bold text-primary">Resultados de la importación</h5>
                    </div>
                    <div class="card-body" id="importResults">
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
$(document).ready(function() {
    // Descargar plantilla para apoderados
    $('#downloadApoderadosTemplate').click(function(e) {
        e.preventDefault();
        // Crear un libro de Excel simple con JavaScript
        const headers = ['DNI', 'Apellido Paterno', 'Apellido Materno', 'Nombres', 'Teléfono', 'Parentesco'];
        const exampleData = ['12345678', 'Perez', 'Gomez', 'Juan Carlos', '987654321', 'Padre'];

        let csvContent = headers.join(',') + '\n';
        csvContent += exampleData.join(',') + '\n';

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'plantilla_apoderados.csv';
        link.click();
    });

    // Descargar plantilla para estudiantes
    $('#downloadEstudiantesTemplate').click(function(e) {
        e.preventDefault();
        const headers = ['DNI Estudiante', 'Apellido Paterno', 'Apellido Materno', 'Nombres', 'Fecha Nacimiento (dd/mm/yyyy)', 'DNI Apoderado', 'Grado', 'Sección', 'Nivel'];
        const exampleData = ['87654321', 'Lopez', 'Martinez', 'Maria Elena', '15/05/2010', '12345678', '4to', 'A', 'Primaria'];

        let csvContent = headers.join(',') + '\n';
        csvContent += exampleData.join(',') + '\n';

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'plantilla_estudiantes.csv';
        link.click();
    });

    // Importar Apoderados
    $('#importApoderadosBtn').click(function() {
        var fileInput = $('#apoderadosFile')[0];
        if (fileInput.files.length === 0) {
            alert('Por favor, seleccione un archivo Excel');
            return;
        }

        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        // Mostrar carga
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importando...');
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("importar.apoderados") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                mostrarResultados(response, 'Apoderados');
                $('#importApoderadosBtn').html('<i class="bi bi-upload me-1"></i> Importar Apoderados');
                $('#importApoderadosBtn').prop('disabled', false);
            },
            error: function(xhr) {
                alert('Error al importar: ' + xhr.responseText);
                $('#importApoderadosBtn').html('<i class="bi bi-upload me-1"></i> Importar Apoderados');
                $('#importApoderadosBtn').prop('disabled', false);
            }
        });
    });

    // Importar Estudiantes
    $('#importEstudiantesBtn').click(function() {
        var fileInput = $('#estudiantesFile')[0];
        if (fileInput.files.length === 0) {
            alert('Por favor, seleccione un archivo Excel');
            return;
        }

        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        // Mostrar carga
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importando...');
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("importar.estudiantes") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                mostrarResultados(response, 'Estudiantes');
                $('#importEstudiantesBtn').html('<i class="bi bi-upload me-1"></i> Importar Estudiantes');
                $('#importEstudiantesBtn').prop('disabled', false);
            },
            error: function(xhr) {
                alert('Error al importar: ' + xhr.responseText);
                $('#importEstudiantesBtn').html('<i class="bi bi-upload me-1"></i> Importar Estudiantes');
                $('#importEstudiantesBtn').prop('disabled', false);
            }
        });
    });

    // Función para mostrar resultados de importación
    function mostrarResultados(data, tipo) {
        var html = '<div class="alert alert-success">';
        html += '<h6>Importación de ' + tipo + ' completada</h6>';
        html += '<p>Registros exitosos: ' + data.exitosos + '</p>';

        if (data.errores && data.errores.length > 0) {
            html += '<p>Errores encontrados:</p>';
            html += '<ul>';
            data.errores.forEach(function(error) {
                html += '<li class="text-danger">' + error + '</li>';
            });
            html += '</ul>';
        }

        html += '</div>';

        $('#importResults').html(html);
        $('#resultCard').removeClass('d-none');

        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#resultCard').offset().top
        }, 1000);
    }
});
</script>
@endsection
