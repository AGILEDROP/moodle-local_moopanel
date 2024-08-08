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

class course_backup_manager {

    public function course_need_backup($courseid) {
        global $DB;

        $data = $DB->get_record('moopanel_course_backups', ['course_id' => $courseid]);

        if (!$data) {
            return true;
        }

        if ($data->last_modified > $data->last_backup) {
            return true;
        }

        return false;
    }

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

    public function generate_password($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ :;?!|/';
        $characterslength = strlen($characters);
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $characterslength - 1)];
        }
        return $password;
    }

    public function create_backup($courseid, $mode) {
        global $CFG, $DB;

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

        $filename = $this->generate_filename($courseid);
        $tmpfilename = $filename . '.mbz';
        $zipfilename = $filename . '.zip';

        $password = $this->generate_password();

        $bc->get_plan()->get_setting('filename')->set_value($tmpfilename);

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

        $file->copy_content_to($destination . $tmpfilename);

        $time3 = new DateTime();
        $diff2 = $time3->diff($time2);

        $backupsize = $file->get_filesize();

        $file->delete();
        $bc->destroy();

        $time4 = new DateTime();
        $diff3 = $time4->diff($time3);

        $zip = new ZipArchive();
        if (!$zip->open($destination . $zipfilename, ZipArchive::CREATE)) {
            $data['message'] = 'Problem while creating backup zip file.';
            return $data;
        }

        // $zip->open($destination . $zipfilename);
        $zip->addFile($destination . $tmpfilename, $tmpfilename);
        $zip->setPassword($password);

        $a = 2;
        $zip->setEncryptionName($tmpfilename, ZipArchive::EM_AES_256, $password);
        $zip->close();

        $time5 = new DateTime();
        $diff4 = $time5->diff($time4);

        if (!file_exists($destination . $zipfilename)) {
            $data['message'] = 'Problem while copying backup zip file.';
            return $data;
        }

        unlink($destination . $tmpfilename);

        $time6 = new DateTime();
        $diff5 = $time6->diff($time5);

        // Update backup data in moopanel_course_backups table for automated backups.
        if ($mode == 'auto') {
            $lastbackup = new DateTime();
            $backupreport = new \stdClass();
            $backupreport->course_id = $courseid;
            $backupreport->last_backup = $lastbackup->getTimestamp();
            $backupreport->last_modified = 0;

            $data = $DB->get_record('moopanel_course_backups', ['course_id' => $courseid]);
            if ($data) {
                $backupreport->id = $data->id;
                $DB->update_record('moopanel_course_backups', $backupreport);
            } else {
                $DB->insert_record('moopanel_course_backups', $backupreport);
            }
        }

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

    public function restore_backup($backupfile, $courseid, $password) {
        global $CFG;

        $dir = $CFG->dataroot . '/moopanel_course_backups/restore/';

        if (!file_exists($backupfile)) {
            return false;
        }

        // Include needle library.
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $backupdir = restore_controller::get_tempdir_name(SITEID, 2);
        $path = make_backup_temp_directory($backupdir);

        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($backupfile, $path);

        $rc = new restore_controller($backupdir, $courseid, backup::INTERACTIVE_NO,
                backup::MODE_GENERAL, 2, backup::TARGET_CURRENT_DELETING);

        // Execute the pre-check to ensure everything is set up correctly.
        if (!$rc->execute_precheck()) {
            return false;
        }

        try {
            $rc->execute_plan();
            $rc->destroy();
            unlink($backupfile);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            unlink($backupfile);
            return false;
        }

        return true;
    }

    public function unzip_local_file($backupfile, $password) {
        global $CFG;

        if (!file_exists($CFG->dataroot . '/moopanel_course_backups/' . $backupfile)) {
            return false;
        }

        $dir = $CFG->dataroot . '/moopanel_course_backups/restore/';

        $zip = new ZipArchive();

        if ($zip->open($CFG->dataroot . '/moopanel_course_backups/' .$backupfile)) {
            $zip->setPassword($password);
            $zip->extractTo($dir);
            $file = $zip->getNameIndex(0);
            $zip->close();
        }

        if (!file_exists($dir . $file)) {
            return false;
        }

        return $dir . $file;
    }

    private function file_size_convert($bytes) {
        $bytes = floatval($bytes);
        $arbytes = [
                0 => [
                        "UNIT" => "TB",
                        "VALUE" => pow(1024, 4),
                ],
                1 => [
                        "UNIT" => "GB",
                        "VALUE" => pow(1024, 3),
                ],
                2 => [
                        "UNIT" => "MB",
                        "VALUE" => pow(1024, 2),
                ],
                3 => [
                        "UNIT" => "KB",
                        "VALUE" => 1024,
                ],
                4 => [
                        "UNIT" => "B",
                        "VALUE" => 1,
                ],
        ];

        foreach ($arbytes as $aritem) {
            if ($bytes >= $aritem["VALUE"]) {
                $result = $bytes / $aritem["VALUE"];
                $result = str_replace(".", ",", strval(round($result, 2)));
                $result .= " " . $aritem["UNIT"];
                break;
            }
        }
        return $result;
    }

    public function available_backups($courseid) {
        $data = [];

        for ($i = 1; $i < 6; $i++) {
            $date = new DateTime();
            $now = $date->getTimestamp();

            $randtimestamp = rand($now - 1000000, $now);
            $randsize = rand(999, 9999999999);

            $date->setTimestamp($randtimestamp);

            $row = [
                    'num' => $i,
                    'backup' => $date->format('Y - m -d  [H:i]'),
                    'filesize' => $this->file_size_convert($randsize),
            ];

            $data['auto'][] = $row;
            $data['manual'][] = $row;
        }

        return $data;
    }

    public function get_existing_backup_delete_task_id($existingtasks, $backupid) {

        foreach ($existingtasks as $existingtask) {
            $a = 2;
            $customdata = $existingtask->get_custom_data();
            $backup = $customdata->backup;

            $id = $backup->backup_result_id;

            if ($id == $backupid) {
                return $existingtask->get_id();
            }
        }

        return null;
    }
}
