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
        Route::get('/maya/{id}/edit', [MayaController::class, 'edit'])->name('maya.edit');
        Route::put('/maya/{id}', [MayaController::class, 'update'])->name('maya.update');
        Route::delete('/maya/{id}', [MayaController::class, 'destroy'])->name('maya.destroy');
        Route::get('/maya/dashboard', [MayaController::class, 'dashboard'])->name('maya.dashboard');

        Route::get('/bimestre', [BimestreController::class, 'index'])->name('bimestre.index');
        Route::get('/bimestre/create', [BimestreController::class, 'create'])->name('bimestre.create');
        Route::post('/bimestre', [BimestreController::class, 'store'])->name('bimestre.store');
        Route::get('/bimestre/{id}/edit', [BimestreController::class, 'edit'])->name('bimestre.edit');
        Route::put('/bimestre/{id}', [BimestreController::class, 'update'])->name('bimestre.update');
        Route::delete('/bimestre/{id}', [BimestreController::class, 'destroy'])->name('bimestre.destroy');

        Route::get('/unidad', [UnidadController::class, 'index'])->name('unidad.index');
        Route::get('/unidad/create', [UnidadController::class, 'create'])->name('unidad.create');
        Route::post('/unidad', [UnidadController::class, 'store'])->name('unidad.store');
        Route::get('/unidad/{id}/edit', [UnidadController::class, 'edit'])->name('unidad.edit');
        Route::put('/unidad/{unidad}', [UnidadController::class, 'update'])->name('unidad.update');
        Route::delete('/unidad/{id}', [UnidadController::class, 'destroy'])->name('unidad.destroy');

        Route::get('/semana', [SemanaController::class, 'index'])->name('semana.index');
        Route::get('/semana/create', [SemanaController::class, 'create'])->name('semana.create');
        Route::post('/semana', [SemanaController::class, 'store'])->name('semana.store');
        Route::get('/semana/{id}/edit', [SemanaController::class, 'edit'])->name('semana.edit');
        Route::put('/semana/{semana}', [SemanaController::class, 'update'])->name('semana.update');
        Route::delete('/semana/{id}', [SemanaController::class, 'destroy'])->name('semana.destroy');

        Route::get('/clase', [ClaseController::class, 'index'])->name('clase.index');
        Route::get('/clase/create', [ClaseController::class, 'create'])->name('clase.create');
        Route::post('/clase', [ClaseController::class, 'store'])->name('clase.store');
        Route::get('/clase/{id}/edit', [ClaseController::class, 'edit'])->name('clase.edit');
        Route::put('/clase/{clase}', [ClaseController::class, 'update'])->name('clase.update');
        Route::delete('/clase/{id}', [ClaseController::class, 'destroy'])->name('clase.destroy');

        Route::get('/tema', [TemaController::class, 'index'])->name('tema.index');
        Route::get('/tema/create', [TemaController::class, 'create'])->name('tema.create');
        Route::post('/tema', [TemaController::class, 'store'])->name('tema.store');
        Route::get('/tema/{id}/edit', [TemaController::class, 'edit'])->name('tema.edit');
        Route::put('/tema/{tema}', [TemaController::class, 'update'])->name('tema.update');
        Route::delete('/tema/{id}', [TemaController::class, 'destroy'])->name('tema.destroy');

        Route::get('/criterio', [CriterioController::class, 'index'])->name('criterio.index');
        Route::get('/criterio/create', [CriterioController::class, 'create'])->name('criterio.create');
        Route::post('/criterio', [CriterioController::class, 'store'])->name('criterio.store');
        Route::get('/criterio/{id}/edit', [CriterioController::class, 'edit'])->name('criterio.edit');
        Route::put('/criterio/{criterio}', [CriterioController::class, 'update'])->name('criterio.update');
        Route::delete('/criterio/{id}', [CriterioController::class, 'destroy'])->name('criterio.destroy');

        Route::get('/user', [UserController::class, 'index'])->name('user.index');
        Route::get('/user/create', [UserController::class, 'create'])->name('user.create');
        Route::post('/user', [UserController::class, 'store'])->name('user.store');
        Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
        //Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');
        //ruta de usuarios mediante ajax
        Route::get('/usuarios/activos', [UserController::class, 'ajaxUserActivo'])->name('usuarios.activos');
        Route::get('/usuarios/lectores', [UserController::class, 'ajaxUserLector'])->name('usuarios.lectores');
        Route::get('/usuarios/inactivos', [UserController::class, 'ajaxUserInactivo'])->name('usuarios.inactivos');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

        Route::get('/apoderados/search', [ApoderadoController::class, 'search'])->name('apoderados.search');






        Route::get('/grado', [GradoController::class, 'index'])->name('grado.index');
        Route::get('/grado/create', [GradoController::class, 'create'])->name('grado.create');
        Route::post('/grado', [GradoController::class, 'store'])->name('grado.store');
        Route::get('/grado/{id}/edit', [GradoController::class, 'edit'])->name('grado.edit');
        Route::put('/grado/{grado}', [GradoController::class, 'update'])->name('grado.update');
        Route::delete('/grado/{id}', [GradoController::class, 'destroy'])->name('grado.destroy');

        Route::get('/materia', [MateriaController::class, 'index'])->name('materia.index');
        Route::get('/materia/create', [MateriaController::class, 'create'])->name('materia.create');
        Route::post('/materia', [MateriaController::class, 'store'])->name('materia.store');
        Route::get('/materia/{id}/edit', [MateriaController::class, 'edit'])->name('materia.edit');
        Route::put('/materia/{materia}', [MateriaController::class, 'update'])->name('materia.update');
        Route::delete('/materia/{id}', [MateriaController::class, 'destroy'])->name('materia.destroy');
    });

    //rutas para docente
    Route::controller(DasboardController::class)->group(function () {
        Route::get('/docente', 'docente')->name('docente.dashboard');
    });
});
