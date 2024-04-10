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
 * Endpoint for manage moodle plugins.
 *
 * File         plugins.php
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
use core_plugin_manager;
use core_user;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class plugin extends endpoint implements endpoint_interface {

    private $plugin;

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {


        // Check if plugin is provided.
        $this->plugin_provided();

        // Check if plugin exist.
        $this->plugin_exist();

        switch ($this->request->method) {
            case 'POST':
                $this->post_request();
                break;

            case 'GET':
                if (in_array('config', $this->request->parameters)) {
                    $this->get_plugin_config();
                } else {
                    $this->get_plugin_info();
                }
                break;
        }

    }

    private function plugin_provided() {
        if (isset($this->request->payload->plugin)) {
            $this->plugin = $this->request->payload->plugin;
        } else {
            $this->response->send_error(STATUS_400, 'Plugin not specified.');
        }
    }

    private function plugin_exist() {

        $pluginman = core_plugin_manager::instance();
        $data = $pluginman->get_plugin_info($this->plugin);

        if (!$data) {
            $this->response->send_error(STATUS_400, 'Plugin not exist.');
        }
    }

    private function get_plugin_info() {
        global $DB;

        $pluginman = core_plugin_manager::instance();
        $data = $pluginman->get_plugin_info($this->plugin);

        $updateschecker = checker::instance();
        $lastcheck = $updateschecker->get_last_timefetched();
        $updates = $updateschecker->get_update_info($this->plugin);

        $updatelogs = $DB->get_records('upgrade_log', ['plugin' => $this->plugin], 'id DESC');

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

        if (isset($data->pluginman)) {
            unset($data->pluginman);
        }

        $this->response->add_body_key('plugininfo', convert_to_array($data));
        $this->response->add_body_key('last_check_for_updates', $lastcheck);
        $this->response->add_body_key('updates_available', $updates);
        $this->response->add_body_key('updates_log', $logs);
    }

    private function get_plugin_config($plugin = null) {

        $config = get_config($plugin);

        $this->response->add_body_key('pluginconfig', $config);
    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not implemented yet.');
    }
}
