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
 * Event handler.
 *
 * File         events.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of observers.
$observers = [
        // Course modules events.
        [
                'eventname' => '\core\event\course_module_created',
                'callback'  => '\local_moopanel\observer::update_course_modified_time',
        ],
        [
                'eventname' => '\core\event\course_module_updated',
                'callback'  => '\local_moopanel\observer::update_course_modified_time',
        ],
        [
                'eventname' => '\core\event\course_module_deleted',
                'callback'  => '\local_moopanel\observer::update_course_modified_time',
        ],
        // User graded.
        [
                'eventname' => '\core\event\user_graded',
                'callback'  => '\local_moopanel\observer::update_course_modified_time',
        ],
];
