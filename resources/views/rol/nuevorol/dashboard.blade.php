@extends('layouts.app')

@section('content')


<div class="container">
    <div class="card shadow-lg p-5 text-center bg-white rounded-3" style="max-width: 38rem; margin: auto;">
        <!-- Animación de entrada con fade -->
        <div class="animate__animated animate__fadeIn">

            <!-- Título principal con gradiente -->
            <h1 class="display-4 fw-bold mb-3" style="background: linear-gradient(to right, #4776E6, #8E54E9); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                ¡Bienvenido a nuestro Sistema!
            </h1>

            <!-- Subtítulo más personal -->
            <p class="lead text-secondary mb-4">
                Nos alegra tenerte aquí. Estamos listos para ayudarte a gestionar tu experiencia educativa.
            </p>

            <!-- Tarjetas de acceso rápido -->
            <div class="row justify-content-center g-3 mb-4">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-book-open fa-2x text-primary mb-3"></i>
                            <h5 class="card-title">Explora</h5>
                            <p class="card-text small">Descubre todas las herramientas disponibles</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-users fa-2x text-success mb-3"></i>
                            <h5 class="card-title">Conecta</h5>
                            <p class="card-text small">Interactúa con tu comunidad educativa</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón de acción principal -->
            <button class="btn btn-primary btn-lg px-5 shadow-sm">
                <i class="fas fa-rocket me-2"></i>Comenzar
            </button>
        </div>
    </div>
</div>

@endsection
