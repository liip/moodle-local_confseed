# local/confseed - A Moodle settings enforcer for custom developments

This plugin allows the automated setup of various configurations that are usually hard to setup through `config.php`.

# Syntax

It uses a special attribute within the `$CFG` configuration variable: `$CFG->CONFSEED`, which is a `stdClass` in which attributes can be set to be enforced at upgrade time:

* `version` This will be used as the `local/confseed` plugin version. Only changes to that field will trigger new configuration enforcements.
* `settings` is an `array` whose keys are the `$CFG->` settings that need to be set to the provided values
* `user_info_categories` is an `array` of `stdClass` `$DB` descriptors for the `{user_info_category}` database table, *which keys are codename for the below `user_info_fields`*.
 * Mandatory fields:
  * `id`
  * `name`
* `user_info_fields` is an `array` of `stdClass` `$DB` descriptors for the `{user_info_field}` database table, *which keys are the table `shortname`s*. Please refer to `user/profile/field/*/define.class.php` for the various attributes' usages.
 * Mandatory fields:
  * `name`
  * `datatype`
* `auth_enable` is an `array` of authentication plugins (without `auth/` nor `auth_` prefixes) that need to be forcibly *enabled*
* `auth_disable` is an `array` of authentication plugins (without `auth/` nor `auth_` prefixes) that need to be forcibly *disabled*


# `config.php` example
```php
$CFG->CONFSEED = new stdClass();
$CFG->CONFSEED->version = 2017110800;
// Set some values.
$CFG->CONFSEED->settings = array(
  'theme' => 'boost',
);
// Create user profile categories.
$CFG->CONFSEED->user_info_categories = array(
  'food' => (object) array(
    'id' => 1,
    'name' => 'Alimentary restrictions',
    'sortorder' => 1,
  ),
);

// Create user profile fields.
$CFG->CONFSEED->user_info_fields = array(
  'meat' => (object) array(
    'category' => 'food',
    'name' => 'I eat meat',
    'required' => false,
    'signup' => true,
    'datatype' => 'checkbox',
    'defaultdata' => false
  ),
  'freetext' => (object) array(
    'category' => 'food',
    'name' => 'Comments …',
    'signup' => true,
    'datatype' => 'text',
    'param1' => 60, // Display size.
    'param2' => 512, // Maximum length.
  ),
);
// Enable or disable certain authentications.
$CFG->CONFSEED->auth_enable = ['cas', ];
$CFG->CONFSEED->auth_disable = ['email', ];
```
