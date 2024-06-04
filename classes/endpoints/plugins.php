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
use local_moopanel\background_process;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use local_moopanel\util\plugin_manager;
use moodle_url;

class plugins extends endpoint implements endpoint_interface {

    private $countupdated;

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {
        global $CFG;

        $backgroundtask = new background_process();
        $url = new moodle_url($CFG->wwwroot . '/local/moopanel/pages/fetch_plugin_updates.php', []);

        $backgroundtask->run($url);

        $path = $this->request->path;

        switch ($this->request->method) {
            case 'POST':

                $this->reset_updated();;

                switch ($path) {
                    case 'plugins/updates':
                        $this->post_update();
                        break;
                    case 'plugins/installzip':
                        $this->post_zip_install();
                        break;
                    default:
                        $this->response->send_error(STATUS_400, 'Bad request - undefined method.');
                        break;
                }

                if ($this->countupdated) {
                    $mustgotourl = new moodle_url($CFG->wwwroot.'/local/moopanel/pages/upgrade_noncore.php', []);
                    $backgroundtask->run($mustgotourl);
                }
                break;

            case 'GET':
                $this->get_plugins();
                break;
        }
    }

    private function get_plugins() {
        global $DB;

        $parameters = $this->request->parameters;
        $displayupdates = false;
        $displayupdateslog = false;

        if (isset($parameters->displayupdates)) {
            $displayupdates = true;
        }
        if (isset($parameters->displayupdateslog)) {
            $displayupdateslog = true;
        }

        $pluginman = core_plugin_manager::instance();
        $updateschecker = checker::instance();
        $availableupdates = $pluginman->available_updates();

        $lastcheck = $updateschecker->get_last_timefetched();

        $this->response->add_body_key('last_check_for_updates', $lastcheck);

        $data = [];
        $plugintypes = $pluginman->get_plugins();


        foreach ($plugintypes as $key => $plugintype) {

            $plugins = $plugintype;

            foreach ($plugins as $plugin) {

                $isstandard = $plugin->is_standard();
                $hasupdates = array_key_exists($plugin->component, $availableupdates);
                $plugininfo = [
                        'plugin' => $plugin->name,
                        'plugintype' => $key,
                        'display_name' => $plugin->displayname,
                        'component' => $plugin->component,
                        'version' => $plugin->versiondb,
                        'enabled' => (bool)$plugin->is_enabled(),
                        'is_standard' => $isstandard,
                        'has_updates' => $hasupdates,
                        'settings_section' => $plugin->get_settings_section_name(),
                        'directory' => $plugin->get_dir(),
                ];

                // Available updates.
                if ($displayupdates) {
                    $plugininfo['update_available'] = $this->get_plugin_updates($plugin);
                }

                // Updates history.
                if ($displayupdateslog) {
                    $plugininfo['update_log'] = $this->get_plugin_updates_log($plugin);
                }

                $data['plugins'][] = $plugininfo;
            }
        }
        $this->response->add_body_key('plugins', $data['plugins']);
    }

    private function post_update() {
        global $CFG;

        if (!isset($this->request->payload->updates)) {
            $this->response->send_error(STATUS_400, 'Bad request - no updates specified.');
        }

        $updates = $this->request->payload->updates;
        if (empty($updates)) {
            $this->response->send_error(STATUS_400, 'Bad request - empty updates.');
        }

        $pluginmanager = new plugin_manager();
        $pluginman = core_plugin_manager::instance();

        $data = [];
        foreach ($updates as $update) {

            $plugin = $pluginman->get_plugin_info($update->component);

            if (!$plugin) {
                $data[] = [
                        $update->model_id => [
                            'status' => false,
                            'component' => $update->component,
                            'error' => 'Plugin not exist in Moodle.',
                        ],
                ];
                continue;
            }

            $availableupdates = $plugin->available_updates();

            $this->response->add_body_key('updates', $data);

            if (!$availableupdates) {
                $versioncurrent = (int)$plugin->versiondisk;
                $versionrequest = (int)$update->version;

                if ($versionrequest == $versioncurrent) {
                    $msg = 'Update already installed.';
                } else {
                    $msg = 'Update not found.';
                }
                $data[] = [
                    $update->model_id => [
                        'status' => false,
                        'component' => $update->component,
                        'error' => $msg,
                    ],
                ];
                continue;
            }

            $updatetoinstall = null;
            foreach ($availableupdates as $availableupdate) {
                if ($availableupdate->version == $update->version) {
                    if ($availableupdate->download == $update->download) {
                        $updatetoinstall = $availableupdate;
                    }
                }
            }

            if (!$updatetoinstall) {
                $data[] = [
                        $update->model_id => [
                                'status' => false,
                                'component' => $update->component,
                                'error' => 'Update not exist.',
                        ],
                ];
                continue;
            }

            $report = $pluginmanager->install_zip($updatetoinstall->download);
            if ($report['status']) {
                $this->increase_updated();
            }

            $data[] = [
                    $update->model_id => $report,
                ];
        }

        $this->response->add_body_key('updates', $data);
    }

    private function post_zip_install() {
        global $CFG;

        $pluginmanager = new plugin_manager();

        if (!isset($this->request->payload->updates)) {
            $this->response->send_error(STATUS_400, 'Bad request - no updates specified.');
        }

        $updates = $this->request->payload->updates;
        if (empty($updates)) {
            $this->response->send_error(STATUS_400, 'Bad request - no zip files urls specified.');
        }

        $reports = [];
        foreach ($updates as $update) {
            $key = $update;

            $report = $pluginmanager->install_zip($update);
            $reports[$key] = $report;

            if ($report['status']) {
                $this->increase_updated();
            }

        }

        $this->response->add_body_key("updates", $updates);
    }

    private function reset_updated() {
        $this->countupdated = 0;
    }

    private function increase_updated() {
        $this->countupdated++;
    }

    private function get_plugin_updates($plugin) {
        $updates = [];

        $updateschecker = checker::instance();

        $pluginupdates = $updateschecker->get_update_info($plugin->component);
        if ($pluginupdates) {
            $data = [];
            foreach ($pluginupdates as $pluginupdate) {
                $pluginupdate->type = 'plugin';
                $updates[] = $pluginupdate;
            }
        }
        return  $updates;
    }

    private function get_plugin_updates_log($plugin) {
        global $DB;

        $updatelogs = $DB->get_records('upgrade_log', ['plugin' => $plugin->component], 'id DESC');

        $logs = [];

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


        return $logs;
    }
}