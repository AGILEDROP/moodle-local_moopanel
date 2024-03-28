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

    public function define_allowed_request_methods() {
        return ['GET', 'POST'];
    }

    public function process_request($requestmethod, $requestparameters, $payload = null, $responsetype = null) {

        switch ($requestmethod) {
            case 'POST':
                $this->post_request($payload);
                break;

            case 'GET':
                $user = $this->get_user($payload);


                $this->responsebody = (object)$user;

                break;
        }
    }

    private function get_user($parameters){

        if(isset($parameters->email)) {
            $user = core_user::get_user_by_email($parameters->email);
        } elseif (isset($parameters->username)) {
            $user = core_user::get_user_by_username($parameters->username);
        } elseif (isset($parameters->id)) {
            $user = core_user::get_user($parameters->id);
        } else {

            $this->responsecode = 400;
            $this->responsemsg = 'OK';
            return [
                    'error' => 'Specify id, username or email to get user.',
            ];
        }

        if (!$user) {
            $this->responsecode = 200;
            $this->responsemsg = 'OK';
            return [
                    'error' => 'User not exist.',
            ];
        }

        $this->responsecode = 200;
        $this->responsemsg = 'OK';
        return [
                'user' => $user,
        ];
    }

    private function post_request($data) {
        $this->responsecode = 501;
        $this->responsemsg = 'Not implemented yet.';
    }
}
