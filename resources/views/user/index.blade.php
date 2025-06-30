@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-people-fill"></i> Administración de Usuarios
        </h1>

        @can('create', App\Models\User::class)
        <a href="{{ route('user.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Usuario
        </a>
        @endcan
    </div>

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#home" aria-selected="true" role="tab">Usuarios</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#profile" aria-selected="false" tabindex="-1" role="tab">Directores</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact" aria-selected="false" tabindex="-1" role="tab">Auxiliares</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact2" aria-selected="false" tabindex="-1" role="tab">Docentes</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact3" aria-selected="false" tabindex="-1" role="tab">Estudiantes</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#contact4" aria-selected="false" tabindex="-1" role="tab">Apoderados</a>
        </li>
    </ul>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane fade show active" id="home" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <fieldset>
                            <legend class="mt-4">Estado</legend>
                            <select class="form-select w-auto" id="estadoSelect">
                                <option value="activos" selected>Activos</option>
                                <option value="lectores">Lectores</option>
                                <option value="inactivos">Inactivos</option>
                            </select>
                        </fieldset>
                        <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                            <thead class="table-dark">
                                <tr>
                                    <th>DNI</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Roles</th> <!-- Cambiado de "Rol" a "Roles" -->
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center">

                        </div>
                    </div>
                </div>
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
                { data: 'usuario' },
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
                url: '//cdn.datatables.net/plug-ins/2.3.2/i18n/es-ES.json',
            }
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
