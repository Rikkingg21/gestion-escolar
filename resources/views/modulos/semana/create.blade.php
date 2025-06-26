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
    <h1>Crear Semanas de la Unidad</h1>
    <form action="{{ route('semana.store') }}" method="POST">
        @csrf
        <label class="form-label">Unidad:</label>
        <div class="form-control" readonly>
            {{ $unidad->nombre ?? '' }}
        </div>
        <input type="hidden" name="unidad_id" value="{{ $unidad->id }}">

        <div class="mb-3">
            <label for="semana" class="form-label">Número de Semana</label>
            <!--Select de 32 Semanas-->
            <select name="semana" id="semana" class="form-select" required>
                <option value="" disabled selected>Seleccione una semana</option>
                @for($i = 1; $i <= 32; $i++)
                    <option value="{{ $i }}" {{ in_array($i, $ocupadoSemanas) ? 'disabled' : '' }}>
                        Semana {{ $i }}{{ in_array($i, $ocupadoSemanas) ? ' (Ocupada)' : '' }}
                    </option>
                @endfor
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Crear</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
