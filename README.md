# local/confseed - A Moodle settings enforcer for custom developments

This plugin allows the automated setup of various configurations that are usually hard to setup through `config.php`.

# Syntax

It uses a special file `config-seed.php` containing only the `$CONFSEED` configuration variable, which is a `stdClass` in which attributes can be set to be enforced at upgrade time:

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
* `enrol_enable` is an `array` of enrolment plugins (without `enrol/` nor `enrolq_` prefixes) that need to be forcibly *enabled*
* `enrol_disable` is an `array` of enrolment plugins (without `enrol/` nor `enrol_` prefixes) that need to be forcibly *disabled*
* `uninstall_plugins` is an `array` of full plugin names (such as `theme_oldmamma`) to be uninstalled. If set, all pre-2.7 themes are also uninstalled
* `filter_activation` is an `array` of plugin names (such as `geshi`) to be *activated/deactivated*.


# `config-seed.php` example
```php
$CONFSEED = new stdClass();
$CONFSEED->version = 2017110800;
// Set some values.
$CONFSEED->settings = array(
  'theme' => 'boost',
  'enablewebservices' => 1,
);
// Set some plugin values (like 'moodlecourse | format')
$CONFSEED->plugin_settings = array(
  'moodlecourse' => array (
    'format' => 'weeks'
  )
);
// Uninstall certain plugins; all the pre-2.7 themes are uninstalled forcibly if the variable is set.
$CONFSEED->uninstall_plugins = array(
    'theme_oldmamma',
);
// Create user profile categories.
$CONFSEED->user_info_categories = array(
  'food' => (object) array(
    'id' => 1,
    'name' => 'Alimentary restrictions',
    'sortorder' => 1,
  ),
);

// Create user profile fields.
$CONFSEED->user_info_fields = array(
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
$CONFSEED->auth_enable = ['cas', ];
$CONFSEED->auth_disable = ['email', ];
// Enable or disable certain enrolment methods.
$CONFSEED->enrol_enable = ['database', ];
$CONFSEED->enrol_disable = ['self', ];
// Show or hide certain activity modules.
$CONFSEED->mod_show = ['assignment', ];
$CONFSEED->mod_hide = ['book', ];
// Enable or disable certain webservices protocols.
$CONFSEED->wsprotocols_enable = ['rest', ];
$CONFSEED->wsprotocols_disable = ['soap', ];

// Activate/Deactivate a Filter plugin by default.
$CONFSEED->filter_activation = [
    'geshi' => '1',
    'algebra' => '0'
];

```

# Limitations

Due to Moodle Core forcibly setting the `registerauth` setting upon install, it is not possible for `local_confseed` to seed this variable upon install. It does work upon upgrade though.
