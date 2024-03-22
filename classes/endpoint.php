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
 * Default endpoint for Moopanel API.
 *
 * File         test_connection.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel;

use stdClass;

class endpoint {

    public int $responsecode;

    public string $responsemsg;

    public stdClass $responsebody;

    public function __construct() {
        $this->responsecode = 404;
        $this->responsemsg = '';
        $this->responsebody = new stdClass();
    }
    public function get_response_code() {
        return $this->responsecode;
    }

    public function get_response_msg() {
        return $this->responsemsg;
    }

    public function get_response() {
        return $this->responsebody;
    }

}
