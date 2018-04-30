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
 * Configuration Seeder upgrade script (run when version.php's version changes)
 *
 * @package local_confseed
 * @copyright Liip AG <https://www.liip.ch/>
 * @author Didier Raboud <didier.raboud@liip.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function launched when local_confseed installs.
 * @return bool result
 */
function xmldb_local_confseed_install() {
    global $CFG, $DB;

    // We always want to run the upgrade.
    require_once($CFG->dirroot . '/local/confseed/db/upgrade.php');
    return xmldb_local_confseed_upgrade(0);
}
