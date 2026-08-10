# Manual de Explicación — Sistema de Gestión Escolar

> Guía para presentar el sistema en orden lógico. Primero la idea central, luego los módulos
> según sus dependencias, y por último cada rol con su menú típico.

---

## 1. La idea central (la columna vertebral) — explicar primero, siempre

Todo el sistema funciona sobre **un solo calendario**: el **Período**, que es el **año escolar**.

- Un Período se divide en **Bimestres**: B1, B2, B3, B4 y BR (recuperación).
- **Regla de oro:** nada se guarda suelto. La asistencia, las notas, la conducta y los criterios
  de evaluación siempre quedan anclados a **período + bimestre + curso + estudiante**.

### Por qué Conducta NO se explica antes de Período

La conducta tiene **dos capas** que dependen del calendario:

1. **La lista de conductas** (Respeto, Responsabilidad, etc.) → es global, se crea una sola vez.
2. **La activación por bimestre** → qué conductas se evalúan en cada B1/B2/B3/B4
   (tabla `conducta_periodo_bimestres`). Recién ahí se puede **calificar** a cada estudiante
   (tabla `conducta_periodo_bimestre_notas`, que guarda período + bimestre + curso + estudiante).

> Frase lista para usar:
> *"Todo el sistema funciona sobre un calendario: el año escolar, que se divide en 4 bimestres.
> Cada nota, asistencia y conducta de un alumno queda registrada en un período, un bimestre y un
> curso. De ahí viene todo."*

---

## 2. Orden de explicación (por dependencias)

| Paso | Módulos | Rol principal | Por qué en este orden |
|------|---------|---------------|-----------------------|
| 0 | Mapa general (6 roles) | Todos | Da el panorama antes de entrar en detalle |
| 1 | **Períodos y Bimestres** | Admin / Director | Columna vertebral; todo depende de esto |
| 2 | Usuarios, Roles, Módulos | Admin | Define quién entra y qué ve cada uno |
| 3 | Grados, Materias (Competencias y Criterios) | Admin / Director | Catálogo básico; los criterios ya se anclan a bimestre |
| 4 | Matrícula | Admin / Director / Auxiliar | Quién está en qué grado este año |
| 5 | Planificación (Maya) | Admin / Director | Qué materia enseña qué docente en qué grado y año |
| 6 | Asistencia → Notas → Conducta | Docente / Auxiliar | Registro diario; la conducta va al final porque usa bimestres |
| 7 | Libreta y Reportes | Docente / Director / Apoderado | La salida: boleta del alumno y avisos |
| 8 | Métodos de Pago y Trámites | Admin | Lo administrativo: cobros y documentos |

### Detalle de cada paso

**Paso 0 — Mapa general (1 minuto)**
> "El sistema lo usan 6 roles: Admin, Director, Auxiliar, Docente, Estudiante y Apoderado.
> Cada rol ve un menú distinto, que el Admin configura."

**Paso 1 — Períodos y Bimestres (Admin/Director)**
- Crear el año escolar con fecha de inicio y fin.
- Crear sus 4 bimestres (+ recuperación) con sus fechas.
- Si esto queda claro, el resto se explica solo.

**Paso 2 — Usuarios, Roles y Módulos (Admin)**
- Cada persona tiene un **usuario** y un **rol**.
- Cada rol ve únicamente los **módulos asignados** (pantalla "Asignar Módulos al Rol").
- No hay menú fijo por rol en el código: el menú sale de la base de datos.

**Paso 3 — Grados y Materias (Admin/Director)**
- Grados con secciones y niveles.
- Materias con sus **Competencias** (habilidades amplias) y **Criterios** (lo que se califica).
- Los criterios se asignan a un grado y a un **bimestre** concreto.

**Paso 4 — Matrícula**
- Une estudiante + período + grado.
- Solo los estudiantes con matrícula activa aparecen en asistencia, notas y libreta.

**Paso 5 — Planificación (Maya)**
- Registro central que une **Materia + Grado + Docente + Año**.
- Es el pegamento: notas, conducta, recuperaciones y reportes apuntan a este registro.

**Paso 6 — Registro diario (Docente/Auxiliar) — en este orden**
1. **Asistencia:** el auxiliar/docente marca puntual, tardanza o falta por fecha y grado. Se puede bloquear un mes para que no se modifique.
2. **Notas:** el docente califica a cada estudiante sobre los **criterios** de cada competencia, por bimestre.
3. **Conducta:** primero se activan las conductas a evaluar en ese bimestre y luego se califican.

**Paso 7 — Salida (Libreta y Reportes)**
- **Libreta:** la boleta del alumno. Junta notas + conducta + asistencia, en HTML o PDF.
- **Reportes:** avisos/notificaciones entre personal y apoderados.

**Paso 8 — Administrativo (Admin)**
- **Métodos de Pago:** referencia de cómo se puede pagar (efectivo, transferencia, depósito).
- **Trámites:** solicitudes de documentos. Cada trámite tiene **dos historiales**: estado del trámite
  (Pendiente → En proceso → Completado) y estado del pago (Pendiente → Aprobado/Rechazado).
  El pago se adjunta como **comprobante** y el admin lo revisa.

---

## 3. Módulos por rol (menú típico)

| Rol | Módulos | Frase que lo resume |
|-----|---------|---------------------|
| **Admin** | Todo + Usuarios, Roles, Módulos, Configuración del colegio, Períodos, Métodos de Pago | "El que configura todo" |
| **Director** | Períodos, Grados, Matrícula, Planificación, Notas, Conducta, Asistencia, Libreta, Trámites, Reportes | "El que ve el cuadro completo" |
| **Auxiliar** | Matrícula, Asistencia, Conducta, Bloqueo de asistencia | "El que registra el día a día" |
| **Docente** | Planificación (sus cursos), Criterios, Notas, Conducta, Asistencia, Libreta | "El que enseña y califica" |
| **Apoderado** | Dashboard, Libreta, Mis Trámites | "El padre que consulta a su hijo" |
| **Estudiante** | Dashboard, Libreta, Mis Trámites | "El alumno que ve sus notas" |

> **Importante:** estos nombres son los típicos. El menú real sale de la base de datos
> (`role_modules` + `modules`), así que **antes de presentar** entra como admin a
> **"Ver Módulos"** y anota los nombres exactos que aparecen por rol.

---

## 4. Respuestas a preguntas típicas

| Pregunta | Respuesta |
|----------|-----------|
| ¿Quién marca la asistencia? | El auxiliar o el docente, por fecha y grado. |
| ¿Puede el papá ver las notas? | Sí, en la **Libreta** desde su perfil de Apoderado. |
| ¿Cómo se paga un trámite? | Se elige un **método de pago**, se sube el comprobante y el admin lo aprueba. |
| ¿Qué pasa si un mes ya se cerró? | El admin puede **bloquear** la asistencia de ese mes para que no se edite. |
| ¿Dónde se define qué se evalúa? | En las **Competencias y Criterios** de cada materia, asignados por bimestre. |
| ¿Qué es la recuperación? | Un bimestre especial (BR) para nivelar competencias reprobadas. |

---

## 5. Guión de presentación (resumen en 3 minutos)

1. **Apertura (la clave):** "El sistema funciona sobre el calendario escolar: un año que se divide
   en 4 bimestres. Todo registro —notas, asistencia, conducta— queda anclado a período, bimestre y curso."
2. **Quiénes lo usan:** "Seis roles. El Admin configura, el Director supervisa, el Auxiliar registra
   la asistencia, el Docente enseña y califica, y el Apoderado y Estudiante consultan la libreta."
3. **El día a día:** "La matrícula define quién está en cada grado. El docente califica notas y conducta
   por bimestre. El auxiliar marca asistencia."
4. **Lo que ve el padre:** "En la Libreta, el apoderado ve la boleta completa de su hijo: notas, conducta
   y asistencia."
5. **Cierre:** "Y todo lo administrativo, como trámites de documentos y pagos, también se maneja desde el sistema."
