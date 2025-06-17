@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Editar Tema de Clase</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('temas.update', $tema->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="clase_id" value="{{ $tema->clase_id }}">

        <div class="mb-3">
            <label class="form-label">Clase:</label>
            <div class="form-control" readonly>
                Clase del {{ \Carbon\Carbon::parse($tema->clase->fecha_clase)->format('d-m-Y') }}: {{ $tema->clase->descripcion }}
            </div>
            <input type="hidden" name="clase_id" value="{{ $tema->clase_id }}">
        </div>
        <div class="mb-3">
            <label for="orden" class="form-label">Orden del Tema</label>
            <input type="number" name="orden" id="orden" class="form-control" value="{{ $tema->orden }}" required>
        </div>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Tema</label>
            <input type="text" name="nombre" id="nombre" class="form-control" value="{{ $tema->nombre }}" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción del Tema</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required>{{ $tema->descripcion }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('temas.index', $tema->clase_id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
