<?php
$a = 2;
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
 * This script creates config.php file and prepares database.
 *
 * This script is not intended for beginners!
 * Potential problems:
 * - su to apache account or sudo before execution
 * - not compatible with Windows platform
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Force OPcache reset if used, we do not want any stale caches
// when detecting if upgrade necessary or when running upgrade.
if (function_exists('opcache_reset') and !isset($_SERVER['REMOTE_ADDR'])) {
    opcache_reset();
}

define('CLI_SCRIPT', true);

echo 'execute - ';

require('config.php');
require_once($CFG->libdir.'/adminlib.php');       // various admin-only functions
require_once($CFG->libdir.'/upgradelib.php');     // general upgrade/install related functions
require_once($CFG->libdir.'/environmentlib.php');

$options = [];
$interactive = false;

if (empty($CFG->version)) {
    //cli_error(get_string('missingconfigversion', 'debug'));
    echo 'unknown version';
}

require("$CFG->dirroot/version.php");       // defines $version, $release, $branch and $maturity
$CFG->target_release = $release;            // used during installation and upgrades

if ($version < $CFG->version) {
    //cli_error(get_string('downgradedcore', 'error'));
    echo 'Can not downgrade core.\n';
}

$oldversion = "$CFG->release ($CFG->version)";
$newversion = "$release ($version)";


if (!moodle_needs_upgrading()) {
    echo 'no updates needed\n';
    //cli_error(get_string('cliupgradenoneed', 'core_admin', $newversion), 0);
}

/*
// Test environment first.
list($envstatus, $environment_results) = check_moodle_environment(normalize_version($release), ENV_SELECT_RELEASE);
if (!$envstatus) {
    $errors = environment_get_errors($environment_results);
    //cli_heading(get_string('environment', 'admin'));
    foreach ($errors as $error) {
        list($info, $report) = $error;
        echo "!! $info !!\n$report\n\n";
    }
    exit(1);
}
*/
// Make sure there are no files left over from previous versions.
if (upgrade_stale_php_files_present()) {
    //cli_problem(get_string('upgradestalefiles', 'admin'));
    echo 'old moodle files found.\n';

    // Stale file info contains HTML elements which aren't suitable for CLI.
    echo 'The Moodle update process has been paused because PHP scripts from at least two major versions of Moodle have been detected in the Moodle directory.';
}

// Test plugin dependencies.
$failed = [];
if (!core_plugin_manager::instance()->all_plugins_ok($version, $failed, $CFG->branch)) {
    $msg = get_string('pluginscheckfailed', 'admin', array('pluginslist' => implode(', ', array_unique($failed))));
    echo $msg;
    $msg = get_string('pluginschecktodo', 'admin');
    echo $msg;
}

$a = new stdClass();
$a->oldversion = $oldversion;
$a->newversion = $newversion;

if ($interactive) {
    echo 'database checking';
}

if ($version > $CFG->version) {
    // We purge all of MUC's caches here.
    // Caches are disabled for upgrade by CACHE_DISABLE_ALL so we must set the first arg to true.
    // This ensures a real config object is loaded and the stores will be purged.
    // This is the only way we can purge custom caches such as memcache or APC.
    // Note: all other calls to caches will still used the disabled API.
    cache_helper::purge_all(true);
    upgrade_core($version, true);
}
set_config('release', $release);
set_config('branch', $branch);

// unconditionally upgrade
//upgrade_noncore(true);

// log in as admin - we need doanything permission when applying defaults
\core\session\manager::set_user(get_admin());

// Apply default settings and output those that have changed.
$settingsoutput = admin_apply_default_settings(null, false);

foreach ($settingsoutput as $setting => $value) {

    if ($options['verbose-settings']) {
        $stringvlaues = array(
                'name' => $setting,
                'defaultsetting' => var_export($value, true) // Expand objects.
        );
        echo get_string('cliupgradedefaultverbose', 'admin', $stringvlaues) . PHP_EOL;

    } else {
        echo get_string('cliupgradedefault', 'admin', $setting) . PHP_EOL;

    }
}

// This needs to happen at the end to ensure it occurs after all caches
// have been purged for the last time.
// This will build a cached version of the current theme for the user
// to immediately start browsing the site.
upgrade_themes();

echo get_string('cliupgradefinished', 'admin', $a)."\n";
exit(0); // 0 means success
