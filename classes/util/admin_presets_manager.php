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
 * Utility class - admin presets manager.
 *
 * File         admin_presets_manager.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\util;

use core_adminpresets\manager;
use core_user;
use DateTime;

class admin_presets_manager {

    public $db;

    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    public function plugin_exist() {
        $action = 'base';
        $classname = 'tool_admin_presets\\local\\action\\'.$action;

        if (!class_exists($classname)) {
            return false;
        }

        return true;
    }

    public function presets_exist($presetname) {
        $conditions = [
            'name' => $presetname,
        ];

        return $this->db->record_exists('adminpresets', $conditions);
    }

    public function presets_get($presetname) {
        $exist = $this->presets_exist($presetname);
        if (!$exist) {
            return false;
        }

        $conditions = [
            'name' => $presetname,
        ];

        $preset = $this->db->get_record('adminpresets', $conditions);
        return $preset->id;
    }

    public function presets_create() {
        global $USER, $DB, $PAGE;

        $USER = $DB->get_record('user', ['id' => 2]);
        $PAGE->set_context(\context_system::instance());

        $manager = new manager();

        $date = new DateTime();
        $now = $date->format('H:i:s - d.m.Y');

        $data = new \stdClass();
        $data->name = 'Moopanel';
        $data->comments = [
                'text' => 'Preset for MooPanel application created on ' . $now,
                'format' => "1",
        ];
        $data->userid = 2;
        $data->author = 'Moopanel App';
        $data->includesensiblesettings = "1";

        $preset = $manager->export_preset($data);

        return $preset[0] ?? false;
    }

    public function preset_get_xml($presetid) {
        global $USER, $CFG, $DB, $PAGE;

        $USER = \core_user::get_user(2);
        /*
        $user = $DB->get_record('user', ['id' => 2]);
        \core\session\manager::login_user($user);
        */

        // Include needle library.
        require_once($CFG->dirroot.'/backup/util/xml/output/xml_output.class.php');
        require_once($CFG->dirroot.'/backup/util/xml/output/memory_xml_output.class.php');
        require_once($CFG->dirroot.'/backup/util/xml/xml_writer.class.php');

        $PAGE->set_context(\context_system::instance());

        $manager = new manager();

        $preset = $manager->download_preset($presetid);

        return $preset[0] ?? false;
    }

    public function presets_delete($presetid) {
        $manager = new manager();
        $manager->delete_preset($presetid);
    }

    public function presets_send($url, $instanceid, $xml) {

        $endpoint = $url . '/local/moopanel/pages/test_post.php?instanceid=' . $instanceid;
        $handler = curl_init($endpoint);

        $apikey = get_config('local_moopanel', 'apikey');

        $authorization = 'Authorization: Bearer ' . $apikey;

        $headers = [
                'Content-Type: application/xml',
        ];

        curl_setopt($handler, CURLOPT_POST, true);
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_TIMEOUT, 30);
        curl_setopt($handler, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($handler);

        $statuscode = curl_getinfo($handler, CURLINFO_HTTP_CODE);

        curl_close($handler);

        if ($statuscode != 200) {
            return false;
        }

        return true;
    }

    /**
     * Send report message to given email address.
     *
     * @param string $to Email address.
     * @param string $subject Message subject.
     * @param string $body Message body (html).
     * @return void
     */
    public function preset_send_to_email($to, $subject, $body) {

        $from = core_user::get_noreply_user();
        $replyto = core_user::get_noreply_user();

        $headers  = "From: " . $from->email . "\r\n";
        $headers .= "Reply-To: " . $replyto->email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: application/xml; charset=UTF-8\r\n";

        mail($to, $subject, $body, $headers);
    }
}
