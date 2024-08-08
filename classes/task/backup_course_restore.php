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
 * Adhoc task class - restore specified course backup.
 *
 * File         backup_course_restore.php
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

class backup_course_restore extends adhoc_task {

    public function get_name() {
        return get_string('task:backupcourserestore', 'local_moopanel');
    }

    public function execute() {
        global $CFG, $DB;

        $response = new response();
        $response->add_header('X-API-KEY', get_config('local_moopanel', 'apikey'));
        $response->add_body_key('status', false);

        $backupmanager = new course_backup_manager();

        $customdata = $this->get_custom_data();
        $returnurl = $customdata->returnurl;
        $storage = $customdata->storage;
        $link = $customdata->link;
        $password = $customdata->password;
        $credentials = $customdata->credentials;
        $courseid = $customdata->courseid;
        $backupid = $customdata->backupid;
        $userid = $customdata->userid;

        $response->add_body_key('user_id', $userid);
        $response->add_body_key('backup_result_id', $backupid);
        $response->add_body_key('courseid', $courseid);

        if ($storage == 'local') {
            if (!file_exists($CFG->dataroot . '/moopanel_course_backups/' . $link)) {
                $response->add_body_key('message', 'Backup file not found.');
                $response->post_to_url($returnurl);
                return false;
            }
            $backupfile = $backupmanager->unzip_local_file($link, $password);
        } else {
            $backupfile = false;
        }

        if (!file_exists($backupfile)) {
            $response->add_body_key('message', 'Password incorrect.');
            $response->post_to_url($returnurl);
            return false;
        }

        $restored = $backupmanager->restore_backup($backupfile, $courseid, $password);
        if ($restored) {
            $response->add_body_key('status', true);
            $response->add_body_key('message', 'Backup restored.');
        } else {
            $response->add_body_key('message', 'Backup not restored.');
        }

        $response->post_to_url($returnurl);
    }
}
