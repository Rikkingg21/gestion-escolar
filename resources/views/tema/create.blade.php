@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Crear Tema de Clase</h1>

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

    <form action="{{ route('temas.store') }}" method="POST">
        @csrf
        <input type="hidden" name="clase_id" value="{{ $clase->id }}">
        <div class="mb-3">
            <label class="form-label">Clase:</label>
            <div class="form-control" readonly>
                Clase del {{ \Carbon\Carbon::parse($clase->fecha_clase)->format('d-m-Y') }}: {{ $clase->descripcion }}
            </div>
            <input type="hidden" name="clase_id" value="{{ $clase->id }}">
        </div>

        <!--Seleccionar o escribir el oriden, leer el ultimo orden ingresado y sumarle uno como auto relleno-->
        <div class="mb-3">
            <label for="orden" class="form-label">Orden del Tema</label>
            <input type="number" name="orden" id="orden" class="form-control" value="{{ $ultimoOrden + 1 }}" required>
        </div>

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Tema</label>
            <input type="text" name="nombre" id="nombre" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción del Tema</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Crear</button>
        <a href="{{ route('temas.index', $clase->id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
