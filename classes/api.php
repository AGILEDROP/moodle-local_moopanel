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
 * Define response status codes.
 */
// 200 = OK.
define('STATUS_200', 200);

// 201 = Created.
define('STATUS_201', 201);

// 202 = Accepted.
define('STATUS_202', 202);

// 400 = Bad request.
define('STATUS_400', 400);

// 401 = Unauthorized.
define('STATUS_401', 401);

// 403 = Forbidden.
define('STATUS_403', 403);

// 404 = Not found.
define('STATUS_404', 404);

// 405 = Method not allowed.
define('STATUS_405', 405);

// 500 = Internal Server Error.
define('STATUS_500', 500);

// 501 = Not implemented.
define('STATUS_501', 501);


/**
 * MooPanel API definition class.
 */
class api {

    public request $request;

    public response $response;

    private $config;

    private $authorized;

    private $endpoint;

    public function __construct() {
        $this->request = new request();
        $this->response = new response();

        $this->config = get_config('local_moopanel');
        $this->authorized = false;
    }

    public function run() {

        try {
            // Check if API is enabled in Moodle.
            $apienabled = $this->api_enabled();
            if (!$apienabled) {
                $this->response->send_error(STATUS_403, 'API is disabled.');
            }

            // Get request headers.
            $this->parse_request_headers($_SERVER);

            // Check for authorization.
            $this->authorize();
            if (!$this->authorized) {
                $this->response->send_error(STATUS_401, 'Authorization failed.');
            }

            // Check for endpoint.
            $this->parse_request_endpoint($_SERVER['REQUEST_URI']);

            // Check if method is allowed for selected endpoint.
            $this->parse_request_method($_SERVER['REQUEST_METHOD']);

            // Check for request path.
            $this->parse_request_path($_SERVER['REQUEST_URI']);

            // Check for parameters.
            $this->parse_request_parameters($_SERVER['REQUEST_URI']);

            // Check for payload.
            $this->parse_request_payload();

            // Set request and response to endpoint controller class.
            $this->endpoint->set_request($this->request);
            $this->endpoint->set_response($this->response);

            // Execute request and build response.
            $this->endpoint->execute_request();

        } catch (\Exception $e) {
            // Send exception response.
            $this->response->send_exception($e);
        }

        // Send response.
        $this->endpoint->response->send();
    }

    protected function api_enabled() {
        return (bool)$this->config->apienabled;
    }

    protected function authorize() {

        // Get API key.
        $apikey = $this->request->get_header('HTTP_X_API_KEY');
        if (!$apikey) {
            $this->response->send_error(STATUS_401, 'Authorization required.');
        }

        // Currently just simple check for auth.
        if ($apikey == $this->config->apikey) {
            $this->authorized = true;
        }
    }

    protected function parse_request_headers($requestdata) {

        $headers = [];

        foreach ($requestdata as $key => $value) {
            // HTTP Headers.
            $httpheader = strpos($key, 'HTTP');
            $contentheader = strpos($key, 'CONTENT');

            if ($httpheader === 0) {
                $headers[$key] = $value;
            }

            if ($contentheader === 0) {
                $headers[$key] = $value;
            }
        }

        $this->request->set_headers($headers);
    }

    protected function parse_request_endpoint($url) {
        $param = parse_url($url);
        $urlpath = $param['path'];
        $urlpathparts = explode('/', $urlpath);

        if (isset($urlpathparts[4])) {
            $endpointcandidate = $urlpathparts[4];
        } else {
            // Default endpoint.
            $endpointcandidate = 'test_connection';
        }

        $classname = "local_moopanel\\endpoints\\" . $endpointcandidate;
        if (class_exists($classname)) {
            // Create endpoint controller.
            $this->endpoint = new $classname();
        } else {
            $this->response->send_error(STATUS_400, 'Endpoint not found.');
        }
    }

    protected function parse_request_method($method) {
        $allowedmethods = $this->endpoint->allowed_methods();

        if (!in_array($method, $allowedmethods)) {
            $this->response->send_error(STATUS_405, 'Method not allowed.');
        }
        $this->request->set_method($method);
    }

    protected function parse_request_path($url) {
        $param = parse_url($url);
        $urlpath = $param['path'];

        $pathparts = explode('/', $urlpath);
        $endpointpath = array_slice($pathparts, 4);
        $path = implode('/', $endpointpath);

        $this->request->set_path($path);
    }

    protected function parse_request_parameters($url) {
        // $urlparts = explode('/', $url);
        // $parameters = array_slice($urlparts, 4);

        $param = parse_url($url);

        if (!isset($param['query'])) {
            return;
        }

        $query = $param['query'];
        parse_str($query, $queryparams);

        // $this->request->set_parameters($parameters);
        $this->request->set_parameters($queryparams);
    }

    protected function parse_request_payload() {
        // Get request body.
        $input = json_decode(file_get_contents('php://input'));
        if (isset($input->data)) {
            $this->request->set_payload($input->data);
        }
    }

}
