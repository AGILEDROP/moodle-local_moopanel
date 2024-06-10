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
 * Endpoint for manage moodle users.
 *
 * File         users.php
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

class users extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {
        switch ($this->request->method) {
            case 'POST':
                $this->post_request();
                break;

            case 'GET':

                switch ($this->request->path) {
                    case 'users/online':
                        $this->get_online_users();
                        break;
                    case 'users/count':
                        $this->get_users(false);
                        break;
                    default:
                        $this->get_users();
                        break;
                }
                break;
        }
    }

    private function get_users($count = true) {

        $parameters = $this->request->parameters;
        $payload = $this->request->payload;

        $search = $parameters->search ?? '';
        $fields = $parameters->fields ?? '*';
        $sort = $parameters->sort ?? 'lastname ASC';

        $data = [];
        $users = get_users($count, $search, false, null, $sort, '', '', '', '', $fields);

        if (is_numeric($users)) {
            $this->response->add_body_key('number_of_users', $users);
            return;
        }

        if (empty($users)) {
            $this->response->add_body_key('users', null);
            return;
        }

        foreach ($users as $user) {
            $data['users'][] = $user;
        }

        $this->response->add_body_key('number_of_users', count($data['users']));
        $this->response->add_body_key('users', $data['users']);
    }

    private function get_online_users() {
        global $DB;

        $now = time();

        $parameters = $this->request->parameters;

        // Parse start time.
        if (isset($parameters->timeStart)) {
            $start = (int)$parameters->timeStart;
        } else {
            $start = $now - 150; // Last 150 seconds.
        }

        // Parse end time.
        if (isset($parameters->timeEnd)) {
            $end = (int)$parameters->timeEnd;
        } else {
            $end = $now + 1;
        }

        // Validation.
        if ($start > $end) {
            $this->response->send_error(400, 'StartTime must be less than endTime.');
        }

        $sql = "SELECT username, firstname, lastname FROM {user} WHERE lastaccess >= :fromtime AND lastaccess < :totime";
        $params = [
            'fromtime' => $start,
            'totime' => $end,
        ];

        // Get number of all users.
        $allusers = $DB->count_records('user');
        // ToDo remove + 10 users. It is just for test.
        if ($allusers < 15) {
            $allusers = $allusers + 10;
        }

        // Get online users.
        $users = $DB->get_records_sql($sql, $params);

        // ToDo - dummy action currently return random numbe- remove it.
        // $online = count($users);
        $online = rand(0, $allusers);

        $this->response->add_body_key('all_users', $allusers);
        $this->response->add_body_key('online', $online);
    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not Implemented yet.');
    }
}
