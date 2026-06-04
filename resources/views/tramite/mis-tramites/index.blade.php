@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Mis Trámites
                </h4>
                <a href="{{ route('tramite.create') }}" class="btn btn-light">
                    <i class="fas fa-plus me-1"></i> Nuevo Trámite
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width: 15%;">Código</th>
                            <th style="width: 25%;">Tipo de Trámite</th>
                            <th style="width: 15%;">Estado Trámite</th>
                            <th style="width: 15%;">Estado Pago</th>
                            <th style="width: 15%;">Fecha</th>
                            <th style="width: 15%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tramites as $tramite)
                        <tr>
                            <td class="text-center fw-bold">{{ $tramite->codigo_tramite }}</td>
                            <td>{{ $tramite->tipoTramite->nombre ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="badge" style="background-color: {{ $tramite->estadoTramite->color ?? '#6c757d' }}">
                                    {{ $tramite->estadoTramite->nombre ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge" style="background-color: {{ $tramite->estadoPago->color ?? '#6c757d' }}">
                                    {{ $tramite->estadoPago->nombre ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">{{ $tramite->created_at->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <a href="{{ route('tramite.show', $tramite->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                @if($tramite->estado_tramite_id == 1)
                                <button type="button" class="btn btn-sm btn-danger" onclick="cancelar({{ $tramite->id }}, '{{ $tramite->codigo_tramite }}')">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No has realizado ningún trámite.
                                    <div class="mt-3">
                                        <a href="{{ route('tramite.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Realizar mi primer trámite
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $tramites->links() }}
            </div>
        </div>
    </div>
</div>

<form id="formCancelar" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function cancelar(id, codigo) {
        if (confirm(`¿Estás seguro de cancelar el trámite "${codigo}"?`)) {
            const form = document.getElementById('formCancelar');
            form.action = "{{ url('tramite') }}/" + id + "/cancelar";
            form.submit();
        }
    }
</script>
@endsection
