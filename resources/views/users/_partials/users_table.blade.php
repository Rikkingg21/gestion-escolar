<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-dark">
                    <tr>
                        <th>DNI</th>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Roles</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>{{ $user->dni }}</td>
                        <td>{{ $user->nombre_usuario }}</td>
                        <td>{{ $user->nombreCompleto }}</td>
                        <td>
                            @foreach($user->roles as $role)
                            <span class="badge bg-{{ $role->color }} mb-1">
                                {{ ucfirst($role->nombre) }}
                            </span>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge bg-{{ $user->estado == 'activo' ? 'success' : 'danger' }}">
                                {{ ucfirst($user->estado) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex">
                                @can('update', $user)
                                <a href="{{ route('users.edit', $user->id) }}"
                                   class="btn btn-sm btn-warning mx-1"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan

                                @can('delete', $user)
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            title="Eliminar"
                                            onclick="return confirm('¿Confirmar eliminación?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No se encontraron usuarios</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($users->hasPages())
            <div class="d-flex justify-content-between align-items-center">
                <div class="showing-results text-muted">
                    Mostrando {{ $users->firstItem() }} a {{ $users->lastItem() }} de {{ $users->total() }} resultados
                </div>
                <div>
                    {{ $users->links('pagination::bootstrap-4') }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
