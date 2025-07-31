@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-file-alt"></i> Reportes Generales</h1>
    @if(auth()->check())
        @if(session('current_role') == 'admin' || session('current_role') == 'director' || session('current_role') == 'auxiliar' || session('current_role') == 'docente')
            <a href="{{ route('reporte.create') }}" class="btn btn-primary mb-3">Crear Reporte</a>
        @endif
    @else
        <div class="text-muted"></div>
    @endif
    <div class="card shadow mb-4">
        <div class="card-body">
            @if(auth()->check())
                @if(session('current_role'))
                    <div><strong>Rol:</strong> <span class="badge bg-warning text-dark">{{ session('current_role') }}</span></div>
                @endif
            @else
                <div class="text-muted">No hay sesión sub activa.</div>
            @endif
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Creador</th>
                        <th>Destinatario (Apoderado)</th>
                        <th>Materia</th>
                        <th>Asunto</th>
                        <th>Citacion fecha y hora</th>
                        <th>Estado</th>
                        <th>Accioness</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportes as $reporte)
                    <tr>
                        <td>{{ $reporte->creador->nombre }}</td>
                        <td>{{ $reporte->destinatario->apoderado->user->nombre ?? '-' }}</td>
                        <td>{{ $reporte->materia->nombre ?? '-' }}</td>
                        <td>{{ $reporte->asunto }}</td>
                        <td>{{ $reporte->fecha }} -- {{ $reporte->hora }}</td>
                        <td>
                            @php
                                $estado = $reporte->estadoreporte->estado ?? 1;
                                $estados = [1 => 'Creado', 2 => 'Enviado', 3 => 'Visto', 4 => 'Aceptado'];
                            @endphp
                            {{ $estados[$estado] }}
                        </td>
                        <td>
                            <a href="{{ route('reporte.show', $reporte->id) }}" class="btn btn-info btn-sm">Ver</a>
                            @if($estado == 1 && (auth()->user()->id == $reporte->creador_id || auth()->user()->hasRole('admin')))
                                <form action="{{ route('reporte.destroy', $reporte->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este reporte?')">
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
