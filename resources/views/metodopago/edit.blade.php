@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-edit me-2"></i>Editar Método de Pago
            </h4>
        </div>

        <div class="card-body">
            <form action="{{ route('metodopago.update', $tipopago->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $tipopago->nombre) }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Categoría <span class="text-danger">*</span></label>
                        <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
                            <option value="">Seleccione...</option>
                            <option value="transferencia" {{ old('categoria', $tipopago->categoria) == 'transferencia' ? 'selected' : '' }}>Transferencia Bancaria</option>
                            <option value="billetera_digital" {{ old('categoria', $tipopago->categoria) == 'billetera_digital' ? 'selected' : '' }}>Billetera Digital</option>
                            <option value="efectivo" {{ old('categoria', $tipopago->categoria) == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                            <option value="tarjeta" {{ old('categoria', $tipopago->categoria) == 'tarjeta' ? 'selected' : '' }}>Tarjeta de Crédito/Débito</option>
                        </select>
                        @error('categoria')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Entidad Financiera</label>
                        <input type="text" name="entidad_financiera" class="form-control @error('entidad_financiera') is-invalid @enderror" value="{{ old('entidad_financiera', $tipopago->entidad_financiera) }}">
                        @error('entidad_financiera')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Número de Cuenta / Celular</label>
                        <input type="text" name="numero_cuenta" class="form-control @error('numero_cuenta') is-invalid @enderror" value="{{ old('numero_cuenta', $tipopago->numero_cuenta) }}">
                        @error('numero_cuenta')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">CCI</label>
                        <input type="text" name="cci" class="form-control @error('cci') is-invalid @enderror" value="{{ old('cci', $tipopago->cci) }}">
                        @error('cci')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Titular de la Cuenta</label>
                        <input type="text" name="titular_cuenta" class="form-control @error('titular_cuenta') is-invalid @enderror" value="{{ old('titular_cuenta', $tipopago->titular_cuenta) }}">
                        @error('titular_cuenta')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Número de Celular</label>
                        <input type="text" name="numero_celular" class="form-control @error('numero_celular') is-invalid @enderror" value="{{ old('numero_celular', $tipopago->numero_celular) }}">
                        @error('numero_celular')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Color (Hexadecimal)</label>
                        <div class="input-group">
                            <input type="color" name="color_hex" class="form-control form-control-color" value="{{ old('color_hex', $tipopago->color_hex) }}" style="width: 60px; padding: 0;" id="colorPicker">
                            <input type="text" class="form-control" value="{{ old('color_hex', $tipopago->color_hex) }}" id="colorText">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">¿Requiere Verificación?</label>
                        <select name="requiere_verificacion" class="form-select">
                            <option value="0" {{ old('requiere_verificacion', $tipopago->requiere_verificacion) == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('requiere_verificacion', $tipopago->requiere_verificacion) == '1' ? 'selected' : '' }}>Sí</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select @error('estado') is-invalid @enderror" required>
                            <option value="1" {{ old('estado', $tipopago->estado) == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('estado', $tipopago->estado) == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 text-end">
                        <a href="{{ route('metodopago.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const colorPicker = document.getElementById('colorPicker');
    const colorText = document.getElementById('colorText');

    colorPicker.addEventListener('change', function() {
        colorText.value = this.value;
    });

    colorText.addEventListener('input', function() {
        if (/^#[a-fA-F0-9]{6}$/.test(this.value)) {
            colorPicker.value = this.value;
        }
    });
</script>
@endsection
