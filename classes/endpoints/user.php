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
 * Endpoint for manage selected moodle user.
 *
 * File         user.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\endpoints;

use core_user;
use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class user extends endpoint implements endpoint_interface {

    private $user;

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {

        switch ($this->request->method) {
            case 'POST':
                $this->post_request();
                break;

            case 'GET':
                $user = $this->get_user();
                break;
        }
    }

    private function get_user() {

        $parameters = $this->request->parameters;

        if (isset($parameters->upn)) {
            $this->user = core_user::get_user_by_email($parameters->upn);
        } else if (isset($parameters->username)) {
            $this->user = core_user::get_user_by_username($parameters->username);
        } else if (isset($parameters->id)) {
            $this->user = core_user::get_user($parameters->id);
        } else {
            $this->response->send_error(STATUS_400, 'No parameters provided.');
        }

        if (!$this->user) {
            $this->response->send_error(STATUS_400, 'User not found.');
        }

        $this->response->add_body_key('user', $this->user);
    }

    private function post_request() {
        $this->response->send_error(STATUS_501, 'Not implemented yet.');

    }
}
