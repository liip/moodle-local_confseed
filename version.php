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
 * @package local_confseed
 * @copyright Liip AG <https://www.liip.ch/>
 * @author Didier Raboud <didier.raboud@liip.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

if (file_exists($CFG->dirroot . '/config-seed.php')) {
    include($CFG->dirroot . '/config-seed.php');
    if (isset($CONFSEED) && isset($CONFSEED->version)) {
        $plugin->version = $CONFSEED->version;
    }
}

// Legacy CONFSEED setup.
if (!isset($plugin->version)) {
    if (isset($CFG->CONFSEED) && isset($CFG->CONFSEED->version)) {
        $plugin->version = $CFG->CONFSEED->version;
    } else {
        $plugin->version = '2018043000';
    }
}

// The rolesactive = 1 marks a finished Moodle install.
if ($CFG->rolesactive != 1) {
    // Pretend it's a one-off lower version, so we can install+upgrade in one step.
    $plugin->version = (string)((int)$plugin->version - 1);
}

$plugin->requires  = 2017051502; // Requires Moodle 3.3.
$plugin->component = 'local_confseed';
$plugin->maturity = MATURITY_BETA;
$plugin->release = 0.1;
