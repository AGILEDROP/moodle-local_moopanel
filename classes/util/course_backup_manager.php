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
 * Utility class - course backup manager.
 *
 * File         course_backup_manager.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moopanel\util;

use backup;
use backup_controller;
use DateTime;
use Exception;
use restore_controller;
use ZipArchive;
use ZipStream\Option\Archive;

class course_backup_manager {

    private function check_backup_directory($dir) {

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        return true;
    }

    private function generate_filename($courseid) {
        $filename = '';

        $date = new DateTime();
        $filename .= 'course_' . $courseid . '__';
        $filename .= $date->format('Y_m_d_H_i');

        return $filename;
    }

    private function generate_password() {
        return "abcd1234";
    }


    public function create_backup($courseid, $mode) {
        global $CFG;

        $data = [
            'status' => false,
            'message' => '',
        ];

        $destination = $CFG->dataroot . '/moopanel_course_backups/' . $mode . '/';
        $destinationstatus = $this->check_backup_directory($destination);

        if (!$destinationstatus) {
            $data['message'] = 'Backup directory not writable.';
            return $data;
        }

        // Include needle library.
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Backup controller.
        $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, 2);

        $zipfilename = $this->generate_filename($courseid);
        $zipfilename .= '.mbz';
        $zipfilepath = $destination . $zipfilename;
        $password = $this->generate_password();

        $bc->get_plan()->get_setting('filename')->set_value($zipfilename);

        $time1 = new DateTime();

        // Execute backup process.
        $bc->execute_plan();

        $time2 = new DateTime();
        $diff1 = $time2->diff($time1);

        $results = $bc->get_results();
        $file = $results['backup_destination'] ?? false;

        if (!$file) {
            $data['message'] = 'Problem while creating backup.';
            return $data;
        }

        $file->copy_content_to($zipfilepath);

        $time3 = new DateTime();
        $diff2 = $time3->diff($time2);

        $backupsize = $file->get_filesize();

        $file->delete();
        $bc->destroy();

        $time4 = new DateTime();
        $diff3 = $time4->diff($time3);

        /*
        $fileexist = file_exists($zipfilepath);
        $zip = new ZipArchive();
        if (! $zip->open($zipfilepath)) {
            $data['message'] = 'Problem while creating backup zip file.';
            return $data;
        }

        // $zip->addFile($tempfilepath, $tempfilename);
        $zip->setPassword($password);
        $zip->setEncryptionName($zipfilepath, ZipArchive::EM_AES_256);
        $zip->close();
        */

        $time5 = new DateTime();
        $diff4 = $time5->diff($time4);

        if (!file_exists($zipfilepath)) {
            $data['message'] = 'Problem while copying backup zip file.';
            return $data;
        }

        $time6 = new DateTime();
        $diff5 = $time6->diff($time5);

        return [
            'status' => true,
            'message' => 'Backup created successfully.',
            'filename' => $zipfilename,
            'filesize' => $backupsize,
            'password' => $password,
            'diff1' => $diff1->h .'-' . $diff1->i . '-' . $diff1->s,
            'diff2' => $diff2->h .'-' . $diff2->i . '-' . $diff2->s,
            'diff3' => $diff3->h .'-' . $diff3->i . '-' . $diff3->s,
            'diff4' => $diff4->h .'-' . $diff4->i . '-' . $diff4->s,
            'diff5' => $diff5->h .'-' . $diff5->i . '-' . $diff5->s,
        ];
    }

    public function restore_backup($backupfile, $courseid) {
        global $CFG;

        if (!file_exists($backupfile)) {
            return false;
        }

        // Include needle library.
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $backupdir = restore_controller::get_tempdir_name(SITEID, 2);
        $path = make_backup_temp_directory($backupdir);

        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($backupfile, $path);

        $rc = new restore_controller($backupdir, $courseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, 2, backup::TARGET_CURRENT_DELETING);

        // Execute the pre-check to ensure everything is set up correctly.
        if (!$rc->execute_precheck()) {
            return false;
        }

        try {
            $rc->execute_plan();
            $rc->destroy();
        } catch (Exception $e) {
            $msg = $e->getMessage();

            return false;
        }

        return true;
    }
}
