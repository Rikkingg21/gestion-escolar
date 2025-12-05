<select class="form-select rol-select-nuevo" name="nuevo_rol[]" required>
    <option value="">Seleccione un rol</option>
    @foreach($roles as $role)
        @if(($currentSessionRole === 'admin' || $role->id !== 1) && !in_array($role->id, $rolesBloqueados))
            <option value="{{ $role->id }}" data-role-name="{{ strtolower($role->nombre) }}">
                {{ $role->nombre }}
            </option>
        @endif
    @endforeach
</select>
