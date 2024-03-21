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
 * Endpoint of Moopanel API.
 *
 * File         index.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require('classes/moopanel_api.php');
require('classes/error_handler.php');

set_exception_handler(\local_moopanel\error_handler::throw_error());



$server = new \local_moopanel\moopanel_api();

$apikey = false;
$ip = false;

if (isset($_SERVER['HTTP_API_KEY'])) {
    $apikey = $_SERVER['HTTP_API_KEY'];
}

$ip = $_SERVER['REMOTE_ADDR'];

$server->run($apikey, $ip);
die();
