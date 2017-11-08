# local/confseed - A Moodle settings enforcer for custom developments

This plugin allows the automated setup of various configurations that are usually hard to setup through `config.php`.

# Syntax

It uses a special attribute within the `$CFG` configuration variable: `$CFG->CONFSEED`, which is a `stdClass` in which attributes can be set to be enforced at upgrade time:

* `version` This will be used as the `local/confseed` plugin version. Only changes to that field will trigger new configuration enforcements.
* `user_info_categories` is an `array` of `stdClass` `$DB` descriptors for the `{user_info_category}` database table, *which keys are the table `id`s*.
 * Mandatory fields:
  * `name`
* `user_info_fields` is an `array` of `stdClass` `$DB` descriptors for the `{user_info_field}` database table, *which keys are the table `shortname`s* please refer to {{user/profile/field/*/define.class.php` for the various attributes' usages.
 * Mandatory fields:
  * `name`
  * `datatype`
