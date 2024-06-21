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

use core\task\course_backup_task;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use local_moopanel\task\backup_course;

class backups extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {

        switch ($this->request->method) {
            case 'POST':
                $this->create_backups();
                break;

            case 'GET':
                $this->backups_in_progress();
            break;
        }
    }

    private function backups_in_progress(){

        $tasks = \core\task\manager::get_adhoc_tasks('\local_moopanel\task\backup_course');

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

        $b = 8;

        $instanceid = $this->request->payload->instance_id ?? false;

        if (!is_numeric($instanceid)) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide a valid instance ID.');
        }

        $storage = $this->request->payload->storage ?? "local";

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
            // ToDo check for this implementation -> $task = new course_backup_task();
            $task = new backup_course();

            $moopanelurl = get_config('local_moopanel', 'moopanelurl');
            $responseurl = $moopanelurl . '/api/backups/courses/instance/' . $instanceid;

            $coustomdata = [
                'returnurl' => $responseurl,
                'instanceid' => $instanceid,
                'type' => $storage,
                'courseid' => $course,
            ];

            $task->set_custom_data((object) $coustomdata);

            // Set run task ASAP.
            $task->set_next_run_time(time() - 1);
            \core\task\manager::queue_adhoc_task($task, true);

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
}