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

use core\task\manager;
use core\update\checker;
use core_user;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use local_moopanel\task\core_update;

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

        if (!empty($updates)) {
            foreach ($updates as $update) {
                $current = $CFG->release;
                $new = $update->release;
                $update->type = $this->resolve_update_type($current, $new);
            }
        }

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

        $this->response->add_body_key('current_version', $CFG->version);
        $this->response->add_body_key('current_release', $CFG->release);
        $this->response->add_body_key('last_check_for_updates', $lastcheck);
        $this->response->add_body_key('update_available', $updates);
        $this->response->add_body_key('update_log', $logs);
    }

    private function resolve_update_type($currentversion, $newversion) {
        if (!$newversion) {
            return null;
        }

        $old = explode('.', $currentversion);
        $new = explode('.', $newversion);

        $mega1 = (int)$old[0] ?? false;
        $mega2 = (int)$new[0] ?? false;

        if (!$mega2) {
            return null;
        }

        if ($mega1 != $mega2) {
                return 'core-mega';
        }

        $major1 = (int)$old[1] ?? false;
        $major2 = (int)$new[1] ?? false;

        if ($major1 != $major2) {
            return 'core-major';
        }

        return 'core-minor';
    }

    private function post_request() {

        $userid = $this->request->payload->user_id ?? false;

        $instanceid = $this->request->payload->instance_id ?? false;

        if (!is_numeric($instanceid)) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide a valid instance ID.');
        }

        $updateid = $this->request->payload->update_id ?? false;

        if (!$updateid) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide update ID.');
        }

        $version = $this->request->payload->version ?? false;

        if (!$version) {
            $this->response->send_error(STATUS_400, 'Bad request - no core version specified.');
        }

        $downloadlink = $this->request->payload->download ?? false;

        if (!$downloadlink) {
            $this->response->send_error(STATUS_400, 'Bad request - no download link specified.');
        }

        $updatechecker = checker::instance();
        $availableupdates = $updatechecker->get_update_info('core');

        if (!$availableupdates) {
            $this->response->send_error(STATUS_400, 'Bad request - Moodle has no available updates.');
        }

        $find = false;
        foreach ($availableupdates as $update) {
            if ($update->version == $version) {
                $find = true;
                $download = $update->download;
                break;
            };
        }

        if (!$find) {
            $this->response->send_error(STATUS_400, 'Bad request - Moodle version not found in available updates.');
        }

        // Define Adhoc task for update moodle core.
        $task = new core_update();

        // URL: api/updates/core/instance/{instance_id}

        $moopanelurl = get_config('local_moopanel', 'moopanelurl');
        $responseurl = $moopanelurl . '/api/updates/core/instance/' . $instanceid;

        $customdata = [
            'returnurl' => $responseurl,
            'userid' => $userid,
            'instanceid' => $instanceid,
            'version' => $version,
            'updateid' => $updateid,
            'download' => $download,
        ];

        $task->set_custom_data((object) $customdata);

        // Set run task ASAP.
        $task->set_next_run_time(time() - 1);
        manager::queue_adhoc_task($task, true);

        $taskwillrun = manager::get_adhoc_tasks('\local_moopanel\task\core_update');

        if ($taskwillrun) {
            $task = reset($taskwillrun);
            $id = $task->get_id();
            $this->response->add_body_key('status', true);
            $this->response->add_body_key('moodle_job_id', $id);
            $this->response->add_body_key('message', 'Core update in progress.');
        } else {
            $this->response->send_error(STATUS_503, 'Service Unavailable - try again later.');
        }
    }

}
