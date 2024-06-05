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

class admin_presets extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {
        switch ($this->request->method) {
            case 'GET':
                $this->get_admin_preset();
                break;
            case 'POST':
                $this->post_request();
                break;
        }
    }

    private function get_admin_preset() {

        $action = 'base';
        $classname = 'tool_admin_presets\\local\\action\\'.$action;

        if (!class_exists($classname)) {
            $this->response->send_error(STATUS_400, 'Bad Request - Admin presets not found.');
        }

        $preset = $this->admin_preset_exist();

        if ($preset) {
            // Preset already exist, we must delete it.
            $this->admin_preset_delete($preset);
        }

        // Create new admin preset.
        $preset = $this->admin_preset_create();

        if (!$preset) {
            $this->response->send_error(STATUS_400, 'Problem while creating preset.');
        }

        $presetdata = $this->admin_preset_download($preset);

        $xml = $presetdata[0] ?? false;

        if (!$xml) {
            $this->response->send_error(STATUS_400, 'Invalid preset xml content.');
        }

        $this->response->set_format('xml');
        $this->response->add_header('Content-Type', 'application/xml');
        $this->response->set_body($xml);
    }

    private function admin_preset_exist() {
        global $DB;

        $conditions = [
                'name' => 'Moopanel',
        ];

        $exist = $DB->record_exists('adminpresets', $conditions);

        if (!$exist) {
            return false;
        }

        $presetrecord = $DB->get_record('adminpresets', $conditions);

        return $presetrecord->id;
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
        $data->author = 'Moopanel App';
        $data->includesensiblesettings = "1";

        $preset = $manager->export_preset($data);

        return $preset[0] ?? false;
    }

    private function admin_preset_delete($preseid) {
        $manager = new manager();
        $manager->delete_preset($preseid);
    }

    private function admin_preset_download($presetid) {
        global $USER, $CFG, $DB, $PAGE;

        $user = $DB->get_record('user', ['id' => 2]);
        \core\session\manager::login_user($user);


        // Include needle library.
        require_once($CFG->dirroot.'/backup/util/xml/output/xml_output.class.php');
        require_once($CFG->dirroot.'/backup/util/xml/output/memory_xml_output.class.php');
        require_once($CFG->dirroot.'/backup/util/xml/xml_writer.class.php');

        $PAGE->set_context(\context_system::instance());

        $manager = new manager();

        $preset = $manager->download_preset($presetid);

        return $preset;
    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not Implemented yet.');
    }
}
