@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h4>Periodo: {{ $periodo->nombre }} ({{ $periodo->anio }})</h4>
            <small>{{ $periodo->descripcion }}</small>
        </div>
        <div class="card-body">
            <a href="{{ route('conducta.index') }}" class="btn btn-primary mb-3">
                <i class="fas fa-arrow-left"></i> Volver
            </a>

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
