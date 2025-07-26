@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-graduation-cap"></i> Registro de Calificaciones
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                {{ $materia->nombre }} - {{ $grado->nombreCompleto }} - {{ $bimestre->nombre }} Bimestre
            </h6>
            <small>Docente: {{ $docente->user->nombre_completo ?? 'No asignado' }}</small>
        </div>

        <div class="card-body">
            <form action="{{ route('nota.store') }}" method="POST">
                @csrf
                <input type="hidden" name="bimestre_id" value="{{ $bimestre->id }}">

                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaNotas">
                        <thead class="thead-dark">
                            <tr>
                                <th rowspan="2">Estudiante</th>
                                @foreach($competencias as $competencia)
                                    <th colspan="{{ $competencia->criterios->count() }}">
                                        {{ $competencia->nombre }}
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($competencias as $competencia)
                                    @foreach($competencia->criterios as $criterio)
                                        <th title="{{ $criterio->descripcion }}">
                                            {{ $criterio->nombre }}
                                            <input type="hidden" name="criterios[]" value="{{ $criterio->id }}">
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estudiantes as $estudiante)
                                <tr>
                                    <td>
                                        {{ $estudiante->user->apellido_paterno }}
                                        {{ $estudiante->user->apellido_materno }},
                                        {{ $estudiante->user->nombre }}
                                    </td>
                                    @foreach($competencias as $competencia)
                                        @foreach($competencia->criterios as $criterio)
                                            <td>
                                                @php
                                                    $nota = $notasExistentes[$estudiante->id][$criterio->id][0]->nota ?? null;
                                                @endphp
                                                <input type="number"
                                                    name="notas[{{ $estudiante->id }}][{{ $criterio->id }}]"
                                                    value="{{ $nota }}"
                                                    min="0"
                                                    max="20"
                                                    step="0.1"
                                                    class="form-control form-control-sm">
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Calificaciones
                    </button>
                    <a href="{{ route('maya.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Validación para que las notas estén entre 0 y 20
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 0) this.value = 0;
            if (this.value > 20) this.value = 20;
        });
    });
</script>
@endsection
