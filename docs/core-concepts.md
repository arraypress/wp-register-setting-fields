# Core Concepts

## How It Works

1. You call `register_setting_fields()` with an ID and config array
2. The library registers an admin menu page, settings group, and option
3. Fields are rendered inside a standard `form-table` layout using the Settings API
4. On save, each field is sanitized by type and stored as a single option array
5. You retrieve values with `get_setting_field_value()` or the typed helpers

## Option Storage

All fields for a settings page are stored in a single WordPress option as a key-value array. The option name defaults to the settings ID:

```php
register_setting_fields( 'my_plugin', [...] );

// Stored as: get_option( 'my_plugin' )
// Returns: [ 'site_name' => 'My Site', 'enable_feature' => true, ... ]
```

You can override the option name via the `option_name` config key.

## Settings ID

The ID passed to `register_setting_fields()` serves as the unique identifier for the settings page. It's used for the menu slug, option name, option group, body class, and REST API routing (all customizable).

## Field Keys

Each field's array key becomes its storage key in the option array and its input name. The key is also used to auto-generate a label if none is provided:

```php
'fields' => [
    'api_key' => [                    // Stored as $options['api_key']
        'type'  => 'text',            // Label auto-generated: "Api key"
        'label' => 'API Key',         // Or set explicitly
    ],
],
```

## Data Flow

1. WordPress loads the settings page
2. Current values are loaded from `get_option()` (with decryption if applicable)
3. Fields are rendered with their current values
4. Form submits to `options.php` (standard Settings API)
5. `sanitize_settings()` runs your sanitizers per field type
6. WordPress saves the sanitized array via `update_option()`

## Assets

The library automatically enqueues only the assets needed based on which field types you use. Select2 is loaded for AJAX fields, `wp-color-picker` for color fields, `wp_enqueue_media()` for image/file/gallery fields, and so on. Assets are only loaded on the settings page itself.
