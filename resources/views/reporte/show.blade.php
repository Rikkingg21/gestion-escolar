@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-alt"></i> Detalles del Reporte
            </h1>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>Información del Reporte</h4>
                    <table class="table table-bordered">
                        <tr>
                            <th>Creado por:</th>
                            <td>{{ $reporte->creador->nombre }}</td>
                        </tr>
                        <tr>
                            <th>Destinatario:</th>
                            <td>{{ $reporte->destinatario->apoderado->user->nombre ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Materia:</th>
                            <td>{{ $reporte->materia->nombre ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Asunto:</th>
                            <td>{{ $reporte->asunto }}</td>
                        </tr>
                        <tr>
                            <th>Fecha:</th>
                            <td>{{ $reporte->fecha }}</td>
                        </tr>
                        <tr>
                            <th>Hora:</th>
                            <td>{{ $reporte->hora }}</td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                @php
                                    $estado = $reporte->estadoreporte->estado ?? 1;
                                    $estados = [1 => 'Creado', 2 => 'Enviado', 3 => 'Visto', 4 => 'Aceptado'];
                                    $badgeClass = [
                                        1 => 'bg-secondary',
                                        2 => 'bg-info',
                                        3 => 'bg-primary',
                                        4 => 'bg-success'
                                    ];
                                @endphp
                                <span class="badge {{ $badgeClass[$estado] }}">
                                    {{ $estados[$estado] }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="row">
                <div class="col-md-12">

                    @if(auth()->user()->hasRole('admin') || auth()->user()->id == $reporte->creador_id)
                        <!-- Botones para creador/admin -->

                        <form action="{{ route('reporte.destroy', $reporte->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este reporte?')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </form>
                    @endif

                    @if(auth()->user()->id == $reporte->destinatario_id && in_array($reporte->estadoreporte->estado ?? 1, [1, 2, 3]))
                        <!-- Botón para destinatario -->
                        <form action="{{ route('reporte.update', $reporte->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="estado" value="4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Confirmar Recepción
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('reporte.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
