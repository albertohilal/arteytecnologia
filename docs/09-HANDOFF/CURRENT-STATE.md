# Current State

```text
PROJECT              = arteytecnologia.com.ar (Moodle local)
SDD                  = SDD_AGENTS_SKILLS_GOVERNANCE
PHASE                = CLOSURE
UNIT                 = governance-framework
GATE                 = GATE_AGENTS_SKILLS_IMPLEMENTATION = CONSUMED
SDD_STATUS           = COMPLETED
VERIFICATION         = CLOSED_SUCCESS
BRANCH               = feature/copia-local-moodle
HEAD                 = 809ba63e2334528901b44163e98ea8cd9a2b0afb
WORKTREE             = DIRTY (pre-existing): modified moodle/reportes/reporteTPporCurso.php;
                       untracked .atl/, AUXILIAR/
LAST_VERIFIED_STATE  = 2026-08-17
NEXT_ACTION          = none — governance framework verified and closed (2026-08-17)
NOT_AUTHORIZED       = (none pending for governance framework; GATE consumed)
RISKS                = native skill loader restart resolved (5 skills visible, scope=project);
                       security evidence still temporary (unrelated to this SDD)
```

## Governance framework — verification (closed 2026-08-17)

```text
PROJECT_AGENTS_LOADED        = YES (AGENTS.md loaded; Moodle project rules active)
NATIVE_SKILLS_DISCOVERED     = YES (5 moodle-* skills in available_skills)
SKILL_REGISTRY_RESULT        = PASS (6 entries: judgment-day/user + 5 moodle/project)
SKILL_SCOPE                  = project (all 5 moodle skills)
SKILL_RESOLUTION             = .opencode/skills/<skill>/SKILL.md (project-local, not global copy)
DOCUMENTATION_RULES          = PRESENT (docs/00-INDEX/DOCUMENTATION-RULES.md)
CURRENT_STATE                = PRESENT (docs/09-HANDOFF/CURRENT-STATE.md)
PREEXISTING_WORKTREE_PRESERVED = YES (moodle/reportes/reporteTPporCurso.php still modified, not touched)
SECRET_AUDIT_RESULT          = PASS (12 pattern hits, all policy text; no real credentials)
DATABASE_CHANGED             = NO
DOCKER_CHANGED               = NO
PRODUCTION_IMPACT            = NONE
GIT_STATUS                   = branch feature/copia-local-moodle @ 809ba63; no commit/push performed
VERIFICATION_RESULT          = PASS
```

## Active SDD Work

```text
SDD_MOODLE_PERIOD_GRADING      = IN_PROGRESS
PHASE                          = IMPLEMENTATION
SPECIFICATION_STATUS           = COMPLETED
SPECIFICATION_READY_FOR_DESIGN = YES
USER_DECISIONS_STILL_REQUIRED_COUNT = 0
DESIGN                         = COMPLETED
DESIGN_STATUS                  = COMPLETED
DESIGN_READY_FOR_AUTHORIZATION = YES
DESIGN_INTERNAL_CONSISTENCY    = CONSISTENT

IMPLEMENTATION                 = PARTIAL
UNIT-1                         = CLOSED_SUCCESS
UNIT-2A                        = CLOSED_SUCCESS
UNIT-2B                        = CLOSED_SUCCESS
UNIT-3                         = CLOSED_SUCCESS
NEXT_UNIT                      = UNIT-5
UNIT-4                         = IMPLEMENTED_REJECTED_BY_USER_VERIFICATION  (histórico)
UNIT-4_CLOSED_SUCCESS          = NO   (intento original rechazado)
UNIT-4_RECTIFICATION_IMPLEMENTATION = COMPLETED
CURRENT_UNIT_4_CODE            = RESTORED_TO_PRE_UNIT4_BASELINE
UNIT-5                         = NOT_AUTHORIZED

DESIGN_DEPENDENCY_RECTIFICATION = COMPLETED  (2026-08-17)
  DEPENDENCY_DEFECT = VALID_GRADED_TP_RUNTIME_INTEGRATION_BEFORE_CANONICAL_RENAME
  (los nombres legacy fid 158..185 no matchean TP-CEE-NN; integrar antes de
   renombrar excluiría esos TP del reporte → regresión)
  CANONICAL_TP_RENAME_SCOPE = fid 158..185 · NON_TP_RENAME_SCOPE = fid 157
  RENAME_PRECHECK_REQUIRED = YES
  VALID_GRADED_TP_RUNTIME_INTEGRATION_REQUIRES_CANONICAL_NAMES = YES

DESIGN_DEPENDENCY_RECTIFICATION_FINAL = COMPLETED  (2026-08-17)
  UNIT-2 (PRECHECK + renombres indivisible) → SUPERSEDED
  UNIT-2A = CANONICAL_RENAME_PREFLIGHT_READ_ONLY  (READ-ONLY, sin gate)
  UNIT-2B = CANONICAL_RENAME_WRITE                (authorization + GATE_DATABASE_CHANGE)
  UNIT-3  = VALID_GRADED_TP_RUNTIME_INTEGRATION   (listado/collect/períodos/setupgrades)

GATE_DATABASE_CHANGE           = AWAITING_AUTHORIZATION
GATE_DATABASE_CHANGE_UNIT_2B   = CONSUMED  (canonical renames; histórico)
GATE_PRODUCTION_DEPLOYMENT     = AWAITING_AUTHORIZATION

SDD_MOODLE_SECURITY_REMEDIATION = SUSPENDED
PHASE                      = DISCOVERY_COMPLETED
SECURITY_EVIDENCE          = TEMPORARILY_PRESERVED
SECURITY_EVIDENCE_DURABLE_COPY = PENDING
```

## SDD_MOODLE_PERIOD_GRADING — UNIT-1 (CLOSED_SUCCESS, 2026-08-17)

```text
UNIT            = UNIT-1
UNIT_NAME       = Parser TP-CEE-NN + clasificación VALID_GRADED_TP
UNIT_STATUS     = CLOSED_SUCCESS

UNIT_1_RECTIFICATION      = CLOSED_SUCCESS  (2026-08-17)
UNIT_1_RECTIFICATION_DEFECT  = TP_ORDINAL_ZERO_ACCEPTED
UNIT_1_RECTIFICATION_ROOT_CAUSE = missing semantic validation of canonical TP ordinal
CANONICAL_NN_RANGE        = 01..99
NN_ZERO_REJECTED          = YES
NN_ZERO_ERROR             = invalid_format

IMPLEMENTED =
  parse_tp_identifier(string $name): array
  reporte_tp_is_valid_graded_tp(object $forum): bool

NOT_INTEGRATED = YES (funciones puras; consumidores migran en UNIT-3, la unidad de
  integración runtime posterior a los renombres canónicos)
STALE_CODE_COMMENT =
  TO_BE_CORRECTED_WITH_NEXT_AUTHORIZED_CODE_UNIT
  (comentario en moodle/reportes/reporteTPporCurso.php línea ~100:
   "La migración de consumidores corresponde a UNIT-2 y posteriores."
   debe decir UNIT-3; NO se corrige en ejecución documental)
REPORT_RUNTIME_BEHAVIOR_CHANGE = NO_INTENTIONAL_CHANGE

PRE_UNIT_CHECKSUM  = 860ec6942f261ba6ed8fa8c2eeacf944e94e85f2b9fee619361f7284dbc5c15f
POST_UNIT_CHECKSUM = 5c08ad3d377e946243279926746da5e66e4cc84d5e3be1803a66d2f1a3e12b50
  (tras rectificación NN=00; UNIT-1 original +109 líneas, rectificación +18 líneas)
PREEXISTING_DIFF_PRESERVED = YES (459 inserciones intactas; 0 líneas eliminadas)

VERIFICATION_RESULT = PASS (php -l OK; 12 válidos + 15 inválidos + 4 VALID_GRADED_TP)
DATABASE_CHANGED    = NO
GRADEBOOK_CHANGED   = NO
DOCKER_CHANGED      = NO
MOODLEDATA_CHANGED  = NO
PRODUCTION_IMPACT   = NONE
```

## SDD_MOODLE_PERIOD_GRADING — UNIT-2A / UNIT-2B (CLOSED_SUCCESS, 2026-08-17)

```text
UNIT-2A = CANONICAL_RENAME_PREFLIGHT_READ_ONLY → CLOSED_SUCCESS (2026-08-17)
UNIT-2B = CANONICAL_RENAME_WRITE               → CLOSED_SUCCESS (2026-08-17)

UNIT_2A_RESULT =
  TOTAL_FIDS_INSPECTED             = 29
  READY_TO_RENAME_COUNT            = 29
  ALREADY_CANONICAL_COUNT          = 0
  UNEXPECTED_CURRENT_NAME_COUNT    = 0
  BLOCKED_GRADE_INCONSISTENCY_COUNT = 0
  UNIT_2B_PREFLIGHT_STATUS         = READY_FOR_AUTHORIZATION

UNIT_2B_RESULT =
  PRE_WRITE_STATE_DRIFT            = NO
  TARGET_NAME_COLLISION_COUNT      = 0
  TOTAL_FIDS_TARGETED              = 29
  RENAMED_COUNT                    = 29
  ALREADY_CANONICAL_COUNT          = 0
  FAILED_COUNT                     = 0
  ROLLBACK_REQUIRED                = NO
  ROLLBACK_RESULT                  = NOT_REQUIRED

CANONICAL_RENAME_EXECUTED_LOCAL = YES
CANONICAL_TP_RENAME_SCOPE       = fid 158..185
NON_TP_RENAME_SCOPE             = fid 157

RENAME_API = set_coursemodule_name($cmid, $expected_name)  (moodle/course/lib.php:939)

POST_NAMES_VERIFICATION             = PASS (29/29)
POST_CMID_VERIFICATION              = PASS
POST_TYPE_VERIFICATION              = PASS
POST_GRADE_FORUM_VERIFICATION       = PASS (grade_forum=0 en los 29, inalterado)
POST_GRADE_CONSISTENCY_VERIFICATION = PASS (forum_grades=0, grade_items=0, grade_grades=0)

PARSER_POST_RENAME_RESULT            = PASS (28 TP válidos; fid 157 news → invalid_format)
VALID_GRADED_TP_POST_RENAME_RESULT   = PASS (28 TP true; fid 157 false)

DATABASE_CHANGED    = YES (mdl_forum.name fid 157..185; sin otros cambios)
GRADEBOOK_CHANGED   = NO (sin grade_items/grade_grades nuevos)
CODE_CHANGED        = NO
DOCUMENTATION_CHANGED = YES (CURRENT-STATE.md)
DOCKER_CHANGED      = NO
MOODLEDATA_CHANGED  = NO
PRODUCTION_IMPACT   = NONE

GATE_DATABASE_CHANGE_UNIT_2B = CONSUMED (no reutilizar en UNIT-3)
GATE_PRODUCTION_DEPLOYMENT   = AWAITING_AUTHORIZATION

NEXT_UNIT       = UNIT-3  (VALID_GRADED_TP_RUNTIME_INTEGRATION)
UNIT_3_STATUS   = NOT_AUTHORIZED
```

## SDD_MOODLE_PERIOD_GRADING — UNIT-3 (CLOSED_SUCCESS, 2026-08-18)

```text
UNIT            = UNIT-3
UNIT_NAME       = VALID_GRADED_TP_RUNTIME_INTEGRATION
UNIT_STATUS     = CLOSED_SUCCESS

IMPLEMENTED =
  reporte_tp_get_period_of_forum(object $forum): ?int   (nueva)
  reporte_tp_collect_period_forums(array $forums, int $period): array   (migrada)
  listado principal filtrado por reporte_tp_is_valid_graded_tp()
  selección de setupgrades filtrada por reporte_tp_is_valid_graded_tp()
  período derivado del parser canónico (no de rangos numéricos)

REMOVED =
  $period_config / $period_config_default
  reporte_tp_get_period_config() · reporte_tp_get_period_range()
  reporte_tp_parse_tp_number()   (todos verificados obsoletos)

CONTRACT_PRESERVED =
  parse_tp_identifier(string $name): array             (sin cambios)
  reporte_tp_is_valid_graded_tp(object $forum): bool   (sin cambios)

VERIFICATION_RESULT = PASS
  PHP_LINT                          = PASS
  TOTAL_VALID_GRADED_TP             = 28
  PERIOD_1_VALID_TP                 = 9
  PERIOD_2_VALID_TP                 = 19
  NEWS_EXCLUDED                     = YES  (fid 157 type=news)
  INVALID_IDENTIFIERS_EXCLUDED      = YES  (parser canónico)
  LEGACY_PERIOD_RUNTIME_DEPENDENCY  = REMOVED
  COLLECT_GATE grade_forum===10.0   = PRESERVED  (P1=0, P2=0 pre-setupgrades)

SETUPGRADES_SELECTION_SAFE = YES
SETUPGRADES_EXECUTED       = NO
DATABASE_CHANGED           = NO   (forum.name/grade_forum, forum_grades,
                                  grade_items, grade_grades invariantes)
GRADEBOOK_CHANGED          = NO
MOODLEDATA_CHANGED         = NO
DOCKER_CHANGED             = NO
PRODUCTION_IMPACT          = NONE

PRE_UNIT_CHECKSUM  = 5c08ad3d377e946243279926746da5e66e4cc84d5e3be1803a66d2f1a3e12b50
POST_UNIT_CHECKSUM = 2b2304802171042e65044bbc23f8c9b436318ee494aabd6c3c7be4c9cfc78648
PREEXISTING_DIFF_PRESERVED = YES

NEXT_UNIT     = UNIT-4  (UI criterios Desarrollo/Presentación)
UNIT_4_STATUS = NOT_AUTHORIZED
```

## HISTORICAL_HANDOFF_NOTICE (added 2026-08-18)

```text
HISTORICAL_HANDOFF_NOTICE =

Los bloques "specification handoff" y "design handoff" preservan el estado
existente al cierre de esas fases y constituyen evidencia histórica.

No representan el estado operativo actual de IMPLEMENTATION.

CURRENT_OPERATIONAL_STATE_SOURCE =
  "Active SDD Work"
  + sección más reciente de cada IMPLEMENTATION UNIT.

CURRENT_IMPLEMENTATION_STATUS =
  UNIT-1  CLOSED_SUCCESS
  UNIT-2A CLOSED_SUCCESS
  UNIT-2B CLOSED_SUCCESS
  UNIT-3  CLOSED_SUCCESS

CURRENT_NEXT_UNIT = UNIT-4
CURRENT_UNIT_4_STATUS = NOT_AUTHORIZED
```

## SDD_MOODLE_PERIOD_GRADING — PERIOD_UI_RECTIFICATION (COMPLETED, 2026-08-18)

```text
PERIOD_UI_RECTIFICATION = COMPLETED

D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL = CONFIRMED

PERIOD_UI =
  C1_PROPOSED
  C1_TEACHER_GRADE
  C2_PROPOSED
  C2_TEACHER_GRADE

PROPOSED_GRADE =
  READ_ONLY
  NOT_PERSISTED

TEACHER_GRADE =
  EDITABLE
  PERSISTED_ONLY_ON_EXPLICIT_SAVE

IMPLEMENTATION_OF_PERIOD_UI_RECTIFICATION = NOT_STARTED

AFFECTED_FUTURE_UNIT = UNIT-7
```

Estado operativo vigente:

```text
UNIT-1  = CLOSED_SUCCESS
UNIT-2A = CLOSED_SUCCESS
UNIT-2B = CLOSED_SUCCESS
UNIT-3  = CLOSED_SUCCESS

UNIT-4    = IMPLEMENTED_REJECTED_BY_USER_VERIFICATION  (histórico)
UNIT-4_RECTIFICATION_IMPLEMENTATION = COMPLETED
CURRENT_UNIT_4_CODE = RESTORED_TO_PRE_UNIT4_BASELINE

NEXT_UNIT = UNIT-5
UNIT-5    = NOT_AUTHORIZED
```

UNIT-4 rectificación de IMPLEMENTATION COMPLETADA (2026-08-18): selects
Desarrollo/Presentación + rango orientativo eliminados; celda TP restaurada a
enlaces + nota editable 0..10 + Guardar + /10 (TP_GRADE_CELL_UI). Código
byte-idéntico al baseline pre-UNIT4. No se inicia UNIT-5 ni UNIT-7.

## SDD_MOODLE_PERIOD_GRADING — UNIT-4 (SUPERSEDED → IMPLEMENTED_REJECTED_BY_USER_VERIFICATION, 2026-08-18)

> **SUPERSEDED_BY_USER_VERIFICATION (2026-08-18).** Esta sección documenta la
> implementación de UNIT-4 que Alberto RECHAZÓ en verificación visual (selects
> Desarrollo/Presentación + rango orientativo por celda TP). Se conserva como
> evidencia histórica. El estado vigente está en
> `UNIT-4_SPEC_DESIGN_RECTIFICATION` (más abajo).

```text
UNIT            = UNIT-4
UNIT_NAME       = UI_CRITERIOS_DESARROLLO_PRESENTACION   (SUPERSEDED)
UNIT_STATUS     = IMPLEMENTED_REJECTED_BY_USER_VERIFICATION
UNIT-4_CLOSED_SUCCESS = NO
CURRENT_UNIT_4_CODE   = CONTAINS_REJECTED_CRITERIA_UI
```

Autorizada por Alberto. Implementada. Verificación visual de Alberto: RECHAZADA
(la interfaz de selects Desarrollo/Presentación + rango orientativo NO representa
el requerimiento real).

IMPLEMENTED =
  .tp-evaluation por celda TP, SOLO docente ($cangradeforum = caneditreport
    + has_capability('mod/forum:grade', modulecontext))
  select Desarrollo   → Adecuado / No adecuado
  select Presentación → Completa / Incompleta
  Rango orientativo (cálculo 100% cliente; JS vanilla, sin librerías externas)
  reporte_tp_update_range(selectEl) — recalcula SOLO la celda propia

RANGE_MAPPING (orientativo, cliente):
  adequate   + complete   → 9–10
  adequate   + incomplete → 7–8
  not_adequate + complete   → 5–6
  not_adequate + incomplete → 4–5
  selección incompleta (0 o 1 criterio) → —

PEDAGOGICAL_RULE (no reabrir):
  RANGES_ARE_ORIENTATIVE = YES
  AUTOMATIC_FINAL_TP_GRADE = NO
  GRADE_INPUT_AUTOFILL = NO
  TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES

CONTRACTS_PRESERVED =
  CRITERIA_DEFAULT_SELECTION = NONE
  CRITERIA_PERSISTED         = NO  (selects sin atributo name)
  CRITERIA_AUTOMATIC_POST    = NO
  SAVEGRADE_BACKEND_CHANGED  = NO  (savegrade/setupgrades/saveperiodgrade intactos)
  STUDENT_EDIT_CONTROLS      = NO  (gate server-side por capability)
  PERIOD_UI_UNTOUCHED        = YES (Cuatrimestre 1/2 + #periodgrade-form)

VERIFICATION_RESULT = PASS (estática)
  PHP_LINT          = PASS
  ISOLATED_DIFF     = +95 / -0  (puramente aditivo UI)
  REVIEW_LENS       = review-risk → ledger vacío (sin BLOCKER/CRITICAL/WARNING)
  DATABASE_CHANGED  = NO  (forum.grade_forum=0 en 174 TP; forum_grades=0;
                          forum grade_items=0; invariantes READ-ONLY verificadas)
  GRADEBOOK_CHANGED = NO
  MOODLEDATA_CHANGED = NO
  DOCKER_CHANGED    = NO
  PRODUCTION_IMPACT = NONE

PRE_UNIT_CHECKSUM  = 2b2304802171042e65044bbc23f8c9b436318ee494aabd6c3c7be4c9cfc78648
POST_UNIT_CHECKSUM = d93c0f04a9cfa946b07a70b80aadd5c77ff6b93fad6dfe14b9be257c5a761983
PREEXISTING_DIFF_PRESERVED = YES
BASELINE_BACKUP    = AUXILIAR/UNIT4-BASELINE/reporteTPporCurso.php.pre-unit4

UI_E2E_VERIFICATION = AWAITING_USER_VERIFICATION
  (Alberto: login → courseid=15 → reporteTPporCurso → seleccionar Desarrollo
   y Presentación → observar rango → cambiar una opción → recargar y confirmar
   no persistencia. SIN pulsar "Guardar" / "Habilitar calificaciones" /
   "Guardar notas del período".)

NEXT_UNIT = UNIT-4_UI_VERIFICATION
UNIT-5    = NOT_AUTHORIZED
```

## SDD_MOODLE_PERIOD_GRADING — UNIT-4_SPEC_DESIGN_RECTIFICATION (COMPLETED, 2026-08-18)

```text
UNIT_4_SPEC_DESIGN_RECTIFICATION = COMPLETED

UNIT-4                          = IMPLEMENTED_REJECTED_BY_USER_VERIFICATION
UNIT-4_CLOSED_SUCCESS           = NO
CURRENT_UNIT_4_CODE             = CONTAINS_REJECTED_CRITERIA_UI

ROOT_CAUSE =
  La SPECIFICATION/DESIGN convirtió criterios pedagógicos utilizados por el
  docente para evaluar un TP en controles obligatorios de interfaz.
  Eso NO representa el requerimiento real.

RECTIFIED_DECISIONS =
  D-TP-EVALUATION-CRITERIA-PEDAGOGICAL-ONLY = CONFIRMED
  D-TP-UI-NO-CRITERIA-CONTROLS = CONFIRMED

  DEVELOPMENT_UI_CONTROL      = NO
  PRESENTATION_UI_CONTROL     = NO
  DEVELOPMENT_PERSISTED       = NO
  PRESENTATION_PERSISTED      = NO
  TP_ORIENTATIVE_RANGE_CONTROL  = NO
  TP_ORIENTATIVE_RANGE_PERSISTED = NO

  TP_EVALUATION_RANGES =
    ADECUADO    + COMPLETA   → 9–10
    ADECUADO    + INCOMPLETA → 7–8
    NO_ADECUADO + COMPLETA   → 5–6
    NO_ADECUADO + INCOMPLETA → 4

  LAST_BAND_4_ONLY = YES
  AUTOMATIC_FINAL_TP_GRADE = NO
  TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES

UNIT-4 = TP_GRADE_CELL_UI
  (enlaces + nota editable 0..10 + Guardar + /10;
   sin controles Desarrollo/Presentación ni rango orientativo por TP)

UNIT-4_DATABASE_IMPACT  = NONE
UNIT-4_GRADEBOOK_IMPACT = NONE  (hasta guardado explícito)

NEXT_UNIT = UNIT-4_RECTIFICATION_IMPLEMENTATION
UNIT-4_RECTIFICATION_IMPLEMENTATION = NOT_AUTHORIZED
UNIT-5    = NOT_AUTHORIZED

PRE_UNIT_4_BASELINE =
  AUXILIAR/UNIT4-BASELINE/reporteTPporCurso.php.pre-unit4
PRE_UNIT_4_CHECKSUM =
  2b2304802171042e65044bbc23f8c9b436318ee494aabd6c3c7be4c9cfc78648
  (permite futura rectificación precisa del código; NO usar para rollback ahora)

DATABASE_CHANGED   = NO
GRADEBOOK_CHANGED  = NO
MOODLEDATA_CHANGED = NO
DOCKER_CHANGED     = NO
PRODUCTION_IMPACT  = NONE

GATE_DATABASE_CHANGE      = AWAITING_AUTHORIZATION
GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION

VERIFICATION_RESULT = PASS (documental)
  PERIOD_MODEL_CHANGED            = NO
  PERIOD_FORMULA_CHANGED          = NO
  TP_WEIGHT_CHANGED               = NO
  GRADE_GE_4_RULE_CHANGED         = NO
  ROUNDING_CHANGED                = NO
  ACADEMIC_CLASSIFICATION_CHANGED = NO
  PERIOD_UI_MODEL_CHANGED         = NO
  ACTIVE_TP_UI_HAS_DEVELOPMENT_SELECT  = NO
  ACTIVE_TP_UI_HAS_PRESENTATION_SELECT = NO
  ACTIVE_TP_UI_HAS_ORIENTATIVE_RANGE   = NO
  ACTIVE_TP_UI_HAS_GRADE_INPUT         = YES
  LAST_BAND_4_ONLY = YES
```

WAIT_FOR_USER_AUTHORIZATION — la rectificación de IMPLEMENTATION NO está autorizada.

## SDD_MOODLE_PERIOD_GRADING — UNIT-4_RECTIFICATION_IMPLEMENTATION (COMPLETED, 2026-08-18)

```text
UNIT-4_RECTIFICATION_IMPLEMENTATION = COMPLETED

ROOT_CAUSE_RECTIFIED =
  La UI de UNIT-4 (selects Desarrollo/Presentación + rango orientativo) fue
  eliminada; la celda TP se restauró a TP_GRADE_CELL_UI.

FILES_CHANGED = moodle/reportes/reporteTPporCurso.php
  (eliminados 3 bloques puramente aditivos de UNIT-4: CSS .tp-evaluation,
   HTML selects + rango, JS reporte_tp_update_range; 95 líneas)

RESULT =
  archivo byte-idéntico al baseline pre-UNIT4
  AUXILIAR/UNIT4-BASELINE/reporteTPporCurso.php.pre-unit4
  sha256 = 2b2304802171042e65044bbc23f8c9b436318ee494aabd6c3c7be4c9cfc78648

UI_ACTIVA =
  enlaces del TP + nota editable 0..10 + botón Guardar + /10
  (sin selects Desarrollo/Presentación ni rango orientativo por TP)

BACKEND_INTACTO =
  acciones savegrade / setupgrades / saveperiodgrade sin cambios
  (grade_item::update_final_grade · force_regrading · forum_gradeitem intactos)

VERIFICATION =
  PHP_LINT           = PASS (No syntax errors detected, PHP 7.4.33 container)
  PAGE_RENDER        = PASS (Moodle HTTP 200; endpoint 303 → login, sin fatal)
  DIFF_VS_PRE_UNIT4  = IDENTICAL (byte-idéntico)
  RESIDUAL_REFERENCES = NONE (tp-evaluation / reporte_tp_update_range ausentes)

DATABASE_CHANGED   = NO
GRADEBOOK_CHANGED  = NO
MOODLEDATA_CHANGED = NO
DOCKER_CHANGED     = NO
PRODUCTION_IMPACT  = NONE

NEXT_UNIT = UNIT-5  (escritura segura de calificación TP)
UNIT-5    = NOT_AUTHORIZED

GATE_DATABASE_CHANGE      = AWAITING_AUTHORIZATION
GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION
```

STOP — esperando autorización expresa para UNIT-5.

## SDD_MOODLE_PERIOD_GRADING — specification handoff (2026-08-17)

```text
IDENTIFIER_MODEL = TP-CEE-NN
  C  = período (0 = PERIOD_1, 2 = PERIOD_2)
  EE = Encuentro canónico 01..24
  NN = ordinal del TP dentro del Encuentro

PERIOD_1_ENCOUNTER_RANGE = 01..07   (C = 0)
PERIOD_2_ENCOUNTER_RANGE = 08..24   (C = 2)
ENCOUNTER_08             = START_OF_PERIOD_2
```

Decisiones funcionales cerradas (no reabrir):

```text
D-FID168-169-ORDINAL = CONFIRMED
  fid 167 → TP-208-01 · fid 168 → TP-208-02 · fid 169 → TP-208-03

D-FID157-RENAME = CONFIRMED
  "TP-00-Programa de la asignatura" → "Programa de la asignatura"
  EXECUTED_IN_MOODLE = NO

D-ENC13-SECTION-NAME = TWO_DISTINCT_ENCOUNTERS
D-SECOND-CURRENTLY-NAMED-ENC13-CANONICAL-CODE = OPTION_A (cascade)
```

Mapa canónico resultante (Encuentro 13 en adelante):

```text
fid 173 → TP-213-01 · fid 174 → TP-214-01
fid 175 → TP-215-01 · fid 176 → TP-215-02
fid 177 → TP-216-01 · fid 178 → TP-217-01
fid 179 → TP-218-01 · fid 180 → TP-219-01
fid 181 → TP-220-01 · fid 182 → TP-221-01
fid 183 → TP-222-01 · fid 184 → TP-223-01
fid 185 → TP-224-01
```

Modelo de calificación del período (cerrado):

```text
GRADING_UNIT = TP
GROUP_BY_ENCOUNTER = NO
TP_NUMERIC_GRADES_AVERAGED = NO
EACH_TP_WEIGHT = EQUAL_WITHIN_PERIOD
TP_WEIGHT = 10 / TOTAL_VALID_TP_IN_PERIOD

TP_PRESENTED_AND_EVALUATED → aporta TP_WEIGHT completo
TP_NOT_PRESENTED           → aporta 0

PERIOD_GRADE_RAW    = (COUNTED_TP / TOTAL_VALID_TP_IN_PERIOD) * 10
PERIOD_GRADE_FINAL  = ROUND_HALF_UP(PERIOD_GRADE_RAW)
PERIOD_GRADE_STORAGE = INTEGER_ONLY
ROUNDING_THRESHOLD   = 0.5

PERIOD_CLASSIFICATION (por período, independiente):
  7..10 → PROMOCION
  4..6  → EXAMEN_FINAL_DICIEMBRE_O_MARZO
  0..3  → RECURSA
```

(Sin fórmula anual que combine P1 y P2.)

Evaluación individual del TP (cerrada):

```text
D-TP-EVALUATION-BEFORE-GRADEBOOK = CONFIRMED

Matriz orientativa:
  DESARROLLO ADECUADO   + PRESENTACION COMPLETA   → 9–10
  DESARROLLO ADECUADO   + PRESENTACION INCOMPLETA → 7–8
  DESARROLLO NO ADECUADO + PRESENTACION COMPLETA  → 5–6
  DESARROLLO NO ADECUADO + PRESENTACION INCOMPLETA → 4–5

RANGES_ARE_ORIENTATIVE = YES
AUTOMATIC_FINAL_TP_GRADE = NO
GRADE_IS_EDITABLE_BEFORE_SAVE = YES
TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES
GRADEBOOK_WRITE_REQUIRES_EXPLICIT_TEACHER_SAVE = YES
NO_SILENT_GRADE_OVERWRITE = YES

TP_EVALUATION_WORKFLOW =
  CRITERIA_SELECTION → ORIENTATIVE_RANGE → TEACHER_GRADE_EDIT
  → TEACHER_CONFIRMATION → GRADEBOOK_WRITE
```

> **SUPERSEDED (2026-08-18):** la matriz "4–5" y el flujo "CRITERIA_SELECTION → …"
> quedan INVALIDATED_BY_USER_VERIFICATION. Banda final = 4 (LAST_BAND_4_ONLY = YES);
> flujo vigente = PEDAGOGICAL_ASSESSMENT → TEACHER_GRADE_EDIT → TEACHER_CONFIRMATION
> → GRADEBOOK_WRITE. Ver UNIT-4_SPEC_DESIGN_RECTIFICATION.

Fuente de verdad: `docs/06-SDD/moodle-period-grading-specification-amendment-2026-08-17.md`.

## SDD_MOODLE_PERIOD_GRADING — design handoff (2026-08-17)

```text
DESIGN_ARTIFACT = docs/06-SDD/moodle-period-grading-design-2026-08-17.md
DESIGN_STATUS   = COMPLETED  (rectificado 2026-08-17)
DESIGN_READY_FOR_AUTHORIZATION = YES
DESIGN_INTERNAL_CONSISTENCY    = CONSISTENT

KEY_DESIGN_DECISIONS =
  DD-PARSER-SINGLE-CONTRACT   → parse_tp_identifier() única (gramática TP-CEE-NN)
  DD-VALID-GRADED-TP-SINGLE   → reporte_tp_is_valid_graded_tp() única
  DD-PERIOD-ROUNDING-EXACT    → PERIOD_GRADE_FINAL = intdiv(20*COUNTED+N, 2*N)
                                (aritmética entera, HALF_UP exacto)
  DD-PERIOD-CONFIG-REPLACE    → período desde parser (C + rango Encuentro), no rango numérico

DESIGN_RECTIFICATION_2026_08_17 (5 rectificaciones aplicadas):
  RECTIFICATION_1 = FORUM_GRADE_SOURCE_OF_TRUTH → forum_grades
    TP_GRADE_WRITE_API = mod_forum\grades\forum_gradeitem::store_grade_from_formdata()
    (NO grade_update() directo como único mecanismo; detectado DIRECT_GRADE_UPDATE_ONLY_RISK)
  RECTIFICATION_2 = OFFICIAL_MODULE_RENAME_API → set_coursemodule_name($cmid, $newname)
    CANONICAL_TP_RENAME_SCOPE = fid 158..185 · NON_TP_RENAME_SCOPE = fid 157
    (fid 157: "TP-00-Programa de la asignatura" → "Programa de la asignatura",
     type = news · IS_TP = NO; captura PRE/POST de grades)
  RECTIFICATION_3 = ALL_IMPLEMENTATION_UNITS_REQUIRE_USER_AUTHORIZATION = YES
    (UNIT-1..UNIT-8; GATE_DATABASE_CHANGE separado y solo cuando toca datos protegidos)
  RECTIFICATION_4 = SAFE_ROLLBACK_WITH_PREEXISTING_DIRTY_WORKTREE
    (ROLLBACK_USES_GIT_CHECKOUT_RESTORE_RESET = NO; PRE_UNIT_BASELINE_CAPTURE)
  RECTIFICATION_5 = LOCAL_3_9_1_REFERENCE_4_0_5_COMPATIBILITY
    (LOCAL = Moodle 3.9.1+ Build 20200814 · REFERENCE = 4.0.5 ·
     PRODUCTION = UNKNOWN_NOT_VERIFIED)

DESIGN_RECTIFICATION_FINAL (micro-rectificación, 3 residuos corregidos):
  RESIDUO_A = CLEAR_TP_GRADE_API → store_grade_from_formdata(..., (object)['grade'=>''])
    (grade_floatval('')→null verificado; forum_grades.grade=NULL + Gradebook=NULL;
     NO grade_update() directo para borrar)
  RESIDUO_B = RENAME_RESYNC_BEHAVIOR → set_coursemodule_name() SÍ resincroniza
    (grade_update_mod_grades() → forum_update_grades() relee forum_grades;
     no afirmar "sin regrade"; verificar identidad numérica PRE/POST)
  RESIDUO_C = FORCE_REGRADING_REAL_BEHAVIOR → force_regrading() marca needsupdate=1
    en grade_item + course item (NO es no-op)
  DESIGN_CONTRADICTIONS_REMAINING_COUNT = 0

MOODLE_VERSION_FACT = local 3.9.1+ (NO 4.0.5 como asumía el encargo)

IMPLEMENTATION_SEQUENCE =
  UNIT-1  Parser TP-CEE-NN + VALID_GRADED_TP aislado → CLOSED_SUCCESS
  → UNIT-2A CANONICAL_RENAME_PREFLIGHT_READ_ONLY → CLOSED_SUCCESS (2026-08-17)
  → UNIT-2B CANONICAL_RENAME_WRITE → CLOSED_SUCCESS (2026-08-17, GATE_DATABASE_CHANGE CONSUMED)
  → UNIT-3  VALID_GRADED_TP_RUNTIME_INTEGRATION → NOT_AUTHORIZED_AT_DESIGN_HANDOFF
  → unidades funcionales posteriores según DESIGN

IMPLEMENTATION_AT_DESIGN_HANDOFF = PARTIAL
NEXT_UNIT_AT_DESIGN_HANDOFF      = UNIT-3
```

## Period-grading technical blockers (NOT user decisions)

```text
BLOCKER_2_STATUS       = PENDING_NOT_ADDRESSED
  La base local proviene de un dump anterior a las calificaciones actuales
  existentes en producción.

LOCAL_LOGIN_DEPENDENCY = PENDING

OBSERVED_TECHNICAL_STATE = courseid=15 has grade_forum=0 en TODOS los foros
  (ningún grade_item de foro creado aún; el action setupgrades no se ha
  ejecutado). Estado técnico observado, NO es USER_DECISION.
```

## Credentials

```text
LOCAL_ADMIN_CREDENTIAL = CONFIGURED_NOT_STORED
```

No passwords, tokens, keys, or credentials are ever written to this file.

```text
CONTAINS_SECRETS = NO
```

## SDD_MOODLE_PERIOD_GRADING — RECTIFICATION-R1 VERIFICATION (PASSED, 2026-08-20)

```text
RECTIFICATION-R1-IMPLEMENTATION = COMPLETED
RECTIFICATION-R1-VERIFICATION   = PASSED

SCOPE =
  LOCAL_ONLY
  forum fid 158..185
  canonical titles rectified according to verified title map

DATABASE_CHANGE =
  mdl_forum.name fid 158..185
  grade_forum preserved
  forum_grades preserved
  grade_grades preserved
  all other data preserved

DATABASE_VERIFICATION = PASSED

FORMULARIO_CALIFICACION = PASS

GRADEABLE_TP_COUNT_VISUAL = PASS (9)
NON_GRADEABLE_TP_VISUAL    = PASS

GRADEBOOK = PASS
  EXPECTED_GRADEABLE_TP_ITEMS = 9
  VISIBLE_GRADEABLE_TP_ITEMS  = 9
  TITLES_PRESERVED            = PASS
  DUPLICATES                  = NONE_VISIBLE
  RESTORED_TP_GRADES          = 0
  ACCIDENTAL_ZERO_TP_GRADES   = NONE_VISIBLE

RELECTURA = PASS
  GRADEABLE_TP_COUNT          = 9
  TITLE_IDENTIFIER_PRESERVED  = PASS
  TITLE_DESCRIPTION_PRESERVED = PASS
  EMPTY_TP_INPUTS_PRESERVED   = YES
  NON_GRADEABLE_PRESERVATION  = PASS

PERIOD_1_VISIBLE_ZERO =
  NOT_A_RESTORED_TP_GRADE
  computed period proposal/placeholder from 0/9 TP
  no period grade persisted by this verification

COURSE_ID = 15
COURSE_CURRENT_SHORTNAME = MedAud-26
COURSE_CURRENT_VISIBLE_NAME = Medios Aud-2026
COURSE_NAME_DOCUMENTATION_DRIFT = DETECTED_NON_BLOCKING

IMPORTANT_CHANGE_STATUS = AWAITING_GIT_CHECKPOINT
GATE_GIT_CHECKPOINT     = AWAITING_AUTHORIZATION

COMMIT     = NOT_AUTHORIZED
PUSH       = NOT_AUTHORIZED
PRODUCTION = NOT_AUTHORIZED
```

## SDD_MOODLE_PERIOD_GRADING — RECOVERY-UNIT-C VERIFICATION PASSED (2026-08-20)

```text
RECOVERY-UNIT-C                     = AWAITING_GIT_CHECKPOINT
RECOVERY-UNIT-C-IMPLEMENTATION      = COMPLETED
RECOVERY-UNIT-C-TECHNICAL-VERIFICATION = PASSED
RECOVERY-UNIT-C-VISUAL-VERIFICATION = PASSED
RECOVERY-UNIT-C-VERIFICATION           = PASSED

SCOPE =
  LOCAL_ONLY
  courseid = 15 (shortname MedAud-26 · fullname Medios Aud-2026)
  forum fid 158..166 · grade_items 64..72
  users EXACT_MATCH 15/15 · TP EXACT_TP_MATCH 9/9
  99 calificaciones restauradas en LOCAL

RECOVERY-UNIT-C-PRE-C-BACKUP = COMPLETED

PRE_C_BACKUP =
  fresh local Moodle DB backup
  preserved outside Git in AUXILIAR/BACKUPS/

PRE_C_BACKUP_PATH =
  AUXILIAR/BACKUPS/pre-recovery-unit-c-2026-08-20-134736.sql

PRE_C_BACKUP_SHA256 =
  ece138d24caf8107bfb1cbf98e00a3692c8707fcddb50f3fb57d11b4235a47f2

PRE_C_BASELINE_PATH =
  AUXILIAR/BACKUPS/pre-recovery-unit-c-baseline-2026-08-20-134736.txt

PRE_C_BASELINE_SHA256 =
  e16dc5b687e7792b3b1078f56870f4638c9afa52f29c7d51b013ed4c9ea70bdd

PRE_C_STATE =
  GRADEABLE_TP_COUNT = 9
  GRADE_ITEMS_COUNT = 9
  forum_grades non-null = 0
  grade_grades non-null = 0
  numeric zero grades = 0
  STATE_DRIFT = NO

GRADER =
  GRADER_USERID = 2 (site admin)
  GRADER_CAPABILITY_CHECK = PASS

WRITE_METHOD =
  mod_forum\grades\forum_gradeitem::store_grade_from_formdata()
  (official API; NO direct SQL writes to forum_grades / grade_grades)
  all 99 writes inside ONE $DB->start_delegated_transaction()

RESTORE_RESULT =
  SOURCE_NON_NULL_GRADE_COUNT = 99
  PLANNED_RESTORE_COUNT = 99
  WRITE_ATTEMPT_COUNT = 99
  WRITE_SUCCESS_COUNT = 99
  IN_TRANSACTION_FORUM_GRADES_MATCH_COUNT = 99
  IN_TRANSACTION_GRADEBOOK_MATCH_COUNT = 99
  IN_TRANSACTION_MISMATCH_COUNT = 0
  ROLLBACK_REQUIRED = NO
  ROLLBACK_EXECUTED = NO
  FULL_DB_RESTORE_EXECUTED = NO

POST_COMMIT_READ_BACK =
  POST_FORUM_GRADES_NON_NULL = 99
  POST_GRADE_GRADES_NON_NULL = 99
  POST_SOURCE_FORUM_MATCH_COUNT = 99
  POST_SOURCE_GRADEBOOK_MATCH_COUNT = 99
  POST_FORUM_GRADEBOOK_MATCH_COUNT = 99
  POST_MISMATCH_COUNT = 0
  GRADEBOOK_API_MATCH_COUNT = 99

DISTRIBUTION =
  158→12 · 159→15 · 160→11 · 161→10 · 162→10 · 163→13 · 164→10 · 165→8 · 166→10

INVARIANTS =
  CURRENT_GRADEABLE_TP_COUNT = 9
  CURRENT_GRADE_ITEMS_COUNT = 9
  FID_167_185_CHANGED = NO
  PERIOD_GRADES_CHANGED = NO
  FORUM_NAMES_CHANGED = NO
  GRADE_FORUM_CHANGED = NO
  grade_items definitions unchanged (50 / 51 / 64..72 identical to PRE-C)

OFFICIAL_REGRADE_SIDE_EFFECT = EXPECTED_NOT_CORRUPTION
  course-total grade item 50 recomputed (15 computed rows = correct sums)
  assign item 51 "Diseño Curricular" gained 15 NULL placeholder rows
  no period grades persisted

VERIFICATION =
  GRADEBOOK_TECHNICAL_VERIFICATION = PASS
  REPORT_TECHNICAL_RELECTURA = PASS
  USER_VISUAL_GRADEBOOK_VERIFICATION = PASS
  USER_VISUAL_REPORT_RELECTURA = PASS

DATABASE_CHANGED =
  NO during this documentation execution
  YES previously during RECOVERY-UNIT-C:
    99 authorized local TP grades via official Moodle API

FILES_CHANGED =
  docs/09-HANDOFF/CURRENT-STATE.md
  (documentation only in this execution)

MOODLE_CODE_CHANGED = NO
MOODLEDATA_CHANGED = NO

ROOT_CAUSE =
  local DB was older than the verified grading source and lacked
  the 99 first-period TP grades

VERIFICATION_RESULT =
  PASS

GIT_STATUS =
  branch feature/copia-local-moodle
  tracked worktree was clean before this documentation update
  CURRENT-STATE.md is now the only tracked modification
  AUXILIAR/ remains untracked and excluded
  no staging / commit / push

DOCKER_CHANGED =
  NO

PRODUCTION_IMPACT =
  NONE

IMPORTANT_CHANGE_STATUS = AWAITING_GIT_CHECKPOINT
GATE_DATABASE_CHANGE = CONSUMED
GATE_GIT_CHECKPOINT = AWAITING_AUTHORIZATION

COMMIT     = NOT_AUTHORIZED
PUSH       = NOT_AUTHORIZED
PRODUCTION = NOT_AUTHORIZED
```

## SDD_MOODLE_PERIOD_GRADING — RECOVERY-UNIT-C FORMAL CLOSURE (2026-08-21)

```text
RECOVERY-UNIT-C-IMPLEMENTATION = COMPLETED
RECOVERY-UNIT-C-TECHNICAL-VERIFICATION = PASSED
RECOVERY-UNIT-C-VISUAL-VERIFICATION = PASSED
RECOVERY-UNIT-C-VERIFICATION = PASSED

GIT_CHECKPOINT_COMMIT =
  30e448b394c24b8546b29ba37af236eb95bbef80 (short=30e448b)

GIT_CHECKPOINT_PUSH =
  PASS

GIT_BRANCH =
  feature/copia-local-moodle

GIT_REMOTE =
  github-viejo

LOCAL_HEAD =
  30e448b394c24b8546b29ba37af236eb95bbef80

REMOTE_HEAD =
  30e448b394c24b8546b29ba37af236eb95bbef80

LOCAL_HEAD_EQUALS_REMOTE_HEAD =
  YES

GATE_GIT_CHECKPOINT =
  CLOSED_SUCCESS

RECOVERY-UNIT-C =
  CLOSED_SUCCESS

IMPORTANT_CHANGE_STATUS =
  CLOSED_SUCCESS

RESTORED_TP_GRADES =
  99

DATABASE_CHANGE =
  LOCAL_ONLY
  99 authorized TP grades restored through official Moodle forum grading API
  forum_grades synchronized to grade_grades
  verification PASSED

PRE_C_BACKUP =
  PRESERVED_IN_AUXILIAR
  NOT_VERSIONED_BY_GIT

AUXILIAR_INCLUDED_IN_GIT =
  NO

ROOT_CAUSE =
  local DB was older than the verified grading source and lacked
  the 99 first-period TP grades

FILES_CHANGED =
  docs/09-HANDOFF/CURRENT-STATE.md
  (formal closure documentation only in this execution)

DATABASE_CHANGED =
  NO during this documentation execution

DOCKER_CHANGED =
  NO

VERIFICATION_RESULT =
  PASS

GIT_STATUS =
  formal closure documentation prepared
  final documentation Git checkpoint pending authorization

PRODUCTION_IMPACT =
  NONE
```

## SDD_MOODLE_PERIOD_GRADING — POST-RECOVERY-C OPERATIONAL RECONCILIATION (2026-08-21)

```text
POST_RECOVERY_C_RECONCILIATION = COMPLETED

CURRENT_OPERATIONAL_STATE_SOURCE = YES
  this section supersedes the stale operational statements reconciled below
  historical blocks above remain preserved as evidence
  (no historical fact rewritten, deleted, or reinterpreted)

RECOVERY-UNIT-C         = CLOSED_SUCCESS
IMPORTANT_CHANGE_STATUS = CLOSED_SUCCESS

CURRENT_GIT_BRANCH      = feature/copia-local-moodle
CURRENT_HEAD            = 542d006087d76eef0975190d27eeb344ac699cbe
  (short = 542d006 · final RECOVERY-UNIT-C closure commit)
CURRENT_TRACKED_WORKTREE = CLEAN
CURRENT_UNTRACKED       = AUXILIAR/ only
  excluded from Git (never staged, never committed)
```

### Reconciliación de los seis residuos (2026-08-21)

```text
RECONCILIATION_SCOPE =
  six stale/contradictory operational statements identified after RECOVERY-UNIT-C closure.
  Each is marked with its RECONCILED state. Historical blocks are preserved.

[1] STALE_REFERENCE = Header histórico: HEAD=809ba63 · WORKTREE=DIRTY (governance-framework block)
    RECONCILED_STATE = HEAD = 542d006 (post-RECOVERY-UNIT-C) · tracked worktree CLEAN ·
                       AUXILIAR/ untracked and excluded
    CLASSIFICATION   = HISTORICAL_VALID (governance closure) + STALE (HEAD/WORKTREE fields)

[2] STALE_REFERENCE = "Active SDD Work": IMPLEMENTATION=PARTIAL ·
                       GATE_DATABASE_CHANGE=AWAITING_AUTHORIZATION
    RECONCILED_STATE = superseded as operational state by RECOVERY-UNIT-C:
                       RECOVERY-UNIT-C = CLOSED_SUCCESS ·
                       GATE_DATABASE_CHANGE (RECOVERY-UNIT-C scope) = CONSUMED.
                       Any future DB-changing operation re-arms
                       GATE_DATABASE_CHANGE = AWAITING_AUTHORIZATION.
    CLASSIFICATION   = STALE_BUT_PRESERVED

[3] STALE_REFERENCE = HISTORICAL_HANDOFF_NOTICE: CURRENT_NEXT_UNIT=UNIT-4 ·
                       UNIT_4_STATUS=NOT_AUTHORIZED
    RECONCILED_STATE = superseded (2026-08-18): UNIT-4 rectified (TP_GRADE_CELL_UI restored
                       to pre-UNIT4 baseline). Next recommended unit = UNIT-5.
    CLASSIFICATION   = STALE_BUT_PRESERVED

[4] STALE_REFERENCE = NEXT_UNIT=UNIT-5 (histórico, anterior a RECOVERY-UNIT-C)
    RECONCILED_STATE = identifier UNIT-5 remains correct, but its rationale/context changed
                       after RECOVERY-UNIT-C. NEXT_RECOMMENDED_UNIT = UNIT-5
                       (reafirmado por reconciliación post-RECOVERY-C).
    CLASSIFICATION   = STALE_BUT_PRESERVED (rationale updated, identifier unchanged)

[5] STALE_REFERENCE = BLOCKER_2_STATUS = PENDING_NOT_ADDRESSED
                       ("local DB anterior a las calificaciones actuales de producción")
    RECONCILED_STATE = PARTIALLY_RESOLVED_FOR_PERIOD_1.
                       The 99 verified first-period TP grades were restored and
                       RECOVERY-UNIT-C closed successfully (CLOSED_SUCCESS).
                       NOT asserted resolved for PERIOD_2 (no evidence exists for
                       second-period grades fid 167..185 in local).
    CLASSIFICATION   = CONTRADICTORY_CURRENT_STATE → PARTIALLY_RESOLVED_FOR_PERIOD_1

[6] STALE_REFERENCE = OBSERVED_TECHNICAL_STATE: grade_forum=0 en TODOS los foros ·
                       sin grade_items (setupgrades no ejecutado)
    RECONCILED_STATE = historical and now FALSE for fid 158..166.
                       Vigente para el primer período:
                         fid 158..166 → grade_forum = 10
                         9 grade_items existentes (64..72)
                         99 calificaciones restauradas/verificadas.
                       No new assertions about fid 167..185 beyond existing evidence.
    CLASSIFICATION   = CONTRADICTORY_CURRENT_STATE (for fid 158..166)
```

### Estado operativo vigente (post-reconciliación)

```text
SDD_MOODLE_PERIOD_GRADING_STATUS =
  IMPLEMENTATION_PARTIAL
    UNIT-1   CLOSED_SUCCESS
    UNIT-2A  CLOSED_SUCCESS
    UNIT-2B  CLOSED_SUCCESS
    UNIT-3   CLOSED_SUCCESS
    UNIT-4   RECTIFIED (TP_GRADE_CELL_UI restored to pre-UNIT4 baseline)
    UNIT-5   NOT_STARTED  (REQUIRED · SUPERSEDED_BY_RECOVERY = PARTIAL)
    UNIT-6   NOT_STARTED
    UNIT-7   NOT_STARTED
    UNIT-8   NOT_STARTED

NEXT_RECOMMENDED_UNIT =
  UNIT-5  (escritura segura de calificación TP al Gradebook — RECTIFICATION_1)

NEXT_RECOMMENDED_ACTION =
  AUTHORIZE UNIT-5 → IMPLEMENT → VERIFY → STOP
  (GATE_DATABASE_CHANGE required for the grade-write scope)

DATABASE_CHANGED    = NO (documentation-only reconciliation)
FILES_CHANGED       = docs/09-HANDOFF/CURRENT-STATE.md (this reconciliation append)
GIT_CHANGED         = NO (no stage/commit/push)
DOCKER_CHANGED      = NO
PRODUCTION_IMPACT   = NONE

IMPLEMENTATION_AUTHORIZED = NO
```

## SDD_SESSION_BOOTSTRAP — NEW_SESSION DYNAMIC TEST + VERIFICATION (2026-08-22)

```text
CHANGE      = session-bootstrap (Bootstrap obligatorio de nueva sesión OpenCode)
EXECUTION   = VERIFICATION + DOCUMENT_CURRENT_STATE (read-only + documentation only)
EXECUTED_BY = gentle-orchestrator (this NEW_SESSION)

NEW_SESSION_TEST             = PASS
PREFLIGHT_AUTO_TRIGGER       = PASS
INTERACTIVE_AUTOMATIC_RESULT = PASS
ENGRAM_RESULT                = PASS
SESSION_PREFLIGHT_GATE_RESULT = PASS
DOUBLE_PREFLIGHT_CHECK       = PASS

IMPLEMENTATION_STATUS = COMPLETE
  LAYER 1  ~/.config/opencode/plugins/session-bootstrap.ts  = in place, functioning
           (GATE_RULE observed injected into this session's system prompt)
  LAYER 2  ~/.config/opencode/opencode.json prompt          = in place, resolves S1-S4

ENGRAM_STATE_RECOVERY_INCONSISTENCY = YES (apparent, not a data defect)
ENGRAM_INCONSISTENCY_ROOT_CAUSE =
  coexistence of multiple valid records (spec #130 / design #131 / judgment-day #133 /
  apply #134 / verify #136) + spec #130 SPECIFICATION_STATUS authored as
  DRAFT_AWAITING_DESIGN (18:54) and never back-updated after design approval (19:13)
  and implementation (20:20+). All 7 observations state=active (no needs_review).
  NOT: stale observation / Engram ranking / missing persistence.

CONCURRENCY_INCIDENT_STATUS = RESOLVED
CURRENT_OPENCODE_CONFIG_STATUS = MATCHES_EXPECTED_IMPLEMENTATION
  opencode.json = valid JSON, 65385 B, sha256 df05cc63... (matches recorded stable hash)
  concurrent LAYER-2 variant accepted as canonical (S1-S4 resolved)
  backups preserved: opencode.json.bak-20260821-session-bootstrap(-impl) (60842 B each)

FILES_CHANGED_DURING_THIS_VERIFICATION = docs/09-HANDOFF/CURRENT-STATE.md (documentation only)
SESSION_BOOTSTRAP_FILES = GLOBAL_CONFIG (outside repo):
  ~/.config/opencode/plugins/session-bootstrap.ts
  ~/.config/opencode/opencode.json (+ .bak backups)
PROJECT_REPOSITORY_FILES = NONE (session-bootstrap made zero repo changes)
GLOBAL_CONFIG_FILES = same as SESSION_BOOTSTRAP_FILES
MOODLE_PERIOD_GRADING_PREEXISTING_CHANGES =
  M moodle/reportes/reporteTPporCurso.php (+212/-51, unstaged, preexisting UNIT-5A)
  ?? AUXILIAR/ (untracked, never versioned by Git)

DATABASE_CHANGED  = NO
DOCKER_CHANGED    = NO
MOODLE_CHANGED    = NO
PRODUCTION_IMPACT = NONE

GIT_STATUS = branch feature/copia-local-moodle · HEAD 2591717 (local==remote)
  uncommitted: reporteTPporCurso.php (preexisting) + AUXILIAR/ (preexisting)
  no repo change attributable to session-bootstrap or this verification

VERIFICATION_RESULT = PASS
DOCUMENT_CURRENT_STATE = COMPLETED

REMAINING_RISKS =
  (1) LAYER 2 refusal-to-operate is prompt-compliance dependent (best-effort; design RISKS(1))
  (2) RESOLVED — spec #130 status field rectified (now terminal COMPLETED, Engram obs #139)
  (3) opencode.json is shared mutable state — re-verify hash/anchors before any future write
  (4) post-compaction re-bootstrap path (acceptance criterion 6) not yet dynamically tested

RECTIFICATION_REQUIRED = NO (completed 2026-08-22, Engram obs #139)
RECTIFICATION_SCOPE =
  advance spec #130 SPECIFICATION_STATUS (DRAFT_AWAITING_DESIGN → terminal
  DESIGN_APPROVED_AND_IMPLEMENTED) + reconciliation note; Engram-only (mem_update obs #130)
NEXT_SDD_PHASE = NONE

GIT_CHECKPOINT_STATUS = AWAITING_AUTHORIZATION
CLOSURE_STATUS = READY_FOR_GIT_CHECKPOINT
```

## UNIT-5A RECTIFICATION IMPLEMENTATION — CURRENT STATE — 2026-08-23

```text
PROJECT           = arteytecnologia.com.ar (Moodle local)
TASK              = UNIT_5A_RECTIFICATION_DOCUMENT_CURRENT_STATE
PHASE             = DOCUMENT_CURRENT_STATE
ENVIRONMENT       = LOCAL_ONLY
AUTHORIZATION     = EXPLICIT
BASELINE_HEAD     = 84b7b47efa13e73fef660263b499d12d2943b685
BRANCH            = feature/copia-local-moodle
FILES             = moodle/reportes/reporteTPporCurso.php (implementación R1–R4 ya realizada)
                    docs/09-HANDOFF/CURRENT-STATE.md (este documento)

=== R1 — DISPLAY DE CALIFICACIONES ===
R1_DISPLAY_QUERY_RECTIFICATION = IMPLEMENTED
ROOT_CAUSE_R1 = GET_RECORDS_SQL_KEY_COLLISION
CHANGE_IMPLEMENTED = SELECT id, forum, userid, grade FROM {forum_grades}
FORUM_GRADES_CANONICAL_SOURCE = forum_grades.grade
TP_CELL_SOURCE = forum_grades.grade (NO se volvió a grade_get_grades() para poblar la celda TP)
EXPECTED_RAW_GRADE_ROWS = 99
DISPLAY_GRADE_MAPPING = 99
VISUAL_R1_VERIFICATION = PASS  (la UI vuelve a mostrar las calificaciones existentes en las celdas)
NOTE = la evidencia gráfica del usuario NO verificó específicamente la fila userid=34/forumid=158;
       ese target fue revalidado por DB READ-ONLY (userid=34, forumid=158, grade=7)

=== R2 — "VER" / ENTREGA DE FORO ===
R2_FORUM_POST_PERMALINK = IMPLEMENTED
FORUM_LINK_ISSUE = PREEXISTING_FUNCTIONAL_LIMITATION
SEMANTICS =
  - post válido existente → "Ver"
  - "Ver" usa permalink Moodle al post
  - URLs externas se preservan como adicionales
  - sin post válido → "Sin entrega"
MULTIPLE_POST_POLICY = EARLIEST_VALID_POST
VISUAL_R2_VERIFICATION = PASS
EVIDENCE = "Ver" abrió correctamente un post Moodle local vía /mod/forum/discuss.php?d=1828#p2115
PRODUCTION_HAS_SAME_PREEXISTING_LIMITATION = YES
GRADE_BACKEND_IMPACT_R2 = NONE

=== R3 — TARGET USER SCOPE ===
R3_TARGET_USER_SCOPE = IMPLEMENTED
STATIC_VERIFICATION = PASS
ADVERSARIAL_REVIEW = PASS
SEMANTICS = savegrade acepta como target únicamente estudiante calificable.
REJECTS = teacher | editingteacher | manager | siteadmin | no matriculado | target sin rol student
PROTECTION =
  - reutiliza criterio semántico de roles (reporte_tp_role_flags)
  - no depende exclusivamente de roleid=5
  - mantiene permisos/capabilities del grader
  - mantiene forum/course scope
  - mantiene VALID_GRADED_TP
DYNAMIC_WRITE_TEST = NOT_EXECUTED (no se afirmó prueba dinámica de escritura para R3)

=== R4 — ROLLBACK LÓGICO VERIFICADO ===
R4_LOGICAL_ROLLBACK_DOUBLE_READBACK = IMPLEMENTED
R4_CRITICAL_ROLLBACK_FAILURE_PATH = IMPLEMENTED
STATIC_VERIFICATION = PASS
ADVERSARIAL_REVIEW = PASS
PRESERVED =
  official forum API
  + core lock
  + optimistic concurrency
  + transaction
  + in-transaction read-back
  + post-commit read-back
ADDED =
  logical rollback vía API oficial
  → post-rollback read-back forum_grades
  → post-rollback read-back grade_grades
  → comparación contra estado original
  → CRITICAL_ROLLBACK_FAILURE si restauración no es exacta
R4_DYNAMIC_FAILURE_INJECTION_TEST = NOT_EXECUTED

=== BACKEND SEGURO PRESERVADO ===
OFFICIAL_FORUM_API_PRESERVED = YES
API = mod_forum\grades\forum_gradeitem::store_grade_from_formdata()
OPTIMISTIC_CONCURRENCY_PRESERVED = YES
LOCK_PRESERVED = YES
DIRECT_WRITE_FORUM_GRADES = NO
DIRECT_WRITE_GRADE_GRADES = NO
UNIT_5B_IMPLEMENTED = NO  (no fetch/AJAX/autosave de UNIT-5B)

=== RUNTIME ===
RUNTIME_MOODLE_VERSION = 3.9.1+ (Build: 20200814)
RUNTIME_MOODLE_BRANCH = 39
MOODLE_3_9_1_COMPATIBILITY = PASS
PHP_LINT = PASS  (ejecutado vía contenedor local arteytecnologia_web con PHP 7.4; el host no dispone del binario php)

=== BASELINE DB PROTEGIDO (READ-ONLY) ===
FORUM_GRADES_NON_NULL_COUNT = 99
GRADE_GRADES_NON_NULL_COUNT = 99
MATCH_COUNT = 99
MISMATCH_COUNT = 0
TARGET_USERID = 34
TARGET_FORUMID = 158
TARGET_GRADE = 7
GRADE_WRITE_EXECUTED = NO
DATABASE_CHANGED_BY_RECTIFICATION = NO

=== VERIFICACIÓN VISUAL (USUARIO) ===
USER_VISUAL_VERIFICATION =
  R1 = PASS — calificaciones existentes nuevamente visibles
  R2 = PASS — "Ver" visible para publicaciones y navegación correcta al post Moodle
  EMPTY_LABEL = "Sin entrega"
R2_MULTIPLE_VER_LABELS = LOW / UX_NON_BLOCKING
  (R2 puede mostrar más de un "Ver" en una celda: permalink primario + URLs externas.
   No se modifica en esta tarea; no ampliar scope.)

=== PRUEBA CONTROLADA ===
CONTROLLED_GRADE_TEST = PAUSED_BEFORE_FIRST_WRITE
AUTHORIZED_TEST_TARGET =
  userid = 34
  forumid = 158
  grade_item_id = 64
  original = 7
7_TO_8_EXECUTED = NO
8_TO_7_EXECUTED = NO
GRADE_WRITE_EXECUTED = NO
CONTROLLED_GRADE_TEST_REQUIRES_NEW_AUTHORIZATION = YES
  (la autorización anterior estaba asociada al backend previo cf68404; el código cambió desde entonces)

=== ESTADO SDD ===
UNIT_5A_RECTIFICATION_DISCOVERY = COMPLETED
UNIT_5A_RECTIFICATION_DESIGN = COMPLETED
UNIT_5A_RECTIFICATION_DESIGN_CHECKPOINT = CLOSED_SUCCESS
UNIT_5A_RECTIFICATION_IMPLEMENTATION = COMPLETED
UNIT_5A_RECTIFICATION_STATIC_VERIFICATION = PASS
UNIT_5A_RECTIFICATION_VISUAL_VERIFICATION = PASS
UNIT_5A_RECTIFICATION_GIT_CHECKPOINT = AWAITING_AUTHORIZATION
UNIT_5A_CONTROLLED_GRADE_TEST = PAUSED_BEFORE_FIRST_WRITE
UNIT_5A_CLOSURE = IN_PROGRESS
UNIT_5B = NOT_IMPLEMENTED
GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION

=== ROOT CAUSES ===
ROOT_CAUSE_R1 = get_records_sql() usaba forum como primera columna no única, colapsando 99 filas en 9 claves.
ROOT_CAUSE_R2 = "Ver" dependía únicamente de URLs http/https literales del mensaje y no de la existencia real de un forum_post.
ROOT_CAUSE_R3 = savegrade validaba matriculación pero no restringía explícitamente el target a estudiante calificable.
ROOT_CAUSE_R4 = rollback lógico post-commit no verificaba mediante doble read-back que el estado original hubiera sido restaurado.

=== GIT / CHECKPOINT ===
GIT_ADD = NOT_EXECUTED
COMMIT = NOT_EXECUTED
PUSH = NOT_EXECUTED
DATABASE_CHANGED = NO
DOCKER_CHANGED = NO
MOODLEDATA_CHANGED = NO
PRODUCTION_IMPACT = NONE
UNIT_5A_RECTIFICATION_GIT_CHECKPOINT = AWAITING_AUTHORIZATION
NEXT_ACTION = AWAIT USER REVIEW AND SEPARATE GIT CHECKPOINT AUTHORIZATION
```

## UNIT-5A CONTROLLED GRADE TEST — FINAL VERIFICATION — 2026-08-23

```text
PROJECT      = arteytecnologia.com.ar (Moodle local)
TASK         = UNIT_5A_CLOSURE_DOCUMENT_CURRENT_STATE
PHASE        = DOCUMENT_CURRENT_STATE
ENVIRONMENT  = LOCAL_ONLY

=== TEST CONTROLADO ===
UNIT_5A_CONTROLLED_GRADE_TEST = PASS
BACKEND_COMMIT = f9c65a87b3d8f18406facdf81e11653541bbe6aa
TEST_TARGET =
  userid = 141
  student = Ruth Daniela Carceles
  forumid = 158
  grade_item_id = 64
  TP = TP-001-01 - Artistas multimedia
CONTROLLED_FLOW = 9 → 8 → 9
WRITE_9_TO_8 = PASS
RESTORE_8_TO_9 = PASS
GRADE_WRITE_SCOPE = userid=141/forumid=158 only
UNEXPECTED_GRADE_WRITES = 0
ORIGINAL_TARGET_34_158 = 7 / INTACT

=== END-TO-END VERIFICATION ===
CHAIN = SOURCE → forum_grades → grade_grades → Gradebook → reporte → relectura

Resultado temporal 9→8:
  forum_grades = 8
  grade_grades = 8
  Gradebook UI = 8
  Reporte = 8
  Relectura = 8
POST_9_TO_8_FULL_VERIFICATION = PASS

Resultado final 8→9:
  forum_grades = 9
  grade_grades = 9
  Gradebook UI = 9.00
  Reporte = 9
  Relectura = 9
FINAL_END_TO_END_VERIFICATION = PASS

=== DATABASE FINAL STATE ===
FORUM_GRADES_NON_NULL_COUNT = 99
GRADE_GRADES_NON_NULL_COUNT = 99
MATCH_COUNT = 99
MISMATCH_COUNT = 0
FINAL_TARGET_FORUM_GRADE = 9
FINAL_TARGET_GRADEBOOK_GRADE = 9
FINAL_TARGET_OVERRIDE = 0
FINAL_CHANGED_GRADE_COUNT = 0
DATABASE_FINAL_STATE = BASELINE_RESTORED

=== CANONICAL CHECKSUM ===
PRE_TEST_CANONICAL_CHECKSUM = c6774611c118a23ce0015555ca9aa09bdd1c37966f2721d4dd26222d7826bd56
POST_TEST_CANONICAL_CHECKSUM = c6774611c118a23ce0015555ca9aa09bdd1c37966f2721d4dd26222d7826bd56
CANONICAL_CHECKSUM_RESTORED = YES
  (el checksum PRE y POST es exactamente idéntico)

=== BACKUP EVIDENCE ===
PRE_TEST_BACKUP_PATH = AUXILIAR/BACKUPS/pre-unit5a-controlled-test-20260823-120323.sql
PRE_TEST_BACKUP_SIZE = 30140292 bytes
PRE_TEST_BACKUP_SHA256 = c00ddb65c33f324eb2102173e5da2ce0c2e062b902b99437a57140e3c1530300
BACKUP_RESTORE_EXECUTED = NO
  (el baseline se restauró mediante el flujo normal 8→9, NO importando el dump)

=== UNIT-5A FINAL STATUS ===
UNIT_5A_RECTIFICATION_IMPLEMENTATION = COMPLETED
UNIT_5A_RECTIFICATION_STATIC_VERIFICATION = PASS
UNIT_5A_RECTIFICATION_VISUAL_VERIFICATION = PASS
UNIT_5A_RECTIFICATION_GIT_CHECKPOINT = CLOSED_SUCCESS
UNIT_5A_CONTROLLED_GRADE_TEST = PASS
UNIT_5A_DATABASE_VERIFICATION = PASS
UNIT_5A_END_TO_END_VERIFICATION = PASS
UNIT_5A_CLOSURE = READY_FOR_GIT_CHECKPOINT
UNIT_5B = NOT_IMPLEMENTED
GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION
  (NO se declara UNIT_5A_CLOSURE = CLOSED_SUCCESS todavía; requiere checkpoint Git final de esta documentación)

=== SAFETY / IMPACT ===
CODE_CHANGED_BY_TEST = NO
DATABASE_FINAL_CHANGE = NO
GRADE_RESIDUAL_CHANGE = NO
DOCKER_CHANGED = NO
MOODLEDATA_CHANGED = NO
PRODUCTION_IMPACT = NONE
AUXILIAR_GIT_POLICY = DO_NOT_STAGE

=== ROOT RESULT ===
VERIFICATION_RESULT =
  UNIT-5A backend verificado end-to-end mediante ciclo controlado 9→8→9 usando la UI real y API oficial Moodle.
  La modificación temporal se propagó correctamente a: forum_grades, grade_grades, Gradebook, reporte, relectura.
  La restauración recuperó exactamente el baseline canónico, sin cambios residuales y con checksum PRE=POST.

=== GIT / CHECKPOINT ===
GIT_ADD = NOT_EXECUTED
COMMIT = NOT_EXECUTED
PUSH = NOT_EXECUTED
UNIT_5A_CLOSURE = READY_FOR_GIT_CHECKPOINT
NEXT_ACTION = AWAIT USER AUTHORIZATION FOR FINAL UNIT-5A DOCUMENTATION GIT CHECKPOINT
```

## UNIT-5B ASYNC_TP_GRADE_SAVE_UI + R1/R5 — FINAL CLOSURE — 2026-08-23

```text
PROJECT = arteytecnologia.com.ar (Moodle local)
TASK = DOCUMENT_CURRENT_STATE (cierre técnico verificado de UNIT-5B + R1 + R5)
ENVIRONMENT = LOCAL_ONLY

=== UNIT-5B — ASYNC_TP_GRADE_SAVE_UI ===
UNIT_5B_IMPLEMENTATION = VERIFIED_PASS
IMPLEMENTATION = COMPLETED
STATIC_VERIFICATION = PASS
DYNAMIC_VERIFICATION = PASS
END_TO_END_VERIFICATION = PASS
DATABASE_VERIFICATION = PASS
UNIT_5B_CLOSURE = READY_FOR_GIT_CHECKPOINT
  (NO se declara CLOSED_SUCCESS todavía; reservado para después del commit/push verificado)

=== UNIT-5B RECTIFICATION-R1 ===
ROOT_CAUSE = el formulario contiene <input type="hidden" name="action" value="savegrade"> y la
  named property `form.action` quedó shadoweada por ese control.
EFECTO = fetch(form.action, ...) posteaba a /reportes/[object HTMLInputElement]
  (access log: POST /reportes/[object%20HTMLInputElement]).
RESULTADO = el backend savegrade no se ejecutaba y la UI mostraba "Error al guardar".
RECTIFICATION = fetch(form.action, ...) → fetch(form.getAttribute('action'), ...)
VERIFICATION = REQUEST_PATH correcto /reportes/reporteTPporCurso.php · HTTP 200 · application/json ·
  ACTION_SHADOWING_RECURRED = NO
R1_DYNAMIC_VERIFICATION = PASS

=== UNIT-5A RECTIFICATION-R5 ===
ROOT_CAUSE = el lock \core\lock se liberaba únicamente desde `finally`; las respuestas terminales
  JSON/redirect usaban exit/redirect y el lock quedaba sin liberar.
EVIDENCIA PREVIA = JSON de éxito seguido por "A lock was created but not released" → contaminaba la
  respuesta → JSON.parse: unexpected non-whitespace character after JSON data → la nota sí se escribía
  pero UNIT-5B mostraba falso "Error al guardar".
RECTIFICATION = helper idempotente $releaselock + liberación antes de toda respuesta terminal +
  finally idempotente de seguridad.
INVARIANTES PRESERVADAS = lock lógico por forumid/userid · optimistic concurrency · API oficial
  store_grade_from_formdata() · transaction · in-transaction readback · post-commit readback ·
  logical rollback · R4 rollback verification.
VERIFICATION = LOCK_NOT_RELEASED_ERROR_COUNT_FINAL_RETEST = 0 · JSON_TRAILING_CONTENT = NO ·
  PHP_WARNING = NO · HTML_AFTER_JSON = NO
R5_DYNAMIC_VERIFICATION = PASS

=== FINAL CONTROLLED RETEST ===
ENVIRONMENT = LOCAL
courseid = 15 · userid = 141 (Ruth Daniela Carceles) · forumid = 158 · grade_item_id = 64 · TP-001-01 - Artistas multimedia
FLUJO = 9 → 8 → 9

WRITE 9→8: ASYNC_SAVED=PASS · FULL_PAGE_RELOAD=NO · expected_previous_grade 9→8
  DB temporal: forum_grades=8 · grade_grades=8 · override=0
  HTTP: POST /reportes/reporteTPporCurso.php · 200 · application/json · JSON puro ok=true grade=8 previousgrade=9

WRITE 8→9: ASYNC_SAVED=PASS · FULL_PAGE_RELOAD=NO · expected_previous_grade 8→9
  DB final: forum_grades=9 · grade_grades=9 · override=0
  HTTP: POST /reportes/reporteTPporCurso.php · 200 · application/json · JSON puro ok=true grade=9 previousgrade=8

=== BASELINE FINAL ===
FINAL_FORUM_COUNT = 99
FINAL_GRADEBOOK_COUNT = 99
FINAL_MATCH_COUNT = 99
FINAL_MISMATCH_COUNT = 0
FINAL_CHANGED_GRADE_COUNT = 0
PRE_TEST_CANONICAL_CHECKSUM = c6774611c118a23ce0015555ca9aa09bdd1c37966f2721d4dd26222d7826bd56
POST_TEST_CANONICAL_CHECKSUM = c6774611c118a23ce0015555ca9aa09bdd1c37966f2721d4dd26222d7826bd56
CANONICAL_CHECKSUM_RESTORED = YES
DATABASE_FINAL_STATE = BASELINE_RESTORED
GRADE_RESIDUAL_CHANGE = NO
PERIOD_GRADES_CHANGED = NO
FID_167_185_CHANGED = NO

=== BACKUP / EVIDENCE ===
FINAL_RETEST_BACKUP_PATH = AUXILIAR/BACKUPS/pre-unit5b-final-retest-20260823-145608.sql
FINAL_RETEST_BACKUP_SIZE = 30149200 bytes
FINAL_RETEST_BACKUP_SHA256 = cbabdf3ac8ee521048dd803c2578229c50b5a7f0d45ff27296d1b1efe937fba3
AUXILIAR permanece fuera de Git.

=== GIT / PRODUCTION STATE ===
PRODUCTION_IMPACT = NONE
GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION
HEAD = 17ddebdbe4d7728506ebac7c9d26bcf313fed648
BRANCH = feature/copia-local-moodle
CODE MODIFIED BUT NOT COMMITTED = moodle/reportes/reporteTPporCurso.php (UNIT-5B + R1 + R5)
DOCUMENT MODIFIED FOR CLOSURE = docs/09-HANDOFF/CURRENT-STATE.md
AUXILIAR = untracked / excluded from checkpoint
GIT CHECKPOINT = NOT_YET_AUTHORIZED
COMMIT = NOT_AUTHORIZED
PUSH = NOT_AUTHORIZED

=== ESTADO FINAL ===
UNIT_5A_RECTIFICATION_R5 = VERIFIED_PASS
UNIT_5B_RECTIFICATION_R1 = VERIFIED_PASS
UNIT_5B_IMPLEMENTATION = VERIFIED_PASS
UNIT_5B_END_TO_END_VERIFICATION = PASS
UNIT_5B_DATABASE_VERIFICATION = PASS
UNIT_5B_CLOSURE = READY_FOR_GIT_CHECKPOINT
  (CLOSED_SUCCESS pendiente de: PREPARE_GIT_CHECKPOINT → AUTHORIZATION → COMMIT → PUSH → VERIFY LOCAL_HEAD=REMOTE_HEAD)

NEXT_ACTION = AWAIT USER REVIEW AND SEPARATE GIT CHECKPOINT AUTHORIZATION (UNIT-5B + R1 + R5)
```
