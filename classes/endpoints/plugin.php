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
                $this->get_plugin();
                break;
        }

    }

    private function plugin_provided() {
        if (isset($this->request->parameters->plugin)) {
            $this->plugin = $this->request->parameters->plugin;
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

    private function get_plugin() {
        global $DB;

        $parameters = $this->request->parameters;

        $displayupdates = false;
        $displayupdateslog = false;
        $displayconfig = false;

        if (isset($parameters->displayupdates)) {
            $displayupdates = true;
        }

        if (isset($parameters->displayupdateslog)) {
            $displayupdateslog = true;
        }

        if (isset($parameters->displayconfig)) {
            $displayconfig = true;
        }

        $pluginman = core_plugin_manager::instance();
        $data = $pluginman->get_plugin_info($this->plugin);

        if (isset($data->pluginman)) {
            unset($data->pluginman);
        }

        $this->response->add_body_key('plugininfo', convert_to_array($data));

        // Available updates.
        if ($displayupdates) {
            $updateschecker = checker::instance();

            $lastcheck = $updateschecker->get_last_timefetched();
            $updates = $this->get_plugin_updates($this->plugin);

            $this->response->add_body_key('last_check_for_updates', $lastcheck);
            $this->response->add_body_key('update_available', $updates);
        }

        // Updates log.
        if ($displayupdateslog) {
            $logs = $this->get_plugin_updates_log($this->plugin);
            $this->response->add_body_key('update_log', $logs);
        }

        // Current plugin config.
        if ($displayconfig) {
            $config = $this->get_plugin_config($this->plugin);
            $this->response->add_body_key('pluginconfig', $config);
        }
    }

    private function get_plugin_updates($plugin) {
        $updates = [];

        $updateschecker = checker::instance();
        $pluginupdates = $updateschecker->get_update_info($plugin);
        if (!empty($pluginupdates)) {
            foreach ($pluginupdates as $pluginupdate) {
                $pluginupdate->type = 'plugin';
                $updates[] = $pluginupdate;
            }
        }

        return $updates;
    }

    private function get_plugin_updates_log($plugin) {
        global $DB;
        $logs = [];

        $updatelogs = $DB->get_records('upgrade_log', ['plugin' => $plugin], 'id DESC');

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

        return $logs;
    }

    private function get_plugin_config($plugin) {

        $config = get_config($plugin);

        if ($config) {
            return $config;
        }

        return [];
    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not implemented yet.');
    }
}
