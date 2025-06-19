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

use App\Http\Controllers\RoleSelectionController;
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
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->name('logout');
});

// Grupo de rutas que requieren autenticación
Route::middleware('auth')->group(function () {
    // Selección de rol
    Route::controller(RoleSelectionController::class)->group(function () {
        Route::get('/select-role', 'showRoleSelection')->name('role.selection');
        Route::post('/select-role', 'selectRole')->name('role.select');
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
    //rutas para admin
    Route::middleware('check.selected.role:admin')->group(function () {
        Route::get('/colegioconfig/edit', [ColegioController::class, 'edit'])
        ->name('colegioconfig.edit');

        Route::put('/colegioconfig/{colegio}', [ColegioController::class, 'update'])
        ->name('colegioconfig.update');
    });

    // Rutas para admin/director (requieren rol seleccionado)
    Route::middleware('check.selected.role:admin,director')->group(function () {
        Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        Route::resource('users', UserController::class)->except(['show'])->names([
            'index' => 'users.index',
            'create' => 'users.create',
            'store' => 'users.store',
            'edit' => 'users.edit',
            'update' => 'users.update',
            'destroy' => 'users.destroy'
        ]);
        Route::resource('estudiantes', EstudianteController::class)->except(['show'])->names([
            'index' => 'estudiantes.index',
            'create' => 'estudiantes.create',
            'store' => 'estudiantes.store',
            'edit' => 'estudiantes.edit',
            'update' => 'estudiantes.update',
            'destroy' => 'estudiantes.destroy'
        ]);
        Route::resource('mayas', MayaController::class)->except(['show'])->names([
            'index' => 'mayas.index',
            'create' => 'mayas.create',
            'store' => 'mayas.store',
            'edit' => 'mayas.edit',
            'update' => 'mayas.update',
            'destroy' => 'mayas.destroy'
        ]);
        Route::get('/mayas/dashboard', [MayaController::class, 'dashboard'])->name('mayas.dashboard');
        Route::resource('bimestres', BimestreController::class)->except(['show'])->names([
            //'index' => 'bimestres.index',
            //'create' => 'bimestres.create',
            'store' => 'bimestres.store',
            'edit' => 'bimestres.edit',
            'update' => 'bimestres.update',
            'destroy' => 'bimestres.destroy'
        ]);
        Route::get('bimestres/{curso_grado_sec_niv_anio_id}', [BimestreController::class, 'index'])->name('bimestres.index');
        Route::get('bimestres/{curso_grado_sec_niv_anio_id}/create', [BimestreController::class, 'create'])->name('bimestres.create');
        Route::post('/bimestres/store-from-dashboard', [BimestreController::class, 'storeFromDashboard'])->name('bimestres.store_from_dashboard');
        Route::delete('/bimestres/destroy-from-dashboard/{id}', [BimestreController::class, 'destroyFromDashboard'])->name('bimestres.destroy_from_dashboard');

        Route::resource('unidades', UnidadController::class)->except(['show'])->parameters(['unidades' => 'unidad'])->names([
            //'index' => 'unidades.index',
            //'create' => 'unidades.create',
            'store' => 'unidades.store',
            'edit' => 'unidades.edit',
            'update' => 'unidades.update',
            'destroy' => 'unidades.destroy'
        ]);
        Route::get('unidades/{bimestre_id}', [UnidadController::class, 'index'])->name('unidades.index');
        Route::get('unidades/{bimestre_id}/create', [UnidadController::class, 'create'])->name('unidades.create');

        Route::resource('semanas', SemanaController::class)->except(['show'])->parameters(['semanas' => 'semana'])->names([
            //'index' => 'semanas.index',
            //'create' => 'semanas.create',
            'store' => 'semanas.store',
            'edit' => 'semanas.edit',
            'update' => 'semanas.update',
            'destroy' => 'semanas.destroy'
        ]);
        Route::get('semanas/{unidad_id}', [SemanaController::class, 'index'])->name('semanas.index');
        Route::get('semanas/{unidad_id}/create', [SemanaController::class, 'create'])->name('semanas.create');

        Route::resource('clases', ClaseController::class)->except(['show'])->names([
            //'index' => 'clases.index',
            //'create' => 'clases.create',
            'store' => 'clases.store',
            'edit' => 'clases.edit',
            'update' => 'clases.update',
            'destroy' => 'clases.destroy'
        ]);
        Route::get('clases/{semana_id}', [ClaseController::class, 'index'])->name('clases.index');
        Route::get('clases/{semana_id}/create', [ClaseController::class, 'create'])->name('clases.create');

        Route::resource('temas', TemaController::class)->except(['show'])->names([
            //'index' => 'temas.index',
            //'create' => 'temas.create',
            'store' => 'temas.store',
            'edit' => 'temas.edit',
            'update' => 'temas.update',
            'destroy' => 'temas.destroy'
        ]);
        Route::get('temas/{clase_id}', [TemaController::class, 'index'])->name('temas.index');
        Route::get('temas/{clase_id}/create', [TemaController::class, 'create'])->name('temas.create');

        Route::resource('criterios', CriterioController::class)->except(['show'])->names([
            //'index' => 'criterios.index',
            //'create' => 'criterios.create',
            'store' => 'criterios.store',
            'edit' => 'criterios.edit',
            'update' => 'criterios.update',
            'destroy' => 'criterios.destroy'
        ]);
        Route::get('criterios/{tema_id}', [CriterioController::class, 'index'])->name('criterios.index');
        Route::get('criterios/{tema_id}/create', [CriterioController::class, 'create'])->name('criterios.create');

        Route::resource('docente', DocenteController::class)->except(['show'])->names([
            'index' => 'docente.index',
            'create' => 'docente.create',
            'store' => 'docente.store',
            'edit' => 'docente.edit',
            'update' => 'docente.update',
            'destroy' => 'docente.destroy'
        ]);

        Route::resource('grados', GradoController::class)->except(['show'])->names([
            'index' => 'grados.index',
            'create' => 'grados.create',
            'store' => 'grados.store',
            'edit' => 'grados.edit',
            'update' => 'grados.update',
            'destroy' => 'grados.destroy'
        ]);
        Route::resource('materias', MateriaController::class)->except(['show'])->names([
            'index' => 'materias.index',
            'create' => 'materias.create',
            'store' => 'materias.store',
            'edit' => 'materias.edit',
            'update' => 'materias.update',
            'destroy' => 'materias.destroy'
        ]);
        Route::resource('apoderados', ApoderadoController::class)->except(['show'])->names([
            'index' => 'apoderados.index',
            'create' => 'apoderados.create',
            'store' => 'apoderados.store',
            'edit' => 'apoderados.edit',
            'update' => 'apoderados.update',
            'destroy' => 'apoderados.destroy'
        ]);
        Route::get('/apoderados/search', [ApoderadoController::class, 'search'])->name('apoderados.search');

        // Rutas DataTables
        Route::get('/users/datatable', [UserController::class, 'usersDatatable'])->name('users.datatable');
        Route::get('/directores/datatable', [UserController::class, 'directoresDatatable'])->name('directores.datatable');
        Route::get('/docentes/datatable', [UserController::class, 'docentesDatatable'])->name('docentes.datatable');
        Route::get('/auxiliares/datatable', [UserController::class, 'auxiliaresDatatable'])->name('auxiliares.datatable');
        Route::get('/estudiantes/datatable', [UserController::class, 'estudiantesDatatable'])->name('estudiantes.datatable');
        Route::get('/apoderados/datatable', [UserController::class, 'apoderadosDatatable'])->name('apoderados.datatable');
    });

    // Rutas para docentes (requieren rol seleccionado)
    Route::middleware(['auth', 'role:docente'])->group(function () {
        Route::get('/docente/dashboard', [DocenteController::class, 'dashboard'])->name('docente.dashboard');
        // Otras subrutas de docente
    });

    // Rutas para auxiliares (requieren rol seleccionado)
    Route::middleware('check.selected.role:auxiliar')->group(function () {
        Route::get('/auxiliar', [AuxiliarController::class, 'dashboard'])->name('auxiliar.dashboard');
    });
    // Rutas para estudiantes y apoderados (requieren rol seleccionado)
    Route::middleware('check.selected.role:estudiante')->group(function () {
        Route::get('/estudiante', [EstudianteController::class, 'dashboard'])->name('estudiante.dashboard');
    });
    //
    Route::middleware('check.selected.role:apoderado')->group(function () {
        Route::get('/apoderado', [ApoderadoController::class, 'dashboard'])->name('apoderado.dashboard');
    });
});
