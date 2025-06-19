@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-0 text-gray-800">Maya Dashboard</h1>

    <form action="{{ route('mayas.dashboard') }}" method="GET" class="my-3">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="maya_id" class="form-label">Seleccione una Maya:</label>
                <select name="maya_id" id="maya_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Todas las Mayas --</option>
                    @foreach($mayas as $maya)
                        <option value="{{ $maya->id }}" {{ $selectedMayaId == $maya->id ? 'selected' : '' }}>
                            {{ $maya->materia->nombre ?? 'N/A' }} -
                            {{ $maya->grado->grado ?? 'N/A' }} {{ $maya->grado->seccion ?? '' }} ({{ $maya->anio }}) -
                            Docente: {{ $maya->docente && $maya->docente->user ? $maya->docente->user->nombre . ' ' . $maya->docente->user->apellido_paterno : 'Sin docente' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if($selectedMaya)
        <h2>Detalles de la Maya Seleccionada:</h2>
        <p><strong>Materia:</strong> {{ $selectedMaya->materia->nombre ?? 'N/A' }}</p>
        <p><strong>Grado:</strong> {{ $selectedMaya->grado->grado ?? 'N/A' }} {{ $selectedMaya->grado->seccion ?? '' }} - Nivel: {{ $selectedMaya->grado->nivel ?? '' }}</p>
        <p><strong>Año:</strong> {{ $selectedMaya->anio }}</p>
        <p><strong>Docente:</strong> {{ $selectedMaya->docente && $selectedMaya->docente->user ? $selectedMaya->docente->user->nombre . ' ' . $selectedMaya->docente->user->apellido_paterno : 'Sin docente' }}</p>

        {{-- Placeholder for Bimestres and other components --}}
        <div id="bimestres-container">
            @if($selectedMaya)
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Bimestres de la Maya</h6>
            </div>
            <div class="card-body">
                @if($selectedMaya->bimestres && $selectedMaya->bimestres->count() > 0)
                    <ul class="list-group">
                        @foreach($selectedMaya->bimestres->sortBy('nombre') as $bimestre)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Bimestre {{ $bimestre->nombre }}
                                <div>
                                    <a href="{{ route('bimestres.edit', ['bimestre' => $bimestre->id, 'from_dashboard' => 'true', 'maya_id' => $selectedMaya->id]) }}" class="btn btn-sm btn-warning me-1">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <form action="{{ route('bimestres.destroy_from_dashboard', $bimestre->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="maya_id" value="{{ $selectedMaya->id }}"> {{-- To keep maya selected after redirect --}}
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este bimestre y todos sus contenidos asociados (unidades, semanas, etc.)?')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>No hay bimestres creados para esta Maya.</p>
                @endif

                <hr>
                <h5>Crear Nuevo Bimestre</h5>
                <form action="{{ route('bimestres.store_from_dashboard') }}" method="POST">
                    @csrf
                    <input type="hidden" name="curso_grado_sec_niv_anio_id" value="{{ $selectedMaya->id }}">
                    <input type="hidden" name="redirect_to_dashboard" value="true"> {{-- To redirect back to dashboard --}}
                    <input type="hidden" name="maya_id" value="{{ $selectedMaya->id }}"> {{-- To keep maya selected after redirect --}}


                    <div class="mb-3">
                        <label for="bimestre_nombre" class="form-label">Número de Bimestre</label>
                        <select name="bimestre" id="bimestre_nombre" class="form-select" required>
                            <option value="" disabled selected>Seleccione un bimestre</option>
                            @php
                                $ocupadoBimestres = $selectedMaya->bimestres->pluck('nombre')->toArray();
                            @endphp
                            @for ($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ in_array($i, $ocupadoBimestres) ? 'disabled' : '' }}>
                                    Bimestre {{ $i }} {{ in_array($i, $ocupadoBimestres) ? '(Creado)' : '' }}
                                </option>
                            @endfor
                        </select>
                        @error('bimestre')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Bimestre</button>
                </form>
            </div>
        </div>
    @endif
        </div>
    @else
        <p class="mt-3">Seleccione una maya para ver sus detalles y componentes.</p>
    @endif

    {{-- Further content will be added in subsequent steps --}}
</div>
@endsection
