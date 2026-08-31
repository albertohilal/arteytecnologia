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
 * CLI migrator: TP period metadata migration (2026) for courses 15, 19, 20.
 *
 * Loads explicit academic-period metadata into local_tpperiods, replacing
 * implicit/hardcoded knowledge. Conforms to Spec #359 and Design #360 (v3),
 * rectified per review-ledger #361 (defects D1-D6).
 *
 * Modes:
 *   --check    Read-only dry-run. Zero writes. Classifies targets.
 *   --execute  Apply migration (requires --backup-path and --backup-sha256).
 *   --help     Show usage.
 *
 * Exactly ONE of --help/--check/--execute is accepted. Zero or more than one
 * fails closed (exit 2). Backup arguments (--backup-path/--backup-sha256) are
 * valid only with --execute.
 *
 * Exit codes:
 *   0 PASS | 1 INTERNAL_ERROR | 2 USAGE_ERROR | 3 PRECHECK_FAILURE |
 *   4 CONFLICT | 5 EXECUTION_FAILURE | 6 VERIFICATION_FAILURE | 7 BACKUP_FAILURE
 *
 * This script NEVER creates backups, never restores, never shells out, and
 * writes ONLY the two plugin tables via Moodle DML.
 *
 * @package    local_tpperiods
 * @category   cli
 * @copyright  2026 arteytecnologia.com.ar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Exit codes (frozen by Design #360).
define('MIGRATOR_EXIT_PASS', 0);
define('MIGRATOR_EXIT_INTERNAL_ERROR', 1);
define('MIGRATOR_EXIT_USAGE_ERROR', 2);
define('MIGRATOR_EXIT_PRECHECK_FAILURE', 3);
define('MIGRATOR_EXIT_CONFLICT', 4);
define('MIGRATOR_EXIT_EXECUTION_FAILURE', 5);
define('MIGRATOR_EXIT_VERIFICATION_FAILURE', 6);
define('MIGRATOR_EXIT_BACKUP_FAILURE', 7);

// Environment expectations (frozen).
define('MIGRATOR_EXPECTED_PLUGIN_VERSION', 2026082900);

// Period status domain (mirrors lib.php, self-contained for the CLI).
define('MIGRATOR_STATUS_OPEN', 0);
define('MIGRATOR_STATUS_CLOSED_FOR_PLANNING', 1);

/**
 * Custom exceptions for fail-closed control flow.
 */
class migrator_conflict_exception extends Exception {
}
class migrator_verification_exception extends Exception {
}

/**
 * Frozen target data, verbatim from Spec #359 / Design #360 (v3).
 *
 * Declarative ONLY: no title parser, no cmid ranges, no cmid ordering,
 * no section/position as source of truth.
 *
 * Name-drift policy:
 *   - UNASSIGNED rows carry a `name` (secondary identity, from Spec #359):
 *     a name difference is recorded as SOFT_NAME_DRIFT, never a hard conflict,
 *     and never determines period membership.
 *   - MAPPED (63) targets do NOT carry names: Spec #359/#360 do not contain
 *     expected names for the mapped targets, so no name is invented here.
 *     Hard identity (cmid, course, module, forumid) is the source of truth.
 *
 * @return array The complete frozen target data.
 */
function migrator_target_data() {
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $data = array(
        // courseid => period => [cmid => forumid].
        'mappings' => array(
            15 => array(
                1 => array(
                    243 => 158, 244 => 159, 247 => 160, 248 => 161, 249 => 162,
                    250 => 163, 253 => 164, 254 => 165, 255 => 166,
                ),
                2 => array(
                    256 => 167, 257 => 168, 258 => 169, 259 => 170, 261 => 171,
                    263 => 172, 264 => 173, 265 => 174, 266 => 175, 267 => 176,
                    268 => 177, 269 => 178, 270 => 179, 271 => 180, 272 => 181,
                    273 => 182, 274 => 183, 275 => 184, 276 => 185,
                ),
            ),
            19 => array(
                1 => array(
                    439 => 305, 384 => 261, 385 => 262, 386 => 263, 388 => 264,
                    389 => 265, 391 => 266, 392 => 267, 393 => 268,
                ),
                2 => array(
                    394 => 269, 395 => 270, 396 => 271, 397 => 272, 398 => 273,
                    399 => 274, 400 => 275,
                ),
            ),
            20 => array(
                1 => array(
                    402 => 277, 415 => 287, 416 => 288, 418 => 289, 419 => 290,
                    423 => 291, 424 => 292, 425 => 293, 426 => 294, 427 => 295,
                    429 => 296, 430 => 297, 431 => 298, 432 => 299,
                ),
                2 => array(
                    433 => 300, 434 => 301, 435 => 302, 436 => 303, 437 => 304,
                ),
            ),
        ),

        // courseid => list of [cmid, forumid, forum_type, name, reason].
        // forum_type in {news, general}; reason in {news, help, deletion}.
        // name is secondary identity (SOFT_NAME_DRIFT only), never a period source.
        'unassigned' => array(
            15 => array(
                array(238, 157, 'news', 'Programa de la asignatura', 'news'),
            ),
            19 => array(
                array(378, 260, 'news', 'Avisos', 'news'),
            ),
            20 => array(
                array(401, 276, 'news', 'Announcements', 'news'),
                array(406, 278, 'general', 'Como responder en la plataforma', 'help'),
                array(407, 279, 'general', 'Publicar un enlace de un archivo en el Foro de la plataforma', 'help'),
                array(408, 280, 'general', 'Cómo responder a un foro en Moodle desde el celular ?', 'help'),
                array(409, 281, 'general', 'TP-00-1-Superposición de figura geometría', 'deletion'),
                array(410, 282, 'general', 'TP-01-2-Superposición de figura humana', 'deletion'),
                array(411, 283, 'general', 'TP-01-3-Disminución de tamaño', 'deletion'),
                array(412, 284, 'general', 'TP-01-4-Arriba abajo, dirección', 'deletion'),
                array(413, 285, 'general', 'TP-01-5-Avance y retroceso del color', 'deletion'),
                array(414, 286, 'general', 'TP-01-6-Escorzo', 'deletion'),
            ),
        ),

        // list of [courseid, period, status].
        'status_targets' => array(
            array(15, 1, MIGRATOR_STATUS_CLOSED_FOR_PLANNING),
            array(15, 2, MIGRATOR_STATUS_OPEN),
            array(19, 1, MIGRATOR_STATUS_CLOSED_FOR_PLANNING),
            array(19, 2, MIGRATOR_STATUS_OPEN),
            array(20, 1, MIGRATOR_STATUS_CLOSED_FOR_PLANNING),
            array(20, 2, MIGRATOR_STATUS_OPEN),
        ),
    );

    return $data;
}

/**
 * Emit one line of KEY=VALUE output.
 *
 * @param string $key   Output key.
 * @param mixed  $value Output value (scalar).
 */
function migrator_out($key, $value) {
    cli_writeln($key . '=' . $value);
}

/**
 * Print usage and fail closed.
 */
function migrator_usage() {
    fwrite(STDERR, "Usage: php migrate_period_metadata_2026.php --check --expected-dbname=<dbname> | "
        . "--execute --expected-dbname=<dbname> --backup-path=<file> --backup-sha256=<hex> | --help\n");
    exit(MIGRATOR_EXIT_USAGE_ERROR);
}

/**
 * Print help text.
 */
function migrator_print_help() {
    cli_writeln('TP period metadata migrator (2026) — local_tpperiods');
    cli_writeln('');
    cli_writeln('Usage:');
    cli_writeln('  php migrate_period_metadata_2026.php --check --expected-dbname=<dbname>');
    cli_writeln('  php migrate_period_metadata_2026.php --execute --expected-dbname=<dbname> --backup-path=<file> --backup-sha256=<hex>');
    cli_writeln('  php migrate_period_metadata_2026.php --help');
    cli_writeln('');
    cli_writeln('Exactly one of --help/--check/--execute is required (zero or more than one fails closed, exit 2).');
    cli_writeln('--expected-dbname=<dbname> is required for --check and --execute.');
    cli_writeln('--backup-path/--backup-sha256 are valid only with --execute.');
}

/**
 * Parse CLI arguments. Unknown arguments fail closed.
 *
 * @param array $argv Raw argv from the shell.
 * @return array Parsed options.
 */
function migrator_parse_args(array $argv) {
    $opts = array(
        'check' => false,
        'execute' => false,
        'help' => false,
        'backup-path' => null,
        'backup-sha256' => null,
        'expected-dbname' => null,
    );

    foreach ($argv as $i => $arg) {
        if ($i === 0) {
            continue;
        }
        if ($arg === '--check') {
            $opts['check'] = true;
            continue;
        }
        if ($arg === '--execute') {
            $opts['execute'] = true;
            continue;
        }
        if ($arg === '--help' || $arg === '-h') {
            $opts['help'] = true;
            continue;
        }
        if (strpos($arg, '--backup-path=') === 0) {
            $opts['backup-path'] = substr($arg, strlen('--backup-path='));
            continue;
        }
        if (strpos($arg, '--backup-sha256=') === 0) {
            $opts['backup-sha256'] = substr($arg, strlen('--backup-sha256='));
            continue;
        }
        if (strpos($arg, '--expected-dbname=') === 0) {
            $opts['expected-dbname'] = substr($arg, strlen('--expected-dbname='));
            continue;
        }
        fwrite(STDERR, "Unknown argument: $arg\n");
        migrator_usage();
    }

    return $opts;
}

/**
 * Resolve the run mode ('help'|'check'|'execute'). Fails closed if the mode
 * count is not exactly one, or if backup arguments appear without --execute.
 *
 * @param array $opts Parsed options.
 * @return string 'help', 'check', or 'execute'.
 */
function migrator_resolve_mode(array $opts) {
    $modes = array();
    if ($opts['help']) {
        $modes[] = 'help';
    }
    if ($opts['check']) {
        $modes[] = 'check';
    }
    if ($opts['execute']) {
        $modes[] = 'execute';
    }
    if (count($modes) !== 1) {
        fwrite(STDERR, "Error: exactly one of --help, --check, --execute is required.\n");
        migrator_usage();
    }

    $mode = $modes[0];

    $hasbackupargs = ($opts['backup-path'] !== null || $opts['backup-sha256'] !== null);
    if ($hasbackupargs && $mode !== 'execute') {
        fwrite(STDERR, "Error: --backup-path/--backup-sha256 are only valid with --execute.\n");
        migrator_usage();
    }

    if ($mode !== 'help') {
        $dbname = isset($opts['expected-dbname']) ? trim($opts['expected-dbname']) : '';
        if ($dbname === '') {
            fwrite(STDERR, "Error: --expected-dbname=<dbname> is required for --check and --execute.\n");
            migrator_usage();
        }
    }

    return $mode;
}

/**
 * Emit a precheck failure and exit with PRECHECK_FAILURE.
 *
 * @param string $label  Precheck label (Pxx).
 * @param string $reason Human-readable reason.
 */
function migrator_precheck_fail($label, $reason) {
    migrator_out('PRECHECK', 'FAIL');
    migrator_out('PRECHECK_FAILED', $label);
    migrator_out('REASON', $reason);
    migrator_out('RESULT', 'FAIL');
    migrator_out('EXIT_CODE', MIGRATOR_EXIT_PRECHECK_FAILURE);
    exit(MIGRATOR_EXIT_PRECHECK_FAILURE);
}

/**
 * Return the module id for the 'forum' module, or null if absent.
 *
 * @return int|null
 */
function migrator_forum_module_id() {
    global $DB;

    $module = $DB->get_record('modules', array('name' => 'forum'), 'id');
    if (!$module) {
        return null;
    }

    return (int) $module->id;
}

/**
 * Confirm that a UNIQUE index over the exact expected columns exists.
 *
 * Uses the read-only core index inspection (moodle_database::get_indexes),
 * which returns per-index 'unique' + 'columns', confirming both uniqueness and
 * exact column membership (D1). Never uses DDL.
 *
 * @param string $tablename       Table name (without prefix).
 * @param array  $expectedcolumns Ordered list of expected column names.
 * @return bool True if a unique index over exactly those columns exists.
 */
function migrator_unique_index_exists($tablename, array $expectedcolumns) {
    global $DB;

    $manager = $DB->get_manager();
    if (!$manager->table_exists($tablename)) {
        return false;
    }

    $indexes = $DB->get_indexes($tablename);
    foreach ($indexes as $indexname => $index) {
        $columns = array_values($index['columns']);
        if ($columns == $expectedcolumns && !empty($index['unique'])) {
            return true;
        }
    }

    return false;
}

/**
 * Detect a duplicate-key (unique constraint) write violation.
 *
 * dml_write_exception carries the raw driver error in ->error. For MariaDB a
 * duplicate-key insert yields "Duplicate entry '...' for key '...'".
 *
 * @param dml_write_exception $e The write exception.
 * @return bool True if this is a unique/duplicate-key violation.
 */
function migrator_is_duplicate_key_error($e) {
    if (!($e instanceof dml_write_exception)) {
        return false;
    }
    return (strpos($e->error, 'Duplicate entry') !== false);
}

/**
 * Capture the critical invariant counts (same-run BEFORE/AFTER baseline).
 *
 * @return array Map of table => count.
 */
function migrator_capture_critical_counts() {
    global $DB;

    return array(
        'grade_items' => (int) $DB->count_records('grade_items'),
        'grade_grades' => (int) $DB->count_records('grade_grades'),
        'forum_grades' => (int) $DB->count_records('forum_grades'),
        'forum' => (int) $DB->count_records('forum'),
        'course_modules' => (int) $DB->count_records('course_modules'),
    );
}

/**
 * Hard identity validation of a mapped target cmid.
 *
 * @param int $courseid      Expected course id.
 * @param int $cmid          Expected course module id.
 * @param int $forumid       Expected forum instance id.
 * @param int $forummoduleid The forum module id (from {modules}).
 * @return array [bool ok, string reason].
 */
function migrator_validate_mapped($courseid, $cmid, $forumid, $forummoduleid) {
    global $DB;

    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    if (!$cm) {
        return array(false, 'cmid missing');
    }
    if ((int) $cm->course !== (int) $courseid) {
        return array(false, 'course mismatch');
    }
    if ((int) $cm->module !== (int) $forummoduleid) {
        return array(false, 'not a forum module');
    }
    if ((int) $cm->instance !== (int) $forumid) {
        return array(false, 'forum instance mismatch');
    }
    if ((int) $cm->deletioninprogress !== 0) {
        return array(false, 'deletioninprogress != 0');
    }
    $forum = $DB->get_record('forum', array('id' => $forumid));
    if (!$forum) {
        return array(false, 'forum missing');
    }
    if ((int) $forum->course !== (int) $courseid) {
        return array(false, 'forum course mismatch');
    }

    return array(true, '');
}

/**
 * Negative identity validation of an unassigned target cmid (D6).
 *
 * Verifies: cmid exists; course correct; module forum; instance/forumid exact;
 * forum record exists; forum.course correct; forum.type equals expected
 * forum_type; deletion state matches reason. Name is NOT a hard criterion —
 * a name difference is returned as drift (SOFT_NAME_DRIFT) and never blocks.
 *
 * @param int    $courseid      Expected course id.
 * @param int    $cmid          Expected course module id.
 * @param int    $forumid       Expected forum instance id.
 * @param string $forumtype     Expected forum type ('news'|'general').
 * @param string $name          Expected forum name (secondary identity).
 * @param string $reason        Unassigned category: news|help|deletion.
 * @param int    $forummoduleid The forum module id (from {modules}).
 * @return array [bool ok, string reason, bool name_drift].
 */
function migrator_validate_unassigned($courseid, $cmid, $forumid, $forumtype, $name, $reason, $forummoduleid) {
    global $DB;

    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    if (!$cm) {
        return array(false, 'cmid missing', false);
    }
    if ((int) $cm->course !== (int) $courseid) {
        return array(false, 'course mismatch', false);
    }
    if ((int) $cm->module !== (int) $forummoduleid) {
        return array(false, 'not a forum module', false);
    }
    if ((int) $cm->instance !== (int) $forumid) {
        return array(false, 'forum instance mismatch', false);
    }
    if ($DB->record_exists('local_tpperiods_cmperiod', array('cmid' => $cmid))) {
        return array(false, 'unexpected existing mapping', false);
    }

    $forum = $DB->get_record('forum', array('id' => $forumid));
    if (!$forum) {
        return array(false, 'forum missing', false);
    }
    if ((int) $forum->course !== (int) $courseid) {
        return array(false, 'forum course mismatch', false);
    }
    if ($forum->type !== $forumtype) {
        return array(false, "forum type mismatch (expected $forumtype, got {$forum->type})", false);
    }

    $deletioninprogress = (int) $cm->deletioninprogress;
    if ($reason === 'deletion') {
        if ($deletioninprogress !== 1) {
            return array(false, 'expected deletioninprogress=1 for deletion row', false);
        }
    } else {
        if ($deletioninprogress !== 0) {
            return array(false, 'unexpected deletioninprogress for news/help row', false);
        }
    }

    $drift = ($forum->name !== $name);

    return array(true, '', $drift);
}

/**
 * Verify external backup evidence (--execute only). Does NOT create backups.
 *
 * @param array $opts Parsed options (backup-path, backup-sha256).
 */
function migrator_verify_backup(array $opts) {
    $path = $opts['backup-path'];
    $sha256 = $opts['backup-sha256'];

    if ($path === null || $sha256 === null || $path === '' || $sha256 === '') {
        migrator_out('BACKUP_VERIFIED', 'NO');
        migrator_out('REASON', 'missing --backup-path or --backup-sha256');
        migrator_out('RESULT', 'FAIL');
        migrator_out('EXIT_CODE', MIGRATOR_EXIT_BACKUP_FAILURE);
        exit(MIGRATOR_EXIT_BACKUP_FAILURE);
    }
    if (!is_file($path) || !is_readable($path)) {
        migrator_out('BACKUP_VERIFIED', 'NO');
        migrator_out('REASON', 'backup file missing or unreadable');
        migrator_out('RESULT', 'FAIL');
        migrator_out('EXIT_CODE', MIGRATOR_EXIT_BACKUP_FAILURE);
        exit(MIGRATOR_EXIT_BACKUP_FAILURE);
    }
    $size = filesize($path);
    if ($size === false || $size <= 0) {
        migrator_out('BACKUP_VERIFIED', 'NO');
        migrator_out('REASON', 'backup file empty');
        migrator_out('RESULT', 'FAIL');
        migrator_out('EXIT_CODE', MIGRATOR_EXIT_BACKUP_FAILURE);
        exit(MIGRATOR_EXIT_BACKUP_FAILURE);
    }
    $actual = hash_file('sha256', $path);
    if ($actual === false || $actual !== strtolower(trim($sha256))) {
        migrator_out('BACKUP_VERIFIED', 'NO');
        migrator_out('REASON', 'backup SHA256 mismatch');
        migrator_out('RESULT', 'FAIL');
        migrator_out('EXIT_CODE', MIGRATOR_EXIT_BACKUP_FAILURE);
        exit(MIGRATOR_EXIT_BACKUP_FAILURE);
    }

    migrator_out('BACKUP_PATH', $path);
    migrator_out('BACKUP_SHA256', strtolower(trim($sha256)));
    migrator_out('BACKUP_SIZE', $size);
    migrator_out('BACKUP_VERIFIED', 'YES');
}

/**
 * Classify current DB state against the frozen targets (read-only).
 *
 * For each target: missing => INSERT, same => NOOP, different => CONFLICT.
 *
 * @return array ['cmperiod' => [ins,noop,conflict], 'period' => [ins,noop,conflict]].
 */
function migrator_classify() {
    global $DB;

    $target = migrator_target_data();

    $cmperiod = array(0, 0, 0);
    foreach ($target['mappings'] as $courseid => $periods) {
        foreach ($periods as $period => $cmap) {
            foreach ($cmap as $cmid => $forumid) {
                $existing = $DB->get_record('local_tpperiods_cmperiod', array('cmid' => $cmid));
                if (!$existing) {
                    $cmperiod[0]++;
                } else if ((int) $existing->period === (int) $period) {
                    $cmperiod[1]++;
                } else {
                    $cmperiod[2]++;
                }
            }
        }
    }

    $periodrows = array(0, 0, 0);
    foreach ($target['status_targets'] as $st) {
        list($courseid, $period, $status) = $st;
        $existing = $DB->get_record('local_tpperiods_period',
            array('courseid' => $courseid, 'period' => $period));
        if (!$existing) {
            $periodrows[0]++;
        } else if ((int) $existing->status === (int) $status) {
            $periodrows[1]++;
        } else {
            $periodrows[2]++;
        }
    }

    return array(
        'cmperiod' => $cmperiod,
        'period' => $periodrows,
    );
}

/**
 * Target-scoped read-back. Never uses a naive global COUNT.
 *
 * @return array [mapping_match, mapping_mismatch, status_match, status_mismatch, unassigned_with_mapping].
 */
function migrator_readback() {
    global $DB;

    $target = migrator_target_data();

    // Build expected per-cmid map and the flat cmid list.
    $expected = array();
    $cmids = array();
    foreach ($target['mappings'] as $courseid => $periods) {
        foreach ($periods as $period => $cmap) {
            foreach ($cmap as $cmid => $forumid) {
                $cmid = (int) $cmid;
                $cmids[] = $cmid;
                $expected[$cmid] = array('courseid' => (int) $courseid, 'period' => (int) $period);
            }
        }
    }

    // (a) cmperiod read-back via JOIN to course_modules (cmperiod has no courseid).
    list($in_sql, $in_params) = $DB->get_in_or_equal($cmids, SQL_PARAMS_QM);
    $sql = "SELECT p.cmid, cm.course AS courseid, p.period
              FROM {local_tpperiods_cmperiod} p
              JOIN {course_modules} cm ON cm.id = p.cmid
             WHERE p.cmid $in_sql";
    $rows = $DB->get_records_sql($sql, $in_params);

    $mapping_match = 0;
    $mapping_mismatch = 0;
    if ($rows) {
        foreach ($rows as $row) {
            $cmid = (int) $row->cmid;
            if (isset($expected[$cmid])
                    && (int) $row->courseid === $expected[$cmid]['courseid']
                    && (int) $row->period === $expected[$cmid]['period']) {
                $mapping_match++;
            } else {
                $mapping_mismatch++;
            }
        }
    }
    // Any expected cmid without a row is a mismatch.
    $missingcount = count($expected) - count($rows);
    if ($missingcount > 0) {
        $mapping_mismatch += $missingcount;
    }

    // (b) status read-back (period table DOES have courseid).
    $status_match = 0;
    $status_mismatch = 0;
    foreach ($target['status_targets'] as $st) {
        list($courseid, $period, $status) = $st;
        $existing = $DB->get_record('local_tpperiods_period',
            array('courseid' => $courseid, 'period' => $period));
        if ($existing && (int) $existing->status === (int) $status) {
            $status_match++;
        } else {
            $status_mismatch++;
        }
    }

    // (c) unassigned must remain without mapping.
    $unassignedcmids = array();
    foreach ($target['unassigned'] as $courseid => $list) {
        foreach ($list as $item) {
            $unassignedcmids[] = (int) $item[0];
        }
    }
    list($ua_sql, $ua_params) = $DB->get_in_or_equal($unassignedcmids, SQL_PARAMS_QM);
    $unassignedwithmapping = (int) $DB->count_records_select('local_tpperiods_cmperiod', "cmid $ua_sql", $ua_params);

    return array(
        'mapping_match' => $mapping_match,
        'mapping_mismatch' => $mapping_mismatch,
        'status_match' => $status_match,
        'status_mismatch' => $status_mismatch,
        'unassigned_with_mapping' => $unassignedwithmapping,
    );
}

/**
 * Rollback a delegated transaction and emit the requested exit code.
 *
 * rollback_delegated_transaction() rethrows the exception (and accepts
 * Exception or Throwable), so we re-catch it to make the exit code reachable
 * (Design #360 R4, rectification D4). This handles ANY pre-commit failure.
 *
 * @param mixed $transaction The moodle_transaction (or null).
 * @param mixed $e           The triggering Exception or Throwable.
 * @param int   $exitcode    The exit code to emit.
 */
function migrator_rollback_and_exit($transaction, $e, $exitcode) {
    global $DB;

    if ($transaction !== null) {
        try {
            $DB->rollback_delegated_transaction($transaction, $e);
        } catch (Throwable $rethrown) {
            // Rollback rethrows; the transaction is already rolled back here.
            migrator_out('TRANSACTION_COMMITTED', 'NO');
            migrator_out('RESULT', 'FAIL');
            migrator_out('EXIT_CODE', $exitcode);
            exit($exitcode);
        }
    }

    migrator_out('TRANSACTION_COMMITTED', 'NO');
    migrator_out('RESULT', 'FAIL');
    migrator_out('EXIT_CODE', $exitcode);
    exit($exitcode);
}

/**
 * Run prechecks P01..P17. Exits on first hard/precondition failure.
 *
 * Metadata conflicts (mapping/status CONFLICT) are NOT treated as precheck
 * failures here: they are classified downstream and reported as exit 4
 * (CONFLICT) by --check and --execute (rectification D3).
 *
 * @param string $mode          'check' or 'execute'.
 * @param array  $opts          Parsed options.
 * @param int    $forummoduleid The forum module id.
 * @return int SOFT_NAME_DRIFT count (name drift on the 12 unassigned).
 */
function migrator_run_prechecks($mode, array $opts, $forummoduleid) {
    global $DB, $CFG;

    // P01: reaching here means config.php bootstrap succeeded.

    // P02: CLI-only execution.
    if (!defined('CLI_SCRIPT') || PHP_SAPI !== 'cli') {
        migrator_precheck_fail('P02', 'must be run from CLI');
    }

    // P03: active DB identity (never print dbuser/dbpass).
    if (empty($CFG->prefix) || $CFG->prefix !== 'mdl_') {
        migrator_precheck_fail('P03', 'unexpected db prefix');
    }
    $expecteddbname = isset($opts['expected-dbname']) ? trim($opts['expected-dbname']) : '';
    if (empty($CFG->dbname) || $CFG->dbname !== $expecteddbname) {
        migrator_precheck_fail('P03', 'unexpected dbname (expected ' . $expecteddbname . ', got ' . $CFG->dbname . ')');
    }

    // P04: plugin installed with expected version.
    $pluginversion = (int) get_config('local_tpperiods', 'version');
    if ($pluginversion < MIGRATOR_EXPECTED_PLUGIN_VERSION) {
        migrator_precheck_fail('P04', 'plugin version too old');
    }

    // P05: tables and unique indexes exist (read-only index inspection, D1).
    if (!migrator_unique_index_exists('local_tpperiods_cmperiod', array('cmid'))) {
        migrator_precheck_fail('P05', 'unique index on cmid missing');
    }
    if (!migrator_unique_index_exists('local_tpperiods_period', array('courseid', 'period'))) {
        migrator_precheck_fail('P05', 'unique index on (courseid, period) missing');
    }

    // P06: target courses exist.
    foreach (array(15, 19, 20) as $courseid) {
        if (!$DB->record_exists('course', array('id' => $courseid))) {
            migrator_precheck_fail('P06', "course $courseid missing");
        }
    }

    // P07-P09, P12: mapped targets hard identity + duplicate detection.
    $target = migrator_target_data();
    $seencmids = array();
    $duplicates = array();
    foreach ($target['mappings'] as $courseid => $periods) {
        foreach ($periods as $period => $cmap) {
            foreach ($cmap as $cmid => $forumid) {
                $cmid = (int) $cmid;
                if (isset($seencmids[$cmid])) {
                    $duplicates[] = $cmid;
                }
                $seencmids[$cmid] = true;
                list($ok, $reason) = migrator_validate_mapped(
                    (int) $courseid, $cmid, (int) $forumid, $forummoduleid);
                if (!$ok) {
                    migrator_precheck_fail('P07-P09', "cmid $cmid: $reason");
                }
            }
        }
    }
    if (!empty($duplicates)) {
        migrator_precheck_fail('P12', 'duplicate mapped cmids: ' . implode(',', $duplicates));
    }

    // P10, P11: unassigned negative validation + no overlap with mapped.
    // SOFT_NAME_DRIFT is accumulated from name differences on the unassigned.
    $softdrift = 0;
    foreach ($target['unassigned'] as $courseid => $list) {
        foreach ($list as $item) {
            list($cmid, $forumid, $forumtype, $name, $reason) = $item;
            $cmid = (int) $cmid;
            if (isset($seencmids[$cmid])) {
                migrator_precheck_fail('P11', "unassigned cmid $cmid overlaps mapped set");
            }
            list($ok, $failreason, $drift) = migrator_validate_unassigned(
                (int) $courseid, $cmid, (int) $forumid, $forumtype, $name, $reason, $forummoduleid);
            if (!$ok) {
                migrator_precheck_fail('P10', "unassigned cmid $cmid: $failreason");
            }
            if ($drift) {
                $softdrift++;
            }
        }
    }

    // P15: critical counts accessible (capture succeeds; compare happens at execute).
    migrator_capture_critical_counts();

    // P16: backup evidence (execute only; check reports SKIPPED).
    if ($mode === 'execute') {
        migrator_verify_backup($opts);
    } else {
        migrator_out('BACKUP_VERIFIED', 'SKIPPED');
    }

    // P17: no pre-existing open transaction.
    if (method_exists($DB, 'is_transaction_started') && $DB->is_transaction_started()) {
        migrator_precheck_fail('P17', 'a transaction is already started');
    }

    return $softdrift;
}

/**
 * Run --check: read-only dry-run (zero writes).
 *
 * @param int $softdrift SOFT_NAME_DRIFT count (name drift on unassigned).
 */
function migrator_run_check($softdrift) {
    $classification = migrator_classify();

    migrator_out('TARGET_CMPERIOD', 63);
    migrator_out('TARGET_PERIOD_ROWS', 6);
    migrator_out('UNASSIGNED_TARGETS', 12);
    migrator_out('CMPERIOD_INSERTS', $classification['cmperiod'][0]);
    migrator_out('CMPERIOD_NOOPS', $classification['cmperiod'][1]);
    migrator_out('CMPERIOD_CONFLICTS', $classification['cmperiod'][2]);
    migrator_out('PERIOD_INSERTS', $classification['period'][0]);
    migrator_out('PERIOD_NOOPS', $classification['period'][1]);
    migrator_out('PERIOD_CONFLICTS', $classification['period'][2]);
    migrator_out('UNASSIGNED_VALIDATED', 12);
    migrator_out('SOFT_NAME_DRIFT_COUNT', $softdrift);
    migrator_out('DRY_RUN_WRITES', 0);

    if ($classification['cmperiod'][2] === 0 && $classification['period'][2] === 0) {
        migrator_out('RESULT', 'PASS');
        migrator_out('EXIT_CODE', MIGRATOR_EXIT_PASS);
        exit(MIGRATOR_EXIT_PASS);
    }

    migrator_out('RESULT', 'CONFLICT');
    migrator_out('EXIT_CODE', MIGRATOR_EXIT_CONFLICT);
    exit(MIGRATOR_EXIT_CONFLICT);
}

/**
 * Insert one cmperiod row, handling a unique-collision race (D5).
 *
 * @param int $cmid   Course module id.
 * @param int $period Period (1|2).
 * @param int $now    Current timestamp.
 * @return bool True if a row was actually inserted (false if NOOP via race).
 */
function migrator_insert_cmperiod($cmid, $period, $now) {
    global $DB;

    if ($DB->record_exists('local_tpperiods_cmperiod', array('cmid' => $cmid))) {
        return false;
    }

    $record = new stdClass();
    $record->cmid = (int) $cmid;
    $record->period = (int) $period;
    $record->timecreated = $now;
    $record->timemodified = $now;

    try {
        $DB->insert_record('local_tpperiods_cmperiod', $record);
        return true;
    } catch (dml_write_exception $e) {
        if (migrator_is_duplicate_key_error($e)) {
            // Unique collision (race): re-read and classify.
            $existing = $DB->get_record('local_tpperiods_cmperiod', array('cmid' => $cmid));
            if ($existing && (int) $existing->period === (int) $period) {
                // Another writer inserted the expected row: NOOP acceptable.
                return false;
            }
            throw new migrator_conflict_exception("unique collision with different period for cmid $cmid");
        }
        // Not a duplicate-key error: do not swallow.
        throw $e;
    }
}

/**
 * Insert one period status row, handling a unique-collision race (D5).
 *
 * @param int $courseid Course id.
 * @param int $period   Period (1|2).
 * @param int $status   Status (0|1).
 * @param int $now      Current timestamp.
 * @return bool True if a row was actually inserted (false if NOOP via race).
 */
function migrator_insert_period_status($courseid, $period, $status, $now) {
    global $DB;

    if ($DB->record_exists('local_tpperiods_period',
            array('courseid' => $courseid, 'period' => $period))) {
        return false;
    }

    $record = new stdClass();
    $record->courseid = (int) $courseid;
    $record->period = (int) $period;
    $record->status = (int) $status;
    $record->timecreated = $now;
    $record->timemodified = $now;

    try {
        $DB->insert_record('local_tpperiods_period', $record);
        return true;
    } catch (dml_write_exception $e) {
        if (migrator_is_duplicate_key_error($e)) {
            // Unique collision (race): re-read and classify.
            $existing = $DB->get_record('local_tpperiods_period',
                array('courseid' => $courseid, 'period' => $period));
            if ($existing && (int) $existing->status === (int) $status) {
                // Another writer inserted the expected row: NOOP acceptable.
                return false;
            }
            throw new migrator_conflict_exception(
                "unique collision with different status for courseid $courseid period $period");
        }
        // Not a duplicate-key error: do not swallow.
        throw $e;
    }
}

/**
 * Run --execute: apply migration inside a delegated transaction.
 *
 * @param array $opts      Parsed options (backup evidence).
 * @param int   $softdrift SOFT_NAME_DRIFT count.
 */
function migrator_run_execute(array $opts, $softdrift) {
    global $DB;

    migrator_out('SOFT_NAME_DRIFT_COUNT', $softdrift);

    $before = migrator_capture_critical_counts();

    $transaction = null;
    try {
        $transaction = $DB->start_delegated_transaction();
        migrator_out('TRANSACTION_STARTED', 'YES');

        // Re-read + reclassify inside the transaction (fail closed on conflict).
        $classification = migrator_classify();
        if ($classification['cmperiod'][2] > 0 || $classification['period'][2] > 0) {
            throw new migrator_conflict_exception('conflict detected inside transaction');
        }

        $target = migrator_target_data();
        $now = time();

        // Insert only missing cmperiod rows.
        $cminserted = 0;
        foreach ($target['mappings'] as $courseid => $periods) {
            foreach ($periods as $period => $cmap) {
                foreach ($cmap as $cmid => $forumid) {
                    if (migrator_insert_cmperiod($cmid, $period, $now)) {
                        $cminserted++;
                    }
                }
            }
        }
        migrator_out('CMPERIOD_INSERTED', $cminserted);

        // Insert only missing period status rows.
        $periodinserted = 0;
        foreach ($target['status_targets'] as $st) {
            list($courseid, $period, $status) = $st;
            if (migrator_insert_period_status($courseid, $period, $status, $now)) {
                $periodinserted++;
            }
        }
        migrator_out('PERIOD_ROWS_INSERTED', $periodinserted);

        // In-transaction read-back.
        $rb = migrator_readback();
        migrator_out('IN_TRANSACTION_READBACK',
            'MAPPING_MATCH=' . $rb['mapping_match']
            . ' STATUS_MATCH=' . $rb['status_match']
            . ' UNASSIGNED_WITH_MAPPING=' . $rb['unassigned_with_mapping']);
        if ($rb['mapping_match'] !== 63 || $rb['mapping_mismatch'] !== 0
                || $rb['status_match'] !== 6 || $rb['status_mismatch'] !== 0
                || $rb['unassigned_with_mapping'] !== 0) {
            throw new migrator_verification_exception('in-transaction read-back mismatch');
        }

        // Critical invariants inside the transaction.
        $after = migrator_capture_critical_counts();
        if ($after !== $before) {
            throw new migrator_verification_exception('critical invariant changed during transaction');
        }

        $DB->commit_delegated_transaction($transaction);
        migrator_out('TRANSACTION_COMMITTED', 'YES');
    } catch (migrator_conflict_exception $e) {
        migrator_rollback_and_exit($transaction, $e, MIGRATOR_EXIT_CONFLICT);
    } catch (migrator_verification_exception $e) {
        migrator_rollback_and_exit($transaction, $e, MIGRATOR_EXIT_VERIFICATION_FAILURE);
    } catch (Exception $e) {
        migrator_rollback_and_exit($transaction, $e, MIGRATOR_EXIT_EXECUTION_FAILURE);
    } catch (Throwable $t) {
        migrator_rollback_and_exit($transaction, $t, MIGRATOR_EXIT_INTERNAL_ERROR);
    }

    // Post-commit read-back (outside the transaction; no rollback on mismatch).
    $rb2 = migrator_readback();
    migrator_out('POST_COMMIT_READBACK',
        'MAPPING_MATCH=' . $rb2['mapping_match']
        . ' STATUS_MATCH=' . $rb2['status_match']
        . ' UNASSIGNED_WITH_MAPPING=' . $rb2['unassigned_with_mapping']);
    migrator_out('MAPPING_MATCH', $rb2['mapping_match']);
    migrator_out('MAPPING_MISMATCH', $rb2['mapping_mismatch']);
    migrator_out('STATUS_MATCH', $rb2['status_match']);
    migrator_out('STATUS_MISMATCH', $rb2['status_mismatch']);
    migrator_out('UNASSIGNED_WITH_MAPPING', $rb2['unassigned_with_mapping']);

    $after2 = migrator_capture_critical_counts();
    $unchanged = ($after2 === $before) ? 'YES' : 'NO';
    migrator_out('CRITICAL_DATA_UNCHANGED', $unchanged);

    if ($rb2['mapping_match'] !== 63 || $rb2['mapping_mismatch'] !== 0
            || $rb2['status_match'] !== 6 || $rb2['status_mismatch'] !== 0
            || $rb2['unassigned_with_mapping'] !== 0 || $unchanged === 'NO') {
        migrator_out('RESULT', 'FAIL');
        migrator_out('EXIT_CODE', MIGRATOR_EXIT_VERIFICATION_FAILURE);
        exit(MIGRATOR_EXIT_VERIFICATION_FAILURE);
    }

    migrator_out('RESULT', 'PASS');
    migrator_out('EXIT_CODE', MIGRATOR_EXIT_PASS);
    exit(MIGRATOR_EXIT_PASS);
}

// ---------------------------------------------------------------------------
// Main dispatch.
// ---------------------------------------------------------------------------

$opts = migrator_parse_args($argv);

$mode = migrator_resolve_mode($opts);

if ($mode === 'help') {
    migrator_print_help();
    exit(MIGRATOR_EXIT_PASS);
}

migrator_out('MODE', strtoupper($mode));
migrator_out('ACTIVE_DB', $CFG->dbname);

$forummoduleid = migrator_forum_module_id();
if ($forummoduleid === null) {
    migrator_precheck_fail('P05', 'forum module not found in {modules}');
}

$softdrift = migrator_run_prechecks($mode, $opts, $forummoduleid);

if ($mode === 'check') {
    migrator_run_check($softdrift);
} else {
    migrator_run_execute($opts, $softdrift);
}
