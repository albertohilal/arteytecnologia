# Plan de Despliegue Controlado a Producción — arteytecnologia.com.ar

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
