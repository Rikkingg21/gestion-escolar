@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Registro de Asistencias</h3>
    </div>

    <!-- Selector de años -->
    <form method="GET" action="{{ route('asistencia.index') }}" class="mb-4">
        <div class="input-group" style="max-width: 300px;">
            <select name="year" class="form-select" onchange="this.form.submit()">
                <option value="{{ now()->year }}" {{ $currentYear == now()->year ? 'selected' : '' }}>
                    Año Actual ({{ now()->year }})
                </option>
                @foreach($availableYears as $year)
                    @if($year != now()->year)
                    <option value="{{ $year }}" {{ $currentYear == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                    @endif
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Grado/Sección</th>
                            <th>Nivel</th>
                            <th>Estado</th>
                            <th>Asistencias Registradas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grados as $grado)
                        <tr>
                            <td>{{ $grado->grado }}° "{{ $grado->seccion }}"</td>
                            <td>{{ $grado->nivel }}</td>
                            <td>
                                @if($grado->estado == 1)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($grado->asistencias_count))
                                    <span class="badge bg-primary">{{ $grado->asistencias_count }}</span>
                                @else
                                    <span class="badge bg-warning">0</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('asistencia.grado', [
                                    'grado_grado_seccion' => $grado->grado . $grado->seccion, // Ej: "1a"
                                    'grado_nivel' => strtolower($grado->nivel), // Ej: "secundaria"
                                    'date' => now()->format('d-m-Y') // Fecha actual
                                ]) }}" class="btn btn-primary">
                                    Tomar Asistencia
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No hay grados disponibles</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
