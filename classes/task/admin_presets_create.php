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
 * Adhoc task class - create admin presets.
 *
 * File         admin_presets_create.php
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
use local_moopanel\util\admin_presets_manager;

class admin_presets_create extends adhoc_task {

    public function get_name() {
        return get_string('task:adminpresetscreate', 'local_moopanel');
    }

    public function execute() {

        $manager = new admin_presets_manager();

        $response = new response();
        $response->add_header('X-API-KEY', get_config('local_moopanel', 'apikey'));
        $response->add_header('Content-Type', 'application/json');
        $response->set_format('xml');

        $preset = $manager->presets_create();
        $customdata = $this->get_custom_data();

        $url = $customdata->responseurl;

        if (!$preset) {
            // Send error to moopanel.
        }

        $xml = $manager->preset_get_xml($preset);

        $response->set_body($xml);

        $send = $response->post_to_url($url);

        mtrace($customdata->responseurl);
        }
}
