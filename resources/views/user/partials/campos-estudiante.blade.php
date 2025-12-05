<!-- Estudiante -->
                        @php
                            $esEstudiante = $user->estudiante !== null;
                            $oldGradoId = old('grado_id', $esEstudiante ? $user->estudiante->grado_id : null);
                            $oldFechaNacimiento = old('fecha_nacimiento', $esEstudiante ? $user->estudiante->fecha_nacimiento : null);
                            $oldApoderadoId = old('apoderado_id', $esEstudiante ? $user->estudiante->apoderado_id : null);
                            $oldParentesco = old('parentesco', $esEstudiante ? $user->estudiante->parentesco : null);
                            $oldEstadoEstudiante = old('estado_estudiante', $esEstudiante ? $user->estudiante->estado : '1');
                        @endphp

                        <div id="campos-estudiante" class="campos-rol mb-4" style="display: none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 text-primary">
                                    <h6 class="mb-0">
                                        <i class="bi bi-mortarboard me-2"></i>Datos del Estudiante
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="estado_estudiante" class="form-label">Estado del Estudiante</label>
                                                <select class="form-select @error('estado_estudiante') is-invalid @enderror"
                                                        id="estado_estudiante" name="estado_estudiante">
                                                    <option value="1" {{ $oldEstadoEstudiante == '1' ? 'selected' : '' }}>Activo</option>
                                                    <option value="0" {{ $oldEstadoEstudiante == '0' ? 'selected' : '' }}>Inactivo</option>
                                                </select>
                                                @error('estado_estudiante')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                                                       id="fecha_nacimiento" name="fecha_nacimiento"
                                                       value="{{ $oldFechaNacimiento }}">
                                                @error('fecha_nacimiento')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="grado_id" class="form-label fw-bold">Grado <span class="text-danger">*</span></label>
                                                <select class="form-select @error('grado_id') is-invalid @enderror"
                                                        id="grado_id" name="grado_id">
                                                    <option value="">Seleccione un grado</option>
                                                    @foreach($grados as $grado)
                                                        <option value="{{ $grado->id }}"
                                                                {{ $oldGradoId == $grado->id ? 'selected' : '' }}>
                                                            {{ $grado->nombre_completo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('grado_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Apoderado -->
                                    <div class="mb-3">
                                        <h6 class="mb-3">
                                            <i class="bi bi-people-fill me-2"></i>Apoderado
                                        </h6>

                                        @php
                                            $sinApoderado = old('sin_apoderado', $esEstudiante && empty($oldApoderadoId));
                                        @endphp

                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox"
                                                   id="sin_apoderado" name="sin_apoderado"
                                                   {{ $sinApoderado ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sin_apoderado">
                                                El estudiante no tiene apoderado
                                            </label>
                                        </div>

                                        <div id="apoderadoContainer" class="{{ $sinApoderado ? 'd-none' : '' }}">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Buscar Apoderado</label>
                                                        <select class="form-select select2-apoderado @error('apoderado_id') is-invalid @enderror"
                                                                id="apoderado_id" name="apoderado_id">
                                                            <option value=""></option>
                                                            @if($oldApoderadoId)
                                                                @php
                                                                    $apoderado = \App\Models\Apoderado::with('user')->find($oldApoderadoId);
                                                                @endphp
                                                                @if($apoderado)
                                                                    <option value="{{ $apoderado->id }}" selected>
                                                                        {{ $apoderado->user->nombre }} {{ $apoderado->user->apellido_paterno }} (DNI: {{ $apoderado->user->dni }})
                                                                    </option>
                                                                @endif
                                                            @endif
                                                        </select>
                                                        @error('apoderado_id')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Parentesco</label>
                                                        <select name="parentesco"
                                                                class="form-select @error('parentesco') is-invalid @enderror">
                                                            <option value="">Seleccione parentesco</option>
                                                            <option value="padre" {{ $oldParentesco == 'padre' ? 'selected' : '' }}>Padre</option>
                                                            <option value="madre" {{ $oldParentesco == 'madre' ? 'selected' : '' }}>Madre</option>
                                                            <option value="tutor" {{ $oldParentesco == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                                            <option value="otro" {{ $oldParentesco == 'otro' ? 'selected' : '' }}>Otro</option>
                                                        </select>
                                                        @error('parentesco')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
