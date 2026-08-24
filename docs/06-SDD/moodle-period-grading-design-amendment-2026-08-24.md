# Design Amendment — Course-Aware TP Classification — 2026-08-24

> DESIGN AMENDMENT (DOCUMENTACIÓN / READ-ONLY). Rectifica el diseño del reporte para
> separar `REPORTABLE_TP` de `CANONICAL_PERIOD_TP` y preservar el comportamiento legacy
> de courses 19/20. NO autoriza implementación.
>
> SPEC_REF = moodle-period-grading-specification-amendment-2026-08-17.md
> (sección "SPECIFICATION RECTIFICATION — COURSE-AWARE TP CLASSIFICATION — 2026-08-24").

---

## D1 — Matriz de consumidores (acoplamiento detectado)

```text
CONSUMER                CURRENT_RULE                        NEEDS_CANONICAL_PERIOD  NEEDS_ONLY_REPORTABLE  WRITE_RISK
----------------------  ----------------------------------  ---------------------  ---------------------  ----------
LISTING (line 1073)     reporte_tp_is_valid_graded_tp       NO                     YES                    READ
SETUPGRADES (line 475)  reporte_tp_is_valid_graded_tp       NO                     YES                    HIGH (grade_forum/grade_items)
SAVEGRADE (line 574)    reporte_tp_is_valid_graded_tp       NO                     YES                    HIGH (grade writes)
PERIOD_GROUPING (1190)  reporte_tp_collect_period_forums    YES                    NO                     READ
PERIOD_GRADE (compute)  collect_period_forums + fórmula     YES                    NO                     READ
EXCEL (line 1197)       usa $forums (== listing)            NO                     YES                    READ

ROOT_CAUSE = CANONICAL_IDENTIFIER_SCOPE_OVERREACH:
  reporte_tp_is_valid_graded_tp() (que exige TP-CEE-NN) se usa en consumidores que solo
  necesitan "reportable TP" (listing/setupgrades/savegrade/excel). Solo period grouping y
  period grade requieren realmente el modelo canónico.
```

## Diseño propuesto (course-aware, sin relajar el parser)

```text
Capa explícita y determinista (policy/strategy, sin hardcodear IDs sin justificación):

  reporte_tp_is_reportable_tp(object $forum, int $courseid): bool
    - course 15:  type != 'news' AND parse_tp_identifier(name).valid   (sin cambios)
    - course 19/20: type != 'news' AND name LIKE 'TP-%' (contrato legacy)
    - otro courseid: FAIL_SAFE (read-only/compatible o unsupported explícito)

  reporte_tp_is_canonical_period_tp(object $forum): bool
    = reporte_tp_is_valid_graded_tp($forum)  (alias/renombre del actual; UNCHANGED)

  reporte_tp_get_period_of_forum() / reporte_tp_collect_period_forums()
    - SÓLO para CANONICAL_PERIOD_TP (course 15).
    - courses 19/20: período NO derivado (null / N/A).

Consumidores re-asignados:
  LISTING / EXCEL   → reporte_tp_is_reportable_tp(forum, courseid)
  SAVEGRADE         → reporte_tp_is_reportable_tp(forum, courseid) + grade_forum>0 + resto protecciones
  SETUPGRADES       → COURSE_15_ONLY (solo canonical)
  PERIOD_GROUPING/GRADE → reporte_tp_is_canonical_period_tp (course 15)
```

## Course ID policy (por qué y fallback)

```text
KNOWN_COURSES = {15: CANONICAL, 19: LEGACY, 20: LEGACY}
  Justificación: los tres son los ACTIVE_COURSES confirmados (15 MedAud-26, 19 ComplPint26,
  20 LVI-2026) con evidencia READ-ONLY de producción.
  Mantenibilidad: policy declarativa (mapa courseid → policy), NO ramas if dispersas.
FALLBACK (courseid no listado) = UNKNOWN_POLICY → read-only/report-compatible o
  explicit unsupported; NUNCA write con policy desconocida.
No asumir que un futuro curso hereda la policy de 15 ni de 19/20.
```

## Write path safety (preservado y reforzado)

```text
SAVEGRADE no reduce ninguna protección ya verificada:
  forum pertenece al course · target es student (reporte_tp_role_flags) · grader capability
  (mod/forum:grade) · grade_forum > 0 · API oficial store_grade_from_formdata · optimistic
  concurrency · lock · double read-back · no silent overwrite.

Elegibilidad del forum = REPORTABLE_TP_POLICY(course) — NUNCA solo name LIKE 'TP-%'.
```

## Setupgrades safety

```text
SETUPGRADES_ALLOWED_COURSES = course 15 only (conservador).
No hay evidencia que justifique setup automático en 19/20. El reporte legacy de 19/20 no
necesita setupgrades para listar/calificar (grade_forum ya configurado por el docente).
No ampliar writes por compatibilidad de visualización.
```

## Period UI policy

```text
COURSE_15: columnas de período (Cuatrimestre 1/2) habilitadas — modelo vigente.
COURSES_19_20: listado/nota preservados; columnas de período NO inferidas.
  Opción coherente: ocultar columnas de período (o mostrar N/A). No implementar fórmula.
```

## Acceptance tests (LOCAL, previo a nuevo deployment)

```text
COURSE 15:  28 canonical TP post-rename · news fid157 excluded · períodos correctos.
COURSE 19:  todos los TP legítimos actuales preservados · notas visibles · posts/links
            preservados · sin foro inesperado incluido.
COURSE 20:  idem.
WRITE PATH: backend safe sigue validando forum apropiadamente (course-aware).
PERIOD:     sin asignación falsa de período en 19/20.
EXCEL:      mismo set reportable que pantalla.
NO DB change requerido por la implementación de compatibilidad en sí.
```

## Decisión de diseño

```text
ROOT_CAUSE = CANONICAL_IDENTIFIER_SCOPE_OVERREACH
CANONICAL_IDENTIFIER_SCOPE = COURSE_15_ONLY
REPORTABLE_TP_CONCEPT_SEPARATED = YES
PERIOD_TP_CONCEPT_SEPARATED = YES

COURSE_15_POLICY = CANONICAL_TP_CEE_NN (unchanged)
COURSE_19_POLICY = LEGACY_COMPATIBLE (name LIKE 'TP-%', type != news)
COURSE_20_POLICY = LEGACY_COMPATIBLE
UNKNOWN_COURSE_POLICY = FAIL_SAFE_READ_ONLY_OR_UNSUPPORTED

COURSES_19_20_RENAME_REQUIRED = NO
PARSE_TP_IDENTIFIER_GLOBAL_RELAXATION = NO

PERIOD_MODEL_COURSE_15 = EXISTING_VALIDATED
PERIOD_MODEL_COURSE_19 = NOT_SPECIFIED
PERIOD_MODEL_COURSE_20 = NOT_SPECIFIED

SETUPGRADES_SCOPE = COURSE_15_ONLY
SAVEGRADE_POLICY = REPORTABLE_TP_POLICY (course-aware) + protecciones verificadas

LOCAL_IMPLEMENTATION_REQUIRED = YES
LOCAL_IMPLEMENTATION_AUTHORIZED = NO
GATE_B = BLOCKED_PENDING_LOCAL_COMPATIBILITY_IMPLEMENTATION_AND_VERIFICATION
GATE_C = AWAITING_SEPARATE_AUTHORIZATION

USER_DECISION_REQUIRED = YES (course 20 fid 277 "TP- 00 -Programa de la asignatura":
  type=general + nombre TP-* + 12 notas → AMBIGUOUS; decidir reportable vs NON_TP).
```

---

## Decisión final — USER_DECISION cerrada (fid277) — 2026-08-24

```text
D-COURSE20-FID277-GRADEABLE = CONFIRMED
FID277_REPORTABLE = YES · FID277_GRADEABLE = YES
FID277_CANONICAL_PERIOD_TP = NO · FID277_PERIOD_MODEL = NONE
FID277_PERIOD_GRADE_CONTRIBUTION = NO

COURSE_20_IDENTIFIER_POLICY = LEGACY_COMPATIBLE
  (fid277 queda como REPORTABLE_GRADED_ACTIVITY; NO se fuerza al modelo canónico.)

CONCEPTOS (3 niveles):
  REPORTABLE_ACTIVITY · GRADEABLE_REPORT_ACTIVITY · CANONICAL_PERIOD_TP

SAVEGRADE_POLICY = course-aware (15 canonical · 19/20 legacy-compatible) + protecciones
SETUPGRADES_SCOPE = COURSE_15_ONLY
UNKNOWN_COURSE = LEGACY_READ_ONLY (reporting) + DENY (writes) + NONE (period)

USER_DECISION_REQUIRED = NO (resuelto)
USER_DECISIONS_STILL_REQUIRED_COUNT = 0
LOCAL_IMPLEMENTATION_REQUIRED = YES · LOCAL_IMPLEMENTATION_AUTHORIZED = NO
GATE_B = BLOCKED_PENDING_LOCAL_COMPATIBILITY_IMPLEMENTATION_AND_VERIFICATION
NEXT_ACTION = USER REVIEW FOR LOCAL COMPATIBILITY IMPLEMENTATION AUTHORIZATION
```
