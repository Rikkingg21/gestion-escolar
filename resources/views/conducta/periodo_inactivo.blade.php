@extends('layouts.app')
@section('title', 'Periodo Inactivo')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    <i class="bi bi-calendar2-x me-2"></i> Periodo: {{ $periodo->nombre }} ({{ $periodo->anio }})
                </h5>
                @if($periodo->descripcion)
                    <small class="d-block mt-1">{{ $periodo->descripcion }}</small>
                @endif
            </div>
            <a href="{{ route('conducta.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body">
            @foreach($periodo->periodobimestres as $bimestre)
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>{{ $bimestre->bimestre }}° Bimestre ({{ $bimestre->sigla }})</strong>
                    </div>
                    <div class="card-body">
                        @if($bimestre->conductas->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-info">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre de Conducta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bimestre->conductas as $conducta)
                                        <tr>
                                            <td width="50">{{ $conducta->id }}</td>
                                            <td>{{ $conducta->nombre }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">No hay conductas asignadas a este bimestre</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
