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

    <!-- Pantalla de carga -->
    <div id="loadingScreen" class="d-none">
        <div class="loading-overlay">
            <div class="loading-content text-center">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h4 class="text-white" id="loadingTitle">Procesando...</h4>
                <p class="text-white" id="loadingMessage">Por favor, espera mientras se completan las operaciones.</p>
                <div class="progress mt-4" style="height: 10px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-white mt-2">0% completado</p>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    @if(session('validacion_completa'))
    <div class="modal fade show d-block" id="confirmModal" tabindex="-1" aria-modal="true" role="dialog" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>Confirmar Importación
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle me-2"></i>Registros Válidos:</h6>
                                <h3 class="text-center mb-0">{{ session('registros_validos') }}</h3>
                                <p class="text-center mb-0">listos para importar</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Advertencias:</h6>
                                <h3 class="text-center mb-0">{{ count(session('errores_validacion', [])) + count(session('duplicados_validacion', [])) }}</h3>
                                <p class="text-center mb-0">errores y duplicados</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs para detalles -->
                    <ul class="nav nav-tabs" id="validationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="valid-tab" data-bs-toggle="tab"
                                    data-bs-target="#valid-tab-pane" type="button" role="tab">
                                <i class="bi bi-check-circle me-1"></i>Válidos ({{ session('registros_validos') }})
                            </button>
                        </li>
                        @if(count(session('errores_validacion', [])) > 0)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="errors-tab" data-bs-toggle="tab"
                                    data-bs-target="#errors-tab-pane" type="button" role="tab">
                                <i class="bi bi-x-circle me-1"></i>Errores ({{ count(session('errores_validacion')) }})
                            </button>
                        </li>
                        @endif
                        @if(count(session('duplicados_validacion', [])) > 0)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="duplicates-tab" data-bs-toggle="tab"
                                    data-bs-target="#duplicates-tab-pane" type="button" role="tab">
                                <i class="bi bi-exclamation-triangle me-1"></i>Duplicados ({{ count(session('duplicados_validacion')) }})
                            </button>
                        </li>
                        @endif
                    </ul>

                    <div class="tab-content p-3 border border-top-0" style="max-height: 300px; overflow-y: auto;">
                        <!-- Tab Válidos -->
                        <div class="tab-pane fade show active" id="valid-tab-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Materia</th>
                                            <th>Competencia</th>
                                            <th>Criterio</th>
                                            <th>Grado</th>
                                            <th>Sigla</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('datos_validos', []) as $registro)
                                        <tr>
                                            <td>{{ $registro['fila'] }}</td>
                                            <td>{{ $registro['datos']['materia'] }}</td>
                                            <td>{{ $registro['datos']['competencia'] }}</td>
                                            <td>{{ $registro['datos']['criterio'] }}</td>
                                            <td>{{ $registro['datos']['grado'] }}</td>
                                            <td><span class="badge bg-info">{{ $registro['datos']['sigla'] }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Errores -->
                        @if(count(session('errores_validacion', [])) > 0)
                        <div class="tab-pane fade" id="errors-tab-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fila</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('errores_validacion') as $error)
                                        <tr>
                                            <td>{{ $error['fila'] }}</td>
                                            <td class="text-danger">{{ $error['error'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        <!-- Tab Duplicados -->
                        @if(count(session('duplicados_validacion', [])) > 0)
                        <div class="tab-pane fade" id="duplicates-tab-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fila</th>
                                            <th>Advertencia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('duplicados_validacion') as $duplicado)
                                        <tr>
                                            <td>{{ $duplicado['fila'] }}</td>
                                            <td class="text-warning">{{ $duplicado['error'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Importante:</strong> Solo los registros válidos serán importados. ¿Desea continuar?
                    </div>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('importar.criterio') }}" method="POST" id="cancelForm">
                        @csrf
                        <input type="hidden" name="accion" value="cancelar">
                        <button type="submit" class="btn btn-secondary" onclick="mostrarPantallaCarga('Cancelando...')">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </button>
                    </form>
                    <form action="{{ route('importar.criterio') }}" method="POST" id="procesarForm">
                        @csrf
                        <input type="hidden" name="accion" value="procesar">
                        <button type="submit" class="btn btn-success" onclick="mostrarPantallaCarga('Importando criterios...')">
                            <i class="bi bi-check-circle me-2"></i>Sí, Importar {{ session('registros_validos') }} Registros
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Mensajes de resultado -->
    <div id="messagesContainer">
        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            @if(session('exitosos'))
            <br><small><strong>{{ session('exitosos') }}</strong> criterios importados exitosamente.</small>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
            @if(session('errores_proceso'))
            <hr>
            <p class="mb-1"><strong>Errores durante el procesamiento:</strong></p>
            <ul class="mb-0">
                @foreach(session('errores_proceso') as $error)
                <li>Fila {{ $error['fila'] }}: {{ $error['error'] }}</li>
                @endforeach
            </ul>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

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

                        <div class="form-group mb-3">
                            <label for="periodo_id" class="form-label text-danger fw-semibold">
                                Período Escolar *
                            </label>
                            <select name="periodo_id" id="periodo_id" class="form-select" required>
                                <option value="">Seleccione un período</option>
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->id }}" {{ old('periodo_id') == $periodo->id ? 'selected' : '' }}>
                                        {{ $periodo->nombre }} ({{ $periodo->anio }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                El período seleccionado debe coincidir con las siglas de bimestres en tu archivo
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="archivo_excel" class="form-label">Archivo Excel *</label>
                            <input type="file" name="archivo_excel" id="archivo_excel"
                                class="form-control" accept=".xlsx,.xls" required>
                            <div class="form-text">Formatos permitidos: .xlsx, .xls (Tamaño máximo: 2MB)</div>
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
                                <li><strong>Columna H:</strong> Sigla del Bimestre (ej: B1, B2, B3, B4, etc. - debe existir en el período seleccionado)</li>
                            </ul>
                            <hr class="my-2">
                            <p class="mb-0 small text-muted">
                                <i class="bi bi-lightbulb me-1"></i>
                                Las siglas de bimestres deben coincidir con las configuradas en el período seleccionado.
                            </p>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success" onclick="return validarYMostrarCarga()" id="btn-importar">
                                <i class="bi bi-search me-2"></i> Validar y Procesar
                            </button>
                            <a href="{{ route('materiacriterio.index') }}" class="btn btn-secondary">
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
                        <p class="text-muted">Descarga la plantilla Excel con el formato correcto.</p>
                        <a href="{{ asset('templates/plantilla_materia_criterio.xlsx') }}"
                        class="btn btn-info btn-block" download>
                            <i class="bi bi-download me-2"></i> Descargar Plantilla
                        </a>
                        <a href="{{ asset('templates/plantilla_materia_criterio_ejemplo.xlsx') }}" class="btn btn-secondary btn-block" download>
                            <i class="bi bi-download me-2"></i> Descargar Plantilla Ejemplo
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
                            Selecciona el período escolar primero
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Descarga la plantilla
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Completa los datos según el formato
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            Las siglas deben coincidir con el período seleccionado
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            La materia y competencia deben existir
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            El grado debe existir en el sistema
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-content {
    background: rgba(255, 255, 255, 0.1);
    padding: 2.5rem;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    min-width: 400px;
}

.modal-backdrop.show {
    opacity: 0.5;
}
</style>

<script>
function validarYMostrarCarga() {
    const fileInput = document.getElementById('archivo_excel');

    if (!fileInput.files.length) {
        alert('Por favor selecciona un archivo Excel.');
        return false;
    }

    // Verificar extensión
    const fileName = fileInput.files[0].name;
    const extension = fileName.split('.').pop().toLowerCase();

    if (extension !== 'xlsx' && extension !== 'xls') {
        alert('Por favor selecciona un archivo Excel válido (.xlsx o .xls).');
        return false;
    }

    mostrarPantallaCarga('Validando archivo...');
    return true;
}

function mostrarPantallaCarga(titulo, mensaje = 'Por favor, espera mientras se completan las operaciones.') {
    document.getElementById('loadingScreen').classList.remove('d-none');
    document.getElementById('loadingTitle').textContent = titulo;
    document.getElementById('loadingMessage').textContent = mensaje;

    // Animar barra de progreso
    let progreso = 0;
    const barra = document.getElementById('progressBar');
    const texto = document.getElementById('progressText');

    const intervalo = setInterval(() => {
        if (progreso < 90) {
            progreso += 1;
            barra.style.width = progreso + '%';
            texto.textContent = progreso + '% completado';
        } else {
            clearInterval(intervalo);
        }
    }, 50);
}

function ocultarPantallaCarga() {
    document.getElementById('loadingScreen').classList.add('d-none');
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressText').textContent = '0% completado';
}

// Ocultar pantalla de carga si hay errores de validación en el formulario
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function() {
            // Si el formulario tiene errores de validación HTML5
            if (!form.checkValidity()) {
                ocultarPantallaCarga();
            }
        });
    });

    // Si hay mensajes de resultado, asegurarse de que la pantalla de carga esté oculta
    if (document.querySelector('.alert')) {
        ocultarPantallaCarga();
    }
});

// Si hay modal de confirmación, deshabilitar cierre con clic fuera
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                e.stopPropagation();
            }
        });
    }
});
</script>
@endsection
