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
 * Endpoint for create course backups.
 *
 * File         backups.php
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
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use local_moopanel\task\backup_course;
use local_moopanel\task\backup_course_restore;

class backups extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET', 'POST', 'PUT', 'DELETE'];
    }

    public function execute_request() {

        switch ($this->request->method) {
            case 'POST':
                $this->create_backups();
                break;

            case 'DELETE':
                $this->delete_backups();
                break;

            case 'PUT':
                $this->restore_backup();
                break;

            case 'GET':
                $this->display_backups_in_progress();
            break;
        }
    }

    private function display_backups_in_progress() {

        $tasks = manager::get_adhoc_tasks('\local_moopanel\task\backup_course');

        if (!$tasks) {
            $this->response->add_body_key('backups_in_progress', null);
            return;
        }

        $data = [];

        foreach ($tasks as $task) {
            $customdata = $task->get_custom_data();
            $courseid = $customdata->courseid;

            $data[] = [
                    "id" => $courseid,
                    "message" => "in progress",
            ];
        }

        $this->response->add_body_key('backups_in_progress', $data);
    }

    private function create_backups() {
        global $DB;

        $instanceid = $this->request->payload->instance_id ?? false;

        if (!is_numeric($instanceid)) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide a valid instance ID.');
        }

        $mode = $this->request->payload->mode ?? "auto";
        $storage = $this->request->payload->storage ?? "local";
        $credentials = $this->request->payload->credentials ?? [];

        $courses = $this->request->payload->courses ?? false;

        if (!$courses) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide Moodle course ids.');
        }

        $data = [];
        $errors = [];

        foreach ($courses as $course) {
            // Check if course exist in Moodle.
            $exist = $DB->record_exists('course', ['id' => $course]);

            if (!$exist) {
                $errors[] = [
                        "id" => $course,
                        "message" => "Course not exist.",
                ];
                continue;
            }

            // Define Adhoc task for create course backup.
            $task = new backup_course();

            $moopanelurl = get_config('local_moopanel', 'moopanelurl');
            $responseurl = $moopanelurl . '/api/backups/courses/' . $instanceid;

            $coustomdata = [
                'returnurl' => $responseurl,
                'mode' => $mode,
                'storage' => $storage,
                'credentials' => $credentials,
                'courseid' => $course,
            ];

            $task->set_custom_data((object) $coustomdata);

            // Set run task ASAP.
            $task->set_next_run_time(time() - 1);
            manager::queue_adhoc_task($task, true);

            $data[] = [
                    "id" => $course,
                    "message" => "Backup will be created.",
            ];
        }

        if (!empty($data)) {
            $this->response->add_body_key('backups', $data);
        }

        if (!empty($errors)) {
            $this->response->add_body_key('errors', $errors);
        }
    }

    private function delete_backups() {
        global $CFG;

        $backups = $this->request->payload->backups ?? [];

        $data = [];

        foreach ($backups as $backup) {
            $file = $CFG->dataroot . $backup->link;

            if (file_exists($file)) {
                unlink($file);
                $data[] = [
                    "backup_result_id" => $backup->backup_result_id,
                    "status" => true,
                ];
            } else {
                $data[] = [
                    "backup_result_id" => $backup->backup_result_id,
                    "status" => false,
                    "message" => "Backup file does not exist.",
                ];
            }
        }
        $this->response->add_body_key('backups', $data);
    }

    private function restore_backup() {
        global $CFG, $DB;

        $courseid = $this->request->payload->moodle_course_id ?? false;

        if (!$courseid) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide valid Moodle course id.');
        }

        $courseexist = $DB->record_exists('course', ['id' => $courseid]);
        if (!$courseexist) {
            $this->response->send_error(STATUS_400, 'Course not exist in Moodle.');
        }

        $backuplink = $this->request->payload->link ?? false;

        if (!$backuplink) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide valid backup link.');
        }

        // Define Adhoc task for restore course backup.
        $task = new backup_course_restore();

        $instanceid = $this->request->payload->instance_id ?? false;

        if (!$instanceid) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide valid instance id.');
        }

        $moopanelurl = get_config('local_moopanel', 'moopanelurl');
        $responseurl = $moopanelurl . '/api/backups/restore/instance/' . $instanceid;

        $storage = $this->request->payload->storage ?? "local";
        $backupmode = $this->request->payload->backup_mode ?? "auto";
        $credentials = $this->request->payload->credentials ?? [];
        $password = $this->request->payload->password ?? "";
        $userid = $this->request->payload->user_id ?? false;
        $backupid = $this->request->payload->backup_result_id ?? false;

        $customdata = [
            'returnurl' => $responseurl,
            'storage' => $storage,
            'link' => $backuplink,
            'password' => $password,
            'credentials' => $credentials,
            'courseid' => $courseid,
            'backupid' => $backupid,
            'userid' => $userid,
        ];

        $task->set_custom_data((object) $customdata);

        // Set run task ASAP.
        $task->set_next_run_time(time() - 1);
        manager::queue_adhoc_task($task, true);

        $taskwillrun = \core\task\manager::get_adhoc_tasks('\local_moopanel\task\backup_course_restore');

        if (!$taskwillrun) {
            $this->response->send_error(STATUS_503, 'Service Unavailable - try again later.');
        }

        $this->response->add_body_key('status', true);
        $this->response->add_body_key('message', "Course backup will be restored.");
    }
}
