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

use core_plugin_manager;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class plugins extends endpoint implements endpoint_interface {

    public function define_allowed_request_methods() {
        return ['GET', 'POST'];
    }

    public function process_request($requestmethod, $requestparameters, $payload = null, $responsetype = null) {

        switch ($requestmethod) {
            case 'POST':
                $this->post_request($payload);
                break;

            case 'GET':
                $contrib = in_array('contrib', $requestparameters);
                $updates = in_array('updates', $requestparameters);

                $plugins = $this->get_plugins($contrib, $updates);

                $this->responsecode = 200;
                $this->responsemsg = 'OK';
                $this->responsebody = (object)$plugins;

                break;
        }
    }


    private function get_plugins($contribonly = false, $updatesonly = false) {
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
                        'available_updates' => $hasupdates,
                        'settings_section' => $plugin->get_settings_section_name(),
                        'directory' => $plugin->get_dir(),
                ];

                if (!$contribonly && !$updatesonly) {
                    // All plugins.
                    $data['plugins'][] = $plugininfo;
                } else if (!$contribonly && $updatesonly) {
                    // All plugins updates.
                    if ($hasupdates) {
                        $data['plugins'][] = $plugininfo;
                    }
                } else if ($contribonly && !$updatesonly) {
                    // All contrib plugins.
                    if (!$isstandard) {
                        $data['plugins'][] = $plugininfo;
                    }
                } else {
                    // Only contrib plugins which has updates.
                    if (!$isstandard && $hasupdates) {
                        $data['plugins'][] = $plugininfo;
                    }
                }
            }
        }
        return $data;
    }

    private function post_request($data) {
        $this->responsecode = 501;
        $this->responsemsg = 'Not implemented yet.';
    }
}
