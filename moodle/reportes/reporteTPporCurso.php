<?php
// reporteTPporCurso.php

require_once(__DIR__ . '/../config.php');
require_login();

global $DB, $CFG, $PAGE, $USER;

require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/gradelib.php');

// Parámetros.
$courseid = optional_param('courseid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_TEXT);
$saved = optional_param('saved', 0, PARAM_INT);
$gradeerror = optional_param('gradeerror', '', PARAM_ALPHANUMEXT);
$action = optional_param('action', '', PARAM_ALPHA);

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

    require_capability('mod/forum:grade', $modulecontext);

    // Solo se guarda sobre "Whole forum grading", no sobre ratings de mensajes.
    if (empty($forum->grade_forum) || (float)$forum->grade_forum <= 0) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'notgradable'
        ]));
    }

    $gradevalue = trim(str_replace(',', '.', $gradevalue));

    if ($gradevalue === '') {
        $rawgrade = null;
    } else if (!is_numeric($gradevalue)) {
        redirect(new moodle_url('/reportes/reporteTPporCurso.php', [
            'courseid' => $postcourseid,
            'gradeerror' => 'invalid'
        ]));
    } else {
        $rawgrade = (float)$gradevalue;

        if ($rawgrade < 0 || $rawgrade > (float)$forum->grade_forum) {
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

// Obtener lista de cursos que tienen foros TP-*.
$courses = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname
    FROM {course} c
    JOIN {forum} f ON f.course = c.id
    WHERE f.name LIKE :prefix
    ORDER BY c.shortname
", [
    'prefix' => 'TP-%'
]);

// Si no viene courseid por URL, seleccionar el primer curso disponible.
if (!$courseid && !empty($courses)) {
    $firstcourse = reset($courses);
    $courseid = (int)$firstcourse->id;
}

$selected_course = null;
$forums = [];
$students = [];
$report_data = [];
$forumgradevalues = [];

if ($courseid) {
    $selected_course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

    // Obtener foros TP-% para este curso.
    $forums = $DB->get_records_sql("
        SELECT id, name, course, grade_forum
        FROM {forum}
        WHERE course = :cid
          AND name LIKE :prefix
        ORDER BY name
    ", [
        'cid' => $courseid,
        'prefix' => 'TP-%'
    ]);

    // Obtener estudiantes con rol estudiante para este curso.
    // En Moodle estándar, roleid = 5 suele ser estudiante.
    $students = $DB->get_records_sql("
        SELECT DISTINCT u.id, u.firstname, u.lastname
        FROM {user} u
        JOIN {role_assignments} ra ON ra.userid = u.id
        JOIN {context} cx ON cx.id = ra.contextid
        WHERE cx.contextlevel = :ctxlevel
          AND cx.instanceid = :cid
          AND ra.roleid = :studentrole
          AND u.deleted = 0
        ORDER BY u.lastname, u.firstname
    ", [
        'ctxlevel' => CONTEXT_COURSE,
        'cid' => $courseid,
        'studentrole' => 5
    ]);

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

            $msgs = $DB->get_records_sql("
                SELECT fp.id, fp.message
                FROM {forum_discussions} fd
                JOIN {forum_posts} fp ON fp.discussion = fd.id
                WHERE fd.forum = :fid
                  AND fp.userid = :uid
                ORDER BY fp.created ASC
            ", [
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
}

// Exportación a Excel.
if ($download === 'excel' && !empty($report_data)) {
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

// Opciones del selector.
$courseoptions = [];
foreach ($courses as $course) {
    $courseoptions[$course->id] = $course->shortname;
}

// Datos de usuario.
$currentuser = fullname($USER);
?>
<!DOCTYPE html>
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

        /* Columnas fijas: Apellido */
        .report-table th:nth-child(1),
        .report-table td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 10;
        }

        /* Columnas fijas: Nombre */
        .report-table th:nth-child(2),
        .report-table td:nth-child(2) {
            position: sticky;
            left: var(--apellido-width);
            z-index: 10;
            box-shadow: 3px 0 4px rgba(0, 0, 0, 0.12);
        }

        /* Encabezados fijos arriba y a la izquierda */
        .report-table thead th:nth-child(1),
        .report-table thead th:nth-child(2) {
            top: 0;
            z-index: 30;
            background: #fff;
        }

        /* Fondo real para las columnas fijas según fila impar/par */
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

        .grade-disabled {
            margin-top: 6px;
            font-size: 12px;
            color: #777;
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

            <?php if ($selected_course): ?>
                <a class="btn btn-success"
                   href="<?php echo (new moodle_url('/reportes/reporteTPporCurso.php', [
                       'courseid' => $courseid,
                       'download' => 'excel'
                   ]))->out(false); ?>">
                    Exportar a Excel
                </a>
            <?php endif; ?>
        </form>

        <?php if ($saved): ?>
            <div class="notice notice-success">
                Calificación guardada correctamente.
            </div>
        <?php endif; ?>

        <?php if ($gradeerror): ?>
            <div class="notice notice-error">
                No se pudo guardar la calificación. Revisá que el foro tenga habilitada la calificación del foro completo y que la nota esté dentro del rango permitido.
            </div>
        <?php endif; ?>
    </section>

    <?php if (empty($courses)): ?>

        <div class="empty-message">
            No se encontraron cursos con foros cuyo nombre empiece con <strong>TP-</strong>.
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
                                        $gradevalue = format_float($currentgrade, 2, false);
                                    }

                                    $isgradable = !empty($forum->grade_forum) && (float)$forum->grade_forum > 0;
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

                                    <?php if ($isgradable): ?>
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
                                                   max="<?php echo s($forum->grade_forum); ?>"
                                                   step="0.1"
                                                   value="<?php echo s($gradevalue); ?>">

                                            <button type="submit" class="grade-save">Guardar</button>

                                            <span class="grade-max">/ <?php echo s($forum->grade_forum); ?></span>
                                        </form>
                                    <?php else: ?>
                                        <div class="grade-disabled">Foro no calificable</div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
