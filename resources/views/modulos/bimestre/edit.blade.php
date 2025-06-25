@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Editar Bimestre</h1>
    <form action="{{ route('bimestre.update', $bimestre->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Curso/Grado/Sec/Niv/Año</label>
            <div class="form-control" readonly>
                {{ $bimestre->cursoGradoSecNivAnio->materia->nombre ?? '' }} |
                {{ $bimestre->cursoGradoSecNivAnio->grado->grado ?? '' }} - {{ $bimestre->cursoGradoSecNivAnio->grado->seccion ?? '' }} - {{ $bimestre->cursoGradoSecNivAnio->grado->nivel ?? '' }} |
                {{ $bimestre->cursoGradoSecNivAnio->anio ?? '' }}
            </div>
            <input type="hidden" name="curso_grado_sec_niv_anio_id" value="{{ $bimestre->curso_grado_sec_niv_anio_id }}">
        </div>

        <div class="mb-3">
            <label for="bimestre" class="form-label">Bimestre</label>
            <select name="bimestre" id="bimestre" class="form-select" required>
                @for($i = 1; $i <= 4; $i++)
                    <option value="{{ $i }}"
                        {{ $bimestre->nombre == $i ? 'selected' : '' }}
                        {{ in_array($i, $ocupadoBimestres) ? 'disabled' : '' }}>
                        Bimestre {{ $i }}{{ in_array($i, $ocupadoBimestres) ? ' (Ocupado)' : '' }}
                    </option>
                @endfor
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('maya.index', ['anio' => $maya->anio]) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
