@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
        <!-- Encabezado -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4 page-header">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-people-fill"></i> Administración de Usuarios
            </h1>

            <a href="{{ route('user.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Nuevo Usuario
            </a>
        </div>

        <!-- Tarjeta de contenido -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <!-- Selector de estado -->
                    <fieldset class="mb-3">
                        <legend class="h6 mt-4">Filtrar por estado:</legend>
                        <select class="form-select w-auto" id="estadoSelect">
                            <option value="activos" selected>Activos</option>
                            <option value="lectores">Lectores</option>
                            <option value="inactivos">Inactivos</option>
                        </select>
                    </fieldset>

                    <!-- Tabla de usuarios -->
                    <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th>DNI</th>
                                <th>Usuario</th>
                                <th>Nombre Completo</th>
                                <th>Roles</th>
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
<script>
$(document).ready(function() {
    // Variable para almacenar la tabla
    var table;

    // Función para inicializar DataTable con una URL específica
    function initDataTable(url) {
        if ($.fn.DataTable.isDataTable('#usersTable')) {
            table.destroy();
        }

        table = $('#usersTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: url,
                error: function(xhr, error, thrown) {
                    console.log('Error:', xhr.responseText);
                }
            },
            columns: [
                { data: 'dni' },
                { data: 'nombre_usuario' },
                { data: 'nombre_completo' },
                { data: 'roles' },
                {
                    data: 'estado',
                    render: function(data, type, row) {
                        var classes = {
                            'Activo': 'badge bg-success',
                            'Lector': 'badge bg-info',
                            'Inactivo': 'badge bg-danger'
                        };
                        return '<span class="'+classes[data]+'">'+data+'</span>';
                    }
                },
                {
                    data: 'acciones',
                    orderable: false,
                    searchable: false
                }
            ],
            language: {
                "decimal": "",
                "emptyTable": "No hay información",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
                "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
                "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ Entradas",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "Sin resultados encontrados",
                "paginate": {
                    "first": "Primero",
                    "last": "Ultimo",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
        });
    }

    // Inicializar con usuarios activos por defecto
    initDataTable('{{ route("usuarios.activos") }}');

    // Manejar cambios en el select
    $('#estadoSelect').change(function() {
        var route;
        switch($(this).val()) {
            case 'activos':
                route = '{{ route("usuarios.activos") }}';
                break;
            case 'lectores':
                route = '{{ route("usuarios.lectores") }}';
                break;
            case 'inactivos':
                route = '{{ route("usuarios.inactivos") }}';
                break;
        }

        initDataTable(route);
    });
});
</script>
@endsection
