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
 * Moopanel request class.
 *
 * File         request.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel;

/**
 * MooPanel API request definition class.
 */
class request {

    public $method;

    public $headers;

    public $parameters;

    public $payload;

    public function __construct() {
        $this->set_method('GET');
        $this->headers = [];
        $this->parameters = [];
        $this->payload = [];
    }

    public function set_method($method) {
        $this->method = $method;
    }

    public function get_method() {
        return $this->method;
    }

    public function set_header($key, $value) {
        $this->headers[$key] = $value;
    }

    public function get_header($key) {
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        } else {
            return false;
        }
    }

    public function set_headers(array $headers) {
        $this->headers = $headers;
    }

    public function get_headers() {
        return $this->headers;
    }

    public function set_parameters(array $parameters) {
        $this->parameters = $parameters;
    }

    public function get_parameters() {
        return $this->parameters;
    }

    public function set_payload($payload) {
        $this->payload = $payload;
    }

    public function get_payload() {
        return $this->payload;
    }
}
