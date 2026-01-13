@extends('layouts.app')
@section('title','Notas')
@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Notas - {{ $curso->grado->nombre }} - {{ $materia->nombre }} - Bimestre {{ $bimestre }}
        </h1>

        <div class="d-flex align-items-center">
            <!-- Estado actual -->
            <div class="mr-3">
                <span class="badge badge-{{ $estadosNotas[$estadoActual][1] ?? 'secondary' }}">
                    {{ $estadosNotas[$estadoActual][0] ?? 'Desconocido' }}
                </span>
            </div>

            <div class="btn-group" role="group">
            <!-- PUBLICAR / AVANZAR -->
                @if($puedePublicar)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#publicarModal">
                    <i class="fas fa-paper-plane me-1"></i>
                    {{ $textoBotonPublicar }}
                </button>
                @endif

                <!-- REVERTIR -->
                @if($puedeRevertir)
                <a href="{{ route('nota.revertir.form', [
                    'curso_grado_sec_niv_anio_id' => $curso_id,
                    'bimestre' => $bimestre
                ]) }}"
                class="btn btn-outline-danger">
                    <i class="fas fa-undo me-1"></i>
                    Revertir
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Información del curso -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Docente
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if($docente && $docente->user)
                                    {{ $docente->user->apellido_paterno.' '.
                                    $docente->user->apellido_materno.', '.
                                    $docente->user->nombre }}
                                @else
                                    No asignado
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Estudiantes Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estudiantesActivos->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Estado de Notas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadosNotas[$estadoActual][0] ?? 'Desconocido' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-columns fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            {{-- Mensajes de error generales del sistema (Validaciones, etc) --}}
            @if ($errors->any())
                <div class="alert alert-danger border-left-danger shadow-sm" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li><i class="fas fa-times-circle me-1"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Mensaje de éxito (si acabas de realizar una acción) --}}
            @if(session('success'))
                <div class="alert alert-success border-left-success shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Tabla de notas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Registro de Notas</h6>
            <div>
                <div class="switch-container mr-4">
                    <label class="mr-2 mb-0">Formato:</label>
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check" name="btnradio" id="btncuantitativo" autocomplete="off" checked value="cuantitativo">
                        <label class="btn btn-outline-primary" for="btncuantitativo">Cuantitativo</label>

                        <input type="radio" class="btn-check" name="btnradio" id="btncualitativo" autocomplete="off" value="cualitativo">
                        <label class="btn btn-outline-primary" for="btncualitativo">Cualitativo</label>

                        <button type="button" class="btn btn-secondary">PDF</button>
                        <button type="button" class="btn btn-success" id="btnExportarExcel">
                            <i class="fas fa-file-excel mr-1"></i>Excel
                        </button>
                    </div>
                </div>
                <span class="text-xs text-gray-600 mr-3">
                    <i class="fas fa-edit text-primary"></i> Puede guardar: {{ $puedeGuardar ? 'Sí' : 'No' }}
                </span>
                <span class="text-xs text-gray-600">
                    <i class="fas fa-paper-plane text-success"></i> Puede publicar: {{ $puedePublicar ? 'Sí' : 'No' }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="tablaNotas" width="100%" cellspacing="0">
                    <thead>
                        <tr class="text-center">
                            <th rowspan="3" class="align-middle" width="30">N°</th>
                            <th rowspan="3" class="align-middle" width="200">
                                ESTUDIANTES
                            </th>

                            @foreach($competencias as $competencia)
                                @if($competencia->criterios->count() > 0)
                                <th colspan="{{ $competencia->criterios->count() }}" class="text-center bg-light">
                                    <div class="font-weight-bold">{{ $competencia->nombre }}</div>
                                    <small class="text-muted">Competencia</small>
                                </th>
                                @endif
                            @endforeach

                            @if($competenciasNoTransversales->count() > 0)
                            <th colspan="{{ $competenciasNoTransversales->count() }}" class="text-center bg-info text-white">
                                <div class="font-weight-bold">SIAGIE</div>
                                <small>Competencias</small>
                            </th>
                            @endif

                            @if($competenciaTransversal && $competenciaTransversal->criterios->count() > 0)
                            <th colspan="{{ $competenciaTransversal->criterios->count() }}" class="text-center bg-info text-white">
                                <div class="font-weight-bold">SIAGIE</div>
                                <small>Transversales</small>
                            </th>
                            @endif

                            @if($conductas->count() > 0)
                            <th colspan="{{ $conductas->count() }}" class="text-center bg-warning">
                                <div class="font-weight-bold">CONDUCTAS</div>
                            </th>
                            @endif
                        </tr>

                        <tr class="text-center">
                            @foreach($competencias as $competencia)
                                @foreach($competencia->criterios as $criterio)
                                <th class="small bg-light">
                                    {{ $criterio->nombre }}
                                </th>
                                @endforeach
                            @endforeach

                            @foreach($competenciasNoTransversales as $competenciaNT)
                            <th class="small bg-info text-white">
                                {{ $competenciaNT->nombre }}
                                <br>
                                <small>Promedio</small>
                            </th>
                            @endforeach

                            @if($competenciaTransversal)
                                @foreach($competenciaTransversal->criterios as $criterioTrans)
                                <th class="small bg-info text-white">
                                    {{ $criterioTrans->nombre }}
                                    <br>
                                    <small>Transversal</small>
                                </th>
                                @endforeach
                            @endif

                            @foreach($conductas as $conducta)
                            <th class="small bg-warning">
                                {{ $conducta->nombre }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($estudiantesActivos as $index => $estudiante)
                        <tr>
                            <td class="text-center align-middle">{{ $index + 1 }}</td>

                            <td class="align-middle" style="white-space: nowrap;">
                                <div class="font-weight-bold">
                                    {{ $estudiante->user->apellido_paterno }}
                                    {{ $estudiante->user->apellido_materno }},
                                    {{ $estudiante->user->nombre }}
                                </div>
                            </td>

                            @foreach($competencias as $competencia)
                                @foreach($competencia->criterios as $criterio)
                                <td class="text-center align-middle">
                                    @php
                                        $key = $estudiante->id . '-' . $criterio->id;
                                        $nota = $notasExistentes[$key]['nota'] ?? null;
                                        $publico = $notasExistentes[$key]['publico'] ?? '0';
                                        $puedeGuardarCampo = $puedeGuardar && in_array($publico, ['0', '1']);
                                        $valorMostrar = $nota;
                                    @endphp

                                    @if($puedeGuardarCampo)
                                    <input type="text"
                                        class="form-control form-control-sm text-center nota-input"
                                        name="notas[{{ $estudiante->id }}][{{ $criterio->id }}]"
                                        value="{{ $valorMostrar }}"
                                        maxlength="1"
                                        pattern="[1-4]"
                                        data-estudiante="{{ $estudiante->id }}"
                                        data-criterio="{{ $criterio->id }}"
                                        data-original="{{ $nota ?? '' }}"
                                        data-type="criterio">
                                    @else
                                    <div class="font-weight-bold
                                        @if($nota >= 3) text-success
                                        @elseif($nota == 2) text-warning
                                        @elseif($nota == 1) text-danger
                                        @endif">
                                        {{ $nota ?? '-' }}
                                    </div>
                                    @endif
                                </td>
                                @endforeach
                            @endforeach

                            @foreach($competenciasNoTransversales as $competenciaNT)
                            <td class="text-center align-middle bg-light">
                                @php
                                    $suma = 0;
                                    $count = 0;
                                    foreach($competenciaNT->criterios as $criterio) {
                                        $key = $estudiante->id . '-' . $criterio->id;
                                        if(isset($notasExistentes[$key]['nota'])) {
                                            $suma += $notasExistentes[$key]['nota'];
                                            $count++;
                                        }
                                    }
                                    $promedio = $count > 0 ? round($suma / $count, 1) : null;
                                @endphp
                                <div class="font-weight-bold
                                    @if($promedio >= 3) text-success
                                    @elseif($promedio == 2) text-warning
                                    @elseif($promedio == 1) text-danger
                                    @endif">
                                    {{ $promedio ?? '-' }}
                                </div>
                            </td>
                            @endforeach

                            @if($competenciaTransversal)
                                @foreach($competenciaTransversal->criterios as $criterioTrans)
                                <td class="text-center align-middle bg-light">
                                    @php
                                        $keyTrans = $estudiante->id . '-' . $criterioTrans->id;
                                        $notaTrans = $notasExistentes[$keyTrans]['nota'] ?? null;
                                        $publicoTrans = $notasExistentes[$keyTrans]['publico'] ?? '0';
                                    @endphp
                                    <div class="font-weight-bold
                                        @if($notaTrans >= 3) text-success
                                        @elseif($notaTrans == 2) text-warning
                                        @elseif($notaTrans == 1) text-danger
                                        @endif">
                                        {{ $notaTrans ?? '-' }}
                                    </div>
                                </td>
                                @endforeach
                            @endif

                            @foreach($conductas as $conducta)
                            <td class="text-center align-middle">
                                @php
                                    $keyCond = $estudiante->id . '-' . $conducta->id;
                                    $notaCond = $conductaNotas[$keyCond]['nota'] ?? null;
                                    $publicoCond = $conductaNotas[$keyCond]['publico'] ?? '0';
                                    $puedeGuardarConducta = $puedeGuardar && in_array($publicoCond, ['0', '1']);
                                @endphp

                                @if($puedeGuardarConducta)
                                <input type="text"
                                       class="form-control form-control-sm text-center conducta-input"
                                       name="conductas[{{ $estudiante->id }}][{{ $conducta->id }}]"
                                       value="{{ $notaCond }}"
                                       min="1"
                                       max="4"
                                       step="0.1"
                                       data-estudiante="{{ $estudiante->id }}"
                                       data-conducta="{{ $conducta->id }}"
                                       style="width: 70px; display: inline-block;">
                                @else
                                <div class="font-weight-bold
                                    @if($notaCond >= 13) text-success
                                    @elseif($notaCond >= 11) text-warning
                                    @elseif($notaCond !== null) text-danger
                                    @endif">
                                    {{ $notaCond ?? '-' }}
                                </div>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach

                        @if($estudiantesInactivos->count() > 0)
                        <tr class="bg-gray-200">
                            <td colspan="{{ 2 + $competencias->sum(fn($c) => $c->criterios->count()) + $totalColumnasSIAGIE + $conductas->count() }}"
                                class="text-center font-weight-bold py-2">
                                <i class="fas fa-user-slash text-gray-600 mr-2"></i>
                                ESTUDIANTES INACTIVOS CON NOTAS REGISTRADAS
                            </td>
                        </tr>

                        @foreach($estudiantesInactivos as $index => $estudiante)
                        <tr class="text-muted">
                            <td class="text-center align-middle">
                                <i class="fas fa-user-slash text-gray-400"></i>
                            </td>

                            <td class="align-middle">
                                <div class="font-weight-bold text-gray-600">
                                    {{ $estudiante->user->apellido_paterno }}
                                    {{ $estudiante->user->apellido_materno }},
                                    {{ $estudiante->user->nombre }}
                                </div>
                                <small class="text-muted">Inactivo</small>
                            </td>

                            @foreach($competencias as $competencia)
                                @foreach($competencia->criterios as $criterio)
                                <td class="text-center align-middle">
                                    @php
                                        $key = $estudiante->id . '-' . $criterio->id;
                                        $nota = $notasExistentes[$key]['nota'] ?? null;
                                        $publico = $notasExistentes[$key]['publico'] ?? '0';
                                        $puedeGuardarCampo = $puedeGuardar && in_array($publico, ['0', '1']);
                                    @endphp

                                    <div class="font-weight-bold
                                        @if($nota >= 3) text-success
                                        @elseif($nota == 2) text-warning
                                        @elseif($nota == 1) text-danger
                                        @endif">
                                        {{ $nota ?? '-' }}
                                    </div>
                                </td>
                                @endforeach
                            @endforeach

                            @foreach($competenciasNoTransversales as $competenciaNT)
                            <td class="text-center align-middle bg-light">
                                @php
                                    $suma = 0;
                                    $count = 0;
                                    foreach($competenciaNT->criterios as $criterio) {
                                        $key = $estudiante->id . '-' . $criterio->id;
                                        if(isset($notasExistentes[$key]['nota'])) {
                                            $suma += $notasExistentes[$key]['nota'];
                                            $count++;
                                        }
                                    }
                                    $promedio = $count > 0 ? round($suma / $count, 1) : null;
                                @endphp
                                <div class="font-weight-bold
                                    @if($promedio >= 3) text-success
                                    @elseif($promedio == 2) text-warning
                                    @elseif($promedio == 1) text-danger
                                    @endif">
                                    {{ $promedio ?? '-' }}
                                </div>
                            </td>
                            @endforeach

                            @if($competenciaTransversal)
                                @foreach($competenciaTransversal->criterios as $criterioTrans)
                                <td class="text-center align-middle bg-light">
                                    @php
                                        $keyTrans = $estudiante->id . '-' . $criterioTrans->id;
                                        $notaTrans = $notasExistentes[$keyTrans]['nota'] ?? null;
                                        $publicoTrans = $notasExistentes[$keyTrans]['publico'] ?? '0';
                                    @endphp
                                    <div class="font-weight-bold
                                        @if($notaTrans >= 3) text-success
                                        @elseif($notaTrans == 2) text-warning
                                        @elseif($notaTrans == 1) text-danger
                                        @endif">
                                        {{ $notaTrans ?? '-' }}
                                    </div>
                                </td>
                                @endforeach
                            @endif

                            @foreach($conductas as $conducta)
                            <td class="text-center align-middle">
                                @php
                                    $keyCond = $estudiante->id . '-' . $conducta->id;
                                    $notaCond = $conductaNotas[$keyCond]['nota'] ?? null;
                                    $publicoCond = $conductaNotas[$keyCond]['publico'] ?? '0';
                                @endphp

                                <div class="font-weight-bold
                                    @if($notaCond >= 3) text-success
                                    @elseif($notaCond == 2) text-warning
                                    @elseif($notaCond == 1) text-danger
                                    @endif">
                                    {{ $notaCond ?? '-' }}
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            @if($puedeGuardar)
            <div class="mt-3 text-right">
                <button type="button" class="btn btn-success" id="btnGuardarNotas">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para publicar notas -->
@if($puedePublicar)
<div class="modal fade" id="publicarModal" tabindex="-1" aria-labelledby="publicarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="publicarModalLabel">
                    <i class="fas fa-paper-plane me-2"></i>
                    {{ $textoBotonPublicar }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('nota.publicar', [
                'curso_grado_sec_niv_anio_id' => $curso_id,
                'bimestre' => $bimestre
            ]) }}" method="POST">
                @csrf

                <div class="modal-body">
                    <p>¿Confirma que desea <strong>{{ strtolower($textoBotonPublicar) }}</strong> las notas de este bimestre?</p>

                    <div class="alert alert-info mt-3">
                        <strong>Estado actual:</strong> {{ $estadosNotas[$estadoActual][0] }}<br>
                        <strong>Nuevo estado:</strong>
                        <strong class="text-primary">{{ str_replace(['Publicar Notas', 'Marcar como '], ['', ''], $textoBotonPublicar) }}</strong>
                    </div>

                    <div class="alert alert-warning small mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta acción avanzará el estado de visibilidad de las notas.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
<script>
$(document).ready(function() {
    // Función para mostrar mensaje Bootstrap
    function mostrarMensaje(tipo, titulo, mensaje, tiempo = 3000) {
        // Crear contenedor si no existe
        if (!$('#mensaje-flotante').length) {
            $('body').append(`
                <div id="mensaje-flotante" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; display: none;">
                    <div class="toast" role="alert">
                        <div class="toast-header">
                            <strong class="me-auto" id="mensaje-titulo"></strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                        </div>
                        <div class="toast-body" id="mensaje-texto"></div>
                    </div>
                </div>
            `);
        }

        // Configurar clases según tipo
        const tipos = {
            'success': { bg: 'bg-success text-white', icon: '✓' },
            'error': { bg: 'bg-danger text-white', icon: '✗' },
            'warning': { bg: 'bg-warning', icon: '⚠' },
            'info': { bg: 'bg-info text-white', icon: 'ℹ' }
        };

        const config = tipos[tipo] || tipos.info;

        $('#mensaje-flotante .toast-header')
            .removeClass('bg-success bg-danger bg-warning bg-info text-white')
            .addClass(config.bg);

        $('#mensaje-titulo').html(`${config.icon} ${titulo}`);
        $('#mensaje-texto').text(mensaje);

        // Mostrar mensaje
        $('#mensaje-flotante').fadeIn();

        // Ocultar automáticamente
        setTimeout(() => {
            $('#mensaje-flotante').fadeOut();
        }, tiempo);
    }

    // Función para mostrar loading
    function mostrarLoading() {
        if (!$('#loading-overlay').length) {
            $('body').append(`
                <div id="loading-overlay" class="position-fixed top-0 left-0 w-100 h-100"
                     style="z-index: 9998; background: rgba(0,0,0,0.5); display: none;">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span class="ms-3 text-light">Guardando...</span>
                    </div>
                </div>
            `);
        }
        $('#loading-overlay').fadeIn();
    }

    // Función para ocultar loading
    function ocultarLoading() {
        $('#loading-overlay').fadeOut();
    }

    // Guardar notas
    $('#btnGuardarNotas').click(function() {
        // Verificar si hay cambios
        let tieneCambios = false;
        $('.nota-input, .conducta-input').each(function() {
            if ($(this).val() !== $(this).data('original')) {
                tieneCambios = true;
                return false;
            }
        });

        if (!tieneCambios) {
            mostrarMensaje('info', 'Sin cambios', 'No hay cambios para guardar');
            return;
        }

        // Organizar notas en formato correcto para el controlador
        const notasCriterios = {};
        const notasConductas = {};

        // Recolectar notas de criterios
        $('.nota-input').each(function() {
            const estudianteId = $(this).data('estudiante');
            const criterioId = $(this).data('criterio');
            const nota = $(this).val();

            if (!notasCriterios[estudianteId]) {
                notasCriterios[estudianteId] = {};
            }

            notasCriterios[estudianteId][criterioId] = nota !== '' ? parseFloat(nota) : null;
        });

        // Recolectar notas de conductas
        $('.conducta-input').each(function() {
            const estudianteId = $(this).data('estudiante');
            const conductaId = $(this).data('conducta');
            const nota = $(this).val();

            if (!notasConductas[estudianteId]) {
                notasConductas[estudianteId] = {};
            }

            notasConductas[estudianteId][conductaId] = nota !== '' ? parseFloat(nota) : null;
        });

        // Mostrar loading
        mostrarLoading();

        // Enviar datos
        $.ajax({
            url: '{{ route("nota.guardarNotas") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                curso_id: {{ $curso_id }},
                bimestre: {{ $bimestre }},
                notas: notasCriterios,
                conductas: notasConductas
            },
            success: function(response) {
                ocultarLoading();

                if(response.success) {
                    mostrarMensaje('success', '¡Guardado!', response.message);

                    // Recargar página después de 2 segundos
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    mostrarMensaje('error', 'Error', response.message);
                }
            },
            error: function(xhr) {
                ocultarLoading();

                let message = 'Ocurrió un error al guardar las notas.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.status === 0) {
                    message = 'Error de conexión. Verifique su internet.';
                } else if (xhr.status === 500) {
                    message = 'Error interno del servidor.';
                }

                mostrarMensaje('error', 'Error ' + xhr.status, message);
            }
        });
    });

    // Validar rango de notas en inputs
    $('.nota-input, .conducta-input').on('blur', function() {
        const valor = $(this).val();

        if (valor !== '') {
            const numValor = parseFloat(valor);

            if (numValor < 1) {
                $(this).val(1);
                mostrarMensaje('warning', 'Aviso', 'La nota mínima es 1', 2000);
            } else if (numValor > 4) {
                $(this).val(4);
                mostrarMensaje('warning', 'Aviso', 'La nota máxima es 4', 2000);
            } else if (![1, 2, 3, 4].includes(numValor)) {
                // Si no es un número entero válido
                mostrarMensaje('warning', 'Valor inválido', 'Solo se permiten los valores 1, 2, 3 o 4', 2000);
                $(this).val('');
            }
        }
    });

    // Validar entrada en tiempo real (solo números 1-4)
    $('.nota-input, .conducta-input').on('input', function() {
        const valor = $(this).val();

        // Solo permitir números 1-4
        if (valor && !/^[1-4]$/.test(valor)) {
            $(this).val(valor.replace(/[^1-4]/g, ''));
        }

        // Verificar cambios
        verificarCambios();
    });

    // Verificar cambios
    function verificarCambios() {
        let tieneCambios = false;

        $('.nota-input, .conducta-input').each(function() {
            if ($(this).val() !== $(this).data('original')) {
                tieneCambios = true;
                return false; // Salir del bucle
            }
        });

        $('#btnGuardarNotas').prop('disabled', !tieneCambios);
    }

    // Guardar valores originales
    $('.nota-input, .conducta-input').each(function() {
        $(this).data('original', $(this).val());
    });

    // Inicializar verificación
    verificarCambios();
});
</script>
<script>
$(document).ready(function() {
    // Mapeo de valores cuantitativos a cualitativos (con decimales)
    const mapeoNotas = {
        '1': 'C',
        '2': 'B',
        '3': 'A',
        '4': 'AD',
        '0': '-',
        'null': '-',
        'undefined': '-'
    };

    // Mapeo inverso para cuando se ingresa en modo cualitativo
    const mapeoInverso = {
        'C': '1',
        'B': '2',
        'A': '3',
        'AD': '4',
        'c': '1',
        'b': '2',
        'a': '3',
        'ad': '4'
    };

    // Estado actual del formato
    let formatoActual = 'cuantitativo';

    // Función para redondear al entero más cercano (1, 2, 3, 4)
    function redondearNota(numero) {
        if (numero === null || numero === undefined || numero === '') return null;

        const num = parseFloat(numero);
        if (isNaN(num)) return null;

        // Redondear al entero más cercano
        const redondeado = Math.round(num);

        // Asegurar que esté entre 1 y 4
        if (redondeado < 1) return 1;
        if (redondeado > 4) return 4;
        return redondeado;
    }

    // Función para cambiar formato de un valor individual
    function cambiarFormato(valor, aFormato) {
        if (valor === null || valor === undefined || valor === '' || valor === '-') {
            return '-';
        }

        // Limpiar el valor
        const valorStr = valor.toString().trim();

        if (aFormato === 'cualitativo') {
            // Para valores decimales, redondear primero
            if (!isNaN(parseFloat(valorStr))) {
                const redondeado = redondearNota(valorStr);
                return redondeado ? mapeoNotas[redondeado.toString()] : '-';
            }
            // Si ya es una letra, mantenerla
            if (['C', 'B', 'A', 'AD', 'c', 'b', 'a', 'ad'].includes(valorStr.toUpperCase())) {
                return valorStr.toUpperCase();
            }
            return valorStr;
        } else {
            // Convertir de cualitativo a cuantitativo
            const valorUpper = valorStr.toUpperCase();
            if (mapeoInverso[valorUpper]) {
                return mapeoInverso[valorUpper];
            }
            // Si es un número, devolverlo
            if (!isNaN(parseFloat(valorStr))) {
                const num = parseFloat(valorStr);
                if (num >= 1 && num <= 4) return num;
                return valorStr;
            }
            return valorStr;
        }
    }

    // Función para cambiar todo el formato de la tabla
    function cambiarFormatoTabla(nuevoFormato) {
        formatoActual = nuevoFormato;

        // Guardar valores originales antes de cambiar
        $('.nota-input, .conducta-input').each(function() {
            const $input = $(this);
            const originalValue = $input.data('original-value') || $input.val();
            $input.data('original-value', originalValue);

            // Solo cambiar si hay un valor
            if (originalValue && originalValue !== '-' && originalValue !== '') {
                const nuevoValor = cambiarFormato(originalValue, nuevoFormato);
                $input.val(nuevoValor);
            } else {
                $input.val(''); // Vacío para poder ingresar nuevo valor
            }
        });

        // Para celdas de solo lectura (promedios)
        $('td .font-weight-bold').each(function() {
            const $celda = $(this);
            const textoActual = $celda.text().trim();

            // Guardar el valor original numérico
            if (!$celda.data('original-value')) {
                $celda.data('original-value', textoActual);
            }

            if (textoActual !== '-') {
                // Para promedios con decimales, redondear si es modo cualitativo
                if (nuevoFormato === 'cualitativo' && !isNaN(parseFloat(textoActual))) {
                    const redondeado = redondearNota(textoActual);
                    const nuevoValor = redondeado ? mapeoNotas[redondeado.toString()] : '-';
                    $celda.text(nuevoValor);
                } else if (nuevoFormato === 'cuantitativo') {
                    // Restaurar valor original (numérico)
                    const original = $celda.data('original-value');
                    if (original && original !== '-') {
                        $celda.text(original);
                    }
                }
            }
        });

        // Cambiar atributos de validación según el formato
        if (nuevoFormato === 'cualitativo') {
            $('.nota-input').attr('pattern', '[ABCDad]')
                           .attr('maxlength', '2')

            $('.conducta-input').attr('pattern', '[ABCDad]')
                               .attr('maxlength', '2')
        } else {
            $('.nota-input').attr('pattern', '[1-4](\.[0-9]+)?')
                           .attr('maxlength', '4')

            $('.conducta-input').attr('pattern', '[1-4](\.[0-9]+)?')
                               .attr('maxlength', '4')
        }
    }

    // Event listener para los radio buttons
    $('input[name="btnradio"]').change(function() {
        const formatoSeleccionado = $(this).val();
        cambiarFormatoTabla(formatoSeleccionado);
    });

    // Validación para inputs según el formato
    $(document).on('input', '.nota-input, .conducta-input', function() {
        const $input = $(this);
        let valor = $input.val();

        if (formatoActual === 'cualitativo') {
            let valorUpper = valor.toUpperCase();

            // Permitir solo AD, A, B, C (mayúsculas o minúsculas)
            if (valor.length > 0) {
                // Si empieza con A, puede ser A o AD
                if (valorUpper === 'A') {
                    $input.val('A');
                } else if (valorUpper === 'AD') {
                    $input.val('AD');
                } else if (valorUpper === 'B') {
                    $input.val('B');
                } else if (valorUpper === 'C') {
                    $input.val('C');
                } else if (['A', 'B', 'C', 'D'].includes(valorUpper.charAt(0))) {
                    // Si empieza con letra válida pero no es completa
                    if (valorUpper.charAt(0) === 'A' && valorUpper.length === 1) {
                        $input.val('A');
                    } else if (valorUpper.charAt(0) === 'B') {
                        $input.val('B');
                    } else if (valorUpper.charAt(0) === 'C') {
                        $input.val('C');
                    } else {
                        $input.val(valorUpper.charAt(0));
                    }
                } else {
                    // Si no es válido, limpiar
                    $input.val('');
                }
            }

            // Guardar el valor actual como original
            $input.data('original-value', $input.val());

        } else {
            // Validación para modo cuantitativo
            if (valor.length > 0) {
                // Permitir números del 1 al 4 con decimales opcionales
                const regex = /^[1-4](\.\d*)?$/;
                if (!regex.test(valor)) {
                    // Si no es válido, intentar corregir
                    const num = parseFloat(valor);
                    if (!isNaN(num)) {
                        if (num < 1) $input.val('1');
                        else if (num > 4) $input.val('4');
                        else $input.val(num.toString().substring(0, 4));
                    } else {
                        $input.val('');
                    }
                }
            }

            // Guardar el valor actual como original
            $input.data('original-value', $input.val());
        }
    });

    // Al hacer clic en un input, si está vacío, limpiar el placeholder temporalmente
    $(document).on('focus', '.nota-input, .conducta-input', function() {
        const $input = $(this);
        if ($input.val() === '') {
            $input.data('previous-placeholder', $input.attr('placeholder'));
            $input.attr('placeholder', '');
        }
    });

    $(document).on('blur', '.nota-input, .conducta-input', function() {
        const $input = $(this);
        const previousPlaceholder = $input.data('previous-placeholder');
        if (previousPlaceholder) {
            $input.attr('placeholder', previousPlaceholder);
            $input.removeData('previous-placeholder');
        }
    });

    // Función para cuando se guarda (opcional - si necesitas convertir antes de enviar)
    $('#btnGuardarNotas').click(function() {
        if (formatoActual === 'cualitativo') {
            // Convertir valores cualitativos a cuantitativos para enviar
            $('.nota-input, .conducta-input').each(function() {
                const $input = $(this);
                const valor = $input.val().toUpperCase();
                if (mapeoInverso[valor]) {
                    $input.val(mapeoInverso[valor]);
                }
            });
        }

        // Tu lógica de guardado aquí...
        // alert('Guardando en formato: ' + formatoActual);

        // Después de guardar, restaurar la vista
        if (formatoActual === 'cualitativo') {
            cambiarFormatoTabla('cualitativo');
        }
    });
});
</script>
<script>
$(document).ready(function() {
    // Mapeo de valores cuantitativos a cualitativos
    const mapeoNotas = {
        '1': 'C',
        '2': 'B',
        '3': 'A',
        '4': 'AD',
        '0': '-',
        'null': '-',
        'undefined': '-'
    };

    // Función para redondear al entero más cercano
    function redondearNota(numero) {
        if (numero === null || numero === undefined || numero === '') return null;
        const num = parseFloat(numero);
        if (isNaN(num)) return null;
        const redondeado = Math.round(num);
        if (redondeado < 1) return 1;
        if (redondeado > 4) return 4;
        return redondeado;
    }

    // Función para cambiar formato de un valor
    function cambiarFormato(valor, aFormato) {
        if (valor === null || valor === undefined || valor === '' || valor === '-') {
            return '-';
        }
        const valorStr = valor.toString().trim();

        if (aFormato === 'cualitativo') {
            if (!isNaN(parseFloat(valorStr))) {
                const redondeado = redondearNota(valorStr);
                return redondeado ? mapeoNotas[redondeado.toString()] : '-';
            }
            if (['C', 'B', 'A', 'AD', 'c', 'b', 'a', 'ad'].includes(valorStr.toUpperCase())) {
                return valorStr.toUpperCase();
            }
            return valorStr;
        }
        return valorStr;
    }

    // Estado del formato
    let formatoActual = 'cuantitativo';

    // Cambiar formato de la tabla
    function cambiarFormatoTabla(nuevoFormato) {
        formatoActual = nuevoFormato;

        $('.nota-input, .conducta-input').each(function() {
            const $input = $(this);
            const originalValue = $input.data('original-value') || $input.val();
            $input.data('original-value', originalValue);

            if (originalValue && originalValue !== '-' && originalValue !== '') {
                const nuevoValor = cambiarFormato(originalValue, nuevoFormato);
                $input.val(nuevoValor);
            } else {
                $input.val('');
            }
        });

        $('td .font-weight-bold').each(function() {
            const $celda = $(this);
            const textoActual = $celda.text().trim();

            if (!$celda.data('original-value')) {
                $celda.data('original-value', textoActual);
            }

            if (textoActual !== '-') {
                if (nuevoFormato === 'cualitativo' && !isNaN(parseFloat(textoActual))) {
                    const redondeado = redondearNota(textoActual);
                    const nuevoValor = redondeado ? mapeoNotas[redondeado.toString()] : '-';
                    $celda.text(nuevoValor);
                } else if (nuevoFormato === 'cuantitativo') {
                    const original = $celda.data('original-value');
                    if (original && original !== '-') {
                        $celda.text(original);
                    }
                }
            }
        });
    }

    // Event listener para radio buttons
    $('input[name="btnradio"]').change(function() {
        cambiarFormatoTabla($(this).val());
    });

    // FUNCIÓN EXCLUSIVA PARA GENERAR PDF
    function generarPDF() {
        // Crear un iframe temporal para la generación del PDF
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;

        // Escribir el contenido HTML para el PDF
        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Registro de Notas - PDF</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header h1 { color: #2c3e50; margin-bottom: 5px; }
                    .header p { color: #7f8c8d; margin: 5px 0; }
                    hr { border: 1px solid #3498db; margin: 10px 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #ddd; padding: 6px; text-align: center; font-size: 10px; }
                    th { background-color: #f8f9fa; font-weight: bold; }
                    .bg-light { background-color: #f8f9fa; }
                    .bg-info { background-color: #17a2b8; color: white; }
                    .bg-warning { background-color: #ffc107; }
                    .text-success { color: #28a745; }
                    .text-warning { color: #ffc107; }
                    .text-danger { color: #dc3545; }
                    .align-middle { vertical-align: middle; }
                    .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #7f8c8d; }
                    .leyenda { margin: 10px 0; font-size: 9px; }
                    .page-break { page-break-after: always; }
                </style>
            </head>
            <body>
        `);

        // Obtener datos de la página actual
        const titulo = 'Registro de Notas';
        const fecha = new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString();

        // Clonar la tabla para procesarla
        const tablaOriginal = document.getElementById('tablaNotas');
        const tablaClon = tablaOriginal.cloneNode(true);

        // Remover elementos interactivos
        $(tablaClon).find('input, button, .btn-group, .switch-container').remove();

        // Reemplazar inputs con sus valores
        $(tablaClon).find('.nota-input, .conducta-input').each(function() {
            const valor = $(this).val() || '-';
            $(this).replaceWith('<div>' + valor + '</div>');
        });

        // Aplicar formato actual a todos los valores
        $(tablaClon).find('td .font-weight-bold, td div').each(function() {
            const $celda = $(this);
            const texto = $celda.text().trim();
            if (texto !== '-') {
                const valorFormateado = cambiarFormato(texto, formatoActual);
                $celda.text(valorFormateado);
            }
        });

        // Añadir clases de color según valores
        $(tablaClon).find('td .font-weight-bold, td div').each(function() {
            const $celda = $(this);
            const texto = $celda.text().trim();

            // Remover clases existentes
            $celda.removeClass('text-success text-warning text-danger');

            // Aplicar clases según valor
            if (formatoActual === 'cuantitativo') {
                const num = parseFloat(texto);
                if (!isNaN(num)) {
                    if (num >= 3) $celda.addClass('text-success');
                    else if (num === 2) $celda.addClass('text-warning');
                    else if (num === 1) $celda.addClass('text-danger');
                }
            } else {
                if (texto === 'AD' || texto === 'A') $celda.addClass('text-success');
                else if (texto === 'B') $celda.addClass('text-warning');
                else if (texto === 'C') $celda.addClass('text-danger');
            }
        });

        // Crear el contenido del PDF
        doc.write(`
            <div class="header">
                <h1>${titulo}</h1>
                <p>Formato: ${formatoActual === 'cuantitativo' ? 'Cuantitativo (1-4)' : 'Cualitativo (AD, A, B, C)'}</p>
                <p>Generado: ${fecha}</p>
                <hr>
                <div class="leyenda">
                    <strong>Leyenda:</strong>
                    <span class="text-success">${formatoActual === 'cuantitativo' ? '3-4' : 'A-AD'} (Satisfactorio)</span> |
                    <span class="text-warning">${formatoActual === 'cuantitativo' ? '2' : 'B'} (En proceso)</span> |
                    <span class="text-danger">${formatoActual === 'cuantitativo' ? '1' : 'C'} (En inicio)</span>
                </div>
            </div>
        `);

        // Añadir la tabla al documento
        doc.write(tablaClon.outerHTML);

        // Pie de página
        doc.write(`
            <div class="footer">
                <hr>
                <p>Sistema de Gestión Académica - Documento generado automáticamente</p>
            </div>
        `);

        doc.write('</body></html>');
        doc.close();

        // Generar el PDF usando print
        setTimeout(function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();

            // Remover el iframe después de un tiempo
            setTimeout(function() {
                document.body.removeChild(iframe);
            }, 1000);
        }, 500);
    }

    // Asignar la función al botón PDF
    $(document).on('click', '.btn-secondary:contains("PDF")', function() {
        generarPDF();
    });
});
</script>
<script>
$(document).on('click', '#btnExportarExcel', function() {
    // Preguntar por formato
    Swal.fire({
        title: 'Exportar a Excel',
        text: '¿En qué formato desea exportar las notas?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Cuantitativo (1-4)',
        cancelButtonText: 'Cualitativo (AD, A, B, C)',
        showDenyButton: true,
        denyButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel) {
            const formato = result.isConfirmed ? 'cuantitativo' : 'cualitativo';

            // Mostrar carga
            Swal.fire({
                title: 'Generando Excel...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Llamar al servidor
            const url = '{{ route("notas.exportar.excel", ["curso_grado_sec_niv_anio_id" => $curso_id, "bimestre" => $bimestre]) }}' +
                        '?formato=' + formato;

            window.location.href = url;

            // Cerrar el loading después de un tiempo
            setTimeout(() => {
                Swal.close();
            }, 2000);
        }
    });
});
</script>
<style>
    .nota-input {
        width: 60px !important;
        text-align: center;
    }
    .promedio-siagie {
        font-weight: bold;
        min-width: 70px;
        text-align: center;
    }
    .switch-container {
        font-size: 0.9rem;
    }
</style>
@endsection
