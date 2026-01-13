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
                        <input type="radio" class="btn-check" name="btnradio" id="btnculitativo" autocomplete="off" checked="">
                        <label class="btn btn-outline-primary" for="btnculitativo">Cualitativo</label>
                        <input type="radio" class="btn-check" name="btnradio" id="btncuantitativo" autocomplete="off" checked="">
                        <label class="btn btn-outline-primary" for="btncuantitativo">Cuantitativo</label>
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
                                <input type="number"
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
