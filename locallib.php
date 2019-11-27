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
 * Configuration Seeder helper functions
 *
 * @package local_confseed
 * @copyright Liip AG <https://www.liip.ch/>
 * @author Didier Raboud <didier.raboud@liip.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upload a local file to an admin configuration settings entry
 *
 * @param string $plugin core_admin
 * @param string $key  logocompact
 * @param string $filepath  path to the file to be uploaded, relative to $CFG->dirroot.
 */
function local_confseed_admin_settings_set_file($plugin, $key, $filepath) {
    global $CFG;

    $fullfilepath = realpath($CFG->dirroot . '/'. $filepath);
    if (!is_file($fullfilepath) || !is_readable($fullfilepath)) {
        return false;
    }
    $filename = basename($fullfilepath);

    // Filearea handling.
    $fs = get_file_storage();
    $contextid = \context_system::instance()->id; // System context.
    $itemid = 0; // Only supports single-file settings.

    // Remove all concerned files before adding ours.
    $files = $fs->get_area_files($contextid, $plugin, $key, $itemid);
    foreach ($files as $f) {
        $f->delete();
    }

    $newfilemeta = [
        'contextid' => $contextid,
        'component' => $plugin,
        'filearea' => $key,
        'itemid' => $itemid,
        'filepath' => '/',
        'filename' => $filename
    ];

    $fs->create_file_from_pathname($newfilemeta, $fullfilepath);
    // Also set the configuration.
    set_config($key, '/' . $filename, $plugin);
}