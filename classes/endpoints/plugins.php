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
use Exception;
use local_moopanel\background_process;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use local_moopanel\task\plugins_install_zip;
use local_moopanel\task\plugins_update;
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

                $this->countupdated = 0;

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
                if ($path == 'plugins/updateprogress') {
                    $this->updates_in_progress();
                } else {
                    $this->get_plugins();
                }
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

        $instanceid = $this->request->payload->instance_id ?? false;

        if (!is_numeric($instanceid)) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide a valid instance ID.');
        }

        $updates = $this->request->payload->updates ?? false;

        if (!$updates) {
            $this->response->send_error(STATUS_400, 'Bad request - no updates specified.');
        }

        if (empty($updates)) {
            $this->response->send_error(STATUS_400, 'Bad request - no updates specified.');
        }

        // Define Adhoc task for update plugins.
        $task = new plugins_update();

        // Attach custom data to task, to know where to send response after task is completed.
        $userid = $this->request->payload->user_id ?? false;
        $username = $this->request->payload->username ?? false;
        $moopanelurl = get_config('local_moopanel', 'moopanelurl');
        $responseurl = $moopanelurl . '/api/updates/plugins/instance/' . $instanceid;

        $customdata = [
            'responseurl' => $responseurl,
            'userid' => $userid,
            'username' => $username,
            'updates' => $updates,
        ];
        $task->set_custom_data((object)$customdata);

        // Set run task ASAP.
        $task->set_next_run_time(time() - 1);
        \core\task\manager::queue_adhoc_task($task, true);

        $taskwillrun = \core\task\manager::get_adhoc_tasks('\local_moopanel\task\plugins_update');

        if ($taskwillrun) {
            $task = reset($taskwillrun);
            $id = $task->get_id();
            $this->response->add_body_key('status', true);
            $this->response->add_body_key('moodle_job_id', $id);
            $this->response->add_body_key('message', 'Plugins update in progress.');
        } else {
            $this->response->send_error(STATUS_503, 'Service Unavailable - try again later.');
        }
    }

    private function post_zip_install() {
        global $CFG;

        require_once($CFG->dirroot.'/lib/upgradelib.php');

        $instanceid = $this->request->payload->instance_id ?? false;

        if (!is_numeric($instanceid)) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide a valid instance ID.');
        }

        $updates = $this->request->payload->updates ?? false;

        if (!$updates) {
            $this->response->send_error(STATUS_400, 'Bad request - no zip files urls specified.');
        }

        if (empty($updates)) {
            $this->response->send_error(STATUS_400, 'Bad request - no zip files urls specified.');
        }

        // Define Adhoc task for update plugins.
        $task = new plugins_install_zip();

        // Attach custom data to task, to know where to send response after task is completed.
        $userid = $this->request->payload->user_id ?? false;
        $username = $this->request->payload->username ?? false;
        $moopanelurl = get_config('local_moopanel', 'moopanelurl');
        $responseurl = $moopanelurl . '/api/updates/plugins/instance/' . $instanceid;

        $customdata = [
                'responseurl' => $responseurl,
                'userid' => $userid,
                'username' => $username,
                'updates' => $updates,
        ];
        $task->set_custom_data((object)$customdata);

        // Set run task ASAP.
        $task->set_next_run_time(time() - 1);
        \core\task\manager::queue_adhoc_task($task, true);

        $taskwillrun = \core\task\manager::get_adhoc_tasks('\local_moopanel\task\plugins_install_zip');

        if ($taskwillrun) {
            $task = reset($taskwillrun);
            $id = $task->get_id();
            $this->response->add_body_key('status', true);
            $this->response->add_body_key('moodle_job_id', $id);
            $this->response->add_body_key('message', 'Plugins install in progress.');
        } else {
            $this->response->send_error(STATUS_503, 'Service Unavailable - try again later.');
        }
    }


    private function get_plugin_updates($plugin) {
        $updates = [];

        $updateschecker = checker::instance();

        $pluginupdates = $updateschecker->get_update_info($plugin->component);
        if ($pluginupdates) {
            $data = [];
            $oldversion = $plugin->versiondisk;
            foreach ($pluginupdates as $pluginupdate) {
                $newversion = $pluginupdate->version;
                if ($newversion > $oldversion) {
                    $pluginupdate->type = 'plugin';
                    $updates[] = $pluginupdate;
                } else {
                    $a = 2;
                }
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

    private function updates_in_progress() {
        global $DB;

        $parameters = $this->request->parameters;

        $taskid = $parameters->moodle_job_id ?? false;

        if (!$taskid) {
            $this->response->send_error(STATUS_400, 'Bad Request - Please provide a valid moodle job ID.');
        }

        // Check if adhoc task exist.
        $taskexist = $DB->record_exists('task_adhoc', ['id' => $taskid]);

        if ($taskexist) {
            // Task is in progress or has failed.
            $task = $DB->get_record('task_adhoc', ['id' => $taskid]);

            if ($task->faildelay) {
                // Task was failed.
                $conditions = [
                    'component' => 'local_moopanel',
                    'classname' => 'local_moopanel\task\plugins_update',
                ];
                $report = $this->get_task_log($taskid);
            }

            if ($task->timestarted) {
                // Task is running.
                $this->response->add_body_key('status', 2);
                $this->response->add_body_key('error', '');
                return;
            }
        } else {
            // Task is completed.
            $report = $this->get_task_log($taskid);

            if (!$report) {
                $this->response->send_error(STATUS_500, 'There was problem running updates, please try again.');
            }

            $failed = $report['result'];

            if (!$failed) {
                // Task finished successfully.
                $this->response->add_body_key('status', 1);
                $this->response->add_body_key('error', '');
            } else {
                // Task failed.
                $this->response->add_body_key('status', 3);
                $this->response->add_body_key('error', $report['message']);
            }
        }
    }

    private function get_task_log($taskid) {
        global $DB;

        $conditions = [
                'component' => 'local_moopanel',
                'classname' => 'local_moopanel\task\plugins_update',
        ];
        $logs = $DB->get_records('task_log', $conditions, 'ID DESC');

        $report = [];

        foreach ($logs as $log) {
            $taskreport = $log->output;

            $search = "task id: " . $taskid . "\n";

            $contain = str_contains($taskreport, $search);

            if ($contain) {
                $report = [
                        'result' => (bool)$log->result,
                    'message' => $taskreport,
                ];
                return  $report;
            }
        }

        return false;
    }
}
