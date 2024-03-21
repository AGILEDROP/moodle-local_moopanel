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
 * Moopanel API class.
 *
 * File         moopanel_api.php
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
 * MooPanel API definition class.
 */
class moopanel_api {

    private $pluginconfig;

    private $endpoint;

    private $requestmethod;

    private $requestdata;

    private $responsetype;

    private $response;

    public function __construct() {
        $this->pluginconfig = get_config('local_moopanel');
        $this->endpoint = 'test_connection';
        $this->requestmethod = $this->set_request_method();
        $this->requestdata = [];
        $this->responsetype = 'json';
        $this->response = new \stdClass();
    }

    public function run($apikey, $ip) {

        $this->api_enabled();

        $this->authenticate_user($apikey, $ip);

        $this->parse_request();

        $classname = "local_moopanel\\endpoints\\" . $this->endpoint;

        $endpointcontroller = new $classname();

        $allowedmethods = $endpointcontroller->allowedmethods;

        if (!in_array($this->requestmethod, $allowedmethods)) {
            $this->return_bad_response(405, ['status' => 'Method not allowed.']);
        }

        $this->response = $endpointcontroller->get_response(
                $this->requestmethod,
                $this->requestdata,
                $this->responsetype
        );

        $this->return_response();
    }

    protected function api_enabled() {
        $apienabled = (bool)$this->pluginconfig->apienabled;
        if (!$apienabled) {
            // Api is not enabled in moodle plugin settings.
            $this->return_bad_response(202, ['status' => 'API disabled.']);
        }
    }

    protected function authenticate_user($apikey, $ip) {

        // Currently just simple check for auth.
        if ($apikey !== $this->pluginconfig->apikey) {
            $this->return_bad_response(401, ['status' => 'unauthorised']);
        }
    }

    protected function set_request_method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    protected function return_bad_response($code, $details) {

        header("Content-Type: application/json; charset=UTF-8");

        http_response_code($code);

        foreach ($details as $key => $value) {
            $this->response->$key = $value;
        }
        echo json_encode($this->response);

        die;
    }

    protected function parse_request() {

        // Get request variables.
        $this->requestdata = array_merge($_GET, $_POST);

        // Check for endpoints.
        if (isset($this->requestdata['operation'])) {
            $endpointcandidate = $this->requestdata['operation'];
            $classname = "local_moopanel\\endpoints\\" . $endpointcandidate;

            if (class_exists($classname)) {
                $this->endpoint = $endpointcandidate;

            } else {
                $this->return_bad_response(403, ['status' => 'Bad request']);
            }
        }
    }

    protected function return_response() {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($this->response);
        die();
    }
}
