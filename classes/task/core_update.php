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
use function Symfony\Component\Translation\t;

class core_update extends adhoc_task {

    public function get_name() {
        return get_string('task:coreupdate', 'local_moopanel');
    }

    public function execute() {
        global $CFG;

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
            $response->send_to_email('test@test.com', 'Core update');
            return;
        }

        chmod($tempdir, 0777);
        $pluginmanager = new plugin_manager();
        $downloaded = $pluginmanager->download_zip_file($customdata->download, $tempdir);
        if (!$downloaded) {
            remove_dir($tempdir);
            $response->add_body_key('message', 'Can not download zip file.');
            $response->post_to_url($customdata->returnurl);
            $response->send_to_email('test@test.com', 'Core update');
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
            $response->send_to_email('test@test.com', 'Core update');
            return;
        }

        if(!file_exists($tempdir)) {
            remove_dir($tempdir);
        }


        /*
        set_config('maintenance_enabled', 1);

        // mkdir($tempdir . '/copied');

        try {
            $source = $tempdir . '/extracted/moodle';
            $destination = $CFG->dirroot;

            $this->recurse_move($source, $destination);
            echo "Folder and files copied successfully.\n";
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }


        // Include needle library.
        require_once($CFG->dirroot.'/lib/adminlib.php');
        require_once($CFG->dirroot.'/lib/pagelib.php');
        require_once($CFG->dirroot.'/lib/moodlelib.php');
        require_once($CFG->dirroot.'/lib/upgradelib.php');

        upgrade_core($customdata->version, true);

        remove_dir($tempdir);

        set_config('maintenance_enabled', 0);
*/
        $response->send_to_email('test@test.com', 'Core update');

    }

    /**
     * Copy a directory and its contents to another location.
     *
     * @param string $src Source directory.
     * @param string $dst Destination directory.
     */
    function recurse_move($src, $dst) {
        if (!is_dir($src)) {
            throw new Exception("Source directory does not exist: $src");
        }

        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $srcFile = $src . '/' . $file;
                $dstFile = $dst . '/' . $file;

                if (is_dir($srcFile)) {
                    $this->recurse_move($srcFile, $dstFile);
                    rmdir($srcFile); // Remove the source directory after moving its contents
                } else {
                    if (file_exists($dstFile)) {
                        unlink($dstFile); // Delete the existing file
                    }
                    if (!rename($srcFile, $dstFile)) {
                        throw new Exception("Failed to move $srcFile to $dstFile");
                    }
                }
            }
        }
        closedir($dir);
    }
}
