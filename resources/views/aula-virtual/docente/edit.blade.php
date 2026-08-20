@extends('layouts.app')
@section('title', 'Editar Clase Virtual')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-camera-video me-2"></i> Editar Clase Virtual
                    </h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="window.history.back()">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </button>
                </div>
                <div class="card-body">
                    <form action="{{ route('aula-virtual-docente.update', $sesion->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if($cursos->isEmpty())
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                No tienes cursos asignados. Coordina con el director para que te asigne
                                materia, grado y periodo en el módulo de Mayas.
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="curso_grado_sec_niv_anio_id" class="form-label fw-semibold">Curso</label>
                                <select name="curso_grado_sec_niv_anio_id" id="curso_grado_sec_niv_anio_id"
                                        class="form-select @error('curso_grado_sec_niv_anio_id') is-invalid @enderror" required>
                                    <option value="">Seleccione un curso</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->id }}"
                                                {{ old('curso_grado_sec_niv_anio_id', $sesion->curso_grado_sec_niv_anio_id) == $curso->id ? 'selected' : '' }}>
                                            {{ $curso->materia->nombre ?? 'Materia' }} - {{ $curso->grado->nombre_completo ?? 'Grado' }}
                                            @if($curso->periodo)
                                                ({{ $curso->periodo->anio }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('curso_grado_sec_niv_anio_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="plataforma" class="form-label fw-semibold">Plataforma</label>
                                <select name="plataforma" id="plataforma"
                                        class="form-select @error('plataforma') is-invalid @enderror">
                                    <option value="meet" {{ old('plataforma', $sesion->plataforma) == 'meet' ? 'selected' : '' }}>Google Meet</option>
                                    <option value="zoom" {{ old('plataforma', $sesion->plataforma) == 'zoom' ? 'selected' : '' }}>Zoom</option>
                                    <option value="teams" {{ old('plataforma', $sesion->plataforma) == 'teams' ? 'selected' : '' }}>Microsoft Teams</option>
                                    <option value="otro" {{ old('plataforma', $sesion->plataforma) == 'otro' ? 'selected' : '' }}>Otro</option>
                                </select>
                                @error('plataforma')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="estado" class="form-label fw-semibold">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="1" {{ old('estado', $sesion->estado) == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado', $sesion->estado) == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="titulo" class="form-label fw-semibold">Título de la clase</label>
                                <input type="text" name="titulo" id="titulo" class="form-control"
                                       value="{{ old('titulo', $sesion->titulo) }}"
                                       placeholder="Ej. Clase de fracciones - Tema 3">
                                @error('titulo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="fecha" class="form-label fw-semibold">Fecha programada</label>
                                <input type="date" name="fecha" id="fecha"
                                       class="form-control @error('fecha') is-invalid @enderror"
                                       value="{{ old('fecha', $sesion->fecha ? $sesion->fecha->format('Y-m-d') : '') }}" required>
                                @error('fecha')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="hora" class="form-label fw-semibold">Hora programada</label>
                                <input type="time" name="hora" id="hora"
                                       class="form-control @error('hora') is-invalid @enderror"
                                       value="{{ old('hora', substr($sesion->hora, 0, 5)) }}" required>
                                @error('hora')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="enlace" class="form-label fw-semibold">
                                    <i class="bi bi-link-45deg me-1"></i> Enlace de la clase (Meet, Zoom, Teams)
                                </label>
                                <input type="url" name="enlace" id="enlace"
                                       class="form-control @error('enlace') is-invalid @enderror"
                                       value="{{ old('enlace', $sesion->enlace) }}"
                                       placeholder="https://meet.google.com/... o https://us02web.zoom.us/j/..." required>
                                @error('enlace')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="enlace_material" class="form-label fw-semibold">
                                    <i class="bi bi-folder2-open me-1"></i> Material de la clase (enlace opcional)
                                </label>
                                <input type="url" name="enlace_material" id="enlace_material"
                                       class="form-control @error('enlace_material') is-invalid @enderror"
                                       value="{{ old('enlace_material', $sesion->enlace_material) }}"
                                       placeholder="https://drive.google.com/... o https://drive.google.com/file/...">
                                <div class="form-text">Enlace a tu material (Drive, PDF, etc.) para esta clase. Tus estudiantes podrán verlo junto a la clase.</div>
                                @error('enlace_material')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="motivo" class="form-label fw-semibold">
                                    <i class="bi bi-shield-check me-1"></i> Motivo de la clase
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea name="motivo" id="motivo" rows="2"
                                          class="form-control @error('motivo') is-invalid @enderror"
                                          placeholder="Describe el motivo o propósito de esta sesión (medida de seguridad)"
                                          required>{{ old('motivo', $sesion->motivo) }}</textarea>
                                <div class="form-text">Este campo es obligatorio como medida de seguridad.</div>
                                @error('motivo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                                <textarea name="observaciones" id="observaciones" rows="2"
                                          class="form-control"
                                          placeholder="Materiales a llevar, indicaciones, etc. (opcional)">{{ old('observaciones', $sesion->observaciones) }}</textarea>
                                @error('observaciones')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mt-3">
                                <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                    <i class="bi bi-x me-1"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i> Actualizar Clase
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection