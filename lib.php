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
use core\task\manager;
use local_moopanel\task\upgrade_noncore;
use local_moopanel\util\course_backup_manager;

/**
 * Library functions and hooks.
 *
 * File         lib.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function local_moopanel_before_footer() {
    global $CFG, $DB;

    // ToDo - Quick test some functionality. Will be removed.
    if (isset($_GET['empty_table'])) {
        $table = $_GET['empty_table'];
        $dbman = $DB->get_manager();

        $exist = $dbman->table_exists($table);

        if ($exist) {
            $DB->execute("TRUNCATE TABLE {".$table."}");
        }
    }
}

/**
 * Extend the course navigation with an "Moopanel course backups" link which redirects to a list of all available course backups.
 *
 * @param settings_navigation $navigation The settings navigation object
 * @param stdClass $course The course
 * @param stdclass $context Course context
 * @return void
 */
function local_moopanel_extend_navigation_course($navigation, $course, $context): void {
    if (has_capability('local/moopanel:coursebackupsview', $context)) {
        $url = new moodle_url('/local/moopanel/pages/course_backups.php', ['id' => $course->id]);
        $settingsnode = navigation_node::create('Moopanel - course backups', $url, navigation_node::TYPE_SETTING,
                null, 'moopanelcoursebackups', new pix_icon('i/settings', ''));
        $navigation->add_node($settingsnode);
    }
}
