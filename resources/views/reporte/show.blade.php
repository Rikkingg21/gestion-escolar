@extends('layouts.app')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-primary text-white p-4 rounded-top-4">
                <div class="d-flex align-items-center">
                    <!-- Icono de Bootstrap Icons para el título -->
                    <i class="bi bi-file-earmark-text fs-1 me-3"></i>
                    <h1 class="h3 mb-0">Detalles del Reporte</h1>
                </div>
            </div>
            <div class="card-body p-4 p-md-5">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row mb-5">
                    <div class="col-12">
                        <h4 class="mb-3 text-primary">Información del Reporte</h4>
                        <!-- Usamos table-striped y clases de tipografía modernas -->
                        <table class="table table-bordered table-striped align-middle rounded-3 overflow-hidden">
                            <tbody>
                                <tr>
                                    <th scope="row" class="bg-light w-25">Creado por:</th>
                                    <td>{{ $reporte->creador->nombre }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="bg-light">Destinatario:</th>
                                    <td>{{ $reporte->destinatario->apoderado->user->nombre ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="bg-light">Materia:</th>
                                    <td>{{ $reporte->materia->nombre ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="bg-light">Asunto:</th>
                                    <td>{{ $reporte->asunto }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="bg-light">Fecha:</th>
                                    <td>{{ \Carbon\Carbon::parse($reporte->fecha)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="bg-light">Hora:</th>
                                    <td>{{ $reporte->hora }}</td>
                                </tr>
                                <!-- Nueva fila: Creado el -->
                                <tr>
                                    <th scope="row" class="bg-light">Creado el:</th>
                                    <td>{{ \Carbon\Carbon::parse($reporte->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="bg-light">Estado:</th>
                                    <td>
                                        @php
                                            $estado = $reporte->estadoreporte->estado ?? 1;
                                            $estados = [1 => 'Creado', 2 => 'Enviado', 3 => 'Visto', 4 => 'Aceptado'];
                                            // Usamos text-bg-* para Bootstrap 5 badges
                                            $badgeClass = [
                                                1 => 'text-bg-secondary', // Gris
                                                2 => 'text-bg-info',      // Azul claro
                                                3 => 'text-bg-primary',   // Azul oscuro
                                                4 => 'text-bg-success'    // Verde
                                            ];
                                        @endphp
                                        <span class="badge {{ $badgeClass[$estado] }} fw-normal p-2">
                                            {{ $estados[$estado] }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="row pt-4 border-top">
                    <div class="col-md-12">

                        @if(auth()->user()->hasRole('admin') || auth()->user()->id == $reporte->creador_id)
                            <!-- Botón Eliminar con rounded-3 (menos redondeado) y icono BI -->
                            <form action="{{ route('reporte.destroy', $reporte->id) }}" method="POST" class="d-inline me-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-lg rounded-3 px-4"
                                    data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                    <i class="bi bi-trash me-1"></i> Eliminar
                                </button>
                            </form>
                        @endif

                        @if(auth()->user()->id == $reporte->destinatario_id && in_array($reporte->estadoreporte->estado ?? 1, [1, 2, 3]))
                            <!-- Botón Confirmar Recepción con rounded-3 (menos redondeado) y icono BI -->
                            <form action="{{ route('reporte.update', $reporte->id) }}" method="POST" class="d-inline me-2">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="estado" value="4">
                                <button type="submit" class="btn btn-success btn-lg rounded-3 px-4">
                                    <i class="bi bi-check-circle me-1"></i> Confirmar Recepción
                                </button>
                            </form>
                        @endif

                        <!-- Botón Volver con rounded-3 (menos redondeado) y icono BI -->
                        <a href="{{ route('reporte.index') }}" class="btn btn-outline-secondary btn-lg rounded-3 px-4">
                            <i class="bi bi-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
