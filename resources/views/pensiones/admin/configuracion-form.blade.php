@extends('layouts.app')
@section('title', isset($config) ? 'Editar Configuración de Pensiones' : 'Nueva Configuración de Pensiones')
@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="bi bi-gear me-2"></i>
            {{ isset($config) ? 'Editar Configuración de Pensiones' : 'Nueva Configuración de Pensiones' }}
        </h4>
        <a href="{{ route('pensiones-admin.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Revise los siguientes errores:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ isset($config) ? route('pensiones-admin.configuracion.update', $config->id) : route('pensiones-admin.configuracion.store') }}">
        @csrf
        @if(isset($config))
            @method('PUT')
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i> Datos generales</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Periodo académico <span class="text-danger">*</span></label>
                        <select name="periodo_id" id="periodoSelect" class="form-select" required>
                            <option value="">Seleccione...</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}" data-anio="{{ $periodo->anio }}"
                                        @selected((isset($config) && $config->periodo_id == $periodo->id) || old('periodo_id') == $periodo->id || request('periodo_id') == $periodo->id)>
                                    {{ $periodo->nombre }} ({{ $periodo->anio }}) {{ $periodo->estado == '1' ? '- Activo' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Grado @if(!isset($config)) (puede elegir varios) @endif <span class="text-danger">*</span></label>
                        @if(isset($config))
                            <select name="grado_id" class="form-select" required>
                                <option value="">Seleccione...</option>
                                @foreach($grados as $grado)
                                    <option value="{{ $grado->id }}" @selected($config->grado_id == $grado->id || old('grado_id') == $grado->id)>
                                        {{ $grado->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="grado_id[]" class="form-select" multiple size="6" required>
                                @foreach($grados as $grado)
                                    <option value="{{ $grado->id }}" @selected(in_array($grado->id, old('grado_id', [])))>
                                        {{ $grado->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Mantenga Ctrl/Cmd para elegir varios grados. Se creará una configuración por grado.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar2-week me-2"></i> Cuotas de la plantilla</h6>
                <button type="button" class="btn btn-sm btn-light" onclick="agregarCuota()">
                    <i class="bi bi-plus-lg me-1"></i> Agregar cuota
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="tablaCuotas">
                        <thead>
                            <tr class="text-center">
                                <th style="width: 25%">Concepto</th>
                                <th style="width: 12%">Mes</th>
                                <th style="width: 25%">Fecha de vencimiento</th>
                                <th style="width: 20%">Monto (S/)</th>
                                <th style="width: 8%">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $cuotasExistentes = isset($config) ? $config->cuotas : collect();
                                $oldCuotas = old('cuotas');
                                if (isset($config)) {
                                    $anioPeriodo = $config->periodo->anio;
                                } else {
                                    $periodoPreseleccionado = $periodos->firstWhere('id', old('periodo_id', request('periodo_id')));
                                    $anioPeriodo = $periodoPreseleccionado?->anio;
                                }
                                $fechaMin = $anioPeriodo ? "{$anioPeriodo}-01-01" : '';
                                $fechaMax = $anioPeriodo ? "{$anioPeriodo}-12-31" : '';
                            @endphp
                            @forelse($cuotasExistentes as $index => $cuota)
                                @include('pensiones.admin._cuota-fila', [
                                    'index' => $index,
                                    'cuota' => $cuota,
                                    'valores' => null,
                                    'fechaMin' => $fechaMin,
                                    'fechaMax' => $fechaMax,
                                ])
                            @empty
                                @if($oldCuotas)
                                    @foreach($oldCuotas as $index => $cuotaOld)
                                        @include('pensiones.admin._cuota-fila', [
                                            'index' => $index,
                                            'cuota' => null,
                                            'valores' => $cuotaOld,
                                            'fechaMin' => $fechaMin,
                                            'fechaMax' => $fechaMax,
                                        ])
                                    @endforeach
                                @else
                                    @include('pensiones.admin._cuota-fila', [
                                        'index' => 0,
                                        'cuota' => null,
                                        'valores' => null,
                                        'fechaMin' => $fechaMin,
                                        'fechaMax' => $fechaMax,
                                    ])
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Al guardar, se generarán automáticamente las cuotas para todas las matrículas activas del periodo y grado seleccionados.
                </div>
            </div>
            <div class="card-footer bg-light text-end">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> {{ isset($config) ? 'Actualizar Configuración' : 'Guardar Configuración' }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    let indiceCuota = {{ isset($config) ? $config->cuotas->count() : 1 }};

    function anioPeriodoActual() {
        const sel = document.getElementById('periodoSelect');
        const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
        return opt && opt.dataset.anio ? opt.dataset.anio : null;
    }

    function limitesFecha() {
        const anio = anioPeriodoActual();
        return anio ? { min: `${anio}-01-01`, max: `${anio}-12-31` } : { min: '', max: '' };
    }

    function agregarCuota() {
        const { min, max } = limitesFecha();
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td><input type="text" name="cuotas[${indiceCuota}][concepto]" class="form-control form-control-sm" placeholder="Ej: Pensión marzo" required></td>
            <td>
                <select name="cuotas[${indiceCuota}][mes]" class="form-select form-select-sm">
                    <option value="">--</option>
                    ${Array.from({length: 12}, (_, i) => `<option value="${i+1}">${i+1}</option>`).join('')}
                </select>
            </td>
            <td><input type="date" name="cuotas[${indiceCuota}][fecha_vencimiento]" class="form-control form-control-sm" required min="${min}" max="${max}"></td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">S/</span>
                    <input type="number" step="0.01" min="0.01" name="cuotas[${indiceCuota}][monto]" class="form-control" required>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        document.querySelector('#tablaCuotas tbody').appendChild(fila);
        indiceCuota++;
    }

    document.getElementById('periodoSelect').addEventListener('change', function () {
        const { min, max } = limitesFecha();
        document.querySelectorAll('#tablaCuotas input[name$="[fecha_vencimiento]"]').forEach(input => {
            input.min = min;
            input.max = max;
        });
    });
</script>
@endsection