<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SessionSelectionController;

use App\Http\Controllers\Rol\DasboardController;

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
Route::post('/logout-sub', [LoginController::class, 'logout_sub'])->name('logout_sub');

// Grupo de rutas que requieren autenticación
Route::middleware('auth')->group(function () {
    // Selección de session
    Route::controller(SessionSelectionController::class)->group(function () {
        Route::get('/select-session', 'showSessionSelection')->name('session.selection');
        Route::post('/select-session', 'selectSessionUser')->name('session.select');

    });





    //rutas para admin
    Route::controller(DasboardController::class)->group(function () {
        Route::get('/admin', 'admin')->name('admin.dashboard');
        Route::get('/colegioconfig/edit', [ColegioController::class, 'edit'])->name('colegioconfig.edit');
        Route::put('/colegioconfig/{colegio}', [ColegioController::class, 'update'])->name('colegioconfig.update');
    });



    //rutas para director
    Route::controller(DasboardController::class)->group(function () {
        Route::get('/director', 'director')->name('director.dashboard');
        Route::get('/maya', [MayaController::class, 'index'])->name('maya.index');
        Route::get('/maya/create', [MayaController::class, 'create'])->name('maya.create');
        Route::post('/maya', [MayaController::class, 'store'])->name('maya.store');
        //Route::get('/maya/{id}', [MayaController::class, 'show'])->name('maya.show');
        Route::get('/maya/{id}/edit', [MayaController::class, 'edit'])->name('maya.edit');
        Route::put('/maya/{id}', [MayaController::class, 'update'])->name('maya.update');
        Route::delete('/maya/{id}', [MayaController::class, 'destroy'])->name('maya.destroy');
    });


    //rutas para docente
    Route::controller(DasboardController::class)->group(function () {
        Route::get('/docente', 'docente')->name('docente.dashboard');
    });
});
