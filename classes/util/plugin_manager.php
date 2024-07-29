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
 * Utility class - plugin manager.
 *
 * File         plugins.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\util;

use moodle_url;
use tool_installaddon_installer;

/**
 * Utility class definition - plugin manager.
 */
class plugin_manager {

    /** @var \core_plugin_manager $pluginman Core plugin manager instance. */
    private $pluginman;

    public function __construct() {
        $this->pluginman = \core_plugin_manager::instance();
    }

    public function install_zip($zipurl) {
        global $CFG, $USER;

        $report = [];
        $report['component'] = null;
        $report['status'] = false;
        $report['error'] = null;

        // ToDo login user.

        $installer = tool_installaddon_installer::instance();

        $storage = $installer->make_installfromzip_storage();

        $downloaded = $this->download_zip_file($zipurl, $storage);

        if (!$downloaded) {
            $delete = remove_dir($storage);
            $report['error'] = 'Cannot download zip file';
            return $report;
        }

        // Check for downloaded file.
        $ziprootdir = $this->pluginman->get_plugin_zip_root_dir($storage.'/plugin.zip');
        if (empty($ziprootdir)) {
            $delete = remove_dir($storage);
            $report['error'] = 'Invalid zip file.';
            return $report;
        }

        // Get plugin component.
        $component = $installer->detect_plugin_component($storage.'/plugin.zip');

        if (empty($component)) {
            $delete = remove_dir($storage);
            $report['error'] = 'Invalid component name.';
            return $report;
        }

        $report['component'] = $component;

        $installable = [];
        $installdata = new \stdClass();
        $installdata->component = $component;
        $installdata->zipfilepath = $storage . '/plugin.zip';

        $installable[] = $installdata;

        // Include needle library.
        require_once($CFG->dirroot.'/lib/pagelib.php');
        require_once($CFG->dirroot.'/lib/moodlelib.php');
        require_once($CFG->dirroot.'/lib/upgradelib.php');

        $installed = $this->pluginman->install_plugins($installable, true, true);
        $report['status'] = $installed;

        // Remove downloaded file.
        $delete = remove_dir($storage);

        return $report;
    }

    public function download_zip_file($zipurl, $storage) {
        global $CFG, $USER;

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, $zipurl);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handler, CURLOPT_TIMEOUT, 100);

        $response = curl_exec ($handler);

        $statuscode = curl_getinfo($handler, CURLINFO_HTTP_CODE) ?? false;

        if (!$response) {
            return false;
        }

        if ($statuscode != 200) {
            return false;
        }

        curl_close ($handler);

        $destination = $storage . '/plugin.zip';
        $zipfile = fopen($destination, "w+");
        fputs($zipfile, $response);
        fclose($zipfile);

        return true;
    }

    public function upgrade_noncore($url) {
        global $CFG;

        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_POST, 0);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($handler);

        $statuscode = curl_getinfo($handler, CURLINFO_HTTP_CODE);

        curl_close($handler);

        if (!$response) {
            return false;
        }

        if ($statuscode != 200) {
            return false;
        }

        return true;
    }

}
