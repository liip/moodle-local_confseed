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

require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/lib/datalib.php');
require_once($CFG->dirroot . '/lib/filterlib.php');
require_once($CFG->dirroot . '/lib/outputlib.php');
require_once($CFG->dirroot . '/local/confseed/locallib.php');

/**
 * Function launched when local_confseed upgrades.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_confseed_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if (file_exists($CFG->dirroot . '/config-seed.php')) {
        include($CFG->dirroot . '/config-seed.php');
    }
    if (!isset($CONFSEED)) {
        // The CONFSEED attribute is not set, local/confseed doesn't do anything.
        return true;
    }

    // Start by uninstalling plugins, only if moodle is installed.
    if ($dbman->table_exists('config_plugins') and isset($CONFSEED->uninstall_plugins)) {
        $pre27themes = [
           'afterburner',
           'anomaly',
           'arialist',
           'binarius',
           'boxxie',
           'brick',
           'formal_white',
           'formfactor',
           'fusion',
           'leatherbound',
           'magazine',
           'nimble',
           'nonzero',
           'overlay',
           'serenity',
           'sky_high',
           'splash',
           'standard',
           'standardold',
        ];

        array_walk($pre27themes, function(&$t) { $t = 'theme_' . $t; });
        $CONFSEED->uninstall_plugins = array_merge($CONFSEED->uninstall_plugins, $pre27themes);

        $pluginman = core_plugin_manager::instance();
        $progress = new progress_trace_buffer(new text_progress_trace(), false);

        foreach ($CONFSEED->uninstall_plugins as $pluginname) {
            $pluginfo = $pluginman->get_plugin_info($pluginname);
            if (!is_null($pluginfo)) {
                $pluginman->uninstall_plugin($pluginfo->component, $progress);
            }
        }
        $progress->finished();
    }

    // Holds the codename-to-ID map.
    $categorycodemap = array();

    // Create or update user profile categories.
    if (isset($CONFSEED->user_info_categories)) {
        $catsortorder = 1; // Start at 2, as the default one has 1.
        foreach ($CONFSEED->user_info_categories as $codename => $newcategory) {
            if (!isset($newcategory->id) ||
                !isset($newcategory->name)) {
                continue;
            };
            if (!isset($newcategory->sortorder)) {
                // Order them as they come.
                $newcategory->sortorder = $catsortorder++;
            }

            $dbcategory = $DB->get_record('user_info_category', array('id' => $newcategory->id));
            if ($dbcategory) {
                // We'll just override this category.
                $DB->update_record('user_info_category', $newcategory);
                $categorycodemap[$codename] = $newcategory->id;
            } else {
                $recordid = $DB->insert_record('user_info_category', $newcategory);
                $categorycodemap[$codename] = $recordid;
            }
        }
        profile_reorder_categories();
    }

    // Create or update user profile fields.
    if (isset($CONFSEED->user_info_fields)) {
        $fieldsortorder = 1; // Start at 2, as the default one has 1.
        foreach ($CONFSEED->user_info_fields as $shortname => $newfield) {
            if (!isset($newfield->name) ||
                !isset($newfield->datatype)) {
                continue;
            }
            // Force it it. The array key is the shortname.
            $newfield->shortname = $shortname;

            // Allow using a codename as ID, for coherence.
            if (isset($newfield->category) && array_key_exists($newfield->category, $categorycodemap)) {
                $newfield->categoryid = $categorycodemap[$newfield->category];
            } else if (!isset($newfield->categoryid)) {
                // Otherwise force-put them in the default category.
                $newfield->categoryid = 1;
            }
            if (!isset($newfield->visible)) {
                $newfield->visible = PROFILE_VISIBLE_PRIVATE; // Force-set visibility to 'Visible to user'.
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

    // Make sure certain modules (activities) are made _hidden_ or _shown_.
    $updatedcoursemodules = false;
    if (isset($CONFSEED->mod_show)) {
        foreach ($CONFSEED->mod_show as $modname) {
            if ($module = $DB->get_record("modules", array("name" => $modname))) {
                $DB->set_field("modules", "visible", "1", array("id" => $module->id));
                // Get the previous saved visible state for the course module.
                $DB->set_field('course_modules', 'visible', '1',
                    array('visibleold' => 1, 'module' => $module->id)
                );
                // Increment course.cacherev for courses where we just made something
                // visible.
                // This will force cache rebuilding on the next request.
                increment_revision_number('course', 'cacherev',
                        "id IN (SELECT DISTINCT course
                                        FROM {course_modules}
                                       WHERE visible=1 AND module=?)",
                        array($module->id));

                $updatedcoursemodules = true;
            }
        }
    }
    if (isset($CONFSEED->mod_hide)) {
        foreach ($CONFSEED->mod_hide as $modname) {
            if ($module = $DB->get_record("modules", array("name" => $modname))) {
                $DB->set_field("modules", "visible", "0", array("id" => $module->id));
                // Remember the visibility status in visibleold
                // and hide...
                $sql = "UPDATE {course_modules}
                           SET visibleold = visible, visible = 0
                         WHERE module = ?";
                $DB->execute($sql, array($module->id));
                // Increment course.cacherev for courses where we just made something invisible.
                // This will force cache rebuilding on the next request.
                increment_revision_number('course', 'cacherev',
                        "id IN (SELECT DISTINCT course
                                        FROM {course_modules}
                                       WHERE visibleold = 1 AND module=?)",
                        array($module->id));
                $updatedcoursemodules = true;
            }
        }
    }
    if ($updatedcoursemodules) {
        core_plugin_manager::reset_caches();
    }

    // Make sure certain auth plugins are _disabled_ or _enabled_.
    // Do not touch those that aren't in either.
    $auths = explode(',', get_config('core', 'auth'));
    if (isset($CONFSEED->auth_enable)) {
        $auths = array_merge($auths, (array) $CONFSEED->auth_enable);
    }
    if (isset($CONFSEED->auth_disable)) {
        $auths = array_diff($auths, (array) $CONFSEED->auth_disable);
    }
    set_config('auth', implode(',', $auths));

    // Make sure certain enrol plugins are _disabled_ or _enabled_.
    // Do not touch those that aren't in either.
    $enrols = explode(',', get_config('core', 'enrol_plugins_enabled'));
    if (isset($CONFSEED->enrol_enable)) {
        $enrols = array_merge($enrols, (array) $CONFSEED->enrol_enable);
    }
    if (isset($CONFSEED->enrol_disable)) {
        $enrols = array_diff($enrols, (array) $CONFSEED->enrol_disable);
    }
    set_config('enrol_plugins_enabled', implode(',', $enrols));

    // Make sure certain webservices protocols are _disabled_ or _enabled_.
    // Do not touch those that aren't in either.
    $wsprotocols = explode(',', get_config('core', 'webserviceprotocols'));
    if (isset($CONFSEED->wsprotocols_enable)) {
        $wsprotocols = array_merge($wsprotocols, (array) $CONFSEED->wsprotocols_enable);
    }
    if (isset($CONFSEED->wsprotocols_disable)) {
        $wsprotocols = array_diff($wsprotocols, (array) $CONFSEED->wsprotocols_disable);
    }
    set_config('webserviceprotocols', implode(',', $wsprotocols));

    // Forcibly set some settings.
    if (isset($CONFSEED->settings) ) {
        foreach ($CONFSEED->settings as $key => $value) {
            set_config($key, $value);
        }
    }

    // Forcibly set some plugin settings, only if moodle is installed.
    if ($dbman->table_exists('config_plugins') and isset($CONFSEED->plugin_settings)) {
        foreach ($CONFSEED->plugin_settings as $plugin => $settings) {
            foreach ($settings as $key => $value) {
                // Try to upload the file anyway; if that fails, set the config.
                if (local_confseed_admin_settings_set_file($plugin, $key, $value) === false) {
                    set_config($key, $value, $plugin);
                }
            }
        }
    }

    // Activate/Deactivate a Filter plugin by default.
    if (isset($CONFSEED->filter_activation)) {
        foreach ($CONFSEED->filter_activation as $plugin => $setting) {
            filter_set_global_state($plugin, $setting);
        }
    }

    // Install language packs.
    if (isset($CONFSEED->languages) && is_array($CONFSEED->languages)) {
        get_string_manager()->reset_caches();
        $controller = new tool_langimport\controller();
        core_php_time_limit::raise();
        $controller->install_languagepacks($CONFSEED->languages);
        get_string_manager()->reset_caches();
    }

    // An upgrade_plugin_savepoint call is not needed here as upgradelib.php's upgrade_plugins() will do it for us.
    return true;
}
