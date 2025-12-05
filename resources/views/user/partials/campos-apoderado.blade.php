<!-- Apoderado -->
@php
    $esApoderado = $user->apoderado !== null;
    $oldEstadoApoderado = old('estado_apoderado_' . ($rolIndex ?? 0), $esApoderado ? $user->apoderado->estado : 1);
    $oldParentescoApoderado = old('parentesco_apoderado_' . ($rolIndex ?? 0), $esApoderado ? $user->apoderado->parentesco : null);
@endphp

<div class="campos-rol-apoderado mb-4">
    <div class="card border-info">
        <div class="card-header bg-info bg-opacity-10 text-info">
            <h6 class="mb-0">
                <i class="bi bi-person-bounding-box me-2"></i>Datos del Apoderado
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="estado_apoderado_{{ $rolIndex ?? 0 }}" class="form-label">Estado del Apoderado</label>
                        <select class="form-select @error('estado_apoderado_' . ($rolIndex ?? 0)) is-invalid @enderror"
                                id="estado_apoderado_{{ $rolIndex ?? 0 }}"
                                name="estado_apoderado_{{ $roleId ?? '' }}_{{ $rolIndex ?? 0 }}">
                            <option value="1" {{ $oldEstadoApoderado == 1 ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ $oldEstadoApoderado == 0 ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado_apoderado_' . ($rolIndex ?? 0))
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="parentesco_apoderado_{{ $rolIndex ?? 0 }}" class="form-label fw-bold">Parentesco <span class="text-danger">*</span></label>
                        <select name="parentesco_apoderado_{{ $roleId ?? '' }}_{{ $rolIndex ?? 0 }}"
                                class="form-select @error('parentesco_apoderado_' . ($rolIndex ?? 0)) is-invalid @enderror"
                                id="parentesco_apoderado_{{ $rolIndex ?? 0 }}">
                            <option value="">Seleccione parentesco</option>
                            <option value="padre" {{ $oldParentescoApoderado == 'padre' ? 'selected' : '' }}>Padre</option>
                            <option value="madre" {{ $oldParentescoApoderado == 'madre' ? 'selected' : '' }}>Madre</option>
                            <option value="tutor" {{ $oldParentescoApoderado == 'tutor' ? 'selected' : '' }}>Tutor</option>
                            <option value="otro" {{ $oldParentescoApoderado == 'otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                        @error('parentesco_apoderado_' . ($rolIndex ?? 0))
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
