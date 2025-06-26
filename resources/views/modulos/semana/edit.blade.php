@extends('layouts.app')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="container-fluid">
    <h1>Editar Semana de la unidad</h1>
    <form action="{{ route('semana.update', $semana->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="anio" value="{{ $anio }}">
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
        <a href="{{ route('maya.index', ['anio' => $anio]) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
