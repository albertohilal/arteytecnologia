# SDD_MOODLE_PERIOD_GRADING — Evidencia de decisiones pendientes

> Fecha: 2026-08-17 · Cambio: `moodle-period-grading` · Fase: SPECIFICATION (SPECIFICATION_AMENDMENT)
> Destino: `docs/06-SDD/moodle-period-grading-decisions-evidence-2026-08-17.md`

Ejecución **READ-ONLY** (solo `SELECT`, sin escrituras SQL, sin cambios de código, sin operaciones Git mutantes, sin tocar producción). Skills cargadas: `moodle-grading` y `moodle-local-docker`.

---

## Resumen

```text
SDD_MOODLE_PERIOD_GRADING = IN_PROGRESS
PHASE                     = SPECIFICATION (SPECIFICATION_AMENDMENT)

D-FID168-169-ORDINAL      = CONFIRMED (fid 167=TP-208-01, fid 168=TP-208-02, fid 169=TP-208-03)
D-GRADING-AGGREGATION     = CONFIRMED (Opción A: cada TP independiente, igual peso)
D-FID157-RENAME           = CONFIRMED (→ "Programa de la asignatura", news, NO TP)
D-ENC13-SECTION-NAME      = TWO_DISTINCT_ENCOUNTERS (rectificada)
D-SECOND-ENC13-CODE       = CONFIRMED (OPTION_A: cascade → fid 174 = TP-214-01)
D-PERIOD-ROUNDING         = CONFIRMED (entero, HALF_UP, umbral 0.5)

USER_DECISIONS_STILL_REQUIRED_COUNT = 0

SPECIFICATION_READY_FOR_DESIGN = YES
IMPLEMENTATION = NOT_AUTHORIZED
```

---

## Evidencia por decisión

### D-FID168-169-ORDINAL

```text
D-FID168-169-ORDINAL

FACTS =
  - courseid=15, sección 8 = "Encuentro 08- P5js y su editor"
    (mdl_course_sections.id=208), sequence = "256,257,258".
  - cmid 256 → fid 167 "TP-08-1-Editor Web de P5js"
  - cmid 257 → fid 168 "TP-08-2-Ejemplos oficiales de p5.js"
  - cmid 258 → fid 169 "TP-08-2-Sketch en el Editor Web de p5.js"
  - course_modules.added: fid168 = 1755029167, fid169 = 1755029547.

EVIDENCE =
  - course_sections.sequence (orden autoritativo) = 256,257,258
    → fid167 (1º), fid168 (2º), fid169 (3º).
  - course_modules.added confirma fid168 creado ANTES que fid169.
  - intros distintas: fid168 = reseñar ejemplos oficiales p5js.org;
    fid169 = copiar/experimentar un sketch concreto del editor.

INFERENCE =
  fid168 = 2º TP del Encuentro 08 (TP-208-02);
  fid169 = 3º TP del Encuentro 08 (TP-208-03).
  El sufijo "-2" de fid169 es un sub-índice DUPLICADO (error de etiqueta).

RECOMMENDATION = fid168 → TP-208-02 y fid169 → TP-208-03 (período 2 = prefijo 2xx).
CONFIDENCE     = HIGH
USER_DECISION  = CONFIRMED (fid 167=TP-208-01, fid 168=TP-208-02, fid 169=TP-208-03)
```

La hipótesis a verificar **se confirma** por dos criterios independientes (orden en `sequence` y orden temporal en `added`).

---

### D-GRADING-AGGREGATION

```text
OPTION_A = cada TP es una unidad calificable independiente.
IMPACT_A =
  - Es el comportamiento ACTUAL del código.
  - Cálculo del período: nota = round((entregados / total_TP) × 10).
  - Un Encuentro con 3 TP pesa 3× más que uno con 1 TP.
  - grade_items: uno por foro (creado por setupgrades), 1:1.
  - Preserva calificaciones de foro 1:1 sin re-agrupar.
  - Compatible con el comportamiento actual; sin cambio de lógica
    más allá del renumerado del parser.

OPTION_B = los TP de un mismo Encuentro se agrupan ANTES de calcular
           la calificación del período.
IMPACT_B =
  - Trata cada ENCUENTRO como unidad pedagógica de igual peso.
  - Requiere lógica nueva: agrupar foros por EEE, calcular nota por
    Encuentro y luego agregar.
  - Requiere SUB-DECISIÓN sobre la regla de agrupado (promedio de
    notas del Encuentro? "todos los TP"? mínima?).
  - Cambia el peso relativo: Encuentros con varios TP dejan de pesar
    más que los de 1 TP.
  - Cambia la auto-nota del período; grade_items de foro pueden
    quedar igual, pero el cálculo del reporte cambia.

CURRENT_BEHAVIOR =
  OPTION_A (código vigente de reporteTPporCurso.php):
  - total = nº de foros del período (cada foro cuenta 1).
  - entregado = foro con nota >= 4.
  - nota = round(entregados/total × 10).
  - Rango de período actual: [[1,10],[11,20]] (por número de TP, modelo viejo).

RECOMMENDATION =
  Técnica/pedagógica (NO vinculante): si el criterio docente es
  "cada clase/encuentro pesa igual", OPTION_B es más coherente con el
  modelo Encuentro-centrado ya confirmado; pero exige definir la regla
  de agrupado. Si se prioriza simplicidad y no tocar el cálculo actual,
  OPTION_A. Es una decisión de criterio pedagógico que solo Alberto
  puede tomar.

RATIONALE =
  Que un Encuentro pueda contener VARIOS TP NO demuestra por sí mismo
  A ni B: ambos modelos admiten múltiples TP por Encuentro. La
  diferencia está en el PESO relativo en la nota del período.

USER_DECISION = CONFIRMED (Opción A: cada TP independiente, igual peso,
                sin agrupación por Encuentro, sin promedio de notas)
```

**Ejemplo numérico simple** (2 Encuentros en el período):

| | Encuentro 01 | Encuentro 08 | Total |
|---|---|---|---|
| Nº de TP | 1 | 3 | 4 |
| TP entregados por el alumno | 1 | 1 | 2 |

- **Opción A**: `2/4 = 50%` → nota **5**.
- **Opción B** (promedio por Encuentro): Enc01 `1/1=100%`, Enc08 `1/3=33%` → `(100+33)/2 = 66%` → nota **~7**.

Con la Opción A, el Encuentro 08 (3 TP) domina la nota; con la Opción B, cada Encuentro vale lo mismo.

> Nota adicional (preservación): la BD local tiene **0 calificaciones** (`grade_grades_total = 0`) y **0 `grade_items` de foro** (todos `grade_forum=0`). No hay calificaciones existentes que preservar en local; el dump local es anterior a las notas de producción (bloqueador conocido, fuera del alcance de esta fase).

---

### DECISIÓN CONFIRMADA — agregación y regla académica (2026-08-17)

**D-GRADING-AGGREGATION** confirmado por Alberto:

```text
GRADING_UNIT               = TP
EACH_TP_WEIGHT             = EQUAL_WITHIN_PERIOD
GROUP_BY_ENCOUNTER         = NO
TP_NUMERIC_GRADES_AVERAGED = NO
```

- Cada TP es una unidad calificable independiente.
- Todos los TP tienen exactamente el mismo peso (dentro del período).
- Que varios TP pertenezcan al mismo Encuentro NO provoca agrupación.
- NO se promedian las calificaciones numéricas de los TP.

**Aporte de cada TP al período** (USER_DECISION, 2026-08-17):

```text
TP_WEIGHT_IS_DYNAMIC = YES
TP_WEIGHT            = 10 / TOTAL_VALID_TP_IN_PERIOD

TP_PRESENTED_AND_EVALUATED → aporta el 100% de TP_WEIGHT
TP_NOT_PRESENTED           → aporta 0

PERIOD_GRADE_RAW = COUNTED_TP * TP_WEIGHT
                 = (COUNTED_TP / TOTAL_VALID_TP) * 10
```

La nota numérica individual del TP NO modifica su peso en el período.

**Regla académica por período** (independiente para cada período):

```text
PERIOD_CLASSIFICATION_SCOPE = EACH_PERIOD_INDEPENDENTLY

[7,10]  → PROMOCION
[4,7)   → EXAMEN_FINAL_DICIEMBRE_O_MARZO
[0,4)   → RECURSA
```

No se define ninguna regla anual que combine P1 y P2 (eso requeriría una
decisión específica posterior; no se infieren `(P1+P2)/2` ni combinaciones
booleanas de P1/P2).

**Umbral de entrega y terminología (resuelto):**

```text
CURRENT_IMPLEMENTATION = FACT
  reporte_tp_compute_period_grades() cuenta un TP ("delivered") cuando
  forum grade >= 4.

TP_GRADE_GE_4_STATUS        = CONFIRMED_AS_CURRENT_PROXY
TP_GRADE_GE_4_MEANS         = PRESENTED_AND_EVALUATED_FOR_PERIOD_COUNT
TP_GRADE_GE_4_DOES_NOT_MEAN = INDIVIDUAL_TP_APPROVAL_FOR_AVERAGING

TERMINOLOGY_ISSUE_STATUS = SPECIFIED_FOR_DESIGN
  "delivered"/"entregados" es ambiguo. Se recomienda para DESIGN:
  counted / presented / presented_and_evaluated.
```

**Redondeo (D-PERIOD-ROUNDING = CONFIRMED):**

```text
PERIOD_GRADE_STORAGE  = INTEGER_ONLY
DECIMAL_PERIOD_GRADES = NO
ROUNDING_MODE         = HALF_UP
ROUNDING_THRESHOLD    = 0.5

PERIOD_GRADE_FINAL = ROUND_HALF_UP(PERIOD_GRADE_RAW)
```

Ejemplos: `6.49→6`, `6.50→7`, `7.50→8`, `3.50→4`. La clasificación académica se
aplica a `PERIOD_GRADE_FINAL` (entero) DESPUÉS del redondeo.

---

### D-FID157-RENAME

```text
D-FID157-RENAME

FACTS =
  - fid 157, course 15, type = 'news', section = 0 (anuncios,
    mdl_course_sections.id=200), cmid=238.
  - name = "TP-00-Programa de la asignatura".
  - grade_forum = 0 (no calificable).
  - intro = "En éste enlace se accede al Proyecto de catedra" + enlace
    a Google Doc del programa.
  - Sin grade_item asociado (grade_items del curso: solo "Diseño
    Curricular" (assign) + ítem de curso).

EVIDENCE =
  - forum.type='news' → foro de anuncios, NO TP.
  - section=0 → zona de anuncios generales del curso.
  - intro = programa de cátedra (documento), no una consigna de TP.
  - grade_forum=0 → no calificable.

CLASSIFICATION = SYSTEM_OR_ANNOUNCEMENT (NO es TP calificable).
CURRENT_NAME   = "TP-00-Programa de la asignatura"
TARGET_NAME    = "Programa de la asignatura"

RECOMMENDATION =
  Quitar el prefijo "TP-00-". Técnicamente corresponde porque es un
  foro de anuncios. El prefijo "TP-" induce clasificación falsa como
  Trabajo Práctico bajo consultas `name LIKE 'TP-%'` (p. ej. la acción
  `setupgrades`, línea 298, que hoy NO excluye `type='news'`).

USER_DECISION = CONFIRMED (renombre conceptual; NO ejecutar rename en Moodle aún)
```

**Detalle del riesgo del prefijo** (para que Alberto decida con base):

- **Parser nuevo** (gramática `TP-CEE-NN`, 3+2 dígitos): `"TP-00-Programa…"` **no matchea** (el grupo 1 tiene solo 2 dígitos) → queda `invalid_format`, se excluye y se reporta. El parser nuevo ya lo maneja con seguridad.
- **Código actual** (`setupgrades` y el listado principal filtran `name LIKE 'TP-%'` sin excluir `news`): `"TP-00-…"` sí es capturado → riesgo real de habilitar calificación (`grade_forum=10`) sobre un foro de anuncios. Esto es un **bug ya identificado** en el DESIGN anterior.
- Por lo tanto el renombre es recomendable **además** de corregir `setupgrades` para que excluya `type='news'` (ambas cosas son complementarias, no excluyentes).

No se renombra nada en esta ejecución (solo se propone).

---

### D-ENC13-SECTION-NAME

```text
D-ENC13-SECTION-NAME

USER_DECISION (RECTIFICADA) = TWO_DISTINCT_ENCOUNTERS

FACTS =
  - Sección id 213, section=13, name "Encuentro 13-Coordenadas Cartesianas
    en la Programación Creativa con p5.js", sequence="264" (fid 173).
  - Sección id 214, section=14, name "Encuentro 13-El Rectángulo y las
    Coordenadas Cartesianas en p5.js", sequence="265" (fid 174 "TP-13-1-Crear
    un patrón visual").
  - Sección id 215, section=15, name "Encuentro 14- Vamos a explorar la Página
    de Referencias", sequence="266,267" (fid 175, fid 176).
  - Sección id 216, section=16, name "Encuentro 15 - El Color" (fid 177).

EVIDENCE =
  - Numeración de sección (autoritativa): 12, 13, 14, 15, 16, 17.
  - Etiquetas: "Encuentro 12", "Encuentro 13", "Encuentro 13", "Encuentro 14",
    "Encuentro 15", "Encuentro 16" (la etiqueta "13" aparece DOS veces).
  - fid 174 se llama "TP-13-1-…"; fid 173 no tiene prefijo TP.
  - intros: fid 173 = "coordenadas cartesianas" (sketch); fid 174 = "Crear un
    patrón visual" (rectángulos + coordenadas).

INFERENCE =
  Las secciones 13 y 14 son DOS Encuentros DISTINTOS (USER_DECISION). El código
  canónico del SEGUNDO ("El Rectángulo", fid 174) NO es determinable unívocamente
  con la evidencia disponible, porque ya existe un "Encuentro 14" posterior.

OPTION_A = fid 174 → "Encuentro 14" (cascade): reencuadrar sec 15..24 como
           Encuentros 15..24 (fid 175→215, fid 176→215, fid 177→216, …,
           fid 185→224).
OPTION_B = fid 174 → otro código a definir por Alberto (no asume cascade).

RECOMMENDATION = OPTION_A (consistente con la numeración de sección).
CONFIDENCE    = HIGH (confirmada por USER_DECISION OPTION_A)
USER_DECISION = CONFIRMED → OPTION_A (D-SECOND-CURRENTLY-NAMED-ENC13-CANONICAL-CODE)
```

> **INVALIDATED_BY_USER_DECISION:** la conclusión anterior "secciones 13 y 14 =
> mismo Encuentro 013 (fid 173 = TP-013-01, fid 174 = TP-013-02)" queda
> SUPERSEDED. La evidencia histórica se conserva solo como registro.

---

## USER_DECISION_PACKET (para Alberto)

Sin decisiones pendientes.

**✅ TODAS RESUELTAS (2026-08-17):**
- **D-GRADING-AGGREGATION**: cada TP independiente, igual peso (`10/N`), sin agrupar.
- **D-FID168-169-ORDINAL**: fid 167 = TP-208-01, fid 168 = TP-208-02, fid 169 = TP-208-03.
- **D-FID157-RENAME**: fid 157 → "Programa de la asignatura" (news, NO TP).
- **D-PERIOD-ROUNDING**: entero, HALF_UP (umbral 0.5).
- **D-SECOND-CURRENTLY-NAMED-ENC13-CANONICAL-CODE** = OPTION_A (cascade): fid 174 =
  TP-214-01; fid 175..185 = TP-215..224.

---

## Cierre

```text
ROOT_CAUSE          = N/A (no se identificó defecto nuevo; se produjo evidencia)
FILES_CHANGED       = docs/06-SDD/moodle-period-grading-decisions-evidence-2026-08-17.md
                      docs/06-SDD/moodle-period-grading-specification-amendment-2026-08-17.md
                      (ambos artefactos de SPECIFICATION rectificados)
DATABASE_CHANGED    = NO
DOCKER_CHANGED      = NO
VERIFICATION_RESULT = PASS (evidencia leída; 0 escrituras de BD; 0 calificaciones alteradas)
GIT_STATUS          = branch feature/copia-local-moodle @ 809ba63 (worktree sucio preexistente;
                      archivos sin commit)
PRODUCTION_IMPACT   = NONE
IMPLEMENTATION      = NOT_AUTHORIZED
```

**Estado final:**

```text
STATUS = SPECIFICATION_COMPLETED
SPECIFICATION_STATUS = COMPLETED
SPECIFICATION_READY_FOR_DESIGN = YES
USER_DECISIONS_STILL_REQUIRED_COUNT = 0
DESIGN = NOT_STARTED
IMPLEMENTATION = NOT_AUTHORIZED
```

No avanzo a DESIGN por iniciativa propia. Quedo detenido esperando la autorización
de Alberto para iniciar la fase DESIGN.
