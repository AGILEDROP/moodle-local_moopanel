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
 * Adhoc task class - create backups for specified course.
 *
 * File         backup_course.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\task;

use core\task\adhoc_task;
use local_moopanel\response;
use local_moopanel\util\course_backup_manager;

class backup_course extends adhoc_task {

    public function get_name() {
        return get_string('task:backupcourse', 'local_moopanel');
    }

    public function execute() {
        global $CFG, $DB;

        $response = new response();
        $response->add_header('X-API-KEY', get_config('local_moopanel', 'apikey'));

        $backupmanager = new course_backup_manager();

        $customdata = $this->get_custom_data();

        $returnurl = $customdata->returnurl;
        $mode = $customdata->mode;
        $storage = $customdata->storage;
        $courseid = $customdata->courseid;

        $backup = $backupmanager->create_backup($courseid, $mode);

        $created = $backup['status'];

        if (!$created) {
            $msg = $backup['message'];
            mtrace($msg);
            $response->add_body_key('status', false);
            $response->add_body_key('message', $msg);
            // Send response to Moo-panel app.
            $send = $response->post_to_url($returnurl);
            return false;
        }

        if ($storage == 'local') {
            $url = $mode . '/' . $backup['filename'];
        } else {
            // ToDo - copy backup file to external storage and delete it from local storage.
            $url = $storage . '/' . $mode . '/' . $backup['filename'];
        }

        $response->add_body_key('status', true);
        $response->add_body_key('courseid', $courseid);
        $response->add_body_key('link', $url);
        $response->add_body_key('password', $backup['password']);
        $response->add_body_key('filesize', $backup['filesize']);

        // Send response to Moo-panel app.
        $send = $response->post_to_url($returnurl);

        mtrace('Execute backup plan      = ' . $backup['diff1']);
        mtrace('Copy mbz backup file     = ' . $backup['diff2']);
        mtrace('Delete org mbz file      = ' . $backup['diff3']);
        mtrace('Create zip file from mbz = ' . $backup['diff4']);
        mtrace('Copy zip and delete tmp  = ' . $backup['diff5']);
        mtrace('Zip file size = ' . $this->file_size_convert($backup['filesize']));
    }

    private function file_size_convert($bytes) {
        $bytes = floatval($bytes);
        $arbytes = [
            0 => [
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4),
                ],
            1 => [
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3),
                ],
            2 => [
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2),
                ],
            3 => [
                "UNIT" => "KB",
                "VALUE" => 1024,
                ],
            4 => [
                "UNIT" => "B",
                "VALUE" => 1,
                ],
            ];

        foreach ($arbytes as $aritem) {
            if ($bytes >= $aritem["VALUE"]) {
                $result = $bytes / $aritem["VALUE"];
                $result = str_replace(".", ",", strval(round($result, 2)));
                $result .= " " . $aritem["UNIT"];
                break;
            }
        }
        return $result;
    }
}
