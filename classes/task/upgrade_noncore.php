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
 * Adhoc task class - upgrade noncore.
 *
 * File         upgrade_noncore.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\task;

use core\task\adhoc_task;
use core_user;

class upgrade_noncore extends adhoc_task {

    public function get_name() {
        return get_string('task:upgradenoncore', 'local_moopanel');
    }

    public function execute() {
        global $CFG, $USER;

        $USER = core_user::get_user(2);
        $id = $USER->id;

        // Include needle library.
        require_once($CFG->dirroot.'/lib/adminlib.php');
        require_once($CFG->dirroot.'/lib/pagelib.php');
        require_once($CFG->dirroot.'/lib/moodlelib.php');
        require_once($CFG->dirroot.'/lib/upgradelib.php');

        upgrade_noncore(true);
    }

}