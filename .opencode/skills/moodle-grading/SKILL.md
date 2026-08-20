---
name: moodle-grading
description: "Read, compute, and persist Moodle course period grades safely via the official gradebook APIs. Use for grading, calificaciones, nota de cuatrimestre, período, gradebook, reporteTPporCurso."
---

# Moodle Grading

Specialized procedure for reading, computing, and persisting course period grades in the Moodle gradebook.

## Responsibility

Handle students, activities, `grade_items`, `grade_grades`, and the gradebook correctly: reading,
calculation, writing, overrides, and — above all — preservation of existing grades.

## API Policy (critical)

- **Prefer official Moodle gradebook APIs** (`grade_item`, `grade_grade`, gradebook service).
- **Avoid direct SQL writes** to `grade_items` / `grade_grades`.
- Direct SQL may be used **only for read-only diagnosis**.
- Any exception (direct SQL write) must be justified and pass **DESIGN + explicit authorization**.
- **Always verify preservation of existing grades** before and after any write.

## Procedure

```text
LOGIN → CURSO → FORMULARIO → LECTURA → CÁLCULO → GUARDADO → DATABASE → GRADEBOOK → RELECTURA
```

1. Login to Moodle local (`http://localhost:8080`) and navigate to the target course.
2. Open the period-grade form / report (`moodle/reportes/reporteTPporCurso.php`).
3. Read current grades (grade_items + grade_grades) via official APIs.
4. Compute the period grade from the configured TP/forum ranges.
5. Save using **override-only semantics** (empty input deletes the persisted override, not the computed value).
6. On any delete/update that affects final grades, trigger `force_regrading()` / `grade_regrade_final_grades()`.
7. Re-read from the DB and the gradebook to confirm the round-trip.

## Gates

Any destructive grade change (bulk write, delete, regrade) stops at:

```text
GATE_DATABASE_CHANGE = AWAITING_AUTHORIZATION
```

## Verification (mandatory)

End-to-end read-back: DB → gradebook → re-read. A change is not "done" until the re-read matches.

## Should NOT contain

Policy, secrets, current state.
