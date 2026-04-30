<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SessionSelectionController;

use App\Http\Controllers\Rol\DashboardController;

use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RoleController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ColegioController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\AuxiliarController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ApoderadoController;

use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\PeriodobimestreController;
use App\Http\Controllers\MatriculaController;

use App\Http\Controllers\Maya\MayaController;
use App\Http\Controllers\Maya\BimestreController;
use App\Http\Controllers\Maya\UnidadController;
use App\Http\Controllers\Maya\SemanaController;
use App\Http\Controllers\Maya\ClaseController;
use App\Http\Controllers\Maya\TemaController;
use App\Http\Controllers\Maya\CriterioController;

use App\Http\Controllers\NotaController;
use App\Http\Controllers\ConductaController;
use App\Http\Controllers\LibretaController;

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AsistenciahistorialController;
use App\Http\Controllers\AsistenciabloqueoController;

use App\Http\Controllers\GradoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\Materia\MateriaCompetenciaController;
use App\Http\Controllers\Materia\MateriaCriterioController;

use App\Http\Controllers\ReporteController;


use App\Models\Apoderado;
use App\Models\Estudiante;
use App\Models\Materia\Materiacompetencia;

// Rutas públicas
Route::redirect('/', '/login');

// Rutas de autenticación
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'index')->name('index');
    Route::post('/login', 'login')->name('login');
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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::get('/colegioconfig/edit', [ColegioController::class, 'edit'])->name('colegioconfig.edit');
    Route::put('/colegioconfig/{colegio}', [ColegioController::class, 'update'])->name('colegioconfig.update');

    Route::get('/role', [RoleController::class, 'index'])->name('role.index');
    Route::get('/role/create', [RoleController::class, 'create'])->name('role.create');
    Route::post('/role', [RoleController::class, 'store'])->name('role.store');
    Route::get('/role/{id}/edit', [RoleController::class, 'edit'])->name('role.edit');
    Route::put('/role/{id}', [RoleController::class, 'update'])->name('role.update');
    Route::delete('/role/{id}', [RoleController::class, 'destroy'])->name('role.destroy');

    Route::get('/role-module/{id}', [RoleController::class, 'module'])->name('role.module');
    Route::post('/role-module/{id}/assign-module', [RoleController::class, 'assignModule'])->name('role.assign-module');
    Route::delete('/role-module/{roleId}/remove-module/{moduleId}', [RoleController::class, 'removeModule'])->name('role.remove-module');

    Route::get('/module', [ModuleController::class, 'index'])->name('module.index');
    Route::get('/module/create', [ModuleController::class, 'create'])->name('module.create');
    Route::post('/module', [ModuleController::class, 'store'])->name('module.store');
    Route::get('/module/{id}/edit', [ModuleController::class, 'edit'])->name('module.edit');
    Route::put('/module/{id}', [ModuleController::class, 'update'])->name('module.update');
    Route::delete('/module/{id}', [ModuleController::class, 'destroy'])->name('module.destroy');

    Route::get('/periodo', [PeriodoController::class, 'index'])->name('periodo.index');
    Route::get('/periodo/create', [PeriodoController::class, 'create'])->name('periodo.create');
    Route::post('/periodo', [PeriodoController::class, 'store'])->name('periodo.store');
    Route::get('/periodo/{id}/edit', [PeriodoController::class, 'edit'])->name('periodo.edit');
    Route::put('/periodo/{id}', [PeriodoController::class, 'update'])->name('periodo.update');
    Route::delete('/periodo/{id}', [PeriodoController::class, 'destroy'])->name('periodo.destroy');

    Route::get('/periodo/{nombre_periodo}/bimestres', [PeriodobimestreController::class, 'index'])->name('periodobimestre.index');
    Route::get('/periodo/{nombre_periodo}/bimestres/create', [PeriodobimestreController::class, 'create'])->name('periodobimestre.create');
    Route::post('/periodo/{nombre_periodo}/bimestres', [PeriodobimestreController::class, 'store'])->name('periodobimestre.store');
    Route::get('/periodo/{nombre_periodo}/bimestres/{id}/edit', [PeriodobimestreController::class, 'edit'])->name('periodobimestre.edit');
    Route::put('/periodo/{nombre_periodo}/bimestres/{id}', [PeriodobimestreController::class, 'update'])->name('periodobimestre.update');
    Route::delete('/periodo/{nombre_periodo}/bimestres/{id}', [PeriodobimestreController::class, 'destroy'])->name('periodobimestre.destroy');

    Route::get('/matricula', function() {
        return redirect()->route('matricula.index', ['nombre' => 'anioActual']);
    });
    Route::get('/matricula/{nombre}', [MatriculaController::class, 'index'])->name('matricula.index');
    Route::get('/matricula/create', [MatriculaController::class, 'create'])->name('matricula.create');

    Route::get('/matricula/{id}/edit', [MatriculaController::class, 'edit'])->name('matricula.edit');
    Route::put('/matricula/{id}', [MatriculaController::class, 'update'])->name('matricula.update');
    Route::delete('/matricula/{id}', [MatriculaController::class, 'destroy'])->name('matricula.destroy');
    Route::get('/matricula/grado/{nombre}/{grado_id}', [MatriculaController::class, 'grado'])->name('matricula.grado');
    Route::post('/matricula', [MatriculaController::class, 'store'])->name('matricula.store');
    Route::post('/matricula/masiva', [MatriculaController::class, 'matricularMasivamente'])->name('matricula.masiva');
    Route::put('/matricula/{matricula}/estado', [MatriculaController::class, 'cambiarEstado'])->name('matricula.estado');

    Route::get('/maya', [MayaController::class, 'index'])->name('maya.index');
    Route::get('/maya/create', [MayaController::class, 'create'])->name('maya.create');
    Route::post('/maya', [MayaController::class, 'store'])->name('maya.store');
    Route::get('/maya/{id}/edit', [MayaController::class, 'edit'])->name('maya.edit');
    Route::put('/maya/{id}', [MayaController::class, 'update'])->name('maya.update');
    Route::delete('/maya/{id}', [MayaController::class, 'destroy'])->name('maya.destroy');
    Route::get('/maya/dashboard', [MayaController::class, 'dashboard'])->name('maya.dashboard');

    Route::get('/nota/{curso_grado_sec_niv_anio_id}/{periodo_bimestre_id}', [NotaController::class, 'index'])->name('nota.index');
    Route::post('/nota-guardar', [NotaController::class, 'guardarNotas'])->name('nota.guardarNotas');
    Route::post('nota/publicar/{curso_grado_sec_niv_anio_id}/{periodo_bimestre_id}', [NotaController::class, 'publicar'])->name('nota.publicar');
    Route::post('nota/revertir/{curso_grado_sec_niv_anio_id}/{periodo_bimestre_id}', [NotaController::class, 'revertir'])->name('nota.revertir');
    Route::get('nota/revertir-form/{curso_grado_sec_niv_anio_id}/{periodo_bimestre_id}', [NotaController::class, 'showRevertirForm'])->name('nota.revertir.form');

    Route::get('/notas/exportar-excel/{curso_grado_sec_niv_anio_id}/{periodo_bimestre_id}', [NotaController::class, 'exportarExcel'])->name('notas.exportar.excel');

    Route::get('/user', [UserController::class, 'index'])->name('user.index');
    Route::get('/user/create', [UserController::class, 'create'])->name('user.create');

    Route::post('/user', [UserController::class, 'store'])->name('user.store');
    Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');

    //ruta de usuarios mediante ajax
    Route::get('/usuarios/activos', [UserController::class, 'ajaxUserActivo'])->name('usuarios.activos');
    Route::get('/usuarios/lectores', [UserController::class, 'ajaxUserLector'])->name('usuarios.lectores');
    Route::get('/usuarios/inactivos', [UserController::class, 'ajaxUserInactivo'])->name('usuarios.inactivos');
    //Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    Route::delete('/users/{user}/remove-relacion-no-protegidos/', [UserController::class, 'removeRelacionRolNoProtegidos'])->name('users.remove-role');

    Route::get('/apoderados/search', [ApoderadoController::class, 'search'])->name('apoderados.search');

    Route::get('/user/importar', [UserController::class, 'importar'])->name('user.importar');
    Route::post('/users/importar/apoderados', [UserController::class, 'importarApoderados'])->name('importar.apoderados');
    Route::post('/users/importar/estudiantes', [UserController::class, 'importarEstudiantes'])->name('importar.estudiantes');

    Route::get('/grado', [GradoController::class, 'index'])->name('grado.index');

    Route::get('/grado/estudiantes/{id}', [GradoController::class, 'estudiantes'])->name('grado.estudiantes');
    Route::put('/grado/estudiantes/{grado}', [GradoController::class, 'estudiantesUpdateGrado'])->name('grado.estudiantesupdategrado');

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

    Route::get('/materia-competencia/importar', [MateriaCompetenciaController::class, 'importar'])->name('materiacompetencia.importar');
    Route::post('/materia-competencia/importar/competencia', [MateriaCompetenciaController::class, 'importarCompetencia'])->name('importar.competencia');

    Route::get('/materia-competencia', [MateriaCompetenciaController::class, 'index'])->name('materiacompetencia.index');
    Route::get('/materia-competencia/create', [MateriaCompetenciaController::class, 'create'])->name('materiacompetencia.create');
    Route::post('/materia-competencia/crear', [MateriaCompetenciaController::class, 'store'])->name('materiacompetencia.store');
    Route::get('/materia-competencia/{materiacompetencia}/edit', [MateriaCompetenciaController::class, 'edit'])->name('materiacompetencia.edit');
    Route::put('/materia-competencia/{materiacompetencia}', [MateriaCompetenciaController::class, 'update'])->name('materiacompetencia.update');
    Route::delete('/materia-competencia/{id}', [MateriaCompetenciaController::class, 'destroy'])->name('materiacompetencia.destroy');

    Route::get('/materia-criterio/importar', [MateriaCriterioController::class, 'importar'])->name('materiacriterio.importar');
    Route::post('/materia-criterio/importar/criterio', [MateriaCriterioController::class, 'importarCriterio'])->name('importar.criterio');

    Route::get('/materia-criterio/importar-periodo-anterior', [MateriaCriterioController::class, 'importarPeriodo'])->name('materiacriterio.importarPeriodoAnterior');

    Route::get('/materia-criterio', [MateriaCriterioController::class, 'index'])->name('materiacriterio.index');
    Route::get('/materia-criterio/create', [MateriaCriterioController::class, 'create'])->name('materiacriterio.create');
    Route::post('/materia-criterio/crear', [MateriaCriterioController::class, 'store'])->name('materiacriterio.store');
    Route::get('/materia-criterio/{materiacriterio}/edit', [MateriaCriterioController::class, 'edit'])->name('materiacriterio.edit');
    Route::put('/materia-criterio/{materiacriterio}', [MateriaCriterioController::class, 'update'])->name('materiacriterio.update');
    Route::delete('/materia-criterio/{id}', [MateriaCriterioController::class, 'destroy'])->name('materiacriterio.destroy');
    Route::get('/materiacriterio/bimestres/{periodo_id}', [MateriacriterioController::class, 'getBimestres'])->name('materiacriterio.bimestres');
    Route::get('/api/competencias-por-materia/{materiaId}', function($materiaId) {
        $competencias = Materiacompetencia::where('materia_id', $materiaId)
            ->where('estado', '1')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($competencias);
    });

    Route::get('/conducta', [ConductaController::class, 'index'])->name('conducta.index');
    Route::get('/conducta/create', [ConductaController::class, 'create'])->name('conducta.create');
    Route::post('/conducta', [ConductaController::class, 'store'])->name('conducta.store');
    Route::get('/conducta/{conducta}/edit', [ConductaController::class, 'edit'])->name('conducta.edit');
    Route::put('/conducta/{conducta}', [ConductaController::class, 'update'])->name('conducta.update');
    Route::delete('/conducta/{conducta}', [ConductaController::class, 'destroy'])->name('conducta.destroy');

    Route::post('/asignar-conductas', [ConductaController::class, 'asignarConductas'])->name('conducta.asignar');
    Route::post('/migrar-conductas', [ConductaController::class, 'migrarConductas'])->name('conducta.migrar');
    Route::get('/conducta/conductas-por-bimestre/{periodoBimestreId}', [ConductaController::class, 'getConductasAsignadas'])->name('conducta.por-bimestre');
    Route::get('/periodo-inactivo/{periodo_id}', [ConductaController::class, 'showPeriodoInactivo'])->name('conducta.periodo-inactivo');

    Route::delete('/eliminar-conducta-bimestre', [ConductaController::class, 'eliminarConductaBimestre'])->name('conducta.eliminar-bimestre');

    Route::get('/reporte', [ReporteController::class, 'index'])->name('reporte.index');
    Route::get('/reporte/create', [ReporteController::class, 'create'])->name('reporte.create');
    Route::get('/reporte/{id}', [ReporteController::class, 'show'])->name('reporte.show');
    Route::post('/reporte', [ReporteController::class, 'store'])->name('reporte.store');
    Route::get('/reporte/{id}/edit', [ReporteController::class, 'edit'])->name('reporte.edit');
    Route::put('/reporte/{reporte}', [ReporteController::class, 'update'])->name('reporte.update');
    Route::delete('/reporte/{id}', [ReporteController::class, 'destroy'])->name('reporte.destroy');

    Route::get('/libreta/{anio}/{bimestre}', [LibretaController::class, 'index'])->name('libreta.index');
    Route::post('/libreta/{anio}/{bimestre}/pdf', [LibretaController::class, 'pdf'])->name('libreta.pdf');

    Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::get('/asistencia/bimestres-por-periodo/{periodo_id}', [AsistenciaController::class, 'getBimestresByPeriodo'])->name('asistencia.bimestres-por-periodo');
    Route::get('/asistencia/obtener-info-fecha', [AsistenciaController::class, 'obtenerInfoFecha'])->name('asistencia.obtener-info-fecha');
    Route::post('/asistencia/marcar-todos-puntualidad', [AsistenciaController::class, 'marcarTodosPuntualidad'])->name('asistencia.marcar-todos-puntualidad');
    Route::post('/asistencia/marcar-todos-tardanza', [AsistenciaController::class, 'marcarTodosTardanza'])->name('asistencia.marcar-todos-tardanza');
    Route::get('/asistencia/verificar-bloqueo-fecha', [AsistenciaController::class, 'verificarBloqueoFecha'])->name('asistencia.verificar-bloqueo-fecha');
    Route::get('/asistencia/obtener-bimestre-y-estado-por-fecha', [AsistenciaController::class, 'obtenerBimestreYEstadoPorFecha'])->name('asistencia.obtener-bimestre-y-estado-por-fecha');
    Route::post('/asistencia/marcar-resto-puntualidad', [AsistenciaController::class, 'marcarRestoDeEstudiantesConPuntualidad'])->name('asistencia.marcar-resto-puntualidad');
    Route::post('/asistencia/marcar-resto-tardanza', [AsistenciaController::class, 'marcarRestoDeEstudiantesConTardanza'])->name('asistencia.marcar-resto-tardanza');


    Route::get('/asistencia/{grado_grado_seccion}/{grado_nivel}/{date}', [AsistenciaController::class, 'showDate'])
        ->name('asistencia.grado')
        ->where([
            'grado_grado_seccion' => '[0-9]+[a-zA-Z]+', // Números y letras para grado y sección (ej: 1a, 2b)
            'grado_nivel' => '[a-zA-Z]+', // Solo letras para el nivel
            'date' => '\d{2}-\d{2}-\d{4}' // Formato dd-mm-yyyy
        ]);
    Route::post('marcar-individual/{estudiante}', [AsistenciaController::class, 'marcarIndividual'])->name('asistencia.marcar-individual');
    Route::post('asistencia/{grado}/{fecha}/guardar', [AsistenciaController::class, 'guardarMultiple'])->name('asistencia.guardar-multiple');

    Route::get('/asistencia/reporte', [AsistenciaController::class, 'reporteAsistencia'])->name('asistencia.reporte');
    Route::get('/asistencia/estudiantes-por-grado', [AsistenciaController::class, 'estudiantesPorGrado'])->name('asistencia.estudiantes-por-grado');

    Route::get('/asistencia/bloqueo-asistencias', [AsistenciabloqueoController::class, 'bloqueoView'])->name('bloqueo.view');
    Route::post('/asistencia/bloquear-masivo', [AsistenciabloqueoController::class, 'bloquearMasivo'])->name('asistencia.bloquear-masivo');
    Route::post('/asistencia/bloquear-definitivo-masivo', [AsistenciabloqueoController::class, 'bloquearDefinitivoMasivo'])->name('asistencia.bloquear-definitivo-masivo');
    Route::post('/asistencia/liberar-masivo', [AsistenciabloqueoController::class, 'liberarMasivo'])->name('asistencia.liberar-masivo');
    Route::post('/asistencia/liberar-definitivo-masivo', [AsistenciabloqueoController::class, 'liberarDefinitivoMasivo'])->name('asistencia.liberar-definitivo-masivo');


    Route::get('/historial-asistencia/bimestres-por-periodo/{periodo_id}', [AsistenciahistorialController::class, 'getBimestresByPeriodo'])->name('historial.bimestres-por-periodo');
    Route::get('/historial-asistencia/{periodo_id?}/{periodobimestre_id?}', [AsistenciahistorialController::class, 'calendarioAsistencia'])->name('asistencia.calendario');
});
