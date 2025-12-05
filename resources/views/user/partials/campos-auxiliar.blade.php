<!-- Auxiliar -->
@php
    $esAuxiliar = $user->auxiliar !== null;
    $oldEstadoAuxiliar = old('estado_auxiliar_' . ($rolIndex ?? 0), $esAuxiliar ? $user->auxiliar->estado : 1);
    $oldTurno = old('turno_' . ($rolIndex ?? 0), $esAuxiliar ? $user->auxiliar->turno : null);
    $oldFunciones = old('funciones_' . ($rolIndex ?? 0), $esAuxiliar ? $user->auxiliar->funciones : null);
@endphp

<div class="campos-rol-auxiliar mb-4">
    <div class="card border-warning">
        <div class="card-header bg-warning bg-opacity-10 text-warning">
            <h6 class="mb-0">
                <i class="bi bi-person-workspace me-2"></i>Datos del Auxiliar
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="estado_auxiliar_{{ $rolIndex ?? 0 }}" class="form-label">Estado del Auxiliar</label>
                        <select class="form-select @error('estado_auxiliar_' . ($rolIndex ?? 0)) is-invalid @enderror"
                                id="estado_auxiliar_{{ $rolIndex ?? 0 }}"
                                name="estado_auxiliar_{{ $roleId ?? '' }}_{{ $rolIndex ?? 0 }}">
                            <option value="1" {{ $oldEstadoAuxiliar == 1 ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ $oldEstadoAuxiliar == 0 ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado_auxiliar_' . ($rolIndex ?? 0))
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="turno_{{ $rolIndex ?? 0 }}" class="form-label">Turno</label>
                        <select class="form-select @error('turno_' . ($rolIndex ?? 0)) is-invalid @enderror"
                                id="turno_{{ $rolIndex ?? 0 }}"
                                name="turno_{{ $roleId ?? '' }}_{{ $rolIndex ?? 0 }}">
                            <option value="">Seleccione turno</option>
                            <option value="mañana" {{ $oldTurno == 'mañana' ? 'selected' : '' }}>Mañana</option>
                            <option value="tarde" {{ $oldTurno == 'tarde' ? 'selected' : '' }}>Tarde</option>
                            <option value="completo" {{ $oldTurno == 'completo' ? 'selected' : '' }}>Completo</option>
                        </select>
                        @error('turno_' . ($rolIndex ?? 0))
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="funciones_{{ $rolIndex ?? 0 }}" class="form-label">Funciones</label>
                        <input type="text" class="form-control @error('funciones_' . ($rolIndex ?? 0)) is-invalid @enderror"
                               id="funciones_{{ $rolIndex ?? 0 }}"
                               name="funciones_{{ $roleId ?? '' }}_{{ $rolIndex ?? 0 }}"
                               value="{{ $oldFunciones }}"
                               placeholder="Ej: Limpieza, Vigilancia">
                        @error('funciones_' . ($rolIndex ?? 0))
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
