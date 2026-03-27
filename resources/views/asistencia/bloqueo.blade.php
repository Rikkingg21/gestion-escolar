@extends('layouts.app')
@section('title', 'Bloquear Asistencia')
@section('content')
    <div class="container-fluid mt-4">
        <h2><i class="fas fa-lock"></i> Bloqueo de Asistencias</h2>

        <!-- Formulario de filtro -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('bloqueo.view') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="periodo_id" class="form-label">Periodo *</label>
                        <select name="periodo_id" id="periodo_id" class="form-select" required>
                            <option value="">Seleccionar Periodo</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}"
                                    {{ $periodoSeleccionado == $periodo->id ? 'selected' : '' }}>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="mes" class="form-label">Mes *</label>
                        <select name="mes" id="mes" class="form-select" required>
                            <option value="">Seleccionar Mes</option>
                            @foreach($meses as $key => $mes)
                                <option value="{{ $key }}"
                                    {{ $mesSeleccionado == $key ? 'selected' : '' }}>
                                    {{ $mes }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="grado_id" class="form-label">Grado (Opcional)</label>
                        <select name="grado_id" id="grado_id" class="form-select">
                            <option value="">Todos los Grados</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}"
                                    {{ $gradoSeleccionado == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->grado }}° "{{ $grado->seccion }}" - {{ $grado->nivel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar Asistencias
                        </button>
                        <a href="{{ route('bloqueo.view') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Limpiar Filtros
                        </a>

                        @if($asistencias->count() > 0)
                        <div class="float-end">
                            <span class="badge bg-info p-2">
                                <i class="fas fa-clipboard-list"></i>
                                Total: {{ $asistencias->count() }} registros
                            </span>
                        </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de resultados -->
        @if($periodoSeleccionado && $mesSeleccionado)
            <div class="card">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i>
                        Asistencias del Mes de
                        @if(isset($meses[$mesSeleccionado]))
                            {{ $meses[$mesSeleccionado] }}
                        @endif
                    </h5>

                    <!-- Botones de acciones masivas -->
                    @if($asistencias->count() > 0)
                    <div class="btn-group" role="group" aria-label="Acciones masivas">
                        <!-- Bloquear todos los libres -->
                        <button type="button" class="btn btn-warning btn-sm"
                                data-bs-toggle="modal" data-bs-target="#modalBloquearMasivo">
                            <i class="fas fa-lock"></i> Bloquear Libres ({{ $asistencias->where('estado', 0)->count() }})
                        </button>

                        <!-- Liberar todos los bloqueados temporales -->
                        <button type="button" class="btn btn-success btn-sm"
                                data-bs-toggle="modal" data-bs-target="#modalLiberarMasivo">
                            <i class="fas fa-unlock"></i> Liberar ({{ $asistencias->where('estado', 1)->count() }})
                        </button>

                        <!-- Bloquear definitivamente todos los temporales -->
                        <button type="button" class="btn btn-danger btn-sm"
                                data-bs-toggle="modal" data-bs-target="#modalBloquearDefMasivo">
                            <i class="fas fa-lock"></i> Bloquear Def. ({{ $asistencias->where('estado', 1)->count() }})
                        </button>

                        <!-- Liberar de bloqueo definitivo a temporal -->
                        <button type="button" class="btn btn-info btn-sm"
                                data-bs-toggle="modal" data-bs-target="#modalLiberarDefMasivo">
                            <i class="fas fa-unlock-alt"></i> Liberar Def. ({{ $asistencias->where('estado', 2)->count() }})
                        </button>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    @if($asistencias->count() > 0)
                        <!-- Contadores de estado -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body text-center">
                                        <h6><i class="fas fa-check-circle"></i> Libres</h6>
                                        <h3>{{ $asistencias->where('estado', 0)->count() }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body text-center">
                                        <h6><i class="fas fa-lock"></i> Bloqueados</h6>
                                        <h3>{{ $asistencias->where('estado', 1)->count() }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-danger">
                                    <div class="card-body text-center">
                                        <h6><i class="fas fa-lock"></i> Bloqueado Def.</h6>
                                        <h3>{{ $asistencias->where('estado', 2)->count() }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-secondary text-white">
                                    <div class="card-body text-center">
                                        <h6><i class="fas fa-total"></i> Total</h6>
                                        <h3>{{ $asistencias->count() }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="tablaAsistencias">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Estudiante</th>
                                        <th>DNI</th>
                                        <th>Grado</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Tipo Asistencia</th>
                                        <th>Bimestre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($asistencias as $index => $asistencia)
                                        @php
                                            $estadoClase = '';
                                            if($asistencia->estado == 0) {
                                                $estadoClase = 'estado-libre';
                                            } elseif($asistencia->estado == 1) {
                                                $estadoClase = 'estado-bloqueado';
                                            } elseif($asistencia->estado == 2) {
                                                $estadoClase = 'estado-bloqueado-def';
                                            }
                                        @endphp

                                        <tr class="{{ $estadoClase }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>
                                                    {{ optional($asistencia->estudiante->user)->nombre ?? 'N/A' }}
                                                    {{ optional($asistencia->estudiante->user)->apellido_paterno ?? '' }}
                                                    {{ optional($asistencia->estudiante->user)->apellido_materno ?? '' }}
                                                </strong>
                                            </td>
                                            <td>{{ optional($asistencia->estudiante->user)->dni ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    {{ $asistencia->grado->grado ?? 'N/A' }}°
                                                    "{{ $asistencia->grado->seccion ?? '' }}" -
                                                    {{ $asistencia->grado->nivel ?? '' }}
                                                </span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}</td>
                                            <td>{{ date('h:i A', strtotime($asistencia->hora)) }}</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ $asistencia->tipoasistencia->nombre ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ $asistencia->bimestre ?? 'N/A' }}</td>
                                            <td>
                                                <small>{{ $asistencia->descripcion ?? 'Sin descripción' }}</small>
                                            </td>
                                            <td>
                                                @if($asistencia->estado == 0)
                                                    <span class="badge-estado bg-success text-white">
                                                        <i class="fas fa-unlock"></i> Libre
                                                    </span>
                                                @elseif($asistencia->estado == 1)
                                                    <span class="badge-estado bg-warning text-dark">
                                                        <i class="fas fa-lock"></i> Bloqueado
                                                    </span>
                                                @elseif($asistencia->estado == 2)
                                                    <span class="badge-estado bg-danger text-white">
                                                        <i class="fas fa-lock"></i> Bloqueado Def.
                                                    </span>
                                                @else
                                                    <span class="badge-estado bg-secondary">Desconocido</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-2x"></i>
                            <h4>No se encontraron asistencias</h4>
                            <p>No hay registros de asistencia para los filtros seleccionados.</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <h4>Seleccione filtros</h4>
                <p>Por favor, seleccione un periodo y un mes para visualizar las asistencias.</p>
            </div>
        @endif
    </div>

    <!-- Modales para confirmar acciones masivas -->

    <!-- Modal: Bloquear Libres -->
    <div class="modal fade" id="modalBloquearMasivo" tabindex="-1" aria-labelledby="modalBloquearMasivoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="modalBloquearMasivoLabel">
                        <i class="fas fa-lock"></i> Bloquear Asistencias Libres
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea bloquear todas las asistencias <strong>libres</strong>?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Esta acción afectará a
                        <strong>{{ $asistencias->where('estado', 0)->count() }}</strong> registros.
                    </p>
                    <form id="formBloquearMasivo" action="{{ route('asistencia.bloquear-masivo') }}" method="POST">
                        @csrf
                        <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                        <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                        @if($gradoSeleccionado)
                            <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                        @endif
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formBloquearMasivo" class="btn btn-warning">
                        <i class="fas fa-lock"></i> Sí, Bloquear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Liberar Bloqueados Temporales -->
    <div class="modal fade" id="modalLiberarMasivo" tabindex="-1" aria-labelledby="modalLiberarMasivoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalLiberarMasivoLabel">
                        <i class="fas fa-unlock"></i> Liberar Asistencias Bloqueadas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea liberar todas las asistencias <strong>bloqueadas temporalmente</strong>?</p>
                    <p class="text-info">
                        <i class="fas fa-info-circle"></i> Esta acción afectará a
                        <strong>{{ $asistencias->where('estado', 1)->count() }}</strong> registros.
                    </p>
                    <form id="formLiberarMasivo" action="{{ route('asistencia.liberar-masivo') }}" method="POST">
                        @csrf
                        <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                        <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                        @if($gradoSeleccionado)
                            <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                        @endif
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formLiberarMasivo" class="btn btn-success">
                        <i class="fas fa-unlock"></i> Sí, Liberar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Bloquear Definitivamente -->
    <div class="modal fade" id="modalBloquearDefMasivo" tabindex="-1" aria-labelledby="modalBloquearDefMasivoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalBloquearDefMasivoLabel">
                        <i class="fas fa-lock"></i> Bloquear Definitivamente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea bloquear <strong>definitivamente</strong> todas las asistencias bloqueadas temporalmente?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Esta acción es <strong>IRREVERSIBLE</strong> y afectará a
                        <strong>{{ $asistencias->where('estado', 1)->count() }}</strong> registros.
                    </p>
                    <form id="formBloquearDefMasivo" action="{{ route('asistencia.bloquear-definitivo-masivo') }}" method="POST">
                        @csrf
                        <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                        <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                        @if($gradoSeleccionado)
                            <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                        @endif
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formBloquearDefMasivo" class="btn btn-danger">
                        <i class="fas fa-lock"></i> Sí, Bloquear Definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Liberar de Bloqueo Definitivo -->
    <div class="modal fade" id="modalLiberarDefMasivo" tabindex="-1" aria-labelledby="modalLiberarDefMasivoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalLiberarDefMasivoLabel">
                        <i class="fas fa-unlock-alt"></i> Liberar de Bloqueo Definitivo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea liberar de <strong>bloqueo definitivo</strong> a bloqueo temporal?</p>
                    <p class="text-info">
                        <i class="fas fa-info-circle"></i> Esta acción afectará a
                        <strong>{{ $asistencias->where('estado', 2)->count() }}</strong> registros.
                    </p>

                    <!-- Formulario de validación -->
                    <div class="mb-3">
                        <label for="usuario_id" class="form-label">Usuario Autorizador *</label>
                        <select name="usuario_id" id="usuario_id" class="form-select" required>
                            <option value="">Seleccionar Usuario</option>
                            @foreach($usuariosAutorizados as $usuario)
                                <option value="{{ $usuario->id }}">
                                    {{ $usuario->nombre }} {{ $usuario->apellido_paterno }}
                                    ({{ $usuario->nombre_usuario }}) -
                                    {{ $usuario->roles->pluck('nombre')->implode(', ') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña *</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <small class="text-muted">Ingrese la contraseña del usuario seleccionado</small>
                    </div>

                    <form id="formLiberarDefMasivo" action="{{ route('asistencia.liberar-definitivo-masivo') }}" method="POST">
                        @csrf
                        <input type="hidden" name="periodo_id" value="{{ $periodoSeleccionado }}">
                        <input type="hidden" name="mes" value="{{ $mesSeleccionado }}">
                        <input type="hidden" name="grado_id" value="{{ $gradoSeleccionado }}">
                        <input type="hidden" name="usuario_autorizador_id" id="usuario_autorizador_id">
                        <input type="hidden" name="password_confirmation" id="password_confirmation">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnConfirmarLiberarDef" class="btn btn-info">
                        <i class="fas fa-unlock-alt"></i> Confirmar y Liberar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Manejar la confirmación para liberar definitivo
            $('#btnConfirmarLiberarDef').click(function() {
                const usuarioId = $('#usuario_id').val();
                const password = $('#password').val();

                if (!usuarioId || !password) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Faltan datos',
                        text: 'Por favor, seleccione un usuario e ingrese su contraseña.',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }

                // Setear los valores en el formulario oculto
                $('#usuario_autorizador_id').val(usuarioId);
                $('#password_confirmation').val(password);

                // Confirmar antes de enviar
                Swal.fire({
                    title: 'Confirmar Acción',
                    text: '¿Está seguro de liberar estas asistencias de bloqueo definitivo? Esta acción requiere autorización especial.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0dcaf0',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, liberar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#formLiberarDefMasivo').submit();
                    }
                });
            });

            // Limpiar campos al cerrar el modal
            $('#modalLiberarDefMasivo').on('hidden.bs.modal', function () {
                $('#usuario_id').val('');
                $('#password').val('');
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTable con traducción local
            @if($asistencias->count() > 0)
                $('#tablaAsistencias').DataTable({
                    "language": {
                        "lengthMenu": "Mostrar _MENU_ registros por página",
                        "zeroRecords": "No se encontraron resultados",
                        "info": "Mostrando página _PAGE_ de _PAGES_",
                        "infoEmpty": "No hay registros disponibles",
                        "infoFiltered": "(filtrado de _MAX_ registros totales)",
                        "search": "Buscar:",
                        "paginate": {
                            "first": "Primera",
                            "last": "Última",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    "pageLength": 25,
                    "order": [[3, 'asc'], [5, 'asc']],
                    "responsive": true,
                    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
                });
            @endif

            // Validación del formulario de búsqueda
            $('form[action="{{ route('bloqueo.view') }}"]').submit(function(e) {
                const periodo = $('#periodo_id').val();
                const mes = $('#mes').val();

                if (!periodo || !mes) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Faltan datos',
                        text: 'Por favor, seleccione tanto el periodo como el mes.',
                        confirmButtonText: 'Entendido'
                    });
                }
            });

            // Confirmación adicional para acciones peligrosas
            $('#formBloquearDefMasivo').submit(function(e) {
                e.preventDefault();

                Swal.fire({
                    title: '¡ALERTA!',
                    text: '¿Está ABSOLUTAMENTE seguro de bloquear definitivamente estas asistencias? Esta acción NO se puede deshacer fácilmente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, bloquear definitivamente',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });

            // Mostrar mensajes de sesión con SweetAlert
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'Aceptar'
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'Aceptar'
                });
            @endif

            @if(session('info'))
                Swal.fire({
                    icon: 'info',
                    title: 'Información',
                    text: '{{ session('info') }}',
                    confirmButtonText: 'Aceptar'
                });
            @endif
        });
    </script>
@endsection
