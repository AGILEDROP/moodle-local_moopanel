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
 * Endpoint for manage Moopanel API - key.
 *
 * File         api_key_status.php
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

class api_key_status extends endpoint implements endpoint_interface {

    public function define_allowed_request_methods() {
        return ['GET', 'POST'];
    }

    public function process_request($requestmethod, $requestparameters, $payload = null, $responsetype = null) {
        global $CFG, $SITE, $THEME, $PAGE;

        switch ($requestmethod) {
            case 'POST':
                $this->update_api_key($payload);
                break;

            case 'GET':
                $this->get_api_key_expiration();
                break;
        }
    }

    private function update_api_key($payload) {
        $data = [];
        $now = time();

        if (isset($payload->key_expiration_date)) {
            $timestamp = $payload->key_expiration_date;
            set_config('key_expiration_date', $timestamp, 'local_moopanel');
            $this->responsecode = 201;
            $this->responsemsg = 'updated';
            $this->responsebody->key_expiration_date = $timestamp;
        } else {
            $this->responsecode = 400;
            $this->responsemsg = 'Bad request.';
            $data['Error'] = 'Missing arguments - key_expiration_date';
            $this->responsebody = (object)$data;
        }
    }

    private function get_api_key_expiration() {
        $data['key_expiration_date'] = get_config('local_moopanel', 'key_expiration_date');
        $this->responsecode = 200;
        $this->responsemsg = 'OK';
        $this->responsebody = (object)$data;
    }
}
