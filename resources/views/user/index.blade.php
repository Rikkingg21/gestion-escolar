@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <!-- Encabezado estilo roles -->
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center p-3">
                    <h5 class="mb-0 fs-5">
                        <i class="bi bi-people-fill me-2"></i>Administración de Usuarios
                    </h5>

                    <div class="btn-group" role="group" aria-label="Acciones de usuarios">
                        <a href="{{ route('user.create') }}" class="btn btn-light btn-sm text-primary">
                            <i class="bi bi-plus-lg me-1"></i>Nuevo Usuario
                        </a>

                        <a href="{{ route('user.importar') }}" class="btn btn-light btn-sm text-primary">
                            <i class="bi bi-upload me-1"></i>Importar Usuarios
                        </a>
                    </div>
                </div>

                <div class="card-body">
    <!-- Filtros Mejorados -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label fw-bold">Estado:</label>
            <select class="form-select" id="estadoSelect">
                <option value="activos" selected>Activos</option>
                <option value="lectores">Lectores</option>
                <option value="inactivos">Inactivos</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Rol:</label>
            <select class="form-select" id="rolSelect">
                <option value="">Todos los roles</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->nombre }}">{{ $rol->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Grado:</label>
            <select class="form-select" id="gradoSelect">
                <option value="">Todos los grados</option>
                @foreach($grados as $grado)
                    <option value="{{ $grado->id }}">
                        {{ $grado->grado }}° '{{ $grado->seccion }}' - {{ $grado->nivel }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="button" id="btnLimpiarFiltros" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-clockwise me-1"></i>Limpiar Filtros
            </button>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
            <thead class="table-dark">
                <tr>
                    <th>DNI</th>
                    <th>Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Roles</th>
                    <th>Grado</th> <!-- COLUMNA AGREGADA -->
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Los datos se cargarán mediante JavaScript -->
            </tbody>
        </table>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar al usuario <strong id="nombreUsuarioEliminar"></strong>?</p>
                <p class="text-danger mb-0">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        Esta acción no se puede deshacer.
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Variable para almacenar la tabla
    var table;

    // Función para construir la URL con filtros
    function construirUrl(baseRoute) {
        let url = new URL(baseRoute, window.location.origin);

        const rol = $('#rolSelect').val();
        const grado = $('#gradoSelect').val();

        if (rol) url.searchParams.append('rol', rol);
        if (grado) url.searchParams.append('grado', grado);

        return url.toString();
    }

    // Función para inicializar DataTable con una URL específica
    function initDataTable(url) {
        if ($.fn.DataTable.isDataTable('#usersTable')) {
            table.destroy();
            $('#usersTable tbody').empty();
        }

        table = $('#usersTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: url,
                error: function(xhr, error, thrown) {
                    console.log('Error:', xhr.responseText);
                    // Mostrar mensaje de error
                    $('#usersTable tbody').html(
                        '<tr><td colspan="7" class="text-center text-danger">Error al cargar los datos</td></tr>'
                    );
                }
            },
            columns: [
                {
                    data: 'dni',
                    className: 'fw-bold'
                },
                {
                    data: 'nombre_usuario',
                    className: 'fw-bold'
                },
                {
                    data: 'nombre_completo'
                },
                {
                    data: 'roles',
                    render: function(data, type, row) {
                        if (!data) {
                            return '<span class="text-muted fst-italic">Sin roles</span>';
                        }
                        // Convertir los roles en badges
                        return data.split(', ').map(rol =>
                            `<span class="badge bg-secondary me-1">${rol}</span>`
                        ).join('');
                    }
                },
                {
                    data: 'grado',
                    render: function(data, type, row) {
                        if (!data || data === 'Sin grado') {
                            return '<span class="text-muted fst-italic">Sin grado</span>';
                        }
                        return `<span class="badge bg-info">${data}</span>`;
                    }
                },
                {
                    data: 'estado',
                    render: function(data, type, row) {
                        var classes = {
                            'Activo': 'badge bg-success',
                            'Lector': 'badge bg-info',
                            'Inactivo': 'badge bg-danger'
                        };
                        var icon = {
                            'Activo': 'bi-check-circle',
                            'Lector': 'bi-eye',
                            'Inactivo': 'bi-x-circle'
                        };
                        return `<span class="${classes[data]}">
                                <i class="bi ${icon[data]} me-1"></i>${data}
                            </span>`;
                    }
                },
                {
                    data: 'acciones',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        // Solo mostrar el botón de editar que viene del controlador
                        return data;
                    }
                }
            ],
            language: {
                "decimal": "",
                "emptyTable": `
                    <div class="text-center py-4">
                        <i class="bi bi-people display-4 text-muted mb-3"></i>
                        <h5 class="text-muted">No hay usuarios registrados</h5>
                        <p class="text-muted">Comienza creando un nuevo usuario</p>
                    </div>
                `,
                "info": "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
                "infoEmpty": "Mostrando 0 de 0 usuarios",
                "infoFiltered": "(filtrado de _MAX_ usuarios totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ usuarios",
                "loadingRecords": `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando usuarios...</p>
                    </div>
                `,
                "processing": "Procesando...",
                "search": '<i class="bi bi-search me-1"></i>Buscar:',
                "zeroRecords": "No se encontraron usuarios coincidentes",
                "paginate": {
                    "first": '<i class="bi bi-chevron-double-left"></i>',
                    "last": '<i class="bi bi-chevron-double-right"></i>',
                    "next": '<i class="bi bi-chevron-right"></i>',
                    "previous": '<i class="bi bi-chevron-left"></i>'
                }
            },
            drawCallback: function(settings) {
                // Inicializar tooltips después de cada dibujo de la tabla
                $('[data-bs-toggle="tooltip"]').tooltip();

                // Agregar icono al botón de editar
                $('.btn-warning').each(function() {
                    if (!$(this).find('i').length) {
                        $(this).prepend('<i class="bi bi-pencil-square me-1"></i>');
                    }
                });

                // Agregar eventos a botones de eliminar si existen
                $('.btn-eliminar').off('click').on('click', function() {
                    const userId = $(this).data('user-id');
                    const userName = $(this).data('user-name');

                    $('#nombreUsuarioEliminar').text(userName);
                    $('#formEliminar').attr('action', '/usuarios/' + userId);
                    $('#modalEliminar').modal('show');
                });
            }
        });
    }

    // Función para obtener la ruta base según el estado
    function obtenerRutaBase(estado) {
        switch(estado) {
            case 'activos': return '{{ route("usuarios.activos") }}';
            case 'lectores': return '{{ route("usuarios.lectores") }}';
            case 'inactivos': return '{{ route("usuarios.inactivos") }}';
            default: return '{{ route("usuarios.activos") }}';
        }
    }

    // Función para actualizar la tabla con todos los filtros
    function actualizarTabla() {
        const estado = $('#estadoSelect').val();
        const rutaBase = obtenerRutaBase(estado);
        const urlFinal = construirUrl(rutaBase);

        initDataTable(urlFinal);
    }

    // Inicializar con usuarios activos por defecto
    actualizarTabla();

    // Manejar cambios en los filtros
    $('#estadoSelect').change(actualizarTabla);
    $('#rolSelect').change(actualizarTabla);
    $('#gradoSelect').change(actualizarTabla);

    // Limpiar filtros
    $('#btnLimpiarFiltros').click(function() {
        $('#rolSelect').val('');
        $('#gradoSelect').val('');
        $('#estadoSelect').val('activos');
        actualizarTabla();
    });

    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Cerrar modal al hacer click en cancelar
    $('#modalEliminar .btn-secondary').click(function() {
        $('#modalEliminar').modal('hide');
    });
});
</script>
@endsection
