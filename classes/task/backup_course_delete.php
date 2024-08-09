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
 * Adhoc task class - delete specified course backups.
 *
 * File         backup_course_delete.php
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

class backup_course_delete extends adhoc_task {

    public function get_name() {
        return get_string('task:backupcoursedelete', 'local_moopanel');
    }

    public function execute() {
        global $CFG;

        $response = new response();
        $response->add_header('X-API-KEY', get_config('local_moopanel', 'apikey'));

        $customdata = $this->get_custom_data();

        $userid = $customdata->userid ?? null;

        $response->add_body_key('status', false);
        $response->add_body_key('user_id', $userid);

        $storage = $customdata->storage;

        $backup = $customdata->backup;
        $backupid = $backup->backup_result_id ?? false;

        $response->add_body_key('backup_result_id', $backupid);

        $storagetype = $storage->storage_key;

        if ($storagetype == 'local') {
            $file = $CFG->dataroot . '/moopanel_course_backups/' . $backup->link;

            if (file_exists($file)) {
                unlink($file);

                $response->add_body_key('status', true);
                $response->add_body_key('message', "Backup deleted successfully.");
            } else {
                $response->add_body_key('message', "Backup file does not exist.");
            }
        } else {
            $response->add_body_key('message', 'Currently support only local storage.');
        }

        $response->send_to_email('test', 'Backup delete');
        $response->post_to_url($customdata->returnurl);
    }
}