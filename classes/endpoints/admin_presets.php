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
 * Endpoint for manage moodle admin presets.
 *
 * File         admin_presets.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\endpoints;

use core\cron;
use core\task\manager;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use local_moopanel\task\admin_presets_create;
use local_moopanel\util\admin_presets_manager;

class admin_presets extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET'];
    }

    public function execute_request() {
        $this->get_admin_preset();
    }

    private function get_admin_preset() {
        $instanceid = $this->request->parameters->instanceid ?? false;

        if (!is_numeric($instanceid)) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide a valid instance ID.');
        }

        $adminpresetsmanager = new admin_presets_manager();

        $pluginexist = $adminpresetsmanager->plugin_exist();
        if (!$pluginexist) {
            $this->response->send_error(STATUS_501, 'Not implemented - Admin presets plugin not found.');
        }

        // If presets already exist, we must delete it.
        $presets = $adminpresetsmanager->presets_get('Moopanel');
        if ($presets) {
            foreach ($presets as $preset) {
                $adminpresetsmanager->presets_delete($preset->id);
            }
        }

        $moopanelurl = get_config('local_moopanel', 'moopanelurl');
        $responseurl = $moopanelurl . '/api/instances/' . $instanceid . '/admin_preset';

        // Define adhoc task for create new admin presets.
        $task = new admin_presets_create();

        // Attach custom data to task, to know where to send response after task is completed.
        $customdata = [
                'responseurl' => $responseurl,
        ];
        $task->set_custom_data((object)$customdata);

        // Set run task ASAP.
        $task->set_next_run_time(time() - 1);
        manager::queue_adhoc_task($task, true);

        $taskwillrun = manager::get_adhoc_tasks('\local_moopanel\task\admin_presets_create');

        if ($taskwillrun) {
            $this->response->add_body_key('status', true);
            $this->response->add_body_key('message', 'Admin presets creation in progress');
        } else {
            $this->response->send_error(STATUS_503, 'Service Unavailable - try again later.');
        }
    }
}
