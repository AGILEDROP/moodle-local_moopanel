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

use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class plugin extends endpoint implements endpoint_interface {

    public function define_allowed_request_methods() {
        return ['GET', 'POST'];
    }

    public function process_request($requestmethod, $requestparameters, $payload = null, $responsetype = null) {

        $plugin = null;
        if (isset($payload->plugin)) {
            $plugin = $payload->plugin;
        }
        // Check if plugin exist.
        if (!$this->plugin_exist($plugin)) {
            return;
        }

        switch ($requestmethod) {
            case 'POST':
                $this->post_request($payload);
                break;

            case 'GET':
                if(in_array('config', $requestparameters)) {
                    $plugindata = $this->get_plugin_config($plugin);
                }
                else {
                    $plugindata = $this->get_plugin($plugin);
                }
                break;
        }
    }

    private function plugin_exist($plugin) {
        if (!$plugin) {
            $this->responsecode = 400;
            $this->responsemsg = 'Plugin not specified.';
            return false;
        }

        $plugin_man = \core_plugin_manager::instance();
        $data = $plugin_man->get_plugin_info($plugin);

        if (!$data) {
            $this->responsecode = 400;
            $this->responsemsg = 'Plugin not exist.';
            return false;
        }

        return true;
    }

    private function get_plugin($plugin = null) {

        $plugin_man = \core_plugin_manager::instance();
        $data = $plugin_man->get_plugin_info($plugin);

        if (isset($data->pluginman)) {
            unset($data->pluginman);
        }

        $arr = convert_to_array($data);

        $this->responsecode = 200;
        $this->responsemsg = 'OK';
        $this->responsebody = (object)$arr;

        return $data;
    }

    private function get_plugin_config($plugin = null) {

        $config = get_config($plugin);

        $this->responsecode = 200;
        $this->responsemsg = 'OK';
        $this->responsebody = (object)$config;

        return $config;
    }


    private function post_request($data) {
        $this->responsecode = 501;
        $this->responsemsg = 'Not implemented yet.';
    }
}
