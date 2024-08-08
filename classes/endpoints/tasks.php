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
 * Endpoint for check moodle tasks.
 *
 * File         tasks.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\endpoints;

use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use local_moopanel\logger;

class tasks extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET'];
    }

    public function execute_request() {

        $path = $this->request->path;

        if ($path == 'tasks/check') {
            $this->check_adhoc_task_status();
        } else {
            $this->response->send_error(STATUS_405, 'Not allowed.');
        }
    }

    private function check_adhoc_task_status() {
        global $DB;

        $logger = new logger();

        $taskid = $this->request->parameters->id ?? false;
        if (!$taskid) {
            $this->response->send_error(STATUS_400, 'No task id specified.');
        }

        $tasktype = $this->request->parameters->type ?? false;
        if (!$tasktype) {
            $this->response->send_error(STATUS_400, 'No task type specified.');
        }

        // Check if adhoc task exist.
        $taskexist = $DB->record_exists('task_adhoc', ['id' => $taskid]);

        if ($taskexist) {
            // Task is in progress or has failed.
            $task = $DB->get_record('task_adhoc', ['id' => $taskid]);

            if ($task->faildelay) {
                // Task was failed.
                $report = $this->get_task_log($taskid, $tasktype);
                $message = $report['message'] ?? 'Task was failed.';
                $this->response->add_body_key('status', 3);
                $this->response->add_body_key('error', 'Task was failed.');

            } else {
                // Task is running or waiting to run.
                $this->response->add_body_key('status', 2);
                $this->response->add_body_key('error', '');
            }
        } else {
            // No adhoc Task.
            $report = $this->get_task_log($taskid, $tasktype);
            $taskstatus = $report['result'] ?? false;
            if ($taskstatus) {
                $status = 1; // Task finished sucessfully and log exist.
                $message  = $report['message'] ?? 'Task finished with no log.';
            } else {
                $status = 4; // No log data.
                $message = 'There is no log data for selected task.';
            }

            $this->response->add_body_key('status', $status);
            $this->response->add_body_key('error', $message);
        }

        $logger->log('response', 'tasks/check', $this->request->get_method(), json_encode($this->request->parameters), $this->response->get_status(), $this->response->encode_body());
    }

    private function get_task_log($taskid, $tasktype) {
        global $DB;

        $taskclass = 'local_moopanel\\task\\' . $tasktype;
        $conditions = [
            'component' => 'local_moopanel',
            'classname' => $taskclass,
            'type' => 1,
        ];

        $logs = $DB->get_records('task_log', $conditions, 'ID DESC');

        if (!$logs) {
            return false;
        }

        foreach ($logs as $log) {
            $taskreport = $log->output;

            $search = "task id: " . $taskid . "\n";

            $contain = str_contains($taskreport, $search);

            if ($contain) {
                return [
                        'result' => (bool)$log->result,
                        'message' => $taskreport,
                ];
            }
        }

        return false;
    }
}
