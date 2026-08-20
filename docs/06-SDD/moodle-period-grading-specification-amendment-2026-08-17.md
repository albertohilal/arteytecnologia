# SDD_MOODLE_PERIOD_GRADING — SPECIFICATION AMENDMENT

> Fecha: 2026-08-17 · Cambio: `moodle-period-grading` · Modelo: `TP-CEE-NN`

---

```
SDD_MOODLE_PERIOD_GRADING =
IN_PROGRESS

FASE =
SPECIFICATION_AMENDMENT

IDENTIFIER_MODEL =
TP-CEE-NN
  C  = código de período (0 = PERIOD_1, 2 = PERIOD_2).
  EE = número real del Encuentro (2 dígitos, 01..24).
  NN = ordinal del TP dentro del Encuentro (2 dígitos).
```

## ENCOUNTER_SEMANTICS

El número principal identifica el **ENCUENTRO/CLASE**, no el TP secuencial. En un
mismo Encuentro pueden coexistir VARIOS TP. La evidencia de CEE en `courseid=15` es
el nombre de sección `mdl_course_sections.name` = `"Encuentro NN"` (secuencia
docente 01..24). Nota: hay dos secciones rotuladas `"Encuentro 13"` (sec 13 y 14),
que por USER_DECISION son DOS Encuentros DISTINTOS (ver RECTIFICATION_HISTORY).

## TP_WITHIN_ENCOUNTER_SEMANTICS

`NN` es el ordinal del TP dentro de su Encuentro, empezando canónicamente en `01` y
continuando `01, 02, 03…`. Debe ser **ÚNICO** dentro del Encuentro. Un Encuentro
puede tener **0 TP** (sin foro), **1 TP**, o **varios TP**.

## PERIOD_MAPPING

```
PERIOD_FROM_ENCOUNTER(CEE):
  C = 0  (001..099) → PERIOD_1
  C = 2  (200..299) → PERIOD_2
  resto             → NO_PERIOD
```

El período se deriva **exclusivamente** del dígito `C` (primera posición de `CEE`).
Corte de período (USER_DECISION confirmada): **Encuentro 08 = inicio del 2º
cuatrimestre**:

```text
PERIOD_1_ENCOUNTER_RANGE = 01..07  (C = 0)
PERIOD_2_ENCOUNTER_RANGE = 08..24  (C = 2)
```

`EE` y `NN` no influyen en el período.

## CANONICAL_GRAMMAR

```
^TP-([0-9]{3})-([0-9]{2})(?:\s*-\s*.*)?$
```

- grupo 1 = `CEE` (C = período 0|2 + EE = Encuentro 2 dígitos)
- grupo 2 = `NN` (TP dentro del Encuentro, 2 dígitos)
- sufijo opcional = `" - descripción"` (cosmético, no afecta la clasificación)

`parse_tp_identifier(name)` → `{ encounter_number, tp_number_within_encounter,
period, valid, error }`:

- no matchea la gramática → `valid=false`, `error='invalid_format'`
- matchea pero `PERIOD_FROM_ENCOUNTER(CEE)=NO_PERIOD` → `valid=false`,
  `error='encounter_out_of_period'`
- matchea y período válido → `valid=true`

## COURSE15_REINTERPRETATION

(CEE inferido desde `mdl_course_sections.name` "Encuentro NN"; C = período (0|2),
EE = número de Encuentro; NN = orden dentro del Encuentro; evidencia = nombre de
sección + orden en sequence + intro.)

```
fid  cmid sec  current_name                     cur_base cur_sub  CEE   NN  proposed      conf    decision
158  243  201  TP-01- 1-Artistas multimedia      01      1        001   01  TP-001-01     HIGH    NO
159  244  201  TP-01-2-capturas del encuentro    01      2        001   02  TP-001-02     HIGH    NO
160  247  202  TP-02-1-Instante decisivo         02      1        002   01  TP-002-01     HIGH    NO
161  248  202  TP-02-2- Espacio y Tiempo...      02      2        002   02  TP-002-02     HIGH    NO
162  249  202  TP-02-3-Secuencia breve           02      3        002   03  TP-002-03     HIGH    NO
163  250  203  TP-03-1- Creación de un video...  03      1        003   01  TP-003-01     HIGH    NO
164  253  206  TP-06-1- Los siete planos...      06      1        006   01  TP-006-01     HIGH    NO
165  254  206  TP-06-2- Los ángulos de toma...   06      2        006   02  TP-006-02     HIGH    NO
166  255  207  TP-07-1-Mi primer animacion       07      1        007   01  TP-007-01     HIGH    NO
167  256  208  TP-08-1-Editor Web de P5js        08      1        208   01  TP-208-01     HIGH    NO
168  257  208  TP-08-2-Ejemplos oficiales p5.js  08      2        208   02  TP-208-02     HIGH    YES(ordinal)
169  258  208  TP-08-2-Sketch en el Editor...    08      2(dup)   208   03  TP-208-03     HIGH    YES(ordinal)
170  259  209  TP-09-Los primeros sketch         09      -        209   01  TP-209-01     HIGH    NO
171  261  211  TP-11-Explorando Figuras 2D       11      -        211   01  TP-211-01     HIGH    NO
172  263  212  TP- 12-01- Parámetros y Arg...    12      01       212   01  TP-212-01     HIGH    NO
173  264  213  Coordenadas cartesianas           -       -        213   01  TP-213-01     HIGH    YES(prev 025)
174  265  214  TP-13-1-Crear un patrón visual    13      1        214   01  TP-214-01     HIGH    YES(cascade)
175  266  215  TP-14-1-Dibujar en el Editor...   14      1        215   01  TP-215-01     HIGH    YES(cascade)
176  267  215  TP-14-2-ejercicio                 14      2        215   02  TP-215-02     HIGH    YES(cascade)
177  268  216  TP-15-1-El Color                  15      1        216   01  TP-216-01     HIGH    YES(cascade)
178  269  217  TP-12-Practica de Consola...      12      -        217   01  TP-217-01     HIGH    YES(relabel)
179  270  218  TP-13-Practica de variables       13      -        218   01  TP-218-01     HIGH    YES(relabel)
180  271  219  TP-14-Ejercicios Variables Pers.  14      -        219   01  TP-219-01     HIGH    YES(relabel)
181  272  220  TP-15-Cada vez que hagas clic...  15      -        220   01  TP-220-01     HIGH    YES(relabel)
182  273  221  TP-16-Ejercicio Funcion map()     16      -        221   01  TP-221-01     HIGH    YES(relabel)
183  274  222  TP-17-Ejercicio createGraphics()  17      -        222   01  TP-222-01     HIGH    YES(relabel)
184  275  223  TP-18-Subir imágenes al Editor    18      -        223   01  TP-223-01     HIGH    YES(relabel)
185  276  224  TP-19-Ejercicio condicionales...  19      -        224   01  TP-224-01     HIGH    YES(relabel)
```

Notas:

- fid 174 (`"TP-13-1-Crear un patrón visual"`, sec 14) → Encuentro 14 (TP-214-01).
- fid 175..185 → cascade OPTION_A (Encuentros 15..24). El "offset -4" de la 2ª
  mitad queda absorbido: desde la sección 13, la sección N coincide con el
  Encuentro N.

## MULTIPLE_TP_PER_ENCOUNTER_ANALYSIS

Encuentros con VARIOS TP (genuinos, confirmados por sección + intros):

- **Encuentro 001** (Presentación): 2 TP → TP-001-01 (listar artistas multimedia),
  TP-001-02 (capturas del encuentro). Consignas distintas.
- **Encuentro 002** (Espacio/tiempo): 3 TP → TP-002-01 (Instante decisivo),
  TP-002-02 (Espacio y Tiempo), TP-002-03 (Secuencia breve). Consignas distintas.
- **Encuentro 006** (Fotografía): 2 TP → TP-006-01 (siete planos), TP-006-02
  (ángulos de toma). Distintos.
- **Encuentro 208** (P5js y su editor): 3 TP → TP-208-01 (editor web), TP-208-02
  (ejemplos oficiales), TP-208-03 (sketch propio). fid 169 está etiquetado `-2`
  duplicado; debe ser `-03`.
- **Encuentro 215** (Página de Referencias): 2 TP → TP-215-01 (Dibujar), TP-215-02
  (ejercicio).

Resto de Encuentros: 1 TP.

> **RECTIFICADO (SUPERSEDED):** la entrada anterior "Encuentro 013 = 2 TP
> (fid 173 + fid 174)" queda **INVALIDADA** por USER_DECISION: secciones 13 y 14
> son DOS Encuentros DISTINTOS, no dos TP del mismo Encuentro. fid 173 = Encuentro
> 213 (1 TP); fid 174 = Encuentro 14 (TP-214-01).

## APPARENT_DUPLICATES_STATUS

Bajo el modelo TP-CEE-NN los "duplicados" de NOMBRE NO son anomalías automáticas:

- `01(×2), 02(×3), 06(×2), 08(×3)` → VARIOS TP del MISMO Encuentro (correcto).
- `14(×2)` → 2 TP del mismo Encuentro 215 (fid 175 "TP-14-1" + fid 176 "TP-14-2").
- `13` → fid 174 se llama "TP-13-1", pero NO es el Encuentro 13: es el Encuentro 14
  (fid 174 = TP-214-01). fid 173 (sin prefijo TP) es el Encuentro 13 (TP-213-01).
  **RECTIFICADO**: dos Encuentros DISTINTOS.

La 2ª mitad del curso presenta un **OFFSET de etiquetado**: los nombres
"TP-12..TP-19" (fid 178..185) corresponden canónicamente a los Encuentros 17..24
(TP-217-01 .. TP-224-01), no a los Encuentros 12..19.

## APPARENT_MISSING_NUMBERS_STATUS

`04, 05, 10` NO son TPs faltantes: son **ENCUENTROS SIN foro-TP**:

- Encuentro 04 ("El video parte 2") → sección vacía (0 TP).
- Encuentro 05 ("Editores de Audio y Video") → solo URL, sin foro (0 TP).
- Encuentro 10 ("Diferencias JavaScript y P5js") → sección vacía (0 TP).

Un Encuentro puede tener 0 TP; esto es válido en el modelo.

## FID157_CLASSIFICATION

`SYSTEM_OR_ANNOUNCEMENT`. `type='news'`, sección 0 (anuncios), contenido = enlace al
programa de cátedra. NO TP, NO grade_forum, NO grade_item, NO cálculo de período.

## FID173 (Coordenadas cartesianas)

```
FID173_ENCOUNTER            = 213
FID173_TP_WITHIN_ENCOUNTER  = 01
FID173_CONFIDENCE           = HIGH
FID173_STATUS               = CONFIRMED (primer Encuentro 13)
```

Evidencia: sección `"Encuentro 13-Coordenadas Cartesianas en la Programación
Creativa con p5.js"` contiene ÚNICAMENTE fid 173. fid 173 es el TP del primer
"Encuentro 13" (código 213). La inferencia anterior `TP-025` queda **INVALIDADA**.

> **RECTIFICADO (SUPERSEDED):** la afirmación anterior "fid 173 = TP-013-01 y
> fid 174 = TP-013-02 (mismo Encuentro 013)" queda **INVALIDADA** por USER_DECISION
> `TWO_DISTINCT_ENCOUNTERS` + OPTION_A: fid 174 = Encuentro 14 (TP-214-01).

## PROPOSED_NORMALIZATION_MAP

```
CURRENT_NAME → ENCOUNTER(CEE) → TP_WITHIN → PROPOSED_CANONICAL → CONFIDENCE → DECISION

TP-01- 1-Artistas multimedia        → 001 → 01 → TP-001-01  HIGH  NO
TP-01-2-capturas del encuentro      → 001 → 02 → TP-001-02  HIGH  NO
TP-02-1-Instante decisivo           → 002 → 01 → TP-002-01  HIGH  NO
TP-02-2- Espacio y Tiempo...        → 002 → 02 → TP-002-02  HIGH  NO
TP-02-3-Secuencia breve             → 002 → 03 → TP-002-03  HIGH  NO
TP-03-1- Creación de un video...    → 003 → 01 → TP-003-01  HIGH  NO
TP-06-1- Los siete planos...        → 006 → 01 → TP-006-01  HIGH  NO
TP-06-2- Los ángulos de toma...     → 006 → 02 → TP-006-02  HIGH  NO
TP-07-1-Mi primer animacion         → 007 → 01 → TP-007-01  HIGH  NO
TP-08-1-Editor Web de P5js          → 208 → 01 → TP-208-01  HIGH  NO
TP-08-2-Ejemplos oficiales p5.js    → 208 → 02 → TP-208-02  HIGH  YES (ordinal)
TP-08-2-Sketch en el Editor...      → 208 → 03 → TP-208-03  HIGH  YES (ordinal)
TP-09-Los primeros sketch           → 209 → 01 → TP-209-01  HIGH  NO
TP-11-Explorando Figuras 2D         → 211 → 01 → TP-211-01  HIGH  NO
TP- 12-01- Parámetros y Argumentos  → 212 → 01 → TP-212-01  HIGH  NO
Coordenadas cartesianas             → 213 → 01 → TP-213-01  HIGH  YES (prev 025)
TP-13-1-Crear un patrón visual      → 214 → 01 → TP-214-01  HIGH  YES (cascade)
TP-14-1-Dibujar en el Editor        → 215 → 01 → TP-215-01  HIGH  YES (cascade)
TP-14-2-ejercicio                   → 215 → 02 → TP-215-02  HIGH  YES (cascade)
TP-15-1-El Color                    → 216 → 01 → TP-216-01  HIGH  YES (cascade)
TP-12-Practica de Consola...        → 217 → 01 → TP-217-01  HIGH  YES (relabel)
TP-13-Practica de variables         → 218 → 01 → TP-218-01  HIGH  YES (relabel)
TP-14-Ejercicios Variables Pers.    → 219 → 01 → TP-219-01  HIGH  YES (relabel)
TP-15-Cada vez que hagas clic...    → 220 → 01 → TP-220-01  HIGH  YES (relabel)
TP-16-Ejercicio Funcion map()       → 221 → 01 → TP-221-01  HIGH  YES (relabel)
TP-17-Ejercicio createGraphics()    → 222 → 01 → TP-222-01  HIGH  YES (relabel)
TP-18-Subir imágenes al Editor      → 223 → 01 → TP-223-01  HIGH  YES (relabel)
TP-19-Ejercicio condicionales...    → 224 → 01 → TP-224-01  HIGH  YES (relabel)
TP-00-Programa de la asignatura     → (news) → UNDETERMINED → NO TP (rename CONFIRMADO)
```

Notas:

- fid 174 (`TP-13-1-Crear un patrón visual`) → Encuentro 14 (cascade OPTION_A).
- fid 175..185 → Encuentros 15..24 (cascade OPTION_A).
- fid 157 (`TP-00-Programa de la asignatura`, type=news) → renombre CONFIRMADO a
  `"Programa de la asignatura"`; NO es TP calificable.

## AMBIGUOUS_CASES

Resueltos por USER_DECISION (2026-08-17):

1. fid 168 vs 169 → **CONFIRMADO**: fid 168 = TP-208-02, fid 169 = TP-208-03
   (`D-FID168-169-ORDINAL`).
2. fid 173 → **CONFIRMADO**: fid 173 = TP-213-01 (primer Encuentro 13).
3. `"Encuentro 13"` duplicado (sec 13/14) → **RECTIFICADO**: `TWO_DISTINCT_ENCOUNTERS`
   + OPTION_A → fid 174 = Encuentro 14 (TP-214-01).
4. Relabel de la 2ª mitad → **CONFIRMADO** (cascade OPTION_A): fid 178-185 =
   Encuentros 217-224.
5. Límite PERIOD_1/PERIOD_2 → **CONFIRMADO**: Encuentro 08 = inicio del 2º período
   (código `2xx`).

Sin casos pendientes.

## VALID_GRADED_TP_SPEC

```
VALID_GRADED_TP(activity) :=
      activity es instancia de foro (mod 'forum')
  AND activity.type != 'news'
  AND parse_tp_identifier(activity.name).valid == true
  AND PERIOD_FROM_ENCOUNTER(CEE) != NO_PERIOD
```

(Una sola definición reutilizada por `setupgrades`, `collect_period_forums`,
cálculo de período y render/UI.)

## SETUPGRADES_REQUIRED_BEHAVIOR

Seleccionar SOLO foros que cumplen `VALID_GRADED_TP` (no news, TP-CEE-NN válido,
CEE en rango de período) y aplicar `grade_forum=10` + `forum_grade_item_update`.
Los foros con prefijo "TP-" que NO cumplan (2 dígitos, formato viejo, news, fuera
de rango) se EXCLUYEN y se reportan; nunca se califican silenciosamente.

## COLLECT_PERIOD_FORUMS_REQUIRED_BEHAVIOR

Usar la MISMA definición `VALID_GRADED_TP` + `PERIOD_FROM_ENCOUNTER` para agrupar
por período. Mantener el gate `grade_forum===10.0` para "efectivamente calificable".
Los inválidos se excluyen y reportan.

## INVALID_IDENTIFIER_BEHAVIOR

`FAIL_SAFE_REPORT`. Cualquier actividad cuyo identificador no pueda interpretarse
de forma no ambigua (ej.: TP-105, TP-99, TP-ABC, TP-08-2 duplicado, nombre sin TP)
NO entra al cálculo automático y se reporta como anomalía pendiente de
rename/definición.

## GRADING_AGGREGATION_DECISION

`D-GRADING-AGGREGATION` **CONFIRMED** por Alberto (USER_DECISION, 2026-08-17).

```
GRADING_UNIT               = TP
EACH_TP_WEIGHT             = EQUAL_WITHIN_PERIOD
GROUP_BY_ENCOUNTER         = NO
TP_NUMERIC_GRADES_AVERAGED = NO
```

Semántica:

- Cada Trabajo Práctico es una unidad calificable **independiente**.
- Todos los TP tienen **exactamente el mismo peso**.
- Un Encuentro puede contener **varios TP**.
- Que varios TP pertenezcan al mismo Encuentro **NO provoca agrupación**.
- **NO** se promedian las calificaciones numéricas de los TP para obtener la
  nota del período.

(Coincide con la OPTION_A documentada en el informe de evidencia; es el
comportamiento actual del código. La nota del período se calcula sobre la
cantidad de TP del período, sin agrupar por Encuentro.)

## TP_PERIOD_CONTRIBUTION_RULE

Aporte de cada TP al período — **USER_DECISION** confirmada por Alberto
(2026-08-17). Refina `D-GRADING-AGGREGATION` (peso dinámico) y resuelve
`TP_GRADE_GE_4_STATUS`.

### TP_WEIGHT_RULE

```
EACH_TP_WEIGHT       = EQUAL_WITHIN_PERIOD
TP_WEIGHT_IS_DYNAMIC = YES
TP_WEIGHT            = 10 / TOTAL_VALID_TP_IN_PERIOD
```

Todos los TP válidos de un mismo período tienen exactamente el mismo peso. El
peso NO es fijo; se calcula dinámicamente según la cantidad total de TP válidos
del período (`N = TOTAL_VALID_TP_IN_PERIOD`): `N=10 → 1 punto`, `N=5 → 2 puntos`,
`N=20 → 0.5 puntos`, `N=8 → 1.25 puntos`.

### TP_PERIOD_CONTRIBUTION

```
TP_PRESENTED_AND_EVALUATED → aporta el 100% de TP_WEIGHT al período
TP_NOT_PRESENTED           → aporta 0 al período
```

La nota numérica individual del TP NO modifica el peso que ese TP aporta al
período. No se promedian las notas individuales (ej. `10, 7, 5, 4`) para obtener
la nota del período.

```
TP_FINAL_GRADE           = evaluación cualitativa/numerizada del trabajo individual

TP_PERIOD_CONTRIBUTION =
  PRESENTED_AND_EVALUATED → TP_WEIGHT
  NOT_PRESENTED           → 0
```

### PERIOD_GRADE_FORMULA

```
TOTAL_VALID_TP    = N
COUNTED_TP        = cantidad de TP PRESENTED_AND_EVALUATED

TP_WEIGHT         = 10 / N
PERIOD_GRADE_RAW  = COUNTED_TP * TP_WEIGHT
```

Equivalente: `PERIOD_GRADE_RAW = (COUNTED_TP / TOTAL_VALID_TP) * 10`. Las dos
expresiones son matemáticamente equivalentes. No agrupar por Encuentro. No
promediar las notas individuales de los TP.

### PERIOD_ROUNDING

`D-PERIOD-ROUNDING` **CONFIRMED** por Alberto (USER_DECISION, 2026-08-17):

```
PERIOD_GRADE_STORAGE  = INTEGER_ONLY
DECIMAL_PERIOD_GRADES = NO
ROUNDING_MODE         = HALF_UP
ROUNDING_THRESHOLD    = 0.5

PERIOD_GRADE_FINAL = ROUND_HALF_UP(PERIOD_GRADE_RAW)
```

Semántica: decimal `< 0.5` → entero inferior; decimal `>= 0.5` → entero superior.
Ejemplos normativos: `6.49→6`, `6.50→7`, `7.49→7`, `7.50→8`, `3.49→3`, `3.50→4`.

No se conservan decimales en la nota final del período. El comportamiento `round()`
actual debe verificarse en DESIGN contra esta regla explícita (no se modifica
código en esta fase).

## PERIOD_ACADEMIC_CLASSIFICATION

Regla académica por período (**USER_DECISION**, 2026-08-17). La condición
académica se determina para **CADA período de manera independiente** según su nota:

```
PERIOD_CLASSIFICATION_SCOPE = EACH_PERIOD_INDEPENDENTLY

[7,10]  → PROMOCION
[4,7)   → EXAMEN_FINAL_DICIEMBRE_O_MARZO
[0,4)   → RECURSA
```

La clasificación se aplica a `PERIOD_GRADE_FINAL` (entero, DESPUÉS del redondeo).
Equivalente entero: `7..10 → PROMOCION`, `4..6 → EXAMEN_FINAL_DICIEMBRE_O_MARZO`,
`0..3 → RECURSA`.

NO se define aquí ninguna regla anual que combine P1 y P2. Cualquier resultado
anual/final de la materia a partir de P1 y P2 requerirá una decisión específica
de usuario. No se infieren fórmulas como `(P1+P2)/2` ni combinaciones booleanas
del tipo `P1>=7 AND P2>=7`, `P1>=4 AND P2>=4`, `P1<4 OR P2<4`.

## TP_EVALUATION_MODEL

`D-TP-EVALUATION-BEFORE-GRADEBOOK` **CONFIRMED** por Alberto (USER_DECISION,
2026-08-17).

DESARROLLO y PRESENTACIÓN son **CRITERIOS PEDAGÓGICOS** utilizados por el docente
para determinar la calificación individual de un Trabajo Práctico. NO constituyen
datos independientes a registrar en Moodle:

```
EVALUATION_CRITERIA =
  DEVELOPMENT  = DESARROLLO_DEL_EJERCICIO   (ADECUADO | NO_ADECUADO)
  PRESENTATION = PRESENTACION_DEL_TRABAJO   (COMPLETA | INCOMPLETA)

CRITERIA_ARE_PEDAGOGICAL_ONLY = YES
CRITERIA_ARE_UI_CONTROLS      = NO
CRITERIA_PERSISTED_AS_DATA    = NO
```

Matriz de rangos **ORIENTATIVOS** (referencia pedagógica, NO fórmula automática):

```
EVALUATION_RANGES =
  ADECUADO    + COMPLETA    → 9–10
  ADECUADO    + INCOMPLETA  → 7–8
  NO_ADECUADO + COMPLETA    → 5–6
  NO_ADECUADO + INCOMPLETA  → 4

LAST_BAND_4_ONLY = YES
  (INVALIDADO_BY_USER_VERIFICATION: la banda "4–5" queda SUPERSEDED;
   la banda final es EXACTAMENTE 4, NO 4–5.)

RANGES_ARE_ORIENTATIVE   = YES
AUTOMATIC_FINAL_TP_GRADE = NO
TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES
```

Los rangos NO constituyen una fórmula matemática automática. No determinan
automáticamente una nota concreta (7, 8, 7.5, …). La nota final del TP la
selecciona/edita el docente.

## TP_EVALUATION_WORKFLOW

> **RECTIFICADO 2026-08-18 (INVALIDATED_BY_USER_VERIFICATION).** El flujo anterior
> `CRITERIA_SELECTION → ORIENTATIVE_RANGE → ...` queda SUPERSEDED: la selección de
> criterios NO es una operación realizada dentro del reporte.

```
TP_EVALUATION_WORKFLOW =

PEDAGOGICAL_ASSESSMENT
→ TEACHER_GRADE_EDIT
→ TEACHER_CONFIRMATION
→ GRADEBOOK_WRITE
```

donde:

```
PEDAGOGICAL_ASSESSMENT =
  evaluación conceptual realizada por el docente utilizando criterios de
  Desarrollo + Presentación. NO es un estado persistido del sistema.
```

Reglas obligatorias:

```
TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES
GRADE_IS_EDITABLE_BEFORE_SAVE        = YES

GRADEBOOK_WRITE_ON_ASSESSMENT        = NO
GRADEBOOK_WRITE_REQUIRES_EXPLICIT_TEACHER_SAVE = YES
```

La evaluación conceptual (Desarrollo + Presentación) es criterio del docente para
determinar la nota; NO produce datos auxiliares persistidos ni controles de interfaz.

## CONCEPT_SEPARATION

```
TP_EVALUATION  = evaluación pedagógica del trabajo (desarrollo + presentación)
TP_FINAL_GRADE = nota 0–10 finalmente determinada y confirmada por el docente
PERIOD_GRADE   = resultado posterior correspondiente al período
```

NO usar la matriz de evaluación del TP para calcular automáticamente la
calificación del período. No se infiere regla nueva para PERIOD_GRADE aquí.

## GRADEBOOK_WRITE_POLICY

La escritura del TP al Gradebook se produce únicamente tras una acción explícita
del docente (GUARDAR / CONFIRMAR):

```
GRADEBOOK_WRITE_REQUIRES_EXPLICIT_CONFIRMATION = YES
NO_SILENT_GRADE_OVERWRITE = YES
```

Antes de la confirmación:

```
GRADEBOOK_CHANGED = NO
grade_items       = UNCHANGED
grade_grades      = UNCHANGED
```

Si ya existe una calificación oficial para el TP:

```
EXISTING_GRADEBOOK_GRADE = READ
```

(se muestra sin sobrescribir; cualquier modificación requiere nueva confirmación
explícita del docente).

La futura implementación usará APIs oficiales de Moodle conforme a
`.opencode/skills/moodle-grading/SKILL.md`. No se diseñará SQL directo de
escritura.

## TP_EVALUATION_FORM_FUNCTIONAL_MODEL

Representación **funcional** (NO HTML/CSS/widgets/implementación).

> **RECTIFICADO 2026-08-18 (INVALIDATED_BY_USER_VERIFICATION).** El modelo anterior
> con selects `Desarrollo` / `Presentación` y `Rango orientativo` queda SUPERSEDED.
> La celda del TP conserva el modelo previo de calificación individual:

```
TP
------------------------------------------------
enlaces existentes del TP

calificación individual editable 0..10

[ Guardar ]

/10
```

Representación funcional por celda:

```
TP-001-01
Ver
[ 8 ] [ Guardar ] /10
```

NO se agregan: `Desarrollo` (select), `Presentación` (select), `Rango orientativo`.

La nota individual del TP sigue siendo determinada por el docente
(TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES).

## TP_DELIVERED_THRESHOLD

```
CURRENT_IMPLEMENTATION = FACT
  reporte_tp_compute_period_grades() considera un TP como contado
  ("delivered") cuando forum grade >= 4.

TP_GRADE_GE_4_STATUS        = CONFIRMED_AS_CURRENT_PROXY
TP_GRADE_GE_4_MEANS         = PRESENTED_AND_EVALUATED_FOR_PERIOD_COUNT
TP_GRADE_GE_4_DOES_NOT_MEAN = INDIVIDUAL_TP_APPROVAL_FOR_AVERAGING
```

Semántica (**USER_DECISION**, 2026-08-17):

```text
grade >= 4
→ TP_PRESENTED_AND_EVALUATED → cuenta para el período

sin calificación / no presentado
→ TP_NOT_PRESENTED → no cuenta para el período
```

`grade >= 4` NO significa "TP aprobado": el cálculo del período se basa en la
cantidad de TP presentados/evaluados, no en un promedio de calificaciones
individuales.

## TERMINOLOGY_ISSUE

```
TERMINOLOGY_ISSUE_STATUS = SPECIFIED_FOR_DESIGN
```

"delivered" / "perioddelivered" / "entregados" es semánticamente ambiguo: puede
significar "entrega realizada", mientras que el código actualmente lo utiliza como
"forum grade >= 4". El término `delivered` se mantiene como FACT del código
existente, pero para DESIGN se recomienda terminología que represente la regla
pedagógica confirmada:

```text
counted
presented
presented_and_evaluated
```

No se renombran variables en esta fase (corresponde a DESIGN/IMPLEMENTATION).

## RECTIFICATION_HISTORY

Rectificaciones de SPECIFICATION por USER_DECISION (2026-08-17). Las afirmaciones
previas listadas abajo quedan **SUPERSEDED / INVALIDATED_BY_USER_DECISION** y NO
deben considerarse regla vigente:

```
SUPERSEDED_1 = "fid 168 = TP-008-02, fid 169 = TP-008-03"
  → INVALIDADO por codificación de período: Encuentro 08 = 2º período
    → TP-208-02 / TP-208-03.

SUPERSEDED_2 = "section 13 + section 14 = mismo Encuentro 013"
  → INVALIDADO por D-ENC13-SECTION-NAME = TWO_DISTINCT_ENCOUNTERS.

SUPERSEDED_3 = "fid 173 = TP-013-01 y fid 174 = TP-013-02 (2 TP del Encuentro 013)"
  → INVALIDADO: fid 173 = TP-213-01; fid 174 = Encuentro 14 (TP-214-01).

SUPERSEDED_4 = modelo "TP-EEE-NN" (EEE = número de Encuentro, 001-099 = P1)
  → RECTIFICADO a TP-CEE-NN (C = período 0|2, EE = Encuentro). Encuentros 08+ = 2xx.

SUPERSEDED_5 = "fid 174 = TP-2??-01 (PENDING); fid 175..185 = TP-214..223 (pre-cascade)"
  → INVALIDADO por OPTION_A: fid 174 = TP-214-01; fid 175..185 = TP-215..224.

SUPERSEDED_6 = "UI del TP con selects Desarrollo/Presentación + rango orientativo
  por celda (UNIT-4)"  [2026-08-18]
  → INVALIDADO_BY_USER_VERIFICATION: Desarrollo/Presentación son criterios
    pedagógicos, NO controles de interfaz. UNIT-4 = TP_GRADE_CELL_UI (enlaces +
    nota editable 0..10 + Guardar + /10), sin selects ni rango orientativo por TP.

SUPERSEDED_7 = "matriz orientativa: NO_ADECUADO + INCOMPLETA → 4–5"  [2026-08-18]
  → INVALIDADO_BY_USER_VERIFICATION: la banda final es EXACTAMENTE 4 (NO 4–5).
    LAST_BAND_4_ONLY = YES.
```

## USER_DECISIONS_STILL_REQUIRED

```
USER_DECISIONS_STILL_REQUIRED_COUNT = 0
USER_DECISIONS_STILL_REQUIRED       = NONE
```

Confirmadas (todas resueltas, 2026-08-17):

- **D-FID168-169-ORDINAL** = CONFIRMED: fid 167 = TP-208-01, fid 168 = TP-208-02,
  fid 169 = TP-208-03.
- **D-FID157-RENAME** = CONFIRMED: fid 157 → "Programa de la asignatura"
  (type=news, NO TP; rename no ejecutado aún).
- **D-ENC13-SECTION-NAME** = TWO_DISTINCT_ENCOUNTERS (rectificada).
- **D-SECOND-CURRENTLY-NAMED-ENC13-CANONICAL-CODE** = OPTION_A (cascade): fid 174 =
  TP-214-01; fid 175..185 = TP-215..224.
- **D-PERIOD-ROUNDING** = CONFIRMED: entero, HALF_UP, umbral 0.5.
- **D-TP-EVALUATION-BEFORE-GRADEBOOK** = CONFIRMED.
- **D-GRADING-AGGREGATION** = CONFIRMED (cada TP independiente, peso dinámico 10/N).
- **D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL** = CONFIRMED (2026-08-18): la interfaz de
  período separa PROPUESTA (read-only, no persistida) de CALIFICACION_DOCENTE
  (editable, persistida solo con GUARDAR).
- Baseline: modelo TP-CEE-NN, rango 01..24, período 2 inicia en Encuentro 08.

---

## PERIOD_UI_RECTIFICATION — D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL (2026-08-18)

> Rectificación de SPECIFICATION por USER_DECISION (2026-08-18). Autorizada
> exclusivamente para SPECIFICATION / DESIGN / CURRENT-STATE. **NO autoriza
> IMPLEMENTATION.** No modifica la fórmula del período ni el modelo de evaluación del TP.

```
D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL = CONFIRMED
```

La interfaz de calificación del período debe separar visual y semánticamente
`CALIFICACION_PROPUESTA` de `CALIFICACION_DOCENTE`.

### PERIOD_PROPOSED_GRADE (CALIFICACION_PROPUESTA)

```
PERIOD_PROPOSED_GRADE_READ_ONLY = YES
PERIOD_PROPOSED_GRADE_EDITABLE  = NO
PERIOD_PROPOSED_GRADE_PERSISTED = NO
PERIOD_PROPOSED_GRADE_AUTOMATIC_GRADEBOOK_WRITE = NO

PERIOD_PROPOSED_GRADE_DISPLAY_MINIMUM =
  PERIOD_GRADE_FINAL
  COUNTED_TP / TOTAL_VALID_TP
```

Semántica: `PERIOD_PROPOSED_GRADE` es el resultado calculado automáticamente mediante
la regla vigente del período. READ_ONLY, NO editable, NO persistido. Su existencia NO
escribe automáticamente el gradebook. La fórmula vigente NO cambia:

```
TOTAL_VALID_TP     = N
COUNTED_TP         = TP_PRESENTED_AND_EVALUATED
PERIOD_GRADE_RAW   = (COUNTED_TP / TOTAL_VALID_TP) * 10
PERIOD_GRADE_FINAL = ROUND_HALF_UP(PERIOD_GRADE_RAW)
```

No se promedian calificaciones individuales de TP. No se agrupa por Encuentro.

### PERIOD_TEACHER_GRADE (CALIFICACION_DOCENTE)

```
PERIOD_TEACHER_GRADE_EDITABLE                   = YES
PERIOD_TEACHER_GRADE_RANGE                      = 0..10 (entero)
PERIOD_TEACHER_GRADE_PERSISTED_ON_EXPLICIT_SAVE = YES
PERIOD_TEACHER_GRADE_SILENT_WRITE               = NO

AUTOMATIC_COPY_PROPOSED_TO_TEACHER_GRADE        = NO
TEACHER_CAN_OVERRIDE_PROPOSAL                   = YES
TEACHER_RETAINS_FINAL_AUTHORITY                 = YES
```

Semántica: `PERIOD_TEACHER_GRADE` es la decisión explícita del docente, editable, entero
0..10, persistida SOLO mediante GUARDAR (submit explícito). La existencia de una
propuesta calculada NO escribe automáticamente el mismo valor como calificación docente.

### PROPOSED_GRADE != TEACHER_GRADE

```
PROPOSED_GRADE != TEACHER_GRADE
```

Son dos conceptos diferentes:

- `PROPOSED_GRADE` = resultado automático del sistema (no persistido).
- `TEACHER_GRADE`  = decisión académica persistida.

Pueden coincidir (`PROPOSED=8`, `TEACHER=8`) o diferir (`PROPOSED=8`, `TEACHER=7`) sin
que ello constituya error del sistema. NO se introduce validación que fuerce igualdad.

### PERIOD_UI_MODEL

```
PERIOD_UI_MODEL =

  CUATRIMESTRE_1:
    PROPUESTA
    CALIFICACION_DOCENTE

  CUATRIMESTRE_2:
    PROPUESTA
    CALIFICACION_DOCENTE
```

Representación funcional (ilustrativa de interfaz; NO nuevos datos ni reglas de cálculo):

```
Apellido | Nombre | ...TP... |
C1 propuesta | C1 calificación |
C2 propuesta | C2 calificación
```

Ejemplo conceptual:

```
C1 propuesta:   8 / 10 · 7/9 TP
C1 calificación: [ 8 ]

C2 propuesta:   6 / 10 · 11/19 TP
C2 calificación: [ 7 ]
```

Nomenclatura de interfaz preferida:

```
C1 propuesta · C1 calificación · C2 propuesta · C2 calificación
```

o

```
Cuatrimestre 1 — Propuesta · Cuatrimestre 1 — Calificación
Cuatrimestre 2 — Propuesta · Cuatrimestre 2 — Calificación
```

NO utilizar todavía `Calificación final`, `Nota final anual`, `Resultado anual`: no
existe una fórmula anual definida que combine P1 y P2.

### Impacto en unidades de implementación

```
AFFECTED_FUTURE_UNIT = UNIT-7  (Grade_item de período / override / UI período)
UNIT-4_SCOPE_CHANGED = NO      (UNIT-4 = UI criterios Desarrollo/Presentación del TP
                                individual; separada de la UI de período)
UNIT-7_NOT_ADVANCED  = YES
```

> **Nota (2026-08-18):** la descripción de UNIT-4 en este bloque queda SUPERSEDED por
> TP_EVALUATION_UI_RECTIFICATION: UNIT-4 = TP_GRADE_CELL_UI.

No se adelanta UNIT-7. No se modifica el propósito de UNIT-4 salvo para aclarar la
separación de responsabilidades.

No se reabre: TP-CEE-NN, VALID_GRADED_TP, peso igual por TP, NO promedio de
calificaciones numéricas de TP, TP_WEIGHT = 10 / TOTAL_VALID_TP_IN_PERIOD, HALF_UP,
clasificación académica por período, modelo Desarrollo + Presentación del TP, autoridad
final del docente.

---

## TP_EVALUATION_UI_RECTIFICATION — D-TP-EVALUATION-CRITERIA-PEDAGOGICAL-ONLY / D-TP-UI-NO-CRITERIA-CONTROLS (2026-08-18)

> Rectificación de SPECIFICATION por verificación de usuario (2026-08-18). Autorizada
> exclusivamente para SPECIFICATION / DESIGN / CURRENT-STATE. **NO autoriza
> IMPLEMENTATION.** No modifica la fórmula del período ni el modelo de cálculo.

La UNIT-4 fue implementada según el DESIGN vigente y verificada visualmente por
Alberto en Moodle local. La interfaz mostró `Desarrollo [select]`,
`Presentación [select]` y `Rango orientativo` dentro de cada celda TP. El usuario
confirmó que esa interpretación es **INCORRECTA**.

```
ROOT_CAUSE =
  La SPECIFICATION/DESIGN convirtió criterios pedagógicos utilizados por el
  docente para evaluar un TP en controles obligatorios de interfaz.
  Eso NO representa el requerimiento real.
```

Decisiones confirmadas:

```
D-TP-EVALUATION-CRITERIA-PEDAGOGICAL-ONLY = CONFIRMED
D-TP-UI-NO-CRITERIA-CONTROLS = CONFIRMED

DEVELOPMENT_UI_CONTROL     = NO
PRESENTATION_UI_CONTROL    = NO
DEVELOPMENT_PERSISTED      = NO
PRESENTATION_PERSISTED     = NO

TP_ORIENTATIVE_RANGE_CONTROL   = NO
TP_ORIENTATIVE_RANGE_PERSISTED = NO

AUTOMATIC_FINAL_TP_GRADE = NO
TEACHER_RETAINS_FINAL_GRADE_AUTHORITY = YES
```

Modelo correcto de celda TP:

```
TP_CELL =
  enlaces existentes del TP
  + calificación individual editable 0..10
  + botón Guardar
  + indicador /10
```

```
TP-001-01
Ver
[ 8 ] [ Guardar ] /10
```

Matriz pedagógica vigente (orientativa):

```
TP_EVALUATION_RANGES =
  ADECUADO    + COMPLETA   → 9–10
  ADECUADO    + INCOMPLETA → 7–8
  NO_ADECUADO + COMPLETA   → 5–6
  NO_ADECUADO + INCOMPLETA → 4

LAST_BAND_4_ONLY = YES
```

Flujo pedagógico rectificado:

```
PEDAGOGICAL_ASSESSMENT
→ TEACHER_GRADE_EDIT
→ TEACHER_CONFIRMATION
→ GRADEBOOK_WRITE
```

No se reabre: TP-CEE-NN, VALID_GRADED_TP, peso igual por TP, NO promedio de
calificaciones numéricas de TP, TP_WEIGHT = 10 / TOTAL_VALID_TP_IN_PERIOD, HALF_UP,
clasificación académica por período, D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL. El modelo
pedagógico Desarrollo + Presentación del TP se preserva como criterio del docente,
pero NO como controles de interfaz ni datos persistidos.

---

```
DESIGN_READY                      = YES
DESIGN                            = NOT_STARTED
IMPLEMENTATION                    = NOT_AUTHORIZED
STATUS                            = SPECIFICATION_COMPLETED
SPECIFICATION_STATUS              = COMPLETED
SPECIFICATION_READY_FOR_DESIGN    = YES
USER_DECISIONS_STILL_REQUIRED_COUNT = 0
SPECIFICATION_RECTIFICATION       = COMPLETED  (2026-08-18, D-PERIOD-UI-SEPARATE-PROPOSAL-FINAL
                                    + D-TP-EVALUATION-CRITERIA-PEDAGOGICAL-ONLY
                                    + D-TP-UI-NO-CRITERIA-CONTROLS)
FILES_CHANGED                     = docs/06-SDD/moodle-period-grading-specification-amendment-2026-08-17.md
                                    (artefacto de especificación rectificado)
DATABASE_CHANGED                  = NO
DOCKER_CHANGED                    = NO
PRODUCTION_IMPACT                 = NONE
NEXT_PHASE                        = DESIGN (NO iniciado; espera autorización de Alberto)

BLOCKER_2_STATUS  = PENDING_NOT_ADDRESSED (dump local anterior a calificaciones
                    de producción; separado, no se resuelve en esta fase)
LOCAL_LOGIN_DEPENDENCY = PENDING
```
