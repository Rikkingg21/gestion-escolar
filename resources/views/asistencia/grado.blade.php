@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>
            Asistencia: {{ $grado->grado }}° {{ $grado->seccion }} - {{ $grado->nivel }}
            <small class="text-muted">Fecha: {{ $fechaSeleccionada }}</small>
        </h3>
        <a href="{{ route('asistencia.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    <form method="GET" action="" class="d-flex align-items-center">
        @csrf
        <div class="input-group me-3">
            <span class="input-group-text">Fecha:</span>
            <input type="date"
                name="fecha"
                class="form-control"
                value="{{ $fechaSeleccionada }}"
                onchange="this.form.submit()">
        </div>

    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('asistencia.store') }}">
                @csrf
                <input type="hidden" name="grado_id" value="{{ $grado->id }}">
                <input type="hidden" name="fecha" value="{{ $fechaFormateada }}">

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Estudiante</th>
                                <th>Tipo Asistencia</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($estudiantes as $estudiante)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $estudiante->user->apellido_paterno ?? '' }}
                                    {{ $estudiante->user->apellido_materno ?? '' }}
                                    {{ $estudiante->user->nombre ?? '' }}
                                </td>
                                <td>
                                    <select name="asistencias[{{ $estudiante->id }}]" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($tiposAsistencia as $tipo)
                                        <option value="{{ $tipo->id }}"
                                            @if($estudiante->asistencias->isNotEmpty())
                                                selected
                                            @elseif($tipo->id == 5)
                                                selected
                                            @endif>
                                            {{ $tipo->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="time"
                                           name="horas[{{ $estudiante->id }}]"
                                           class="form-control"
                                           value="{{ $estudiante->asistencias->isNotEmpty() ? $estudiante->asistencias->first()->hora : now()->format('H:i') }}">
                                </td>
                                <td>
                                    @if($estudiante->asistencias->isNotEmpty())
                                        <span class="badge bg-success">Registrado</span>
                                    @else
                                        <span class="badge bg-warning">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No hay estudiantes activos en este grado</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Asistencias
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
