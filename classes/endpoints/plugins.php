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

class plugins extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {
        switch ($this->request->method) {
            case 'POST':
                $this->post_request();
                break;

            case 'GET':
                $this->get_plugins();
                break;
        }
    }


    private function get_plugins() {
        global $DB;

        $displayupdates = in_array('updates', $this->request->parameters);

        $pluginman = core_plugin_manager::instance();
        $data = [];
        $plugintypes = $pluginman->get_plugins();

        foreach ($plugintypes as $key => $plugintype) {

            $plugins = $plugintype;

            foreach ($plugins as $plugin) {

                $isstandard = $plugin->is_standard();
                $hasupdates = (bool)$plugin->available_updates();
                $plugininfo = [
                        'plugin' => $plugin->name,
                        'plugintype' => $key,
                        'display_name' => $plugin->displayname,
                        'component' => $plugin->component,
                        'version' => $plugin->versiondb,
                        'enabled' => (bool)$plugin->is_enabled(),
                        'is_standard' => $isstandard,
                        'has_updates' => ($hasupdates) ? $hasupdates : null,
                        'settings_section' => $plugin->get_settings_section_name(),
                        'directory' => $plugin->get_dir(),
                ];

                if ($displayupdates) {
                    $availableupdates = null;
                    $updateschecker = checker::instance();
                    $pluginupdates = $updateschecker->get_update_info($plugin->component);
                    if ($pluginupdates) {
                        $updates = [];
                        foreach ($pluginupdates as $pluginupdate) {
                            $updates[] = $pluginupdate;
                        }
                        $availableupdates = $updates;
                    }
                    $plugininfo['available_updates'] = $availableupdates;

                    // Updates history.
                    $updatelogs = $DB->get_records('upgrade_log', ['plugin' => $plugin->component], 'id DESC');

                    $logs = null;

                    foreach ($updatelogs as $updatelog) {

                        $info = $updatelog->info;
                        $filter1 = strpos($info, 'Starting');

                        if (is_numeric($filter1)) {
                            continue;
                        }

                        $filter2 = strpos($info, 'savepoint reached');
                        if (is_numeric($filter2)) {
                            continue;
                        }

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
                    $plugininfo['updates_log'] = $logs;
                }

                $data['plugins'][] = $plugininfo;
            }
        }
        $this->response->add_body_key('plugins', $data['plugins']);
    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not Implemented yet.');
    }
}
