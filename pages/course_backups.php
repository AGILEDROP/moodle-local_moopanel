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
 * Display available backups for selected course.
 *
 * File         course_backups.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_moopanel\util\course_backup_manager;

require_once('../../../config.php');

$id = required_param('id', PARAM_INT); // Course Id.

// Page setup.
global $PAGE, $OUTPUT;

// Access + permissions.
$course = get_course($id);
require_course_login($course, false);

$context = context_course::instance($course->id);
if (!has_capability('local/moopanel:coursebackupsview', $context)) {
    throw new \moodle_exception('nopermissions', 'error', '', 'No permission.');
}

// Page setup.
global $PAGE, $OUTPUT;
$pagetitle = 'Available course backups';
$pageurl = new moodle_url('/local/moopanel/pages/course_backups.php', ['id' => $course->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->add_body_class('limitedwidth');

// Display.
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$backupmanager = new course_backup_manager();

$backups = $backupmanager->available_backups($id);

$autobackups = $backups['auto'] ?? false;
$manualbackups = $backups['manual'] ?? false;

echo html_writer::tag('p', 'Display list of all available backups created by Moopanel application.');

echo html_writer::tag('h3', 'Automated backups');
if (!$autobackups) {
    echo html_writer::tag('p', 'No automated backups.');
} else {
    $table = new html_table();
    $table->head = ['', 'Backuo', 'Filesize'];
    $table->data = $autobackups;

    echo html_writer::table($table);
}

echo html_writer::tag('h3', 'Manual backups');
if (!$manualbackups) {
    echo html_writer::tag('p', 'No manual backups.');
} else {
    $table = new html_table();
    $table->head = ['', 'Backuo', 'Filesize'];
    $table->data = $manualbackups;

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
