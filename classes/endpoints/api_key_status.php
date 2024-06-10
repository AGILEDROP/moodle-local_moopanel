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

    public function allowed_methods() {
        return ['GET', 'POST'];
    }

    public function execute_request() {
        switch ($this->request->method) {
            case 'POST':
                $this->update_api_key();
                break;

            case 'GET':
                $this->get_api_key_info();
                break;
        }
    }

    private function update_api_key() {
        $now = time();

        $payload = $this->request->payload;

        if (object_property_exists($payload, 'key_expiration_date')) {

            $expiration = $payload->key_expiration_date;

            if ($expiration == null) {
                $timestamp = 'permanent';
            } else {
                $timestamp = $expiration;
            }

            set_config('key_expiration_date', $timestamp, 'local_moopanel');
            $this->response->set_status(STATUS_201);
            $this->response->add_body_key('status', true);
            $this->response->add_body_key('key_expiration_date', $timestamp);
        } else {
            $this->response->send_error(STATUS_400, 'Missing API key expiration_date');
        }
    }

    private function get_api_key_info() {
        $timestamp = get_config('local_moopanel', 'key_expiration_date');

        if ($timestamp == 'permanent') {
            $expirationdate = null;
        } else {
            $expirationdate = date('m/d/Y H:i:s', $timestamp);
        }

        $this->response->add_body_key('key_expiration_date', $expirationdate);
    }
}
