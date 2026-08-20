@extends('layouts.app')
@section('title', 'Registrar Pago de Pensión')
@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Registrar Pago de Pensión</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('pensiones-admin.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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

    <div class="row g-4">
        {{-- Columna izquierda: búsqueda --}}
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-search me-2"></i> 1. Buscar estudiante matriculado</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Periodo académico</label>
                            <select id="periodo_select" class="form-select">
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->id }}"
                                            @selected($periodo->estado == '1' && $loop->first)>
                                        {{ $periodo->nombre }} ({{ $periodo->anio }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DNI, nombres o apellidos</label>
                            <input type="text" id="buscar_input" class="form-control"
                                   placeholder="Ej: 12345678, Juan o García"
                                   onkeydown="if(event.key === 'Enter'){ event.preventDefault(); buscarEstudiantes(); }">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" onclick="buscarEstudiantes()">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>

                    <hr>

                    <div id="resultadosBusqueda">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-person-search display-4 d-block mb-2 text-muted opacity-50"></i>
                            <p class="mb-0">Busque al estudiante o apoderado por DNI, nombre o apellidos.</p>
                            <small>Se muestran solo estudiantes matriculados en el periodo seleccionado.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-calendar2-week me-2"></i> 2. Seleccionar cuota</h6>
                </div>
                <div class="card-body">
                    <div id="estudianteSeleccionado" class="alert alert-info d-none">
                        <strong id="estudianteNombre"></strong>
                        <span class="text-muted">|</span>
                        <span id="estudianteDni"></span>
                        <span class="text-muted">|</span>
                        <span id="estudianteGrado"></span>
                    </div>
                    <div id="cuotasEstudiante">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-cash-stack display-4 d-block mb-2 text-muted opacity-50"></i>
                            <p class="mb-0">Seleccione primero un estudiante para ver sus cuotas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna derecha: formulario de pago --}}
        <div class="col-md-5">
            <form method="POST" action="{{ route('pensiones-admin.registrar-pago.store') }}"
                  enctype="multipart/form-data" id="formRegistrarPago">
                @csrf
                <input type="hidden" name="periodo_id" id="periodo_hidden">
                <input type="hidden" name="estudiante_id" id="estudiante_hidden">
                <input type="hidden" name="pension_id" id="pension_hidden">

                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-white py-2">
                        <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i> 3. Datos del pago</h6>
                    </div>
                    <div class="card-body">
                        <div id="pagoResumen" class="alert alert-light border d-none">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Cuota:</span>
                                <strong id="resumenConcepto"></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Vencimiento:</span>
                                <span id="resumenVencimiento"></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Monto:</span>
                                <strong id="resumenMonto"></strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Método de pago <span class="text-danger">*</span></label>
                            <select name="metodo_pago_id" class="form-select" required>
                                <option value="">Seleccione...</option>
                                @foreach($tiposPago as $tipo)
                                    <option value="{{ $tipo->id }}" data-es-efectivo="{{ $tipo->es_efectivo }}">
                                        {{ $tipo->nombre }}
                                        @if($tipo->entidad_financiera)
                                            - {{ $tipo->entidad_financiera }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Número de operación / voucher</label>
                            <input type="text" name="numero_operacion" class="form-control"
                                   placeholder="Opcional. Si es efectivo se genera automáticamente.">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Comprobante (imagen o PDF)</label>
                            <input type="file" name="comprobante" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Opcional. Máx. 5MB. Formatos: JPG, PNG, PDF.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"
                                      placeholder="Información adicional del pago..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100" id="btnRegistrar" disabled>
                            <i class="bi bi-check-circle me-1"></i> Registrar y aprobar pago
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let cuotasCargadas = [];

    function syncPeriodo() {
        document.getElementById('periodo_hidden').value = document.getElementById('periodo_select').value;
    }

    async function buscarEstudiantes() {
        const periodoId = document.getElementById('periodo_select').value;
        const q = document.getElementById('buscar_input').value.trim();

        if (!periodoId) {
            alert('Seleccione un periodo académico.');
            return;
        }

        syncPeriodo();

        const contenedor = document.getElementById('resultadosBusqueda');
        contenedor.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border text-primary"></div><p class="mt-2 mb-0">Buscando...</p></div>';

        try {
            const url = '/pensiones-admin/buscar-estudiantes?periodo_id=' + periodoId + '&q=' + encodeURIComponent(q);
            const res = await fetch(url);
            const json = await res.json();

            if (!json.success || json.data.length === 0) {
                contenedor.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No se encontraron estudiantes matriculados en el periodo seleccionado.
                    </div>`;
                return;
            }

            let filas = '';
            json.data.forEach((est) => {
                filas += `
                    <tr>
                        <td><strong>${est.nombre_completo}</strong><div class="text-muted small">DNI: ${est.dni}</div></td>
                        <td>${est.grado_nombre}</td>
                        <td class="small text-muted">${est.apoderado_nombre}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-primary" onclick="seleccionarEstudiante(${est.estudiante_id}, '${est.nombre_completo}', '${est.dni}', '${est.grado_nombre}')">
                                <i class="bi bi-check-lg"></i> Seleccionar
                            </button>
                        </td>
                    </tr>`;
            });

            contenedor.innerHTML = `
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Estudiante</th><th>Grado</th><th>Apoderado</th><th></th></tr></thead>
                        <tbody>${filas}</tbody>
                    </table>
                </div>`;
        } catch (e) {
            contenedor.innerHTML = '<div class="alert alert-danger mb-0">Ocurrió un error al buscar.</div>';
        }
    }

    async function seleccionarEstudiante(id, nombre, dni, grado) {
        document.getElementById('estudiante_hidden').value = id;
        document.getElementById('estudianteSeleccionado').classList.remove('d-none');
        document.getElementById('estudianteNombre').textContent = nombre;
        document.getElementById('estudianteDni').textContent = 'DNI: ' + dni;
        document.getElementById('estudianteGrado').textContent = grado;

        const periodoId = document.getElementById('periodo_select').value;
        const contenedor = document.getElementById('cuotasEstudiante');
        contenedor.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border text-success"></div><p class="mt-2 mb-0">Cargando cuotas...</p></div>';

        try {
            const url = '/pensiones-admin/estudiante/' + id + '/cuotas?periodo_id=' + periodoId;
            const res = await fetch(url);
            const json = await res.json();

            if (!json.success || json.data.length === 0) {
                contenedor.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check2-circle me-1"></i>
                        El estudiante no tiene cuotas pendientes o atrasadas.
                    </div>`;
                cuotasCargadas = [];
                actualizarBoton();
                return;
            }

            cuotasCargadas = json.data;

            let filas = '';
            json.data.forEach((cuota) => {
                filas += `
                    <tr class="${cuota.estado_efectivo === 'atrasado' ? 'table-danger' : ''}">
                        <td>
                            <label class="form-check-label">
                                <input type="radio" name="cuota_radio" class="form-check-input me-1"
                                       value="${cuota.id}" onchange="seleccionarCuota(${cuota.id})">
                                ${cuota.concepto}
                            </label>
                        </td>
                        <td>${cuota.fecha_vencimiento}</td>
                        <td>${cuota.monto_formateado}</td>
                        <td><span class="badge text-bg-${cuota.estado_color}">${cuota.estado_label}</span></td>
                    </tr>`;
            });

            contenedor.innerHTML = `
                <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Cuota</th><th>Vence</th><th>Monto</th><th>Estado</th></tr></thead>
                        <tbody>${filas}</tbody>
                    </table>
                </div>`;
        } catch (e) {
            contenedor.innerHTML = '<div class="alert alert-danger mb-0">Ocurrió un error al cargar las cuotas.</div>';
        }
    }

    function seleccionarCuota(id) {
        document.getElementById('pension_hidden').value = id;
        const cuota = cuotasCargadas.find(c => c.id === id);
        if (cuota) {
            document.getElementById('pagoResumen').classList.remove('d-none');
            document.getElementById('resumenConcepto').textContent = cuota.concepto;
            document.getElementById('resumenVencimiento').textContent = cuota.fecha_vencimiento;
            document.getElementById('resumenMonto').textContent = cuota.monto_formateado;
        }
        actualizarBoton();
    }

    function actualizarBoton() {
        const btn = document.getElementById('btnRegistrar');
        const cuotaSeleccionada = document.querySelector('input[name="cuota_radio"]:checked');
        btn.disabled = !cuotaSeleccionada;
    }

    document.addEventListener('DOMContentLoaded', function () {
        syncPeriodo();
    });
</script>

@endsection