# INFORME TÉCNICO — Sistema de Gestión Escolar

> **Proyecto:** gestion-escolar  
> **Framework:** Laravel 12.x  
> **PHP:** ^8.2  
> **Base de datos:** MySQL (producción), SQLite `:memory:` (testing)  
> **Propósito:** Sistema de gestión escolar para instituciones educativas en Perú  
> **Fecha del informe:** Junio 2026

---

## Índice

1. [Mapa de Archivos](#1-mapa-de-archivos)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura General](#3-arquitectura-general)
4. [Flujo de Autenticación y Autorización](#4-flujo-de-autenticación-y-autorización)
5. [Flujo de Datos por Módulo](#5-flujo-de-datos-por-módulo)
6. [Base de Datos — Esquema Completo](#6-base-de-datos--esquema-completo)
7. [Modelos y Relaciones Clave](#7-modelos-y-relaciones-clave)
8. [Servicios (Lógica de Negocio)](#8-servicios-lógica-de-negocio)
9. [Controladores y Rutas](#9-controladores-y-rutas)
10. [Vistas y Frontend](#10-vistas-y-frontend)
11. [Testing y CI/CD](#11-testing-y-cicd)
12. [Resumen de lo Construido](#12-resumen-de-lo-construido)

---

## 1. Mapa de Archivos

```
gestion-escolar/
│
├── AGENTS.md                          # Instrucciones para IA (convenciones, comandos)
├── .env                               # Configuración de entorno (MySQL, sesiones, etc.)
├── composer.json                      # Dependencias PHP
├── package.json                       # Dependencias Node (solo build tools)
├── vite.config.js                     # Vite + laravel-vite-plugin
├── phpunit.xml                        # Configuración PHPUnit 11
│
├── app/
│   ├── Console/Commands/
│   │   ├── ClearSessions.php          # Artisan: limpiar sesiones
│   │   └── TestCoreCommand.php        # Comando de prueba vacío
│   │
│   ├── Http/Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php        # Autenticación (nombre_usuario + password)
│   │   │   ├── ForgotPasswordController.php
│   │   │   ├── ResetPasswordController.php
│   │   │   ├── VerificationController.php
│   │   │   └── ConfirmPasswordController.php
│   │   ├── Materia/
│   │   │   ├── MateriaCompetenciaController.php
│   │   │   └── MateriaCriterioController.php
│   │   ├── Maya/
│   │   │   └── MayaController.php          # Planificación curricular
│   │   ├── Metodopago/
│   │   │   └── TipopagoController.php
│   │   ├── Rol/
│   │   │   └── DashboardController.php     # ~1000 líneas, ruteo por rol
│   │   ├── Tramite/
│   │   │   ├── TramiteController.php       # Trámites del usuario
│   │   │   └── TramiteadminController.php  # Gestión admin de trámites
│   │   ├── ApoderadoController.php
│   │   ├── AsistenciaController.php
│   │   ├── AsistenciabloqueoController.php
│   │   ├── AsistenciahistorialController.php
│   │   ├── ColegioController.php
│   │   ├── ConductaController.php
│   │   ├── Controller.php                  # Base controller
│   │   ├── GradoController.php
│   │   ├── LibretaController.php           # Generación PDF de libretas
│   │   ├── MateriaController.php
│   │   ├── MatriculaController.php
│   │   ├── ModuleController.php
│   │   ├── NotaController.php
│   │   ├── PeriodoController.php
│   │   ├── PeriodobimestreController.php
│   │   ├── ReporteController.php
│   │   ├── RoleController.php
│   │   ├── SessionSelectionController.php  # Selección de rol/identidad post-login
│   │   └── UserController.php
│   │
│   ├── Models/
│   │   ├── Asistencia/
│   │   │   ├── Asistencia.php              # estudiante_asistencias
│   │   │   └── Tipoasistencia.php          # tipo_asistencias
│   │   ├── Materia/
│   │   │   ├── Materiacompetencia.php
│   │   │   ├── Materiacriterio.php
│   │   │   └── Recuperacioncompetencia.php
│   │   ├── Maya/
│   │   │   ├── Bimestre.php                # maya_bimestres
│   │   │   └── Cursogradosecnivanio.php    # maya_curso_grado_sec_niv_anios
│   │   ├── Metodopago/
│   │   │   └── Tipopago.php                # m_tipo_pagos
│   │   ├── Reporte/
│   │   │   ├── Estadoreporte.php
│   │   │   └── Reporte.php
│   │   ├── Tramite/
│   │   │   ├── Estadopago.php
│   │   │   ├── Estadotramite.php
│   │   │   ├── Pagocomprobante.php
│   │   │   ├── Tramite.php
│   │   │   ├── Tramitepagoregistro.php
│   │   │   ├── Tramiteregistro.php
│   │   │   └── Tramitetipo.php
│   │   ├── Admin.php
│   │   ├── Apoderado.php
│   │   ├── Auxiliar.php
│   │   ├── Colegio.php                     # Singleton (id=1)
│   │   ├── Conducta.php
│   │   ├── Conductanota.php
│   │   ├── Conductaperiodobimestre.php
│   │   ├── Conductaperiodobimestrenota.php
│   │   ├── Director.php
│   │   ├── Docente.php
│   │   ├── Estudiante.php
│   │   ├── Grado.php
│   │   ├── Materia.php
│   │   ├── Matricula.php
│   │   ├── Module.php
│   │   ├── Nota.php
│   │   ├── Periodo.php
│   │   ├── Periodobimestre.php
│   │   ├── Role.php                        # Sistema de roles propio
│   │   ├── Rolemodule.php
│   │   ├── Rolemoduleexception.php
│   │   ├── User.php                        # Modelo principal con SoftDeletes
│   │   └── Userrole.php
│   │
│   ├── Policies/
│   │   └── UserPolicy.php                  # Policy para User CRUD
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php          # Gate, Blade directives, view composers
│   │
│   └── Services/
│       ├── BaseNotasService.php            # Escala cualitativa AD/A/B/C
│       ├── EvaluacionEstudianteService.php # Evaluación de notas mínimas
│       ├── ModuleRouteService.php          # Rutas/iconos personalizados de módulos
│       ├── ModuleService.php               # Autorización por role_modules
│       ├── ProcesarnotasCompetenciaService.php
│       ├── ProcesarnotasCriterioService.php
│       └── ProcesarnotasMateriaService.php
│
├── bootstrap/
│   └── app.php                             # Configuración Laravel 12 (middleware, rutas)
│
├── config/
│   ├── app.php                             # timezone America/Lima, locale es
│   ├── database.php                        # MySQL default, SQLite testing
│   └── ... (otros config de Laravel)
│
├── database/
│   ├── factories/UserFactory.php
│   ├── migrations/                         # 25 migraciones
│   ├── seeders/
│   │   ├── AdminUserSeeder.php
│   │   ├── ConfigIeSeeder.php
│   │   ├── DatabaseSeeder.php
│   │   ├── RolesTableSeeder.php
│   │   └── UsersTableSeeder.php
│   └── database.sqlite                     # Artefacto de testing (committed)
│
├── resources/views/                        # 84 archivos Blade
│   ├── layouts/app.blade.php               # Layout principal (sidebar, navbar)
│   ├── auth/                               # login, select-session
│   ├── rol/                                # 7 dashboards por rol
│   ├── asistencia/                         # 5 vistas
│   ├── conducta/                           # 4 vistas
│   ├── grado/                              # 4 vistas
│   ├── libreta/                            # 2 vistas (index + PDF)
│   ├── materia/                            # 11 vistas (incluye competencias/criterios)
│   ├── matricula/                          # 2 vistas
│   ├── metodopago/                         # 4 vistas
│   ├── modulos/maya/                       # 3 vistas
│   ├── nota/                               # 2 vistas
│   ├── periodo/                            # 3 vistas
│   ├── periodobimestre/                    # 1 vista
│   ├── reporte/                            # 3 vistas
│   ├── role/                               # 4 vistas
│   ├── module/                             # 3 vistas
│   ├── tramite/                            # 5 vistas
│   ├── user/                               # 5 vistas + 6 partials
│   └── errors/                             # 403, 404, 419
│
├── routes/
│   ├── web.php                             # ~283 líneas, todas las rutas principales
│   ├── api.php                             # 3 endpoints JSON
│   └── console.php
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
└── .github/workflows/tests.yml            # CI: lint + test en push/PR
```

---

## 2. Stack Tecnológico

### Backend

| Componente | Tecnología | Versión | Propósito |
|---|---|---|---|
| Framework | Laravel | 12.x | MVC, ORM, routing, auth |
| Lenguaje | PHP | ^8.2 | Backend |
| Motor de plantillas | Blade | — | Vistas del lado del servidor |
| ORM | Eloquent | — | Base de datos |
| Base de datos | MySQL | — | Producción |
| Base de datos testing | SQLite | `:memory:` | Tests unitarios/feature |
| PDF | barryvdh/laravel-dompdf | ^3.1 | Generación de libretas |
| Excel | maatwebsite/excel | ^1.1 | Importación/exportación de usuarios y notas |
| Spreadsheets | phpoffice/phpspreadsheet | ^5.2 | Manipulación de hojas de cálculo |
| Tinker | laravel/tinker | ^2.10 | REPL de artisan |
| Testing | PHPUnit | ^11.5 | Pruebas automatizadas |
| Code Style | laravel/pint | ^1.13 | Formateo de código PHP |

### Frontend

| Componente | CDN / Cargado desde | Propósito |
|---|---|---|
| Bootstrap 5 | CDN via layout | UI framework |
| jQuery | CDN | DOM manipulation |
| DataTables | CDN | Tablas dinámicas con búsqueda/paginación |
| Select2 | CDN | Selectores autocompletados |
| Chart.js | CDN | Gráficos en dashboards |
| SweetAlert2 | CDN | Alertas modales interactivas |
| Font Awesome | CDN | Iconos |
| Vite | `npm run dev/build` | Configurado pero **no enlazado a ninguna vista Blade** |

> **Nota importante:** Aunque Vite está configurado con `laravel-vite-plugin`, **ninguna vista Blade usa `@vite()`**. Todos los assets se cargan mediante CDN. Vite solo existe como infraestructura potencial.

---

## 3. Arquitectura General

### Patrón

El sistema sigue el patrón **MVC de Laravel** con una capa de **Servicios** para lógica de negocio compleja:

```
Request → Routes → Middleware (auth) → Controller → Service (opcional) → Model (Eloquent) → DB
                                                      ↕
                                              Response (Blade view + data)
```

### Sistema de Autorización (custom, reemplazó a Spatie)

- **Tablas:** `roles`, `modules`, `role_modules` (pivot con `estado`)
- **Mecanismo:** `session('current_role')` contiene el nombre del rol seleccionado
- **Verificación:** `User::canAccessModule($moduleId)` → consulta `role_modules` donde `role_id` coincida con el rol en sesión y `estado = 1`
- **Blade directives:** `@canAccessModule('ModuleName')`, `@hasrole('rolename')`
- **Gate:** `access-module` registrado en `AppServiceProvider`

### Manejo de Sesiones

- **Driver:** `database` (tabla `sessions`)
- **Estructura:** `session('sessionmain')` = usuario autenticado original; `session('current_role')` = rol activo; `sub_session` para logout parcial

---

## 4. Flujo de Autenticación y Autorización

```
1. GET  /login
   → LoginController@index()
   → Muestra formulario con datos del colegio (logo, nombre)

2. POST /login
   → LoginController@login()
   → Valida nombre_usuario + password
   → Autentica con Auth::attempt()
   → Guarda usuario en session('sessionmain')
   → Redirige a /select-session

3. GET  /select-session
   → SessionSelectionController@showSessionSelection()
   → Muestra identidades disponibles según el rol del usuario:
       - admin:       ve TODOS los usuarios del sistema
       - director:    ve todos excepto admins
       - apoderado:   ve su propia cuenta + estudiantes vinculados
       - otros:       ven solo su propia cuenta

4. POST /select-session
   → SessionSelectionController@selectSessionUser()
   → Auth::login() como el usuario seleccionado
   → Guarda session('current_role') = nombre del rol
   → Auth::user() ahora es el usuario seleccionado
   → Redirige a /dashboard

5. GET  /dashboard
   → DashboardController@index()
   → Según session('current_role'), renderiza:
       - admin       → estadísticas de usuarios por rol
       - director    → analítica por grado (promedios, asistencia, conducta)
       - docente     → lista de estudiantes por asignación
       - auxiliar    → control de asistencia por grado
       - apoderado   → progreso de estudiantes a cargo
       - estudiante  → sus propias notas y asistencia
       - nuevorol    → dashboard genérico

6. Acceso a módulos (en cada petición):
   → @canAccessModule directive → Gate::allows('access-module', $moduleName)
   → ModuleService::getActiveModules() → filtra role_modules para el rol actual
   → Si no tiene acceso → muestra vista de acceso denegado (errors/403)
```

---

## 5. Flujo de Datos por Módulo

### 5.1 Planificación Curricular (Maya)

```
Periodo Académico
  └── Bimestres (periodo_bimestres: 4 académicos + 1 recuperación)
      └── Grados (grados: grado + sección + nivel)
          └── Materias (materias)
              └── Cursogradosecnivanio (maya: asigna docente + materia a grado + año)
                  └── Materiacompetencia (competencias vinculadas a la materia)
                      └── Materiacriterio (criterios filtrados por grado + bimestre)
                          └── Notas (estudiante_notas: estudiante + criterio + nota 1-4 AD/A/B/C)
                                  + Conducta (Conductaperiodobimestre + notas por estudiante)
```

### 5.2 Calificaciones (Notas)

```
Selección de periodo + grado + materia + bimestre
  → NotaController@index()
  → Muestra matriz: estudiantes × criterios
  → Carga notas existentes de estudiante_notas
  → Guarda/actualiza notas por AJAX o formulario
  → Publica notas (toggle publico = '1')
  → Revierte notas por lote (soft delete + recreación)
  → Exporta a Excel (Maatwebsite)

Procesamiento de notas (servicios):
  1. ProcesarnotasCriterioService → agrupa por (estudiante_id, criterio_id) → promedio → cualitativo
  2. ProcesarnotasCompetenciaService → agrupa criterios por competencia → promedio + recuperación
  3. ProcesarnotasMateriaService   → agrupa competencias por materia → promedio final
  4. EvaluacionEstudianteService   → determina estado: aprobado/recuperación/sin_evaluación
```

### 5.3 Asistencia

```
Selección de grado + fecha
  → AsistenciaController@index()
  → Muestra listado de estudiantes + tipos de asistencia
  → Marca individual (AJAX) o masiva (formulario)
  → Almacena en estudiante_asistencias (con fecha, hora, registrador_id)
  → Bloqueo: AsistenciabloqueoController → impide marcar fechas pasadas/futuras
  → Reportes: filtros por bimestre, estudiante, tipo
  → Calendario: vista mensual con resumen por día
```

### 5.4 Conducta

```
Selección de periodo + grado + bimestre
  → ConductaController@index()
  → Muestra tabla: estudiantes × conductas predefinidas
  → Asigna notas de conducta (Conductanota)
  → Migración de configuraciones entre periodos
```

### 5.5 Trámites (Tramite)

```
Usuario solicita trámite → TramiteController
  → Selecciona tipo de trámite (Tramitetipo: constancia, certificado, etc.)
  → Ingresa datos del estudiante
  → Sistema genera código único de trámite
  → Registra pago (con comprobante opcional)
  → Admin procesa → TramiteadminController
      → Cambia estados (Tramiteregistro)
      → Confirma pagos (Tramitepagoregistro)
      → Descarga comprobantes
      → Finaliza trámite
```

### 5.6 Matrícula

```
Selección de periodo + grado
  → MatriculaController@index()
  → Matricula masiva desde grado anterior
  → Cambio de estado (activo/retirado/trasladado)
  → Historial por estudiante
```

### 5.7 Libreta (Report Card)

```
Selección de periodo + grado + estudiante + bimestre
  → LibretaController@index()
  → Muestra vista previa HTML con todas las notas, conducta, asistencia
  → Genera PDF con DomPDF
```

### 5.8 Métodos de Pago

```
CRUD de Tipopago (m_tipo_pagos)
  → nombre, categoría (banco, billetera, efectivo), entidad, cuenta, CCI, titular
  → Toggle de estado (activo/inactivo)
  → Configuración de verificación requerida
```

---

## 6. Base de Datos — Esquema Completo

### Tablas del Sistema

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `users` | Cuentas de usuario | dni, nombre_usuario, nombre, apellido_paterno, apellido_materno, email, password, foto_path, estado, telefono |
| `roles` | Roles del sistema (custom) | nombre, descripcion, estado — **SoftDeletes** |
| `user_roles` | Asignación usuario-rol | user_id, role_id |
| `modules` | Módulos de navegación | nombre, icono, ruta_base, estado |
| `role_modules` | Permisos rol-módulo | role_id, module_id, estado |
| `role_module_exceptions` | Excepciones de permisos | role_id, module_id, user_id |

### Tablas Institucionales

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `colegio_config` | Configuración del colegio (singleton) | nombre, direccion, telefono, email, ruc, director_actual, logo_path |
| `periodos` | Períodos académicos | nombre, estado, anio, fecha_inicio, fecha_fin, tipo_periodo |
| `periodo_bimestres` | Bimestres dentro de un período | periodo_id, bimestre (1-5), sigla, fechas, tipo_bimestre (A=académico, R=recuperación) |
| `grados` | Grados/secciones/niveles | grado (1º, 2º...), seccion (A, B...), nivel (inicial, primaria, secundaria), estado |

### Tablas de Personas

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `estudiantes` | Perfiles de estudiante | user_id, grado_id, apoderado_id, fecha_nacimiento, estado |
| `docentes` | Perfiles de docente | user_id, estado |
| `auxiliares` | Perfiles de auxiliar | user_id, turno, funciones, estado |
| `directores` | Perfiles de director | user_id, estado |
| `apoderados` | Perfiles de apoderado | user_id, parentesco, estado |
| `admins` | Perfiles de admin | user_id |

### Tablas Académicas

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `materias` | Materias/asignaturas | nombre, estado |
| `materia_competencias` | Competencias por materia | materia_id, nombre, descripcion, estado |
| `materia_criterios` | Criterios por competencia | materia_competencia_id, materia_id, grado_id, periodo_bimestre_id, nombre, descripcion |
| `maya_curso_grado_sec_niv_anios` | Asignación docente-materia-grado-año | docente_designado_id, grado_id, anio, materia_id, periodo_id |
| `maya_bimestres` | Bimestres de planificación Maya | curso_grado_sec_niv_anio_id, nombre |
| `estudiante_notas` | Calificaciones | estudiante_id, materia_criterio_id, periodo_id, bimestre_id, publico (ENUM), nota (entero) |
| `matriculas` | Matrículas | estudiante_id, periodo_id, grado_id, estado |
| `recuperacion_competencias` | Notas de recuperación | |

### Tablas de Conducta

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `conductas` | Items de conducta predefinidos | nombre, estado |
| `conducta_periodo_bimestres` | Asignación conducta-bimestre | conducta_id, periodo_bimestre_id |
| `conducta_notas` | Notas individuales de conducta | estudiante_id, conducta_id, periodo_bimestre_id, nota |
| `conducta_periodo_bimestre_notas` | Notas agregadas por bimestre | |

### Tablas de Asistencia

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `estudiante_asistencias` | Registros diarios | estudiante_id, grado_id, tipo_asistencia_id, periodo_id, bimestre_id, fecha, hora, registrador_id, estado, descripcion |
| `tipo_asistencias` | Tipos de asistencia | nombre (PUNTUALIDAD, FALTA, TARDANZA...), color_hex |
| `asistencia_bloqueos` | Bloqueo de marcación | |

### Tablas de Trámites

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `m_tramite_tramites` | Solicitudes de trámite | codigo_tramite (único), user_id, tipo_tramite_id, estudiante_id, monto_pagado, fechas |
| `m_tramite_tipo_tramites` | Tipos de trámite | nombre, codigo, descripcion, costo, flags |
| `m_tramite_registros` | Seguimiento de estados | tramite_id, estado_tramite_id, observaciones |
| `m_tramite_pago_registros` | Registros de pago | tramite_id, monto, estado_pago_id |
| `m_tramite_estado_tramites` | Catálogo de estados de trámite | nombre |
| `m_tramite_estado_pagos` | Catálogo de estados de pago | nombre |
| `m_tramite_pago_comprobantes` | Comprobantes de pago (PDF/imagen) | tramite_id, archivo_path |

### Otras Tablas

| Tabla | Propósito |
|---|---|
| `m_tipo_pagos` | Métodos de pago configurables |
| `reportes` | Reportes internos (mensajería) |
| `reporte_estados` | Estados de reporte |
| `sessions` | Sesiones de usuario (driver database) |
| `cache` | Caché (driver database) |
| `jobs` | Cola de trabajos (driver database) |
| `password_resets` | Tokens de reseteo de contraseña |

---

## 7. Modelos y Relaciones Clave

### User (app/Models/User.php)

```php
// Relaciones
- roles()              → belongsToMany(Role::class, 'user_roles')
- estudiante()         → hasOne(Estudiante::class)
- docente()            → hasOne(Docente::class)
- apoderado()          → hasOne(Apoderado::class)
- auxiliar()           → hasOne(Auxiliar::class)
- director()           → hasOne(Director::class)

// Accesors
- getNombreCompletoAttribute() → "ApellidoP ApellidoM, Nombre"

// Métodos clave
- hasRole($role)       → verifica si tiene un rol (string o colección)
- canAccessModule($moduleId) → verifica role_modules para el rol en sesión
- scopeActivos()       → estado = 1
- scopeLectores()      → estado = 2 (lector)
- scopeInactivos()     → estado = 0 (inactivo)

// SoftDeletes
// implements Authenticatable
```

### Role

```php
- users()              → belongsToMany(User::class, 'user_roles')
- modules()            → belongsToMany(Module::class, 'role_modules')->withPivot('estado')
- moduleExceptions()   → hasMany(Rolemoduleexception::class)
```

### Estudiante

```php
- user()               → belongsTo(User::class)
- grado()              → belongsTo(Grado::class)
- apoderado()          → belongsTo(Apoderado::class)
- notas()              → hasMany(Nota::class)
- asistencias()        → hasMany(Asistencia::class)
- matriculas()         → hasMany(Matricula::class)
- matriculaActiva()    → hasOne(Matricula::class)->where('estado', 'activo')
```

### Nota

```php
// Tabla: estudiante_notas
// $casts: ['publico' => 'string', 'nota' => 'integer']
- estudiante()         → belongsTo(Estudiante::class)
- criterio()           → belongsTo(Materiacriterio::class, 'materia_criterio_id')
- periodo()            → belongsTo(Periodo::class)
- periodoBimestre()    → belongsTo(Periodobimestre::class)
- competencia()        → hasOneThrough(Materiacompetencia::class, Materiacriterio::class, ...)
```

### Cursogradosecnivanio (Maya)

```php
// Tabla: maya_curso_grado_sec_niv_anios
- grado()              → belongsTo(Grado::class)
- docente()            → belongsTo(Docente::class, 'docente_designado_id')
- materia()            → belongsTo(Materia::class)
- periodo()            → belongsTo(Periodo::class)
```

### Tramite

```php
// Tabla: m_tramite_tramites
- user()               → belongsTo(User::class)
- tipoTramite()        → belongsTo(Tramitetipo::class)
- estudiante()         → belongsTo(Estudiante::class)
- tramiteRegistros()   → hasMany(Tramiteregistro::class)
- tramitePagoRegistros() → hasMany(Tramitepagoregistro::class)
- comprobantes()       → hasMany(Pagocomprobante::class)
- ultimoEstadoTramite() → hasOne(Tramiteregistro::class)->latest()
- ultimoEstadoPago()   → hasOne(Tramitepagoregistro::class)->latest()
- getMontoPagadoTotalAttribute() → suma de pagos aprobados
```

---

## 8. Servicios (Lógica de Negocio)

### ModuleService

```php
- getActiveModules()         → Obtiene módulos activos para el rol en sesión
- getUserModules()           → Delega a getActiveModules()
- hasAccessToModule($module) → Verifica permiso en role_modules + estado = 1
```

### BaseNotasService (abstracto)

```php
- NOTA_AD = 3.5
- NOTA_A  = 2.5
- NOTA_B  = 1.5
- convertirACualitativo($nota)     → AD (3.5-4), A (2.5-3.49), B (1.5-2.49), C (0-1.49)
- convertirEnumANota($enum)        → AD→4, A→3, B→2, C→1
- convertirNotaAEnum($nota)        → inverso del cualitativo
```

### EvaluacionEstudianteService

```php
- NOTA_MINIMA_APROBACION = 1.5
- getEstadoGeneral($criterios)     → 'aprobado', 'recuperacion', 'sin_evaluacion'
- evaluarAprobacionCompetencia()   → Verifica si el promedio de criterios >= 1.5
- necesitaRecuperacion()           → Si algún criterio está por debajo del mínimo
- enriquecerCompetencias()         → Agrega datos calculados a competencias
- enriquecerMaterias()             → Agrega datos calculados a materias
```

### ProcesarnotasCriterioService

```php
- procesar($materiaCriterios, $estudiantes)
  → Agrupa notas por (estudiante_id, materia_criterio_id)
  → Promedia valores numéricos
  → Convierte a cualitativo (AD/A/B/C)
  → Retorna colección enriquecida
```

### ProcesarnotasCompetenciaService

```php
- procesar($competencias, $materiaCriterios, $estudiantes, $recuperaciones)
  → Agrupa criterios por competencia
  → Aplica notas de recuperación si existen
  → Calcula promedio de competencia
  → Retorna matriz [estudiante][competencia] = nota
```

### ProcesarnotasMateriaService

```php
- procesar($materia, $competencias, $resultados_competencia, $estudiantes)
  → Agrupa resultados de competencias por materia
  → Calcula promedio final de materia
  → Retorna matriz [estudiante][materia] = nota final
```

---

## 9. Controladores y Rutas

### routes/web.php (~283 líneas)

**Rutas Públicas:**
| Método | URI | Controlador |
|---|---|---|
| GET | `/` | Redirect a /login |
| GET | `/login` | LoginController@index |
| POST | `/login` | LoginController@login |
| POST | `/logout` | LoginController@logout |

**Rutas Autenticadas (middleware `auth`):**
| Grupo | URI Base | Propósito |
|---|---|---|
| Sesión | `/select-session` | Selección de rol/identidad |
| Dashboard | `/dashboard` | Dashboard por rol |
| Colegio | `/colegioconfig` | Configuración del colegio |
| Roles | `/role/*`, `/role-module/*` | CRUD de roles + asignación módulos |
| Módulos | `/module/*` | CRUD de módulos |
| Períodos | `/periodo/*` | CRUD de períodos académicos |
| Bimestres | `/periodo/{periodo}/bimestres/*` | CRUD de bimestres |
| Matrícula | `/matricula/*` | Gestión de matrículas |
| Maya | `/maya/*` | Planificación curricular |
| Notas | `/nota/*` | Calificaciones (CRUD, publicar, revertir, exportar) |
| Usuarios | `/user/*` | CRUD + importación + AJAX |
| Grados | `/grado/*` | CRUD + gestión estudiantes |
| Materias | `/materia/*` | CRUD |
| Competencias | `/materia-competencia/*` | CRUD + importar |
| Criterios | `/materia-criterio/*` | CRUD + importar + importar desde período anterior |
| Conducta | `/conducta/*` | CRUD + asignar + migrar |
| Reportes | `/reporte/*` | CRUD |
| Libreta | `/libreta/*` | Vista + PDF |
| Asistencia | `/asistencia/*` | CRUD + bloqueo + historial + calendario + reportes |
| Métodos Pago | `/metodos-de-pago/*` | CRUD + toggle estado |
| Trámites Admin | `/tramite-admin/*` | Gestión administrativa |
| Mis Trámites | `/mis-tramites/*` | Trámites del usuario |

### routes/api.php (29 líneas)

| Método | URI | Propósito |
|---|---|---|
| GET | `/api/grados-por-nivel/{nivel}` | Grados filtrados por nivel |
| GET | `/api/secciones-por-grado/{nivel}/{grado}` | Secciones filtradas |
| GET | `/api/tipo-pago/{id}` | Detalle de tipo de pago |

### DashboardController (Rol/DashboardController.php, ~1000 líneas)

El controlador más grande del sistema. Su método `index()`:
1. Obtiene el rol actual de `session('current_role')`
2. Según el rol, ejecuta lógica diferente:
   - **admin**: cuenta usuarios por rol
   - **director**: consultas analíticas pesadas (promedios por grado, % asistencia, gráficos)
   - **docente**: carga asignaciones del docente, estudiantes, progreso
   - **auxiliar**: datos de asistencia por grado
   - **apoderado**: estudiantes a cargo con notas y conducta
   - **estudiante**: notas y asistencia propias
3. Renderiza la vista de dashboard correspondiente

---

## 10. Vistas y Frontend

### Layout Principal (`resources/views/layouts/app.blade.php`)

- **Sidebar:** Colapsable, muestra módulos según `ModuleService::getActiveModules()`
- **Navbar:** Selector de período activo, información de sesión (rol actual + nombre), logout dropdown
- **Breadcrumbs:** Navegación contextual
- **Scripts CDN cargados:**
  ```html
  <!-- Bootstrap 5 CSS+JS -->
  <!-- jQuery -->
  <!-- DataTables + Bootstrap 5 integration -->
  <!-- Select2 -->
  <!-- Chart.js -->
  <!-- SweetAlert2 -->
  <!-- Font Awesome -->
  ```
- **View Composers (AppServiceProvider):**
  - Comparte `$modulos` (sidebar) calculados por ModuleService
  - Comparte `$colegio` (configuración institucional)

### Dashboards por Rol

| Ruta | Vista | Contenido |
|---|---|---|
| `/dashboard` (admin) | `rol/admin/dashboard.blade.php` | Tarjetas con conteo de usuarios por rol, enlaces rápidos |
| `/dashboard` (director) | `rol/director/dashboard.blade.php` | Selector período/bimestre, tabla de promedios por grado, gráficos Chart.js de rendimiento, conducta y asistencia |
| `/dashboard` (docente) | `rol/docente/dashboard.blade.php` | Lista de asignaciones (grado-materia), progreso por bimestre, % de notas completadas |
| `/dashboard` (auxiliar) | `rol/auxiliar/dashboard.blade.php` | Resumen de asistencia por grado con porcentajes por tipo |
| `/dashboard` (apoderado) | `rol/apoderado/dashboard.blade.php` | Tarjetas de estudiantes a cargo con notas, conducta, recuperación |
| `/dashboard` (estudiante) | `rol/estudiante/dashboard.blade.php` | Notas personales, asistencia, conducta |

### Sistema de Módulos

Los módulos se renderizan dinámicamente en el sidebar. Cada módulo tiene:
- `nombre`: identificador único
- `icono`: clase Font Awesome
- `ruta_base`: ruta base de Laravel para generar enlaces

---

## 11. Testing y CI/CD

### Configuración PHPUnit (`phpunit.xml`)

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
```

### Suites de Tests

- **Unit:** `tests/Unit/` — pruebas unitarias
- **Feature:** `tests/Feature/` — pruebas de integración con `RefreshDatabase`

### Comandos

```bash
vendor/bin/phpunit              # Ejecutar tests
php artisan test                # Ídem con output mejorado
vendor/bin/pint                 # Formatear código PHP
```

### CI/CD (GitHub Actions)

Archivo: `.github/workflows/tests.yml`

```yaml
on: [push, pull_request]
jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
      - run: composer install
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: vendor/bin/pint --test    # Lint
      - run: vendor/bin/phpunit         # Tests
```

---

## 12. Resumen de lo Construido

### Dominios Funcionales Completos

| # | Módulo | Estado | Funcionalidades |
|---|---|---|---|
| 1 | **Autenticación** | ✅ Completo | Login con nombre_usuario, selección de rol/identidad, logout parcial, recuperación de contraseña |
| 2 | **Roles y Permisos** | ✅ Completo | CRUD de roles, asignación de módulos por rol, verificación en backend y frontend (Spatie reemplazado) |
| 3 | **Gestión de Usuarios** | ✅ Completo | CRUD con 6 tipos (admin, director, docente, auxiliar, estudiante, apoderado), importación por Excel, filtros AJAX |
| 4 | **Períodos Académicos** | ✅ Completo | CRUD de períodos, gestión de bimestres (4 académicos + 1 recuperación) |
| 5 | **Grados/Secciones** | ✅ Completo | CRUD por nivel (inicial, primaria, secundaria), gestión de estudiantes por grado |
| 6 | **Matrícula** | ✅ Completo | Matrícula por período, matrícula masiva desde grado anterior, cambios de estado |
| 7 | **Planificación Curricular (Maya)** | ✅ Completo | Asignación docente-materia-grado-año, gestión de competencias y criterios por bimestre |
| 8 | **Calificaciones (Notas)** | ✅ Completo | Matriz estudiante×criterios, escala AD/A/B/C, publicación/reversión, exportación Excel, procesamiento por servicios en cadena |
| 9 | **Evaluación** | ✅ Completo | Promedio por criterio → competencia → materia, detección de recuperación, estado general |
| 10 | **Asistencia** | ✅ Completo | Marcación diaria individual/masiva, tipos personalizables con colores, bloqueo por rango de fechas, reportes, calendario mensual |
| 11 | **Conducta** | ✅ Completo | Items de conducta configurables, asignación por bimestre, notas individuales |
| 12 | **Trámites Documentarios** | ✅ Completo | Solicitud con código único, tipos de trámite configurables, flujo de aprobación, registro de pagos con comprobantes |
| 13 | **Métodos de Pago** | ✅ Completo | Cuentas bancarias, billeteras digitales, efectivo; configurables por el admin |
| 14 | **Libreta (Report Card)** | ✅ Completo | Vista previa HTML + generación PDF con DomPDF |
| 15 | **Reportes Internos** | ✅ Completo | Sistema de mensajería/reportes entre usuarios |
| 16 | **Dashboard Directivo** | ✅ Completo | Analíticas con Chart.js (promedios, asistencia, conducta por grado) |
| 17 | **Dashboard Docente** | ✅ Completo | Vista de asignaciones, progreso de notas, estudiantes por curso |
| 18 | **Dashboard Apoderado** | ✅ Completo | Seguimiento de estudiantes a cargo |
| 19 | **Dashboard Estudiante** | ✅ Completo | Notas, asistencia y conducta personal |

### Convenciones Técnicas

- **Nombrado:** CamelCase para clases, snake_case para tablas/columnas, archivos Blade en kebab-case
- **Estados de usuario:** 1=activo, 2=lector, 0=inactivo
- **Estados de matrícula:** activo, retirado, trasladado
- **Tipo de bimestre:** 'A'=académico, 'R'=recuperación
- **Escala cualitativa:** AD (logro destacado), A (logro esperado), B (en proceso), C (en inicio)
- **Nota mínima de aprobación:** 1.5

---

> **Nota para otra IA:** Este informe es un snapshot estático. Para obtener el estado actual exacto, ejecuta `php artisan route:list`, revisa las migraciones con `php artisan migrate:status`, y explora el directorio `app/` para detectar cambios desde la fecha de este informe.
