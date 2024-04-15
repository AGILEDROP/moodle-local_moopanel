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
 * Endpoint for manage moodle core versions.
 *
 * File         moodle_core.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\endpoints;

use core\update\checker;
use core_user;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class moodle_core extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {

        switch ($this->request->method) {
            case 'POST':
                $this->post_request();
                break;

            case 'GET':
                $this->get_moodle_core_info();
                break;
        }
    }


    private function get_moodle_core_info() {
        global $CFG, $DB;

        $updateschecker = checker::instance();
        $lastcheck = $updateschecker->get_last_timefetched();
        $updates = $updateschecker->get_update_info('core');

        $conditions = [
                'plugin' => 'core',
        ];
        $updatelogs = $DB->get_records('upgrade_log', $conditions, 'id DESC');
        $logs = [];

        foreach ($updatelogs as $updatelog) {

            $username = null;
            $email = null;
            $updatelog->userid = (int)$updatelog->userid;

            if ($updatelog->userid > 0) {
                $user = core_user::get_user($updatelog->userid);
                if ($user) {
                    $username = $user->username;
                    $email = $user->email;
                }
            } else {
                $updatelog->userid = null;
            }
            $updatelog->id = (int)$updatelog->id;
            $updatelog->type = (int)$updatelog->type;
            $updatelog->timemodified = (int)$updatelog->timemodified;
            $updatelog->username = $username;
            $updatelog->email = $email;
            $logs[] = (array) $updatelog;
        }

        $this->response->add_body_key('current_version', $CFG->release);
        $this->response->add_body_key('last_check_for_updates', $lastcheck);
        $this->response->add_body_key('update_available', $updates);
        $this->response->add_body_key('update_log', $logs);
    }


    private function post_request() {
        $this->response->send_error(STATUS_501, 'Method not implemented yet.');
    }
}
