# WordPress Register Setting Fields

Settings pages for the WordPress admin, from a field configuration.

Registration, rendering, sanitization and the Settings API are handled; you describe the fields. Every type comes from
[wp-field-kit](https://github.com/arraypress/wp-field-kit), so the same configuration renders the same control here, in
a metabox, on a term screen or in a flyout.

## Features

- Every field type the kit has — sixty-odd, from text to a repeater to an email editor
- Tabs and sections, with the tab remembered in the URL
- A branded header with a logo, badge and whatever you want beside it
- Conditional fields, encrypted values, and constants that override an option
- Reset, export and import, when you want them
- Values readable through helpers that know what a field meant, not just what it stored

## Requirements

- PHP 7.4+
- WordPress 5.8+

## Installation

```bash
composer require arraypress/wp-register-setting-fields
```

## Quick start

```php
register_setting_fields( 'my_plugin', [
    'page_title'  => __( 'My Plugin Settings', 'my-plugin' ),
    'menu_title'  => __( 'My Plugin', 'my-plugin' ),
    'parent_slug' => 'options-general.php',
    'fields'      => [
        'site_name'      => [ 'type' => 'text', 'label' => __( 'Site name', 'my-plugin' ) ],
        'enable_feature' => [ 'type' => 'toggle', 'label' => __( 'Enable feature', 'my-plugin' ) ],
        'items_per_page' => [
            'type'  => 'number',
            'label' => __( 'Items per page', 'my-plugin' ),
            'min'   => 1,
            'max'   => 100,
        ],
    ],
] );
```

Reading them back:

```php
$name = get_setting_field_value( 'my_plugin', 'site_name' );

if ( is_setting_on( 'my_plugin', 'enable_feature' ) ) {
    // ...
}
```

## Registration

```php
register_setting_fields( 'my_plugin', [
    'page_title'    => __( 'My Plugin Settings', 'my-plugin' ),
    'menu_title'    => __( 'My Plugin', 'my-plugin' ),
    'menu_slug'     => 'my-plugin',
    'parent_slug'   => 'options-general.php',
    'capability'    => 'manage_options',
    'option_name'   => 'my_plugin',
    'icon'          => 'dashicons-admin-generic',
    'position'      => null,
    'submit_button' => true,
    'reset_button'  => false,
    'export_import' => false,
    'tabs'          => [ /* ... */ ],
    'sections'      => [ /* ... */ ],
    'fields'        => [ /* ... */ ],
] );
```

| Key | What it does |
| --- | --- |
| `page_title` / `menu_title` | The page's heading and its menu entry. |
| `menu_slug` | The page slug. Defaults to the id. |
| `parent_slug` | Where it sits. A top-level menu when empty. |
| `capability` | Who may see it. Defaults to `manage_options`. |
| `option_name` | The option every value is stored in. Defaults to the id. |
| `option_group` | The Settings API group. Defaults to `{id}_group`. |
| `constant_prefix` | Constants that override stored values. Defaults to `{option_name}_`. |
| `header_title`, `logo`, `badge` | A branded header instead of the plain one. |
| `icon`, `position` | Menu icon and position, for a top-level page. |
| `submit_button` | Whether to draw one. |
| `reset_button` | A "restore defaults" button. |
| `export_import` | Buttons to download and upload the settings as JSON. |
| `help_tabs`, `help_sidebar` | Contextual help, in core's own panel. |

## Tabs and sections

A field says which tab and which section it belongs to. Both are optional and can be mixed.

```php
register_setting_fields( 'my_plugin', [
    'tabs'   => [
        'general'  => __( 'General', 'my-plugin' ),
        'advanced' => __( 'Advanced', 'my-plugin' ),
    ],
    'sections' => [
        'branding' => [
            'title'       => __( 'Branding', 'my-plugin' ),
            'description' => __( 'How it looks.', 'my-plugin' ),
        ],
    ],
    'fields' => [
        'site_name' => [
            'type'    => 'text',
            'label'   => __( 'Site name', 'my-plugin' ),
            'tab'     => 'general',
            'section' => 'branding',
        ],
        'debug' => [
            'type'  => 'toggle',
            'label' => __( 'Debug mode', 'my-plugin' ),
            'tab'   => 'advanced',
        ],
    ],
] );
```

## Conditional fields

```php
'fields' => [
    'mode'    => [
        'type'    => 'select',
        'label'   => __( 'Mode', 'my-plugin' ),
        'options' => [ 'auto' => __( 'Automatic', 'my-plugin' ), 'manual' => __( 'Manual', 'my-plugin' ) ],
    ],
    'api_key' => [
        'type'    => 'password',
        'label'   => __( 'API key', 'my-plugin' ),
        'depends' => [ 'mode' => 'manual' ],
    ],
],
```

The field is removed from the tab order when hidden, not merely made invisible.

## Encrypted values

```php
'api_secret' => [
    'type'      => 'password',
    'label'     => __( 'API secret', 'my-plugin' ),
    'encrypted' => true,
],
```

Stored encrypted, refused REST exposure, and decrypted on the way back out through `get_setting_field_value()`.

## Constants

Any value can be overridden by a constant, which is how a secret stays out of the database on a production install.

```php
// In wp-config.php:
define( 'MY_PLUGIN_API_KEY', 'sk-live-...' );
```

`get_setting_field_value( 'my_plugin', 'api_key' )` returns the constant, and the field renders read-only with a note
saying where the value came from.

## Reading values

| Function | Returns |
| --- | --- |
| `get_setting_field_value( $id, $key, $fallback = null )` | One value, with its default and any constant applied. |
| `get_all_setting_values( $id )` | Everything, keyed by field. |
| `is_setting_on( $id, $key )` | Whether a toggle or checkbox is on. |
| `is_setting_enabled( $id, $key, $option )` | Whether an option is among a multiple field's values. |
| `get_setting_field_list( $id, $key )` | A list field's values as an array. |
| `get_setting_field_ids( $id, $key )` | A relational field's ids, as integers. |
| `get_setting_field_page_url( $id, $key )` | The permalink of a page a field points at. |
| `is_setting_field_page( $id, $key )` | Whether the current request is that page. |
| `update_setting_field_value( $id, $key, $value )` | Write one, sanitized by its own type. |
| `delete_setting_field_value( $id, $key )` | Remove one, so it falls back to its default. |

Email and licence fields have their own: `get_setting_fields_email()`, `is_setting_fields_email_enabled()`,
`get_setting_field_license_status()`, `is_setting_field_license_active()`.

## Custom sanitization

A field's type sanitizes it. Supply a callback to replace that entirely:

```php
'slug' => [
    'type'              => 'text',
    'label'             => __( 'Slug', 'my-plugin' ),
    'sanitize_callback' => fn( $value ) => sanitize_title( $value ),
],
```

The callback receives the raw value, not one the type has already cleaned.

## License

GPL-2.0-or-later
