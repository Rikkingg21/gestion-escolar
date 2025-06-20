<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ColegioController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\Maya\MayaController;
use App\Http\Controllers\Maya\BimestreController;
use App\Http\Controllers\Maya\UnidadController;
use App\Http\Controllers\Maya\SemanaController;
use App\Http\Controllers\Maya\ClaseController;
use App\Http\Controllers\Maya\TemaController;
use App\Http\Controllers\Maya\CriterioController;

use App\Http\Controllers\SessionSelectionController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ApoderadoController;
use App\Models\Apoderado;
use App\Models\Estudiante;

// Rutas públicas
Route::redirect('/', '/login');

// Rutas de autenticación
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'index')->name('index');
    Route::post('/login', 'login')->name('login');;
    Route::post('/logout', 'logout')->name('logout');
});

// Grupo de rutas que requieren autenticación
Route::middleware('auth')->group(function () {
    // Selección de rol
    Route::controller(SessionSelectionController::class)->group(function () {
        Route::get('/select-session', 'showSessionSelection')->name('session.selection');
        Route::post('/select-session', 'selectSession')->name('session.select');
    });


    // Redirección post-login
    Route::get('/home', function () {
        return match(session('current_role')) {
            'admin', 'director' => redirect()->route('admin.dashboard'),
            'docente' => redirect()->route('docente.dashboard'),
            'auxiliar' => redirect()->route('auxiliar.dashboard'),
            'estudiante' => redirect()->route('estudiante.dashboard'),
            'apoderado' => redirect()->route('apoderado.dashboard'),
            default => redirect()->route('role.selection')
        };
    })->name('home');



});
