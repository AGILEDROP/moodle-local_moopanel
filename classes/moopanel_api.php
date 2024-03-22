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

    private $responsecode;

    private $responsemsg;

    private $responsebody;

    public function __construct() {
        $this->pluginconfig = get_config('local_moopanel');
        $this->endpoint = 'test_connection';
        $this->requestmethod = $this->set_request_method();
        $this->requestdata = [];
        $this->responsetype = 'json';
        $this->responsecode = false;
        $this->responsemsg = false;
        $this->responsebody = new \stdClass();
    }

    public function run($apikey, $ip) {

        $this->api_enabled();

        $this->authenticate_user($apikey, $ip);

        $this->parse_request();

        $classname = "local_moopanel\\endpoints\\" . $this->endpoint;

        $endpointcontroller = new $classname();

        $allowedmethods = $endpointcontroller->define_allowed_request_methods();

        if (!in_array($this->requestmethod, $allowedmethods)) {
            $this->responsecode = 405;
            $this->responsemsg = 'Method not allowed.';
            $this->return_response();
        }

        $endpointcontroller->process_request(
                $this->requestmethod,
                $this->requestdata,
                $this->responsetype
        );

        $this->responsecode = $endpointcontroller->get_response_code();
        $this->responsemsg = $endpointcontroller->get_response_msg();
        $this->responsebody = $endpointcontroller->get_response();

        $this->return_response();
    }

    protected function api_enabled() {
        $apienabled = (bool)$this->pluginconfig->apienabled;
        if (!$apienabled) {
            // Api is not enabled in moodle plugin settings.
            $this->responsecode = 202;
            $this->responsemsg = 'API disabled.';
            $this->return_response();
        }
    }

    protected function authenticate_user($apikey, $ip) {

        // Currently just simple check for auth.
        if ($apikey !== $this->pluginconfig->apikey) {
            $this->responsecode = 401;
            $this->responsemsg = 'unauthorised';
            $this->return_response();
        }
    }

    protected function set_request_method() {
        return $_SERVER['REQUEST_METHOD'];
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
                $this->responsecode = 403;
                $this->responsemsg = 'Bad request.';
                $this->return_response();
            }
        }
    }

    protected function return_response() {

        http_response_code($this->responsecode);

        header("Content-Type: application/json; charset=UTF-8");

        if ($this->responsemsg) {
            $this->responsebody->status = $this->responsemsg;
        }

        echo json_encode($this->responsebody);
        die();
    }
}
