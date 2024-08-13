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
 * Adhoc task class - update Moodle core.
 *
 * File         core_update.php
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
use Exception;
use local_moopanel\response;
use local_moopanel\util\plugin_manager;
use ZipArchive;

class core_update extends adhoc_task {

    private $filescount = 0;
    private $errors;

    public function get_name() {
        return get_string('task:coreupdate', 'local_moopanel');
    }

    public function execute() {
        global $CFG;

        $this->errors = [];

        $id = $this->get_id();

        $customdata = $this->get_custom_data();

        $response = new response();
        $response->add_header('X-API-KEY', get_config('local_moopanel', 'apikey'));

        $response->add_body_key('status', false);
        $response->add_body_key('moodle_job_id', (int)$id);
        $response->add_body_key('user_id', $customdata->userid);
        $response->add_body_key('update_id', $customdata->updateid);

        $tempdir = make_temp_directory('moopanel_core_update');

        if (!$tempdir) {
            $response->add_body_key('message', 'Problem creating temporary directory.');
            $response->post_to_url($customdata->returnurl);
            $response->send_to_email('uros.virag@agiledrop.com', 'Core update');
            return;
        }

        chmod($tempdir, 0777);
        $pluginmanager = new plugin_manager();
        $downloaded = $pluginmanager->download_zip_file($customdata->download, $tempdir);
        // $downloaded = true; // Todo remove this and download file.
        if (!$downloaded) {
            remove_dir($tempdir);
            $response->add_body_key('message', 'Can not download zip file.');
            $response->post_to_url($customdata->returnurl);
            $response->send_to_email('uros.virag@agiledrop.com', 'Core update');
            return;
        }

        mkdir($tempdir . '/extracted');
        $zip = new ZipArchive();
        $zip->open($tempdir . '/plugin.zip');
        $zip->extractTo($tempdir . '/extracted');

        if (!file_exists($tempdir . '/extracted/moodle')) {
            remove_dir($tempdir);
            $response->add_body_key('message', 'Can not extract downloaded zip file.');
            $response->post_to_url($customdata->returnurl);
            $response->send_to_email('uros.virag@agiledrop.com', 'Core update');
            return;
        }

        if(!file_exists($tempdir)) {
            remove_dir($tempdir);
        }

        set_config('maintenance_enabled', 1);

        try {
            $source = $tempdir . '/extracted/moodle';
            $destination = $CFG->dirroot;

            $this->copy_files($source, $destination);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }


        $errors = $this->errors;

        $output = shell_exec('../../local/moopanel/script/core_upgrade.sh');

        remove_dir($tempdir);

        set_config('maintenance_enabled', 0);

        if ($CFG->version == $customdata->version) {
            $response->add_body_key('status', true);
        }

        $response->send_to_email('uros.virag@agiledrop.com', 'Core update');
    }

    /**
     * Copy a directory and its contents to another location.
     *
     * @param string $src Source directory.
     * @param string $dst Destination directory.
     */
    function copy_files($src,$dst) {
        $dir = opendir($src);
        if (is_dir($src)) {
            if (!file_exists($dst)) {
                $created = mkdir($dst, 0777, true);
                if (!$created) {
                    $this->errors[] = 'Cant create directory ' . $dst;
                }
            }
        }

        while(( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->copy_files($src .'/'. $file, $dst .'/'. $file);
                }
                else {
                    $copied = copy($src .'/'. $file,$dst .'/'. $file);
                    if (!$copied) {
                        $this->errors[] = "$src ./$file -----> $dst";
                    } else {
                        chmod($dst . '/' . $file, 755);
                        $this->filescount++;
                    }
                }
            }
        }
        closedir($dir);
    }
}
