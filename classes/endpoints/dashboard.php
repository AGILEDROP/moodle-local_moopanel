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
 * Moopanel API endpoint Dashboard.
 *
 * File         dashboard.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\endpoints;

use local_moopanel\endpoint;
use local_moopanel\endpoint_interface;

class dashboard extends endpoint implements endpoint_interface {

    public function allowed_methods() {
        return ['GET'];
    }

    public function execute_request() {
        global $CFG, $SITE, $THEME, $PAGE;

        $PAGE->set_context(\context_system::instance());
        $renderer = $PAGE->get_renderer('core');

        $logourl = $renderer->get_logo_url(300, 300);
        $logocompacturl = $renderer->get_compact_logo_url(300, 300);

        $logo = ($logourl) ? $logourl->raw_out() : '';
        $logocompact = ($logocompacturl) ? $logocompacturl->raw_out() : '';

        $this->response->add_body_key('url', $CFG->wwwroot);
        $this->response->add_body_key('site_fullname', $SITE->fullname ?? '');
        $this->response->add_body_key('site_shortname', $SITE->shortname ?? '');
        $this->response->add_body_key('logo', $logo);
        $this->response->add_body_key('logocompact', $logocompact);
        $this->response->add_body_key('theme', $CFG->theme ?? '');
        $this->response->add_body_key('moodle_version', $CFG->release ?? '');
    }

}
