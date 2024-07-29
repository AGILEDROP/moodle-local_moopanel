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
 * Log request and response messages.
 *
 * File         logger.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel;

use DateTime;
use stdClass;

class logger {

    public function log($type, $endpoint, $method, $parameters, $statuscode, $body) {
        global $DB;

        $now = new DateTime();
        $log = new stdClass();

        $log->timestamp = $now->getTimestamp();
        $log->type = $type;
        $log->endpoint = $endpoint;
        $log->method = $method;
        $log->parameters = $parameters;
        $log->statuscode = $statuscode;
        $log->body = $body;

        $DB->insert_record('moopanel_logs', $log);
    }
}