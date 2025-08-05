@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="card mb-4">
        <br>
        <form method="GET" class="mb-3 row g-2">
        <div class="col-md-3">
            <select name="grado_id" class="form-select">
                <option value="">-- Grado --</option>
                @foreach($grados as $grado)
                    <option value="{{ $grado->id }}" {{ $grado_id == $grado->id ? 'selected' : '' }}>
                        {{ $grado->nombre ?? 'Sin grado' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="bimestre_id" class="form-select">
                <option value="">-- Bimestre --</option>
                @foreach($bimestres as $bim)
                    <option value="{{ $bim->id }}" {{ $bimestre_id == $bim->id ? 'selected' : '' }}>
                        {{ $bim->nombre ?? 'Sin bimestre' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="anio" class="form-select">
                <option value="">-- Año --</option>
                @foreach($anios as $a)
                    <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>
    </div>

<table class="table table-bordered mb-4">
        <!-- Encabezado de informe -->
        <tr>
            <td colspan="5">INFORME DE PROGRESO DE LAS COMPETENCIAS DEL ESTUDIANTE (SECUNDARIA EBR)</td>
        </tr>
        <tr>
            <td colspan="5">AÑO - {{ $anio ?? date('Y') }} - {{ $bimestre_selected->nombre ?? 'I BIMESTRE' }} BIMESTRE</td>
        </tr>
        <tr>
            <td rowspan="6">imagen</td>
            <td>UGEL:</td>
            <td>Tacna</td>
        </tr>
        <tr>
            <td>Nivel:</td><td>Secundaria</td>
        </tr>
        <tr>
            <td>II.EE:</td><td>Santa Teresita del Niño Jesús</td>
        </tr>
        <tr>
            <td>Grado:</td><td>{{ $grado_selected->nombre ?? '1' }}</td>
        </tr>
        <tr>
            <td>Sección:</td><td>{{ $estudiante->seccion ?? 'A' }}</td>
        </tr>
        <tr>
            <td>Estudiante:</td><td>{{ $estudiante->user->apellido_paterno }} {{ $estudiante->user->apellido_materno }}, {{ $estudiante->user->nombre }}</td>
        </tr>
        <tr>
            <th>Área</th>
            <th>Competencias</th>
            <th>Criterios de evaluación alcanzados</th>
            <th>CRIT.</th>
            <th>Valor</th>
        </tr>

        @forelse($detalle as $materiaData)
            @php
                $materiaRowspan = $materiaData['total_criterios'];
            @endphp

            @foreach($materiaData['competencias'] as $competencia)
                @php
                    $compRowspan = $competencia['total_criterios'] + 1; // +1 para la fila de valoración
                @endphp

                @foreach($competencia['criterios'] as $index => $criterio)
                    <tr>
                        @if($loop->parent->first && $loop->first)
                            <td rowspan="{{ $materiaRowspan }}">{{ $materiaData['nombre'] }}</td>
                        @endif

                        @if($loop->first)
                            <td rowspan="{{ $compRowspan }}">{{ $competencia['nombre'] }}</td>
                        @endif

                        <td>{{ $criterio['nombre'] }}</td>
                        <td>C{{ $loop->parent->index * 10 + $loop->iteration }}</td>
                        <td class="{{ $criterio['valor_class'] }}">{{ $criterio['valor'] }}</td>
                    </tr>
                @endforeach

                {{-- Fila de valoración de competencia --}}
                <tr>
                    <td>VALORACIÓN DE COMPETENCIA</td>
                    <td>{{ $competencia['codigo_valoracion'] }}</td>
                    <td class="{{ $competencia['valor_competencia_class'] }}">
                        {{ $competencia['valor_competencia'] }}
                    </td>
                </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="5" class="text-center">No hay registros de notas públicas para mostrar.</td>
            </tr>
        @endforelse
    </table>
</div>

<style>
    .table th, .table td {
        vertical-align: middle;
        text-align: center;
    }
    .valor-ad {
        background-color: #4e73df;
        color: white;
        font-weight: bold;
    }
    .valor-a {
        background-color: #1cc88a;
        color: white;
        font-weight: bold;
    }
    .valor-b {
        background-color: #f6c23e;
        color: #2c3e50;
        font-weight: bold;
    }
    .valor-c {
        background-color: #e74a3b;
        color: white;
        font-weight: bold;
    }
    .valor-d {
        background-color: #5a5c69;
        color: white;
        font-weight: bold;
    }
</style>
@endsection
