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

            <!-- Modal de confirmación para Apoderados -->
            @if(session('validacion_apoderados') && session('tipo_importacion') == 'apoderados')
            <div class="modal fade show d-block" id="confirmModalApoderados" tabindex="-1" aria-modal="true" role="dialog" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-check-circle me-2"></i>Confirmar Importación de Apoderados
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="alert alert-success">
                                        <h6><i class="bi bi-check-circle me-2"></i>Registros Válidos:</h6>
                                        <h3 class="text-center mb-0">{{ session('registros_validos_apoderados') }}</h3>
                                        <p class="text-center mb-0">apoderados listos para importar</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Errores de Validación:</h6>
                                        <h3 class="text-center mb-0">{{ count(session('errores_validacion_apoderados', [])) }}</h3>
                                        <p class="text-center mb-0">registros con errores</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabs para detalles -->
                            <ul class="nav nav-tabs" id="validationTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="valid-tab" data-bs-toggle="tab"
                                            data-bs-target="#valid-tab-pane" type="button" role="tab">
                                        <i class="bi bi-check-circle me-1"></i>Válidos ({{ session('registros_validos_apoderados') }})
                                    </button>
                                </li>
                                @if(count(session('errores_validacion_apoderados', [])) > 0)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="errors-tab" data-bs-toggle="tab"
                                            data-bs-target="#errors-tab-pane" type="button" role="tab">
                                        <i class="bi bi-x-circle me-1"></i>Errores ({{ count(session('errores_validacion_apoderados')) }})
                                    </button>
                                </li>
                                @endif
                            </ul>

                            <div class="tab-content p-3 border border-top-0" style="max-height: 400px; overflow-y: auto;">
                                <!-- Tab Válidos -->
                                <div class="tab-pane fade show active" id="valid-tab-pane" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>DNI</th>
                                                    <th>Apellidos y Nombres</th>
                                                    <th>Teléfono</th>
                                                    <th>Parentesco</th>
                                                    <th>Email</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(session('datos_validos_apoderados', []) as $registro)
                                                <tr>
                                                    <td>{{ $registro['fila'] }}</td>
                                                    <td><strong>{{ $registro['datos']['dni'] }}</strong></td>
                                                    <td>
                                                        {{ $registro['datos']['apellido_paterno'] }}
                                                        {{ $registro['datos']['apellido_materno'] }},
                                                        {{ $registro['datos']['nombre'] }}
                                                    </td>
                                                    <td>{{ $registro['datos']['telefono'] ?? 'N/A' }}</td>
                                                    <td><span class="badge bg-info">{{ ucfirst($registro['datos']['parentesco']) }}</span></td>
                                                    <td>{{ $registro['datos']['email'] }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab Errores -->
                                @if(count(session('errores_validacion_apoderados', [])) > 0)
                                <div class="tab-pane fade" id="errors-tab-pane" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Fila</th>
                                                    <th>DNI</th>
                                                    <th>Error</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(session('errores_validacion_apoderados') as $error)
                                                <tr>
                                                    <td>{{ $error['fila'] }}</td>
                                                    <td><code>{{ $error['dni'] }}</code></td>
                                                    <td class="text-danger">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $error['error'] }}
                                                    </td>
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
                                <strong>Información importante:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Los apoderados se crearán con su DNI como usuario y contraseña</li>
                                    <li>El email será generado automáticamente: <strong>DNI@ietere.com</strong></li>
                                    <li>Se asignará automáticamente el rol de <strong>Apoderado</strong></li>
                                    <li>Solo se importarán los registros marcados como válidos</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <form action="{{ route('importar.apoderados') }}" method="POST" id="cancelFormApoderados">
                                @csrf
                                <input type="hidden" name="accion" value="cancelar">
                                <button type="submit" class="btn btn-secondary" onclick="mostrarPantallaCarga('Cancelando importación...')">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </button>
                            </form>
                            <form action="{{ route('importar.apoderados') }}" method="POST" id="procesarFormApoderados">
                                @csrf
                                <input type="hidden" name="accion" value="procesar">
                                <button type="submit" class="btn btn-success" onclick="mostrarPantallaCarga('Importando apoderados...')">
                                    <i class="bi bi-check-circle me-2"></i>Sí, Importar {{ session('registros_validos_apoderados') }} Apoderados
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Modal de confirmación para Estudiantes -->
            @if(session('validacion_estudiantes') && session('tipo_importacion') == 'estudiantes')
            <div class="modal fade show d-block" id="confirmModalEstudiantes" tabindex="-1" aria-modal="true" role="dialog" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-check-circle me-2"></i>Confirmar Importación de Estudiantes
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="alert alert-success">
                                        <h6><i class="bi bi-check-circle me-2"></i>Registros Válidos:</h6>
                                        <h3 class="text-center mb-0">{{ session('registros_validos_estudiantes') }}</h3>
                                        <p class="text-center mb-0">estudiantes listos para importar</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Errores de Validación:</h6>
                                        <h3 class="text-center mb-0">{{ count(session('errores_validacion_estudiantes', [])) }}</h3>
                                        <p class="text-center mb-0">registros con errores</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabs para detalles -->
                            <ul class="nav nav-tabs" id="validationTabsEstudiantes" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="valid-tab-estudiantes" data-bs-toggle="tab"
                                            data-bs-target="#valid-tab-pane-estudiantes" type="button" role="tab">
                                        <i class="bi bi-check-circle me-1"></i>Válidos ({{ session('registros_validos_estudiantes') }})
                                    </button>
                                </li>
                                @if(count(session('errores_validacion_estudiantes', [])) > 0)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="errors-tab-estudiantes" data-bs-toggle="tab"
                                            data-bs-target="#errors-tab-pane-estudiantes" type="button" role="tab">
                                        <i class="bi bi-x-circle me-1"></i>Errores ({{ count(session('errores_validacion_estudiantes')) }})
                                    </button>
                                </li>
                                @endif
                            </ul>

                            <div class="tab-content p-3 border border-top-0" style="max-height: 400px; overflow-y: auto;">
                                <!-- Tab Válidos -->
                                <div class="tab-pane fade show active" id="valid-tab-pane-estudiantes" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>DNI Estudiante</th>
                                                    <th>Estudiante</th>
                                                    <th>DNI Apoderado</th>
                                                    <th>Apoderado</th>
                                                    <th>Grado</th>
                                                    <th>Fecha Nac.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(session('datos_validos_estudiantes', []) as $registro)
                                                <tr>
                                                    <td>{{ $registro['fila'] }}</td>
                                                    <td><strong>{{ $registro['datos']['dni_estudiante'] }}</strong></td>
                                                    <td>
                                                        {{ $registro['datos']['apellido_paterno'] }}
                                                        {{ $registro['datos']['apellido_materno'] ?? '' }},
                                                        {{ $registro['datos']['nombre'] }}
                                                    </td>
                                                    <td><code>{{ $registro['datos']['dni_apoderado'] }}</code></td>
                                                    <td>{{ $registro['datos']['apoderado_nombre'] }}</td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            {{ $registro['datos']['grado'] }}° {{ $registro['datos']['seccion'] }} - {{ $registro['datos']['nivel'] }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($registro['datos']['fecha_nacimiento'])
                                                            {{ \Carbon\Carbon::parse($registro['datos']['fecha_nacimiento'])->format('d/m/Y') }}
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab Errores -->
                                @if(count(session('errores_validacion_estudiantes', [])) > 0)
                                <div class="tab-pane fade" id="errors-tab-pane-estudiantes" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Fila</th>
                                                    <th>DNI Estudiante</th>
                                                    <th>Error</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(session('errores_validacion_estudiantes') as $error)
                                                <tr>
                                                    <td>{{ $error['fila'] }}</td>
                                                    <td><code>{{ $error['dni_estudiante'] }}</code></td>
                                                    <td class="text-danger">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $error['error'] }}
                                                    </td>
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
                                <strong>Información importante:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Los estudiantes se crearán con su DNI como usuario y contraseña</li>
                                    <li>El email será generado automáticamente: <strong>DNI@ietere.com</strong></li>
                                    <li>Se asignará automáticamente el rol de <strong>Estudiante</strong></li>
                                    <li>Se vincularán automáticamente con el apoderado correspondiente</li>
                                    <li>Se asignarán al grado y sección especificados</li>
                                    <li>Solo se importarán los registros marcados como válidos</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <form action="{{ route('importar.estudiantes') }}" method="POST" id="cancelFormEstudiantes">
                                @csrf
                                <input type="hidden" name="accion" value="cancelar">
                                <button type="submit" class="btn btn-secondary" onclick="mostrarPantallaCarga('Cancelando importación...')">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </button>
                            </form>
                            <form action="{{ route('importar.estudiantes') }}" method="POST" id="procesarFormEstudiantes">
                                @csrf
                                <input type="hidden" name="accion" value="procesar">
                                <button type="submit" class="btn btn-success" onclick="mostrarPantallaCarga('Importando estudiantes...')">
                                    <i class="bi bi-check-circle me-2"></i>Sí, Importar {{ session('registros_validos_estudiantes') }} Estudiantes
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
                    @if(session('exitosos_apoderados'))
                    <br><small><strong>{{ session('exitosos_apoderados') }}</strong> apoderados importados exitosamente.</small>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
                    @if(session('errores_proceso_apoderados'))
                    <hr>
                    <p class="mb-1"><strong>Errores durante el procesamiento:</strong></p>
                    <ul class="mb-0">
                        @foreach(session('errores_proceso_apoderados') as $error)
                        <li>Fila {{ $error['fila'] }} (DNI: {{ $error['dni'] }}): {{ $error['error'] }}</li>
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

                @if (session('exitosos_estudiantes'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') ?? 'Importación de estudiantes completada' }}
                    <br><small><strong>{{ session('exitosos_estudiantes') }}</strong> estudiantes importados exitosamente.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if (session('errores_proceso_estudiantes'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') ?? 'Se produjeron errores durante la importación' }}
                    <hr>
                    <p class="mb-1"><strong>Errores durante el procesamiento:</strong></p>
                    <ul class="mb-0">
                        @foreach(session('errores_proceso_estudiantes') as $error)
                        <li>Fila {{ $error['fila'] }} (DNI: {{ $error['dni_estudiante'] }}): {{ $error['error'] }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <div class="row">
                        <!-- Importar Apoderados -->
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="import-section h-100">
                                <h5 class="mb-3">
                                    <i class="bi bi-person-badge me-2"></i> Importar Apoderados
                                </h5>
                                <p class="text-muted">Formato requerido para apoderados:</p>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Columna</th>
                                                <th>Campo</th>
                                                <th>Requerido</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>A</strong></td>
                                                <td>DNI (8 dígitos)</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>B</strong></td>
                                                <td>Apellido Paterno</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>C</strong></td>
                                                <td>Apellido Materno</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>D</strong></td>
                                                <td>Nombres</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>E</strong></td>
                                                <td>Teléfono</td>
                                                <td><span class="badge bg-success">No</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>F</strong></td>
                                                <td>Parentesco (padre, madre, tutor)</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="{{ asset('templates/plantilla_apoderados.xlsx') }}" class="btn btn-outline-info btn-sm mb-3" download>
                                    <i class="bi bi-download me-1"></i> Descargar plantilla
                                </a>
                                <form action="{{ route('importar.apoderados') }}" method="POST" enctype="multipart/form-data" id="importApoderadosForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="apoderadosFile" class="form-label">Seleccionar archivo Excel</label>
                                        <input class="form-control" type="file" id="apoderadosFile" name="file" accept=".xlsx,.xls" required>
                                        <div class="form-text">Tamaño máximo: 10MB</div>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100" onclick="return validarYMostrarCarga('apoderados')">
                                        <i class="bi bi-search me-1"></i> Validar y Procesar Apoderados
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Importar Estudiantes -->
                        <div class="col-md-6">
                            <div class="import-section h-100">
                                <h5 class="mb-3">
                                    <i class="bi bi-person-vcard me-2"></i> Importar Estudiantes
                                </h5>
                                <p class="text-muted">Formato requerido para estudiantes:</p>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Columna</th>
                                                <th>Campo</th>
                                                <th>Requerido</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>A</strong></td>
                                                <td>DNI del estudiante</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>B</strong></td>
                                                <td>Apellido Paterno</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>C</strong></td>
                                                <td>Apellido Materno</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>D</strong></td>
                                                <td>Nombres</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>E</strong></td>
                                                <td>Fecha de nacimiento (dd/mm/yyyy)</td>
                                                <td><span class="badge bg-success">No</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>F</strong></td>
                                                <td>DNI del apoderado</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>G</strong></td>
                                                <td>Grado (1-6)</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>H</strong></td>
                                                <td>Sección (A, B, C...)</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>I</strong></td>
                                                <td>Nivel (Primaria o Secundaria)</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="{{ asset('templates/plantilla_estudiantes.xlsx') }}" class="btn btn-outline-info btn-sm mb-3" download>
                                    <i class="bi bi-download me-1"></i> Descargar plantilla
                                </a>
                                <form action="{{ route('importar.estudiantes') }}" method="POST" enctype="multipart/form-data" id="importEstudiantesForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="estudiantesFile" class="form-label">Seleccionar archivo Excel</label>
                                        <input class="form-control" type="file" id="estudiantesFile" name="file" accept=".xlsx,.xls" required>
                                        <div class="form-text">Tamaño máximo: 10MB</div>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100" onclick="return validarYMostrarCarga('estudiantes')">
                                        <i class="bi bi-search me-1"></i> Validar y Procesar Estudiantes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
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

.import-section {
    border-left: 4px solid #198754;
    padding-left: 1rem;
}

.import-section:nth-child(2) {
    border-left-color: #0d6efd;
}
</style>

<script>
function validarYMostrarCarga(tipo) {
    const fileInput = tipo === 'apoderados'
        ? document.getElementById('apoderadosFile')
        : document.getElementById('estudiantesFile');

    if (!fileInput.files.length) {
        alert('Por favor selecciona un archivo Excel para ' + (tipo === 'apoderados' ? 'apoderados' : 'estudiantes') + '.');
        return false;
    }

    // Verificar extensión
    const fileName = fileInput.files[0].name;
    const extension = fileName.split('.').pop().toLowerCase();

    if (extension !== 'xlsx' && extension !== 'xls') {
        alert('Por favor selecciona un archivo Excel válido (.xlsx o .xls).');
        return false;
    }

    mostrarPantallaCarga('Validando archivo de ' + (tipo === 'apoderados' ? 'apoderados' : 'estudiantes') + '...');
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
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                e.stopPropagation();
            }
        });
    });
});
</script>
@endsection
