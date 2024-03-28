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

    public function define_allowed_request_methods() {
        return ['GET', 'POST'];
    }

    public function process_request($requestmethod, $requestparameters, $payload = null, $responsetype = null) {

        switch ($requestmethod) {
            case 'POST':
                $this->post_request($payload);
                break;

            case 'GET':
                $count = in_array('count', $requestparameters);
                $online = in_array('online', $requestparameters);

                if ($online) {
                    $users = $this->get_online_users();
                } else {
                    $users = $this->get_users($payload, $count);
                }

                $this->responsecode = 200;
                $this->responsemsg = 'OK';
                $this->responsebody = (object)$users;

                break;
        }
    }

    private function get_users($parameters, $count){
        $count = !$count;

        $search = (isset($parameters->search)) ? $parameters->search : '';
        $confirmed = (isset($parameters->confirmed)) ? $parameters->confirmed : false;
        $ignoreids = (isset($parameters->ignoreids)) ? $parameters->ignoreids : null;
        $sort = (isset($parameters->sort)) ? $parameters->sort : 'firstname ASC';
        $firstinitial = (isset($parameters->firstinitial)) ? $parameters->firstinitial : '';
        $lastinitial = (isset($parameters->lastinitial)) ? $parameters->lastinitial : '';
        $page = (isset($parameters->page)) ? $parameters->page : '';
        $limit = (isset($parameters->limit)) ? $parameters->limit : 9999999999;
        $fields = (isset($parameters->fields)) ? $parameters->fields : '*';

        $data = [];
        $users = get_users($count, $search, $confirmed, $ignoreids, $sort, $firstinitial, $lastinitial, $page, $limit, $fields);

        if (is_numeric($users)) {
            return [
                    'number_of_users' => $users,
            ];
        }

        if (empty($users)) {
            return [
                    'users' => 'No users for given parameters.',
            ];
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

        return $data;
    }

    private function get_online_users() {
        global $DB;

        return [
                'number_of_users' => 123,
            'users' => [],
        ];
    }

    private function post_request($data) {
        $this->responsecode = 501;
        $this->responsemsg = 'Not implemented yet.';
    }
}
