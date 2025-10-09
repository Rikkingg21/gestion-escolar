@extends('layouts.app')
@section('title', 'Módulos del sistema')
@section('content')
    <h1>Modulos</h1>
    <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link active" data-bs-toggle="tab" href="#activo" aria-selected="false" role="tab" tabindex="-1">Activos</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#inactivo" aria-selected="true" role="tab">Inactivos</a>
    </li>
    </ul>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane fade active show" id="activo" role="tabpanel">
            <p>activos</p>
        </div>
        <div class="tab-pane fade" id="inactivo" role="tabpanel">
            <p>Inactivos</p>
        </div>
    </div>
@endsection
