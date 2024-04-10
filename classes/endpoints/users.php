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
                $count = in_array('count', $this->request->parameters);
                $online = in_array('online', $this->request->parameters);

                if ($online) {
                    $this->get_online_users();
                } else {
                    $this->get_users($count);
                }
                break;
        }
    }

    private function get_users($count) {

        $payload = $this->request->payload;

        $count = !$count;

        $search = (isset($payload->search)) ? $payload->search : '';
        $confirmed = (isset($payload->confirmed)) ? $payload->confirmed : false;
        $ignoreids = (isset($payload->ignoreids)) ? $payload->ignoreids : null;
        $sort = (isset($payload->sort)) ? $payload->sort : 'firstname ASC';
        $firstinitial = (isset($payload->firstinitial)) ? $payload->firstinitial : '';
        $lastinitial = (isset($payload->lastinitial)) ? $payload->lastinitial : '';
        $page = (isset($payload->page)) ? $payload->page : '';
        $limit = (isset($payload->limit)) ? $payload->limit : 9999999999;
        $fields = (isset($payload->fields)) ? $payload->fields : '*';

        $data = [];
        $users = get_users($count, $search, $confirmed, $ignoreids, $sort, $firstinitial, $lastinitial, $page, $limit, $fields);

        if (is_numeric($users)) {
            $this->response->add_body_key('number_of_users', $users);
            return;
        }

        if (empty($users)) {
            $this->response->add_body_key('users', null);
            return;
        }

        if ($fields == '*') {
            foreach ($users as $user) {
                $data['users'][] = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ];
            }
        } else {
            foreach ($users as $user) {
                $data['users'][] = $user;
            }
        }

        $this->response->add_body_key('users', $data['users']);
    }

    private function get_online_users() {
        global $DB;

        $now = time();
        $fromtime = $now - 150; // Last 150 seconds.
        $sql = "SELECT username, firstname, lastname FROM {user} WHERE lastaccess > :fromtime";
        $params = [
                'fromtime' => $fromtime,
        ];

        $users = $DB->get_records_sql($sql, $params);

        $this->response->add_body_key('number_of_users', count($users));

        $data = null;

        if (!empty($users)) {
            foreach ($users as $user) {
                $data[] = $user;
            }
        }

        $this->response->add_body_key('users', $data);



    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not Implemented yet.');
    }
}
