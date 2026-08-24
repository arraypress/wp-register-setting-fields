# Registration Options

```php
register_setting_fields( 'my_plugin', [
    // Menu
    'page_title'      => 'My Plugin Settings',
    'menu_title'      => 'My Plugin',
    'menu_slug'       => 'my-plugin',        // Defaults to the settings ID
    'capability'      => 'manage_options',
    'parent_slug'     => '',                 // Empty = top-level menu page
    'icon'            => 'dashicons-admin-generic',  // Top-level only
    'position'        => null,               // Top-level only

    // Storage
    'option_name'     => 'my_plugin',        // Defaults to the settings ID
    'option_group'    => 'my_plugin_group',  // Defaults to ID + '_group'
    'constant_prefix' => 'my_plugin_',       // Defaults to option name + '_'

    // Header (see Header page)
    'header_title'    => '',                 // Defaults to page_title
    'logo'            => '',
    'badge'           => '',

    // Tabs (see Tabs page)
    'tabs'            => [],

    // Sections (see Sections page)
    'sections'        => [],

    // Fields (see Field Types)
    'fields'          => [],

    // Submit button
    'submit_button'   => true,               // Set false to hide

    // Reset button (see Reset & Export/Import page)
    'reset_button'    => false,              // Set true to show

    // Export/Import (see Reset & Export/Import page)
    'export_import'   => false,              // Set true to enable

    // Help screen (see Help Tabs page)
    'help_tabs'       => [],
    'help_sidebar'    => '',
] );
```

Encryption has no page-level configuration — a field asks for it with `'encrypted' => true`. See [Encryption](advanced/encryption.md).

## Top-Level vs Submenu

When `parent_slug` is empty a top-level page is created with `add_menu_page()`. Set `parent_slug` to nest under an existing menu:

```php
// Under Settings
'parent_slug' => 'options-general.php',

// Under Tools
'parent_slug' => 'tools.php',

// Under a custom post type
'parent_slug' => 'edit.php?post_type=my_type',

// Under a custom top-level page
'parent_slug' => 'my-plugin-dashboard',
```

## When Registration Happens

The Settings API registration happens at construction, not on `admin_init`. That is deliberate: the registered sanitize callback is the gate **every** write to the option passes through, including one from a cron job or a webhook, and a gate that is only in place on admin screens is not one.

The menu and the assets are still hooked as normal, so registering on `plugins_loaded` or `init` is fine.
