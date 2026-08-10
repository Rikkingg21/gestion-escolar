@extends('layouts.app')
@section('title', 'Detalle de Método de Pago')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-credit-card me-2"></i>{{ $tipopago->nombre }}
            </h4>
            <div>
                <a href="{{ route('metodopago.edit', $tipopago->id) }}" class="btn btn-light btn-sm">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
                <a href="{{ route('metodopago.index') }}" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th class="bg-light" style="width: 40%">Nombre</th>
                            <td>{{ $tipopago->nombre }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Categoría</th>
                            <td>{{ ucfirst($tipopago->categoria) }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Estado</th>
                            <td>
                                @if($tipopago->estado == '1')
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        @if($tipopago->requiere_verificacion)
                        <tr>
                            <th class="bg-light">Requiere Verificación</th>
                            <td><span class="badge bg-warning">Sí</span></td>
                        </tr>
                        @endif
                        @if($tipopago->color_hex)
                        <tr>
                            <th class="bg-light">Color</th>
                            <td><span class="badge" style="background-color: {{ $tipopago->color_hex }}">&nbsp;&nbsp;&nbsp;</span> {{ $tipopago->color_hex }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                @if(in_array($tipopago->categoria, ['transferencia', 'billetera_digital']))
                <div class="col-md-6">
                    <h5 class="mb-3">Datos Bancarios</h5>
                    <table class="table table-bordered">
                        @if($tipopago->entidad_financiera)
                        <tr>
                            <th class="bg-light" style="width: 40%">Entidad Financiera</th>
                            <td>{{ $tipopago->entidad_financiera }}</td>
                        </tr>
                        @endif
                        @if($tipopago->numero_cuenta)
                        <tr>
                            <th class="bg-light">N° Cuenta / Celular</th>
                            <td>{{ $tipopago->numero_cuenta }}</td>
                        </tr>
                        @endif
                        @if($tipopago->cci)
                        <tr>
                            <th class="bg-light">CCI</th>
                            <td>{{ $tipopago->cci }}</td>
                        </tr>
                        @endif
                        @if($tipopago->titular_cuenta)
                        <tr>
                            <th class="bg-light">Titular</th>
                            <td>{{ $tipopago->titular_cuenta }}</td>
                        </tr>
                        @endif
                        @if($tipopago->numero_celular)
                        <tr>
                            <th class="bg-light">Celular</th>
                            <td>{{ $tipopago->numero_celular }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
