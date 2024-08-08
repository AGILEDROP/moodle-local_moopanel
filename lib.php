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
use core\task\manager;
use local_moopanel\task\upgrade_noncore;
use local_moopanel\util\course_backup_manager;

/**
 * Library functions and hooks.
 *
 * File         lib.php
 * Encoding     UTF-8
 *
 * @package     local_moopanel
 *
 * @copyright   Agiledrop, 2024
 * @author      Agiledrop 2024 <hello@agiledrop.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function local_moopanel_before_footer() {
    global $CFG, $DB;

    // ToDo - Quick test some functionality. Will be removed.

    $manager = new course_backup_manager();

    ################

    // $manager->unzip_local_file('/moopanel_course_backups/manual/course_2420__2024_08_05_14_21.zip', 'abcd1234');
    //$manager->restore_backup('/var/www/html/moodledata/moopanel_course_backups/manual/course_2420__2024_08_05_14_21.zip', 2420, 'abcd1234');

    ################

    $pluginmanager = new \local_moopanel\util\plugin_manager();

    //$storage = make_temp_directory('moopanel_core_update');
    //$pluginmanager->download_zip_file('https://download.moodle.org/download.php/direct/stable403/moodle-latest-403.zip', $storage);

    $a = 4;

    if (isset($_GET['bash'])) {

        $script = 'local/moopanel/bash/test.sh';
        $exist = file_exists($script);

        if ($exist) {
            chmod($script, 0777);
            $output = shell_exec('local/moopanel/bash/test.sh');
        } else {
            $output = 'No script found';
        }

        echo "----------------------<br>";
        echo "<pre>";
        echo $output;
        echo "</pre>";
        echo "----------------------<br>";

        $b = 44;
    }

    if (isset($_GET['task'])) {
        $noncoreupgrade = new upgrade_noncore();

        // Set run task ASAP.
        $noncoreupgrade->set_next_run_time(time() - 1);
        manager::queue_adhoc_task($noncoreupgrade, true);

        $a = 2;
    }

    if (isset($_GET['empty_table'])) {
        $table = $_GET['empty_table'];
        $dbman = $DB->get_manager();

        $exist = $dbman->table_exists($table);

        if ($exist) {
            $DB->execute("TRUNCATE TABLE {".$table."}");
        }
    }

    $rand1 = $manager->generate_password();
    $rand2 = $manager->generate_password();
    $rand3 = $manager->generate_password();
    $rand4 = $manager->generate_password();
    $rand5 = $manager->generate_password();

    $a = 2;
}

/**
 * Extend the course navigation with an "Moopanel course backups" link which redirects to a list of all available course backups.
 *
 * @param settings_navigation $navigation The settings navigation object
 * @param stdClass $course The course
 * @param stdclass $context Course context
 * @return void
 */
function local_moopanel_extend_navigation_course($navigation, $course, $context): void {
    if (has_capability('local/moopanel:coursebackupsview', $context)) {
        $url = new moodle_url('/local/moopanel/pages/course_backups.php', ['id' => $course->id]);
        $settingsnode = navigation_node::create('Moopanel - course backups', $url, navigation_node::TYPE_SETTING,
                null, 'moopanelcoursebackups', new pix_icon('i/settings', ''));
        $navigation->add_node($settingsnode);
    }
}
