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
 * Adhoc task class - update specified plugins.
 *
 * File         plugins_update.php
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
use core_plugin_manager;
use local_moopanel\response;
use local_moopanel\util\plugin_manager;

class plugins_update extends adhoc_task {

    public function get_name() {
        return get_string('task:pluginsupdate', 'local_moopanel');
    }


    public function execute() {
        $id = $this->get_id();

        $response = new response();
        $response->add_header('X-API-KEY', get_config('local_moopanel', 'apikey'));

        $pluginmanager = new plugin_manager();
        $pluginman = core_plugin_manager::instance();

        $customdata = $this->get_custom_data();

        $response->add_body_key('moodle_job_id', (int)$id);
        $response->add_body_key('user_id', $customdata->userid);
        $response->add_body_key('username', $customdata->username);

        $url = $customdata->responseurl;
        $updates = $customdata->updates;

        $data = [];
        $pluginsupdated = 0;

        foreach ($updates as $update) {

            $updateprocess = [
                'model_id' => $update->model_id,
                'status' => false,
                'component' => $update->component,
                'error' => null,
            ];

            $plugin = $pluginman->get_plugin_info($update->component);

            if (!$plugin) {
                $updateprocess['error'] = 'Plugin not exist in Moodle';
                $data[] = $updateprocess;
                continue;
            }

            // Get available updates for current plugin.
            $availableupdates = $plugin->available_updates();

            if (!$availableupdates) {
                $versioncurrent = (int)$plugin->versiondisk;
                $versionrequest = (int)$update->version;

                if ($versionrequest == $versioncurrent) {
                    $updateprocess['status'] = true;
                    $updateprocess['error'] = 'Update already installed';
                } else if ($versionrequest < $versioncurrent) {
                    $updateprocess['status'] = true;
                    $updateprocess['error'] = 'Newer version of plugin already installed';
                } else {
                    $updateprocess['status'] = false;
                    $updateprocess['error'] = 'Update not found';
                }

                $data[] = $updateprocess;
                continue;
            }

            $updatetoinstall = null;
            // Check which update to install.
            foreach ($availableupdates as $availableupdate) {
                if ($availableupdate->version == $update->version) {
                    if ($availableupdate->download == $update->download) {
                        $updatetoinstall = $availableupdate;
                    }
                }
            }

            if (!$updatetoinstall) {
                $updateprocess['status'] = false;
                $updateprocess['error'] = 'Update not exist';
                $data[] = $updateprocess;
                continue;
            }

            $report = $pluginmanager->install_zip($updatetoinstall->download);
            if ($report['status']) {
                $pluginsupdated++;
                $updateprocess['status'] = true;
            } else {
                $updateprocess['error'] = $report['error'];
            }

            $data[] = $updateprocess;
        }

        $response->add_body_key('updates', $data);

        // ToDo Send response to Moo-panel app.
        // $send = $response->post_to_url($url).

        $response->send_to_email('test@test.com', 'Plugin updates', $response->body);
    }
}
