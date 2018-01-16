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
 * Configuration Seeder version
 *
 * @package local
 * @subpackage confseed
 * @author Liip <https://www.liip.ch/>
 * @author Didier Raboud <didier.raboud@liip.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

if (file_exists($CFG->dirroot . '/config-seed.php')) {
    require_once($CFG->dirroot . '/config-seed.php');
    if (isset($CONFSEED) && isset($CCONFSEED->version)) {
        $plugin->version = $CONFSEED->version;
    }
}

// Legacy CONFSEED setup.
if (!isset($plugin->version) && isset($CFG->CONFSEED) && isset($CFG->CONFSEED->version)) {
    $plugin->version = $CFG->CONFSEED->version;
} else {
    $plugin->version = '2017121900';
}

$plugin->requires  = 2017051502; // Requires Moodle 3.3.
$plugin->component = 'local_confseed';
$plugin->maturity = MATURITY_BETA;
$plugin->release = 0.1;
