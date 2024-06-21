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
 * Adhoc task class - install plugins from specified zip files.
 *
 * File         plugins_install_zip.php
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

class plugins_install_zip extends adhoc_task {

    public function get_name() {
        return get_string('task:pluginsinstallzip', 'local_moopanel');
    }

    public function execute() {
        $id = $this->get_id();

        $pluginman = core_plugin_manager::instance();

        $customdata = $this->get_custom_data();

        $response = new response();
        $response->add_header('X-API-KEY', get_config('local_moopanel', 'apikey'));
        $response->add_body_key('moodle_job_id', (int)$id);
        $response->add_body_key('user_id', $customdata->userid);
        $response->add_body_key('username', $customdata->username);

        $url = $customdata->responseurl;
        $updates = $customdata->updates;

        $pluginmanager = new plugin_manager();

        $data = [];

        mtrace("################################################################################");

        foreach ($updates as $update) {
            $report = $pluginmanager->install_zip($update);
            $component = $report['component'] ?? null;
            $version = null;

            if ($component) {
                $plugin = $pluginman->get_plugin_info($component);
                $version = $plugin->versiondisk;
            }

            $data[] = [
                'link' => $update,
                'status' => $report['status'],
                'component' => $component,
                'version' => $version,
                'error' => $report['error'],
            ];

            $msg = $update . ' status: ' . $report['status'] . ', error: ' . $report['error'];
            mtrace($msg);
        }

        mtrace("################################################################################");

        $response->add_body_key('updates', $data);

        // Send response to Moo-panel app.
        $post = $response->post_to_url($url);

        // $response->send_to_email('test@test.com', 'Plugins install', $response->body);
    }
}
