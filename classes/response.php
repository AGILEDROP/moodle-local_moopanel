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

use core_user;
use Exception;
use stdClass;

/**
 * MooPanel API response definition class.
 */
class response {

    public $status;

    public $headers;

    public $body;

    public $error;

    public $format;

    private $logger;

    public function __construct() {
        $this->set_status(200);
        $this->headers = [];
        $this->add_header('Content-Type', 'application/json');
        $this->set_format('json');
        $this->body = new stdClass();
        $this->error = null;
        $this->logger = new logger();
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
        $value = $this->body->$key ?? false;
        return $value;
    }

    public function set_error($value) {
        $this->error = $value;
    }

    public function get_error() {
        return $this->error;
    }

    public function print_headers() {
        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }
    }

    public function build_headers_array() {
        $data = [];
        foreach ($this->headers as $header => $value) {
            $data[] = $header . ': ' . $value;
        }

        return $data;
    }

    public function encode_body() {
        switch ($this->format) {
            case 'json':
                $body = json_encode($this->body);
                break;
            case 'xml':
                $body = $this->body;
                break;
            default:
                $body = 'Unknown response format';
        }
        return $body;
    }

    public function send_error($status, $errormsg) {
        $this->set_status($status);
        $this->set_error($errormsg);
        $this->add_body_key('error', $errormsg);

        $this->send();
    }

    public function send() {
        $error = $this->get_error();
        $body = $this->encode_body();

        $this->logger->log(
                ($error) ? 'error': 'response',
                '',
                '',
                '',
                $this->get_status(),
                $body,
        );

        http_response_code($this->status);

        $this->print_headers();
        echo $body;

        die();
    }

    public function post_to_url($url) {
        $handler = curl_init($url);

        $headers = $this->build_headers_array();
        $body = $this->encode_body();

        curl_setopt($handler, CURLOPT_POST, true);
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_TIMEOUT, 30);
        curl_setopt($handler, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($handler);

        $statuscode = curl_getinfo($handler, CURLINFO_HTTP_CODE);

        curl_close($handler);

        $this->logger->log(
                'adhoc_task_response',
                $url,
                'POST',
                '',
                $this->get_status(),
                $this->encode_body(),
        );

        return $statuscode;
    }

    /**
     * Send report message to given email address.
     *
     * @param string $to Email address.
     * @param string $subject Message subject.
     * @param mixed $body Message body (html/json/xml/string).
     * @return void
     */
    public function send_to_email($to, $subject) {

        $from = core_user::get_noreply_user();
        $replyto = core_user::get_noreply_user();

        $this->add_header('From', $from->email);
        $this->add_header('Reply-To', $replyto->email);
        $this->add_header('MIME-Version', '1.0');

        $headers = $this->headers;

        $body = $this->encode_body();

        mail($to, $subject, $body, $headers);
    }

    public function send_exception(Exception $exception) {
        global $CFG;

        $this->set_status(555);
        $this->body = new stdClass();
        $this->add_body_key('code', $exception->getCode());
        $this->add_body_key('message', $exception->getMessage());
        $this->add_body_key('file', $exception->getFile());
        $this->add_body_key('line', $exception->getLine());
        $this->add_body_key('trace', $exception->getTraceAsString());

        $subject = 'Error on ' . $CFG->wwwroot;
        $msg = $this->encode_body();
        $to = 'uros.virag@agiledrop.com';

        $this->send_exception_to_email($to, $subject, $msg);

        $this->send();
    }

    /**
     * Send report message to given email address.
     *
     * @param string $to Email address.
     * @param string $subject Message subject.
     * @param string $body Message body (html).
     * @return void
     */
    private function send_exception_to_email($to, $subject, $body) {

        $from = core_user::get_noreply_user();
        $replyto = core_user::get_noreply_user();

        $headers  = "From: " . $from->email . "\r\n";
        $headers .= "Reply-To: " . $replyto->email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        mail($to, $subject, $body, $headers);
    }

    private function log_error() {
        global $DB;

        $now = new \DateTime();
        $log = new stdClass();
        $log->timestamp = $now->getTimestamp();
        $log->type = 'error';
        $log->endpoint = '';
        $log->method = '';
        $log->statuscode = $this->get_status();
        $log->body = (string)$this->body->error;

        $DB->insert_record('moopanel_logs', $log);
    }

}
