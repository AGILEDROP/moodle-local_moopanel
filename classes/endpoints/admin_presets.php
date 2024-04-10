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

use core_adminpresets\manager;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;
use memory_xml_output;
use xml_writer;
use tool_admin_presets\local\action\export;

class admin_presets extends endpoint implements endpoint_interface {

    public function define_allowed_request_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {
        switch ($this->request->method) {
            case 'POST':
                $this->post_request();
                break;

            case 'GET':
                $this->get_admin_preset();
                break;
        }
    }

    private function get_admin_preset() {
        global $DB, $SESSION;

        $action = 'base';
        $classname = 'tool_admin_presets\\local\\action\\'.$action;

        if (!class_exists($classname)) {
            $this->response->send_error(STATUS_400, 'Bad Request - there is no action.');
        }

        $manager = new manager();

        $presetexist = $this->admin_preset_exist();

        // There is no presets yet, we must create new one.
        if (!$presetexist) {
            $this->admin_preset_create();
        }

        $presetrecord = $DB->get_record('adminpresets', ['name' => 'Moopanel']);

        ob_clean();
        ob_start();

        //$preset = $this->admin_preset_download($presetrecord->id);

        $this->response->add_body_key('preset_id', $presetrecord->id);
    }

    private function admin_preset_exist() {
        global $DB;

        $count = $DB->count_records('adminpresets', ['name' => 'moopanel']);

        if ($count === 1) {
            return true;
        }

        return false;
    }

    private function admin_preset_create() {
        global $USER, $DB, $PAGE;

        $USER = $DB->get_record('user', ['id' => 2]);
        $PAGE->set_context(\context_system::instance());

        $manager = new manager();

        $data = new \stdClass();
        $data->name = 'Moopanel';
        $data->comments = [
                'text' => 'Preset created for MooPanel application.',
                'format' => "1",
        ];
        $data->userid = 2;
        $data->author = 'Admin User';
        $data->includesensiblesettings = "1";

        $preset = $manager->export_preset($data);
    }

    private function admin_preset_download($presetid) {
        global $USER, $DB, $PAGE;

        $user = $DB->get_record('user', ['id' => 2]);
        \core\session\manager::login_user($user);

        $a = 2;
        $PAGE->set_context(\context_system::instance());

        $manager = new manager();

        // $exporter = new export();
        // $exporter->download_xml();

        $xmloutput = new memory_xml_output();
        $xmlwriter = new xml_writer($xmloutput);
        $xmlwriter->start();

        $a = 2;

        $preset = $manager->download_preset($presetid);

        return $preset;
    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not Implemented yet.');
    }
}
