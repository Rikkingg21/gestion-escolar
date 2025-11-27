@extends('layouts.app')
@section('title', 'Importar Usuarios desde Excel')
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
                                        <i class="bi bi-upload me-1"></i> Validar y Importar Apoderados
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
                                        <i class="bi bi-upload me-1"></i> Validar y Importar Estudiantes
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
                    <h5 class="m-0 font-weight-bold text-primary">Resultados de la validación</h5>
                </div>
                <div class="card-body" id="importResults">
                </div>
            </div>

            <!-- Barra de progreso -->
            <div class="card shadow mt-4 d-none" id="progressCard">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i>Progreso de Importación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="progress-container">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="progressText">Procesando registros...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar" style="width: 0%"
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted" id="progressDetails">Iniciando importación...</small>
                        </div>
                        <div class="mt-3 text-center">
                            <button type="button" class="btn btn-secondary btn-sm d-none" id="cancelImportProgressBtn">
                                <i class="bi bi-x-circle me-1"></i> Cancelar Importación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let registrosPendientes = [];
    let registrosEstudiantesPendientes = [];
    let sessionKey = '';
    let sessionKeyEstudiantes = '';
    let importInProgress = false;

    // Importar Apoderados - Primera fase: Validación
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
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Validando...');
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("importar.validar-apoderados") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    mostrarResultadosValidacionApoderados(response);
                    registrosPendientes = response.registros_validos;
                    sessionKey = response.session_key;
                } else {
                    alert('Error: ' + response.error);
                }
                $('#importApoderadosBtn').html('<i class="bi bi-upload me-1"></i> Validar y Importar Apoderados');
                $('#importApoderadosBtn').prop('disabled', false);
            },
            error: function(xhr) {
                let errorMessage = 'Error al validar el archivo';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage += ': ' + xhr.responseJSON.error;
                } else if (xhr.status === 413) {
                    errorMessage = 'El archivo es demasiado grande. El tamaño máximo permitido es 10MB.';
                } else if (xhr.responseText) {
                    errorMessage += ': ' + xhr.responseText;
                }
                alert(errorMessage);
                $('#importApoderadosBtn').html('<i class="bi bi-upload me-1"></i> Validar y Importar Apoderados');
                $('#importApoderadosBtn').prop('disabled', false);
            }
        });
    });

    // Importar Estudiantes - Primera fase: Validación
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
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Validando...');
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("importar.validar-estudiantes") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    mostrarResultadosValidacionEstudiantes(response);
                    registrosEstudiantesPendientes = response.registros_validos;
                    sessionKeyEstudiantes = response.session_key;
                } else {
                    alert('Error: ' + response.error);
                }
                $('#importEstudiantesBtn').html('<i class="bi bi-upload me-1"></i> Validar y Importar Estudiantes');
                $('#importEstudiantesBtn').prop('disabled', false);
            },
            error: function(xhr) {
                let errorMessage = 'Error al validar el archivo';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage += ': ' + xhr.responseJSON.error;
                } else if (xhr.status === 413) {
                    errorMessage = 'El archivo es demasiado grande. El tamaño máximo permitido es 10MB.';
                } else if (xhr.responseText) {
                    errorMessage += ': ' + xhr.responseText;
                }
                alert(errorMessage);
                $('#importEstudiantesBtn').html('<i class="bi bi-upload me-1"></i> Validar y Importar Estudiantes');
                $('#importEstudiantesBtn').prop('disabled', false);
            }
        });
    });

    // Función para mostrar resultados de validación de apoderados
    function mostrarResultadosValidacionApoderados(data) {
        let html = '<div class="validation-results">';

        // Resumen
        html += '<div class="alert alert-info">';
        html += '<h6>Resultados de la Validación - Apoderados</h6>';
        html += '<p><strong>Total de registros procesados:</strong> ' + data.total_registros + '</p>';
        html += '<p><strong>Registros válidos:</strong> <span class="text-success">' + data.total_validos + '</span></p>';
        html += '<p><strong>Errores encontrados:</strong> <span class="text-danger">' + data.total_errores + '</span></p>';
        html += '</div>';

        // Mostrar errores si existen
        if (data.errores && data.errores.length > 0) {
            html += '<div class="alert alert-warning">';
            html += '<h6>Errores encontrados:</h6>';
            html += '<div style="max-height: 200px; overflow-y: auto;">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead><tr><th>Fila</th><th>DNI</th><th>Error</th></tr></thead>';
            html += '<tbody>';
            data.errores.forEach(function(error) {
                html += '<tr>';
                html += '<td>' + error.fila + '</td>';
                html += '<td>' + error.dni + '</td>';
                html += '<td class="text-danger">' + error.error + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '</div></div>';
        }

        // Mostrar registros válidos
        if (data.registros_validos && data.registros_validos.length > 0) {
            html += '<div class="alert alert-success">';
            html += '<h6>Registros listos para importar (' + data.total_validos + '):</h6>';
            html += '<div style="max-height: 300px; overflow-y: auto;">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead><tr><th>Fila</th><th>DNI</th><th>Apellidos y Nombres</th><th>Parentesco</th><th>Teléfono</th></tr></thead>';
            html += '<tbody>';
            data.registros_validos.forEach(function(registro) {
                html += '<tr>';
                html += '<td>' + registro.fila + '</td>';
                html += '<td>' + registro.dni + '</td>';
                html += '<td>' + registro.apellido_paterno + ' ' + registro.apellido_materno + ', ' + registro.nombre + '</td>';
                html += '<td>' + registro.parentesco + '</td>';
                html += '<td>' + (registro.telefono || 'N/A') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '</div>';

            // Botón de confirmación
            html += '<div class="mt-3">';
            html += '<button type="button" class="btn btn-success" id="confirmImportApoderadosBtn">';
            html += '<i class="bi bi-check-circle me-1"></i> Confirmar e Importar ' + data.total_validos + ' Apoderados';
            html += '</button>';
            html += '<button type="button" class="btn btn-secondary ms-2" id="cancelImportApoderadosBtn">';
            html += '<i class="bi bi-x-circle me-1"></i> Cancelar';
            html += '</button>';
            html += '</div>';
            html += '</div>';
        } else {
            html += '<div class="alert alert-warning">';
            html += '<p>No hay registros válidos para importar.</p>';
            html += '</div>';
        }

        html += '</div>';

        $('#importResults').html(html);
        $('#resultCard').removeClass('d-none');

        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#resultCard').offset().top
        }, 1000);

        // Evento para el botón de confirmación
        $('#confirmImportApoderadosBtn').click(confirmarImportacionApoderados);
        $('#cancelImportApoderadosBtn').click(function() {
            $('#resultCard').addClass('d-none');
            registrosPendientes = [];
            $('#apoderadosFile').val('');
        });
    }

    // Función para mostrar resultados de validación de estudiantes
    function mostrarResultadosValidacionEstudiantes(data) {
        let html = '<div class="validation-results">';

        // Resumen
        html += '<div class="alert alert-info">';
        html += '<h6>Resultados de la Validación - Estudiantes</h6>';
        html += '<p><strong>Total de registros procesados:</strong> ' + data.total_registros + '</p>';
        html += '<p><strong>Registros válidos:</strong> <span class="text-success">' + data.total_validos + '</span></p>';
        html += '<p><strong>Errores encontrados:</strong> <span class="text-danger">' + data.total_errores + '</span></p>';
        html += '</div>';

        // Mostrar errores si existen
        if (data.errores && data.errores.length > 0) {
            html += '<div class="alert alert-warning">';
            html += '<h6>Errores encontrados:</h6>';
            html += '<div style="max-height: 200px; overflow-y: auto;">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead><tr><th>Fila</th><th>DNI Estudiante</th><th>Error</th></tr></thead>';
            html += '<tbody>';
            data.errores.forEach(function(error) {
                html += '<tr>';
                html += '<td>' + error.fila + '</td>';
                html += '<td>' + error.dni_estudiante + '</td>';
                html += '<td class="text-danger">' + error.error + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '</div></div>';
        }

        // Mostrar registros válidos
        if (data.registros_validos && data.registros_validos.length > 0) {
            html += '<div class="alert alert-success">';
            html += '<h6>Registros listos para importar (' + data.total_validos + '):</h6>';
            html += '<div style="max-height: 300px; overflow-y: auto;">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead><tr><th>Fila</th><th>DNI Estudiante</th><th>Estudiante</th><th>Grado/Sección</th><th>Apoderado</th><th>DNI Apoderado</th></tr></thead>';
            html += '<tbody>';
            data.registros_validos.forEach(function(registro) {
                html += '<tr>';
                html += '<td>' + registro.fila + '</td>';
                html += '<td>' + registro.dni_estudiante + '</td>';
                html += '<td>' + registro.apellido_paterno + ' ' + (registro.apellido_materno || '') + ', ' + registro.nombre + '</td>';
                html += '<td>' + registro.grado + ' ' + registro.seccion + ' - ' + registro.nivel + '</td>';
                html += '<td>' + registro.apoderado_nombre + '</td>';
                html += '<td>' + registro.dni_apoderado + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '</div>';

            // Botón de confirmación
            html += '<div class="mt-3">';
            html += '<button type="button" class="btn btn-success" id="confirmImportEstudiantesBtn">';
            html += '<i class="bi bi-check-circle me-1"></i> Confirmar e Importar ' + data.total_validos + ' Estudiantes';
            html += '</button>';
            html += '<button type="button" class="btn btn-secondary ms-2" id="cancelImportEstudiantesBtn">';
            html += '<i class="bi bi-x-circle me-1"></i> Cancelar';
            html += '</button>';
            html += '</div>';
            html += '</div>';
        } else {
            html += '<div class="alert alert-warning">';
            html += '<p>No hay registros válidos para importar.</p>';
            html += '</div>';
        }

        html += '</div>';

        $('#importResults').html(html);
        $('#resultCard').removeClass('d-none');

        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#resultCard').offset().top
        }, 1000);

        // Evento para el botón de confirmación
        $('#confirmImportEstudiantesBtn').click(confirmarImportacionEstudiantes);
        $('#cancelImportEstudiantesBtn').click(function() {
            $('#resultCard').addClass('d-none');
            registrosEstudiantesPendientes = [];
            $('#estudiantesFile').val('');
        });
    }

    // Función para confirmar la importación de apoderados
    function confirmarImportacionApoderados() {
        if (registrosPendientes.length === 0) {
            alert('No hay registros para importar');
            return;
        }

        if (importInProgress) {
            alert('Ya hay una importación en progreso');
            return;
        }

        importInProgress = true;
        const totalRegistros = registrosPendientes.length;

        // Ocultar resultados de validación y mostrar barra de progreso
        $('#resultCard').addClass('d-none');
        $('#progressCard').removeClass('d-none');

        // Actualizar barra de progreso con la cantidad real de registros
        actualizarProgreso(0, totalRegistros, 'Iniciando importación de apoderados...');

        // Usar FormData para enviar los datos
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('registros', JSON.stringify(registrosPendientes));

        $.ajax({
            url: '{{ route("importar.apoderados") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                // Simular progreso basado en la cantidad real de registros
                var progress = 0;
                var totalSteps = Math.min(totalRegistros, 100);
                var progressIncrement = totalSteps > 0 ? Math.floor(totalRegistros / totalSteps) : 1;
                var currentStep = 0;

                var interval = setInterval(function() {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        progress = Math.min((currentStep / totalSteps) * 100, 90);
                        var registrosSimulados = Math.min(Math.floor((currentStep / totalSteps) * totalRegistros), totalRegistros);
                        actualizarProgreso(registrosSimulados, totalRegistros, 'Procesando apoderados...');
                    }
                }, 300);

                xhr.addEventListener('loadend', function() {
                    clearInterval(interval);
                });

                return xhr;
            },
            success: function(response) {
                // Mostrar 100% de progreso con la cantidad real
                actualizarProgreso(totalRegistros, totalRegistros, 'Importación completada!');
                setTimeout(function() {
                    mostrarResultadosFinales(response, 'apoderados');
                    registrosPendientes = [];
                    $('#apoderadosFile').val('');
                    importInProgress = false;
                    $('#progressCard').addClass('d-none');
                }, 1000);
            },
            error: function(xhr) {
                let errorMessage = 'Error al importar los registros';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage += ': ' + xhr.responseJSON.message;
                } else if (xhr.status === 419) {
                    errorMessage = 'Error de autenticación CSRF. Por favor, recarga la página e intenta nuevamente.';
                } else if (xhr.responseText) {
                    errorMessage += ': ' + xhr.responseText;
                }

                actualizarProgreso(0, totalRegistros, 'Error en la importación');
                setTimeout(function() {
                    alert(errorMessage);
                    $('#progressCard').addClass('d-none');
                    $('#resultCard').removeClass('d-none');
                    importInProgress = false;
                }, 1000);
            }
        });
    }

    // Función para confirmar la importación de estudiantes
    function confirmarImportacionEstudiantes() {
        if (registrosEstudiantesPendientes.length === 0) {
            alert('No hay registros para importar');
            return;
        }

        if (importInProgress) {
            alert('Ya hay una importación en progreso');
            return;
        }

        importInProgress = true;
        const totalRegistros = registrosEstudiantesPendientes.length;

        // Ocultar resultados de validación y mostrar barra de progreso
        $('#resultCard').addClass('d-none');
        $('#progressCard').removeClass('d-none');

        // Actualizar barra de progreso con la cantidad real de registros
        actualizarProgreso(0, totalRegistros, 'Iniciando importación de estudiantes...');

        // Usar FormData para enviar los datos
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('registros', JSON.stringify(registrosEstudiantesPendientes));

        $.ajax({
            url: '{{ route("importar.estudiantes") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                // Simular progreso basado en la cantidad real de registros
                var progress = 0;
                var totalSteps = Math.min(totalRegistros, 100);
                var progressIncrement = totalSteps > 0 ? Math.floor(totalRegistros / totalSteps) : 1;
                var currentStep = 0;

                var interval = setInterval(function() {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        progress = Math.min((currentStep / totalSteps) * 100, 90);
                        var registrosSimulados = Math.min(Math.floor((currentStep / totalSteps) * totalRegistros), totalRegistros);
                        actualizarProgreso(registrosSimulados, totalRegistros, 'Procesando estudiantes...');
                    }
                }, 300);

                xhr.addEventListener('loadend', function() {
                    clearInterval(interval);
                });

                return xhr;
            },
            success: function(response) {
                actualizarProgreso(totalRegistros, totalRegistros, 'Importación completada!');
                setTimeout(function() {
                    mostrarResultadosFinales(response, 'estudiantes');
                    registrosEstudiantesPendientes = [];
                    $('#estudiantesFile').val('');
                    importInProgress = false;
                    $('#progressCard').addClass('d-none');
                }, 1000);
            },
            error: function(xhr) {
                let errorMessage = 'Error al importar los estudiantes';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage += ': ' + xhr.responseJSON.error;
                } else if (xhr.status === 419) {
                    errorMessage = 'Error de autenticación CSRF. Por favor, recarga la página e intenta nuevamente.';
                } else if (xhr.responseText) {
                    errorMessage += ': ' + xhr.responseText;
                }

                actualizarProgreso(0, totalRegistros, 'Error en la importación');
                setTimeout(function() {
                    alert(errorMessage);
                    $('#progressCard').addClass('d-none');
                    $('#resultCard').removeClass('d-none');
                    importInProgress = false;
                }, 1000);
            }
        });
    }

    // Función para actualizar la barra de progreso
    function actualizarProgreso(procesados, total, mensaje) {
        const porcentaje = total > 0 ? Math.min(100, Math.max(0, (procesados / total) * 100)) : 0;
        $('#progressBar').css('width', porcentaje + '%').attr('aria-valuenow', porcentaje);
        $('#progressPercent').text(Math.round(porcentaje) + '%');
        $('#progressText').text(mensaje);

        if (total > 0) {
            $('#progressDetails').text(`${procesados} de ${total} registros procesados (${Math.round(porcentaje)}%)`);
        } else {
            $('#progressDetails').text(mensaje);
        }

        // Cambiar color según el progreso
        if (porcentaje < 30) {
            $('#progressBar').removeClass('bg-success bg-warning').addClass('bg-info');
        } else if (porcentaje < 70) {
            $('#progressBar').removeClass('bg-info bg-success').addClass('bg-warning');
        } else {
            $('#progressBar').removeClass('bg-info bg-warning').addClass('bg-success');
        }
    }

    // Función para mostrar resultados finales
    function mostrarResultadosFinales(data, tipo) {
        let html = '<div class="alert ' + (data.errores.length > 0 ? 'alert-warning' : 'alert-success') + '">';
        html += '<h6>Importación Completada - ' + (tipo === 'apoderados' ? 'Apoderados' : 'Estudiantes') + '</h6>';
        html += '<p><strong>Registros exitosos:</strong> ' + data.exitosos + '</p>';

        if (data.errores && data.errores.length > 0) {
            html += '<p><strong>Errores durante la importación:</strong></p>';
            html += '<div style="max-height: 200px; overflow-y: auto;">';
            html += '<ul>';
            data.errores.forEach(function(error) {
                html += '<li class="text-danger">' + error + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        } else {
            html += '<p class="mb-0">Todos los registros se importaron correctamente.</p>';
        }

        html += '</div>';

        // Agregar botón para cerrar
        html += '<div class="text-end">';
        html += '<button type="button" class="btn btn-primary" id="closeResultsBtn">';
        html += '<i class="bi bi-check-lg me-1"></i> Aceptar';
        html += '</button>';
        html += '</div>';

        $('#importResults').html(html);
        $('#resultCard').removeClass('d-none');

        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#resultCard').offset().top
        }, 1000);

        // Evento para cerrar los resultados
        $('#closeResultsBtn').click(function() {
            $('#resultCard').addClass('d-none');
            $('#importApoderadosBtn').html('<i class="bi bi-upload me-1"></i> Validar y Importar Apoderados');
            $('#importEstudiantesBtn').html('<i class="bi bi-upload me-1"></i> Validar y Importar Estudiantes');
        });
    }
});
</script>

<style>
.import-section {
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.template-download {
    display: inline-block;
    margin-bottom: 15px;
    color: #0d6efd;
    text-decoration: none;
}

.template-download:hover {
    text-decoration: underline;
}

.validation-results table {
    font-size: 0.875rem;
}

.validation-results .table th {
    background-color: #f8f9fa;
}

.progress-container {
    max-width: 100%;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.3s ease;
}
</style>
@endsection
