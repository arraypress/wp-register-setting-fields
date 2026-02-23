# Registration Options

```php
register_setting_fields( 'my_plugin', [
    // Menu
    'page_title'    => 'My Plugin Settings',
    'menu_title'    => 'My Plugin',
    'menu_slug'     => 'my-plugin',          // Defaults to settings ID
    'capability'    => 'manage_options',
    'parent_slug'   => '',                   // Empty = top-level menu page
    'icon'          => 'dashicons-admin-generic',  // Top-level only
    'position'      => null,                 // Top-level only
    'body_class'    => '',                   // Extra body class on the page

    // Storage
    'option_name'   => 'my_plugin',          // Defaults to settings ID
    'option_group'  => 'my_plugin_group',    // Defaults to ID + '_group'

    // Branded header (see Branded Header page)
    'logo'          => '',
    'header_title'  => '',
    'header_badge'  => '',

    // Tabs (see Tabs page)
    'tabs' => [],

    // Sections (see Sections page)
    'sections' => [],

    // Fields (see Field Types)
    'fields' => [],

    // Submit button
    'submit_button' => true,                 // Set false to hide

    // Reset button (see Reset & Export/Import page)
    'reset_button'  => false,                // Set true to show

    // Export/Import (see Reset & Export/Import page)
    'export_import' => false,                // Set true to enable

    // Help screen (see Help Tabs page)
    'help_tabs'     => [],
    'help_sidebar'  => '',

    // Encryption (see Encryption page)
    'encryption' => [
        'enabled' => null,                   // Auto-detected from fields
        'key'     => null,                   // Custom key, or uses WP salts
        'prefix'  => '',                     // Defaults to settings ID
    ],
] );
```

## Top-Level vs Submenu

When `parent_slug` is empty, a top-level menu page is created with `add_menu_page()`. Set `parent_slug` to nest under an existing menu:

```php
// Under Settings
'parent_slug' => 'options-general.php',

// Under Tools
'parent_slug' => 'tools.php',

// Under a custom top-level page
'parent_slug' => 'my-plugin-dashboard',
```

The library automatically fixes parent/submenu highlighting when using `parent_slug`.
