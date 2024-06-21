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
 * Adhoc task class - create backups for specified course.
 *
 * File         plugins_install_zip.php
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
use local_moopanel\response;

class backup_course extends adhoc_task {

    public function execute() {

        $response = new response();

        $customdata = $this->get_custom_data();

        $returnurl = $customdata->returnurl;
        $type = $customdata->type;
        $instanceid = $customdata->instanceid;
        $courseid = $customdata->courseid;

        $msg = 'Course Backup for course id ' . $courseid . 'created.';

        // ToDo create real backup.
        mtrace($msg);

        $response->add_body_key('courseid', $courseid);
        $response->add_body_key('link', 'https://test.si/123-2024-06-21.zip');
        $response->add_body_key('password', 'abcdefgh12345678');
        $response->add_body_key('status', true);

        // Send response to Moo-panel app.
        $send = $response->post_to_url($returnurl);

        // $response->send_to_email('test@test.com', 'Course backup', $response->body);
    }
}