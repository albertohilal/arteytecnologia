# Plan de Despliegue Controlado a Producción — arteytecnologia.com.ar

> **RECTIFICADO 2026-08-24 (post-GATE_A).** Este plan fue rectificado tras el cierre
> formal de GATE_A (`GATE_PRODUCTION_GRADE_RECONCILIATION = CLOSED_SUCCESS`, commit
> `9e0c7ab`). Las secciones 1–17 originales (2026-08-23) se conservan como evidencia
> histórica (HISTORICAL_VALID); sus campos de estado (gates, HEAD, secuencia) quedan
> **SUPERSEDED** por las secciones R-B1..R-B14 al final de este documento.

```text
FECHA               = 2026-08-23
PROYECTO            = arteytecnologia.com.ar (Moodle)
FASE SDD            = DESIGN — PRE-DEPLOYMENT PLAN DOCUMENTATION
AUTORIZACIÓN        = DOCUMENTATION_ONLY
IMPLEMENTATION_PRODUCCIÓN = NOT_AUTHORIZED
GATE_PRODUCTION_DEPLOYMENT = BLOCKED
```

---

## 1. Objetivo

Definir el procedimiento formal, controlado y por capas para trasladar a **PRODUCCIÓN**,
de forma segura y verificable, los cambios ya cerrados y verificados en LOCAL:

- el archivo funcional `moodle/reportes/reporteTPporCurso.php` (UNIT-5A + UNIT-5B, R1–R5);
- los renombres canónicos de las actividades/foros TP (nombres `TP-CEE-NN - descripción`).

Este documento **no autoriza** ningún despliegue ni escritura en producción.
Es exclusivamente el plan que habilita una futura autorización explícita.

---

## 2. Alcance

**DENTRO del alcance del despliegue (cuando se autorice):**

- renombres de actividades/foros TP del curso 15 vía API oficial Moodle;
- despliegue del archivo `moodle/reportes/reporteTPporCurso.php` verificado;
- verificación funcional READ-ONLY post-despliegue.

**FUERA del alcance inicial:**

- escritura/borrado/restauración de calificaciones (requiere autorización separada);
- cambios masivos de `grade_forum` / `grade_items`;
- cambios de entregas, usuarios, matriculaciones;
- importación de DB LOCAL a producción (PROHIBIDO);
- traslado de moodledata LOCAL;
- cualquier cambio de Moodle core, Docker o infraestructura.

---

## 3. Baseline LOCAL

Estado Git revalidado READ-ONLY (2026-08-23):

```text
BRANCH           = feature/copia-local-moodle
LOCAL_HEAD       = 0e347127111a8c96f44ea8a7c07ce345dd394265
REMOTE           = github-viejo
REMOTE_BRANCH    = feature/copia-local-moodle
REMOTE_HEAD      = 0e347127111a8c96f44ea8a7c07ce345dd394265
LOCAL_HEAD_EQUALS_REMOTE_HEAD = YES
TRACKED_WORKTREE = CLEAN
UNTRACKED        = AUXILIAR/ (no versionar)
```

Commit verificable:

```text
0e34712 feat: add verified async TP grade saving
```

El cierre de UNIT-5A y UNIT-5B está registrado en `docs/09-HANDOFF/CURRENT-STATE.md`
(`UNIT_5B_CLOSURE = CLOSED_SUCCESS`).

---

## 4. Cambios que SÍ se trasladan

El archivo `moodle/reportes/reporteTPporCurso.php` contiene, entre otros, los cambios
ya verificados end-to-end en LOCAL:

1. parser canónico `TP-CEE-NN`;
2. `VALID_GRADED_TP` (excluye `type=news`, identificadores no canónicos y CEE fuera de período);
3. lectura correcta de `forum_grades` (fuente canónica `forum_grades.grade`, sin colisión de claves DML);
4. enlaces `"Ver"` hacia publicaciones reales del foro (`/mod/forum/discuss.php?d=…#p…`);
5. protección del target de guardado a **estudiantes calificables** (rechaza teacher/editingteacher/manager/siteadmin/no-matriculado);
6. backend seguro de guardado mediante API oficial Moodle (`mod_forum\grades\forum_gradeitem::store_grade_from_formdata()`);
7. optimistic concurrency (`expected_previous_grade`);
8. lock lógico por `forumid/userid`;
9. transacción + in-transaction read-back + post-commit read-back;
10. rollback lógico verificado (doble read-back, `CRITICAL_ROLLBACK_FAILURE` distinguible);
11. UNIT-5B guardado asíncrono sin full-page reload (progressive enhancement);
12. RECTIFICATION-R1: `form.action` shadoweado por `<input name="action">` corregido con `form.getAttribute('action')`;
13. RECTIFICATION-R5: liberación idempotente del lock antes de toda respuesta terminal.

No se amplía el alcance funcional.

---

## 5. Cambios que NO se trasladan

```text
LOCAL DB → PRODUCTION = PROHIBIDO
```

No se trasladan desde LOCAL:

- las 99 calificaciones restauradas (fue una **reparación exclusiva del entorno LOCAL**);
- `forum_grades`;
- `grade_grades`;
- `grade_items`;
- dumps completos de LOCAL;
- `moodledata` LOCAL;
- cambios masivos de `grade_forum`.

---

## 6. Fuente de verdad de datos

Principio crítico:

```text
PRODUCTION = SOURCE_OF_TRUTH for:
  usuarios
  matriculaciones
  entregas
  forum_posts
  forum_grades
  grade_grades
  calificaciones nuevas
```

La DB LOCAL es **anterior** a parte de los datos actualmente presentes en producción.
Ningún dato académico de producción debe originarse ni reemplazarse desde LOCAL.

---

## 7. Mapping canónico

Regla de rectificación exigida:

```text
TP-CEE-NN - descripción textual
```

Identificadores y nombres **actuales en la DB LOCAL** (evidencia READ-ONLY 2026-08-23,
para ser re-obtenidos y comparados contra PRODUCCIÓN antes de desplegar):

```text
fid 157  type=news    grade_forum=0   "Programa de la asignatura"            (NO TP)
fid 158  type=general grade_forum=10  "TP-001-01 - Artistas multimedia"
fid 159  type=general grade_forum=10  "TP-001-02 - capturas del encuentro"
fid 160  type=general grade_forum=10  "TP-002-01 - Instante decisivo"
fid 161  type=general grade_forum=10  "TP-002-02 - Espacio y Tiempo en la Imagen Visual y Audiovisual"
fid 162  type=general grade_forum=10  "TP-002-03 - Secuencia breve"
fid 163  type=general grade_forum=10  "TP-003-01 - Creación de un video educativo"
fid 164  type=general grade_forum=10  "TP-006-01 - Los siete planos del encuadre"
fid 165  type=general grade_forum=10  "TP-006-02 - Los ángulos de toma, elaboración y clasificación"
fid 166  type=general grade_forum=10  "TP-007-01 - Mi primer animacion"
fid 167  type=general grade_forum=0   "TP-208-01 - Editor Web de P5js"
fid 168  type=general grade_forum=0   "TP-208-02 - Ejemplos oficiales de p5.js"
fid 169  type=general grade_forum=0   "TP-208-03 - Sketch en el Editor Web de p5.js"
fid 170  type=general grade_forum=0   "TP-209-01 - Los primeros sketch"
fid 171  type=general grade_forum=0   "TP-211-01 - Explorando Figuras Primitivas 2D en p5.js"
fid 172  type=general grade_forum=0   "TP-212-01 - Parámetros y Argumentos en p5.js"
fid 173  type=general grade_forum=0   "TP-213-01 - Coordenadas cartesianas"
fid 174  type=general grade_forum=0   "TP-214-01 - Crear un patrón visual"
fid 175  type=general grade_forum=0   "TP-215-01 - Dibujar en el Editor de P5JS"
fid 176  type=general grade_forum=0   "TP-215-02 - ejercicio"
fid 177  type=general grade_forum=0   "TP-216-01 - El Color"
fid 178  type=general grade_forum=0   "TP-217-01 - Practica de Consola y comentarios"
fid 179  type=general grade_forum=0   "TP-218-01 - Practica de variables"
fid 180  type=general grade_forum=0   "TP-219-01 - Ejercicios Variables Personalizadas"
fid 181  type=general grade_forum=0   "TP-220-01 - Cada vez que hagas clic con el mouse obtener"
fid 182  type=general grade_forum=0   "TP-221-01 - Ejercicio Funcion map()"
fid 183  type=general grade_forum=0   "TP-222-01 - Ejercicio createGraphics()"
fid 184  type=general grade_forum=0   "TP-223-01 - Subir imágenes al Editor Web"
fid 185  type=general grade_forum=0   "TP-224-01 - Ejercicio condicionales y booleanos"
```

Observaciones:

- fid 158–166 son los 9 foros calificables (`grade_forum=10`) que sostienen las 99 calificaciones;
- fid 167–185 (`grade_forum=0`) pertenecen al período 2 (`TP-2xx-…`), aún sin calificación de foro;
- fid 157 es `type=news` y **no** es un TP.

**Exigencia PRE-producción:** obtener desde la DB LOCAL viva (y comparar contra PRODUCCIÓN)
el **mapping exacto completo de nombres finales** (identificador + descripción textual).
No reconstruir descripciones a mano si no existe evidencia en la DB viva.

---

## 8. Backups

### 8.1 Backup preexistente (candidato)

```text
PATH = AUXILIAR/BACKUPS/2026-08-23 15-30-iunaorg_arteytecnologia.sql
SIZE = 38861234 bytes  (~37.1 MiB)
CLASIFICACIÓN = PRE_EXISTING_PRODUCTION_BACKUP_CANDIDATE
```

Antes de considerarlo autoritativo:

- verificar SHA256;
- identificar encabezado/formato/origen;
- preservar sin modificar;
- **NO importar**.

Dado que puede haber nuevas entregas/calificaciones posteriores a las 15:30, este backup
**no** sustituye el backup definitivo PRE-deployment.

### 8.2 Backup definitivo (obligatorio)

```text
FRESH_PRODUCTION_BACKUP_REQUIRED = YES
MOMENTO = DESPUÉS de activar MODO MANTENIMIENTO
```

Se tomará un backup NUEVO y definitivo de producción con el sitio en mantenimiento.

---

## 9. Precheck de producción (READ-ONLY)

Antes de activar mantenimiento, ejecutar **solo DISCOVERY READ-ONLY** en producción:

- versión real de Moodle;
- `courseid=15` (shortname/fullname);
- forum ids / cmids;
- nombres actuales;
- `type`;
- `grade_forum`;
- `grade_items`;
- conteos de `forum_grades`;
- conteos de `grade_grades`;
- cantidad de entregas/posts;
- existencia de nuevas calificaciones/entregas;
- mapping LOCAL ↔ PRODUCCIÓN.

**No asumir que los IDs locales y los de producción coinciden** hasta comprobarlo.

---

## 10. Modo mantenimiento

```text
MAINTENANCE_MODE = REQUIRED
```

Motivo: producción tiene actividad académica viva. Durante los cambios no deben producirse
nuevas entregas, nuevas calificaciones ni cambios concurrentes que introduzcan diferencias
entre baseline y estado posterior.

El backup autoritativo PRE-DEPLOYMENT se toma **después** de activar mantenimiento.

---

## 11. Procedimiento de implementación

Orden inamovible:

```text
 1. DISCOVERY / DESIGN / LOCAL FIRST = COMPLETED
 2. USER REVIEW
 3. AWAIT GATE_A AUTHORIZATION
 4. [si autorizado] PRE-IMPLEMENTATION SAFETY PRECHECK
 5. PRE_DEPLOY_CORE_HASH_PARITY
 6. ENABLE MAINTENANCE MODE
 7. FRESH DB BACKUP
 8. FRESH BASELINE + dynamic dataset
 9. REVALIDATE before first write
10. RECONCILE grades (grade_grades.finalgrade → official forum API → forum_grades + grade_grades)
11. VERIFY reconciliation 100%

STOP CHECKPOINT (si PASS):
  GATE_A = CONSUMED/CLOSED_SUCCESS
  GATE_B = AWAITING_AUTHORIZATION
  NO continuar automáticamente.

12. AWAIT GATE_B AUTHORIZATION

Sólo si posteriormente autorizado GATE_B:
13. canonical renames course 15
14. verify rename/data invariants
15. backup current production PHP
16. deploy verified PHP
17. verify deployed SHA256
18. functional verification courses 15/19/20
19. optional controlled write only if GATE_C separately authorized
20. exit maintenance only after required verification PASS
21. post-deployment verification
22. docs / Git checkpoint gates
```

### Invariante de orden

```text
CANONICAL_RENAMES BEFORE NEW_REPORT_CODE
```

El PHP nuevo usa el parser `TP-CEE-NN`; no debe existir una ventana en la que el código
nuevo esté activo mientras las actividades conservan nombres legacy.

### Método de renombre

```text
OFFICIAL_MOODLE_RENAME_API = set_coursemodule_name($cmid, $newname)
```

- **No** SQL directo para renombres (salvo que futura evidencia técnica obligue a
  reconsiderarlo mediante nueva Specification/Design).
- Advertencia: `set_coursemodule_name()` puede resincronizar el `grade_item`.
  Por ello, antes y después de cada conjunto de renombres verificar:
  `forum.name`, `grade_item.itemname`, `forum_grades`, `grade_grades`,
  valores numéricos, conteos, entregas/posts.

### Ruta física del archivo en producción

```text
LOCAL_SOURCE_FILE =
  /home/beto/Proyectos/arteytecnologia.com.ar/moodle/reportes/reporteTPporCurso.php

PRODUCTION_PUBLIC_ENDPOINT =
  https://arteytecnologia.com.ar/reportes/reporteTPporCurso.php

PRODUCTION_MOODLE_ROOT =
  /home/iunaorg/public_html/arteytecnologia.com.ar

PRODUCTION_PHYSICAL_PATH =
  /home/iunaorg/public_html/arteytecnologia.com.ar/reportes/reporteTPporCurso.php

PRODUCTION_PHYSICAL_PATH_RESOLVED = YES  (verificada READ-ONLY desde cPanel)
```

Nota histórica: originalmente `PRODUCTION_PHYSICAL_PATH = TO_BE_RESOLVED_READ_ONLY_DURING_PRODUCTION_PRECHECK`;
ahora RESUELTA (evidencia cPanel). Antes del backup/reemplazo del PHP se re-verificará el
archivo físico que sirve el endpoint.

---

## 12. Invariantes de datos

Cambio de DB esperado permitido (separado por gate):

```text
GATE_A_EXPECTED_DATABASE_CHANGE =
  únicamente canonicalization de las EXISTING production grades mediante API oficial:
    forum_grades.grade NULL/absent → existing finalgrade X
    grade_grades.finalgrade X → X
  academic value change = 0

GATE_B_EXPECTED_DATABASE_CHANGE =
  canonical activity/forum names del course 15
  y side effects Moodle esperados/verificados del rename API

Nunca presentar ambos como una misma autorización.
```

No autorizado por defecto (ambos gates):

```text
GRADE_WRITE
GRADE_DELETE
GRADE_RESTORE
GRADE_ITEM_MASS_CREATE
GRADE_FORUM_MASS_CHANGE
SUBMISSION_CHANGE
USER_CHANGE
ENROLMENT_CHANGE
```

---

## 13. Verificación (antes de salir de mantenimiento)

Sin modificar calificaciones:

```text
LOGIN → COURSE 15 → reporteTPporCurso.php
  → render correcto
  → TP correctos
  → títulos completos
  → calificaciones existentes visibles
  → "Ver" abre posts correctos
  → "Sin entrega" correcto
  → Gradebook consistente
  → no warnings PHP
  → no errores de lock
  → no HTML contaminando JSON
```

### Prueba de escritura (fuera de la autorización inicial)

```text
GATE_PRODUCTION_CONTROLLED_GRADE_WRITE = AWAITING_SEPARATE_AUTHORIZATION
```

Si se autoriza posteriormente: seleccionar UNA nota, capturar baseline, modificarla
temporalmente, verificar `forum_grades → grade_grades → Gradebook → reporte`, restaurar
inmediatamente el valor original mediante API oficial, y confirmar checksum/baseline
equivalente con cero cambios residuales.

### 13.1 Verificación POST-despliegue (producción)

`LOCAL_HEAD == REMOTE_HEAD` pertenece **exclusivamente** al checkpoint Git y **no** demuestra
que producción quedó correctamente desplegada.

La verificación de PRODUCCIÓN exige como mínimo:

```text
DEPLOYED_PHP_SHA256 =
  debe coincidir con LOCAL_VERIFIED_PHP_SHA256

DATABASE_INVARIANTS = PASS
FORUM_GRADES_UNEXPECTED_CHANGE = NO
GRADE_GRADES_UNEXPECTED_CHANGE = NO
SUBMISSIONS_UNEXPECTED_CHANGE = NO
FORUM_POSTS_UNEXPECTED_CHANGE = NO
UNAUTHORIZED_GRADE_WRITE = NO
FUNCTIONAL_VERIFICATION = PASS
MAINTENANCE_MODE = DISABLED únicamente después de PASS
```

El SHA256 del PHP LOCAL se captura **inmediatamente antes del despliegue** y se compara con
el archivo efectivamente desplegado en producción.

---

## 14. Rollback (por capas)

```text
FALLA DE PHP      → restore previous reporteTPporCurso.php
FALLA DE RENOMBRES → revertir selectivamente vía API oficial al nombre PRE capturado
FALLA DE DATOS    → STOP inmediato; NO restaurar DB completa sin diagnóstico y autorización expresa
```

El dump completo queda como **último** mecanismo de recuperación.

---

## 15. Gates de autorización (A / B / C separados)

```text
GATE_A = GATE_PRODUCTION_GRADE_RECONCILIATION
  ESTADO = AWAITING_AUTHORIZATION
  AUTORIZA SOLAMENTE (cuando el usuario lo haga expresamente):
    - activar maintenance mode necesario para esa operación
    - fresh production DB backup
    - fresh baseline
    - fresh dynamic reconciliation dataset
    - pre-write validations
    - reconciliation de EXISTING production grades
      (grade_grades.finalgrade → official forum API → forum_grades.grade + grade_grades.finalgrade)
    - ledger
    - double read-back
    - stop-on-first-failure
    - verification 100%
  EXCLUYE:
    - canonical renames
    - deploy PHP
    - controlled temporary grade test
    - Git commit/push

GATE_B = GATE_PRODUCTION_DEPLOYMENT
  ESTADO = BLOCKED (hasta GRADE_RECONCILIATION_VERIFICATION = PASS)
  REQUIERE NUEVA AUTORIZACIÓN EXPRESA para:
    - course 15 canonical renames
    - verify renames/data invariants
    - backup existing production PHP
    - deploy verified reporteTPporCurso.php
    - SHA256 verification
    - functional verification
    - exit maintenance según plan
  (NO asumir que la autorización de GATE_A autoriza GATE_B)

GATE_C = GATE_PRODUCTION_CONTROLLED_GRADE_WRITE
  ESTADO = AWAITING_SEPARATE_AUTHORIZATION
  (únicamente prueba temporal de UNA nota; no forma parte de GATE_A ni GATE_B)

GATE_GIT_CHECKPOINT = AWAITING_AUTHORIZATION
COMMIT = only if explicitly authorized
PUSH   = only if separately explicitly authorized
VERIFY LOCAL_HEAD = REMOTE_HEAD (post-push)

PRODUCTION_VERSION_VERIFIED = YES · PRODUCTION_LOCAL_VERSION_PARITY = YES
LOCAL_FIRST_EXISTING_EVIDENCE_SUFFICIENT = YES · MINIMAL_LEGACY_REHEARSAL_REQUIRED = NO
PRE_DEPLOY_CORE_HASH_PARITY_REQUIRED = YES · PRE_DEPLOY_CORE_HASH_PARITY_EXECUTED = NO

NO AUTHORIZATION = NO EXECUTION
NO AUTHORIZATION = NO COMMIT
NO AUTHORIZATION = NO PUSH
```

### Política de mantenimiento entre GATE_A y GATE_B

```text
GRADE_RECONCILIATION PASS
→ document/verify
→ si GATE_B no está ya autorizado:
     EXIT MAINTENANCE MODE
     STOP
→ deployment futuro requerirá NUEVO maintenance window + fresh precheck/backup apropiado.
```

No dejar el sitio indefinidamente en mantenimiento mientras se espera autorización humana para GATE_B.

---

## 16. Checklist de ejecución

```text
[completed]  discovery / design / local-first / version verification
[pending]    GATE_A (grade reconciliation authorization)
[blocked]    GATE_B (deployment)
[separate]   GATE_C (controlled grade write)

[ ] 1. USER REVIEW
[ ] 2. AWAIT GATE_A AUTHORIZATION
[ ] 3. PRE-IMPLEMENTATION SAFETY PRECHECK
[ ] 4. PRE_DEPLOY_CORE_HASH_PARITY
[ ] 5. ENABLE MAINTENANCE MODE
[ ] 6. FRESH DB BACKUP (registrar path/size/sha256)
[ ] 7. FRESH BASELINE + dynamic dataset
[ ] 8. REVALIDATE before first write
[ ] 9. RECONCILE grades (grade_grades.finalgrade → official forum API → forum_grades + grade_grades)
[ ]10. VERIFY reconciliation 100%
--- STOP CHECKPOINT (GATE_A=CLOSED_SUCCESS · GATE_B=AWAITING_AUTHORIZATION · NO continuar automáticamente) ---
[ ]11. AWAIT GATE_B AUTHORIZATION
[ ]12. canonical renames course 15
[ ]13. verify rename/data invariants
[ ]14. backup current production PHP
[ ]15. deploy verified PHP
[ ]16. verify deployed SHA256
[ ]17. functional verification courses 15/19/20
[ ]18. optional controlled write (GATE_C separate)
[ ]19. exit maintenance (after required verification PASS)
[ ]20. post-deployment verification
[ ]21. docs / Git checkpoint gates
```

---

## 17. Estado SDD

```text
DISCOVERY                  = COMPLETED (STEP_1 local map PASS; STEP_2 precheck → LEGACY GRADING FINDING)
SPECIFICATION              = PRODUCTION_GRADE_RECONCILIATION_SPECIFICATION = COMPLETED (RECTIFIED)
DESIGN                     = PRODUCTION_GRADE_RECONCILIATION_DESIGN = COMPLETED (RECTIFIED)
AUTHORIZATION              = DOCUMENTATION_ONLY
IMPLEMENTATION_PRODUCTION  = NOT_AUTHORIZED
GATE_A (GRADE RECONCILIATION) = AWAITING_AUTHORIZATION
GATE_B (DEPLOYMENT)           = BLOCKED
GATE_C (CONTROLLED WRITE)     = AWAITING_SEPARATE_AUTHORIZATION
PRODUCTION_VERSION_VERIFIED = YES · PRODUCTION_LOCAL_VERSION_PARITY = YES
LOCAL_FIRST_EXISTING_EVIDENCE_SUFFICIENT = YES · MINIMAL_LEGACY_REHEARSAL_REQUIRED = NO
PRE_DEPLOY_CORE_HASH_PARITY_REQUIRED = YES · EXECUTED = NO
PRODUCTION_PHYSICAL_PATH = /home/iunaorg/public_html/arteytecnologia.com.ar/reportes/reporteTPporCurso.php (RESOLVED)
```

Este documento es solo el PLAN. No habilita ejecución alguna en producción.

---

# RECTIFICACIÓN DEL PLAN — R-B1..R-B14 — 2026-08-24 (post-GATE_A)

> RECTIFICACIÓN DOCUMENTAL (DOCUMENTATION_ONLY). NO ejecuta GATE_B. Las secciones
> 1–17 originales (2026-08-23) se conservan como HISTORICAL_VALID en cuanto a decisiones
> de diseño; sus campos de estado quedan SUPERSEDED por lo siguiente. El estado operativo
> VIGENTE y el procedimiento de GATE_B están aquí.

## R-B1 — ESTADO OPERATIVO POST-GATE_A (CURRENT)

```text
GATE_A                        = CLOSED_SUCCESS
GATE_A_GIT_CHECKPOINT         = CLOSED_SUCCESS
GATE_B                        = AWAITING_AUTHORIZATION
GATE_C                        = AWAITING_SEPARATE_AUTHORIZATION
GATE_B_IMPLEMENTATION         = NOT_AUTHORIZED
GATE_B_AUTHORIZED             = NO

CURRENT_HEAD                  = 9e0c7abc9928182bd67ca448ca33a719e8e2fc86
BRANCH                        = feature/copia-local-moodle
REMOTE                        = github-viejo
LOCAL_HEAD_EQUALS_REMOTE_HEAD = YES

Clasificación de referencias stale del plan original:
  GATE_A = AWAITING_AUTHORIZATION             → SUPERSEDED (ahora CLOSED_SUCCESS)
  GATE_B = BLOCKED                            → SUPERSEDED (ahora AWAITING_AUTHORIZATION)
  IMPLEMENTATION_PRODUCCIÓN = NOT_AUTHORIZED  → SUPERSEDED (→ GATE_B_IMPLEMENTATION = NOT_AUTHORIZED)
  PRE_DEPLOY_CORE_HASH_PARITY_EXECUTED = NO   → SUPERSEDED (GATE_A ejecutó parity 4/4; GATE_B requiere parity nueva → R-B5)
  HEAD = 0e347...                             → SUPERSEDED (ahora 9e0c7ab)
```

## R-B2 — NUEVA MAINTENANCE WINDOW PARA GATE_B (CURRENT)

```text
GATE_A terminó y el sitio salió de maintenance (MAINTENANCE_MODE_DISABLED_FINAL = YES).
GATE_B requiere UNA NUEVA ventana propia. Secuencia inamovible:

GATE_B AUTHORIZATION
→ FRESH READ-ONLY PRECHECK
→ FRESH GATE_B CORE/API PARITY (R-B5)
→ ENABLE MAINTENANCE
→ FRESH PRE-GATE-B DB BACKUP (R-B3)
→ FRESH PRE-GATE-B BASELINE (R-B4)
→ REVALIDATE ALL RENAME TARGETS (R-B7)
→ CANONICAL RENAMES (R-B6)
→ VERIFY RENAMES + DATA INVARIANTS (R-B8)
→ PRE-DEPLOYMENT COMPATIBILITY CHECK courses 15/19/20 (R-B9)
→ BACKUP CURRENT PRODUCTION PHP
→ STAGE/LINT NEW PHP TEMPORALLY (R-B10)
→ CONTROLLED FILE REPLACEMENT
→ VERIFY DEPLOYED SHA256
→ FUNCTIONAL VERIFICATION (R-B11)
→ DISABLE MAINTENANCE (R-B12)
→ POST VERIFICATION
→ DOCUMENT
→ GIT CHECKPOINT (R-B13)

No dejar maintenance abierto esperando interacción humana innecesaria.
```

## R-B3 — FRESH PRE-GATE-B DB BACKUP (CURRENT)

```text
GATE_B_FRESH_DB_BACKUP_REQUIRED = YES
MOMENTO = después de ENABLE MAINTENANCE y antes del primer rename.
Estado a representar = POST-GATE-A / PRE-GATE-B.

Debe preservar: 564 forum_grades canónicas · todos los grade_grades · grade_items ·
grade_forum · users · enrolments · discussions · posts · submissions · nombres legacy PRE-rename.

Registrar: path · size · sha256 · timestamp · verification.
Ubicación preferida: AUXILIAR/BACKUPS/GATE-B/ (fuera de webroot, fuera de Git).

GATE_A_BACKUP_REUSED_AS_GATE_B_BASELINE = NO
(restaurar el backup PRE-GATE-A revertiría la reconciliación de las 564 notas.)
```

## R-B4 — BASELINE PRE-GATE-B (CURRENT)

```text
Bajo maintenance capturar baseline determinista PRE, como mínimo:
  COURSE_15_FORUM_MAP_PRE = fid · cmid · name · type · grade_forum
  GRADE_ITEMS_PRE         = id · iteminstance · itemnumber · itemname · grademin · grademax · needsupdate
  FORUM_GRADES_PRE        = count · non-null count · checksum académico
  GRADE_GRADES_PRE        = non-null count · checksum académico
  DISCUSSIONS_PRE · POSTS_PRE
  grade_forum checksum · grade_item definition checksum · canonical grade checksum

Verificar que las 564 notas reconciliadas continúan forum_grades = grade_grades
antes del primer rename.
```

## R-B5 — RENAME API HASH PARITY (CURRENT)

```text
GATE_B_CRITICAL_CORE_HASH_PARITY_REQUIRED = YES
GATE_B_CRITICAL_CORE_HASH_PARITY_EXECUTED = NO   (en esta ejecución documental)

Archivos core críticos para GATE_B (fresh SHA256 LOCAL↔PROD):
  1. course/lib.php     (set_coursemodule_name() → grade_update_mod_grades())
  2. mod/forum/lib.php  (forum_update_grades())
  3. lib/gradelib.php   (grade_update())

GATE_A ya verificó parity 4/4: component_gradeitem.php · forum_gradeitem.php ·
mod/forum/lib.php · lib/gradelib.php. GATE_B añade course/lib.php y puede revalidar
cualquier archivo core directamente involucrado.

Si un hash difiere en el futuro precheck:
  STOP_BEFORE_MAINTENANCE_OR_WRITE; o si maintenance ya activo: STOP_BEFORE_RENAME + safe exit.
```

## R-B6 — RENAME SCOPE (CURRENT)

```text
CANONICAL_RENAME_SCOPE = COURSE 15 ONLY
Targets = fid 158..185 + fid 157 (NON_TP rename según mapa acordado)

NO renombrar courses 19/20 por inferencia.
NO modificar grade_forum.
NO crear/eliminar grade_items.
NO direct SQL rename.

RENAME_API = set_coursemodule_name($cmid, $expected_name)

RENAME_NAME_SOURCE      = VERIFIED CANONICAL MAP (de LOCAL)
PRODUCTION_GRADE_FORUM  = PRESERVE EXACTLY
PRODUCTION_GRADE_ITEMS  = PRESERVE DEFINITIONS
(la config académica de producción difiere de LOCAL en grade_forum de período 2;
 el nombre canónico viene del mapa, la config académica NO se importa de LOCAL.)
```

## R-B7 — PREVALIDATION DE TODOS LOS RENAMES (CURRENT)

```text
ANTES del primer rename validar los 29 targets completos:
  fid · cmid · courseid · current name · expected name · forum type · grade_forum ·
  grade item mapping · collision

Exigir:
  COURSE_ID = 15
  FID/CMID mapping exacto
  CURRENT_NAME pertenece al mapa legacy esperado o ya coincide con canonical target
  TARGET_NAME_COLLISION_COUNT = 0
  UNEXPECTED_CURRENT_NAME_COUNT = 0
  MISSING_FORUM_COUNT = 0 · MISSING_CM_COUNT = 0

No continuar si existe drift ambiguo. Capturar PRE por target para rollback selectivo.
```

## R-B8 — RENAME VERIFICATION (CURRENT)

```text
set_coursemodule_name() → grade_update_mod_grades() → forum_update_grades():
cada rename puede resincronizar Gradebook. GATE_A ya reparó forum_grades.

Después de cada rename o grupo controlado verificar:
  forum.name · course_modules identity · grade_item.itemname · grade_forum ·
  forum_grades · grade_grades

Exigir:
  ACADEMIC_VALUE_CHANGE = 0
  FORUM_GRADES_MISMATCH = 0 · GRADE_GRADES_MISMATCH = 0
  GRADE_FORUM_CHANGED = 0
  GRADE_ITEM_DEFINITION_UNEXPECTED_CHANGE = 0
  DISCUSSIONS_CHANGE = 0 · POSTS_CHANGE = 0

STOP_ON_FIRST_RENAME_FAILURE = YES

ROLLBACK DE RENOMBRE = revertir exclusivamente el nombre mediante la misma API al
  PRE capturado, si el diseño lo considera seguro y el estado académico permanece verificado.
FULL DB RESTORE = último recurso + corrupción confirmada + NUEVA autorización expresa.
```

## R-B9 — COMPATIBILIDAD COURSES 19/20 (CURRENT)

```text
PRE-DEPLOYMENT COMPATIBILITY CHECK READ-ONLY obligatorio ANTES de desplegar PHP.

El nuevo reporte usa reporte_tp_is_valid_graded_tp() + parse_tp_identifier() (TP-CEE-NN).
El plan solo renombra course 15. NO asumir que 19/20 son compatibles.

Para cada course 15/19/20: obtener forums actuales y aplicar LOCALMENTE la lógica/parser
del NUEVO reporte contra esos nombres; calcular:
  TOTAL_FORUMS · VALID_TP · INVALID_IDENTIFIER · NEWS_EXCLUDED ·
  GRADEABLE_FORUMS · GRADEABLE_FORUMS_SURVIVING_NEW_FILTER

COURSE_15: debe quedar compatible después de los renames.
COURSES_19_20: NO renombrar automáticamente.

Si el nuevo código excluiría TP legítimos de 19/20:
  REPORT_DEPLOYMENT_COMPATIBILITY = FAIL
  → STOP BEFORE PHP DEPLOYMENT
  → GATE_B_PARTIAL = RENAMES_VERIFIED_BUT_PHP_DEPLOY_BLOCKED
  → documentar · exit maintenance safely · nueva Specification/Design requerida.
```

## R-B10 — PHP DEPLOYMENT SEGURO (CURRENT)

```text
LOCAL SOURCE  = /home/beto/Proyectos/arteytecnologia.com.ar/moodle/reportes/reporteTPporCurso.php
PRODUCTION    = /home/iunaorg/public_html/arteytecnologia.com.ar/reportes/reporteTPporCurso.php
PRODUCTION_PHP_BINARY = /opt/alt/php74/usr/bin/php   (NO usar PHP 8.2 del PATH)

Procedimiento obligatorio:
  1. LOCAL_VERIFIED_PHP_SHA256 fresh
  2. verificar path production real
  3. backup del PHP ACTUAL de producción (fuera del webroot / evidence dir no ejecutable)
  4. registrar PRE_DEPLOY_PHP_SHA256
  5. transferir el nuevo archivo a un TEMPORARY NON-LIVE PATH
  6. /opt/alt/php74/usr/bin/php -l <tempfile>  →  PHP_LINT_TEMP = PASS obligatorio
  7. revisar ownership/permissions esperados
  8. reemplazo controlado del archivo live solo después de lint PASS
  9. DEPLOYED_PHP_SHA256 == LOCAL_VERIFIED_PHP_SHA256
  10. lint también sobre live si es seguro
  11. NO modificar otros archivos PHP

Si cualquier control falla: restore PREVIOUS_PHP → verify previous SHA256 → STOP.
```

## R-B11 — FUNCTIONAL VERIFICATION (CURRENT)

```text
Con renames y PHP deployed verificar courses 15/19/20:
  LOGIN → reporte course → render → TP esperados → títulos completos →
  notas existentes visibles → enlaces Ver correctos → Sin entrega correcto →
  Gradebook consistente → no warnings PHP → no HTML contaminando JSON → no lock warnings.

GATE_C sigue separado. CONTROLLED_PRODUCTION_GRADE_WRITE = NOT_AUTHORIZED dentro de GATE_B.
Backend se verifica con STATIC + READ-ONLY + comportamiento ya demostrado LOCAL.
```

## R-B12 — MAINTENANCE EXIT (CURRENT)

```text
Si RENAMES_VERIFICATION = PASS AND PHP_DEPLOYMENT_VERIFICATION = PASS AND
FUNCTIONAL_VERIFICATION = PASS:
  → disable maintenance → verify disabled → post-deployment read-only verification →
    DOCUMENT_CURRENT_STATE → PREPARE_GIT_CHECKPOINT → STOP before commit.

Si GATE_B falla parcialmente: determinar estado seguro → rollback de capa autorizada si
corresponde → verificar → disable maintenance si seguro → document exact partial state → STOP.
No dejar site indefinidamente en maintenance.
```

## R-B13 — GIT CHECKPOINT (CURRENT)

```text
IMPLEMENT → VERIFY → DOCUMENT_CURRENT_STATE → PREPARE_GIT_CHECKPOINT →
USER AUTHORIZATION → COMMIT → separate PUSH AUTHORIZATION → PUSH →
LOCAL_HEAD=REMOTE_HEAD → CLOSE.
NO commit/push automático dentro de GATE_B.
```

## R-B14 — CHECKLIST VIGENTE (CURRENT)

```text
[completed] GATE_A reconciliation
[completed] GATE_A Git checkpoint
[pending authorization] GATE_B
[separate] GATE_C

GATE_B checklist:
[ ] GATE_B authorization
[ ] fresh read-only precheck
[ ] GATE_B critical core hash parity (course/lib.php · mod/forum/lib.php · lib/gradelib.php)
[ ] enable maintenance
[ ] fresh PRE-GATE-B DB backup
[ ] fresh PRE-GATE-B baseline
[ ] all-target rename prevalidation (29 targets)
[ ] canonical renames course 15
[ ] rename/data verification
[ ] courses 15/19/20 report compatibility precheck
[ ] backup current production PHP
[ ] temporary PHP transfer
[ ] PHP 7.4 lint
[ ] controlled live replacement
[ ] deployed SHA256 verification
[ ] functional verification
[ ] disable maintenance
[ ] post-deployment verification
[ ] document
[ ] prepare Git checkpoint
[ ] commit authorization
[ ] push authorization
[ ] LOCAL_HEAD=REMOTE_HEAD
[ ] close GATE_B

GATE_C no forma parte de esta checklist salvo referencia separada.
```

## R-B15 — COMPATIBILITY BLOCKER (R-B9 resultado) — 2026-08-24 (CURRENT)

```text
REPORT_DEPLOYMENT_COMPATIBILITY = FAIL
DEPLOYMENT_BLOCKER = COURSES_19_20_IDENTIFIER_MODEL_INCOMPATIBILITY

El nuevo reporte exige TP-CEE-NN globalmente, pero courses 19/20 usan identificadores
legacy (41 TP legítimos excluidos: 16 en course 19, 25 en course 20; ~21 con notas reales).

RECTIFICACIÓN (documentación):
  spec amendment → REPORTABLE_TP vs CANONICAL_PERIOD_TP (course-aware).
  design amendment → moodle-period-grading-design-amendment-2026-08-24.md.
  USER_DECISION_REQUIRED = YES (course 20 fid 277 "TP- 00 -Programa..." AMBIGUOUS).

GATE_B = BLOCKED_PENDING_LOCAL_COMPATIBILITY_IMPLEMENTATION_AND_VERIFICATION
GATE_B_IMPLEMENTATION_READY_FOR_AUTHORIZATION = NO

SUPERSEDE parcial de R-B14: el ítem "courses 15/19/20 report compatibility precheck"
YA se ejecutó → FAIL. Se requiere implementación LOCAL de la policy course-aware
(aún NO autorizada) + verificación ANTES de re-autorizar GATE_B.

NEXT_ACTION = USER REVIEW OF COMPATIBILITY SPECIFICATION/DESIGN
```

## R-B16 — USER DECISION CLOSED (fid277) — 2026-08-24 (CURRENT)

```text
D-COURSE20-FID277-GRADEABLE = CONFIRMED
FID277 = REPORTABLE_GRADED_ACTIVITY (reportable=YES, gradeable=YES, canonical=NO, period=NONE)
USER_DECISIONS_STILL_REQUIRED_COUNT = 0

COURSE_20_POLICY = LEGACY_COMPATIBLE (incluye fid277)
SAVEGRADE_POLICY = course-aware · SETUPGRADES_SCOPE = COURSE_15_ONLY

GATE_B = BLOCKED_PENDING_LOCAL_COMPATIBILITY_IMPLEMENTATION_AND_VERIFICATION
LOCAL_IMPLEMENTATION_REQUIRED = YES · AUTHORIZED = NO
NEXT_ACTION = USER REVIEW FOR LOCAL COMPATIBILITY IMPLEMENTATION AUTHORIZATION
```
