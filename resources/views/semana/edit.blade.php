@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Editar Semana de la unidad</h1>
    <form action="{{ route('semanas.update', $semana->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Unidad:</label>
            <div class="form-control" readonly>
                {{ $semana->unidad->nombre ?? '' }}
            </div>
            <input type="hidden" name="unidad_id" value="{{ $semana->unidad->id }}">
        </div>
        <div class="mb-3">
            <label for="semana" class="form-label">Número de Semana</label>
            <select name="semana" id="semana" class="form-select" required>
                <option value="" disabled>Seleccione una semana</option>
                @for($i = 1; $i <= 32; $i++)
                    <option value="{{ $i }}"
                        {{ $semana->nombre == $i ? 'selected' : '' }}
                        {{ in_array($i, $ocupadoSemanas) ? 'disabled' : '' }}>
                        Semana {{ $i }}{{ in_array($i, $ocupadoSemanas) ? ' (Ocupada)' : '' }}
                    </option>
                @endfor
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('semanas.index', $semana->unidad->id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
