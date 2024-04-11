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
 * Moopanel response class.
 *
 * File         response.php
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
 * MooPanel API response definition class.
 */
class response {

    public $status;

    public $headers;

    public $body;

    public $errors;

    public $format;

    public function __construct() {
        $this->set_status(STATUS_200);
        $this->headers = [];
        $this->add_header('Content-Type', 'application/json');
        $this->set_format('json');
        $this->body = new \stdClass();
        $this->errors = [];
    }

    public function set_status($status) {
        $this->status = $status;
    }

    public function get_status() {
        return $this->status;
    }

    public function add_header($key, $value) {
        $this->headers[$key] = $value;
    }

    public function get_header($key) {
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        } else {
            return false;
        }
    }

    public function set_format($format) {
        $this->format = $format;
    }

    public function get_format() {
        return $this->format;
    }

    public function set_body($body) {
        $this->body = $body;
    }

    public function get_body() {
        return $this->body;
    }

    public function add_body_key($key, $value) {
        $this->body->$key = $value;
    }

    public function get_body_key($key) {
        return $this->body->$key;
    }

    public function set_error($key, $value) {
        $this->errors[$key] = $value;
    }

    public function set_errors($errors) {
        $this->errors = $errors;
    }

    public function get_errors() {
        return $this->errors;
    }

    public function print_headers() {
        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }
    }

    public function encode_body() {
        switch ($this->format) {
            case 'json':
                $body = json_encode($this->body);
                break;
            case 'xml':
                $body = [];
                break;
            default:
                $body = 'Unknown response format';
        }
        return $body;
    }

    public function send_error($status, $errormsg) {
        $this->set_status($status);
        $this->add_body_key('error', $errormsg);

        $this->send();
    }

    public function send() {
        http_response_code($this->status);

        $this->print_headers();
        echo $this->encode_body();

        die();
    }
}
