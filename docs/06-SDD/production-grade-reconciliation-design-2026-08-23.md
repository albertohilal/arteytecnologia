# Production Grade Reconciliation Design — 2026-08-23 (RECTIFIED v2)

```text
FECHA      = 2026-08-23
PROYECTO   = arteytecnologia.com.ar (Moodle)
FASE SDD   = SPECIFICATION + DESIGN RECTIFICATION (post revisión técnica externa)
AUTORIZACIÓN = DOCUMENTATION_ONLY
IMPLEMENTACIÓN = NOT_AUTHORIZED
REVISION_EXTERNA = 10 DEFECTOS BLOQUEANTES (R1–R10) — RECTIFICADOS EN ESTA VERSIÓN
```

---

## 1. Objetivo

Diseñar la **reconciliación segura de calificaciones de producción** (reparación de la fuente
canónica) necesaria antes de desplegar el `reporteTPporCurso.php`.

El despliegue queda **BLOQUEADO** hasta cerrar esta Specification + Design rectificada y
verificar la versión real de Moodle producción.

---

## 2. Evidencia de DISCOVERY (corregida — R1)

```text
PRODUCTION_GRADING_MODEL_CURRENT = LEGACY_GRADE_GRADES_ONLY

ACTIVE_COURSES =
  15 | MedAud-26   | Medios Aud-2026
  19 | ComplPint26 | T.Comp Pint 2026
  20 | LVI-2026    | Lenguaje Visual I-2026

HISTORICAL_COURSES = OUT_OF_SCOPE
```

Sobre `forum_grades` (R1 — NO afirmar "vacío/cero filas"):

```text
FORUM_GRADES_NON_NULL_GRADE_COUNT = 0
FORUM_GRADES_TOTAL_ROWS = >0 (existen filas placeholder con grade = NULL)
```

Las calificaciones vigentes están en `grade_grades.finalgrade`:

```text
grade_grades.finalgrade NON_NULL = 564 (DISCOVERY, ver R2)
```

---

## 3. Problema

El backend verificado en LOCAL usa como fuente canónica Moodle:

```text
mod_forum\grades\forum_gradeitem → forum_grades → Gradebook
```

En producción las notas vigentes residen SOLO en `grade_grades.finalgrade` (con filas
`forum_grades` placeholder NULL). La API oficial, por diseño, mantiene `forum_grades` ↔
`grade_grades` sincronizados; por tanto hay que **reparar la fuente canónica** pasando las
notas por la API oficial, sin cambiar ningún valor académico.

---

## 4. Baseline (DISCOVERY, no constante de migración — R2)

```text
DISCOVERY_GRADE_COUNT      = 564
DISCOVERY_DISCUSSION_COUNT = 673
DISCOVERY_POST_COUNT       = 812
```

Distribución DISCOVERY (NO hardcodear en implementación):

```text
course 15: 101 · course 19: 59 · course 20: 404
```

```text
IMPLEMENTATION_SOURCE_SCOPE = FRESH_AUTHORIZED_PRE_RECONCILIATION_DATASET
FRESH_GRADE_COUNT_PRE = N        (capturado tras mantenimiento + backup + baseline fresh)
GRADE_COUNT_POST      = FRESH_GRADE_COUNT_PRE
```

El baseline definitivo se captura DESPUÉS de: `MAINTENANCE MODE → FRESH DB BACKUP → FRESH BASELINE`.

---

## 5. Dos alcances conceptualmente distintos

### A. GLOBAL GRADE RECONCILIATION

```text
courses = 15, 19, 20
dataset = FRESH_AUTHORIZED_PRE_RECONCILIATION_DATASET (dinámico, no 564)
```

Objetivo:

```text
grade_grades.finalgrade (fuente legacy)
→ API oficial Moodle forum grading
→ forum_grades.grade (fuente canónica reparada)
→ grade_grades.finalgrade (sincronizado, valor idéntico)
```

### B. CANONICAL TP RENAME

```text
CANONICAL_RENAME_SCOPE = course 15 only
courseid = 15 SOLO · fid 157..185
COURSE_19_CANONICAL_RENAME = NO
COURSE_20_CANONICAL_RENAME = NO
```

---

## 6. Reconciliación — Specification (rectificada — R2)

```text
PRODUCTION_GRADE_RECONCILIATION_SPECIFICATION = COMPLETED (RECTIFIED)

SOURCE_GRADE  = grade_grades.finalgrade
SOURCE_SCOPE  = FRESH_AUTHORIZED_PRE_RECONCILIATION_DATASET (dinámico, NO "exactly 564")

TARGET_API =
  mod_forum\grades\forum_gradeitem::store_grade_from_formdata()
  (o API oficial equivalente confirmada e INSPECCIONADA para la versión REAL de producción)

PRODUCTION_VERSION_VERIFIED = YES
PRODUCTION_LOCAL_VERSION_PARITY = YES
PRODUCTION_MOODLE_VERSION = VERIFIED (branch 39 · 3.9.1+ Build 20200814 · version 2020061501.07)
PRODUCTION_API_IMPLEMENTATION_MUST_BE_INSPECTED = YES (PRE_DEPLOY_CORE_HASH_PARITY_REQUIRED = YES)
  (comparar el código REAL de producción de: component_gradeitem::store_grade_from_formdata(),
   forum_gradeitem::store_grade(), forum_update_grades(), forum_grade_item_update())
```

---

## 7. Invariantes (rectificados — R2/R7)

```text
FRESH_GRADE_COUNT_PRE = N
GRADE_COUNT_POST      = FRESH_GRADE_COUNT_PRE
VALUE_IDENTITY_REQUIRED = YES
```

Para cada tupla `(courseid, forumid, userid)`:

```text
SOURCE_FINALGRADE_PRE
  = forum_grades.grade POST
  = grade_grades.finalgrade POST
```

Invariantes de `forum_grades` (R7):

```text
PRE_EXISTING_NULL_ROWS_PRESERVED_WHEN_OUT_OF_SCOPE = YES
UNEXPECTED_FORUM_GRADE_ROWS = 0
UNEXPECTED_NON_NULL_FORUM_GRADES = 0
```

El número esperado de filas nuevas se calcula desde el FRESH baseline (no desde 564).

Además:

```text
UNRELATED_GRADE_CHANGE = 0 · GRADE_DELETE = 0 · GRADE_NEW_UNEXPECTED = 0
OVERRIDDEN_CHANGE = 0 · LOCKED_CHANGE = 0 · EXCLUDED_CHANGE = 0 · HIDDEN_CHANGE = 0
DISCUSSION_CHANGE = 0 · POST_CHANGE = 0 · USER_CHANGE = 0
ENROLMENT_CHANGE = 0 · FORUM_CONTENT_CHANGE = 0
```

---

## 8. Prohibiciones

```text
LOCAL_DB_IMPORT = NO
LOCAL_FORUM_GRADES_TO_PRODUCTION = NO
LOCAL_GRADE_GRADES_TO_PRODUCTION = NO
LOCAL_GRADE_ITEMS_TO_PRODUCTION = NO
LOCAL_99_COURSE15_GRADES_TO_PRODUCTION = NO

DIRECT INSERT INTO forum_grades = NO
DIRECT UPDATE forum_grades = NO
DIRECT UPDATE grade_grades = NO
```

---

## 9. Precheck de implementación (antes de cualquier write)

1. verificar versión exacta Moodle de PRODUCCIÓN;
2. verificar presencia/compatibilidad + **inspeccionar** la API oficial en producción;
3. verificar course IDs 15,19,20;
4. capturar todas las notas non-null actuales;
5. comprobar unicidad `(courseid, forumid, userid)`;
6. comprobar rango válido de notas;
7. detectar overrides;
8. detectar locks;
9. detectar exclusions;
10. detectar hidden grades;
11. verificar existencia del forum y grade_item asociado;
12. verificar capacidad y usuario grader;
13. capturar entregas/posts;
14. generar checksum canónico PRE.

Además (R1/R9):

```text
FORUM_GRADES_TOTAL_ROWS_PRE
FORUM_GRADES_NULL_ROWS_PRE
FORUM_GRADES_NON_NULL_PRE

TARGETS_WITH_EXISTING_FORUM_GRADE_ROW
TARGETS_WITH_EXISTING_NULL_ROW
TARGETS_WITHOUT_FORUM_GRADE_ROW

ENROLMENT_CLASSIFICATION per target:
  CURRENTLY_ENROLLED | SUSPENDED_ENROLMENT | NOT_CURRENTLY_ENROLLED
```

La implementación debe distinguir **UPDATE de fila existente** vs **creación de fila nueva**
por la API (la API crea la fila component-grade si no existe).

Bloqueantes (STOP_BEFORE_FIRST_WRITE):

```text
MISSING_FORUM · MISSING_GRADE_ITEM · AMBIGUOUS_GRADE_MAPPING
OVERRIDDEN_GRADE · LOCKED_GRADE · OUT_OF_RANGE_GRADE
DUPLICATE_TARGET · API_INCOMPATIBILITY
```

---

## 10. Modelo de ejecución (rectificado — R4/R8)

```text
EXECUTION_ORDER = PER_FORUM
SAFE_WRITE_UNIT = PER_GRADE  (ONE (courseid, forumid, userid) GRADE)
STOP_ON_FIRST_FAILURE = YES
```

Decisión de transacción (R8 — conclusión técnica):

```text
TRANSACTION_BOUNDARY = NONE_EXPLICIT (no envolver la API de una nota en una transacción SQL outer)
```

Justificación: `store_grade_from_formdata()` → `store_grade()` → `UPDATE forum_grades` +
`forum_update_grades()` → `forum_grade_item_update()` → `grade_update()` produce side effects
(caché, eventos, grade history) que quedan **fuera** de una transacción SQL local. Una
transacción outer no aporta atomicidad real y puede entrar en conflicto con la gestión interna
de Moodle. Se prefiere:

```text
API WRITE
+
IMMEDIATE DOUBLE READ-BACK (forum_grades + grade_grades)
+
STOP-ON-FIRST-FAILURE
```

No se afirma atomicidad que Moodle no garantiza.

---

## 11. Flujo por safe unit (R4/R6)

```text
READ FRESH SOURCE (grade_grades.finalgrade)
→ validate unchanged since baseline
→ validate target student/forum/context
→ API WRITE (store_grade_from_formdata)
→ forum_grades READ-BACK
→ grade_grades READ-BACK
→ verify exact value identity (SOURCE_PRE = FORUM_POST = GRADEBOOK_POST)
→ ledger CONFIRMED
→ next grade
```

Si una nota falla:

```text
STOP IMMEDIATELY · DO NOT CONTINUE
```

No borrar automáticamente las reconciliaciones ya confirmadas.

Resultado por nota correcta:

```text
PRE:  grade_grades = X · forum_grades = NULL
POST: grade_grades = X · forum_grades = X
ACADEMIC_VALUE_CHANGED = NO
CANONICAL_SOURCE_REPAIRED = YES
```

### Ledger obligatorio por safe write (R6)

```text
courseid · forumid · grade_item_id · userid
source_finalgrade_pre
forum_grade_row_existed_pre · forum_grade_pre
gradebook_finalgrade_pre
forum_grade_post · gradebook_finalgrade_post
value_identity_pass · result · timestamp/order
```

El ledger no debe contener secretos.

---

## 12. Rollback (rectificado — R3/R5)

```text
LEGACY_EXACT_LOGICAL_ROLLBACK = NOT_DEMONSTRATED
```

Razón (R3): si se pasa NULL/no-grade por la API oficial de foro:

```text
forum_grades → NULL → forum_update_grades() → grade_update()
```

por diseño puede resincronizar también `grade_grades` a NULL. El estado PRE legacy
(`forum_grades=NULL`, `grade_grades=X`) es un estado DESINCRONIZADO que **no se ha demostrado**
que pueda reconstruirse con una única llamada a la API oficial. No autorizar implementación
que dependa de ese supuesto.

### Política de parcialidad (R4)

```text
SAFE_PARTIAL_RECONCILIATION = YES
  SOLAMENTE si cada safe unit completada demostró SOURCE_PRE = FORUM_POST = GRADEBOOK_POST
```

Ante fallo:

```text
STOP → PRESERVE_LEDGER → DIAGNOSE → NEW_AUTHORIZATION_REQUIRED_TO_RESUME
```

No llamar CLOSED_SUCCESS hasta completar y verificar el 100 % del fresh dataset.

### Full restore (R5)

```text
FULL_DB_RESTORE =
  ONLY_FOR_CONFIRMED_DATA_CORRUPTION
  AND EXPLICIT_USER_AUTHORIZATION
```

No restaurar una DB completa por una mera interrupción de migración si las safe units
ejecutadas preservaron exactamente los valores académicos.

---

## 13. Verificación post-reconciliation (rectificada — R7)

```text
SOURCE_PRE → forum_grades POST → grade_grades POST  (100 % del dataset fresh)
UNEXPECTED_GRADE_CHANGES = 0
UNEXPECTED_FORUM_GRADE_ROWS = 0
UNEXPECTED_NON_NULL_FORUM_GRADES = 0
DISCUSSIONS_UNCHANGED = YES
POSTS_UNCHANGED = YES
```

Solo tras:

```text
GRADE_RECONCILIATION_VERIFICATION = PASS
```

---

## 14. Usuarios históricos / enrolment (R9)

```text
EXISTING_PRODUCTION_GRADE = DATA_TO_PRESERVE
```

No descartar automáticamente una nota válida porque el estudiante no esté matriculado
actualmente. El precheck clasifica cada target, pero la nota existente se preserva.
Si la API/capability impide reconciliar una nota histórica:

```text
STOP_BEFORE_TARGET_WRITE
```

y requerir diseño explícito. No eliminar ni ignorar silenciosamente la nota.

---

## 15. Revisión adversarial (actualizada)

| # | Riesgo | Mitigación |
|---|--------|-----------|
| 1 | Sobrescribir una nota existente | VALUE_IDENTITY + doble read-back |
| 2 | Perder las notas | GRADE_DELETE=0; tocar SOLO dataset fresh; backup + baseline fresh |
| 3 | Side effects de `store_grade_from_formdata()` | mantenimiento + verificación por safe unit |
| 4 | Interacción con `grade_update_mod_grades()` | inspeccionar API producción (R10); read-back |
| 5 | `set_coursemodule_name()` resincroniza grade_item | verificar grade_item antes/después |
| 6 | Locks | lock lógico por (forumid,userid) |
| 7 | Regrading | comprobar `needsupdate`; no regrade explícito |
| 8 | `needsupdate` flag | bloquear si != 0 |
| 9 | Usuarios no estudiantes | target scope semántico (R3) |
| 10 | Estudiantes desmatriculados | DATA_TO_PRESERVE; clasificar; STOP si API bloquea (R9) |
| 11 | Filas placeholder NULL | distinguir UPDATE vs INSERT; preservar NULL out-of-scope (R7) |
| 12 | Notas de foros no-TP | reconciliar SOLO grade items de foro mapeados |
| 13 | Diferencia course 15 vs 19/20 | renombre SOLO course 15; reconciliación por foro |
| 14 | Creación de filas NULL nuevas | UNEXPECTED_NON_NULL_FORUM_GRADES=0; ledger |
| 15 | Transacciones parciales | PER_GRADE safe unit + stop-on-first-failure + ledger |
| 16 | Rollback incompleto | LEGACY_EXACT_LOGICAL_ROLLBACK=NOT_DEMONSTRATED; forward-safe resumable (R3/R4) |
| 17 | Discrepancia forum_grades/grade_grades | doble read-back + comparación |
| 18 | Versión Moodle producción | VERIFIED + API_INSPECTED (R10) |

---

## 16. Gates (rectificados)

```text
PRODUCTION_MOODLE_VERSION = VERIFIED (branch 39 · 3.9.1+ Build 20200814 · version 2020061501.07)
PRODUCTION_LOCAL_VERSION_PARITY = YES
PRE_DEPLOY_CORE_HASH_PARITY_REQUIRED = YES
  (comparar hashes LOCAL vs archivos PROD críticos inmediatamente antes de IMPLEMENTATION)

GATE_PRODUCTION_GRADE_RECONCILIATION = AWAITING_AUTHORIZATION
GATE_PRODUCTION_DEPLOYMENT = BLOCKED
GATE_PRODUCTION_CONTROLLED_GRADE_WRITE = AWAITING_SEPARATE_AUTHORIZATION
GATE_GIT_CHECKPOINT = AWAITING_AUTHORIZATION

NO AUTHORIZATION = NO EXECUTION
```

---

## 17. LOCAL FIRST — decisión de evidencia (2026-08-23)

```text
LOCAL_FIRST_EXISTING_EVIDENCE_SUFFICIENT = YES
MINIMAL_LEGACY_REHEARSAL_REQUIRED = NO
REDUNDANT_TEST_AVOIDED = YES
```

### A. Ya demostrado dinámicamente

```text
RECOVERY-UNIT-C: store_grade_from_formdata() escribió 99 notas (forum_grades + grade_grades)
  desde forum_grades NULL/absent y grade_grades vacío. CLOSED_SUCCESS. 99/99 match, mismatch=0.

UNIT-5A / UNIT-5B: flujo 9→8→9 sincronizó forum_grades ↔ grade_grades ↔ Gradebook ↔ reporte,
  baseline restaurado, checksum PRE=POST, 0 writes inesperados. PASS.
```

### B. Inferido del código (inspección core 3.9.1+)

```text
store_grade_from_formdata() → get_grade_for_user() (reusa placeholder NULL o crea fila)
  → asigna grade → store_grade().
store_grade() → check_grade_validity() → update_record(forum_grades, X) → forum_update_grades(userid).
forum_update_grades() → lee forum_grades (rawgrade=X) → forum_grade_item_update() → grade_update(itemnumber=1).
grade_update() recomputa finalgrade desde rawgrade=X y escribe grade_grades.finalgrade=X (idempotente sobre el valor).
```

### C. Diferencia PRODUCCIÓN vs RECOVERY-C

```text
PRODUCCIÓN: grade_grades ya tiene X; forum_grades NULL/placeholder.
RECOVERY-C: grade_grades vacío; forum_grades NULL.

Única diferencia de rama = INSERT vs UPDATE de grade_grades (mismo valor X), y
get_grade_for_user() reutiliza la fila placeholder existente. Con overridden=locked=excluded=hidden=0
en producción, NO se dispara ninguna rama de override/lock/history/regrade distinta.
```

### Conclusión

```text
No existe una rama de código relevante distinta por el hecho de que grade_grades ya contenga X.
La operación requerida (X/NULL → X/X) es la MISMA ruta ya demostrada por RECOVERY-C + UNIT-5A/5B.
Un ensayo LOCAL adicional de estado legacy sería REDUNDANTE.
```

Lo NO demostrado (R3: `LEGACY_EXACT_LOGICAL_ROLLBACK = NOT_DEMONSTRATED`) ya queda resuelto por el
modelo forward-safe (R4/R5: stop-on-failure + safe partial reconciliation + full restore solo por
corrupción), no por un ensayo adicional.
