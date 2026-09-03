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
 * Period management page: list Cuatrimestre 1/2 with status and actions.
 *
 * @package    local_tpperiods
 * @copyright  2026 arteytecnologia.com.ar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$coursecontext = context_course::instance($course->id);
require_capability('moodle/course:manageactivities', $coursecontext);

$url = new moodle_url('/local/tpperiods/manage.php', ['courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('managetitle', 'local_tpperiods'));
$PAGE->set_heading(format_string($course->fullname));

$periods = [LOCAL_TPPERIODS_PERIOD_1, LOCAL_TPPERIODS_PERIOD_2];

// Handle POST actions. Each action changes the period status via an upsert
// keyed by (courseid, period). Actions require a valid sesskey.
$action = optional_param('action', '', PARAM_ALPHA);
$period = optional_param('period', 0, PARAM_INT);

if ($action !== '' && in_array($period, $periods, true)) {
    if (!in_array($action, ['open', 'close', 'reopen'], true)) {
        $action = '';
    } else {
        require_sesskey();

        $status = ($action === 'close')
            ? LOCAL_TPPERIODS_STATUS_CLOSED_FOR_PLANNING
            : LOCAL_TPPERIODS_STATUS_OPEN;

        $record = $DB->get_record('local_tpperiods_period', ['courseid' => $courseid, 'period' => $period]);
        $now = time();

        if ($record) {
            $record->status = $status;
            $record->timemodified = $now;
            $DB->update_record('local_tpperiods_period', $record);
        } else {
            $newrecord = new stdClass();
            $newrecord->courseid = $courseid;
            $newrecord->period = $period;
            $newrecord->status = $status;
            $newrecord->timecreated = $now;
            $newrecord->timemodified = $now;
            $DB->insert_record('local_tpperiods_period', $newrecord);
        }

        redirect($url);
    }
}

echo $OUTPUT->header();

$table = new html_table();
$table->head = [
    get_string('period', 'local_tpperiods'),
    get_string('status', 'local_tpperiods'),
    get_string('actions', 'local_tpperiods'),
];
$table->data = [];

foreach ($periods as $periodvalue) {
    $status = local_tpperiods_get_period_status($courseid, $periodvalue);

    switch ($status) {
        case LOCAL_TPPERIODS_STATUS_OPEN:
            $statuslabel = get_string('statusopen', 'local_tpperiods');
            $actionname = 'close';
            $actionlabel = get_string('actionclose', 'local_tpperiods');
            break;
        case LOCAL_TPPERIODS_STATUS_CLOSED_FOR_PLANNING:
            $statuslabel = get_string('statusclosed', 'local_tpperiods');
            $actionname = 'reopen';
            $actionlabel = get_string('actionreopen', 'local_tpperiods');
            break;
        case LOCAL_TPPERIODS_STATUS_NOT_CONFIGURED:
        default:
            $statuslabel = get_string('statusnotconfigured', 'local_tpperiods');
            $actionname = 'open';
            $actionlabel = get_string('actionopen', 'local_tpperiods');
            break;
    }

    $periodlabel = ($periodvalue === LOCAL_TPPERIODS_PERIOD_1)
        ? get_string('period1', 'local_tpperiods')
        : get_string('period2', 'local_tpperiods');

    $actionurl = new moodle_url('/local/tpperiods/manage.php', [
        'courseid' => $courseid,
        'action'   => $actionname,
        'period'   => $periodvalue,
    ]);

    $button = $OUTPUT->single_button($actionurl, $actionlabel, 'post');

    $table->data[] = [$periodlabel, $statuslabel, $button];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
