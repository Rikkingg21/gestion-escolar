
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>
            Asistencia: {{ $curso->materia->nombre ?? 'Sin materia' }} -
            {{ $curso->grado->grado ?? '' }} {{ $curso->grado->seccion ?? '' }} -
            {{ $bimestre->nombre }} BIMESTRE

        </h3><br>
        <form method="GET" action="{{ route('asistencia.manual', [$curso->id, $bimestre->id]) }}" class="d-flex align-items-center">
            @csrf
            <div class="input-group me-3">
                <span class="input-group-text">Fecha:</span>
                <input type="date"
                       name="fecha"
                       class="form-control"
                       value="{{ $fechaSeleccionada }}"
                       onchange="this.form.submit()">
            </div>
            <a href="" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </form>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('asistencia.store') }}">
                @csrf
                <input type="hidden" name="bimestre_id" value="{{ $bimestre->id }}">
                <input type="hidden" name="curso_id" value="{{ $curso->id }}">

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="40%">Estudiante</th>
                                <th width="40%">Asistencia</th>
                                <th width="10%">Hora</th>
                                <th width="10%">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($estudiantes as $estudiante)
                            <tr>
                                <td>
                                    {{ $estudiante->user->apellido_paterno ?? '' }}
                                    {{ $estudiante->user->apellido_materno ?? '' }}
                                    {{ $estudiante->user->nombre ?? '' }}
                                </td>
                                <td>
                                    <select name="asistencias[{{ $estudiante->id }}]"
                                            class="form-control select-asistencia"
                                            required>
                                        <option value="">Seleccione...</option>
                                        @foreach($tipos as $tipo)
                                            <option value="{{ $tipo->id }}"
                                                @if($estudiante->asistencias->isNotEmpty() &&
                                                    $estudiante->asistencias->first()->tipo_asistencia_id == $tipo->id)
                                                    selected
                                                @endif>
                                                {{ $tipo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="time"
                                           name="horas[{{ $estudiante->id }}]"
                                           class="form-control"
                                           value="{{ $estudiante->asistencias->isNotEmpty() ? $estudiante->asistencias->first()->hora : '' }}">
                                </td>
                                <td class="text-center">
                                    @if($estudiante->asistencias->isNotEmpty())
                                        <span class="badge bg-success">Registrado</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No hay estudiantes en este grado</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Asistencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .select-asistencia {
        min-width: 180px;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Marcar en rojo los selects no seleccionados al enviar el formulario
        $('form').on('submit', function() {
            $('.select-asistencia').each(function() {
                if ($(this).val() === '') {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Verificar si hay algún campo no seleccionado
            if ($('.select-asistencia.is-invalid').length > 0) {
                alert('Por favor, seleccione el tipo de asistencia para todos los estudiantes');
                return false;
            }
        });
    });
</script>
@endsection
