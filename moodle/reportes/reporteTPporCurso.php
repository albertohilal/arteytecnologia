<?php
// reporteTPporCurso.php

require_once(__DIR__ . '/../config.php');
require_login();

global $DB, $CFG, $PAGE, $USER;

require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

// Parámetros.
$courseid = optional_param('courseid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_TEXT);
$saved = optional_param('saved', 0, PARAM_INT);
$setupdone = optional_param('setupdone', 0, PARAM_INT);
$periodsaved = optional_param('periodsaved', 0, PARAM_INT);
$gradeerror = optional_param('gradeerror', '', PARAM_ALPHANUMEXT);
$action = optional_param('action', '', PARAM_ALPHA);

/**
 * Devuelve permisos operativos del usuario para este reporte.
 *
 * Reglas de seguridad:
 * - Si el usuario tiene rol student en el contexto del curso y no tiene rol docente/manager,
 *   queda forzado a modo lectura.
 * - Solo editingteacher, teacher, manager o siteadmin pueden editar calificaciones.
 * - No usamos moodle/grade:viewall para habilitar edición visual, porque puede estar
 *   demasiado amplio en algunos sitios.
 */
function reporte_tp_role_flags(context_course $context, int $userid): array {
    $courseroles = get_user_roles($context, $userid, false);
    $inheritedroles = get_user_roles($context, $userid, true);

    $graderroles = ['manager', 'editingteacher', 'teacher'];

    $hasstudentrole = false;
    $hascoursergraderrole = false;
    $hasinheritedmanagerrole = false;

    foreach ($courseroles as $role) {
        if ($role->shortname === 'student') {
            $hasstudentrole = true;
        }

        if (in_array($role->shortname, $graderroles, true)) {
            $hascoursergraderrole = true;
        }
    }

    foreach ($inheritedroles as $role) {
        if ($role->shortname === 'manager') {
            $hasinheritedmanagerrole = true;
        }
    }

    $issiteadmin = is_siteadmin($userid);
    $hasgraderrole = $issiteadmin || $hascoursergraderrole || $hasinheritedmanagerrole;

    // Si es solo estudiante del curso, nunca puede editar desde este reporte.
    $isstudentonly = $hasstudentrole && !$hascoursergraderrole && !$hasinheritedmanagerrole && !$issiteadmin;

    return [
        'hasgraderrole' => $hasgraderrole,
        'hasstudentrole' => $hasstudentrole,
        'isstudentonly' => $isstudentonly,
        'caneditreport' => $hasgraderrole && !$isstudentonly,
    ];
}

// ===========================================================================
// UNIT-1: infraestructura nueva TP-CEE-NN (clasificación canónica).
// Estas funciones son PURAS (side-effect-free): no consultan ni modifican
// base de datos, gradebook, grade_forum, forum_grades ni grade_items.
// La integración de consumidores runtime corresponde a UNIT-3.
// ===========================================================================

/**
 * Parsea un identificador de TP según la gramática canónica TP-CEE-NN.
 *
 * Gramática (case-insensitive): ^TP-([0-9]{3})-([0-9]{2})(?:\s*-\s*.*)?$
 *   grupo 1 = CEE (3 dígitos): C (período: 0|2) + EE (Encuentro, 2 dígitos).
 *   grupo 2 = NN (ordinal del TP dentro del Encuentro, 2 dígitos).
 *
 * Reglas de período:
 *   C = 0 → PERIOD_1 (period = 1), EE permitido 01..07.
 *   C = 2 → PERIOD_2 (period = 2), EE permitido 08..24.
 *   C ∉ {0,2}          → encounter_out_of_period.
 *   EE fuera del rango del período codificado → encounter_out_of_period.
 *
 * Regla de ordinal:
 *   NN canónico = 01..99. NN = 00 → invalid_format.
 *
 * @param string $name Nombre de la actividad (p. ej. "TP-208-01 - descripción").
 * @return array [ 'valid' => bool, 'error' => string|null, 'cee' => string|null,
 *                 'period' => int|null, 'encounter_number' => int|null,
 *                 'tp_number_within_encounter' => int|null ]
 */
function parse_tp_identifier(string $name): array {
    if (!preg_match('/^TP-([0-9]{3})-([0-9]{2})(?:\s*-\s*.*)?$/i', $name, $m)) {
        return [
            'valid' => false,
            'error' => 'invalid_format',
            'cee' => null,
            'period' => null,
            'encounter_number' => null,
            'tp_number_within_encounter' => null,
        ];
    }

    $cee = $m[1];
    $perioddigit = $cee[0];
    $encounter = (int)substr($cee, 1, 2);
    $ordinal = (int)$m[2];

    // NN canónico = 01..99. El ordinal 00 no es un TP válido (SPECIFICATION:
    // "NN empieza canónicamente en 01"). La gramática ya limita NN a 2 dígitos,
    // por lo que aquí solo se rechaza el 00. Se reutiliza la categoría pública
    // invalid_format, que comprende también NN fuera del dominio 01..99.
    if ($ordinal < 1) {
        return [
            'valid' => false,
            'error' => 'invalid_format',
            'cee' => $cee,
            'period' => null,
            'encounter_number' => $encounter,
            'tp_number_within_encounter' => $ordinal,
        ];
    }

    if ($perioddigit === '0') {
        $period = 1;
        $inrange = ($encounter >= 1 && $encounter <= 7);
    } else if ($perioddigit === '2') {
        $period = 2;
        $inrange = ($encounter >= 8 && $encounter <= 24);
    } else {
        // C fuera de {0,2}: sin período válido.
        return [
            'valid' => false,
            'error' => 'encounter_out_of_period',
            'cee' => $cee,
            'period' => null,
            'encounter_number' => $encounter,
            'tp_number_within_encounter' => $ordinal,
        ];
    }

    if (!$inrange) {
        // EE fuera del rango del período codificado en C.
        return [
            'valid' => false,
            'error' => 'encounter_out_of_period',
            'cee' => $cee,
            'period' => $period,
            'encounter_number' => $encounter,
            'tp_number_within_encounter' => $ordinal,
        ];
    }

    return [
        'valid' => true,
        'error' => null,
        'cee' => $cee,
        'period' => $period,
        'encounter_number' => $encounter,
        'tp_number_within_encounter' => $ordinal,
    ];
}

/**
 * Clasificación canónica VALID_GRADED_TP (definición única del DESIGN).
 *
 * VALID_GRADED_TP(forum) :=
 *       actividad foro (recibida como $forum)
 *   AND forum.type != 'news'
 *   AND parse_tp_identifier(forum.name).valid == true
 *
 * Pura / side-effect-free: opera solo sobre el objeto recibido. No consulta ni
 * modifica base de datos; no cambia grade_forum, no crea grade_items, no escribe
 * forum_grades/grade_grades, no ejecuta setupgrades, renombres, cache ni regrade.
 *
 * @param object $forum Registro de foro (stdClass) con al menos ->type y ->name.
 * @return bool
 */
function reporte_tp_is_valid_graded_tp(object $forum): bool {
    if (isset($forum->type) && $forum->type === 'news') {
        return false;
    }

    if (empty($forum->name) || !is_string($forum->name)) {
        return false;
    }

    return parse_tp_identifier($forum->name)['valid'];
}

// Período (1|2) derivado del parser canónico, o null si no es VALID_GRADED_TP.
function reporte_tp_get_period_of_forum(object $forum): ?int {
    if (!reporte_tp_is_valid_graded_tp($forum)) {
        return null;
    }

    $parsed = parse_tp_identifier($forum->name);

    return $parsed['period'] ?? null;
}

// Foros de un período: VALID_GRADED_TP + período desde el parser + grade_forum === 10.0.
// Cada TP permanece independiente (no se agrupa por Encuentro).
function reporte_tp_collect_period_forums(array $forums, int $period): array {
    $collected = [];
    foreach ($forums as $forum) {
        if (!reporte_tp_is_valid_graded_tp($forum)) {
            continue;
        }
        if ((float)$forum->grade_forum !== 10.0) {
            continue;
        }
        if (reporte_tp_get_period_of_forum($forum) !== $period) {
            continue;
        }
        $collected[$forum->id] = $forum;
    }

    return $collected;
}

// Nota de período 0-10: redondeo half-up de (entregados / total) * 10.
function reporte_tp_compute_period_grade(int $delivered, int $total): int {
    if ($total === 0) {
        return 0;
    }

    return (int)round(($delivered / $total) * 10);
}

// Conteo de entregados/total y nota del período por estudiante. Entregado = calificación de foro completo >= 4.
function reporte_tp_compute_period_grades(array $students, array $periodforums, array $forumgradevalues): array {
    $result = [];
    foreach ($students as $student) {
        $uid = (int)$student->id;
        $delivered = 0;
        $total = count($periodforums);

        foreach ($periodforums as $fid => $forum) {
            if (($forumgradevalues[$fid][$uid] ?? 0) >= 4) {
                $delivered++;
            }
        }

        $result[$uid] = [
            'delivered' => $delivered,
            'total' => $total,
            'grade' => reporte_tp_compute_period_grade($delivered, $total),
        ];
    }

    return $result;
}

// Encuentra (o crea) el grade_item manual del período por idnumber periodo{period}-{courseid}.
function reporte_tp_ensure_period_grade_item(int $courseid, int $period): grade_item {
    global $DB;

    $idnumber = "periodo{$period}-{$courseid}";
    $existing = $DB->get_record('grade_items', ['courseid' => $courseid, 'idnumber' => $idnumber]);

    if ($existing) {
        $item = new grade_item($existing);

        if ((float)$item->grademax != 10 || (float)$item->grademin != 0 || $item->itemname !== "Cuatrimestre {$period}") {
            $item->grademax = 10;
            $item->grademin = 0;
            $item->itemname = "Cuatrimestre {$period}";
            $item->update();
        }

        return $item;
    }

    $item = new grade_item((object)[
        'courseid' => $courseid,
        'itemtype' => 'manual',
        'itemmodule' => null,
        'iteminstance' => null,
        'itemnumber' => 0,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => 10,
        'grademin' => 0,
        'idnumber' => $idnumber,
        'itemname' => "Cuatrimestre {$period}",
    ]);
    $item->insert();

    return $item;
}

// Escribe la nota final vía grade_item::update_final_grade(); lanza excepción si falla (dispara el rollback).
function reporte_tp_write_period_grade(grade_item $item, int $userid, float $grade): void {
    $result = $item->update_final_grade($userid, $grade);

    if ($result === false) {
        throw new Exception("No se pudo escribir la nota del período para el usuario {$userid}.");
    }
}

// Elimina la nota manual persistida del período para un estudiante, si existe.
// Si no hay grade_item ni grade_grade persistido, no hace nada (no-op), y la nota
// vuelve a calcularse automáticamente al renderizar.
function reporte_tp_delete_period_grade(int $courseid, int $period, int $userid): void {
    global $DB;

    $record = $DB->get_record('grade_items', [
        'courseid' => $courseid,
        'idnumber' => "periodo{$period}-{$courseid}"
    ], '*', IGNORE_MISSING);

    if (!$record) {
        return;
    }

    $item = new grade_item($record);

    $grade = grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);

    if (!$grade) {
        return;
    }

    if ($grade->delete('reportes/reporteTPporCurso') === false) {
        throw new Exception("No se pudo eliminar la nota del período para el usuario {$userid}.");
    }

    $item->force_regrading();
}

// Lee las notas de período persistidas (grade_item manual) para un curso y período.
// Devuelve [userid => finalgrade] solo para notas numéricas no nulas.
function reporte_tp_get_saved_period_grades(int $courseid, int $period): array {
    global $DB;

    $item = $DB->get_record('grade_items', [
        'courseid' => $courseid,
        'idnumber' => "periodo{$period}-{$courseid}"
    ], '*', IGNORE_MISSING);

    if (!$item) {
        return [];
    }

    if (class_exists('grade_grade')) {
        $grades = grade_grade::fetch_all(['itemid' => $item->id]);
    } else {
        $grades = $DB->get_records('grade_grades', ['itemid' => $item->id]);
    }

    $result = [];
    if ($grades) {
        foreach ($grades as $grade) {
            $finalgrade = $grade->finalgrade;
            if ($finalgrade !== null && is_numeric($finalgrade)) {
                $result[(int)$grade->userid] = (float)$finalgrade;
            }
        }
    }

    return $result;
}

// Configuración mínima de página.
$pageparams = [];
if ($courseid) {
    $pageparams['courseid'] = $courseid;
}
if ($download) {
    $pageparams['download'] = $download;
}

$PAGE->set_url(new moodle_url('/reportes/reporteTPporCurso.php', $pageparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Participación en Foros TP');
$PAGE->set_heading('Participación en Foros TP');

// Habilitar calificación oficial para todos los foros TP-* del curso seleccionado.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'setupgrades') {
    require_sesskey();

    $postcourseid = required_param('courseid', PARAM_INT);

    $course = $DB->get_record('course', ['id' => $postcourseid], '*', MUST_EXIST);
    require_login($course);

    $coursecontext = context_course::instance($course->id);
    $roleflags = reporte_tp_role_flags($coursecontext, (int)$USER->id);

    // Solo docentes, managers o administradores pueden crear/actualizar ítems de calificación.
    if (!$roleflags['caneditreport']) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'nopermission'
        ]));
    }

    require_capability('moodle/course:manageactivities', $coursecontext);

    $tpforums = $DB->get_records_sql("\n        SELECT f.*, cm.idnumber AS cmidnumber\n        FROM {forum} f\n        JOIN {course_modules} cm ON cm.instance = f.id\n        JOIN {modules} m ON m.id = cm.module\n        WHERE f.course = :courseid\n          AND f.name LIKE :prefix\n          AND m.name = :modname\n        ORDER BY f.name\n    ", [
        'courseid' => $postcourseid,
        'prefix' => 'TP-%',
        'modname' => 'forum'
    ]);

    // UNIT-3: la selección de setupgrades es exclusivamente VALID_GRADED_TP
    // (excluye type='news', identificadores inválidos y CEE fuera de período).
    $tpforums = array_filter($tpforums, 'reporte_tp_is_valid_graded_tp');

    $updatedcount = 0;

    foreach ($tpforums as $forum) {
        if ((float)$forum->grade_forum !== 10.0 || (int)$forum->grade_forum_notify !== 0) {
            $update = new stdClass();
            $update->id = $forum->id;
            $update->grade_forum = 10;
            $update->grade_forum_notify = 0;

            $DB->update_record('forum', $update);

            $forum->grade_forum = 10;
            $forum->grade_forum_notify = 0;

            $updatedcount++;
        }

        // Crea o actualiza el ítem oficial en el libro de calificaciones.
        forum_grade_item_update($forum);
    }

    rebuild_course_cache($course->id, true);

    redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
        'courseid' => $postcourseid,
        'setupdone' => $updatedcount
    ]));
}

// Guardado rápido de calificación oficial del foro completo.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'savegrade') {
    require_sesskey();

    $postcourseid = required_param('courseid', PARAM_INT);
    $forumid = required_param('forumid', PARAM_INT);
    $userid = required_param('userid', PARAM_INT);
    $gradevalue = optional_param('grade', '', PARAM_RAW_TRIMMED);

    $course = $DB->get_record('course', ['id' => $postcourseid], '*', MUST_EXIST);
    require_login($course);

    $coursecontext = context_course::instance($course->id);

    if (!is_enrolled($coursecontext, $userid)) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'notenrolled'
        ]));
    }

    $forum = $DB->get_record('forum', [
        'id' => $forumid,
        'course' => $postcourseid
    ], '*', MUST_EXIST);

    $cm = get_coursemodule_from_instance('forum', $forum->id, $postcourseid, false, MUST_EXIST);
    $modulecontext = context_module::instance($cm->id);
    $roleflags = reporte_tp_role_flags($coursecontext, (int)$USER->id);

    // Protección real del backend: solo docentes, managers o administradores pueden calificar.
    if (!$roleflags['caneditreport']) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'nopermission'
        ]));
    }

    require_capability('mod/forum:grade', $modulecontext);

    // Solo se guarda sobre "Whole forum grading", no sobre ratings de mensajes.
    if (empty($forum->grade_forum) || (float)$forum->grade_forum <= 0) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'notgradable'
        ]));
    }

    $gradevalue = trim($gradevalue);

    if ($gradevalue === '') {
        $rawgrade = null;
    } else if (!preg_match('/^\d+$/', $gradevalue)) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'invalid'
        ]));
    } else {
        $rawgrade = (int)$gradevalue;

        if ($rawgrade < 0 || $rawgrade > (int)$forum->grade_forum) {
            redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                'courseid' => $postcourseid,
                'gradeerror' => 'range'
            ]));
        }
    }

    $grade = new stdClass();
    $grade->userid = $userid;
    $grade->rawgrade = $rawgrade;
    $grade->usermodified = $USER->id;
    $grade->dategraded = time();

    $gradeitemname = new stdClass();
    $gradeitemname->name = $forum->name;

    $itemdetails = [
        'itemname' => get_string('gradeitemnameforwholeforum', 'forum', $gradeitemname),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => (float)$forum->grade_forum,
        'grademin' => 0
    ];

    $result = grade_update(
        'mod/forum',
        $forum->course,
        'mod',
        'forum',
        $forum->id,
        1,
        $grade,
        $itemdetails
    );

    if ($result !== GRADE_UPDATE_OK) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'save'
        ]));
    }

    redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
        'courseid' => $postcourseid,
        'saved' => 1
    ]));
}

// Guardado de notas de período (cuatrimestre).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'saveperiodgrade') {
    require_sesskey();

    $postcourseid = required_param('courseid', PARAM_INT);

    $course = $DB->get_record('course', ['id' => $postcourseid], '*', MUST_EXIST);
    require_login($course);

    $coursecontext = context_course::instance($course->id);
    $roleflags = reporte_tp_role_flags($coursecontext, (int)$USER->id);

    if (!$roleflags['caneditreport']) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'nopermission'
        ]));
    }

    require_capability('mod/forum:grade', $coursecontext);

    // Recuperación 2-D segura: data_submitted() aplica fix_utf8() recursivo sobre $_POST.
    $submitted = data_submitted();
    $periodgrade = is_object($submitted) ? ($submitted->periodgrade ?? []) : [];

    if (!is_array($periodgrade)) {
        $periodgrade = [];
    }

    // Validar TODO antes de escribir cualquier cosa.
    $writes = [];
    $deletes = [];
    foreach ($periodgrade as $periodkey => $userentries) {
        $period = (int)$periodkey;

        if ($period !== 1 && $period !== 2) {
            redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                'courseid' => $postcourseid,
                'gradeerror' => 'invalid'
            ]));
        }

        if (!is_array($userentries)) {
            redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                'courseid' => $postcourseid,
                'gradeerror' => 'invalid'
            ]));
        }

        foreach ($userentries as $uidkey => $rawvalue) {
            $uid = (int)$uidkey;

            if ($uid <= 0) {
                redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                    'courseid' => $postcourseid,
                    'gradeerror' => 'invaliduser'
                ]));
            }

            if (!$DB->get_record('user', ['id' => $uid], 'id', IGNORE_MISSING)) {
                redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                    'courseid' => $postcourseid,
                    'gradeerror' => 'invaliduser'
                ]));
            }

            if (!is_enrolled($coursecontext, $uid)) {
                redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                    'courseid' => $postcourseid,
                    'gradeerror' => 'notenrolled'
                ]));
            }

            $value = trim((string)$rawvalue);

            if ($value === '') {
                // Campo vacío: eliminar el override persistido si existe (no-op si no hay).
                $deletes[] = ['period' => $period, 'uid' => $uid];
                continue;
            }

            if (!preg_match('/^\d{1,2}$/', $value)) {
                redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                    'courseid' => $postcourseid,
                    'gradeerror' => 'invalid'
                ]));
            }

            $grade = (int)$value;

            if ($grade < 0 || $grade > 10) {
                redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
                    'courseid' => $postcourseid,
                    'gradeerror' => 'range'
                ]));
            }

            $writes[] = ['period' => $period, 'uid' => $uid, 'grade' => $grade];
        }
    }

    if (empty($writes) && empty($deletes)) {
        // Nada que escribir (todos los campos vacíos): no-op silencioso.
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid
        ]));
    }

    $transaction = $DB->start_delegated_transaction();

    try {
        foreach ($writes as $write) {
            $item = reporte_tp_ensure_period_grade_item((int)$postcourseid, $write['period']);
            reporte_tp_write_period_grade($item, $write['uid'], (float)$write['grade']);
        }

        foreach ($deletes as $delete) {
            reporte_tp_delete_period_grade((int)$postcourseid, $delete['period'], $delete['uid']);
        }

        $transaction->allow_commit();
    } catch (Exception $e) {
        try {
            $transaction->rollback($e);
        } catch (Exception $ignored) {
            // rollback() relanza la excepción original; el rollback ya se realizó.
        }

        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'save'
        ]));
    }

    redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
        'courseid' => $postcourseid,
        'periodsaved' => 1
    ]));
}

// Obtener lista de cursos que tienen foros TP-*.
$allcourses = $DB->get_records_sql("\n    SELECT DISTINCT c.id, c.shortname\n    FROM {course} c\n    JOIN {forum} f ON f.course = c.id\n    WHERE f.name LIKE :prefix\n    ORDER BY c.shortname\n", [
    'prefix' => 'TP-%'
]);

// Filtrar cursos disponibles para el usuario.
// Docentes/administradores ven cursos donde pueden ver todas las calificaciones.
// Estudiantes ven cursos donde están matriculados.
$courses = [];
foreach ($allcourses as $course) {
    $context = context_course::instance($course->id);
    $roleflags = reporte_tp_role_flags($context, (int)$USER->id);
    $isenrolledincourse = is_enrolled($context, $USER, '', true);

    if ($roleflags['caneditreport'] || $isenrolledincourse) {
        $courses[$course->id] = $course;
    }
}

// Si no viene courseid por URL, seleccionar el primer curso disponible para este usuario.
if (!$courseid && !empty($courses)) {
    $firstcourse = reset($courses);
    $courseid = (int)$firstcourse->id;
}

$selected_course = null;
$coursecontext = null;
$forums = [];
$students = [];
$report_data = [];
$forumgradevalues = [];
$forumgradepermissions = [];
$needsgradesetup = false;
$ungradedforumcount = 0;
$canmanageactivities = false;
$canviewallgrades = false;
$canviewownreport = false;
$caneditperiodgrade = false;
$periodforums = [];
$periodgrades = [];
$savedperiodgrades = [];

if ($courseid) {
    $selected_course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    require_login($selected_course);

    $coursecontext = context_course::instance($selected_course->id);
    $PAGE->set_context($coursecontext);

    // Docentes, managers y administradores ven todas las filas. Estudiantes ven solo su propia fila.
    $roleflags = reporte_tp_role_flags($coursecontext, (int)$USER->id);
    $canviewallgrades = $roleflags['caneditreport'];
    $canviewownreport = is_enrolled($coursecontext, $USER, '', true);

    if (!$canviewallgrades && !$canviewownreport) {
        require_login($selected_course);
        print_error('nopermissions', 'error', '', 'ver este reporte');
    }

    $canmanageactivities = $roleflags['caneditreport'] && has_capability('moodle/course:manageactivities', $coursecontext);
    $caneditperiodgrade = $roleflags['caneditreport'] && has_capability('mod/forum:grade', $coursecontext);

    // Obtener solo foros TP-% visibles en la página del curso.
$forums = $DB->get_records_sql("
    SELECT 
        f.id,
        f.name,
        f.course,
        f.grade_forum,
        f.type,
        cm.id AS cmid
    FROM {forum} f
    JOIN {course_modules} cm 
        ON cm.instance = f.id
    JOIN {modules} m 
        ON m.id = cm.module
    JOIN {course_sections} cs
        ON cs.id = cm.section
    WHERE f.course = :cid
      AND f.name LIKE :prefix
      AND m.name = :modname
      AND cm.course = f.course
      AND cm.visible = 1
      AND cm.visibleoncoursepage = 1
      AND cm.deletioninprogress = 0
      AND cs.course = f.course
      AND cs.visible = 1
      AND FIND_IN_SET(cm.id, cs.sequence) > 0
    ORDER BY f.name
", [
    'cid' => $courseid,
    'prefix' => 'TP-%',
    'modname' => 'forum'
]);

    // UNIT-3: el listado de TP calificables se rige por VALID_GRADED_TP
    // (excluye type='news', identificadores no canónicos y CEE fuera de período).
    $forums = array_filter($forums, 'reporte_tp_is_valid_graded_tp');

    // Determinar permisos de calificación y si falta habilitar algún foro.
    foreach ($forums as $forum) {
        if (empty($forum->grade_forum) || (float)$forum->grade_forum <= 0) {
            $needsgradesetup = true;
            $ungradedforumcount++;
        }

        $modulecontext = context_module::instance($forum->cmid);
        $forumgradepermissions[$forum->id] = $roleflags['caneditreport'] && has_capability('mod/forum:grade', $modulecontext);
    }

    // Obtener estudiantes.
    if ($canviewallgrades) {
        // En Moodle estándar, roleid = 5 suele ser estudiante.
        $students = $DB->get_records_sql("\n            SELECT DISTINCT u.id, u.firstname, u.lastname\n            FROM {user} u\n            JOIN {role_assignments} ra ON ra.userid = u.id\n            JOIN {context} cx ON cx.id = ra.contextid\n            WHERE cx.contextlevel = :ctxlevel\n              AND cx.instanceid = :cid\n              AND ra.roleid = :studentrole\n              AND u.deleted = 0\n            ORDER BY u.lastname, u.firstname\n        ", [
            'ctxlevel' => CONTEXT_COURSE,
            'cid' => $courseid,
            'studentrole' => 5
        ]);
    } else {
        $students = $DB->get_records_sql("\n            SELECT u.id, u.firstname, u.lastname\n            FROM {user} u\n            WHERE u.id = :userid\n              AND u.deleted = 0\n        ", [
            'userid' => $USER->id
        ]);
    }

    // Obtener calificaciones actuales del Whole forum grading.
    $studentids = array_map('intval', array_keys($students));

    foreach ($forums as $forum) {
        $forumgradevalues[$forum->id] = [];

        if (!empty($forum->grade_forum) && (float)$forum->grade_forum > 0 && !empty($studentids)) {
            $gradesinfo = grade_get_grades($courseid, 'mod', 'forum', $forum->id, $studentids);

            if (!empty($gradesinfo->items[1])) {
                foreach ($gradesinfo->items[1]->grades as $gradeduserid => $gradeobject) {
                    $forumgradevalues[$forum->id][(int)$gradeduserid] = $gradeobject->grade;
                }
            }
        }
    }

    foreach ($students as $student) {
        $row = [
            'userid'   => (int)$student->id,
            'apellido' => s($student->lastname),
            'nombre'   => s($student->firstname),
            'links'    => [],
            'grades'   => []
        ];

        foreach ($forums as $forum) {
            $links = [];

            $msgs = $DB->get_records_sql("\n                SELECT fp.id, fp.message\n                FROM {forum_discussions} fd\n                JOIN {forum_posts} fp ON fp.discussion = fd.id\n                WHERE fd.forum = :fid\n                  AND fp.userid = :uid\n                ORDER BY fp.created ASC\n            ", [
                'fid' => $forum->id,
                'uid' => $student->id
            ]);

            foreach ($msgs as $msg) {
                if (preg_match_all('/https?:\/\/[^\s"\'<>]+/i', $msg->message, $matches)) {
                    foreach ($matches[0] as $url) {
                        if (!in_array($url, $links, true)) {
                            $links[] = $url;
                        }
                    }
                }
            }

            $row['links'][$forum->id] = $links;
            $row['grades'][$forum->id] = $forumgradevalues[$forum->id][$student->id] ?? null;
        }

        $report_data[] = $row;
    }

    // Agrupación por período y notas de cuatrimestre por estudiante.
    // El período se deriva del parser canónico (VALID_GRADED_TP), no de rangos de número de TP.
    for ($period = 1; $period <= 2; $period++) {
        $periodforums[$period] = reporte_tp_collect_period_forums($forums, $period);
        $periodgrades[$period] = reporte_tp_compute_period_grades($students, $periodforums[$period], $forumgradevalues);
        $savedperiodgrades[$period] = reporte_tp_get_saved_period_grades((int)$courseid, $period);
    }
}

// Exportación a Excel. Solo docentes/administradores.
if ($download === 'excel') {
    if (!$canviewallgrades) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $courseid,
            'gradeerror' => 'nopermission'
        ]));
    }

    if (!empty($report_data)) {
        $filename = 'reporteTP_' . $selected_course->shortname . '_' . date('Ymd_His') . '.xlsx';

        $workbook = new MoodleExcelWorkbook('-');
        $workbook->send($filename);
        $worksheet = $workbook->add_worksheet('Participación');

        $formattext = $workbook->add_format(['bold' => 0]);
        $formatlink = $workbook->add_format([
            'colour' => 'blue',
            'underline' => 1
        ]);

        $rownum = 0;
        $col = 0;

        $worksheet->write_string($rownum, $col++, 'Apellido', $formattext);
        $worksheet->write_string($rownum, $col++, 'Nombre', $formattext);

        foreach ($forums as $forum) {
            $worksheet->write_string($rownum, $col++, $forum->name, $formattext);
        }

        foreach ($report_data as $data) {
            $rownum++;
            $col = 0;

            $worksheet->write_string($rownum, $col++, $data['apellido'], $formattext);
            $worksheet->write_string($rownum, $col++, $data['nombre'], $formattext);

            foreach ($forums as $forum) {
                $links = $data['links'][$forum->id] ?? [];

                if (!empty($links)) {
                    $text = implode("\n", $links);
                    $worksheet->write_url($rownum, $col, $links[0], $formatlink, $text);
                } else {
                    $worksheet->write_string($rownum, $col, '', $formattext);
                }

                $col++;
            }
        }

        $workbook->close();
        exit;
    }
}

// Opciones del selector.
$courseoptions = [];
foreach ($courses as $course) {
    $courseoptions[$course->id] = $course->shortname;
}

// Datos de usuario.
$currentuser = fullname($USER);
?>
<!DOCTYPE html>
<!-- reporteTPporCurso: estudiante solo lectura / docente edicion - version segura -->
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Participación en Foros TP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --apellido-width: 135px;
            --nombre-width: 155px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background: #f7f7f7;
            color: #333;
        }

        body {
            overflow-x: hidden;
        }

        .topbar {
            width: 100%;
            background: #f42668;
            color: #fff;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .topbar-title {
            font-size: 24px;
            font-weight: 600;
        }

        .topbar-user {
            font-size: 14px;
            opacity: 0.95;
        }

        .navline {
            width: 100%;
            background: #fff;
            border-bottom: 2px solid #00a99d;
            padding: 8px 24px;
            display: flex;
            gap: 18px;
            align-items: center;
            font-size: 13px;
        }

        .navline a {
            color: #333;
            text-decoration: none;
        }

        .navline a:hover {
            text-decoration: underline;
        }

        .report-page {
            width: calc(100vw - 32px);
            max-width: calc(100vw - 32px);
            margin: 24px 16px;
            padding: 0;
        }

        .report-header {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 18px 20px;
            margin-bottom: 14px;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 8px 0;
            font-weight: 500;
        }

        .course-current {
            margin: 0 0 14px 0;
            font-size: 14px;
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .controls label {
            font-size: 14px;
        }

        select {
            padding: 7px 9px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid transparent;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            line-height: 1.2;
        }

        .btn-success {
            color: #fff;
            background: #2f8a3a;
            border-color: #2f8a3a;
        }

        .btn-success:hover {
            background: #256f2e;
        }

        .btn-warning {
            color: #fff;
            background: #d88900;
            border-color: #d88900;
        }

        .btn-warning:hover {
            background: #b87300;
        }

        .setup-grades-form {
            margin-top: 10px;
        }

        .notice {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 14px;
        }

        .notice-success {
            background: #dff0d8;
            border: 1px solid #b2d8a6;
            color: #245724;
        }

        .notice-error {
            background: #f8d7da;
            border: 1px solid #e3a6ad;
            color: #721c24;
        }

        .table-panel {
            width: 100%;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0;
            overflow: hidden;
        }

        .table-wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 245px);
        }

        table.report-table {
            border-collapse: collapse;
            width: max-content;
            min-width: 100%;
            table-layout: auto;
            font-size: 16px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #d0d0d0;
            padding: 10px 12px;
            vertical-align: top;
            text-align: left;
            white-space: normal;
        }

        .report-table thead th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 20;
            font-weight: 600;
        }

        .report-table tbody tr:nth-child(odd) {
            background: #9fe3f1;
        }

        .report-table tbody tr:nth-child(even) {
            background: #fff;
        }

        .report-table th:nth-child(1),
        .report-table td:nth-child(1) {
            min-width: var(--apellido-width);
            width: var(--apellido-width);
        }

        .report-table th:nth-child(2),
        .report-table td:nth-child(2) {
            min-width: var(--nombre-width);
            width: var(--nombre-width);
        }

        .report-table th:nth-child(n+3),
        .report-table td:nth-child(n+3) {
            min-width: 210px;
            max-width: 260px;
        }

        .report-table th:nth-child(1),
        .report-table td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 10;
        }

        .report-table th:nth-child(2),
        .report-table td:nth-child(2) {
            position: sticky;
            left: var(--apellido-width);
            z-index: 10;
            box-shadow: 3px 0 4px rgba(0, 0, 0, 0.12);
        }

        .report-table thead th:nth-child(1),
        .report-table thead th:nth-child(2) {
            top: 0;
            z-index: 30;
            background: #fff;
        }

        .report-table tbody tr:nth-child(odd) td:nth-child(1),
        .report-table tbody tr:nth-child(odd) td:nth-child(2) {
            background: #9fe3f1;
        }

        .report-table tbody tr:nth-child(even) td:nth-child(1),
        .report-table tbody tr:nth-child(even) td:nth-child(2) {
            background: #fff;
        }

        .forum-cell a {
            display: inline-block;
            margin-bottom: 4px;
            color: #35606a;
            text-decoration: none;
            font-weight: 500;
        }

        .forum-cell a:hover {
            text-decoration: underline;
        }

        .no-links {
            display: block;
            margin-bottom: 6px;
            color: #777;
            font-size: 13px;
        }

        .grade-form {
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: nowrap;
        }

        .grade-input {
            width: 58px;
            padding: 4px 5px;
            border: 1px solid #aaa;
            border-radius: 4px;
            font-size: 13px;
        }

        .grade-save {
            padding: 4px 7px;
            border: 1px solid #2f8a3a;
            border-radius: 4px;
            background: #2f8a3a;
            color: #fff;
            font-size: 12px;
            cursor: pointer;
        }

        .grade-save:hover {
            background: #256f2e;
        }

        .grade-max {
            font-size: 12px;
            color: #666;
        }

        .grade-readonly {
            margin-top: 6px;
            font-size: 13px;
            color: #333;
            font-weight: 600;
        }

        .grade-disabled {
            margin-top: 6px;
            font-size: 12px;
            color: #777;
        }

        .period-grade-cell {
            text-align: center;
            vertical-align: top;
        }

        .period-grade-input {
            width: 58px;
            padding: 4px 5px;
            border: 1px solid #aaa;
            border-radius: 4px;
            font-size: 13px;
            text-align: center;
        }

        .period-grade-meta {
            display: block;
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }

        .period-grade-readonly {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        .period-grade-empty {
            color: #999;
            font-size: 13px;
        }

        #periodgrade-form {
            margin-top: 14px;
            padding: 4px 0;
        }

        .empty-message {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            font-size: 15px;
        }

        @media (max-width: 768px) {
            :root {
                --apellido-width: 105px;
                --nombre-width: 115px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .report-page {
                width: calc(100vw - 16px);
                max-width: calc(100vw - 16px);
                margin: 12px 8px;
            }

            .report-header {
                padding: 14px;
            }

            h1 {
                font-size: 23px;
            }

            .table-wrapper {
                max-height: calc(100vh - 220px);
            }

            .report-table th,
            .report-table td {
                padding: 8px 9px;
                font-size: 13px;
            }

            .report-table th:nth-child(n+3),
            .report-table td:nth-child(n+3) {
                min-width: 190px;
                max-width: 230px;
            }

            .grade-form {
                gap: 4px;
            }

            .grade-input {
                width: 52px;
            }

            .grade-save {
                padding: 4px 6px;
            }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-title">Campus Arte y Tecnologia</div>
    <div class="topbar-user"><?php echo s($currentuser); ?></div>
</header>

<nav class="navline">
    <a href="<?php echo $CFG->wwwroot; ?>/">Inicio</a>
    <a href="<?php echo $CFG->wwwroot; ?>/my/">Dashboard</a>
    <a href="<?php echo $CFG->wwwroot; ?>/course/">Cursos</a>
    <a href="<?php echo $CFG->wwwroot; ?>/reportes/">Índice de reportes</a>
</nav>

<main class="report-page">

    <section class="report-header">
        <h1>Participación en Foros TP</h1>

        <?php if ($selected_course): ?>
            <p class="course-current">
                Curso: <?php echo s(format_string($selected_course->shortname)); ?>
            </p>
        <?php endif; ?>

        <form method="get" class="controls">
            <label for="courseid">Seleccionar curso:</label>

            <select name="courseid" id="courseid" onchange="this.form.submit()">
                <?php foreach ($courseoptions as $id => $shortname): ?>
                    <option value="<?php echo (int)$id; ?>" <?php echo ((int)$id === (int)$courseid) ? 'selected' : ''; ?>>
                        <?php echo s($shortname); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($selected_course && $canviewallgrades): ?>
                <a class="btn btn-success"
                   href="<?php echo (new moodle_url('/reportes/reporteTPporCurso.php', [
                       'courseid' => $courseid,
                       'download' => 'excel'
                   ]))->out(false); ?>">
                    Exportar a Excel
                </a>
            <?php endif; ?>
        </form>

        <?php if ($selected_course && $needsgradesetup && $canmanageactivities): ?>
            <form method="post"
                  class="controls setup-grades-form"
                  action="<?php echo (new moodle_url('/reportes/reporteTPporCurso.php'))->out(false); ?>">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <input type="hidden" name="action" value="setupgrades">
                <input type="hidden" name="courseid" value="<?php echo (int)$courseid; ?>">

                <button type="submit"
                        class="btn btn-warning"
                        onclick="return confirm('Esto habilitará calificación sobre 10 en todos los foros TP de este curso. ¿Continuar?');">
                    Habilitar calificaciones TP en este curso
                </button>
            </form>
        <?php endif; ?>

        <?php if ($saved): ?>
            <div class="notice notice-success">
                Calificación guardada correctamente.
            </div>
        <?php endif; ?>

        <?php if ($periodsaved): ?>
            <div class="notice notice-success">
                Notas del período guardadas correctamente.
            </div>
        <?php endif; ?>

        <?php if ($setupdone): ?>
            <div class="notice notice-success">
                Se habilitó la calificación oficial en <?php echo (int)$setupdone; ?> foro(s) TP del curso.
            </div>
        <?php endif; ?>

        <?php if ($gradeerror): ?>
            <div class="notice notice-error">
                <?php
                    $grademessages = [
                        'nopermission' => 'No tenés permisos para guardar calificaciones.',
                        'notenrolled'  => 'Uno de los estudiantes indicados no está matriculado en este curso.',
                        'invaliduser'  => 'Uno de los usuarios indicados no es válido.',
                        'notgradable'  => 'El foro no tiene habilitada la calificación del foro completo.',
                        'invalid'      => 'La nota debe ser un número entero.',
                        'range'        => 'La nota está fuera del rango permitido.',
                        'save'         => 'No se pudo guardar la calificación. Intentalo nuevamente.',
                    ];
                    echo isset($grademessages[$gradeerror]) ? $grademessages[$gradeerror] : 'No se pudo guardar la calificación. Revisá los datos ingresados.';
                ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if (empty($courses)): ?>

        <div class="empty-message">
            No hay cursos con foros <strong>TP-</strong> disponibles para tu usuario.
        </div>

    <?php elseif ($selected_course && empty($forums)): ?>

        <div class="empty-message">
            El curso seleccionado no tiene foros cuyo nombre empiece con <strong>TP-</strong>.
        </div>

    <?php elseif ($selected_course): ?>

        <section class="table-panel">
            <div class="table-wrapper">
                <table class="report-table">
                    <thead>
                    <tr>
                        <th>Apellido</th>
                        <th>Nombre</th>
                        <?php foreach ($forums as $forum): ?>
                            <th><?php echo s($forum->name); ?></th>
                        <?php endforeach; ?>
                        <th>Cuatrimestre 1</th>
                        <th>Cuatrimestre 2</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($report_data as $data): ?>
                        <tr>
                            <td><?php echo $data['apellido']; ?></td>
                            <td><?php echo $data['nombre']; ?></td>

                            <?php foreach ($forums as $forum): ?>
                                <?php
                                    $links = $data['links'][$forum->id] ?? [];
                                    $currentgrade = $data['grades'][$forum->id] ?? null;
                                    $gradevalue = '';

                                    if ($currentgrade !== null && $currentgrade !== false) {
                                        $gradevalue = format_float($currentgrade, 0, false);
                                    }

                                    $isgradable = !empty($forum->grade_forum) && (float)$forum->grade_forum > 0;
                                    $cangradeforum = $canviewallgrades && !empty($forumgradepermissions[$forum->id]);
                                ?>

                                <td class="forum-cell">
                                    <?php if (!empty($links)): ?>
                                        <?php foreach ($links as $url): ?>
                                            <a href="<?php echo s($url); ?>"
                                               target="_blank"
                                               rel="noopener noreferrer">Ver</a><br>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="no-links">Sin enlace</span>
                                    <?php endif; ?>

                                    <?php if ($isgradable && $cangradeforum): ?>
                                        <form method="post"
                                              class="grade-form"
                                              action="<?php echo (new moodle_url('/reportes/reporteTPporCurso.php'))->out(false); ?>">
                                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                                            <input type="hidden" name="action" value="savegrade">
                                            <input type="hidden" name="courseid" value="<?php echo (int)$courseid; ?>">
                                            <input type="hidden" name="forumid" value="<?php echo (int)$forum->id; ?>">
                                            <input type="hidden" name="userid" value="<?php echo (int)$data['userid']; ?>">

                                            <input type="number"
                                                   class="grade-input"
                                                   name="grade"
                                                   min="0"
                                                   max="<?php echo (int)$forum->grade_forum; ?>"
                                                   step="1"
                                                   inputmode="numeric"
                                                   pattern="[0-9]*"
                                                   value="<?php echo s($gradevalue); ?>">

                                            <button type="submit" class="grade-save">Guardar</button>

                                            <span class="grade-max">/ <?php echo (int)$forum->grade_forum; ?></span>
                                        </form>
                                    <?php elseif ($isgradable): ?>
                                        <div class="grade-readonly">
                                            Nota: <?php echo ($gradevalue !== '') ? s($gradevalue) : '-'; ?> / <?php echo (int)$forum->grade_forum; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="grade-disabled">Foro no calificable</div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>

                            <?php foreach ([1, 2] as $period): ?>
                                <?php
                                    $pinfo = $periodgrades[$period][$data['userid']] ?? null;
                                    $periodtotal = (int)($pinfo['total'] ?? 0);
                                    $perioddelivered = (int)($pinfo['delivered'] ?? 0);

                                    // Valor mostrado (estudiante, solo lectura): nota persistida (manual) si existe, si no la calculada.
                                    $computedperiodgrade = (int)($pinfo['grade'] ?? 0);
                                    $savedperiodgrade = $savedperiodgrades[$period][$data['userid']] ?? null;
                                    $hasoverride = ($savedperiodgrade !== null && is_numeric($savedperiodgrade));
                                    $periodgrade = $hasoverride ? (int)$savedperiodgrade : $computedperiodgrade;

                                    // En edición (docente): el input solo se precarga con el override persistido;
                                    // la nota calculada se muestra como placeholder (sugerencia de auto-nota).
                                    $periodinputvalue = $hasoverride ? (int)$savedperiodgrade : '';
                                    $periodplaceholder = (string)$computedperiodgrade;
                                ?>
                                <td class="period-grade-cell">
                                    <?php if ($caneditperiodgrade): ?>
                                        <?php if ($periodtotal > 0): ?>
                                            <input type="number"
                                                   class="period-grade-input"
                                                   form="periodgrade-form"
                                                   name="periodgrade[<?php echo (int)$period; ?>][<?php echo (int)$data['userid']; ?>]"
                                                   min="0"
                                                   max="10"
                                                   step="1"
                                                   inputmode="numeric"
                                                   pattern="[0-9]*"
                                                   placeholder="<?php echo s($periodplaceholder); ?>"
                                                   value="<?php echo s((string)$periodinputvalue); ?>">
                                            <span class="period-grade-meta">(<?php echo $perioddelivered; ?>/<?php echo $periodtotal; ?>)</span>
                                        <?php else: ?>
                                            <span class="period-grade-empty">—</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="period-grade-readonly">
                                            <?php echo ($periodtotal > 0) ? ($periodgrade . ' / 10') : '—'; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($caneditperiodgrade): ?>
            <form id="periodgrade-form"
                  method="post"
                  action="<?php echo (new moodle_url('/reportes/reporteTPporCurso.php'))->out(false); ?>">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <input type="hidden" name="action" value="saveperiodgrade">
                <input type="hidden" name="courseid" value="<?php echo (int)$courseid; ?>">

                <button type="submit" class="btn btn-success">Guardar notas del período</button>
            </form>
        <?php endif; ?>

    <?php endif; ?>

</main>

</body>
</html>
