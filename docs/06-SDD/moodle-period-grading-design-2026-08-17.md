# SDD_MOODLE_PERIOD_GRADING — DESIGN

> Fecha: 2026-08-17 · Cambio: `moodle-period-grading` · Fase: DESIGN (rectificado)
> Destino: `docs/06-SDD/moodle-period-grading-design-2026-08-17.md`

---

```text
SDD_MOODLE_PERIOD_GRADING = IN_PROGRESS
PHASE                     = DESIGN_RECTIFICATION

SPECIFICATION_STATUS           = COMPLETED
SPECIFICATION_READY_FOR_DESIGN = YES
USER_DECISIONS_STILL_REQUIRED_COUNT = 0

DESIGN_STATUS               = COMPLETED  (tras DESIGN_RECTIFICATION_2026_08_17)
PERIOD_UI_RECTIFICATION     = COMPLETED  (2026-08-18, D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL)
TP_EVALUATION_UI_RECTIFICATION = COMPLETED  (2026-08-18, D-TP-EVALUATION-CRITERIA-PEDAGOGICAL-ONLY
                                            + D-TP-UI-NO-CRITERIA-CONTROLS)
DESIGN_READY_FOR_AUTHORIZATION = YES
DESIGN_INTERNAL_CONSISTENCY = CONSISTENT

IMPLEMENTATION = NOT_AUTHORIZED
```

---

## DESIGN_RECTIFICATION_2026_08_17

> Rectificación del DESIGN tras inspección READ-ONLY del código real local:
> `mod_forum\grades\forum_gradeitem`, `core_grades\component_gradeitem`,
> `forum_update_grades()`, `forum_grade_item_update()`, `set_coursemodule_name()`.
> No modifica la SPECIFICATION. Las decisiones previas que se indican quedan
> **SUPERSEDED**.

### RECTIFICATION_1 — FORUM_GRADE_SOURCE_OF_TRUTH

**FACT verificado (código local):**

```text
- mod_forum\grades\forum_gradeitem::get_table_name() → 'forum_grades'.
  La fuente de verdad del módulo foro para la nota de foro completo es forum_grades
  (itemnumber = 1 = "whole forum grade"; itemnumber 0 = rating).
- forum_gradeitem::store_grade() (protected): check_grade_validity()
  → $DB->update_record('forum_grades', $grade)
  → forum_update_grades($forumrecord, $grade->userid).
- forum_gradeitem::store_grade_from_formdata($gradeduser, $grader, $formdata)
  (public): get_grade_for_user() → asigna $grade->grade desde $formdata->grade
  (si está seteado) → store_grade().
- forum_update_grades() (mod/forum/lib.php:808): lee forum_grades
  (SELECT g.grade FROM {forum_grades} g ...) y sincroniza el Gradebook vía
  forum_grade_item_update() → grade_update() (itemnumber 1).
```

**RIESGO detectado (confirmado):**

```text
DIRECT_GRADE_UPDATE_ONLY_RISK =
  El action savegrade actual llama grade_update() directamente y NO persiste
  forum_grades. El Gradebook puede contener una nota que NO existe en forum_grades,
  por lo que una posterior resincronización/regrade/rename del foro
  (forum_update_grades() / grade_update_mod_grades()) reconstruye el Gradebook
  desde forum_grades y puede perder o alterar la nota escrita directamente.
```

**RECTIFICACIÓN de DD-GRADEBOOK-WRITE-API (SUPERSEDED):**

```text
TP_GRADE_WRITE_API =
  mod_forum\grades\forum_gradeitem::load_from_context($modulecontext)
    → require_user_can_grade($gradeduser, $grader)
    → store_grade_from_formdata($gradeduser, $grader, (object)['grade' => $grade])

NO escribir directamente en forum_grades.
NO escribir directamente en grade_grades.
NO usar grade_update() como ÚNICO mecanismo de persistencia de la calificación
individual (evita la fuente de verdad forum_grades).
```

**Round-trip obligatorio:**

```text
FORMULARIO → validación → forum_gradeitem API → forum_grades
  → forum_update_grades() → grade_items / grade_grades
  → relectura forum_grades → relectura Gradebook (grade_get_grades)
```

Se verifica igualdad de la nota en ambas capas (`forum_grades.grade` y
`grade_grades.finalgrade`).

**READ vs WRITE (distinción obligatoria):**

```text
get_grade_for_user() tiene SIDE EFFECT: crea un registro forum_grades vacío si no
existe (create_empty_grade()). NO usarla como lectura READ-ONLY.

READ_EXISTING_GRADE (sin side effect):
  - $gradeitem->user_has_grade($gradeduser)      → bool (true = existe con grade != null)
  - grade_get_grades(...)                        → vista Gradebook (read-only)
  - $DB->get_record('forum_grades', ['forum'=>..., 'itemnumber'=>1, 'userid'=>...])
                                                 → lectura directa (read-only)

CREATE_OR_UPDATE_GRADE (solo al guardar, POST explícito):
  - $gradeitem->store_grade_from_formdata(...)   → crea/actualiza forum_grades + sincroniza
```

### RECTIFICATION_2 — OFFICIAL_MODULE_RENAME_API

**FACT verificado:** `set_coursemodule_name($cmid, $newname)` (`moodle/course/lib.php:939`):

```text
1. get_coursemodule_from_id('', $id, 0, false, MUST_EXIST) → $cm.
2. Limpia el nombre: clean_param($name, PARAM_TEXT) o PARAM_CLEANHTML según
   $CFG->formatstringstriptags.
3. Retorna false si el nombre no cambia o queda vacío; lanza excepción si >255.
4. $DB->update_record($cm->modname, $module) → actualiza mdl_forum.name (modname='forum').
5. Dispara \core\event\course_module_updated.
6. rebuild_course_cache($cm->course, true).
7. grade_update_mod_grades($grademodule) → sincroniza el grade item (llama
   forum_update_grades() → forum_grade_item_update() → grade_update()).
8. course_module_update_calendar_events($cm->modname, $grademodule, $cm).
```

**RECTIFICACIÓN de DD-RENAME-VIA-MOODLE-API (SUPERSEDED):**

```text
ACTIVITY_RENAME_API =
  set_coursemodule_name($cmid, $newname)   (API oficial)

  Aplicar a fid 157 (news, no TP) y fid 158..185 (TP canónicos),
  resolviendo previamente fid → cmid (get_coursemodule_from_instance('forum', $fid, $courseid)).
  Scope completo: CANONICAL_TP_RENAME_SCOPE = fid 158..185 · NON_TP_RENAME_SCOPE = fid 157.

  No ejecutar ahora. No usar $DB->update_record('forum') directo como mecanismo
  principal de renombre.
```

**Consecuencia clave (vínculo con RECTIFICATION_1):** `set_coursemodule_name()` dispara
`grade_update_mod_grades()` → `forum_update_grades()` que **reconstruye el Gradebook desde
`forum_grades`**. Por eso la fuente de verdad `forum_grades` debe estar correcta antes de
renombrar; un renombre sobre notas escritas solo con `grade_update()` las perdería.

**RENAME_GRADE_PRESERVATION_PLAN (PRE vs POST):**

```text
Antes y después de cada lote de renombres capturar y verificar:
  forum.id · forum.name · course_modules.id
  grade_items.id · grade_items.itemname
  grade_grades.userid · grade_grades.finalgrade
  forum_grades.userid · forum_grades.grade

Las calificaciones numéricas (grade_grades.finalgrade y forum_grades.grade) deben
permanecer idénticas PRE vs POST. Solo cambian forum.name y grade_items.itemname.
```

### RECTIFICATION_3 — ALL_IMPLEMENTATION_UNITS_REQUIRE_USER_AUTHORIZATION

```text
El DESIGN previo marcaba UNIT-1, UNIT-2, UNIT-4 y UNIT-6 con AUTHORIZATION_REQUIRED = NO.
Eso contradice el contrato: DESIGN != IMPLEMENTATION · NO AUTHORIZATION = NO EXECUTION.

RECTIFICACIÓN:
  Toda unidad que EJECUTE una modificación de código declara:
    USER_AUTHORIZATION_REQUIRED = YES   (incluye UNIT-1..UNIT-8)
  No se inventa un canonical gate para simples cambios de código.

Distinción:
  USER_AUTHORIZATION_REQUIRED = YES   → autorización del usuario para tocar código.
  GATE_DATABASE_CHANGE               → se consume SOLO cuando la unidad va a modificar
                                        datos protegidos / gradebook / DB conforme a
                                        AGENTS.md y moodle-grading/SKILL.md.

Ejecución secuencial (sin autorización en cadena):
  AUTHORIZE UNIT-N → IMPLEMENT UNIT-N → VERIFY UNIT-N → STOP → pedir autorización UNIT-N+1
  cuando la unidad siguiente tenga impacto independiente. Una autorización de UNIT-N
  NO autoriza automáticamente UNIT-N+1.
```

### RECTIFICATION_4 — SAFE_ROLLBACK_WITH_PREEXISTING_DIRTY_WORKTREE

```text
El rollback NO puede usar git checkout / git restore / git reset como mecanismo normal:
existe un working tree sucio preexistente y moodle/reportes/reporteTPporCurso.php ya
estaba modificado antes de este SDD.

ROLLBACK_USES_GIT_CHECKOUT_RESTORE_RESET = NO

En su lugar, PRE_UNIT_BASELINE_CAPTURE antes de cada unidad de código:
  - git status (READ-ONLY)
  - git diff (READ-ONLY)
  - checksum del archivo (p. ej. sha256sum)
  - copia/snapshot exacto del archivo previo a ESA unidad, en ubicación temporal/auxiliar
    controlada (cuando la unidad esté autorizada)
  - registrar exactamente qué hunks introduce la unidad

Rollback de una unidad:
  - revertir exclusivamente los cambios introducidos por ESA unidad
  - preservar todos los cambios preexistentes
  - verificar checksum/diff contra PRE_UNIT_BASELINE_CAPTURE
  - nunca limpiar el working tree completo

La creación de la copia/patch auxiliar forma parte de la IMPLEMENTATION autorizada de la
unidad, NO de esta ejecución DESIGN.

Siguen prohibidos sin autorización específica:
  git reset · git restore · git clean · git switch · git checkout · git commit ·
  git push · git merge · git rebase
```

### RECTIFICATION_5 — LOCAL_3_9_1_REFERENCE_4_0_5_COMPATIBILITY

```text
LOCAL_MOODLE_VERSION    = Moodle 3.9.1+ (Build 20200814)   [FACT verificado version.php]
PROJECT_REFERENCE_VERSION = Moodle 4.0.5                    [referencia del encargo]

LOCAL_IMPLEMENTATION_TARGET     = ACTUAL_LOCAL_RUNTIME  (3.9.1+)
REFERENCE_COMPATIBILITY_TARGET  = MOODLE_4_0_5

PRODUCTION_MOODLE_VERSION = UNKNOWN_NOT_VERIFIED
  → antes de cualquier deployment, verificar la versión real de producción y la
    compatibilidad de las APIs. No conectar ni modificar producción en esta ejecución.
```

**Compatibilidad de APIs verificada contra el runtime local (3.9.1+):**

| API | Local 3.9.1+ | 4.0.5 | Uso en DESIGN |
|-----|:---:|:---:|---------------|
| `mod_forum\grades\forum_gradeitem` (+ `load_from_context`, `store_grade_from_formdata`, `user_has_grade`) | ✔ presente | ✔ | Escritura/lectura de nota de foro |
| `core_grades\component_gradeitem` | ✔ presente | ✔ | base de `forum_gradeitem` |
| `forum_update_grades()` / `forum_grade_item_update()` | ✔ presente | ✔ | sincronización Gradebook |
| `set_coursemodule_name()` | ✔ presente | ✔ | renombres de actividad |
| `grade_item::update_final_grade()` | ✔ presente | ✔ | grade_item manual del período |
| `grade_grade::fetch()` / `fetch_all()` / `delete()` | ✔ presente | ✔ | período override |
| `grade_get_grades()` | ✔ presente | ✔ | lectura de notas |
| `data_submitted()` / `required_param()` / `optional_param()` | ✔ presente | ✔ | formularios |

La implementación LOCAL continúa solo con APIs verificadas en el runtime local.

### Recomputación tras la rectificación

```text
DESIGN_INTERNAL_CONSISTENCY   = CONSISTENT
DESIGN_READY_FOR_AUTHORIZATION = YES
DESIGN_STATUS                 = COMPLETED
IMPLEMENTATION                = NOT_AUTHORIZED
```

---

## DESIGN_DEPENDENCY_RECTIFICATION_2026_08_17

> Rectificación documental del DESIGN (sin código, sin DB, sin renombres). Corrige
> exclusivamente `IMPLEMENTATION_DEPENDENCY_ORDER` y `RENAME_SCOPE_COMPLETENESS`.
> No reabre ninguna USER_DECISION; el mapa canónico sigue confirmado.

```text
DEPENDENCY_DEFECT =
  VALID_GRADED_TP_RUNTIME_INTEGRATION_BEFORE_CANONICAL_RENAME

POTENTIAL_RUNTIME_REGRESSION =
  CURRENT_LEGACY_TP_DISAPPEAR_FROM_REPORT
  (los nombres legacy fid 158..185 no matchean TP-CEE-NN; si VALID_GRADED_TP
   gobernara el listado antes de renombrar, esos TP se excluirían del reporte)

REGLA DE ORDEN OBLIGATORIA:
  CANONICAL_RENAME_COMPLETE BEFORE VALID_GRADED_TP_RUNTIME_INTEGRATION
  (NO conectar VALID_GRADED_TP a listado/collect/cálculo/setupgrades mientras
   los nombres reales sigan en formato legacy; UNIT-1 permanece aislada)

CANONICAL_TP_RENAME_SCOPE = fid 158..185
NON_TP_RENAME_SCOPE        = fid 157
RENAME_PRECHECK_REQUIRED   = YES  (§3.4)
VALID_GRADED_TP_RUNTIME_INTEGRATION_REQUIRES_CANONICAL_NAMES = YES

DESIGN_DEPENDENCY_RECTIFICATION = COMPLETED
DESIGN_INTERNAL_CONSISTENCY     = CONSISTENT
DESIGN_STATUS                   = COMPLETED
DESIGN_READY_FOR_AUTHORIZATION  = YES

IMPLEMENTATION = PARTIAL  (UNIT-1 CLOSED_SUCCESS)
NEXT_UNIT      = UNIT-2A  (CANONICAL_RENAME_PREFLIGHT_READ_ONLY)
NEXT_UNIT_STATUS = NOT_AUTHORIZED
UNIT_2B_STATUS = NOT_AUTHORIZED
UNIT_3_STATUS  = NOT_AUTHORIZED
```

---

## 0. Alcance y restricciones de esta fase

Esta fase produce **solamente** el diseño técnico. No modifica código, Moodle, base de
datos, calificaciones ni renombres. El diseño es **implementable, mínimo, reversible y
verificable**.

```text
AUTORIZADO_EN_ESTA_FASE =
  READ_ONLY_DISCOVERY_FOR_DESIGN
  + DESIGN_DOCUMENTATION
  + CURRENT_STATE_UPDATE_IF_DESIGN_COMPLETES

NO_AUTORIZADO_EN_ESTA_FASE =
  modificar reporteTPporCurso.php / otros PHP / Moodle core
  escribir grade_items / grade_grades / forum / course_sections
  ejecutar setupgrades / savegrade / saveperiodgrade
  force_regrading() / grade_regrade_final_grades()
  renombres Moodle · SQL de escritura · operaciones Git mutantes
  tocar producción / Docker / moodledata
```

---

## 1. FACT — Baseline real del código (inspección READ-ONLY)

Fuente inspeccionada: `moodle/reportes/reporteTPporCurso.php` (1529 líneas, worktree
**sucio preexistente**: `git diff` reporta 459 inserciones / 1 borrado respecto de HEAD).

> **FACT — versión de Moodle.** El `moodle/version.php` local reporta
> `$version = 2020061501.07`, `$release = '3.9.1+ (Build: 20200814)'`, `$branch = '39'`.
> **El entorno local es Moodle 3.9.1+, NO 4.0.5** como se asumía en el encargo.
> Todas las APIs oficiales usadas en este diseño (`mod_forum\grades\forum_gradeitem`
> con `store_grade_from_formdata()`/`user_has_grade()`, `forum_update_grades()`,
> `forum_grade_item_update()`, `set_coursemodule_name()`, `grade_item::update_final_grade()`,
> `grade_grade::fetch()/fetch_all()/delete()`, `grade_get_grades()`, `data_submitted()`)
> existen y son estables tanto en 3.9 como en 4.x. `grade_update()` solo aparece como
> mecanismo INTERNO de `forum_grade_item_update()` y como descripción del código legado
> actual (ver RECTIFICATION_1 y RECTIFICATION_5). Se diseña contra la realidad local
> (3.9.1+) y se señala la discrepancia como FACT, no como bloqueo.

### 1.1 Git (READ-ONLY)

```text
BRANCH  = feature/copia-local-moodle
HEAD    = 809ba63e2334528901b44163e98ea8cd9a2b0afb
REMOTE  = github-viejo → https://github.com/albertohilal/arteytecnologia.git
STATUS  = adelantada 1 commit a github-viejo/feature/copia-local-moodle
WORKTREE = DIRTY preexistente:
           modified   .gitignore
           modified   README.md
           modified   moodle/reportes/reporteTPporCurso.php  (459+/1-)
           untracked  .opencode/ AGENTS.md AUXILIAR/ docs/ (todo el árbol)
```

Se reconoce expresamente: **`reporteTPporCurso.php` ya tiene cambios locales
preexistentes** y **no se asume que coincida con HEAD**. El worktree es evidencia.

### 1.2 Mapa de funciones y comportamiento actual

| # | Aspecto | FACT (código actual) |
|---|---------|----------------------|
| 1 | Estudiantes | `SELECT ... FROM {user} u JOIN {role_assignments} ra ... WHERE ra.roleid = 5` (rol estudiante **hardcodeado**); docentes ven todos, estudiantes solo su fila. |
| 2 | Foros TP | `SELECT ... FROM {forum} f JOIN {course_modules} cm ... WHERE f.name LIKE 'TP-%' AND m.name='forum' AND cm.visible=1 AND cs.visible=1` (no excluye `type='news'` en el listado principal). |
| 3 | Parser actual | `reporte_tp_parse_tp_number()`: `preg_match('/^TP-(\d+)/i', $name, $m)` → extrae el número secuencial tras `TP-`. **Modelo viejo TP-NN**, incompatible con TP-CEE-NN. |
| 4 | Config períodos | `$period_config[15] = [[1,10],[11,20]]` (rangos por **número de TP**); `reporte_tp_get_period_config()` / `reporte_tp_get_period_range()`. **Modelo viejo**, no TP-CEE-NN. |
| 5 | `setupgrades` | SQL `name LIKE 'TP-%'` (NO excluye `type='news'`); por cada foro: `grade_forum=10`, `grade_forum_notify=0`, `$DB->update_record('forum')`, `forum_grade_item_update($forum)`; `rebuild_course_cache()`. **Bug conocido: captura el foro de anuncios fid 157.** |
| 6 | Lectura calif. foro | `grade_get_grades($courseid, 'mod', 'forum', $forum->id, $studentids)` → `$gradesinfo->items[1]->grades` (API oficial). |
| 7 | Escritura calif. foro | acción `savegrade` → `grade_update('mod/forum', course, 'mod', 'forum', fid, 1, $grade, $itemdetails)` (API oficial; whole-forum grading). **RIESGO: no persiste `forum_grades` (ver RECTIFICATION_1).** |
| 8 | Cálculo período | `reporte_tp_compute_period_grades()`: `delivered` = `(grade>=4)`; `total` = `count($periodforums)`; `grade = (int)round(($delivered/$total)*10)`. |
| 9 | Grade_item período | `reporte_tp_ensure_period_grade_item()`: `itemtype='manual'`, `gradetype=GRADE_TYPE_VALUE`, `grademax=10`, `grademin=0`, `idnumber="periodo{period}-{courseid}"`, `itemname="Cuatrimestre {period}"`. |
| 10 | Escritura período | `reporte_tp_write_period_grade()`: `grade_item::update_final_grade($userid, $grade)` (API oficial). |
| 11 | Borrado período | `reporte_tp_delete_period_grade()`: `grade_grade::fetch()` + `$grade->delete()` + `$item->force_regrading()`. |
| 12 | Lectura período | `reporte_tp_get_saved_period_grades()`: `grade_grade::fetch_all(['itemid'=>...])`. |
| 13 | UI TP individual | por celda: input `grade` (0..grade_forum, step 1) + botón "Guardar" → POST `savegrade` por foro/usuario. |
| 14 | UI período | columnas "Cuatrimestre 1/2"; input con `placeholder` = nota calculada (propuesta) y `value` = override persistido; un único `<form id="periodgrade-form">` → POST `saveperiodgrade`. |
| 15 | Permisos | `reporte_tp_role_flags()`: roles grader = manager/editingteacher/teacher; `is_siteadmin`; `isstudentonly`; capacidades `moodle/course:manageactivities` (setup) y `mod/forum:grade` (calificación). |
| 16 | CSRF | `require_sesskey()` en los tres POST (`setupgrades`, `savegrade`, `saveperiodgrade`). |
| 17 | Transacción | `saveperiodgrade` usa `$DB->start_delegated_transaction()` + `allow_commit()`/`rollback()`. |
| 18 | SQL grades | Solo **lectura** vía APIs (`grade_get_grades`, `grade_grade::fetch*`). **Sin SQL de escritura** a `grade_items`/`grade_grades` en el código actual. |

---

## 2. DESIGN — Identificador TP-CEE-NN (parsing contract único)

### 2.1 DECISIÓN — `parse_tp_identifier()` como única fuente de verdad

```text
DESIGN_DECISION = DD-PARSER-SINGLE-CONTRACT

Reemplaza a reporte_tp_parse_tp_number().
```

Se define **una única función** de parsing reutilizada por `setupgrades`,
`collect_period_forums`, render/UI, cálculo de período y validación/reporte:

```text
parse_tp_identifier(string $name): array
```

Gramática canónica (del SPECIFICATION, sin reabrir):

```text
CANONICAL_GRAMMAR = ^TP-([0-9]{3})-([0-9]{2})(?:\s*-\s*.*)?$
  grupo 1 = CEE  (3 dígitos: C + EE)
  grupo 2 = NN   (2 dígitos)
  sufijo  = " - descripción" (cosmético, opcional)
```

Contrato de retorno (nombres propuestos para la futura implementación):

```text
parse_tp_identifier($name) → [
    'valid'                    => bool,
    'error'                    => string|null,  // 'invalid_format' | 'encounter_out_of_period'
    'cee'                      => string|null,  // 3 dígitos originales
    'period'                   => int|null,     // 1 | 2 | null
    'encounter_number'         => int|null,     // EE, 1..24
    'tp_number_within_encounter' => int|null,   // NN, 1..99
]
```

### 2.2 DECISIÓN — Derivación de período y validación de rango de Encuentro

```text
DESIGN_DECISION = DD-PERIOD-FROM-DIGIT + DD-ENCOUNTER-RANGE-GATE

PERIOD_FROM_DIGIT(C):
  '0' → 1  (PERIOD_1)
  '2' → 2  (PERIOD_2)
  resto → null  (NO_PERIOD)

PERIOD_ENCOUNTER_RANGE:
  PERIOD_1 = Encuentros 01..07  (CEE 001..007)
  PERIOD_2 = Encuentros 08..24  (CEE 208..224)

CANONICAL_NN_RANGE = 01..99
  (SPECIFICATION: NN es el ordinal del TP dentro del Encuentro, empezando
   canónicamente en 01. NN = 00 NO es un identificador canónico válido.
   invalid_format comprende también un ordinal NN fuera del dominio 01..99.)
```

Algoritmo de `parse_tp_identifier()`:

```text
1. regex (flag i) ^TP-([0-9]{3})-([0-9]{2})(?:\s*-\s*.*)?$ sobre $name
     no matchea → { valid:false, error:'invalid_format' }
2. cee = m[1]; c = cee[0]; ee = (int)substr(cee,1,2); nn = (int)m[2]
3. validación del ordinal NN:
     nn < 1  (NN = 00) → { valid:false, error:'invalid_format' }
4. period = PERIOD_FROM_DIGIT(c)
     null → { valid:false, error:'encounter_out_of_period' }   // C ∉ {0,2}
5. validación de rango del Encuentro contra el período:
     period==1 y ee ∉ 1..7  → { valid:false, error:'encounter_out_of_period' }
     period==2 y ee ∉ 8..24 → { valid:false, error:'encounter_out_of_period' }
6. else → { valid:true, cee, period, encounter_number:ee,
            tp_number_within_encounter:nn, error:null }
```

**Justificación de la validación de rango (paso 4):** el SPECIFICATION codifica período
en `C` y rango de Encuentro en `EE`; ambos deben ser coherentes. Un CEE como `015` (C=0,
EE=15) o `207` (C=2, EE=07) es internamente inconsistente y debe excluirse y reportarse,
no calificarse silenciosamente. El gate `valid` solo distingue las dos categorías
canónicas (`invalid_format` / `encounter_out_of_period`); el mensaje de reporte puede
añadir detalle (`C fuera de {0,2}` vs `EE fuera del rango del período`) sin cambiar el gate.

**Decisión de case-sensitivity:** se aplica flag `/i` (como el código actual
`reporte_tp_parse_tp_number`) por compatibilidad con nombres existentes en mayúsculas;
los nombres canónicos de la BD son mayúsculas (`TP-`), por lo que el flag no altera la
clasificación real y solo añade tolerancia.

### 2.3 DECISIÓN — `VALID_GRADED_TP` como definición única

```text
DESIGN_DECISION = DD-VALID-GRADED-TP-SINGLE

VALID_GRADED_TP(activity) :=
      activity es foro (mod 'forum')
  AND activity.type != 'news'
  AND parse_tp_identifier(activity.name).valid == true
```

Se implementa como una única función:

```text
reporte_tp_is_valid_graded_tp(object $forum): bool
```

Reutilizada por: `setupgrades` (selección), `collect_period_forums` (agrupado), cálculo de
período y render/UI. Excluye de forma explícita:

```text
forum.type = 'news'
identificador inválido (no matchea gramática)
CEE fuera de período (C ∉ {0,2} o EE fuera del rango del período)
actividad no correspondiente (no es foro / name sin TP-)
```

### 2.4 DECISIÓN — Comportamiento FAIL-SAFE

```text
DESIGN_DECISION = DD-FAIL-SAFE-REPORT

FAIL_SAFE_REPORT:
  Cualquier actividad cuyo identificador no se interpreta de forma no ambigua
  (TP-105, TP-99, TP-ABC, TP-08-2 duplicado, nombre sin TP, news, CEE inconsistente)
  NO entra al cálculo automático y se reporta como anomalía pendiente de rename/definición.
```

No se califica silenciosamente. No se aborta el reporte completo por un identificador
inválido: se excluye esa actividad y se emite una advertencia visible para el docente
(nunca un write).

---

## 3. DESIGN — Mapa de normalización (renombres, sin ejecutar)

### 3.1 DECISIÓN — Mapa canónico fuente de verdad

```text
DESIGN_DECISION = DD-NORMALIZATION-MAP

CANONICAL_TP_RENAME_SCOPE = fid 158..185   (actividades TP, renombre canónico)
NON_TP_RENAME_SCOPE        = fid 157       (foro news, no TP)

NORMALIZATION_MAP (CONFIRMADO, completo, no ejecutar):
  fid 158 → TP-001-01 · fid 159 → TP-001-02
  fid 160 → TP-002-01 · fid 161 → TP-002-02 · fid 162 → TP-002-03
  fid 163 → TP-003-01
  fid 164 → TP-006-01 · fid 165 → TP-006-02
  fid 166 → TP-007-01
  fid 167 → TP-208-01 · fid 168 → TP-208-02 · fid 169 → TP-208-03
  fid 170 → TP-209-01
  fid 171 → TP-211-01
  fid 172 → TP-212-01
  fid 173 → TP-213-01
  fid 174 → TP-214-01
  fid 175 → TP-215-01 · fid 176 → TP-215-02
  fid 177 → TP-216-01
  fid 178 → TP-217-01 · fid 179 → TP-218-01 · fid 180 → TP-219-01
  fid 181 → TP-220-01 · fid 182 → TP-221-01 · fid 183 → TP-222-01
  fid 184 → TP-223-01 · fid 185 → TP-224-01

  fid 157: "TP-00-Programa de la asignatura" → "Programa de la asignatura"
           type = news · IS_TP = NO · renombre conceptual confirmado

RENAME_PRECHECK_REQUIRED = YES
  Antes de cualquier write de renombre, ejecutar un PRECHECK READ-ONLY por fid
  (ver §3.4). El write se detiene si current_name no coincide con el nombre de
  origen del mapa, salvo que ya sea ALREADY_CANONICAL.
```

### 3.2 DECISIÓN — Mecanismo de renombre seguro (para futura IMPLEMENTATION)

> **SUPERSEDED por RECTIFICATION_2 — OFFICIAL_MODULE_RENAME_API.**
> El mecanismo de renombre se rectifica a `set_coursemodule_name($cmid, $newname)`
> (API oficial), que además sincroniza grade item, evento de curso, cache y calendario.
> Ver la sección `DESIGN_RECTIFICATION_2026_08_17` (RECTIFICATION_2).

### 3.3 DECISIÓN — Impacto esperado del renombre y preservación de calificaciones

```text
DESIGN_DECISION = DD-RENAME-IMPACT

IMPACTO:
  forum.name          → cambia (solo el campo name del registro).
  course cache        → invalida/rebuild; nombre visible en secciones cambia.
  grade_item names    → itemname sigue al foro; idnumber e iteminstance NO cambian.
  report UI           → muestra el nombre canónico nuevo.
  gradebook           → muestra el nombre nuevo; valores numéricos intactos.

RESINCRONIZACIÓN (RECTIFICATION_2):
  set_coursemodule_name() provoca resincronización del grade item vía
  grade_update_mod_grades() → forum_update_grades() → forum_grade_item_update().
  forum_update_grades() puede RELEER forum_grades. Por eso, ANTES del renombre,
  forum_grades y grade_grades deben ser coherentes (RECTIFICATION_1).

PRESERVACIÓN:
  grade_grades se indexa por itemid (no por nombre); el renombre de forum.name
  y de grade_item.itemname NO debe cambiar los VALORES NUMÉRICOS. Se verifica
  identidad numérica PRE/POST (RENAME_GRADE_PRESERVATION_PLAN). El renombre sí
  ejecuta el proceso de sincronización de calificaciones del foro; NO se afirma
  "sin regrade/sincronización".
```

### 3.4 DECISIÓN — PRECHECK READ-ONLY del mapa de normalización

```text
DESIGN_DECISION = DD-RENAME-PRECHECK

RENAME_PRECHECK_REQUIRED = YES
  Antes de CUALQUIER write de renombre, ejecutar un PRECHECK READ-ONLY que para
  CADA fid produzca:

  fid · cmid · current_name · expected_name · type · courseid · grade_forum
  · forum_grades_count · grade_items_count · grade_grades_count

  y clasifique cada fid:

  READY_TO_RENAME            → current_name coincide con el nombre de origen del mapa
  ALREADY_CANONICAL          → current_name ya es el nombre canónico esperado
  UNEXPECTED_CURRENT_NAME    → current_name NO coincide con el mapa (investigar)
  BLOCKED_GRADE_INCONSISTENCY → incoherencia PRE entre forum_grades.grade y
                                grade_grades.finalgrade (no renombrar)

  El PRECHECK NO modifica ningún dato. El write futuro se detiene si
  current_name != nombre esperado del mapa, salvo ALREADY_CANONICAL.

  Coherencia PRE obligatoria cuando existan notas: forum_grades.grade ==
  grade_grades.finalgrade (FORUM_GRADE_SOURCE_OF_TRUTH = forum_grades).
```

---

## 4. DESIGN — Evaluación individual del TP

> **RECTIFICADO 2026-08-18 (INVALIDATED_BY_USER_VERIFICATION).** Las decisiones
> previas `DD-TP-EVALUATION-WORKFLOW` (CRITERIA_SELECTION → …) y
> `DD-TP-EVAL-FORM-MODEL` (selects Desarrollo/Presentación + rango orientativo)
> quedan SUPERSEDED. DESARROLLO y PRESENTACIÓN son criterios pedagógicos, NO
> controles de interfaz. Ver `TP_EVALUATION_UI_RECTIFICATION` (§4.5).

### 4.1 DECISIÓN — Flujo confirmado (rectificado 2026-08-18)

```text
DESIGN_DECISION = DD-TP-EVALUATION-WORKFLOW (rectificado)

PEDAGOGICAL_ASSESSMENT → TEACHER_GRADE_EDIT
  → TEACHER_CONFIRMATION → GRADEBOOK_WRITE

PEDAGOGICAL_ASSESSMENT =
  evaluación conceptual realizada por el docente utilizando criterios de
  Desarrollo + Presentación. NO es un estado persistido del sistema.
```

Criterios pedagógicos y matriz orientativa (rectificado):

```text
DESARROLLO   ∈ { ADECUADO, NO_ADECUADO }
PRESENTACION ∈ { COMPLETA, INCOMPLETA }

CRITERIA_ARE_PEDAGOGICAL_ONLY = YES
CRITERIA_ARE_UI_CONTROLS      = NO
CRITERIA_PERSISTED_AS_DATA    = NO

ADECUADO    + COMPLETA    → 9–10
ADECUADO    + INCOMPLETA  → 7–8
NO_ADECUADO + COMPLETA    → 5–6
NO_ADECUADO + INCOMPLETA  → 4

LAST_BAND_4_ONLY = YES   (INVALIDADO el "4–5" anterior)

RANGES_ARE_ORIENTATIVE     = YES
AUTOMATIC_FINAL_TP_GRADE   = NO
GRADE_IS_EDITABLE_BEFORE_SAVE = YES
TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES
```

### 4.2 DECISIÓN — Modelo funcional del formulario (NO HTML/PHP aún) — RECTIFICADO

> **SUPERSEDED_BY_USER_VERIFICATION (2026-08-18).** El modelo anterior con selects
> `Desarrollo` / `Presentación` y `Rango orientativo` queda INVALIDADO. Nuevo modelo
> `DD-TP-GRADE-CELL-UI`:

```text
DESIGN_DECISION = DD-TP-GRADE-CELL-UI   (2026-08-18, rectificado)
```

Celda de TP (docente con permiso de calificación):

```text
enlaces existentes del TP

calificación individual editable 0..10

[ Guardar ]

/10
```

```text
TP-001-01
Ver
[ 8 ] [ Guardar ] /10
```

NO se agregan: `Desarrollo` (select), `Presentación` (select), `Rango orientativo`.

Comportamiento:

```text
- PEDAGOGICAL_ASSESSMENT: el docente evalúa conceptualmente el TP usando criterios
  de Desarrollo + Presentación (fuera del sistema; NO hay controles ni persistencia).
- TEACHER_GRADE_EDIT: la nota final es un input libre 0..10 (entero).
  Si existe calificación previa, se precarga con la nota existente del gradebook
  (EXISTING_GRADEBOOK_GRADE = READ); la nota es editable.
- TEACHER_CONFIRMATION: única acción que dispara POST (botón Guardar).
- GRADEBOOK_WRITE: ocurre SOLO tras el POST explícito.
```

### 4.3 DECISIÓN — Precarga, modificación, POST, validación, CSRF, vacío

```text
DESIGN_DECISION = DD-TP-EVAL-SEMANTICS
```

- **Precarga de nota existente:** leer `grade_get_grades()` (como hoy) y mostrar el
  valor en el input. No hay criterios ni selects (DESARROLLO/PRESENTACION son
  criterios pedagógicos externos, no controles de UI). Al recargar, si hay nota
  guardada se muestra la nota.
- **Modificación:** el input es editable; el docente puede corregir la nota precargada.
- **POST:** solo el submit "Guardar calificación" (acción `savegrade`). Misma sesión
  POST + `sesskey`.
- **Validación servidor (obligatoria, en este orden):**
  1. `require_sesskey()`.
  2. `require_login($course)` + `context_course` + `reporte_tp_role_flags().caneditreport`.
  3. `require_capability('mod/forum:grade', $modulecontext)`.
  4. `is_enrolled($coursecontext, $userid)`.
  5. `grade_forum > 0` (foro calificable), y el foro debe ser `VALID_GRADED_TP`.
  6. Nota: vacía → "borrar" (ver abajo); si no vacía → `^\d{1,2}$` y `0..10`.
- **Preservar nota si no guarda:** sin POST no hay escritura. Si el docente cambia el
  input pero NO guarda, el gradebook queda intacto (NO_SILENT_GRADE_OVERWRITE).
- **Campo vacío:** vacío + guardar = acción explícita de "limpiar calificación".
  Se borra la nota usando la API oficial del componente (RECTIFICATION_1), NO
  `grade_update()` directo:

  ```text
  CLEAR_TP_GRADE_API =
    mod_forum\grades\forum_gradeitem
      → store_grade_from_formdata($gradeduser, $grader, (object)['grade' => ''])
  ```

  Resultado: `forum_grades.grade = NULL` y sincronización posterior del Gradebook a
  NULL vía `forum_update_grades()` / `forum_grade_item_update()`.
  (FACT verificado: `unformat_float('')` → null y `grade_floatval('')` → null, por lo
  que `grade => ''` produce `grade = null` en `store_grade()`.)

  Round-trip de borrado:

  ```text
  PRE:  forum_grades.grade = valor existente · grade_grades.finalgrade = mismo valor
  POST: forum_grades.grade = NULL · grade_grades.finalgrade = NULL
  ```

  Requiere confirmación explícita del docente antes del borrado. No es un no-op silencioso.
- **CSRF:** `sesskey` en el formulario y `require_sesskey()` en el POST.

### 4.4 DECISIÓN — Capacidades Moodle requeridas

```text
DESIGN_DECISION = DD-TP-EVAL-CAPABILITIES

  mod/forum:grade                 → calificar un TP (contexto de módulo).
  moodle/course:manageactivities  → habilitar calificación (setupgrades).
  role_flags.caneditreport        → gate de rol (manager/editingteacher/teacher/siteadmin).
  is_enrolled                     → el usuario objetivo debe estar matriculado.
```

### 4.5 TP_EVALUATION_UI_RECTIFICATION — UNIT-4 redefinida (2026-08-18)

> **SUPERSEDED_BY_USER_VERIFICATION.** La definición vigente de UNIT-4
> "UI de evaluación TP (selects DESARROLLO/PRESENTACION + rango orientativo +
> nota editable)" queda INVALIDADA por verificación visual de Alberto.

```text
UNIT-4_OLD_DEFINITION =
  UI de evaluación TP (selects DESARROLLO/PRESENTACION
  + rango orientativo + nota editable)

UNIT-4_OLD_DEFINITION_STATUS = SUPERSEDED_BY_USER_VERIFICATION

UNIT-4 =
  TP_GRADE_CELL_UI

UNIT-4_PURPOSE =
  mantener/restaurar la interfaz individual del TP:
    - enlaces;
    - nota editable 0..10;
    - Guardar;
    - /10;
  sin controles Desarrollo/Presentación
  y sin rango orientativo por TP.

UNIT-4_DATABASE_IMPACT  = NONE
UNIT-4_GRADEBOOK_IMPACT = NONE
  (hasta ejecutar una acción explícita de guardado)
```

Decisiones confirmadas:

```text
D-TP-EVALUATION-CRITERIA-PEDAGOGICAL-ONLY = CONFIRMED
D-TP-UI-NO-CRITERIA-CONTROLS = CONFIRMED

DEVELOPMENT_UI_CONTROL     = NO
PRESENTATION_UI_CONTROL    = NO
DEVELOPMENT_PERSISTED      = NO
PRESENTATION_PERSISTED     = NO
TP_ORIENTATIVE_RANGE_CONTROL   = NO
TP_ORIENTATIVE_RANGE_PERSISTED = NO
```

La rectificación de IMPLEMENTATION todavía NO está autorizada:

```text
UNIT-4_RECTIFICATION_IMPLEMENTATION = NOT_AUTHORIZED
```

Se preserva: `D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL` (UI de período) intacta; el
"Rango orientativo" del TP NO se confunde con la propuesta de calificación del
período.

---

## 5. DESIGN — Escritura al Gradebook

### 5.1 DECISIÓN — API de escritura de la nota individual de foro (RECTIFICADA)

> **SUPERSEDED por RECTIFICATION_1 — FORUM_GRADE_SOURCE_OF_TRUTH.**
> La decisión previa `DD-GRADEBOOK-WRITE-API = KEEP grade_update()` queda rectificada.
> La fuente de verdad del módulo foro es `forum_grades`; la escritura individual debe
> pasar por `mod_forum\grades\forum_gradeitem` para persistir `forum_grades` y luego
> sincronizar el Gradebook, evitando el riesgo `DIRECT_GRADE_UPDATE_ONLY_RISK`.
> Ver la sección `DESIGN_RECTIFICATION_2026_08_17` (RECTIFICATION_1).

```text
TP_GRADE_WRITE_API =
  mod_forum\grades\forum_gradeitem::load_from_context($modulecontext)
    → require_user_can_grade($gradeduser, $grader)
    → store_grade_from_formdata($gradeduser, $grader, (object)['grade' => $grade])
```

**Flujo (spec cerrado + skill `moodle-grading`):**

```text
FORMULARIO → VALIDACIÓN → CONFIRMACIÓN DOCENTE → forum_gradeitem API
  → forum_grades → forum_update_grades() → GRADEBOOK → RELECTURA (ambas capas)
```

### 5.2 DECISIÓN — Preservación y no-sobrescritura silenciosa

```text
DESIGN_DECISION = DD-NO-SILENT-OVERWRITE

EXISTING_GRADEBOOK_GRADE = READ_BEFORE_WRITE
NO_SILENT_GRADE_OVERWRITE = YES
```

- Antes de escribir, leer la calificación existente (`grade_get_grades`) y mostrarla.
- La escritura solo ocurre tras confirmación explícita del docente.
- Un POST sin cambios respecto de la nota existente es idempotente (reescribe el mismo
  valor) o se puede omitir; no se fuerza nunca un borrado implícito.

### 5.3 DECISIÓN — Escritura del grade_item de período

```text
DESIGN_DECISION = DD-PERIOD-WRITE-API

KEEP grade_item::update_final_grade($userid, $grade) (API oficial) para la nota de período.
KEEP grade_grade::delete() + $item->force_regrading() para borrar un override.
```

No se diseñará SQL directo de escritura a `grade_items`/`grade_grades` en ningún caso.

---

## 6. DESIGN — Cálculo del período (10/N + HALF_UP)

### 6.1 DECISIÓN — Fórmula (spec cerrado, sin reabrir)

```text
DESIGN_DECISION = DD-PERIOD-FORMULA

TOTAL_VALID_TP = N                      (N = nº de foros VALID_GRADED_TP del período)
COUNTED_TP     = cantidad de TP PRESENTED_AND_EVALUATED

TP_WEIGHT         = 10 / N
PERIOD_GRADE_RAW  = (COUNTED_TP / N) * 10 = (10 * COUNTED_TP) / N
PERIOD_GRADE_FINAL = ROUND_HALF_UP(PERIOD_GRADE_RAW)

PERIOD_GRADE_STORAGE = INTEGER_ONLY
```

### 6.2 DECISIÓN — Redondeo exacto en aritmética entera

```text
DESIGN_DECISION = DD-PERIOD-ROUNDING-EXACT
```

**Verificación del `round()` actual:** PHP `round()` usa por defecto `PHP_ROUND_HALF_UP`
(redondea el medio alejándose de cero); para valores no negativos equivale exactamente a
HALF_UP. **El código actual ya es HALF_UP para valores válidos.** El riesgo no es la regla
sino la **deriva de punto flotante** de `($delivered/$total)*10`.

**Recomendación de implementación** (evita flotante; COUNTED_TP y N son enteros):

```text
PERIOD_GRADE_FINAL = (N == 0)
  ? 0
  : intdiv(20 * COUNTED_TP + N, 2 * N)
```

Equivalencia matemática (exacta, sin deriva): `floor((10*C)/N + 0.5) = floor((20*C + N)/(2*N))`.

Verificación contra los ejemplos normativos del spec:

```text
N=10,C=7 → intdiv(150,20)=7   (7.0→7)   ✓
N=20,C=7 → intdiv(160,40)=4   (3.5→4)   ✓ (half-up)
N=8, C=6 → intdiv(128,16)=8   (7.5→8)   ✓ (half-up)
N=8, C=5 → intdiv(108,16)=6   (6.25→6)  ✓
N=8, C=3 → intdiv(68,16)=4    (3.75→4)  ✓
N=24,C=17→ intdiv(364,48)=7   (7.08→7)  ✓
```

### 6.3 DECISIÓN — Clasificación académica (por período, independiente)

```text
DESIGN_DECISION = DD-PERIOD-CLASSIFICATION (spec cerrado)

[7,10]  → PROMOCION
[4,7)   → EXAMEN_FINAL_DICIEMBRE_O_MARZO
[0,4)   → RECURSA

SCOPE = EACH_PERIOD_INDEPENDENTLY
P1/P2 AVERAGE = NO
```

No se diseña ninguna regla anual. La clasificación se aplica a `PERIOD_GRADE_FINAL`
(entero, después del redondeo).

---

## 7. DESIGN — Semántica de TP contabilizado (terminología inequívoca)

### 7.1 DECISIÓN — Terminología interna

```text
DESIGN_DECISION = DD-COUNTED-TERMINOLOGY

TP_PRESENTED_AND_EVALUATED → aporta el 100% de TP_WEIGHT
TP_NOT_PRESENTED           → aporta 0
```

El proxy actual confirmado: **forum grade >= 4 → PRESENTED_AND_EVALUATED**.

Nombres concretos propuestos para la futura implementación (no renombrar código ahora):

| Concepto | Nombre propuesto | Justificación |
|----------|------------------|---------------|
| Total de TP válidos del período (N) | `$total_valid_tp` / `reporte_tp_count_valid_tp()` | "valid" liga a `VALID_GRADED_TP`. |
| Cantidad que aporta peso | `$counted_tp` / `reporte_tp_count_presented_and_evaluated()` | "counted" = "contabilizado en el período"; evita "delivered/approved/passed". |
| Regla por TP | `reporte_tp_is_presented_and_evaluated(float $grade): bool` | `$grade >= 4`. |
| Cálculo de nota | `reporte_tp_compute_period_grade(int $counted_tp, int $total_valid_tp): int` | renombra params `delivered,total`. |

**Justificación de la elección:** `counted` y `presented_and_evaluated` representan
exactamente la regla pedagógica confirmada (presentado **y** evaluado → cuenta), sin
arrastrar la ambigüedad de `delivered/entregados` (que sugería "entrega realizada" en vez
de "forum grade >= 4") ni de `approved/passed` (que sugería aprobación individual, lo que
está explícitamente descartado: la nota individual NO se promedia).

---

## 8. DESIGN — Grade_item de período (KEEP/MODIFY/REPLACE)

### 8.1 DECISIÓN por función

| Función | Veredicto | Motivo |
|---------|-----------|--------|
| `reporte_tp_ensure_period_grade_item()` | **KEEP** (MODIFY menor: aceptar parámetro `period` sin cambiar `idnumber`; mantener `itemname`) | Crea el grade_item manual correcto vía API. No se requiere cambio estructural. |
| `reporte_tp_write_period_grade()` | **KEEP** | Usa `update_final_grade()` (API oficial). |
| `reporte_tp_get_saved_period_grades()` | **KEEP** | Lectura vía `grade_grade::fetch_all()`. |
| `reporte_tp_delete_period_grade()` | **KEEP** | Borra override vía `grade_grade::delete()` + `force_regrading()`. |

### 8.2 DECISIÓN — Contrato del grade_item de período

```text
DESIGN_DECISION = DD-PERIOD-GRADEITEM-CONTRACT

  itemname      = "Cuatrimestre {period}"   (1|2)
  idnumber      = "periodo{period}-{courseid}"
  itemtype      = 'manual'
  gradetype     = GRADE_TYPE_VALUE
  grademin      = 0
  grademax      = 10
  rango         = 0..10
```

> **SUPERSEDED por D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL (2026-08-18).**
> El modelo "input de período: `placeholder` = nota calculada · `value` = override
> persistido" queda RECTIFICADO. La nota calculada ya NO se conceptualiza como
> `placeholder` del input de calificación. Nuevo modelo: `PROPOSED_GRADE_COLUMN`
> (READ_ONLY display) separado de `TEACHER_GRADE_COLUMN` (editable input).
> Ver §8.3 PERIOD_UI_COLUMNS. Se preserva a continuación como evidencia histórica.

**Semántica nota calculada vs override manual:**

```text
- La nota de período es una PROPUESTA CALCULADA (no se persiste automáticamente).
- El grade_item manual guarda el OVERRIDE explícito del docente.
- [SUPERSEDED] La nota calculada se muestra como placeholder (sugerencia de auto-nota).
- [SUPERSEDED] El input se precarga SOLO con el override persistido; vacío = "usar la
  calculada" (modelo de input único; reemplazado por dos columnas — ver §8.3).
- Campo vacío + guardar = ELIMINAR el override persistido (volver a la propuesta calculada).
- Escritura solo en saveperiodgrade (explicit save).
- Eliminación de override: reporte_tp_delete_period_grade() (delete + force_regrading).
- PERIOD_OVERRIDE_DELETE_REGRADING:
  al eliminar un grade_grade de un item manual, force_regrading() NO es un no-op:
  marca needsupdate=1 en el grade_item y en el course item correspondiente
  (FACT verificado: grade_item::force_regrading() → $this->needsupdate=1 +
  $DB->set_field_select('grade_items','needsupdate',1, "(itemtype='course' OR id=?) AND courseid=?", ...)),
  de modo que las agregaciones/totales dependientes puedan actualizarse conforme al
  Gradebook de Moodle.
  No ejecutar ningún regrade ahora. Cualquier ejecución futura que modifique
  grade_grades o active regrading queda bajo GATE_DATABASE_CHANGE = AWAITING_AUTHORIZATION.
- Round-trip: tras escribir, releer grade_grade::fetch(['itemid'=>..., 'userid'=>...])
  y comparar finalgrade con el valor guardado (entero 0..10).
```

### 8.3 DECISIÓN — PERIOD_UI_COLUMNS (RECTIFICACIÓN D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL)

```text
DESIGN_DECISION = DD-PERIOD-UI-SEPARATE-PROPOSAL-FINAL   (2026-08-18)

SUPERSEDED_BY = D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL:
  OLD_MODEL = "input de período: placeholder = nota calculada · value = override
               persistido"   (ver §8.2, marcado SUPERSEDED)
  NEW_MODEL = dos columnas separadas por cuatrimestre

PERIOD_UI_COLUMNS =
  P1_PROPOSED
  P1_TEACHER_GRADE
  P2_PROPOSED
  P2_TEACHER_GRADE
```

**P*_PROPOSED** (columna de propuesta calculada):

```text
P*_PROPOSED_READ_ONLY                  = YES
P*_PROPOSED_DISPLAYS_PERIOD_GRADE_FINAL = YES
P*_PROPOSED_DISPLAYS_COUNTED_TOTAL      = YES   (COUNTED_TP / TOTAL_VALID_TP)
P*_PROPOSED_INPUT                      = NO
P*_PROPOSED_POST                       = NO
P*_PROPOSED_PERSISTED                  = NO
```

**P*_TEACHER_GRADE** (columna de calificación docente):

```text
P*_TEACHER_GRADE_EDITABLE              = YES
P*_TEACHER_GRADE_INPUT                 = entero 0..10
P*_TEACHER_GRADE_PRECARGA              = override persistido si existe
P*_TEACHER_GRADE_PERSISTENCE           = SOLO submit explícito (saveperiodgrade)
P*_TEACHER_GRADE_AUTOMATIC_COPY_FROM_PROPOSED = NO
```

Semántica de override vigente (sin cambios):

```text
- Campo vacío = eliminar override persistido y volver a usar la propuesta calculada
  como referencia visual.
- NO copiar automáticamente la propuesta a grade_grades.
- Escritura solo en saveperiodgrade (explicit save).
```

Representación funcional (ilustrativa de interfaz; NO nuevos datos ni reglas de cálculo):

```text
Apellido | Nombre | ...TP... |
C1 propuesta | C1 calificación |
C2 propuesta | C2 calificación
```

Ejemplo conceptual:

```text
C1 propuesta:   8 / 10 · 7/9 TP
C1 calificación: [ 8 ]

C2 propuesta:   6 / 10 · 11/19 TP
C2 calificación: [ 7 ]
```

Nomenclatura de encabezados preferida:

```text
C1 propuesta · C1 calificación · C2 propuesta · C2 calificación
```

o

```text
Cuatrimestre 1 — Propuesta · Cuatrimestre 1 — Calificación
Cuatrimestre 2 — Propuesta · Cuatrimestre 2 — Calificación
```

NO utilizar todavía: `Calificación final` · `Nota final anual` · `Resultado anual`
(no existe fórmula anual que combine P1 y P2).

Impacto en unidades:

```text
AFFECTED_FUTURE_UNIT = UNIT-7  (Grade_item de período / override / UI período)
UNIT-4_SCOPE_CHANGED = NO       (UNIT-4 = UI criterios Desarrollo/Presentación del TP
                                 individual; separada de la UI de período)
UNIT-7_NOT_ADVANCED  = YES
```

> **Nota (2026-08-18):** la descripción de UNIT-4 en este bloque queda SUPERSEDED por
> TP_EVALUATION_UI_RECTIFICATION (§4.5): UNIT-4 = TP_GRADE_CELL_UI.

---

## 9. DESIGN — Configuración de períodos (reemplazo del rango numérico)

### 9.1 DECISIÓN — Reemplazar la configuración por rango de número de TP

```text
DESIGN_DECISION = DD-PERIOD-CONFIG-REPLACE

REPLACE $period_config[15] = [[1,10],[11,20]] y reporte_tp_get_period_range().
```

El período ya no se infiere de un rango numérico de TP (`1..10`, `11..20`) sino del
parser (`C` + rango de Encuentro). Nueva fuente de verdad:

```text
reporte_tp_get_period_of_forum(object $forum): ?int
  → parse_tp_identifier($forum->name)['period'] si VALID_GRADED_TP, si no null.

reporte_tp_collect_period_forums(array $forums, int $period): array
  → filtra por VALID_GRADED_TP + grade_forum===10.0 + period coincidente.
```

`reporte_tp_parse_tp_number()` y `reporte_tp_get_period_config()`/`reporte_tp_get_period_range()`
quedan **obsoletos** y se eliminan en la futura implementación.

---

## 10. BLOCKERS — Análisis de impacto (no corregir)

```text
BLOCKER_2_STATUS       = PENDING_NOT_ADDRESSED
  La BD local proviene de un dump anterior a las calificaciones actuales de producción.

LOCAL_LOGIN_DEPENDENCY = PENDING

OBSERVED_TECHNICAL_STATE = courseid=15 tiene grade_forum=0 en TODOS los foros
  (sin grade_items de foro creados; setupgrades no ejecutado).
```

**Impacto en IMPLEMENTATION y VERIFICATION (análisis, no corrección):**

| Bloqueo | Impacto en implementación | Impacto en verificación |
|---------|---------------------------|--------------------------|
| BD local anterior a producción | No afecta el código. Afecta la **fiabilidad** de las pruebas de preservación de notas. | No se puede verificar "preservar notas existentes / no-sobrescritura" contra datos reales de producción; solo con datos sintéticos. |
| Login local | No afecta el código. | Bloquea la verificación E2E de UI (LOGIN→CURSO→FORMULARIO…). |
| grade_forum=0 / sin grade_items | El código ya maneja el estado (setupgrades pendiente). | El cálculo de período con datos reales queda bloqueado hasta ejecutar `setupgrades` (acción mutante → requiere autorización). |

**Verificaciones POSIBLES con el estado local actual (READ-ONLY, sin mutar):**

```text
- Parsing TP-CEE-NN contra nombres reales (sin DB): unit-level.
- Redondeo HALF_UP exacto (intdiv) contra los ejemplos normativos: unit-level.
- SQL READ de forum.name + course_sections.name (ya ejecutado en SPECIFICATION):
  confirma el mapa fid→canónico.
- Revisión estática del código (linters, revisión de diff) sobre la futura implementación.
```

**Verificaciones BLOQUEADAS localmente:**

```text
- Ejecutar setupgrades (crea grade_items, muta DB) → requiere GATE_DATABASE_CHANGE.
- Round-trip de escritura de calificaciones reales (no hay notas locales).
- E2E de UI (login dependency).
- Renombres Moodle (mutación) y regrade.
```

---

## 11. IMPLEMENTATION_UNIT — Partición de trabajo futuro (orientativa, refinada al código real)

> **DESIGN_DEPENDENCY_RECTIFICATION_2026_08_17 + DESIGN_DEPENDENCY_RECTIFICATION_FINAL:**
> la secuencia anterior (UNIT-2 = integración VALID_GRADED_TP, UNIT-3 = renombres) queda
> **SUPERSEDED**. Los nombres locales siguen en formato LEGACY (fid 158..185); integrar
> VALID_GRADED_TP al runtime ANTES de renombrar excluiría los TP legacy del reporte.
> Además, la unidad de renombre se SEPARA en PRECHECK READ-ONLY (UNIT-2A) y WRITE
> (UNIT-2B) para respetar OBSERVE → VERIFY → AUTHORIZE → IMPLEMENT → VERIFY.

No se autoriza ninguna unidad. El orden se propone según dependencias y reversibilidad.

```text
IMPLEMENTATION_SEQUENCE_PREVIOUS (SUPERSEDED_BY_DEPENDENCY_RECTIFICATION):
  UNIT-1 parser → UNIT-2 integración VALID_GRADED_TP → UNIT-3 renombres → …

IMPLEMENTATION_SEQUENCE_PREVIOUS_2 (SUPERSEDED_BY_DEPENDENCY_RECTIFICATION_FINAL):
  UNIT-1 parser → UNIT-2 (PRECHECK + renombres indivisible) → UNIT-3 integración → …

IMPLEMENTATION_SEQUENCE_RECTIFIED (vigente):
  UNIT-1   Parser TP-CEE-NN + clasificación VALID_GRADED_TP (aislado)   ← CLOSED_SUCCESS
  UNIT-2A  CANONICAL_RENAME_PREFLIGHT_READ_ONLY   (READ-ONLY, sin gate)
  UNIT-2B  CANONICAL_RENAME_WRITE                 (authorization + GATE_DATABASE_CHANGE)
  UNIT-3   VALID_GRADED_TP_RUNTIME_INTEGRATION (listado/collect/períodos/setupgrades)
  UNIT-4   Modelo UI de evaluación del TP
  UNIT-5   Escritura segura de TP al Gradebook
  UNIT-6   Cálculo de período 10/N + HALF_UP (aritmética entera)
  UNIT-7   Grade_item de período / override
  UNIT-8   Verificación end-to-end

OBSERVE → VERIFY MAP → AUTHORIZE WRITE → RENAME → VERIFY → INTEGRATE PARSER
```

| Unidad | PURPOSE | FILES | DATABASE_IMPACT | GRADEBOOK_IMPACT | PRECONDITIONS | RISKS | ROLLBACK | VERIFICATION | USER_AUTHORIZATION_REQUIRED | GATE_DATABASE_CHANGE |
|--------|---------|-------|------------------|------------------|---------------|-------|-----------|--------------|-----------------------------|----------------------|
| UNIT-1 | Implementar `parse_tp_identifier()` y `reporte_tp_is_valid_graded_tp()` (clasificación, sin escribir). | `moodle/reportes/reporteTPporCurso.php` | Ninguno (read-only). | Ninguno. | SPECIFICATION cerrado. | Confusión de contrato si se usa en varios sitios; mitigar con tests unitarios del parser. | PRE_UNIT_BASELINE_CAPTURE (§13): revertir solo los hunks de esta unidad. | Unit tests del parser contra nombres reales + casos inválidos. | **YES** | NO |
| UNIT-2A | PRECHECK READ-ONLY del mapa de normalización (fid 157 + 158..185): inspeccionar fid/cmid/courseid/type/current_name/expected_name/grade_forum/grade_forum_notify/forum_grades_count/grade_items_count/grade_grades_count (+ comparación forum_grades.grade vs grade_grades.finalgrade) y clasificar cada fid (READY_TO_RENAME / ALREADY_CANONICAL / UNEXPECTED_CURRENT_NAME / BLOCKED_GRADE_INCONSISTENCY). No renombra nada. | `moodle/reportes/reporteTPporCurso.php` (acción/script READ-ONLY) | Ninguno (read-only). | Ninguno. | UNIT-1 + mapa CONFIRMADO. | Lectura incorrecta de estado; mitigar con SQL read-only y reporte por fid. | N/A (read-only, sin write). | Informe de preflight con clasificación por fid; STOP antes de UNIT-2B. | **YES** | NO |
| UNIT-2B | Renombres canónicos (fid 158..185 + 157) vía `set_coursemodule_name($cmid, $expectedname)`, solo sobre fids validados por UNIT-2A. | `moodle/reportes/reporteTPporCurso.php` (acción/script), `mdl_forum.name`. | `mdl_forum.name` cambia; NO grade_items/grade_grades directos. | `grade_item.itemname` sigue al foro (calificables); `grade_grades`/`forum_grades` intactos. | UNIT-2A CLOSED_SUCCESS + todos READY_TO_RENAME|ALREADY_CANONICAL + BLOCKED=0 + UNEXPECTED=0 + autorización + GATE_DATABASE_CHANGE. | Renombrar fid incorrecto; mitigar con confirmación + captura PRE/POST. | `set_coursemodule_name()` inverso (nombre anterior); no toca grades. | POST: releer forum.name + itemname + comparar grade_grades/forum_grades antes/después. | **YES** | **SÍ** |
| UNIT-3 | Integrar `VALID_GRADED_TP` al runtime: listado, collect_period_forums, configuración de períodos y selección segura de setupgrades (migración de consumidores). | `moodle/reportes/reporteTPporCurso.php` | Ninguno en diseño; el SETUP si se ejecuta muta (separa el SELECT del WRITE). | Ninguno hasta setupgrades. | UNIT-2B (renombres canónicos COMPLETOS; nombres ya TP-CEE-NN). | Setupgrades captura news (bug actual); asegurar exclusión de `type='news'`. | PRE_UNIT_BASELINE_CAPTURE (§13). | Verificar que el listado incluye todos los TP canónicos y excluye news/ inválidos/ fuera de período. | **YES** | NO (solo SELECT); **SÍ** si se ejecuta setupgrades |
| UNIT-4 | TP_GRADE_CELL_UI: restaurar la interfaz individual del TP (enlaces + nota editable 0..10 + Guardar + /10), SIN controles Desarrollo/Presentación y SIN rango orientativo por TP. | `moodle/reportes/reporteTPporCurso.php` | Ninguno (solo formulario; sin escritura hasta Guardar explícito). | Ninguno (solo formulario; sin escritura hasta Guardar explícito). | UNIT-1/3. | Escritura accidental al gradebook si el POST no está bien gated; mitigar con validación servidor. | PRE_UNIT_BASELINE_CAPTURE (§13). | Verificar que la celda muestra enlaces + nota editable + Guardar + /10; sin selects ni rango orientativo. | **YES** | NO |
| UNIT-5 | POST de guardado de TP (validación + `forum_gradeitem::store_grade_from_formdata()` + CSRF + no-sobrescritura, RECTIFICATION_1). | `moodle/reportes/reporteTPporCurso.php` | `forum_grades` + grade_grades (vía API oficial). | Sí: escritura de calificación de foro + sincronización Gradebook. | UNIT-4 + autorización + GATE_DATABASE_CHANGE. | Sobrescritura silenciosa; mitigar con READ_EXISTING_GRADE + confirmación. | Borrar el grade escrito (vía API) o restaurar valor previo; forum_grades + grade_grades. | Round-trip doble (forum_grades + gradebook relectura). | **YES** | **SÍ** |
| UNIT-6 | Cálculo período con aritmética entera HALF_UP + clasificación. | `moodle/reportes/reporteTPporCurso.php` | Ninguno. | Ninguno (cálculo en memoria). | UNIT-1/3. | Deriva flotante; mitigar con intdiv (sin flotante). | PRE_UNIT_BASELINE_CAPTURE (§13). | Unit tests con vectores normativos (N=0,5,8,10,20; RAW 3.49/3.50/6.49/6.50/7.49/7.50). | **YES** | NO |
| UNIT-7 | Grade_item de período + override (ensure/write/delete/read). | `moodle/reportes/reporteTPporCurso.php` | grade_items/grade_grades (vía API). | Sí: grade_item manual + overrides. | UNIT-6 + autorización + GATE_DATABASE_CHANGE. | Confundir override con calculada; mitigar con contrato DD-PERIOD-GRADEITEM-CONTRACT. | Borrar override (delete) o revertir item. | Round-trip read-back del override. | **YES** | **SÍ** |
| UNIT-8 | Verificación end-to-end (UI + round-trip) con datos sintéticos; luego con datos reales si se autoriza. | `moodle/reportes/reporteTPporCurso.php` + plan de pruebas | Según unidades probadas. | Según unidades probadas. | UNIT-1..7 + login local resuelto. | Falsos verdes por datos sintéticos; mitigar con vectores del spec. | N/A (verificación). | Plan §12. | **YES** | Según escrituras involucradas |

> **RECTIFICATION_3 (aplicada):** toda unidad que EJECUTA una modificación de código
> requiere `USER_AUTHORIZATION_REQUIRED = YES`. `GATE_DATABASE_CHANGE` se consume SOLO
> cuando la unidad modifica datos protegidos / gradebook / DB. Ejecución secuencial:
> `AUTHORIZE UNIT-N → IMPLEMENT → VERIFY → STOP → pedir autorización UNIT-N+1`.
> Una autorización de UNIT-N NO autoriza UNIT-N+1.

---

## 12. VERIFICATION_PLAN — End-to-end (futuro, no ejecutar ahora)

Acorde a `moodle-grading` (LOGIN → CURSO → FORMULARIO → … → RELECTURA).

### 12.1 Flujo TP individual

```text
LOGIN → CURSO → FORMULARIO → LECTURA (grade_get_grades, READ-ONLY)
  → NOTA EDITABLE (0..10) → GUARDADO (explicit POST + sesskey)
  → forum_gradeitem API → forum_grades → forum_update_grades()
  → GRADEBOOK → RELECTURA doble (forum_grades + grade_get_grades)
```

> **RECTIFICADO 2026-08-18:** sin pasos de CRITERIOS (selects) ni RANGO ORIENTATIVO;
> la celda TP solo tiene nota editable + Guardar + /10 (ver §4.2 y §4.5).

### 12.2 Flujo período

```text
TP VÁLIDOS → TP CONTABILIZADOS (grade>=4) → 10/N → HALF_UP
  → NOTA PERÍODO → CLASIFICACIÓN → GUARDADO (explicit) → GRADEBOOK → RELECTURA
```

### 12.3 Casos límite obligatorios

```text
N (TOTAL_VALID_TP) = 0, 5, 8, 10, 20
  N=0  → nota 0 (sin división por cero).
  N=5  → peso 2.0; N=8 → peso 1.25; N=10 → peso 1.0; N=20 → peso 0.5.

REDONDEO (RAW):
  3.49 → 3 · 3.50 → 4 · 6.49 → 6 · 6.50 → 7 · 7.49 → 7 · 7.50 → 8
  (usar la forma entera exacta intdiv; los valores 3.49/6.49/7.49 no son alcanzables
  con N≤24 pero validan la regla <0.5/≥0.5.)

TP existente con nota     → se precarga; sin guardar no cambia.
TP sin nota               → input vacío; guardar vacío = limpiar (confirmación).
foro news (fid 157)       → excluido de setupgrades, collect y cálculo.
identificador inválido    → excluido y reportado (TP-105, TP-99, TP-ABC, sin TP).
Encuentro con varios TP   → cada TP cuenta independiente (sin agrupar): p. ej. Encuentro
                            002 con 3 TP pesa 3×; Encuentro 208 con 3 TP pesa 3×.
Encuentro sin TP (04,05,10)→ 0 TP; no afecta N.
```

---

## 13. ROLLBACK_PLAN

> **RECTIFICATION_4 (aplicada):** el rollback NO usa `git checkout`/`git restore`/`git reset`.
> Existe un working tree sucio preexistente y `reporteTPporCurso.php` ya estaba modificado
> antes de este SDD. Todo rollback se hace con baseline por unidad.

```text
ROLLBACK_USES_GIT_CHECKOUT_RESTORE_RESET = NO
PREEXISTING_WORKTREE_PRESERVATION = YES

PRE_UNIT_BASELINE_CAPTURE (antes de CADA unidad de código autorizada):
  1. git status   (READ-ONLY)
  2. git diff     (READ-ONLY)
  3. checksum del archivo (p. ej. sha256sum)
  4. copia/snapshot exacto del archivo previo a ESA unidad, en ubicación
     temporal/auxiliar controlada
  5. registrar exactamente qué hunks introduce la unidad

ROLLBACK de una unidad:
  - revertir exclusivamente los cambios introducidos por ESA unidad
  - preservar todos los cambios preexistentes (nunca limpiar el working tree completo)
  - verificar checksum/diff contra PRE_UNIT_BASELINE_CAPTURE

Reversiones específicas por dominio:
  - Renombres (UNIT-2B): set_coursemodule_name() inverso (nombre anterior);
    grade_grades y forum_grades nunca se tocan (indexados por itemid).
  - Calificaciones TP (UNIT-5): borrar el grade escrito vía API oficial o restaurar
    el valor previo; re-sincronizar forum_grades + grade_grades.
  - Overrides de período (UNIT-7): reporte_tp_delete_period_grade() para quitar el
    override; la propuesta calculada no está persistida.
  - setupgrades (UNIT-3): los grade_items de foro creados se mantienen como items
    normales; restaurar grade_forum anterior si fuese necesario (misma API Moodle).

La creación de la copia/patch auxiliar forma parte de la IMPLEMENTATION autorizada de la
unidad, NO de esta ejecución DESIGN.

Prohibidos sin autorización específica:
  git reset · git restore · git clean · git switch · git checkout · git commit ·
  git push · git merge · git rebase

NUNCA: docker compose down -v · docker volume rm · docker system prune --volumes.
```

---

## 14. PRODUCTION — Deployment futuro (plan, no ejecutar)

```text
FILES_TO_MODIFY       = moodle/reportes/reporteTPporCurso.php
DATABASE_IMPACT       = mdl_forum.name (renombres) + grade_items/grade_grades (vía API)
GRADEBOOK_IMPACT      = grade_item.itemname (renombres) + calificaciones (vía API)
BACKUP_REQUIRED       = SÍ: backup de BD antes de renombres/escrituras (skill moodle-database)
RISKS                 = sobrescritura silenciosa · renombre incorrecto · datos producción
                        reales (no es entorno de prueba)
ROLLBACK              = restaurar nombres (misma API) · borrar grades escritos (API)
POST_DEPLOY_VERIFICATION = round-trip gradebook (escribir→releer) + comparación de
                           grade_grades antes/después + verificación UI

GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION
```

Producción **NO** es entorno de prueba. No conectarse a producción para modificar ni
probar. Requiere checklist completo (`moodle-production-deploy`).

```text
PRODUCTION_MOODLE_VERSION = UNKNOWN_NOT_VERIFIED
  → Antes de cualquier deployment verificar la versión real de producción y la
    compatibilidad de las APIs seleccionadas (RECTIFICATION_5). El diseño local se
    validó contra Moodle 3.9.1+; la referencia del encargo es 4.0.5.
```

---

## 15. Cierre del DESIGN

```text
DESIGN_STATUS               = COMPLETED  (tras DESIGN_RECTIFICATION_2026_08_17)
DESIGN_INTERNAL_CONSISTENCY = CONSISTENT
DESIGN_READY_FOR_AUTHORIZATION = YES
DESIGN_ARTIFACT             = docs/06-SDD/moodle-period-grading-design-2026-08-17.md
PERIOD_UI_RECTIFICATION     = COMPLETED  (2026-08-18, D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL)
TP_EVALUATION_UI_RECTIFICATION = COMPLETED  (2026-08-18, UNIT-4 = TP_GRADE_CELL_UI)
PERIOD_UI_COLUMNS           = P1_PROPOSED · P1_TEACHER_GRADE · P2_PROPOSED · P2_TEACHER_GRADE

FORUM_GRADE_SOURCE_OF_TRUTH = forum_grades  (itemnumber 1 = whole forum grade)
TP_GRADE_WRITE_API          = mod_forum\grades\forum_gradeitem::store_grade_from_formdata()
CLEAR_TP_GRADE_API          = store_grade_from_formdata(..., (object)['grade' => ''])
                              → forum_grades.grade = NULL + Gradebook sincronizado a NULL
DIRECT_GRADE_UPDATE_ONLY    = NO  (rectificado)
DIRECT_GRADE_UPDATE_ONLY_RISK = YES detectado (ver RECTIFICATION_1)

ACTIVITY_RENAME_API         = set_coursemodule_name($cmid, $newname)
RENAME_RESYNC_BEHAVIOR      = set_coursemodule_name() → grade_update_mod_grades()
                              → forum_update_grades() relee forum_grades (no es "sin regrade")
RENAME_GRADE_PRESERVATION_PLAN = captura PRE/POST de forum/grade_items/grade_grades/forum_grades
                                 (valores numéricos idénticos)

PERIOD_OVERRIDE_DELETE_REGRADING =
  force_regrading() marca needsupdate=1 en grade_item + course item (NO es no-op)

ALL_IMPLEMENTATION_UNITS_REQUIRE_USER_AUTHORIZATION = YES (UNIT-1..UNIT-8)
DATABASE_GATE_POLICY        = GATE_DATABASE_CHANGE solo cuando se modifica datos protegidos
                               / gradebook / DB

ROLLBACK_USES_GIT_CHECKOUT_RESTORE_RESET = NO
PREEXISTING_WORKTREE_PRESERVATION = YES (PRE_UNIT_BASELINE_CAPTURE)

LOCAL_MOODLE_VERSION        = Moodle 3.9.1+ (Build 20200814)
REFERENCE_MOODLE_VERSION    = Moodle 4.0.5
PRODUCTION_MOODLE_VERSION_STATUS = UNKNOWN_NOT_VERIFIED

FILES_PLANNED_FOR_IMPLEMENTATION = moodle/reportes/reporteTPporCurso.php

DATABASE_IMPACT_PLANNED =
  mdl_forum.name (renombres) · forum_grades · grade_items/grade_grades (SOLO vía API,
  tras autorización)

GRADEBOOK_IMPACT_PLANNED =
  grade_item.itemname (renombres) · calificaciones de foro y overrides de período (vía API)

IMPLEMENTATION_UNITS = UNIT-1, UNIT-2A, UNIT-2B, UNIT-3..UNIT-8 (ver §11; REORDENADO por
                       DESIGN_DEPENDENCY_RECTIFICATION_2026_08_17 + FINAL)

CANONICAL_TP_RENAME_SCOPE = fid 158..185
NON_TP_RENAME_SCOPE       = fid 157
RENAME_PRECHECK_REQUIRED  = YES
VALID_GRADED_TP_RUNTIME_INTEGRATION_REQUIRES_CANONICAL_NAMES = YES

IMPLEMENTATION = PARTIAL  (UNIT-1 CLOSED_SUCCESS)
NEXT_UNIT      = UNIT-2A  (CANONICAL_RENAME_PREFLIGHT_READ_ONLY)
NEXT_UNIT_STATUS = NOT_AUTHORIZED
UNIT_2B_STATUS = NOT_AUTHORIZED
UNIT_3_STATUS  = NOT_AUTHORIZED
TECHNICAL_BLOCKERS = BLOCKER_2 (dump local anterior a producción) ·
                     LOCAL_LOGIN_DEPENDENCY ·
                     grade_forum=0 en todos los foros (setupgrades no ejecutado)
VERIFICATION_PLAN_STATUS = DEFINED (§12)
ROLLBACK_PLAN_STATUS = DEFINED (§13)

IMPLEMENTATION            = NOT_AUTHORIZED
GATE_DATABASE_CHANGE      = AWAITING_AUTHORIZATION
GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION

ROOT_CAUSE        = N/A (fase de diseño; sin defecto nuevo)
FILES_CHANGED     = docs/06-SDD/moodle-period-grading-design-2026-08-17.md
                    docs/09-HANDOFF/CURRENT-STATE.md (estado)
DATABASE_CHANGED  = NO
DOCKER_CHANGED    = NO
VERIFICATION_RESULT = N/A (solo diseño; sin ejecución)
GIT_STATUS        = branch feature/copia-local-moodle @ 809ba63 (worktree sucio preexistente;
                    sin operaciones Git mutantes)
PRODUCTION_IMPACT = NONE
```

Detenerse al finalizar DESIGN. No iniciar IMPLEMENTATION. Esperar autorización expresa de
Alberto para cualquier futura unidad de IMPLEMENTATION.

---

## UNIT-5 — DESIGN AMENDMENT (2026-08-21)

> Enmienda de DESIGN posterior a la Specification UNIT-5 cerrada
> (`D-TP-GRADE-SAVE-NO-FULL-PAGE-RELOAD = REQUIRED`). Define técnicamente
> UNIT-5A (SAFE_TP_GRADE_BACKEND) y UNIT-5B (ASYNC_TP_GRADE_SAVE_UI).
> **DESIGN ≠ IMPLEMENTATION.** No se autoriza ni se ejecuta código.

### 0. Orden de ejecución y baseline

```text
PROTECTED_BASELINE_GRADES = 99
RECOVERY-UNIT-C           = CLOSED_SUCCESS

EXECUTION_ORDER (obligatorio, no monolítico):
  UNIT-5A → VERIFY → GIT CHECKPOINT → autorización independiente
  → UNIT-5B → VERIFY → GIT CHECKPOINT

UNIT-5A_PRECEDES_UNIT-5B = YES
  (5A es independiente y verificable sin 5B; 5B depende de 5A verificado)
```

### 1. UNIT-5A — SAFE_TP_GRADE_BACKEND

Reemplaza el path legacy de `savegrade` (hoy `grade_update()` directo — líneas 531–540
del reporte). Fuente de verdad: **`forum_grades`** (RECTIFICATION_1).

```text
OFFICIAL_API (obligatoria):
  mod_forum\grades\forum_gradeitem::load_from_context($modulecontext)
    → $gradeitem->require_user_can_grade($gradeduser, $grader)
    → $gradeitem->store_grade_from_formdata($gradeduser, $grader, (object)['grade' => $grade])

PROHIBIDO como mecanismo primario:
  SQL directo a forum_grades
  SQL directo a grade_grades
  grade_update() directo

VALIDACIONES_SERVER_SIDE (obligatorias, en orden):
  1. require_login($course)
  2. require_sesskey()
  3. courseid válido (MUST_EXIST)
  4. forumid válido (MUST_EXIST) y forum.course == courseid
  5. cmid/context correcto: get_coursemodule_from_instance('forum', forumid, courseid)
     → context_module::instance($cm->id)
  6. reporte_tp_is_valid_graded_tp($forum) == true   (TP-CEE-NN válido, type != news)
  7. is_enrolled($coursecontext, $userid)
  8. reporte_tp_role_flags(...).caneditreport + require_capability('mod/forum:grade', $modulecontext)
  9. grade_forum > 0 (foro calificable)
  10. nota: '' → clear · /^\d+$/ · 0..grade_forum
  11. UNA petición modifica exactamente una combinación student × TP
```

### 2. Concurrencia — compare-before-write (NO_SILENT_OVERWRITE)

```text
OPTIMISTIC_CONCURRENCY = YES
  La UI envía, además del valor nuevo: EXPECTED_PREVIOUS_GRADE
  El backend relee inmediatamente antes del write: CURRENT_SERVER_GRADE
  (lectura sin side-effect: $DB->get_record('forum_grades', ...) o user_has_grade();
   NO usar get_grade_for_user(), que crea registro vacío)

REGLA:
  CURRENT_SERVER_GRADE == EXPECTED_PREVIOUS_GRADE → continuar write
  si difieren → NO escribir · devolver CONFLICT · informar valor actual
                · obligar al docente a releer/decidir · nunca sobrescribir silencioso

COMPARACIÓN ROBUSTA (NULL != 0):
  representación canónica del valor:
    sin nota (forum_grades.grade NULL / ausente) → sentinel 'null'
    0                     → '0'
    1..10                 → (string)int del valor
  EXPECTED_PREVIOUS_GRADE vacío en UI == sentinel 'null'

  El valor oculto del cliente NO es fuente de verdad: solo es precondition
  comparada contra DB.

GRADE_CONFLICT_STATUS =
  HTTP 409 (conceptual) — en el entorno Moodle/PHP local se materializa como
  respuesta JSON { ok:false, error:"conflict", currentgrade, message }
```

### 3. Transacción y atomicidad 5A

```text
UNIT_5A_TRANSACTION_STRATEGY =
  PRECHECK
  → READ CURRENT SERVER GRADE
  → OPTIMISTIC CONCURRENCY CHECK
  → start_delegated_transaction()
  → store_grade_from_formdata()
  → IN_TRANSACTION READ-BACK forum_grades
  → IN_TRANSACTION READ-BACK grade_grades
  → VERIFY both == requested grade

  si mismatch o excepción:
    transaction rollback
    verify original grade preserved
    STOP
    NO COMMIT

  si ambas capas coinciden:
    allow_commit()

  → POST-COMMIT READ-BACK forum_grades
  → POST-COMMIT READ-BACK grade_grades

  si POST-COMMIT mismatch:
    logical rollback únicamente del target mediante API oficial
    → restore original grade
    → double read-back
    → report failure

IN_TRANSACTION_DATA_VERIFICATION = REQUIRED
POST_COMMIT_DATA_VERIFICATION    = REQUIRED

RATIONALE =
  evitar confirmar una inconsistencia detectable antes del commit,
  manteniendo además verificación post-commit porque Moodle grading
  puede producir efectos secundarios fuera de la atomicidad estricta
  de la transacción DB.

DATABASE_TRANSACTION_COVERS_ALL_MOODLE_SIDE_EFFECTS = NO
  → DB consistency check antes del commit
  + post-commit read-back
  + logical rollback por API si fuera necesario.

INTERACCIÓN REAL (verificada, RECTIFICATION_1 + código local):
  store_grade_from_formdata()
    → get_grade_for_user() (crea fila vacía si no existe)
    → store_grade(): check_grade_validity() → $DB->update_record('forum_grades', $grade)
      → forum_update_grades($forumrecord, $grade->userid)
  forum_update_grades() (mod/forum/lib.php:808) lee forum_grades y sincroniza:
    → forum_grade_item_update() → grade_update()  → grade_grades.finalgrade
  Orden: forum_grades se escribe PRIMERO, luego grade_grades se sincroniza.
```

### 4. Respuesta del backend (JSON)

```text
RESPONSE_FORMAT = JSON cuando la petición es async; redirect tradicional cuando no.

CONTRATO MÍNIMO:
  success:
    { "ok": true, "userid": ..., "forumid": ..., "grade": ...,
      "previousgrade": ..., "message": ... }
  error validación:
    { "ok": false, "error": "validation", "message": ... }
  conflict:
    { "ok": false, "error": "conflict", "currentgrade": ..., "message": ... }

NO INCLUIR: stack traces · SQL · secrets · información de otros alumnos.

DISTINCIÓN HTML vs ASYNC:
  la petición async lleva async=1 (campo de formulario) y/o header Accept: application/json.
  backend: optional_param('async', 0, PARAM_INT) → si async: emitir JSON y terminar;
  si no: mantener el redirect() actual (fallback progressive enhancement).
```

### 5. Compatibilidad / fallback

```text
PROGRESSIVE_ENHANCEMENT = YES
  UNIT-5A funciona correctamente ANTES de UNIT-5B:
    - el botón Guardar actual puede seguir provocando reload temporalmente durante 5A;
    - pero el backend YA escribe mediante API oficial (seguro).
  UNIT-5B luego intercepta el guardado para evitar reload.

NO_DOS_ALGORITMOS = YES
  HTML POST tradicional y async reutilizan el MISMO backend seguro (5A).
  La única diferencia es el formato de respuesta (redirect vs JSON), no la ruta de escritura.
```

### 6. UNIT-5B — ASYNC_TP_GRADE_SAVE_UI

```text
ALTERNATIVA ELEGIDA = A — interceptar el submit del <form> de celda (.grade-form)
JUSTIFICACIÓN:
  - reutiliza el formulario nativo existente (funciona sin JS → fallback a reload);
  - menor cambio posible (no se reestructura el HTML de la celda);
  - reutiliza el backend 5A (mismo endpoint action=savegrade);
  - vanilla JS sin framework (el reporte actual NO tiene infraestructura JS/AMD);
  - progressive enhancement: si JS falla, el submit nativo sigue funcionando.

FLUJO:
  docente edita nota → Guardar explícito (NO autosave)
  → estado local "Guardando…"
  → fetch() async POST (FormData del form + async=1 + header Accept: application/json)
  → backend 5A → JSON
  → éxito / conflicto / error
  → actualizar exclusivamente ESA celda (no la página)
  → NO full-page reload
```

### 7. Estados de celda

```text
CELL_STATES = IDLE · SAVING · SAVED · ERROR · CONFLICT

IDLE      → [ Guardar ]
SAVING    → Guardando…
SAVED     → ✓ Guardado
ERROR     → Error al guardar
CONFLICT  → La nota cambió desde que cargaste la página. No se guardó.

FALSE_SUCCESS_MESSAGE = IMPOSSIBLE_BY_DESIGN
  (SAVED solo tras respuesta ok:true del backend, nunca ante error/conflicto)

CONFLICT:
  NO modificar automáticamente el input con el valor del servidor;
  mostrar el valor actual (currentgrade) como información para que el docente decida.
```

### 8. Scroll y foco

```text
AUTO_ADVANCE_TO_NEXT_GRADE = NO
SCROLL_POSITION            = UNCHANGED
FOCUS_AFTER_SUCCESS        = SAME_CELL_OPERATIONAL_CONTEXT
FOCUS_AFTER_ERROR          = GRADE_INPUT_SAME_CELL
FOCUS_AFTER_CONFLICT       = GRADE_INPUT_SAME_CELL

  - NO mover automáticamente al siguiente estudiante/TP;
  - NO scroll programático;
  - posición de página intacta;
  - tras respuesta, conservar/restaurar el foco al control operativo de la misma celda;
  - sin navegación automática en UNIT-5B.

JUSTIFICACIÓN: comportamiento conservador, evita sorpresas; la Specification solo exige
PRESERVE_OPERATIONAL_CONTEXT (EXACT_FOCUS_BEHAVIOR = TO_BE_DECIDED_IN_DESIGN → aquí se
decide el mínimo conservador, sin auto-avance).
```

### 9. Sincronización del valor previo

```text
Tras un SAVE exitoso, el cliente actualiza su EXPECTED_PREVIOUS_GRADE al valor
confirmado por el backend (evita falsos conflictos en ediciones sucesivas sin reload).

Ejemplo:
  página carga con 7 → guarda 8 → backend confirma 8
  → expected_previous pasa a 8 → luego guarda 9 comparando contra 8.

MECANISMO MÍNIMO PROPUESTO:
  atributo data-expected-grade en el <input type="number"> de la celda
  ('' = sin nota). El JS lo lee al submit y lo actualiza tras éxito.
  (alternativa equivalente: hidden input por celda; se decide en IMPLEMENTATION.)
```

### 10. Protección de las otras 98/99 notas

```text
INVARIANTS:
  TARGET_CELL_CHANGED    = 1 máximo
  UNRELATED_GRADES_CHANGED = 0

PRUEBA CONTROLADA (UNA nota existente):
  PRE_TEST:
    - seleccionar una combinación student × TP explícita (en PRECHECK de implementación,
      no ahora);
    - capturar forum_grades.grade y grade_grades.finalgrade del target;
    - capturar checksum/matriz de las otras 98 notas en scope;
    - registrar valor original.
  TEST: original → temporary_test_value (vía API oficial)
  VERIFY: source request → forum_grades → grade_grades → Gradebook → reporte/relectura
  ROLLBACK TEST: temporary_test_value → original (vía API oficial)
  FINAL VERIFY:
    - target vuelve al original;
    - otras 98 = idénticas;
    - 99 notas preservadas;
    - period grades unchanged.
```

### 11. Test de conflicto (NO_SILENT_OVERWRITE)

```text
1. leer grade original X;
2. simular request con EXPECTED_PREVIOUS_GRADE distinto de X (precondition falsa);
3. backend debe responder CONFLICT;
4. DB permanece exactamente X;
5. Gradebook permanece X.

Esta prueba NO requiere escribir otra nota: el conflicto se induce con precondition falsa.
```

### 12. Gates

```text
GATE_DATABASE_CHANGE_UNIT_5A = REQUIRED
  (5A modifica calificaciones durante su test controlado).
  Antes de implementar 5A debe existir: precheck · backup/snapshot de los 99 grades ·
  selección explícita del test target · rollback definido · autorización expresa.

GATE_DATABASE_CHANGE_UNIT_5B = NOT_REQUIRED_FOR_CODE_IMPLEMENTATION
  (5B solo modifica código/UI y reutiliza 5A ya verificado).
  PERO la verificación E2E de 5B que guarde una nota real requerirá protección
  equivalente o reutilizar un test controlado expresamente autorizado.
```

### 13. Archivos / arquitectura

```text
UNIT_5A_PROPOSED_FILES = moodle/reportes/reporteTPporCurso.php
UNIT_5B_PROPOSED_FILES = moodle/reportes/reporteTPporCurso.php

JUSTIFICACIÓN (un único archivo, sin endpoint nuevo):
  - el reporte es un script custom autocontenido; el handler savegrade YA vive allí
    y YA posee require_login()/sesskey()/contexto correctos;
  - un endpoint separado duplicaría bootstrap de login/sesskey/permisos (riesgo + mantenimiento);
  - 5A = modificar el handler savegrade (API oficial + concurrency + JSON branch);
  - 5B = añadir bloque <script> vanilla JS + atributo data-expected-grade en la celda;
  - sin crear archivos en esta fase (se decide en IMPLEMENTATION si se externaliza el JS).
```

### 14. Compatibilidad Moodle (3.9.1+ local · 4.0.5 referencia)

```text
APIs VERIFICADAS en runtime local 3.9.1+ (ya documentadas en RECTIFICATION_1/5):
  mod_forum\grades\forum_gradeitem (load_from_context, store_grade_from_formdata, user_has_grade)
  core_grades\component_gradeitem · forum_update_grades() · forum_grade_item_update()
  grade_get_grades() · $DB->get_record('forum_grades', ...)

JS:
  fetch() + FormData + addEventListener + data-* attributes — estándar web, sin dependencia
  de versión de Moodle; funciona en el theme de 3.9 y en 4.0.5.

JSON:
  json_encode() (PHP core) — disponible en ambos.

No se introducen APIs ausentes en 3.9.1+.
```

### 15. Rollback

```text
UNIT-5A code rollback:
  volver al código PRE-5A mediante baseline específico por unidad (PRE_UNIT_BASELINE_CAPTURE);
  NO git restore/reset/checkout automático (worktree histórico protegido).

UNIT-5A data rollback:
  restaurar ÚNICAMENTE el test target mediante API oficial (store_grade_from_formdata).
  No restaurar dump completo salvo fallo extraordinario + nueva autorización.

UNIT-5B rollback:
  volver al código PRE-5B; el backend 5A permanece funcional e independiente.
```

### 16. Criterios de aceptación de DESIGN

```text
UNIT-5A:
  OFFICIAL_API_WRITE           = PASS
  FORUM_GRADE_MATCH            = PASS
  GRADEBOOK_MATCH              = PASS
  NO_SILENT_OVERWRITE          = PASS
  CONFLICT_PROTECTION          = PASS
  UNRELATED_98_GRADES_UNCHANGED = PASS
  TARGET_RESTORED_TO_ORIGINAL  = PASS
  PERIOD_GRADES_CHANGED        = NO
  FID_OUT_OF_SCOPE_CHANGED     = NO

  IN_TRANSACTION_FORUM_GRADE_MATCH = PASS
  IN_TRANSACTION_GRADEBOOK_MATCH   = PASS
  IN_TRANSACTION_MISMATCH_COUNT    = 0
  COMMIT_ALLOWED_ONLY_IF_IN_TRANSACTION_MATCH = YES
  POST_COMMIT_FORUM_GRADE_MATCH    = PASS
  POST_COMMIT_GRADEBOOK_MATCH      = PASS
  PARTIAL_COMMIT_ON_DETECTED_PRECOMMIT_MISMATCH = NO

UNIT-5B:
  FULL_PAGE_RELOAD             = NO
  EXPLICIT_SAVE                = YES
  AUTOSAVE                     = NO
  SCROLL_POSITION_PRESERVED    = YES
  OPERATIONAL_FOCUS_PRESERVED  = YES
  SUCCESS_FEEDBACK             = PASS
  ERROR_RETAINS_INPUT          = YES
  CONFLICT_RETAINS_INPUT       = YES
  FALSE_SUCCESS_MESSAGE        = NO
  BACKEND_5A_REUSED            = YES
```

### 17. Estado final del design

```text
UNIT_5_DESIGN_AMENDMENT = COMPLETED
UNIT-5A_DESIGN          = COMPLETED
UNIT-5B_DESIGN          = COMPLETED

UNIT-5A_READY_FOR_AUTHORIZATION = YES
UNIT-5B_DEPENDS_ON_UNIT-5A_VERIFIED = YES

UNIT-5A_IMPLEMENTATION = NOT_AUTHORIZED
UNIT-5B_IMPLEMENTATION = NOT_AUTHORIZED

NEXT_ACTION = GIT CHECKPOINT OF DESIGN · luego authorization review for UNIT-5A

DATABASE_CHANGED  = NO
DOCKER_CHANGED    = NO
PRODUCTION_IMPACT = NONE
```
