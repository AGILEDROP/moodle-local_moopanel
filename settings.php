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
 * General global plugin settings
 *
 * File         settings.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('moodle_internal not defined');

$settings = new admin_settingpage('moopanel', get_string('pluginname', 'local_moopanel'));
$ADMIN->add('localplugins', $settings);

if ($hassiteconfig) {

    // Enable / disable API.
    $settings->add(new admin_setting_configcheckbox(
            'local_moopanel/apienabled',
            get_string('label:apienabled', 'local_moopanel'),
            get_string('label:apienabled_help', 'local_moopanel'),
           ''
    ));

    // API key.
    $settings->add(new admin_setting_configtext(
            'local_moopanel/apikey',
            get_string('label:apikey', 'local_moopanel'),
            get_string('label:apikey_help', 'local_moopanel'),
            ''
    ));

    // IP restriction.
    $settings->add(new admin_setting_configtextarea(
            'local_moopanel/iprestrict',
            get_string('label:iprestrict', 'local_moopanel'),
            get_string('label:iprestrict_help', 'local_moopanel'),
            ''
    ));
}
