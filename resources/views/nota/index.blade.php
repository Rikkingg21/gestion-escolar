@extends('layouts.app')
@section('title', 'Notas')
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-graduation-cap"></i> Registro de Calificaciones
        </h1>

        <!-- Indicador de estado actual -->
        @php
            $estados = [
                '0' => ['Privado', 'secondary'],
                '1' => ['Publicado', 'info'],
                '2' => ['Oficial', 'success'],
                '3' => ['Extra Oficial', 'warning']
            ];

            // Determinar si el usuario actual puede editar
            $puedeEditar = auth()->user()->hasRole('admin') ||
                           auth()->user()->hasRole('director') ||
                           (auth()->user()->hasRole('docente') && in_array($estadoActual, ['0', '1']));
        @endphp
        <span class="badge bg-{{ $estados[$estadoActual][1] }} badge-lg">
            Estado: {{ $estados[$estadoActual][0] }}
        </span>
    </div>

    <!-- Botones de publicación/reversión -->
    <div class="mt-3">
        @php
            $user = auth()->user();
            $esDocenteDelCurso = $user->hasRole('docente') && $docente && $docente->id == ($user->docente->id ?? 0);

            // Lógica para botón de publicación
            $puedePublicar = false;
            $textoBotonPublicar = '';

            if ($user->hasRole('admin') && in_array($estadoActual, ['0', '1', '2'])) {
                $puedePublicar = true;
                if ($estadoActual == '0') $textoBotonPublicar = 'Publicar Notas';
                elseif ($estadoActual == '1') $textoBotonPublicar = 'Marcar como Oficial';
                elseif ($estadoActual == '2') $textoBotonPublicar = 'Marcar como Extra Oficial';
            } elseif ($user->hasRole('director') && in_array($estadoActual, ['0', '1'])) {
                $puedePublicar = true;
                if ($estadoActual == '0') $textoBotonPublicar = 'Publicar Notas';
                elseif ($estadoActual == '1') $textoBotonPublicar = 'Marcar como Oficial';
            } elseif ($esDocenteDelCurso && $estadoActual == '0') {
                $puedePublicar = true;
                $textoBotonPublicar = 'Publicar Notas';
            }
        @endphp

        @if($puedePublicar)
        <form action="{{ route('nota.publicar', ['curso_grado_sec_niv_anio_id' => $curso_id, 'bimestre' => $bimestre]) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning" onclick="return confirm('¿Está seguro de cambiar el estado de las notas?')">
                <i class="fas fa-share-square"></i> {{ $textoBotonPublicar }}
            </button>
        </form>
        @endif

        @if(($user->hasRole('admin') || $user->hasRole('director')) && in_array($estadoActual, ['1', '2', '3']))
        <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#revertirModal">
            <i class="fas fa-undo"></i> Revertir Estado
        </button>
        @endif
    </div><br>

    <!-- Mensajes de éxito/error globales -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>¡Éxito!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>¡Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Por favor corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                {{ $materia->nombre }} - {{ $grado->nombreCompleto }} - Bimestre {{ $bimestre }}
            </h6>
            <small>
                Docente:
                @if($docente && $docente->user)
                    {{ $docente->user->apellido_paterno.' '.
                      $docente->user->apellido_materno.', '.
                      $docente->user->nombre }}
                @else
                    No asignado
                @endif
                | Año: {{ $curso->anio }}
            </small>
        </div>

        <div class="card-body">
            <!-- Mensajes de advertencia según el estado -->
            @if(!$puedeEditar)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Las notas están en estado <strong>{{ $estados[$estadoActual][0] }}</strong>.
                @if(auth()->user()->hasRole('docente'))
                    Solo puede editar en estados Privado y Publicado.
                @else
                    No tiene permisos para editar en este estado.
                @endif
            </div>
            @endif

            <!-- Mensajes específicos para falta de datos -->
            @if($competencias->count() == 0)
            <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i>
                <strong>No hay criterios configurados</strong> para esta materia en el bimestre {{ $bimestre }}.
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('director'))
                    <a href="{{ route('materiacriterio.create') }}?materia_id={{ $materia->id }}&grado_id={{ $grado->id }}&anio={{ $curso->anio }}&bimestre={{ $bimestre }}"
                       class="btn btn-sm btn-primary ms-2">
                        <i class="fas fa-plus me-1"></i> Configurar Criterios
                    </a>
                @endif
            </div>
            @endif

            @if($estudiantesActivos->count() == 0)
            <div class="alert alert-info">
                <i class="fas fa-users me-2"></i>
                <strong>No hay estudiantes activos</strong> en este grado para el año {{ $curso->anio }}.
            </div>
            @endif

            <!-- Pestañas -->
            <ul class="nav nav-tabs" id="notasTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="materia-tab" data-bs-toggle="tab" data-bs-target="#materia" type="button" role="tab">
                        <i class="fas fa-book"></i> Notas de la Materia
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="comportamiento-tab" data-bs-toggle="tab" data-bs-target="#comportamiento" type="button" role="tab">
                        <i class="fas fa-users"></i> Notas de Comportamiento
                    </button>
                </li>
            </ul>

            <!-- Contenido de las pestañas -->
            <div class="tab-content" id="notasTabsContent">
                <!-- Pestaña Notas de la Materia -->
                <div class="tab-pane fade show active" id="materia" role="tabpanel">
                    <form action="{{ route('nota.store') }}" method="POST" id="formNotas">
                        @csrf
                        <input type="hidden" name="curso_id" value="{{ $curso_id }}">
                        <input type="hidden" name="bimestre" value="{{ $bimestre }}">

                        <!-- Mensaje específico para esta pestaña -->
                        @if($competencias->count() == 0 || $estudiantesActivos->count() == 0)
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información importante:</strong>
                            <ul class="mb-0 mt-2">
                                @if($competencias->count() == 0)
                                    <li>No hay criterios de evaluación configurados para este bimestre</li>
                                @endif
                                @if($estudiantesActivos->count() == 0)
                                    <li>No hay estudiantes activos matriculados en este grado</li>
                                @endif
                            </ul>
                        </div>
                        @endif

                        <!-- Tabla para Estudiantes Activos -->
                        @if($estudiantesActivos->count() > 0 && $competencias->count() > 0)
                        <h5 class="mb-3 mt-3 text-success">
                            <i class="fas fa-user-check"></i> Estudiantes Activos
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaNotasActivos">
                                <thead class="table-dark">
                                    <tr>
                                        <th rowspan="2" style="text-align: center;">Estudiante</th>
                                        @foreach($competencias as $competencia)
                                            @if($competencia->criterios && $competencia->criterios->count() > 0)
                                                <th colspan="{{ $competencia->criterios->count() }}">
                                                    {{ $competencia->nombre }}
                                                </th>
                                            @endif
                                        @endforeach
                                        <!-- Nueva columna SIAGIE -->
                                        @php
                                            // Filtrar competencias NO transversales
                                            $competenciasNoTransversales = $competencias->filter(function($competencia) {
                                                return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') === false;
                                            });

                                            // Encontrar la competencia TRANSVERSALES
                                            $competenciaTransversal = $competencias->first(function($competencia) {
                                                return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false;
                                            });

                                            $numCompetenciasNoTransversales = $competenciasNoTransversales->count();
                                            $numCriteriosTransversales = $competenciaTransversal ? $competenciaTransversal->criterios->count() : 0;
                                            $totalColumnasSIAGIE = $numCompetenciasNoTransversales + $numCriteriosTransversales;
                                        @endphp
                                        <th colspan="{{ $totalColumnasSIAGIE }}" style="background-color: #2c3e50; color: white; text-align: center;">
                                            <i class="fas fa-chart-line"></i> SIAGIE
                                        </th>
                                    </tr>
                                    <tr>
                                        @foreach($competencias as $competencia)
                                            @foreach($competencia->criterios ?? [] as $criterio)
                                                <th class="alinear-vertical" title="{{ $criterio->descripcion ?? 'Sin descripción' }}">
                                                    {{ $criterio->nombre }}
                                                    <input type="hidden" name="criterios[]" value="{{ $criterio->id }}">
                                                </th>
                                            @endforeach
                                        @endforeach
                                        <!-- Subcolumnas de SIAGIE - Competencias NO transversales -->
                                        @foreach($competenciasNoTransversales as $competencia)
                                            <th style="background-color: #34495e; color: white;" class="alinear-vertical">
                                                {{ $competencia->nombre }}
                                            </th>
                                        @endforeach
                                        <!-- Subcolumnas de SIAGIE - Criterios transversales -->
                                        @if($competenciaTransversal)
                                            @foreach($competenciaTransversal->criterios ?? [] as $criterio)
                                                <th style="background-color: #34495e; color: white;" class="alinear-vertical">
                                                    {{ $criterio->nombre }}
                                                </th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estudiantesActivos as $estudiante)
                                        <tr>
                                            <td style="white-space: nowrap; min-width: 250px; text-align: left; padding: 8px 15px;">
                                                {{ $estudiante->user->apellido_paterno }}
                                                {{ $estudiante->user->apellido_materno }},
                                                {{ $estudiante->user->nombre }}
                                            </td>
                                            @php
                                                // Inicializar arrays para cálculos
                                                $sumasPorCompetencia = [];
                                                $contadoresPorCompetencia = [];
                                                $notasTransversales = [];

                                                // Inicializar arrays para cada competencia
                                                foreach($competencias as $competencia) {
                                                    $sumasPorCompetencia[$competencia->id] = 0;
                                                    $contadoresPorCompetencia[$competencia->id] = 0;
                                                }

                                                // Inicializar array para notas de cada criterio transversal
                                                if($competenciaTransversal) {
                                                    foreach($competenciaTransversal->criterios as $criterio) {
                                                        $notasTransversales[$criterio->id] = null;
                                                    }
                                                }
                                            @endphp

                                            @foreach($competencias as $competencia)
                                                @foreach($competencia->criterios as $criterio)
                                                    @php
                                                        $key = $estudiante->id.'-'.$criterio->id;
                                                        $notaData = $notasExistentes[$key] ?? null;
                                                        $nota = $notaData['nota'] ?? null;

                                                        if ($nota !== null) {
                                                            // Sumar para promedio de esta competencia
                                                            $sumasPorCompetencia[$competencia->id] += $nota;
                                                            $contadoresPorCompetencia[$competencia->id]++;

                                                            // Almacenar nota de criterio transversal
                                                            if (strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false) {
                                                                $notasTransversales[$criterio->id] = $nota;
                                                            }
                                                        }
                                                    @endphp
                                                    <td>
                                                        <input type="number"
                                                            name="notas[{{ $estudiante->id }}][{{ $criterio->id }}]"
                                                            value="{{ $nota }}"
                                                            min="1"
                                                            max="4"
                                                            step="1"
                                                            class="form-control form-control-sm nota-input"
                                                            {{ !$puedeEditar ? 'readonly' : '' }}
                                                            style="{{ !$puedeEditar ? 'background-color: #f8f9fa;' : '' }}"
                                                            oninput="this.value = this.value.replace(/[^0-4]/g, '').replace(/(\..*)\./g, '$1');"
                                                            data-competencia-id="{{ $competencia->id }}"
                                                            data-criterio-id="{{ $criterio->id }}"
                                                            data-es-transversal="{{ strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false ? 'true' : 'false' }}">
                                                    </td>
                                                @endforeach
                                            @endforeach

                                            <!-- Celdas SIAGIE para promedios de cada competencia NO transversal -->
                                            @foreach($competenciasNoTransversales as $competencia)
                                                @php
                                                    $promedioCompetencia = $contadoresPorCompetencia[$competencia->id] > 0
                                                        ? round($sumasPorCompetencia[$competencia->id] / $contadoresPorCompetencia[$competencia->id], 1)
                                                        : '-';
                                                @endphp
                                                <td style="background-color: #ecf0f1; font-weight: bold; text-align: center;">
                                                    <span class="badge {{ $promedioCompetencia != '-' ? 'bg-primary' : 'bg-secondary' }}" style="font-size: 1em;">
                                                        {{ $promedioCompetencia }}
                                                    </span>
                                                    @if($promedioCompetencia != '-')
                                                        <input type="hidden"
                                                            name="promedios[{{ $estudiante->id }}][competencia_{{ $competencia->id }}]"
                                                            value="{{ $promedioCompetencia }}">
                                                    @endif
                                                </td>
                                            @endforeach

                                            <!-- Celdas SIAGIE para cada criterio de COMPETENCIAS TRANSVERSALES -->
                                            @if($competenciaTransversal)
                                                @foreach($competenciaTransversal->criterios as $criterio)
                                                    <td style="background-color: #ecf0f1; font-weight: bold; text-align: center;">
                                                        @php
                                                            $notaTransversal = $notasTransversales[$criterio->id] ?? null;
                                                        @endphp
                                                        <span class="badge {{ $notaTransversal !== null ? 'bg-success' : 'bg-secondary' }}" style="font-size: 1em;">
                                                            {{ $notaTransversal ?? '-' }}
                                                        </span>
                                                        @if($notaTransversal !== null)
                                                            <input type="hidden"
                                                                name="promedios[{{ $estudiante->id }}][transversal_criterio_{{ $criterio->id }}]"
                                                                value="{{ $notaTransversal }}">
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        <!-- Tabla para Estudiantes Inactivos (solo lectura) -->
                        @if($estudiantesInactivos->count() > 0 && $competencias->count() > 0)
                        <h5 class="mb-3 mt-4 text-secondary">
                            <i class="fas fa-user-times"></i> Estudiantes Inactivos (Solo Lectura)
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaNotasInactivos">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2" style="text-align: center;">Estudiante</th>
                                        @foreach($competencias as $competencia)
                                            @if($competencia->criterios && $competencia->criterios->count() > 0)
                                                <th colspan="{{ $competencia->criterios->count() }}">
                                                    {{ $competencia->nombre }}
                                                </th>
                                            @endif
                                        @endforeach
                                        <!-- Nueva columna SIAGIE -->
                                        @php
                                            // Filtrar competencias NO transversales
                                            $competenciasNoTransversales = $competencias->filter(function($competencia) {
                                                return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') === false;
                                            });

                                            // Encontrar la competencia TRANSVERSALES
                                            $competenciaTransversal = $competencias->first(function($competencia) {
                                                return strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false;
                                            });

                                            $numCompetenciasNoTransversales = $competenciasNoTransversales->count();
                                            $numCriteriosTransversales = $competenciaTransversal ? $competenciaTransversal->criterios->count() : 0;
                                            $totalColumnasSIAGIE = $numCompetenciasNoTransversales + $numCriteriosTransversales;
                                        @endphp
                                        <th colspan="{{ $totalColumnasSIAGIE }}" style="background-color: #7f8c8d; color: white; text-align: center;">
                                            <i class="fas fa-chart-line"></i> SIAGIE
                                        </th>
                                    </tr>
                                    <tr>
                                        @foreach($competencias as $competencia)
                                            @foreach($competencia->criterios ?? [] as $criterio)
                                                <th class="alinear-vertical" title="{{ $criterio->descripcion ?? 'Sin descripción' }}">
                                                    {{ $criterio->nombre }}
                                                </th>
                                            @endforeach
                                        @endforeach
                                        <!-- Subcolumnas de SIAGIE - Competencias NO transversales -->
                                        @foreach($competenciasNoTransversales as $competencia)
                                            <th style="background-color: #95a5a6; color: white;" class="alinear-vertical">
                                                {{ $competencia->nombre }}
                                            </th>
                                        @endforeach
                                        <!-- Subcolumnas de SIAGIE - Criterios transversales -->
                                        @if($competenciaTransversal)
                                            @foreach($competenciaTransversal->criterios ?? [] as $criterio)
                                                <th style="background-color: #95a5a6; color: white;" class="alinear-vertical">
                                                    {{ $criterio->nombre }}
                                                </th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estudiantesInactivos as $estudiante)
                                        <tr class="table-secondary">
                                            <td style="white-space: nowrap; min-width: 250px; text-align: left; padding: 8px 15px;">
                                                <i class="fas fa-lock text-muted me-2"></i>
                                                {{ $estudiante->user->apellido_paterno }}
                                                {{ $estudiante->user->apellido_materno }},
                                                {{ $estudiante->user->nombre }}
                                                <span class="badge bg-warning ms-2">Inactivo</span>
                                            </td>
                                            @php
                                                // Inicializar arrays para cálculos
                                                $sumasPorCompetencia = [];
                                                $contadoresPorCompetencia = [];
                                                $notasTransversales = [];

                                                // Inicializar arrays para cada competencia
                                                foreach($competencias as $competencia) {
                                                    $sumasPorCompetencia[$competencia->id] = 0;
                                                    $contadoresPorCompetencia[$competencia->id] = 0;
                                                }

                                                // Inicializar array para notas de cada criterio transversal
                                                if($competenciaTransversal) {
                                                    foreach($competenciaTransversal->criterios as $criterio) {
                                                        $notasTransversales[$criterio->id] = null;
                                                    }
                                                }
                                            @endphp

                                            @foreach($competencias as $competencia)
                                                @foreach($competencia->criterios as $criterio)
                                                    @php
                                                        $key = $estudiante->id.'-'.$criterio->id;
                                                        $notaData = $notasExistentes[$key] ?? null;
                                                        $nota = $notaData['nota'] ?? null;

                                                        if ($nota !== null) {
                                                            // Sumar para promedio de esta competencia
                                                            $sumasPorCompetencia[$competencia->id] += $nota;
                                                            $contadoresPorCompetencia[$competencia->id]++;

                                                            // Almacenar nota de criterio transversal
                                                            if (strpos(strtoupper($competencia->nombre), 'TRANSVERSAL') !== false) {
                                                                $notasTransversales[$criterio->id] = $nota;
                                                            }
                                                        }
                                                    @endphp
                                                    <td>
                                                        <input type="number"
                                                            value="{{ $nota }}"
                                                            min="1"
                                                            max="4"
                                                            step="1"
                                                            class="form-control form-control-sm"
                                                            readonly
                                                            style="background-color: #f8f9fa; cursor: not-allowed;">
                                                    </td>
                                                @endforeach
                                            @endforeach

                                            <!-- Celdas SIAGIE para promedios de cada competencia NO transversal -->
                                            @foreach($competenciasNoTransversales as $competencia)
                                                @php
                                                    $promedioCompetencia = $contadoresPorCompetencia[$competencia->id] > 0
                                                        ? round($sumasPorCompetencia[$competencia->id] / $contadoresPorCompetencia[$competencia->id], 1)
                                                        : '-';
                                                @endphp
                                                <td style="background-color: #d5dbdb; font-weight: bold; text-align: center;">
                                                    <span class="badge {{ $promedioCompetencia != '-' ? 'bg-secondary' : 'bg-light text-dark' }}" style="font-size: 1em;">
                                                        {{ $promedioCompetencia }}
                                                    </span>
                                                </td>
                                            @endforeach

                                            <!-- Celdas SIAGIE para cada criterio de COMPETENCIAS TRANSVERSALES -->
                                            @if($competenciaTransversal)
                                                @foreach($competenciaTransversal->criterios as $criterio)
                                                    <td style="background-color: #d5dbdb; font-weight: bold; text-align: center;">
                                                        @php
                                                            $notaTransversal = $notasTransversales[$criterio->id] ?? null;
                                                        @endphp
                                                        <span class="badge {{ $notaTransversal !== null ? 'bg-secondary' : 'bg-light text-dark' }}" style="font-size: 1em;">
                                                            {{ $notaTransversal ?? '-' }}
                                                        </span>
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary" {{ !$puedeEditar ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Guardar Calificaciones
                            </button>
                            <a href="{{ route('maya.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Pestaña Notas de Comportamiento -->
                <div class="tab-pane fade" id="comportamiento" role="tabpanel">
                    <form action="{{ route('nota.storeConductaNotas') }}" method="POST" id="formConductaNotas">
                        @csrf
                        <input type="hidden" name="curso_id" value="{{ $curso_id }}">
                        <input type="hidden" name="bimestre" value="{{ $bimestre }}">

                        <!-- Mensaje específico para esta pestaña -->
                        @if($conductas->count() == 0 || $estudiantesActivos->count() == 0)
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información importante:</strong>
                            <ul class="mb-0 mt-2">
                                @if($conductas->count() == 0)
                                    <li>No hay conductas configuradas en el sistema</li>
                                @endif
                                @if($estudiantesActivos->count() == 0)
                                    <li>No hay estudiantes activos matriculados en este grado</li>
                                @endif
                            </ul>
                            @if($conductas->count() == 0 && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('director')))
                                <a href="{{ route('conducta.create') }}" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i> Configurar Conductas
                                </a>
                            @endif
                        </div>
                        @endif

                        <!-- Tabla para Estudiantes Activos - Conducta -->
                        @if($estudiantesActivos->count() > 0 && $conductas->count() > 0)
                        <h5 class="mb-3 mt-3 text-success">
                            <i class="fas fa-user-check"></i> Estudiantes Activos - Conducta
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaConductaActivos">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Estudiante</th>
                                        @foreach($conductas as $conducta)
                                        <th title="{{ $conducta->nombre }}">
                                            {{ $conducta->nombre }}
                                            <input type="hidden" name="conductas[]" value="{{ $conducta->id }}">
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estudiantesActivos as $estudiante)
                                        <tr>
                                            <td>
                                                {{ $estudiante->user->apellido_paterno }}
                                                {{ $estudiante->user->apellido_materno }},
                                                {{ $estudiante->user->nombre }}
                                            </td>
                                            @foreach($conductas as $conducta)
                                                <td>
                                                    @php
                                                        $key = $estudiante->id.'-'.$conducta->id;
                                                        $notaData = $conductaNotas[$key] ?? null;
                                                        $notaConducta = $notaData['nota'] ?? null;
                                                    @endphp
                                                    <input type="number"
                                                        name="notas_conducta[{{ $estudiante->id }}][{{ $conducta->id }}]"
                                                        value="{{ $notaConducta }}"
                                                        min="1"
                                                        max="4"
                                                        step="1"
                                                        class="form-control form-control-sm nota-input"
                                                        {{ !$puedeEditar ? 'readonly' : '' }}
                                                        style="{{ !$puedeEditar ? 'background-color: #f8f9fa;' : '' }}"
                                                        oninput="this.value = this.value.replace(/[^0-4]/g, '').replace(/(\..*)\./g, '$1');">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        <!-- Tabla para Estudiantes Inactivos - Conducta (solo lectura) -->
                        @if($estudiantesInactivos->count() > 0 && $conductas->count() > 0)
                        <h5 class="mb-3 mt-4 text-secondary">
                            <i class="fas fa-user-times"></i> Estudiantes Inactivos - Conducta (Solo Lectura)
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaConductaInactivos">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estudiante</th>
                                        @foreach($conductas as $conducta)
                                        <th title="{{ $conducta->nombre }}">
                                            {{ $conducta->nombre }}
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estudiantesInactivos as $estudiante)
                                        <tr class="table-secondary">
                                            <td>
                                                <i class="fas fa-lock text-muted me-1"></i>
                                                {{ $estudiante->user->apellido_paterno }}
                                                {{ $estudiante->user->apellido_materno }},
                                                {{ $estudiante->user->nombre }}
                                                <span class="badge bg-warning ms-2">Inactivo</span>
                                            </td>
                                            @foreach($conductas as $conducta)
                                                <td>
                                                    @php
                                                        $key = $estudiante->id.'-'.$conducta->id;
                                                        $notaData = $conductaNotas[$key] ?? null;
                                                        $notaConducta = $notaData['nota'] ?? null;
                                                    @endphp
                                                    <input type="number"
                                                        value="{{ $notaConducta }}"
                                                        min="1"
                                                        max="4"
                                                        step="1"
                                                        class="form-control form-control-sm"
                                                        readonly
                                                        style="background-color: #f8f9fa;">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        @if($conductas->count() > 0 && $estudiantesActivos->count() > 0)
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary" {{ !$puedeEditar ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Guardar Notas de Conducta
                            </button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Reversión -->
<div class="modal fade" id="revertirModal" tabindex="-1" aria-labelledby="revertirModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="revertirModalLabel">
                    <i class="fas fa-undo"></i> Revertir Estado de Notas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Advertencia:</strong> Esta acción requiere autenticación con la sesión.
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Estado Actual:</strong>
                        <span class="badge bg-secondary">{{ $estados[$estadoActual][0] }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Nuevo Estado:</strong>
                        <span class="badge bg-info">
                            @if($estadoActual == '3') Oficial
                            @elseif($estadoActual == '2') Publicado
                            @elseif($estadoActual == '1') Privado
                            @else No aplica
                            @endif
                        </span>
                    </div>
                </div>

                @if(session('sessionmain'))
                <form id="revertirForm" action="{{ route('nota.revertir', ['curso_grado_sec_niv_anio_id' => $curso_id, 'bimestre' => $bimestre]) }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="password" class="form-label"><strong>Contraseña *</strong></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" required
                               placeholder="Ingrese la contraseña de {{ session('sessionmain')->nombre_usuario }}">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Contraseña de la sesión principal para autorizar la reversión.
                        </small>
                    </div>
                </form>
                @else
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    No se puede proceder sin una sesión principal activa.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                @if(session('sessionmain'))
                <button type="submit" form="revertirForm" class="btn btn-danger">
                    <i class="fas fa-undo"></i> Confirmar Reversión
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación de inputs de notas - solo formato, no obligatoriedad
    document.querySelectorAll('.nota-input').forEach(input => {
        input.addEventListener('input', function() {
            let value = parseInt(this.value);

            if (isNaN(value)) {
                this.value = '';
                return;
            }

            if (value < 1) {
                this.value = 1;
            } else if (value > 4) {
                this.value = 4;
            } else {
                this.value = Math.floor(value); // Asegura número entero
            }
        });

        // Validación cuando pierde el foco
        input.addEventListener('blur', function() {
            let value = parseInt(this.value);
            if (!isNaN(value) && (value < 1 || value > 4)) {
                this.style.borderColor = 'red';
                this.title = 'La nota debe estar entre 1 y 4';
            } else {
                this.style.borderColor = '';
                this.title = '';
            }
        });
    });

    // Activar las pestañas de Bootstrap
    const triggerTabList = document.querySelectorAll('#notasTabs button');
    triggerTabList.forEach(triggerEl => {
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            const tab = new bootstrap.Tab(this);
            tab.show();
        });
    });

    // Validación mejorada para formularios
    document.getElementById('formNotas')?.addEventListener('submit', function(e) {
        const errores = validarFormularioNotas();
        if (errores.length > 0) {
            e.preventDefault();
            mostrarErrores(errores, 'formNotas');
        } else {
            if(confirm('¿Está seguro de guardar las calificaciones?')) {
                return true;
            } else {
                e.preventDefault();
            }
        }
    });

    document.getElementById('formConductaNotas')?.addEventListener('submit', function(e) {
        const errores = validarFormularioConducta();
        if (errores.length > 0) {
            e.preventDefault();
            mostrarErrores(errores, 'formConductaNotas');
        } else {
            if(confirm('¿Está seguro de guardar las notas de conducta?')) {
                return true;
            } else {
                e.preventDefault();
            }
        }
    });

    function validarFormularioNotas() {
        const errores = [];
        const inputs = document.querySelectorAll('#formNotas .nota-input:not([readonly])');
        let tieneNotas = false;

        inputs.forEach(input => {
            if (input.value !== '') {
                tieneNotas = true;
                let value = parseInt(input.value);
                if (isNaN(value) || value < 1 || value > 4) {
                    input.style.borderColor = 'red';
                    input.title = 'La nota debe estar entre 1 y 4';
                    errores.push('Algunas notas tienen valores fuera del rango permitido (1-4)');
                } else {
                    input.style.borderColor = '';
                    input.title = '';
                }
            }
        });

        if (!tieneNotas) {
            errores.push('Debe ingresar al menos una nota para guardar');
        }

        return [...new Set(errores)]; // Eliminar duplicados
    }

    function validarFormularioConducta() {
        const errores = [];
        const inputs = document.querySelectorAll('#formConductaNotas .nota-input:not([readonly])');
        let tieneNotas = false;

        inputs.forEach(input => {
            if (input.value !== '') {
                tieneNotas = true;
                let value = parseInt(input.value);
                if (isNaN(value) || value < 1 || value > 4) {
                    input.style.borderColor = 'red';
                    input.title = 'La nota debe estar entre 1 y 4';
                    errores.push('Algunas notas de conducta tienen valores fuera del rango permitido (1-4)');
                } else {
                    input.style.borderColor = '';
                    input.title = '';
                }
            }
        });

        if (!tieneNotas) {
            errores.push('Debe ingresar al menos una nota de conducta para guardar');
        }

        return [...new Set(errores)];
    }

    function mostrarErrores(errores, formId) {
        // Remover mensajes de error existentes
        const existingAlerts = document.querySelectorAll(`#${formId} .alert-danger`);
        existingAlerts.forEach(alert => alert.remove());

        // Crear nuevo mensaje de error
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
        errorAlert.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Errores encontrados:</strong>
            <ul class="mb-0 mt-2">
                ${errores.map(error => `<li>${error}</li>`).join('')}
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Insertar antes del botón de guardar
        const submitButton = document.querySelector(`#${formId} button[type="submit"]`);
        submitButton.parentNode.insertBefore(errorAlert, submitButton);

        // Hacer scroll al mensaje de error
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }


});

// Manejo del modal de reversión
document.addEventListener('DOMContentLoaded', function() {
    const revertirModal = document.getElementById('revertirModal');
    if (revertirModal) {
        revertirModal.addEventListener('hidden.bs.modal', function () {
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.value = '';
                passwordInput.classList.remove('is-invalid');
            }
        });

        const revertirForm = document.getElementById('revertirForm');
        if (revertirForm) {
            revertirForm.addEventListener('submit', function(e) {
                const passwordInput = document.getElementById('password');
                if (!passwordInput.value.trim()) {
                    e.preventDefault();
                    passwordInput.classList.add('is-invalid');
                    passwordInput.focus();

                    // Mostrar mensaje de error
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger mt-2';
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>La contraseña es obligatoria';

                    const existingError = passwordInput.parentNode.querySelector('.alert');
                    if (existingError) {
                        existingError.remove();
                    }
                    passwordInput.parentNode.appendChild(errorDiv);
                }
            });
        }

        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const errorAlert = this.parentNode.querySelector('.alert');
                if (errorAlert) {
                    errorAlert.remove();
                }
            });
        }
    }
});
</script>
<script>
    // Calcular promedios en tiempo real
document.querySelectorAll('.nota-input').forEach(input => {
    input.addEventListener('input', function() {
        calcularPromediosCompetencias(this.closest('tr'));
    });
    input.addEventListener('blur', function() {
        calcularPromediosCompetencias(this.closest('tr'));
    });
});

function calcularPromediosCompetencias(row) {
    if (!row) return;

    // Reiniciar arrays para cálculos
    const sumasPorCompetencia = {};
    const contadoresPorCompetencia = {};
    const notasTransversales = {};

    // Obtener todos los inputs de notas en esta fila
    const notaInputs = row.querySelectorAll('.nota-input');

    // Primera pasada: recolectar datos
    notaInputs.forEach(input => {
        const nota = parseFloat(input.value);
        if (!isNaN(nota) && nota >= 1 && nota <= 4) {
            const competenciaId = input.dataset.competenciaId;
            const esTransversal = input.dataset.esTransversal === 'true';
            const criterioId = input.dataset.criterioId;

            // Inicializar arrays si no existen
            if (!sumasPorCompetencia[competenciaId]) {
                sumasPorCompetencia[competenciaId] = 0;
                contadoresPorCompetencia[competenciaId] = 0;
            }

            // Sumar para promedio de competencia
            sumasPorCompetencia[competenciaId] += nota;
            contadoresPorCompetencia[competenciaId]++;

            // Si es transversal, guardar nota individual
            if (esTransversal) {
                notasTransversales[criterioId] = nota;
            }
        }
    });

    // Segunda pasada: actualizar promedios en celdas SIAGIE
    // Obtener el índice de la primera celda SIAGIE
    const totalCriterios = document.querySelectorAll('#tablaNotasActivos thead tr:first-child th:not(:first-child):not([colspan])').length;
    const primeraCeldaSIAGIE = totalCriterios + 1; // +1 por la columna "Estudiante"

    // Actualizar promedios de competencias NO transversales
    let celdaIndex = primeraCeldaSIAGIE;

    // Buscar todas las celdas SIAGIE de competencias
    const headersSIAGIE = document.querySelectorAll('#tablaNotasActivos thead tr:nth-child(2) th');

    headersSIAGIE.forEach((header, index) => {
        if (index >= totalCriterios) { // Esto son las celdas SIAGIE
            const headerText = header.textContent.trim();
            const headerTitle = header.title;

            // Buscar la competencia correspondiente
            let competenciaId = null;
            let esCriterioTransversal = false;
            let criterioIdTransversal = null;

            // Verificar si es competencia NO transversal
            @foreach($competenciasNoTransversales as $competencia)
                if (headerText === '{{ $competencia->nombre }}') {
                    competenciaId = {{ $competencia->id }};
                }
            @endforeach

            // Verificar si es criterio transversal
            @if($competenciaTransversal)
                @foreach($competenciaTransversal->criterios as $criterio)
                    if (headerText === '{{ $criterio->nombre }}') {
                        esCriterioTransversal = true;
                        criterioIdTransversal = {{ $criterio->id }};
                    }
                @endforeach
            @endif

            // Obtener la celda correspondiente
            const celdaSIAGIE = row.cells[primeraCeldaSIAGIE + index - totalCriterios];

            if (celdaSIAGIE) {
                if (competenciaId) {
                    // Es competencia NO transversal - mostrar promedio
                    const suma = sumasPorCompetencia[competenciaId] || 0;
                    const contador = contadoresPorCompetencia[competenciaId] || 0;
                    const promedio = contador > 0 ? (suma / contador).toFixed(1) : '-';

                    const badge = celdaSIAGIE.querySelector('.badge');
                    if (badge) {
                        badge.textContent = promedio;
                        badge.className = 'badge ' + (promedio != '-' ? 'bg-primary' : 'bg-secondary');
                    }

                    // Actualizar hidden input
                    const hiddenInput = celdaSIAGIE.querySelector('input[type="hidden"]');
                    if (hiddenInput && promedio != '-') {
                        hiddenInput.value = promedio;
                    }
                } else if (esCriterioTransversal) {
                    // Es criterio transversal - mostrar nota individual
                    const notaTransversal = notasTransversales[criterioIdTransversal] || null;
                    const valorMostrar = notaTransversal !== null ? notaTransversal : '-';

                    const badge = celdaSIAGIE.querySelector('.badge');
                    if (badge) {
                        badge.textContent = valorMostrar;
                        badge.className = 'badge ' + (notaTransversal !== null ? 'bg-success' : 'bg-secondary');
                    }

                    // Actualizar hidden input
                    const hiddenInput = celdaSIAGIE.querySelector('input[type="hidden"]');
                    if (hiddenInput && notaTransversal !== null) {
                        hiddenInput.value = notaTransversal;
                    }
                }
            }
        }
    });
}

// Inicializar cálculos al cargar
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#tablaNotasActivos tbody tr').forEach(row => {
        calcularPromediosCompetencias(row);
    });
});
</script>
<style>
    .alinear-vertical {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        white-space: normal;
        max-height: 210px;
        padding: 10px;
        text-align: left;
        font-size: 1.2em;
    }
    /* Estilos para inputs */
    .nota-input {
        width: 60px;
        margin: 0 auto;
        text-align: center;
        font-weight: bold;
    }
    /* Ajustes para responsividad */
    @media (max-width: 768px) {
        .alinear-vertical {
            min-height: 120px;
            max-height: 150px;
            width: 25px !important;
            padding: 8px 2px !important;
            font-size: 0.8rem;
        }
    }
</style>

@endsection
