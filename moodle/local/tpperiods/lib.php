<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Public API and course module lifecycle callbacks for local_tpperiods.
 *
 * @package    local_tpperiods
 * @copyright  2026 arteytecnologia.com.ar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Academic period assignment domain (course module -> period).
define('LOCAL_TPPERIODS_PERIOD_UNASSIGNED', 0);
define('LOCAL_TPPERIODS_PERIOD_1', 1);
define('LOCAL_TPPERIODS_PERIOD_2', 2);

// Academic period configuration status domain.
define('LOCAL_TPPERIODS_STATUS_OPEN', 0);
define('LOCAL_TPPERIODS_STATUS_CLOSED_FOR_PLANNING', 1);
// Return-only value, never stored in the database.
define('LOCAL_TPPERIODS_STATUS_NOT_CONFIGURED', -1);

/**
 * Return the academic period assigned to a course module.
 *
 * @param int $cmid Course module id.
 * @return int 0 (UNASSIGNED) if no row, otherwise 1|2.
 */
function local_tpperiods_get_activity_period(int $cmid): int {
    global $DB;

    $record = $DB->get_record('local_tpperiods_cmperiod', ['cmid' => $cmid]);
    if (!$record) {
        return LOCAL_TPPERIODS_PERIOD_UNASSIGNED;
    }

    return (int) $record->period;
}

/**
 * Return the course module ids of a course assigned to the given period.
 *
 * Joins with {course_modules} so the period rows are filtered by course.
 *
 * @param int $courseid Course id.
 * @param int $period Academic period (1|2).
 * @return int[] Array of cmid ints (may be empty).
 */
function local_tpperiods_get_period_cmids(int $courseid, int $period): array {
    global $DB;

    $sql = "SELECT cm.id
              FROM {local_tpperiods_cmperiod} p
              JOIN {course_modules} cm ON cm.id = p.cmid
             WHERE cm.course = :courseid
               AND p.period = :period";

    $cmids = $DB->get_fieldset_sql($sql, ['courseid' => $courseid, 'period' => $period]);

    return array_map('intval', $cmids);
}

/**
 * Return the configured periods (1|2) of a course.
 *
 * Read from the explicit configuration table, never inferred from
 * assignment rows: an OPEN period with zero activities is still listed.
 *
 * @param int $courseid Course id.
 * @return int[] Array of period ints (1|2), possibly empty.
 */
function local_tpperiods_get_configured_periods(int $courseid): array {
    global $DB;

    $periods = $DB->get_fieldset_select('local_tpperiods_period', 'period', 'courseid = :courseid',
        ['courseid' => $courseid]);

    $result = array_map('intval', $periods);
    sort($result);

    return $result;
}

/**
 * Return the status of a period for a course.
 *
 * @param int $courseid Course id.
 * @param int $period Academic period (1|2).
 * @return int STATUS_NOT_CONFIGURED(-1) if no row, else stored status (0|1).
 */
function local_tpperiods_get_period_status(int $courseid, int $period): int {
    global $DB;

    $record = $DB->get_record('local_tpperiods_period', ['courseid' => $courseid, 'period' => $period]);
    if (!$record) {
        return LOCAL_TPPERIODS_STATUS_NOT_CONFIGURED;
    }

    return (int) $record->status;
}

/**
 * Add the "Cuatrimestre" field to the course module edit form (forum only).
 *
 * @param moodleform_mod $formwrapper The module form wrapper.
 * @param MoodleQuickForm $mform The underlying form.
 */
function local_tpperiods_coursemodule_standard_elements($formwrapper, $mform) {
    // R9 gate: module type source must be CREATE/EDIT-safe. get_coursemodule()
    // is NULL on CREATE, so use get_current()->modulename instead.
    $current = $formwrapper->get_current();
    if (empty($current->modulename) || $current->modulename !== 'forum') {
        return;
    }

    $periodoptions = [
        LOCAL_TPPERIODS_PERIOD_UNASSIGNED => get_string('unassigned', 'local_tpperiods'),
        LOCAL_TPPERIODS_PERIOD_1 => '1',
        LOCAL_TPPERIODS_PERIOD_2 => '2',
    ];

    $mform->addElement('select', 'local_tpperiods_period', get_string('fieldlabel', 'local_tpperiods'), $periodoptions);

    $cm = $formwrapper->get_coursemodule();
    if ($cm) {
        // EDIT: preload the persisted value.
        $currentperiod = local_tpperiods_get_activity_period((int) $cm->id);
        $mform->setDefault('local_tpperiods_period', $currentperiod);

        // If the current assignment belongs to a CLOSED period, freeze the select.
        if ($currentperiod !== LOCAL_TPPERIODS_PERIOD_UNASSIGNED) {
            $courseid = (int) $cm->course;
            if (local_tpperiods_get_period_status($courseid, $currentperiod) === LOCAL_TPPERIODS_STATUS_CLOSED_FOR_PLANNING) {
                $mform->freeze('local_tpperiods_period');
                $mform->addElement('static', 'local_tpperiods_closednote', '',
                    get_string('closednote', 'local_tpperiods'));
            }
        }
    } else {
        // CREATE: default "Sin asignar".
        $mform->setDefault('local_tpperiods_period', LOCAL_TPPERIODS_PERIOD_UNASSIGNED);
    }
}

/**
 * Persist or remove the period assignment after a course module is saved.
 *
 * @param stdClass $data The module info submitted by the form.
 * @param stdClass $course The course record.
 * @return stdClass The (possibly unchanged) module info.
 */
function local_tpperiods_coursemodule_edit_post_actions($data, $course) {
    global $DB;

    if (empty($data->coursemodule)) {
        return $data;
    }
    if (empty($data->modulename) || $data->modulename !== 'forum') {
        return $data;
    }
    if (!isset($data->local_tpperiods_period)) {
        // Field not rendered/present: preserve existing mapping.
        return $data;
    }

    $cmid = (int) $data->coursemodule;

    $context = context_module::instance($cmid);
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return $data;
    }

    $submitted = (int) $data->local_tpperiods_period;
    if (!in_array($submitted,
            [LOCAL_TPPERIODS_PERIOD_UNASSIGNED, LOCAL_TPPERIODS_PERIOD_1, LOCAL_TPPERIODS_PERIOD_2], true)) {
        // Invalid submitted value: ignore.
        return $data;
    }

    $currentperiod = local_tpperiods_get_activity_period($cmid);

    if ($currentperiod === $submitted) {
        // No-op.
        return $data;
    }

    // Server-side membership protection (R2/R6): a change is applied only if
    // both the LEAVING period (if any) and the JOINING period (if any) are not
    // CLOSED_FOR_PLANNING. Otherwise the existing mapping is preserved.
    $courseid = (int) $course->id;
    $leaving = ($currentperiod !== LOCAL_TPPERIODS_PERIOD_UNASSIGNED) ? $currentperiod : null;
    $joining = ($submitted !== LOCAL_TPPERIODS_PERIOD_UNASSIGNED) ? $submitted : null;

    if ($leaving !== null) {
        if (local_tpperiods_get_period_status($courseid, $leaving) === LOCAL_TPPERIODS_STATUS_CLOSED_FOR_PLANNING) {
            return $data;
        }
    }
    if ($joining !== null) {
        if (local_tpperiods_get_period_status($courseid, $joining) === LOCAL_TPPERIODS_STATUS_CLOSED_FOR_PLANNING) {
            return $data;
        }
    }

    $now = time();

    if ($submitted === LOCAL_TPPERIODS_PERIOD_UNASSIGNED) {
        // Leaving period was OPEN: delete the row.
        $DB->delete_records('local_tpperiods_cmperiod', ['cmid' => $cmid]);
    } else {
        // Upsert (never insert a duplicate cmid).
        $record = $DB->get_record('local_tpperiods_cmperiod', ['cmid' => $cmid]);
        if ($record) {
            $record->period = $submitted;
            $record->timemodified = $now;
            $DB->update_record('local_tpperiods_cmperiod', $record);
        } else {
            $newrecord = new stdClass();
            $newrecord->cmid = $cmid;
            $newrecord->period = $submitted;
            $newrecord->timecreated = $now;
            $newrecord->timemodified = $now;
            $DB->insert_record('local_tpperiods_cmperiod', $newrecord);
        }
    }

    return $data;
}

/**
 * Remove the period assignment before the course module is deleted (orphan cleanup).
 *
 * @param stdClass $cm Full course module record.
 */
function local_tpperiods_pre_course_module_delete($cm) {
    global $DB;

    if (empty($cm->id)) {
        return;
    }

    $DB->delete_records('local_tpperiods_cmperiod', ['cmid' => $cm->id]);
}

/**
 * Add a "Gestión de cuatrimestres" node to the course administration navigation.
 *
 * @param navigation_node $coursenode The course administration node.
 * @param stdClass $course The course record.
 * @param context_course $coursecontext The course context.
 */
function local_tpperiods_extend_navigation_course($coursenode, $course, $coursecontext) {
    if (!has_capability('moodle/course:manageactivities', $coursecontext)) {
        return;
    }

    $url = new moodle_url('/local/tpperiods/manage.php', ['courseid' => $course->id]);
    $coursenode->add(
        get_string('managelink', 'local_tpperiods'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_tpperiods_manage',
        new pix_icon('i/settings', '')
    );
}
