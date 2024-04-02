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
 * Endpoint for manage moodle core versions.
 *
 * File         moodle_core.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\endpoints;

use core\update_checker_test;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class moodle_core extends endpoint implements endpoint_interface {

    public function define_allowed_request_methods() {
        return ['GET', 'POST'];
    }

    public function process_request($requestmethod, $requestparameters, $payload = null, $responsetype = null) {

        switch ($requestmethod) {
            case 'POST':
                $this->post_request($payload);
                break;

            case 'GET':

                $data = $this->get_moodle_core_info();

                $this->responsecode = 200;
                $this->responsemsg = 'OK';
                $this->responsebody = (object)$data;

                break;
        }
    }

    private function get_moodle_core_info() {
        global $CFG, $DB;

        $updateschecker = \core\update\checker::instance();
        $lastcheck = $updateschecker->get_last_timefetched();
        $updates = $updateschecker->get_update_info('core');
        $updatesstable = [];
        foreach ($updates as $update) {
            // Only stable updates.
            if ($update->maturity === 200) {
                $updatesstable[] = $update;
            }
        }

        $conditions = [
                'plugin' => 'core',
        ];
        $updatelogs = $DB->get_records('upgrade_log', $conditions, 'id DESC');
        $logs = [];

        foreach ($updatelogs as $updatelog) {

            $info = $updatelog->info;
            if ($info == 'Core upgraded' || $info == 'Core installed')
            $logs[] = (array)$updatelog;
        }

        $data = [
            'status' => 'OK',
            'current_version' => $CFG->release,
            'last_check_for_updates' => $lastcheck,
            'updates_available' => $updatesstable,
            'updates_log' => $logs,
        ];

        return $data;
    }


    private function post_request($data) {
        $this->responsecode = 501;
        $this->responsemsg = 'Not implemented yet.';
    }
}
