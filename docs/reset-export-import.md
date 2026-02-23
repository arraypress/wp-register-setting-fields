# Reset & Export/Import

## Reset Button

Adds a "Reset to Defaults" button next to Submit. When tabs are present, only fields on the current tab are reset.

```php
register_setting_fields( 'my_plugin', [
    'reset_button' => true,
    'tabs'         => [
        'general'  => 'General',
        'advanced' => 'Advanced',
    ],
    'fields'       => [
        'site_name' => [
            'type'    => 'text',
            'label'   => 'Site Name',
            'default' => 'My Site',
            'tab'     => 'general',
        ],
        'cache_ttl' => [
            'type'    => 'number',
            'label'   => 'Cache TTL',
            'default' => 3600,
            'tab'     => 'advanced',
        ],
    ],
] );
```

When viewing the General tab, clicking reset restores only `site_name` to `'My Site'`. The Advanced tab's `cache_ttl` is untouched.

A confirm dialog is shown before any reset occurs.

## Export/Import

Adds an Export/Import panel below the settings form. Export downloads a JSON file; import reads one back.

```php
register_setting_fields( 'my_plugin', [
    'export_import' => true,
    'fields'        => [ /* ... */ ],
] );
```

### Export

Clicking **Export Settings** downloads a JSON file named `{settings_id}-settings-{date}.json`:

```json
{
    "settings_id": "my_plugin",
    "version": 1,
    "exported_at": "2025-06-15 10:30:00",
    "data": {
        "site_name": "My Site",
        "cache_ttl": 3600,
        "api_key": "sk-live-abc123"
    }
}
```

Encrypted fields are exported **decrypted** so the file works across environments with different encryption keys.

Fields skipped during export: layout fields (`message`, `html`, `separator`, `heading`), `hidden`, `action_button`, `clipboard`, and any field whose value comes from a PHP constant.

### Import

Clicking **Import Settings** opens a file picker. After selecting a `.json` file, a confirm dialog is shown. On confirm:

1. The `settings_id` in the file is validated against the current settings page
2. Each value is run through the standard field sanitizer
3. Encrypted fields are re-encrypted with the current environment's key
4. Values are merged with existing options (fields not in the file are preserved)
5. The page reloads

### Both Together

```php
register_setting_fields( 'my_plugin', [
    'reset_button'  => true,
    'export_import' => true,
    'fields'        => [ /* ... */ ],
] );
```

### REST Endpoints

| Method | Route                        | Description                  |
|--------|------------------------------|------------------------------|
| POST   | `/setting-fields/v1/reset`   | Reset fields to defaults     |
| GET    | `/setting-fields/v1/export`  | Export settings as JSON      |
| POST   | `/setting-fields/v1/import`  | Import settings from JSON    |

All three require `manage_options` capability (or the filtered equivalent).
