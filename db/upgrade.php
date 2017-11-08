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
 * @package local
 * @subpackage confseed
 * @author Liip <https://www.liip.ch/>
 * @author Didier Raboud <didier.raboud@liip.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Function launched when local_confseed upgrades.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_confseed_upgrade($oldversion) {
    global $CFG, $DB;

    if (!isset($CFG->CONFSEED)) {
        // The $CFG->CONFSEED attribute is not set, local/confseed doesn't do anything.
        return true;
    }

    // Create or update user profile categories.
    if (isset($CFG->CONFSEED->user_info_categories)) {
        $catsortorder = 1; // Start at 2, as the default one has 1.
        foreach ($CFG->CONFSEED->user_info_categories as $id => $newcategory) {
            if (!isset($newcategory->name)) {
                continue;
            }
            // Force it it. The array key is the id.
            $newcategory->id = $id;

            if (!isset($newcategory->sortorder)) {
                // Order them as they come.
                $newcategory->sortorder = $catsortorder++;
            }

            $dbcategory = $DB->get_record('user_info_category', array('id' => $newcategory->id));
            if ($dbcategory) {
                // We'll just override this category.
                $DB->update_record('user_info_category', $newcategory);
            } else {
                $DB->insert_record('user_info_category', $newcategory);
            }
        }
        profile_reorder_categories();
    }

    // Create or update user profile fields.
    if (isset($CFG->CONFSEED->user_info_fields)) {
        $fieldsortorder = 1; // Start at 2, as the default one has 1.
        foreach ($CFG->CONFSEED->user_info_fields as $shortname => $newfield) {
            if (!isset($newfield->name) ||
                !isset($newfield->datatype)) {
                continue;
            }
            // Force it it. The array key is the shortname.
            $newfield->shortname = $shortname;

            if (!isset($newfield->categoryid)) {
                $newfield->categoryid = 1; // Force-put them in the default category.
            }
            if (!isset($newfield->visible)) {
                $newfield->visible = PROFILE_VISIBLE_PRIVATE; // Force-set visibility to 'Visible to user'
            }
            if (!isset($newfield->sortorder)) {
                // Order them as they come.
                $newfield->sortorder = $fieldsortorder++;
            }
            $dbfield = $DB->get_record('user_info_field', array('shortname' => $newfield->shortname));
            if ($dbfield) {
                // We'll just override this field.
                $newfield->id = $dbfield->id;
                $DB->update_record('user_info_field', $newfield);
            } else {
                $DB->insert_record('user_info_field', $newfield);
            }
        }
        profile_reorder_fields();
    }

    // An upgrade_plugin_savepoint call is not needed here as upgradelib.php's upgrade_plugins() will do it for us.
    return true;
}
