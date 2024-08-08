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
 * Event observers used in local_moopanel.
 *
 * File         observer.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel;

use core\event\base;
use core\task\adhoc_task;
use stdClass;

/**
 * Event observer for local_moopanel.
 */
class observer {

    public static function update_course_modified_time(base $event) {
        global $DB;

        $courseid = $event->courseid ?? false;

        if (!$courseid) {
            return false;
        }

        $coursedata = new stdClass();
        $now = new \DateTime();

        $coursedata->course_id = $courseid;
        $coursedata->last_modified = $now->getTimestamp();

        $existing = $DB->get_record('moopanel_course_backups', ['course_id' => $courseid]);
        if ($existing) {
            $coursedata->id = $existing->id;
            $DB->update_record('moopanel_course_backups', $coursedata);
        } else {
            $coursedata->last_backup = 0;
            $DB->insert_record('moopanel_course_backups', $coursedata);
        }
    }
}
