<!-- Director -->
                        @php
                            $esDirector = $user->director !== null;
                            $oldEstadoDirector = old('estado_director', $esDirector ? $user->director->estado : 1);
                        @endphp

                        <div id="campos-director" class="campos-rol mb-4" style="display: none;">
                            <div class="card border-danger">
                                <div class="card-header bg-danger bg-opacity-10 text-danger">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-badge me-2"></i>Datos del Director
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No se requieren datos adicionales para el rol de Director.
                                    </div>
                                </div>
                            </div>
                        </div>
